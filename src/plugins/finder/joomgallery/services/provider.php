<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Plugin\Finder\Joomgallery\Extension\JoomImage;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface
{
  public function register(Container $container)
  {
    $container->set(
        PluginInterface::class,
        function (Container $container) {
          $plugin     = PluginHelper::getPlugin('finder', 'joomgallery');
          $dispatcher = $container->get(DispatcherInterface::class);

          /** @var \Joomla\CMS\Plugin\CMSPlugin $plugin */
          $plugin = new JoomImage($dispatcher, (array) $plugin);
          $plugin->setApplication(Factory::getApplication());
          $plugin->setDatabase($container->get(DatabaseInterface::class));

          return $plugin;
        }
    );
  }
};
