<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomgallery\Plugin\WebServices\Joomgallery\Extension\Joomgallery;

return new class implements ServiceProviderInterface
{
  public function register(Container $container)
  {
    $container->set(
      PluginInterface::class,
      function (Container $container)
      {
        $plugin     = PluginHelper::getPlugin('webservices', 'joomgallery');
        $dispatcher = $container->get(DispatcherInterface::class);

        /** @var \Joomla\CMS\Plugin\CMSPlugin $plugin */
        $plugin = new Joomgallery($dispatcher, (array) $plugin);
        $plugin->setApplication(Factory::getApplication());

        return $plugin;
      }
    );
  }
};
