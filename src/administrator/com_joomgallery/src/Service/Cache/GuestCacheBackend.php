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

use Joomla\CMS\Cache\Cache as JoomlaCache;
use Joomla\CMS\Factory;

/**
 * Joomla cache operations with stable keys across administrator and site clients
 *
 * Guest namespaces already describe the language and permission context.
 * Joomla's optional device prefix must not hide entries from maintenance.
 *
 * @package JoomGallery
 * @since   4.5.0
 */
final class GuestCacheBackend
{
  /**
   * Joomla cache instance used for backend operations
   *
   * @var     JoomlaCache
   * @since   4.5.0
   */
  private JoomlaCache $cache;

  /**
   * Stores the Joomla cache dependency
   *
   * @param   JoomlaCache  $cache  the configured Joomla cache
   *
   * @return  void
   * @since   4.5.0
   */
  public function __construct(JoomlaCache $cache)
  {
    $this->cache = $cache;
  }

  /**
   * Executes one synchronous operation without Joomla's device-specific prefix
   *
   * The application setting is restored immediately, including on exceptions.
   *
   * @param   string  $method     the Joomla cache method
   * @param   array   $arguments  the method arguments
   *
   * @return  mixed
   * @since   4.5.0
   */
  public function __call(string $method, array $arguments): mixed
  {
    $app      = Factory::getApplication();
    $previous = $app->get('cache_platformprefix', false);
    $app->set('cache_platformprefix', false);

    try
    {
      return $this->cache->$method(...$arguments);
    }
    finally
    {
      $app->set('cache_platformprefix', $previous);
    }
  }
}
