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

use Joomla\CMS\Factory;

/**
 * Owns the request state shared by all cache objects of one component.
 *
 * Session access is lazy, so request-only helper caching never opens a session.
 * @since 4.5.0
 */
class CacheStorage
{
  private array $requestCaches  = [];
  private array $runtimeCaches  = [];
  private array $loadedCaches   = [];
  private array $dirtyCaches    = [];
  private array $cacheRevisions = [];
  private array $scopes         = [];
  private array $scopeRevisions = [];
  private \Closure $sessionProvider;

  public function __construct(?\Closure $sessionProvider = null)
  {
    $this->sessionProvider = $sessionProvider
      ?? static fn() => Factory::getApplication()->getSession();
  }

  public function register(string $namespace, ?string $scope): void
  {
    if(\array_key_exists($namespace, $this->scopes) && $this->scopes[$namespace] !== $scope)
    {
      throw new \LogicException('Conflicting cache scope for namespace: ' . $namespace);
    }

    $this->scopes[$namespace] = $scope;
  }

  /**
   * Retire every loaded namespace in a scope after a local revision change.
   * Other requests are observed on their next request through CacheRevision.
   */
  private function synchronise(string $namespace): ?string
  {
    $scope = $this->scopes[$namespace] ?? null;

    if($scope === null)
    {
      return null;
    }

    $revision = CacheRevision::get($scope);

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

  public function revision(string $namespace): ?string
  {
    return $this->synchronise($namespace);
  }

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

  public function get(string $namespace, string $key, mixed $default, bool $requestOnly): mixed
  {
    if(!$this->has($namespace, $key, $requestOnly)) return $default;

    return $requestOnly ? $this->requestCaches[$namespace][$key] : $this->runtimeCaches[$namespace][$key];
  }

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
   * Session-backed scoped removal invalidates the entire shared scope.
   * Request-only removal remains local and supports key patterns.
   */
  public function remove(string $namespace, string|false $pattern, bool $decodeBase64, bool $requestOnly): void
  {
    $scope = $this->scopes[$namespace] ?? null;

    if(!$requestOnly && $scope !== null)
    {
      CacheRevision::invalidate($scope);
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
   * Local maintenance must never advance a shared revision.
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
