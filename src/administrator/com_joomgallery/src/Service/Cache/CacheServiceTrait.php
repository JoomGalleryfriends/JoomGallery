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
 * Creates namespace objects sharing one component-owned storage instance.
 * @since 4.5.0
 */
trait CacheServiceTrait
{
  /** Public for compatibility with existing $component->cache consumers. */
  public ?CacheInterface $cache = null;

  private ?CacheStorage $cacheStorage = null;

  public function getCache(): CacheInterface
  {
    if($this->cache === null) $this->createCache('com_joomgallery', true);

    return $this->cache;
  }

  /**
   * $store=true retains the default helper cache and returns true.
   * $store=false returns a new object without replacing the default cache.
   * Neither option selects session persistence; configure() does that explicitly.
   */
  public function createCache(string $namespace = 'com_joomgallery', bool $store = false): CacheInterface|bool
  {
    $this->cacheStorage ??= new CacheStorage();

    if(!$store)
    {
      return new Cache($namespace, 0, $this->cacheStorage);
    }

    $this->cache ??= new Cache($namespace, 0, $this->cacheStorage);

    return true;
  }
}
