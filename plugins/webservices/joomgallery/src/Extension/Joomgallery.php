<?php
/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Plugin\WebServices\Joomgallery\Extension;

use Joomla\Router\Route;
use Joomla\CMS\Router\ApiRouter;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\CMS\Event\Application\BeforeApiRouteEvent;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Services adapter for com_joomgallery.
 *
 * @since  4.4.0
 */
final class Joomgallery extends CMSPlugin implements SubscriberInterface
{
  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return  array
   *
   * @since   4.4.0
   */
  public static function getSubscribedEvents(): array
  {
    return [
      'onBeforeApiRoute' => 'onBeforeApiRoute',
    ];
  }

  /**
   * Registers com_joomgallery API's routes in the application
   *
   * @param   BeforeApiRouteEvent   $event  The event object
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
  {
    $router = $event->getRouter();

    $defaults = ['component' => 'com_joomgallery'];
    // ToDo: Remove when tests finished ?
    // enables access without token
    // $getDefaults = array_merge(['public' => true], $defaults);
    $getDefaults = array_merge(['public' => false], $defaults);

    $this->DBGalleriesImages($router, $getDefaults);

    $this->DBConfigAndVersion($router, $getDefaults);

  }

  /**
   * DB galleries
   *
   * @param   ApiRouter   $router
   * @param   array       $getDefaults
   *
   * @since   4.4.0
   */
  public function DBGalleriesImages(ApiRouter $router, array $getDefaults): void
  {

    $router->createCRUDRoutes(
      'v1/joomgallery/categories',
      'categories',
      ['component' => 'com_joomgallery'],
      $getDefaults
    );

    $router->createCRUDRoutes(
      'v1/joomgallery/images',
      'images',
      ['component' => 'com_joomgallery'],
      $getDefaults
    );

  }

  /**
   * Config and version
   *
   * @param   ApiRouter   $router
   * @param   array       $getDefaults
   *
   *
   * @since   4.4.0
   */
  public function DBConfigAndVersion(ApiRouter $router, array $getDefaults): void
  {
    //--- J! config part of JG -------------------------------------------

    // Joomla parameter part of JG
    $router->addRoutes(
      [
        new Route(['GET'], 'v1/joomgallery/config_in_j', 'configinj.display', [], $getDefaults),
      ]
    );

    //--- JG config sets -------------------------------------------

    $router->createCRUDRoutes(
      'v1/joomgallery/configs',
      'configs',
      ['component' => 'com_joomgallery'],
      $getDefaults
    );

    //--- JG version in db manifest -----------------------------

    // JG version
    $router->addRoutes(
      [
        // version, creationDate
        new Route(['GET'], 'v1/joomgallery/version', 'version.displayItem', [], $getDefaults),
        new Route(['PATCH'], 'v1/joomgallery/version', 'version.edit', [], $getDefaults),
      ]
    );
  }

}
