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

use Joomla\CMS\Factory;

/**
 * Namespace-specific cache backed by shared component storage
 *
 * Defaults to request-only storage. Session persistence and revision tracking
 * are selected explicitly through configure().
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
class Cache implements CacheInterface
{
  /**
   * Namespace identifying this cache object
   *
   * @var     string
   * @access  protected
   *
   * @since   4.5.0
   */
  protected string $namespace;

  /**
   * Maximum namespace age in seconds; zero disables expiration
   *
   * @var     int
   * @access  protected
   *
   * @since   4.5.0
   */
  protected int $max_age;

  /**
   * Shared component storage backing this namespace
   *
   * @var     CacheStorage
   * @access  private
   *
   * @since   4.5.0
   */
  private CacheStorage $storage;

  /**
   * Whether entries are restricted to the current request
   *
   * @var     bool
   * @access  private
   *
   * @since   4.5.0
   */
  private bool $requestOnly = true;


  /**
   * Shared revision scope, or null for unscoped entries
   *
   * @var     string|null
   * @access  private
   *
   * @since   4.5.0
   */
  private ?string $scope = null;

  /**
   * Initialises the cache namespace and its storage dependencies
   *
   * @param   string             $namespace  the namespace identifying the cache entries
   * @param   int                $max_age    the maximum namespace age in seconds; zero disables expiration
   * @param   CacheStorage|null  $storage    the shared storage, or null to create independent storage
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function __construct(string $namespace, int $max_age = 0, ?CacheStorage $storage = null)
  {
    $this->namespace = $namespace;
    $this->max_age   = $max_age;
    $this->storage   = $storage ?? new CacheStorage(Factory::getApplication()->bootComponent('com_joomgallery')->getCacheRevision());
  }

  /**
   * Selects revision tracking, storage mode and namespace lifetime
   *
   * The shared flag selects guest storage and takes precedence over session.
   * Shared storage requires a config/ACL scope and a positive lifetime.
   * Otherwise session selects session storage; both flags false are request-only.
   *
   * @param   string|null  $scope    the shared revision scope, or null for an unscoped cache
   * @param   bool         $session  whether entries are persisted in the current session
   * @param   int          $maxAge   the maximum namespace age in seconds; zero disables expiration
   * @param   bool         $shared   whether to use shared guest storage instead of the session
   *
   * @return  void
   *
   * @throws  \LogicException  If the namespace has a conflicting scope.
   * @since   4.5.0
   */
  public function configure(?string $scope = null, bool $session = false, int $maxAge = 0, bool $shared = false): void
  {
    $this->storage->register($this->namespace, $scope);
    $this->storage->registerShared($this->namespace, $scope, $maxAge, $shared);
    $this->scope       = $scope;
    $this->requestOnly = !$session && !$shared;
    $this->max_age     = $maxAge;
  }

  /**
   * Registers the namespace and loads eligible session entries
   *
   * Expired or outdated session entries are ignored. Repeated initialisation
   * reuses the loaded request state.
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function initialise(): void
  {
    $this->storage->register($this->namespace, $this->scope);

    if(!$this->requestOnly)
    {
      $this->storage->initialise($this->namespace, $this->max_age);
    }
  }

  /**
   * Returns the revision currently associated with the namespace
   * Returns null when the namespace has no shared revision scope.
   *
   * @return  string|null
   *
   * @since   4.5.0
   */
  public function getRevision(): ?string
  {
    $this->initialise();

    return $this->storage->revision($this->namespace);
  }

  /**
   * Checks for an entry without treating null as a cache miss
   *
   * @param   string  $key  the cache entry key
   *
   * @return  bool
   *
   * @since   4.5.0
   */
  public function has(string $key): bool
  {
    $this->initialise();

    return $this->storage->has($this->namespace, $key, $this->requestOnly);
  }

  /**
   * Returns a cached value or the supplied default
   *
   * @param   string  $key      the cache entry key
   * @param   mixed   $default  the value returned when the entry is absent
   *
   * @return  mixed
   *
   * @since   4.5.0
   */
  public function get(string $key, mixed $default = null): mixed
  {
    $this->initialise();

    return $this->storage->get($this->namespace, $key, $default, $this->requestOnly);
  }

  /**
   * Stores a value and returns the previous cached value
   *
   * New entries start at one hit. A full cache removes expired entries, ages
   * usage counts and evicts the least frequently used, then least recently used.
   * Reading the previous value here does not count as a cache hit.
   *
   * @param   string  $key    the cache entry key
   * @param   mixed   $value  the value to store
   * @param   int     $limit  the maximum entry count; zero means no limit
   *
   * @return  mixed
   *
   * @since   4.5.0
   */
  public function set(string $key, mixed $value = null, int $limit = 0): mixed
  {
    $this->initialise();
    $previous = $this->storage->get($this->namespace, $key, null, $this->requestOnly, false);
    $this->storage->put($this->namespace, $key, $value, $limit, $this->requestOnly);

    return $previous;
  }

  /**
   * Removes local entries or invalidates a shared revision scope
   *
   * Session-backed scoped caches invalidate the entire scope even when a
   * pattern is supplied. Request-only and unscoped caches support local
   * pattern removal.
   *
   * @param   string|false  $pattern       the key-matching regular expression, or false for all entries
   * @param   bool          $decodeBase64  whether to decode keys before pattern matching
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function remove(string|false $pattern = false, bool $decodeBase64 = false): void
  {
    $this->initialise();
    $this->storage->remove($this->namespace, $pattern, $decodeBase64, $this->requestOnly);
  }

  /**
   * Removes rejected session entries and enforces the entry limit
   *
   * This is local maintenance and does not increment a shared revision.
   *
   * @param   callable  $keep   the callback accepting an entry and returning true to retain it
   * @param   int       $limit  the maximum entry count; zero means no limit
   *
   * @return  void
   * @throws  \LogicException  If the cache is request-only.
   *
   * @since   4.5.0
   */
  public function prune(callable $keep, int $limit = 0): void
  {
    if($this->requestOnly)
    {
      throw new \LogicException('Entry pruning requires persistent cache storage.');
    }

    $this->initialise();
    $this->storage->prune($this->namespace, $keep, $limit);
  }

  /**
   * Writes dirty namespace entries to the current session
   *
   * Preserves the revision associated with the calculated entries so that an
   * older request cannot relabel stale data as current.
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function persist(): void
  {
    if(!$this->requestOnly) $this->storage->persist($this->namespace);
  }

  /**
   * Persists dirty session namespaces in the selected scope
   *
   * Includes namespaces used by earlier service instances during the request.
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function persistAll(): void
  {
    if(!$this->requestOnly) $this->storage->persistAll($this->scope);
  }
}
