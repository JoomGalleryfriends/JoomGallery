<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use Joomgallery\Component\Joomgallery\Administrator\MVC\MVCFactoryProvider as MVCFactory;
use Joomgallery\Component\Joomgallery\Administrator\Router\RouterFactoryProvider as RouterFactory;
use Joomgallery\Component\Joomgallery\Administrator\User\UserFactory;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
//use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * The Joomgallery service provider.
 *
 * @package JoomGallery
 * @since  4.0.0
 */
return new class implements ServiceProviderInterface {
  /**
   * Registers the service provider with a DI container.
   *
   * @param   Container  $container  The DI container.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function register(Container $container)
  {
    $container->registerServiceProvider(new MVCFactory('\\Joomgallery\\Component\\Joomgallery'));
    $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomgallery\\Component\\Joomgallery'));
    $container->registerServiceProvider(new RouterFactory('\\Joomgallery\\Component\\Joomgallery'));

    // Create the component class
    $container->set(
        ComponentInterface::class,
        function (Container $container) {
          $component = new JoomgalleryComponent($container->get(ComponentDispatcherFactoryInterface::class));

          $component->setRegistry($container->get(Registry::class));
          $component->setMVCFactory($container->get(MVCFactoryInterface::class));
          $component->setRouterFactory($container->get(RouterFactoryInterface::class));

          return $component;
        }
    );
  }
};
