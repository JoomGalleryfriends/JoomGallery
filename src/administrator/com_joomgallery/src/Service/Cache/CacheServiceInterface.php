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
 * Contract for creating and retrieving component cache objects
 *
 * The store flag controls ownership of the default object, not session
 * persistence.
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
interface CacheServiceInterface
{

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
  public function createCache(string $namespace = 'com_joomgallery', bool $store = false): CacheInterface|bool;

  /**
   * Returns the default request-only component cache
   *
   * Creates the default cache lazily when it has not yet been stored.
   *
   * @return  CacheInterface
   *
   * @since   4.5.0
   */
  public function getCache(): CacheInterface;
  
  /**
   * Returns the revision store shared by the component's cache objects
   *
   * @return  CacheRevision
   * @since   4.5.0
   */
  public function getCacheRevision(): CacheRevision;
}
