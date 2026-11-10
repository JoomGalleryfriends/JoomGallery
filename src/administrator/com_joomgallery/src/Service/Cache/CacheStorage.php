<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Cache;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\CacheHelper;
use Joomla\CMS\Factory;

/**
 * Shared request state and session storage for component caches
 *
 * Loads session data lazily and preserves the revision associated with each
 * cached snapshot. Session changes are staged immediately in Joomla's in-memory
 * session; Joomla writes its backend when the session closes. This also covers
 * redirects and exit(), which can bypass dispatcher completion and finally.
 * Request-only and shared guest caching do not open a session.
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
class CacheStorage
{
  /**
   * Shared guest backend adapter
   *
   * @var     GuestCacheStorage
   * @since   4.5.0
   */
  private GuestCacheStorage $guestStorage;

  /**
   * Shared namespaces and their maximum entry lifetimes
   *
   * @var     array
   * @since   4.5.0
   */
  private array $sharedNamespaces = [];

  /**
   * Shared keys already read, including misses, during this request
   *
   * @var     array
   * @since   4.5.0
   */
  private array $sharedReads = [];

  /**
   * Revisions captured when a shared calculation starts
   *
   * @var     array
   * @since   4.5.0
   */
  private array $readRevisions = [];

  /**
   * Last maintenance signal inspected during this request
   *
   * @var     string|null
   * @since   4.5.0
   */
  private ?string $maintenanceRevision = null;

  /**
   * Component-owned revision store used to validate session snapshots
   *
   * @var     CacheRevision
   * @since   4.5.0
   */
  private CacheRevision $revisionStore;

  /**
   * Request-only entries indexed by namespace and cache key
   *
   * @var     array
   *
   * @since   4.5.0
   */
  private array $requestCaches = [];

  /**
   * Session-backed runtime entries indexed by namespace and key
   *
   * @var     array
   *
   * @since   4.5.0
   */
  private array $runtimeCaches = [];

  /** @var array Request-only hit counters in least-recently-used order. */
  private array $requestUsage = [];

  /** @var array Persistent hit counters in least-recently-used order. */
  private array $runtimeUsage = [];

  /** @var array Namespace timestamps, preserved when only usage changes. */
  private array $cacheCreated = [];

  /**
   * Namespaces already loaded during this request
   *
   * @var     array<string, bool>
   *
   * @since   4.5.0
   */
  private array $loadedCaches = [];

  /**
   * Namespaces with changes awaiting session persistence
   *
   * @var     array<string, bool>
   *
   * @since   4.5.0
   */
  private array $dirtyCaches = [];

  /**
   * Revisions associated with the loaded namespace snapshots
   *
   * @var     array<string, string|null>
   *
   * @since   4.5.0
   */
  private array $cacheRevisions = [];

  /**
   * Shared revision scopes registered for each namespace
   *
   * @var     array<string, string|null>
   *
   * @since   4.5.0
   */
  private array $scopes = [];

  /**
   * Scope revisions already observed by this storage instance
   *
   * @var     array<string, string>
   *
   * @since   4.5.0
   */
  private array $scopeRevisions = [];

  /**
   * Factory returning the session when persistence is needed
   *
   * @var     \Closure
   *
   * @since   4.5.0
   */
  private \Closure $sessionProvider;

  /**
   * Initialises lazy access to the current session
   *
   * The supplied closure must return an object exposing the session get() and
   * set() methods.
   *
   * @param   CacheRevision  $revisionStore  the shared component revision store
   * @param   \Closure|null  $sessionProvider  the session factory, or null to use the current application
   * @param   GuestCacheStorage|null  $guestStorage  optional shared guest backend
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function __construct(CacheRevision $revisionStore, ?\Closure $sessionProvider = null, ?GuestCacheStorage $guestStorage = null)
  {
    $this->guestStorage    = $guestStorage ?? new GuestCacheStorage();
    $this->revisionStore   = $revisionStore;
    $this->sessionProvider = $sessionProvider ?? static fn() => Factory::getApplication()->getSession();
  }

  /**
   * Associates a namespace with a shared invalidation scope
   *
   * Rejects attempts to register the same namespace with a conflicting scope.
   *
   * @param   string       $namespace  the namespace identifying the cache entries
   * @param   string|null  $scope      the shared revision scope, or null for an unscoped cache
   *
   * @return  void
   *
   * @throws  \LogicException  If the namespace has a conflicting scope.
   * @since   4.5.0
   */
  public function register(string $namespace, ?string $scope): void
  {
    if(\array_key_exists($namespace, $this->scopes) && $this->scopes[$namespace] !== $scope)
    {
      throw new \LogicException('Conflicting cache scope for namespace: ' . $namespace);
    }

    $this->scopes[$namespace] = $scope;
  }

  /**
   * Retires loaded entries after a local scope revision changes
   *
   * Clears request and session-backed runtime entries in the affected scope.
   * External changes are observed through the next request revision lookup.
   *
   * @param   string  $namespace  the namespace identifying the cache entries
   *
   * @return  string|null
   *
   * @since   4.5.0
   */
  private function synchronise(string $namespace): ?string
  {
    $scope = $this->scopes[$namespace] ?? null;

    if($scope === null)
    {
      return null;
    }

    $revision = $this->revisionStore->get($scope);

    if(isset($this->scopeRevisions[$scope]) && $this->scopeRevisions[$scope] !== $revision)
    {
      foreach($this->scopes as $name => $registeredScope)
      {
        if($registeredScope !== $scope) continue;

        unset($this->requestCaches[$name], $this->requestUsage[$name], $this->sharedReads[$name]);

        if(isset($this->loadedCaches[$name]))
        {
          $this->runtimeCaches[$name]  = [];
          $this->runtimeUsage[$name]   = [];
          $this->cacheRevisions[$name] = $revision;
          $this->dirtyCaches[$name]    = true;
        }
      }
    }

    $this->scopeRevisions[$scope] = $revision;

    return $revision;
  }

  /**
   * Registers the namespace and loads eligible session entries
   *
   * Expired or outdated session entries are ignored. Repeated initialisation
   * reuses the loaded request state.
   *
   * @param   string  $namespace  the namespace identifying the cache entries
   * @param   int     $maxAge     the maximum namespace age in seconds; zero disables expiration
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function initialise(string $namespace, int $maxAge = 0): void
  {
    $revision = $this->synchronise($namespace);

    if(isset($this->loadedCaches[$namespace])) return;

    if(isset($this->sharedNamespaces[$namespace]))
    {
      $this->runtimeCaches[$namespace]  = [];
      $this->runtimeUsage[$namespace]   = [];
      $this->loadedCaches[$namespace]   = true;
      $this->cacheRevisions[$namespace] = $revision;

      return;
    }

    $this->maintainSessionCaches();
    $stored = ($this->sessionProvider)()->get($namespace, []);
    $items  = [];

    if(\is_array($stored) && isset($stored['items']) && \is_array($stored['items'])
      && ($revision === null || (isset($stored['revision']) && (string) $stored['revision'] === $revision)))
    {
      $created = (int) ($stored['created'] ?? 0);

      if($maxAge === 0 || ($created > 0 && $created + $maxAge >= time()))
      {
        $items = $stored['items'];
      }
    }
    elseif($revision === null && \is_array($stored) && $maxAge === 0)
    {
      // Compatibility for older unscoped session caches.
      $items = $stored;
    }

    $this->runtimeUsage[$namespace]   = CacheHelper::reconcileUsage($items, \is_array($stored['usage'] ?? null) ? $stored['usage'] : []);
    $this->cacheCreated[$namespace]   = (int) ($stored['created'] ?? time());
    $this->runtimeCaches[$namespace]  = $items;
    $this->loadedCaches[$namespace]   = true;
    $this->cacheRevisions[$namespace] = $revision;

    if($revision !== null && (!isset($stored['revision']) || (string) $stored['revision'] !== $revision))
    {
      $this->dirtyCaches[$namespace] = true;
      $this->persist($namespace);
    }
  }

  /**
   * Returns the current revision after synchronising local entries
   *
   * Returns null for an unscoped namespace.
   *
   * @param   string  $namespace  the namespace identifying the cache entries
   *
   * @return  string|null
   *
   * @since   4.5.0
   */
  public function revision(string $namespace): ?string
  {
    return $this->synchronise($namespace);
  }

  /**
   * Checks for an entry without treating null as a cache miss
   *
   * @param   string  $namespace    the namespace identifying the cache entries
   * @param   string  $key          the cache entry key
   * @param   bool    $requestOnly  whether to use request-only storage
   *
   * @return  bool
   *
   * @since   4.5.0
   */
  public function has(string $namespace, string $key, bool $requestOnly): bool
  {
    $this->synchronise($namespace);

    if($requestOnly)
    {
      return \array_key_exists($key, $this->requestCaches[$namespace] ?? []);
    }

    $this->initialise($namespace);

    if(isset($this->sharedNamespaces[$namespace]) && !isset($this->sharedReads[$namespace][$key]))
    {
      $revision                                = $this->cacheRevisions[$namespace];
      $this->readRevisions[$namespace][$key] ??= $revision;
      $entry                                   = $this->guestStorage->get($this->scopes[$namespace], $revision, $namespace, $key, false);
      $this->sharedReads[$namespace][$key]     = true;

      if($entry !== null)
      {
        $this->runtimeCaches[$namespace][$key] = $entry['value'];
        $this->runtimeUsage[$namespace][$key]  = ['hits' => 1, 'expires' => $entry['expires']];
      }
    }

    return \array_key_exists($key, $this->runtimeCaches[$namespace]);
  }

  /**
   * Returns a valid cached value and records usage unless explicitly suppressed
   *
   * @param   string  $namespace    the namespace identifying the cache entries
   * @param   string  $key          the cache entry key
   * @param   mixed   $default      the value returned when the entry is absent
   * @param   bool    $requestOnly  whether to use request-only storage
   * @param   bool    $countHit     whether this retrieval counts as use
   *
   * @return  mixed
   *
   * @since   4.5.0
   */
  public function get(string $namespace, string $key, mixed $default, bool $requestOnly, bool $countHit = true): mixed
  {
    if(!$this->has($namespace, $key, $requestOnly)) return $default;

    $value = $requestOnly ? $this->requestCaches[$namespace][$key] : $this->runtimeCaches[$namespace][$key];

    if(CacheHelper::isExpired($value) || (!$requestOnly && CacheHelper::isExpired($this->runtimeUsage[$namespace][$key] ?? []))) return $default;

    if($countHit)
    {
      if($requestOnly)
      {
        CacheHelper::recordHit($this->requestUsage[$namespace], $key);
      }
      else
      {
        CacheHelper::recordHit($this->runtimeUsage[$namespace], $key);

        if(isset($this->sharedNamespaces[$namespace]))
        {
          $this->guestStorage->touch($this->scopes[$namespace], $this->cacheRevisions[$namespace], $namespace, $key);
        }
        else
        {
          $this->dirtyCaches[$namespace] = true;
          $this->persist($namespace, true);
        }
      }
    }

    return $value;
  }

  /**
   * Stores an entry and enforces expired-first LFU eviction with aging
   *
   * New entries start at one hit. Full-cache insertions remove expired entries,
   * divide surviving hit counts by 1.5 (floored, minimum one), then evict the
   * least frequently used entry, breaking ties by least recent retrieval.
   *
   * @param   string  $namespace    the namespace identifying the cache entries
   * @param   string  $key          the cache entry key
   * @param   mixed   $value        the value to store
   * @param   int     $limit        the maximum entry count; zero means no limit
   * @param   bool    $requestOnly  whether to use request-only storage
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function put(string $namespace, string $key, mixed $value, int $limit, bool $requestOnly): void
  {
    $this->synchronise($namespace);

    if($requestOnly)
    {
      $this->requestCaches[$namespace] ??= [];
      $items                             =& $this->requestCaches[$namespace];
      $this->requestUsage[$namespace]  ??= [];
      $usage                             =& $this->requestUsage[$namespace];
    }
    else
    {
      $this->initialise($namespace);
      $items                         =& $this->runtimeCaches[$namespace];
      $usage                         =& $this->runtimeUsage[$namespace];
      $this->dirtyCaches[$namespace] = true;
    }

    if(isset($this->sharedNamespaces[$namespace]))
    {
      $captured = $this->readRevisions[$namespace][$key] ?? $this->cacheRevisions[$namespace];
      // Never publish a calculation under a revision observed after it began.
      $this->guestStorage->put($this->scopes[$namespace], $captured, $namespace, $key, $value, $this->sharedNamespaces[$namespace]);
      unset($this->readRevisions[$namespace][$key]);

      if($captured !== $this->cacheRevisions[$namespace]) return;
      $this->sharedReads[$namespace][$key] = true;
    }

    $usage = CacheHelper::reconcileUsage($items, $usage);

    $expired = \array_key_exists($key, $items) && (CacheHelper::isExpired($items[$key]) || CacheHelper::isExpired($usage[$key]));
    $full    = (!\array_key_exists($key, $items) || $expired) && $limit > 0 && \count($items) >= $limit;

    if($expired)
    {
      unset($items[$key], $usage[$key]);
    }

    if($full)
    {
      foreach($items as $oldKey => $entry)
      {
        if(CacheHelper::isExpired($entry) || CacheHelper::isExpired($usage[$oldKey]))
        {
          unset($items[$oldKey], $usage[$oldKey], $this->sharedReads[$namespace][$oldKey]);
        }
      }
      $usage = CacheHelper::reconcileUsage($items, $usage);
      CacheHelper::ageUsage($usage);

      while(\count($items) >= $limit)
      {
        $oldKey = CacheHelper::evictionKey($usage);
        unset($items[$oldKey], $usage[$oldKey], $this->sharedReads[$namespace][$oldKey]);
      }
    }

    $items[$key]   = $value;
    $usage[$key] ??= ['hits' => 1];

    // A replacement has a new value lifetime; do not retain an old guest envelope expiry.
    unset($usage[$key]['expires']);

    // A reduced limit also applies to replacements, without aging on a write.
    while($limit > 0 && \count($items) > $limit)
    {
      $oldKey = CacheHelper::evictionKey($usage);
      unset($items[$oldKey], $usage[$oldKey], $this->sharedReads[$namespace][$oldKey]);
    }

    // Stage the bounded snapshot before a redirect, close() or exit can end the request.
    if(!$requestOnly) $this->persist($namespace);
  }

  /**
   * Removes local entries or invalidates a shared revision scope
   *
   * Session-backed scoped caches invalidate the entire scope even when a
   * pattern is supplied. Request-only and unscoped caches support local
   * pattern removal.
   *
   * @param   string        $namespace     the namespace identifying the cache entries
   * @param   string|false  $pattern       the key-matching regular expression, or false for all entries
   * @param   bool          $decodeBase64  whether to decode keys before pattern matching
   * @param   bool          $requestOnly   whether to use request-only storage
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function remove(string $namespace, string|false $pattern, bool $decodeBase64, bool $requestOnly): void
  {
    $scope = $this->scopes[$namespace] ?? null;

    if(!$requestOnly && $scope !== null)
    {
      $this->revisionStore->invalidate($scope);
      $this->synchronise($namespace);
      $this->initialise($namespace);
      $this->persistAll($scope);

      return;
    }

    if($requestOnly)
    {
      $this->requestCaches[$namespace] ??= [];
      $items                             =& $this->requestCaches[$namespace];
      $this->requestUsage[$namespace]  ??= [];
      $usage                             =& $this->requestUsage[$namespace];
    }
    else
    {
      $this->initialise($namespace);
      $items =& $this->runtimeCaches[$namespace];
      $usage =& $this->runtimeUsage[$namespace];
    }

    if($pattern === false)
    {
      $items = [];
    }
    elseif(@preg_match($pattern, '') !== false)
    {
      foreach(array_keys($items) as $key)
      {
        $matchKey = $decodeBase64 ? base64_decode((string) $key) : (string) $key;

        if(preg_match($pattern, $matchKey)) unset($items[$key]);
      }
    }

    $usage = CacheHelper::reconcileUsage($items, $usage);

    if(!$requestOnly)
    {
      $this->dirtyCaches[$namespace] = true;
      $this->persist($namespace);
    }
  }

  /**
   * Removes rejected session entries and enforces the entry limit
   * This is local maintenance and does not increment a shared revision.
   *
   * @param   string    $namespace  the namespace identifying the cache entries
   * @param   callable  $keep       the callback accepting an entry and returning true to retain it
   * @param   int       $limit      the maximum entry count; zero means no limit
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function prune(string $namespace, callable $keep, int $limit): void
  {
    $this->initialise($namespace);

    foreach($this->runtimeCaches[$namespace] as $key => $entry)
    {
      if(!$keep($entry))
      {
        unset($this->runtimeCaches[$namespace][$key]);
        $this->dirtyCaches[$namespace] = true;
      }
    }

    $this->runtimeUsage[$namespace] = CacheHelper::reconcileUsage($this->runtimeCaches[$namespace], $this->runtimeUsage[$namespace]);

    while($limit > 0 && \count($this->runtimeCaches[$namespace]) > $limit)
    {
      $key = CacheHelper::evictionKey($this->runtimeUsage[$namespace]);
      unset($this->runtimeCaches[$namespace][$key], $this->runtimeUsage[$namespace][$key]);
      $this->dirtyCaches[$namespace] = true;
    }

    $this->persist($namespace);
  }

  /**
   * Stages dirty namespace entries in Joomla's in-memory session
   *
   * Preserves the revision associated with the calculated entries so that an
   * older request cannot relabel stale data as current. This does not close the
   * session or write its backend; Joomla owns that lifecycle. Repeated calls
   * without changes are no-ops, and shared guest namespaces bypass the session.
   *
   * @param   string  $namespace    the namespace identifying the cache entries
   * @param   bool    $preserveAge  whether only usage changed, leaving expiration unchanged
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function persist(string $namespace, bool $preserveAge = false): void
  {
    $this->synchronise($namespace);

    if(isset($this->sharedNamespaces[$namespace]))
    {
      unset($this->dirtyCaches[$namespace]);

      return;
    }

    if(empty($this->dirtyCaches[$namespace])) return;

    if(!$preserveAge) $this->cacheCreated[$namespace] = time();

    $items                          = $this->runtimeCaches[$namespace] ?? [];
    $this->runtimeUsage[$namespace] = CacheHelper::reconcileUsage($items, $this->runtimeUsage[$namespace] ?? []);
    $stored                         = [
      'created' => $this->cacheCreated[$namespace] ?? time(),
      'items' => $items,
      'usage' => $this->runtimeUsage[$namespace],
    ];

    if(($this->scopes[$namespace] ?? null) !== null)
    {
      // Never obtain a fresh external revision to relabel an older snapshot.
      $stored['revision'] = $this->cacheRevisions[$namespace];
    }

    ($this->sessionProvider)()->set($namespace, $stored);
    unset($this->dirtyCaches[$namespace]);
  }

  /**
   * Persists dirty session namespaces in the selected scope
   *
   * A null scope persists all loaded namespaces. Synchronisation may mark
   * previously clean namespaces dirty.
   *
   * @param   string|null  $scope  the scope to persist, or null for all registered scopes
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function persistAll(?string $scope = null): void
  {
    // Iterate loaded namespaces too: synchronisation can make them dirty.
    foreach(array_keys($this->loadedCaches) as $namespace)
    {
      if($scope === null || ($this->scopes[$namespace] ?? null) === $scope)
      {
        $this->persist($namespace);
      }
    }
  }

  /**
   * Clears all scopes or requests expired-only maintenance across sessions
   *
   * Other sessions observe the change when they next initialise a cache.
   *
   * @param   bool  $expiredOnly  whether to retain unexpired values
   *
   * @return  void
   * @since   4.5.0
   */
  public function clearCaches(bool $expiredOnly = false): void
  {
    if(!$expiredOnly)
    {
      $this->revisionStore->invalidate('config');
      $this->revisionStore->invalidate('acl');
      $this->requestCaches = [];
      $this->requestUsage  = [];
    }
    else
    {
      foreach($this->requestCaches as $namespace => $items)
      {
        $this->requestCaches[$namespace] = $this->withoutExpiredEntries($items);
        $this->requestUsage[$namespace]  = CacheHelper::reconcileUsage($this->requestCaches[$namespace], $this->requestUsage[$namespace] ?? []);
      }
    }

    $this->guestStorage->clear($expiredOnly);
    $this->revisionStore->invalidate('cleanup');
    $this->maintainSessionCaches();

    foreach(array_keys($this->loadedCaches) as $namespace)
    {
      $this->synchronise($namespace);

      if(!$expiredOnly)
      {
        $this->runtimeCaches[$namespace] = [];
        $this->runtimeUsage[$namespace]  = [];
        unset($this->sharedReads[$namespace]);
      }
      else
      {
        $items = $this->withoutExpiredEntries($this->runtimeCaches[$namespace]);

        if($items === $this->runtimeCaches[$namespace]) continue;
        $this->runtimeCaches[$namespace] = $items;
        $this->runtimeUsage[$namespace]  = CacheHelper::reconcileUsage($items, $this->runtimeUsage[$namespace]);
      }
      $this->dirtyCaches[$namespace] = true;
    }

    $this->persistAll();
  }

  /**
   * Applies a shared maintenance request once to every gallery session namespace
   *
   * Session data is accessed through Joomla so all session handlers are supported.
   *
   * @return  void
   * @since   4.5.0
   */
  private function maintainSessionCaches(): void
  {
    $revision = $this->revisionStore->get('cleanup');

    if($this->maintenanceRevision === $revision) return;

    $session = ($this->sessionProvider)();

    if((string) $session->get('com_joomgallery.cacheCleanupRevision', '') !== $revision)
    {
      foreach(['configcache' => 'config', 'accesscache' => 'acl'] as $root => $scope)
      {
        $namespace = 'com_joomgallery.' . $root;
        $tree      = $session->get($namespace, []);
        $session->set($namespace, $this->cleanSessionTree($tree, $this->revisionStore->get($scope)));
      }
      $session->set('com_joomgallery.cacheCleanupRevision', $revision);
    }

    $this->maintenanceRevision = $revision;
  }

  /**
   * Cleans cache envelopes while preserving namespace structure and timestamps
   *
   * @param   mixed   $tree      the nested session namespace
   * @param   string  $revision  the valid revision for this scope
   *
   * @return  mixed
   * @since   4.5.0
   */
  private function cleanSessionTree(mixed $tree, string $revision): mixed
  {
    if(!\is_array($tree) && !\is_object($tree)) return $tree;

    $object = \is_object($tree);
    $nodes  = (array) $tree;

    if(isset($nodes['items']) && \is_array($nodes['items']))
    {
      $nodes['items'] = (string) ($nodes['revision'] ?? '') === $revision ? $this->withoutExpiredEntries($nodes['items']) : [];
      $nodes['usage'] = CacheHelper::reconcileUsage($nodes['items'], \is_array($nodes['usage'] ?? null) ? $nodes['usage'] : []);
    }
    else
    {
      foreach($nodes as $key => $node) $nodes[$key] = $this->cleanSessionTree($node, $revision);
    }

    return $object ? (object) $nodes : $nodes;
  }

  /**
   * Removes expired values without discarding entries that have no expiry
   *
   * @param   array  $items  the cached entries
   *
   * @return  array
   * @since   4.5.0
   */
  private function withoutExpiredEntries(array $items): array
  {
    $now = time();

    return array_filter($items, static fn($entry) => !\is_array($entry) || !isset($entry['expires']) || (int) $entry['expires'] >= $now);
  }

  /**
   * Selects shared storage for an explicitly resolved guest context
   *
   * @param   string       $namespace  the guest-specific namespace
   * @param   string|null  $scope      the config or ACL revision scope
   * @param   int          $lifetime   maximum entry lifetime in seconds
   * @param   bool         $shared     whether shared storage is selected
   *
   * @return  void
   * @since   4.5.0
   */
  public function registerShared(string $namespace, ?string $scope, int $lifetime, bool $shared = true): void
  {
    if(!$shared)
    {
      if(isset($this->sharedNamespaces[$namespace])) throw new \LogicException('Shared namespaces cannot use session or request-only storage.');

      return;
    }

    if(!\in_array($scope, ['config', 'acl'], true) || $lifetime <= 0)
    {
      throw new \InvalidArgumentException('Shared guest caching requires an ACL/config scope and positive lifetime.');
    }

    if(isset($this->loadedCaches[$namespace]) && !isset($this->sharedNamespaces[$namespace]))
    {
      throw new \LogicException('Cannot change a loaded session namespace to shared storage.');
    }

    $this->register($namespace, $scope);
    $this->sharedNamespaces[$namespace] = $lifetime;
  }
}
