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

/**
 * Contract for namespace-specific cache operations
 * Separates cache operations from component ownership and session persistence.
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
interface CacheInterface
{
  /**
   * Initialises a namespace-specific cache object
   *
   * @param   string  $namespace  the namespace identifying the cache entries
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function __construct(string $namespace);

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
   * @throws  \LogicException  If the namespace has a conflicting scope.
   *
   * @since   4.5.0
   */
  public function configure(?string $scope = null, bool $session = false, int $maxAge = 0, bool $shared = false): void;

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
  public function initialise(): void;

  /**
   * Returns the revision currently associated with the namespace
   *
   * Returns null when the namespace has no shared revision scope.
   *
   * @return  string|null
   *
   * @since   4.5.0
   */
  public function getRevision(): ?string;

  /**
   * Checks for an entry without treating null as a cache miss
   *
   * @param   string  $key  the cache entry key
   *
   * @return  bool
   *
   * @since   4.5.0
   */
  public function has(string $key): bool;

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
  public function get(string $key, mixed $default = null): mixed;

  /**
   * Stores a value and returns the previous cached value
   *
   * New entries start at one hit. Full-cache insertions remove expired entries,
   * age usage counts by dividing by 1.5 (floored, minimum one), then evict the
   * least frequently used entry, breaking ties by least recent retrieval.
   *
   * @param   string  $key    the cache entry key
   * @param   mixed   $value  the value to store
   * @param   int     $limit  the maximum entry count; zero means no limit
   *
   * @return  mixed
   *
   * @since   4.5.0
   */
  public function set(string $key, mixed $value = null, int $limit = 0): mixed;

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
  public function remove(string|false $pattern = false, bool $decodeBase64 = false): void;

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
  public function prune(callable $keep, int $limit = 0): void;

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
  public function persist(): void;

  /**
   * Persists dirty session namespaces in the selected scope
   *
   * Includes namespaces used by earlier service instances during the request.
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function persistAll(): void;
}
