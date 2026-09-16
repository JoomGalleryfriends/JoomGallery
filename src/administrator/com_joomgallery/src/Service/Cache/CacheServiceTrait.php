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
use Joomla\Database\DatabaseInterface;

/**
 * Factory for cache objects sharing component-owned storage
 *
 * Retains the public default cache for existing component consumers while
 * creating separate namespace objects for other services.
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
trait CacheServiceTrait
{
  /**
   * Revision store shared by all cache objects owned by the component
   *
   * @var     CacheRevision|null
   * @since   4.5.0
   */
  private ?CacheRevision $cacheRevision = null;

  /**
   * Default request-only cache exposed on the component
   *
   * Kept public for existing $component->cache consumers.
   *
   * @var     CacheInterface|null
   *
   * @since   4.5.0
   */
  public ?CacheInterface $cache = null;

  /**
   * Storage shared by cache objects created by this component
   *
   * @var     CacheStorage|null
   *
   * @since   4.5.0
   */
  private ?CacheStorage $cacheStorage = null;

  /**
   * Returns the default request-only component cache
   *
   * Creates the default cache lazily when it has not yet been stored.
   *
   * @return  CacheInterface
   *
   * @since   4.5.0
   */
  public function getCache(): CacheInterface
  {
    if($this->cache === null) $this->createCache('com_joomgallery', true);

    return $this->cache;
  }


  /**
   * Returns the component's shared revision store
   *
   * @return  CacheRevision
   * @since   4.5.0
   */
  public function getCacheRevision(): CacheRevision
  {
    $this->cacheRevision ??= new CacheRevision(Factory::getContainer()->get(DatabaseInterface::class));

    return $this->cacheRevision;
  }

  /**
   * Creates a namespace object or retains the default component cache
   *
   * With store enabled, retains the default object and returns true. Otherwise
   * returns a new object sharing component storage without replacing the
   * default cache.
   *
   * @param   string  $namespace  the namespace identifying the cache entries
   * @param   bool    $store      whether to retain the default object on the component
   *
   * @return  CacheInterface|bool
   *
   * @since   4.5.0
   */
  public function createCache(string $namespace = 'com_joomgallery', bool $store = false): CacheInterface|bool
  {
    $this->cacheStorage ??= new CacheStorage($this->getCacheRevision());

    if(!$store)
    {
      return new Cache($namespace, 0, $this->cacheStorage);
    }

    $this->cache ??= new Cache($namespace, 0, $this->cacheStorage);

    return true;
  }
}
