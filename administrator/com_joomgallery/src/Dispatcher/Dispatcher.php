<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Factory;

/**
 * ComponentDispatcher class for com_joomgallery
 *
 * @since  4.0.0
 */
class Dispatcher extends ComponentDispatcher
{
  /**
   * Dispatch a controller task. Redirecting the user if appropriate.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function dispatch()
  {
    parent::dispatch();
    $config = null;

    try
    {
      $config = Factory::getApplication()->bootComponent('com_joomgallery')->getConfig();
    }
    catch(\Throwable $th){
    }

    // Handle stuff at the very last, when the runtime finishes
    // and the component gets destructed
    if(!\is_null($config))
    {
      $config->storeCacheToSession();
    }
  }
}
