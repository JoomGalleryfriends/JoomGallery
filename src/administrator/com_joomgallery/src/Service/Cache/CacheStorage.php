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

\defined('_JEXEC') || die;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;

/**
 * Shared request state and session storage for component caches
 *
 * Loads session data lazily and preserves the revision associated with each
 * cached snapshot. Request-only helper caching does not open a session.
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
class CacheStorage
{
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
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function __construct(CacheRevision $revisionStore, ?\Closure $sessionProvider = null)
  {
    $this->revisionStore   = $revisionStore;
    $this->sessionProvider = $sessionProvider
      ?? static fn() => Factory::getApplication()->getSession();
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

        unset($this->requestCaches[$name]);

        if(isset($this->loadedCaches[$name]))
        {
          $this->runtimeCaches[$name]  = [];
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

    $this->runtimeCaches[$namespace]  = $items;
    $this->loadedCaches[$namespace]   = true;
    $this->cacheRevisions[$namespace] = $revision;

    if($revision !== null && (!isset($stored['revision']) || (string) $stored['revision'] !== $revision))
    {
      $this->dirtyCaches[$namespace] = true;
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

    return \array_key_exists($key, $this->runtimeCaches[$namespace]);
  }

  /**
   * Returns a cached value or the supplied default
   *
   * @param   string  $namespace    the namespace identifying the cache entries
   * @param   string  $key          the cache entry key
   * @param   mixed   $default      the value returned when the entry is absent
   * @param   bool    $requestOnly  whether to use request-only storage
   *
   * @return  mixed
   *
   * @since   4.5.0
   */
  public function get(string $namespace, string $key, mixed $default, bool $requestOnly): mixed
  {
    if(!$this->has($namespace, $key, $requestOnly)) return $default;

    return $requestOnly ? $this->requestCaches[$namespace][$key] : $this->runtimeCaches[$namespace][$key];
  }

  /**
   * Stores an entry and enforces insertion-order eviction
   *
   * Preserves numeric keys during eviction and marks session-backed entries
   * dirty.
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
    }
    else
    {
      $this->initialise($namespace);
      $items                         =& $this->runtimeCaches[$namespace];
      $this->dirtyCaches[$namespace] = true;
    }

    unset($items[$key]);
    $items[$key] = $value;

    while($limit > 0 && \count($items) > $limit)
    {
      // Unlike array_shift(), this preserves numeric user-ID keys.
      unset($items[array_key_first($items)]);
    }
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
    }
    else
    {
      $this->initialise($namespace);
      $items =& $this->runtimeCaches[$namespace];
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

    while($limit > 0 && \count($this->runtimeCaches[$namespace]) > $limit)
    {
      unset($this->runtimeCaches[$namespace][array_key_first($this->runtimeCaches[$namespace])]);
      $this->dirtyCaches[$namespace] = true;
    }
  }

  /**
   * Writes dirty namespace entries to the current session
   *
   * Preserves the revision associated with the calculated entries so that an
   * older request cannot relabel stale data as current.
   *
   * @param   string  $namespace  the namespace identifying the cache entries
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function persist(string $namespace): void
  {
    $this->synchronise($namespace);

    if(empty($this->dirtyCaches[$namespace])) return;

    $stored = ['created' => time(), 'items' => $this->runtimeCaches[$namespace] ?? []];

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
}
