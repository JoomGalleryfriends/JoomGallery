<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\Traits\CacheAwareTrait;

/**
 * Cache class for Joomgallery
 * Component global implementation of the cache trait
 *
 * @since   4.0.0
 */
class JoomCache
{
  use CacheAwareTrait;

  /**
   * Namespace of the bounded session hot cache.
   *
   * @var string
   */
  protected $cacheNamespace = 'com_joomgallery';

  /**
   * Returns a property of the object or the default value if the property is not set.
   *
   * @param   string  $key       The name of the key.
   * @param   mixed   $default   The default value.
   *
   * @return  mixed    The value of the property.
   *
   * @since   4.4.0
   */
  public function get(string $key, $default = null)
  {
    // Get cached value if exists
    $value = $this->getCacheEntry($this->cacheNamespace, $key);

    if($value)
    {
      return $value;
    }

    // Return default value as fallback
    return $default;
  }

  /**
   * Modifies a cache entry, creating it if it does not already exist.
   *
   * @param   string  $key       The name of the cache entry.
   * @param   mixed   $value     The value of the entry to set.
   *
   * @return  mixed  Previous value of the entry.
   *
   * @since   4.4.0
   */
  public function set(string $key, $value = null)
  {
    if($this->hasCacheEntry($this->cacheNamespace, $key))
    {
      // Set the real property if exists
      $previous = $this->getCacheEntry($this->cacheNamespace, $key) ?? null;
    }

    // Set the new value
    $this->putCacheEntry($this->cacheNamespace, $key, $value);

    return $previous;
  }
}
