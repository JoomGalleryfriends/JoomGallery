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
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;
use Joomgallery\Plugin\Console\Joomconsole\Extension\JoomgalleryConsole;

return new class implements ServiceProviderInterface {
  public function register(Container $container)
  {
    //$container->registerServiceProvider(new MVCFactory('Joomgallery\\Component\\JoomConsole'));
    // $container->registerServiceProvider(new MVCFactory('Joomgallery\\Component\\JoomgalleryConsole'));
    // $container->registerServiceProvider(new MVCFactory('Joomgallery\\Component\\Joomgallery\\Administrator'));
    $container->registerServiceProvider(new MVCFactory('Joomgallery\\Component\\Joomgallery'));

    $container->set(
      PluginInterface::class,
      function (Container $container) {
        $config = (array)PluginHelper::getPlugin('console', 'joomconsole');
        $subject = $container->get(DispatcherInterface::class);
        $mvcFactory = $container->get(MVCFactoryInterface::class);
        $plugin = new JoomgalleryConsole($subject, $config);

        $plugin->setApplication(Factory::getApplication());

        $plugin->setMVCFactory($mvcFactory);


//                $config = (array)PluginHelper::getPlugin('system', 'joomconsole');
//                $subject = $container->get(DispatcherInterface::class);
//
//                $plugin = new JoomConsole($subject, $config);
//
//                $plugin->setApplication(\Joomla\CMS\Factory::getApplication());
//
//                $plugin->init();

        return $plugin;
      }
    );
  }
};
