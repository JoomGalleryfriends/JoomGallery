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
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;

/**
 * Shared guest values stored through Joomla's configured cache handler
 *
 * Individual values use revision-specific keys. Small locked index buckets
 * support expiration cleanup and LFU eviction with aging and LRU tie-breaking.
 * Backend retention is seven days; entry expiration is checked independently.
 *
 * @package JoomGallery
 * @since   4.5.0
 */
class GuestCacheStorage
{
  /**
   * Factory returning a Joomla cache object for a scope
   *
   * @var     \Closure
   * @since   4.5.0
   */
  private \Closure $cacheProvider;

  /**
   * Cache objects already obtained during this request
   *
   * @var     array
   * @since   4.5.0
   */
  private array $caches = [];

  /**
   * Calculation locks held until the value is written or the request ends
   *
   * @var     array
   * @since   4.5.0
   */
  private array $locks = [];

  /**
   * Number of independent index buckets in each scope
   *
   * @var     int
   * @since   4.5.0
   */
  protected int $bucketCount = 64;

  /**
   * Factory for the request's global guest policy
   *
   * @var     \Closure
   * @since   4.5.0
   */
  private \Closure $policyProvider;

  /**
   * Policy captured for this adapter's reads and writes
   *
   * @var     array|null
   * @since   4.5.0
   */
  private ?array $policy = null;

  /**
   * Scopes already checked for a policy change in this request
   *
   * @var     array
   * @since   4.5.0
   */
  private array $policyChecked = [];

  /**
   * Initialises lazy access to Joomla's shared cache backend
   *
   * @param   \Closure|null  $cacheProvider   optional backend factory for tests
   * @param   \Closure|null  $policyProvider  optional guest policy factory
   *
   * @return  void
   * @since   4.5.0
   */
  public function __construct(?\Closure $cacheProvider = null, ?\Closure $policyProvider = null)
  {
    $this->policyProvider = $policyProvider ?? static fn() => GuestCachePolicy::get();
    $this->cacheProvider  = $cacheProvider ?? static function (string $scope) {
      $app = Factory::getApplication();

      // Isolate files from Joomla's global GC, which uses a different TTL.
      $base = rtrim((string) $app->get('tmp_path', JPATH_ROOT . '/tmp'), '/\\');

      if($base === '') $base = JPATH_ROOT . '/tmp';
      $base                 .= '/joomgallery-guest-cache';

      if(!is_dir($base) && !@mkdir($base, 0755, true) && !is_dir($base))
      {
        throw new \RuntimeException('Cannot create the JoomGallery guest cache directory.');
      }

      $cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
        ->createCacheController(
            'callback',
            [
              'defaultgroup' => 'com_joomgallery_guest_' . $scope,
              'cachebase'    => $base,
              'storage'      => $app->get('cache_handler', 'file') ?: 'file',
              'caching'      => true,
              'locking'      => true,
              'locktime'     => 1,
              'lifetime'     => 10080,
              'language'     => 'en-GB',
            ]
        )->cache;

      return new GuestCacheBackend($cache);
    };
  }

  /**
   * Releases abandoned calculation locks after errors or uncached calculations
   *
   * @return  void
   * @since   4.5.0
   */
  public function __destruct()
  {
    foreach($this->locks as $scope => $ids)
    {
      foreach(array_keys($ids) as $id) $this->release($scope, $id);
    }
  }

