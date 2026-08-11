<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Dispatcher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Factory;

/**
 * ComponentDispatcher class for com_joomgallery
 *
 * @package JoomGallery
 * @since   4.0.0
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

        $component = null;
        $config    = null;
        $access    = null;

        try
        {
            $component = Factory::getApplication()->bootComponent('com_joomgallery');
            $config    = $component->getConfig();
        }
        catch(\Throwable $th)
        {
        }

        try
        {
            if(!\is_null($component))
            {
                $access = $component->getAccess();
            }
        }
        catch(\Throwable $th)
        {
        }

        if(!\is_null($config))
        {
            $config->storeCacheToSession();
        }

        if(!\is_null($access))
        {
            $access->storeCacheToSession();
        }
    }
}
