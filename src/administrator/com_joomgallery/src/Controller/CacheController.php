<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

\defined('_JEXEC') || die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Administrator actions for gallery cache maintenance
 *
 * @package JoomGallery
 * @since   4.5.0
 */
class CacheController extends BaseController
{
  /**
   * Clears every gallery cache scope
   *
   * @return  void
   * @since   4.5.0
   */
  public function clear(): void
  {
    $this->maintain(false);
  }

  /**
   * Removes expired gallery cache entries while retaining valid values
   *
   * @return  void
   * @since   4.5.0
   */
  public function purge(): void
  {
    $this->maintain(true);
  }

  /**
   * Authorises and runs a cache maintenance operation
   *
   * @param   bool  $expiredOnly  whether to remove only expired entries
   *
   * @return  void
   * @since   4.5.0
   */
  private function maintain(bool $expiredOnly): void
  {
    $this->checkToken('post');

    if(!$this->app->isClient('administrator') || !$this->app->getIdentity()->authorise('core.admin', 'com_joomgallery'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $this->setRedirect('index.php?option=com_joomgallery&view=control');

    try
    {
      $this->app->bootComponent('com_joomgallery')->clearCaches($expiredOnly);
      $this->app->enqueueMessage(Text::_($expiredOnly ? 'COM_JOOMGALLERY_CACHE_EXPIRED_CLEARED' : 'COM_JOOMGALLERY_CACHE_CLEARED'));
    }
    catch(\Throwable $e)
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_CACHE_CLEAR_FAILED'), 'error');
    }
  }
}