  /**
   * Returns an entry envelope or null on a miss or unavailable backend
   *
   * A short calculation lock allows concurrent readers to reuse a completed
   * value. If unavailable, the caller remains free to calculate locally.
   *
   * @param   string  $scope      the invalidation scope
   * @param   string  $revision   the revision captured before calculation
   * @param   string  $namespace  the complete guest context namespace
   * @param   string  $key        the logical entry key
   * @param   bool    $countHit   whether this retrieval counts as use
   *
   * @return  array|null
   * @since   4.5.0
   */
  public function get(string $scope, string $revision, string $namespace, string $key, bool $countHit = true): ?array
  {
    $policy = $this->policy();

    if($policy['entries'] === 0 || $policy['lifetime'] === 0) return null;

    $id = $this->key($revision, $namespace, $key);

    try
    {
      $cache = $this->backend($scope);
      $this->preparePolicy($scope, $cache);
      $entry = $this->decode($cache->get($id));

      if($entry !== null && $entry['expires'] >= time())
      {
        if($countHit) $this->touch($scope, $revision, $namespace, $key);

        return $entry;
      }

      if(!isset($this->locks[$scope][$id]))
      {
        $lock = $cache->lock('build-' . $id, null, 1);

        if($lock->locked)
        {
          $this->locks[$scope][$id] = true;
          $entry                    = $this->decode($cache->get($id));

          if($entry !== null && $entry['expires'] >= time())
          {
            $this->release($scope, $id);

            if($countHit) $this->touch($scope, $revision, $namespace, $key);

            return $entry;
          }
        }
      }
    }
    catch(\Throwable $e)
    {
      $this->release($scope, $id);
    }

    return null;
  }

  /**
   * Records one successful retrieval in the shared bucket under its write lock
   *
   * Missing or expired index entries are never recreated. A failed metadata
   * update does not prevent the caller from using its already retrieved value.
   * Counters and LRU order change without extending the value's expiration.
   *
   * @param   string  $scope      the invalidation scope
   * @param   string  $revision   the revision of the retrieved value
   * @param   string  $namespace  the guest context namespace
   * @param   string  $key        the logical entry key
   *
   * @return  void
   * @since   4.5.0
   */
  public function touch(string $scope, string $revision, string $namespace, string $key): void
  {
    $policy = $this->policy();

    if($policy['entries'] === 0 || $policy['lifetime'] === 0) return;

    $id     = $this->key($revision, $namespace, $key);
    $count  = min($this->bucketCount, $policy['entries']);
    $bucket = 'index-' . (hexdec(substr($id, 0, 8)) % $count);
    $locked = false;

    try
    {
      $cache = $this->backend($scope);
      $this->preparePolicy($scope, $cache);
      $locked = (bool) $cache->lock($bucket, null, 1)->locked;

      if(!$locked) return;

      $index = $this->readIndex($cache->get($bucket));

      if(!isset($index[$id]) || CacheHelper::isExpired($index[$id])) return;

      CacheHelper::recordHit($index, $id);
      $cache->store(serialize($index), $bucket);
    }
    catch(\Throwable $e)
    {
      // Usage accounting must not turn a cache hit into an application failure.
    }
    finally
    {
      if($locked)
      {
        try { $cache->unlock($bucket);
        }
        catch(\Throwable $e) {
        }
      }
    }
  }

