<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Router;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Categories\CategoryFactoryInterface;
use \Joomla\CMS\Component\Router\RouterFactoryInterface;
use \Joomla\CMS\Extension\Service\Provider\RouterFactory as RouterFactoryBaseProvider;
use \Joomla\Database\DatabaseInterface;
use \Joomla\DI\Container;
use \Joomla\DI\ServiceProviderInterface;

/**
 * Service provider for the service router factory.
 *
 * @since  4.0.0
 */
class RouterFactoryProvider extends RouterFactoryBaseProvider implements ServiceProviderInterface
{
    /**
     * The module namespace
     *
     * @var  string
     *
     * @since   4.0.0
     */
    private $namespace;

    /**
     * DispatcherFactory constructor.
     *
     * @param   string  $namespace  The namespace
     *
     * @since   4.0.0
     */
    public function __construct(string $namespace)
    {
        $this->namespace = $namespace;
    }

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
            RouterFactoryInterface::class,
            function (Container $container) {
                $categoryFactory = null;

                if ($container->has(CategoryFactoryInterface::class))
                {
                    $categoryFactory = $container->get(CategoryFactoryInterface::class);
                }

                return new \Joomgallery\Component\Joomgallery\Administrator\Router\RouterFactory(
                    $this->namespace,
                    $categoryFactory,
                    $container->get(DatabaseInterface::class)
                );
            }
        );
    }
}
