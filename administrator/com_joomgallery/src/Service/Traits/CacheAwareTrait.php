<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Traits;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;

/**
 * Request cache with an optional, bounded session-backed hot cache.
 * Key construction and cache policy remain the responsibility of the service.
 *
 * There are request-only entries and session-backed runtime entries.
 * - A request-only entry is never read from or written to the session.
 * - A runtime entry is the current-request copy of a session-backed entry.
 * - A dirty namespace has changed and its session copy has not yet been updated.
 *
 * @since  4.4.0
 */
trait CacheAwareTrait
{
  /**
   * Request-only caches indexed by namespace.
   *
   * These entries are never loaded from or persisted to the session.
   *
   * @var array
   */
  protected static $requestCaches = [];

  /**
   * Runtime caches indexed by session namespace.
   *
   * @var array
   */
  protected static $runtimeCaches = [];

  /**
   * Namespaces already loaded from the session.
   *
   * @var array
   */
  protected static $loadedCaches = [];

  /**
   * Dirty namespaces waiting to be persisted.
   *
   * @var array
   */
  protected static $dirtyCaches = [];

  /**
   * Initialises a cache namespace and loads its stored entries from the
   * current session. Expired session data is ignored.
   *
   * @param   string  $namespace  Session key identifying the cache namespace.
   * @param   int     $maxAge     Maximum cache age in seconds, or zero to disable expiration.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function initialiseCache(string $namespace, int $maxAge = 0): void
  {
    if(isset(self::$loadedCaches[$namespace]))
    {
      return;
    }

    $stored = Factory::getApplication()->getSession()->get($namespace, []);
    $items  = [];

    if(\is_array($stored) && isset($stored['items']) && \is_array($stored['items']))
    {
      $created = (int) ($stored['created'] ?? 0);

      if($maxAge === 0 || ($created > 0 && $created + $maxAge >= time()))
      {
        $items = $stored['items'];
      }
    }
    // Compatibility with the former Config cache format.
    elseif(\is_array($stored) && $maxAge === 0)
    {
      $items = $stored;
    }

    self::$runtimeCaches[$namespace] = $items;
    self::$loadedCaches[$namespace]  = true;
  }

  /**
   * Checks whether a key exists in the specified cache namespace.
   * Entries containing null are treated as existing cache entries.
   *
   * @param   string  $namespace    Key identifying the cache namespace.
   * @param   string  $key          Cache entry key to check.
   * @param   bool    $requestOnly  True to use the request-only cache.
   *
   * @return  bool  True when the entry exists, false otherwise.
   *
   * @since   4.4.0
   */
  protected function hasCacheEntry(string $namespace, string $key, bool $requestOnly = false): bool
  {
    if($requestOnly)
    {
      return isset(self::$requestCaches[$namespace])
        && \array_key_exists($key, self::$requestCaches[$namespace]);
    }

    $this->initialiseCache($namespace);

    return \array_key_exists($key, self::$runtimeCaches[$namespace]);
  }

  /**
   * Returns an entry from the specified cache namespace or a default value
   * when the key is not cached.
   *
   * @param   string  $namespace    Key identifying the cache namespace.
   * @param   string  $key          Cache entry key to retrieve.
   * @param   mixed   $default      Value returned when the entry does not exist.
   * @param   bool    $requestOnly  True to use the request-only cache.
   *
   * @return  mixed  Cached value or the supplied default value.
   *
   * @since   4.4.0
   */
  protected function getCacheEntry(string $namespace, string $key, $default = null, bool $requestOnly = false)
  {
    if(!$this->hasCacheEntry($namespace, $key, $requestOnly))
    {
      return $default;
    }

    return $requestOnly ? self::$requestCaches[$namespace][$key] : self::$runtimeCaches[$namespace][$key];
  }

  /**
   * Stores an entry in the runtime cache, marks the namespace as dirty and
   * optionally evicts the least recently inserted entries.
   *
   * @param   string  $namespace    Key identifying the cache namespace.
   * @param   string  $key          Cache entry key under which to store the value.
   * @param   mixed   $value        Value to store.
   * @param   int     $limit        Maximum number of entries, or zero for no limit.
   * @param   bool    $requestOnly  True to store the entry for the current request only.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function putCacheEntry(string $namespace, string $key, mixed $value, int $limit = 0, bool $requestOnly = false): void
  {
    if($requestOnly)
    {
      unset(self::$requestCaches[$namespace][$key]);
      self::$requestCaches[$namespace][$key] = $value;

      if($limit > 0)
      {
        while(\count(self::$requestCaches[$namespace]) > $limit)
        {
          array_shift(self::$requestCaches[$namespace]);
        }
      }

      return;
    }

    $this->initialiseCache($namespace);

    // Reinsertion makes array order a compact LRU approximation.
    unset(self::$runtimeCaches[$namespace][$key]);
    self::$runtimeCaches[$namespace][$key] = $value;

    if($limit > 0)
    {
      while(\count(self::$runtimeCaches[$namespace]) > $limit)
      {
        array_shift(self::$runtimeCaches[$namespace]);
      }
    }

    self::$dirtyCaches[$namespace] = true;
  }

  /**
   * Removes all entries or entries whose keys match a regular expression,
   * then immediately persists the updated namespace to the session.
   *
   * @param   string        $namespace     Session key identifying the cache namespace.
   * @param   string|false  $pattern       Key-matching regular expression, or false to remove all entries.
   * @param   bool          $decodeBase64  True to decode Base64-encoded keys before matching.
   * @param   bool          $requestOnly   True to remove the entry for the current request only.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function removeCacheEntries(string $namespace, string|false $pattern = false, bool $decodeBase64 = false, bool $requestOnly = false): void
  {
    if($requestOnly)
    {
      self::$requestCaches[$namespace] ??= [];
      $cache =& self::$requestCaches[$namespace];
    }
    else
    {
      $this->initialiseCache($namespace);
      $cache =& self::$runtimeCaches[$namespace];
    }

    if($pattern === false)
    {
      $cache = [];
    }
    elseif(@preg_match($pattern, '') !== false)
    {
      foreach(array_keys($cache) as $key)
      {
        $matchKey = $decodeBase64 ? base64_decode($key) : $key;

        if(preg_match($pattern, $matchKey))
        {
          unset($cache[$key]);
        }
      }
    }

    if($requestOnly) return;

    self::$dirtyCaches[$namespace] = true;
    $this->persistCacheNamespace($namespace);
  }

  /**
   * Writes a namespace to the current session using
   * the common timestamped cache envelope.
   *
   * @param   string  $namespace  Session key identifying the cache namespace.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function persistCacheNamespace(string $namespace): void
  {
    if(empty(self::$dirtyCaches[$namespace]))
    {
      return;
    }

    Factory::getApplication()->getSession()->set(
        $namespace,
        ['created' => time(), 'items' => self::$runtimeCaches[$namespace] ?? []]
    );

    unset(self::$dirtyCaches[$namespace]);
  }

  /**
   * Writes all cache namespaced marked as dirty during the current
   * request to the session.
   *
   * @return  void
   *
   * @since   4.4.0
   */
  protected function persistCachesToSession(): void
  {
    foreach(array_keys(self::$dirtyCaches) as $namespace)
    {
      $this->persistCacheNamespace($namespace);
    }
  }
}