  /**
   * Stores one entry and updates its bounded expiration index under a lock
   *
   * @param   string  $scope      the invalidation scope
   * @param   string  $revision   the revision captured before calculation
   * @param   string  $namespace  the complete guest context namespace
   * @param   string  $key        the logical entry key
   * @param   mixed   $value      the calculated value
   * @param   int     $lifetime   the maximum entry lifetime in seconds
   *
   * @return  void
   * @since   4.5.0
   */
  public function put(string $scope, string $revision, string $namespace, string $key, mixed $value, int $lifetime): void
  {
    $id     = $this->key($revision, $namespace, $key);
    $policy = $this->policy();

    if($policy['entries'] === 0 || $policy['lifetime'] === 0) return;

    $count  = min($this->bucketCount, $policy['entries']);
    $number = hexdec(substr($id, 0, 8)) % $count;
    $limit  = intdiv($policy['entries'], $count) + ($number < $policy['entries'] % $count ? 1 : 0);
    $bucket = 'index-' . $number;
    $locked = false;

    try
    {
      $cache = $this->backend($scope);
      $this->preparePolicy($scope, $cache);
      $expires = time() + min($policy['lifetime'], max(1, $lifetime));

      if(\is_array($value) && isset($value['expires'])) $expires = min($expires, (int) $value['expires']);

      if($expires < time()) return;

      $locked = (bool) $cache->lock($bucket, null, 1)->locked;

      if(!$locked) return;
      $index = $this->readIndex($cache->get($bucket));

      $newEntry = !isset($index[$id]) || CacheHelper::isExpired($index[$id]);
      $full     = $newEntry && \count($index) >= $limit;

      foreach($index as $oldId => $metadata)
      {
        if(CacheHelper::isExpired($metadata))
        {
          $cache->remove($oldId);
          unset($index[$oldId]);
        }
      }

      if($full) CacheHelper::ageUsage($index);

      if($newEntry)
      {
        while(\count($index) >= $limit)
        {
          $oldId = CacheHelper::evictionKey($index);
          $cache->remove($oldId);
          unset($index[$oldId]);
        }
      }

      if($cache->store(serialize(['expires' => $expires, 'value' => $value]), $id))
      {
        $index[$id]          ??= ['hits' => 1];
        $index[$id]['expires'] = $expires;
      }

      // Persist cleanup and aging even when the new value could not be stored.
      $cache->store(serialize($index), $bucket);
    }
    catch(\Throwable $e)
    {
      // Cache availability must not prevent fresh configuration or ACL checks.
    }
    finally
    {
      if($locked)
      { try { $cache->unlock($bucket);
      }
      catch(\Throwable $e) {
      }
      }
      $this->release($scope, $id);
    }
  }

  /**
   * Clears both shared scopes or removes only entries whose expiry has passed
   *
   * @param   bool  $expiredOnly  whether to preserve valid entries
   *
   * @return  void
   * @throws  \RuntimeException  If cache maintenance cannot be completed.
   * @since   4.5.0
   */
  public function clear(bool $expiredOnly): void
  {
    foreach(['config', 'acl'] as $scope)
    {
      $cache = $this->backend($scope);

      if(!$expiredOnly)
      {
        if(!$cache->clean()) throw new \RuntimeException('Unable to clear shared guest cache.');
        unset($this->policyChecked[$scope]);
        continue;
      }

      for($i = 0; $i < $this->bucketCount; $i++)
      {
        $bucket = 'index-' . $i;

        if($cache->get($bucket) === false) continue;

        if(!$cache->lock($bucket, null, 1)->locked) throw new \RuntimeException('Guest cache cleanup is busy.');
        try
        {
          $index = $this->readIndex($cache->get($bucket));

          foreach($index as $id => $metadata)
          {
            if(CacheHelper::isExpired($metadata))
            { $cache->remove($id);
unset($index[$id]);
            }
          }

          if(!$cache->store(serialize($index), $bucket)) throw new \RuntimeException('Unable to persist guest cache cleanup.');
        }
        finally
        {
          $cache->unlock($bucket);
        }
      }
    }
  }

  /**
   * Returns the backend for an allowed scope
   *
   * @param   string  $scope  the cache scope
   *
   * @return  object
   * @since   4.5.0
   */
  private function backend(string $scope): object
  {
    if(!\in_array($scope, ['config', 'acl'], true)) throw new \InvalidArgumentException('Unsupported guest cache scope.');

    return $this->caches[$scope] ??= ($this->cacheProvider)($scope);
  }

  /**
   * Builds an unambiguous revision-specific backend identifier
   *
   * @param   string  $revision   the captured revision
   * @param   string  $namespace  the guest context namespace
   * @param   string  $key        the logical key
   *
   * @return  string
   * @since   4.5.0
   */
  private function key(string $revision, string $namespace, string $key): string
  {
    return hash('sha256', serialize([$revision, $namespace, $key, $this->policy()]));
  }

