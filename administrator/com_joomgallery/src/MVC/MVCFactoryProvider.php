<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\MVC;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\User\UserFactory;
use \Joomla\CMS\Cache\CacheControllerFactoryInterface;
use \Joomla\CMS\Extension\Service\Provider\MVCFactory as MVCFactoryBaseProvider;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\Mail\MailerFactoryInterface;
use \Joomla\CMS\MVC\Factory\ApiMVCFactory;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomla\CMS\Router\SiteRouter;
use \Joomla\Database\DatabaseInterface;
use \Joomla\DI\Container;
use \Joomla\DI\ServiceProviderInterface;
use \Joomla\Event\DispatcherInterface;

/**
 * Service provider for the service MVC factory.
 *
 * @since  4.0.0
 */
class MVCFactoryProvider extends MVCFactoryBaseProvider implements ServiceProviderInterface
{
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
    $container->set(
        MVCFactoryInterface::class,
        function (Container $container) {
          if(Factory::getApplication()->isClient('api'))
          {
            $factory = new ApiMVCFactory('\\Joomgallery\\Component\\Joomgallery');
          }
          else
          {
            $factory = new MVCFactory('\\Joomgallery\\Component\\Joomgallery');
          }

          $factory->setFormFactory($container->get(FormFactoryInterface::class));
          $factory->setDispatcher($container->get(DispatcherInterface::class));
          $factory->setDatabase($container->get(DatabaseInterface::class));
          $factory->setSiteRouter($container->get(SiteRouter::class));
          $factory->setCacheControllerFactory($container->get(CacheControllerFactoryInterface::class));
          $factory->setUserFactory(new UserFactory($container->get(DatabaseInterface::class)));
          $factory->setMailerFactory($container->get(MailerFactoryInterface::class));

          return $factory;
        }
    );
  }
}
