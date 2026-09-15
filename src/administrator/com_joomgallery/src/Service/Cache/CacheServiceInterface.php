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
 * Factory for the component's cache objects.
 * @since 4.5.0
 */
interface CacheServiceInterface
{
  /**
   * Return a new namespace object, or retain the default object and return true.
   * The store flag controls component ownership, not session persistence.
   */
  public function createCache(string $namespace = 'com_joomgallery', bool $store = false): CacheInterface|bool;

  /** Return (and lazily create) the component's default request-only cache. */
  public function getCache(): CacheInterface;
}