  /**
   * Decodes trusted cache data while allowing only plain parameter objects
   *
   * @param   mixed  $data  the serialized backend value
   *
   * @return  array|null
   * @since   4.5.0
   */
  private function decode(mixed $data): ?array
  {
    if(!\is_string($data)) return null;
    $entry = @unserialize($data, ['allowed_classes' => ['stdClass']]);

    return \is_array($entry) && isset($entry['expires']) && \array_key_exists('value', $entry) ? $entry : null;
  }

  /**
   * Decodes usage metadata, accepting older expiration-only bucket entries
   *
   * @param   mixed  $data  the serialized index
   *
   * @return  array
   * @since   4.5.0
   */
  private function readIndex(mixed $data): array
  {
    $index = \is_string($data) ? @unserialize($data, ['allowed_classes' => false]) : null;

    if(!\is_array($index)) return [];

    foreach($index as $id => &$entry)
    {
      if(\is_int($entry))
      {
        $entry = ['expires' => $entry, 'hits' => 1];
      }
      elseif(\is_array($entry) && isset($entry['expires']))
      {
        $entry['hits'] = max(1, (int) ($entry['hits'] ?? 1));
      }
      else
      {
        unset($index[$id]);
      }
    }
    unset($entry);

    return $index;
  }

  /**
   * Releases a held calculation lock without affecting request completion
   *
   * @param   string  $scope  the cache scope
   * @param   string  $id     the backend identifier
   *
   * @return  void
   * @since   4.5.0
   */
  private function release(string $scope, string $id): void
  {
    if(!isset($this->locks[$scope][$id])) return;
    try { $this->backend($scope)->unlock('build-' . $id);
$this->backend($scope)->remove('build-' . $id);
    }
    catch(\Throwable $e) {
    }
    unset($this->locks[$scope][$id]);
  }

  /**
   * Returns the captured policy with the same bounds as the configuration form
   *
   * @return  array{entries: int, lifetime: int}
   * @since   4.5.0
   */
  private function policy(): array
  {
    if($this->policy === null)
    {
      $policy       = ($this->policyProvider)();
      $this->policy = [
        'entries' => max(0, min(100000, (int) $policy['entries'])),
        'lifetime' => max(0, min(604800, (int) $policy['lifetime'])),
      ];
    }

    return $this->policy;
  }

  /**
   * Removes entries indexed under an older policy before applying new quotas
   *
   * Keeps the bucket directory stable so reductions also retire inactive buckets.
   * Individual bucket locks serialize cleanup with writers. Policy-specific keys
   * prevent late old-policy writes from being reused by new requests.
   *
   * @param   string  $scope  the shared scope
   * @param   object  $cache  the backend cache object
   *
   * @return  void
   * @since   4.5.0
   */
  private function preparePolicy(string $scope, object $cache): void
  {
    if(isset($this->policyChecked[$scope])) return;
    $signature = serialize($this->policy());

    if($cache->get('guest-policy') !== $signature)
    {
      if(!$cache->lock('guest-policy', null, 1)->locked) throw new \RuntimeException('Guest policy update is busy.');
      try
      {
        if($cache->get('guest-policy') !== $signature)
        {
          for($i = 0; $i < $this->bucketCount; $i++)
          {
            $bucket = 'index-' . $i;

            if($cache->get($bucket) === false) continue;

            if(!$cache->lock($bucket, null, 1)->locked) throw new \RuntimeException('Guest index update is busy.');
            try
            {
              foreach($this->readIndex($cache->get($bucket)) as $id => $expiry) $cache->remove($id);

              if(!$cache->store(serialize([]), $bucket)) throw new \RuntimeException('Unable to reset guest index.');
            }
            finally
            {
              $cache->unlock($bucket);
            }
          }

          if(!$cache->store($signature, 'guest-policy')) throw new \RuntimeException('Unable to store guest policy.');
        }
      }
      finally
      {
        $cache->unlock('guest-policy');
      }
    }
    $this->policyChecked[$scope] = true;
  }
}
