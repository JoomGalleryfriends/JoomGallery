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

/**
 * A namespace-specific cache object backed by shared component storage.
 * Defaults to request-only storage, as used by JoomHelper.
 * @since 4.5.0
 */
class Cache implements CacheInterface
{
  protected string $namespace;
  protected int $max_age;
  private CacheStorage $storage;
  private bool $requestOnly = true;
  private ?string $scope    = null;

  public function __construct(string $namespace, int $max_age = 0, ?CacheStorage $storage = null)
  {
    $this->namespace = $namespace;
    $this->max_age   = $max_age;
    $this->storage   = $storage ?? new CacheStorage();
  }

  public function configure(?string $scope = null, bool $session = false, int $maxAge = 0): void
  {
    $this->storage->register($this->namespace, $scope);
    $this->scope       = $scope;
    $this->requestOnly = !$session;
    $this->max_age     = $maxAge;
  }

  public function initialise(): void
  {
    $this->storage->register($this->namespace, $this->scope);

    if(!$this->requestOnly)
    {
      $this->storage->initialise($this->namespace, $this->max_age);
    }
  }

  public function getRevision(): ?string
  {
    $this->initialise();

    return $this->storage->revision($this->namespace);
  }

  public function has(string $key): bool
  {
    $this->initialise();

    return $this->storage->has($this->namespace, $key, $this->requestOnly);
  }

  public function get(string $key, mixed $default = null): mixed
  {
    $this->initialise();

    return $this->storage->get($this->namespace, $key, $default, $this->requestOnly);
  }

  /** Stores a value and returns the previous value. */
  public function set(string $key, mixed $value = null, int $limit = 0): mixed
  {
    $previous = $this->get($key);
    $this->storage->put($this->namespace, $key, $value, $limit, $this->requestOnly);

    return $previous;
  }

  public function remove(string|false $pattern = false, bool $decodeBase64 = false): void
  {
    $this->initialise();
    $this->storage->remove($this->namespace, $pattern, $decodeBase64, $this->requestOnly);
  }

  public function prune(callable $keep, int $limit = 0): void
  {
    if($this->requestOnly)
    {
      throw new \LogicException('Session-entry pruning requires a session-backed cache.');
    }

    $this->initialise();
    $this->storage->prune($this->namespace, $keep, $limit);
  }

  public function persist(): void
  {
    if(!$this->requestOnly) $this->storage->persist($this->namespace);
  }

  /** Flush all objects in this scope, including earlier Config instances. */
  public function persistAll(): void
  {
    if(!$this->requestOnly) $this->storage->persistAll($this->scope);
  }
}
