<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Plugin\WebServices\Joomgallery\Extension;

use Joomla\CMS\Event\Application\BeforeApiRouteEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\ApiRouter;
use Joomla\Event\SubscriberInterface;
use Joomla\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Web Services adapter for com_joomgallery.
 *
 * @since  4.0.0
 */
final class Joomgallery extends CMSPlugin implements SubscriberInterface
{
  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return  array
   *
   * @since   5.1.0
   */
  public static function getSubscribedEvents(): array
  {
    return [
      'onBeforeApiRoute' => 'onBeforeApiRoute',
    ];
  }

  /**
   * Registers com_joomgallery's API's routes in the application
   *
   * @param   BeforeApiRouteEvent   $event  The event object
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
  {
    $router = $event->getRouter();

    $defaults = ['component' => 'com_joomgallery'];
    // ToDo: Remove when tests finished, enables access without token
    // $getDefaults = array_merge(['public' => true], $defaults);
    $getDefaults = array_merge(['public' => false], $defaults);

//          new Route(['GET'], 'v1/example/items/:slug', 'item.displayItem',
//              ['slug' => '(.*)'], ['option' => 'com_example']),

    $this->DBGalleriesImages($router, $getDefaults);

    $this->DBConfigAndVersion($router, $getDefaults);

    $this->UploadImages($router, $getDefaults);

    //    $this->($router);

    //    $this->createContentHistoryRoutes($router);
  }

  /**
   * DB galleries
   * @param   ApiRouter  $router
   *
   *
   * @since version
   */
  public function DBGalleriesImages(ApiRouter $router, array $getDefaults): void
  {
//      $router->addRoutes([
//          new Route(['GET'], 'v1/joomgallery', 'joomgallery.displayItem', [], $getDefaults),
//      ]);

    $router->createCRUDRoutes(
        'v1/joomgallery/categories',
        'categories',
        ['component' => 'com_joomgallery'],
        true // ToDo: Remove when tests finished
    );

    $router->createCRUDRoutes(
        'v1/joomgallery/images',
        'images',
        ['component' => 'com_joomgallery'],
        true // ToDo: Remove when tests finished
    );


    // ToDo: custom fields
    // $this->createFieldsRoutes($router);
  }

  /**
   * Config and version
   * @param   ApiRouter  $router
   * @param   array      $getDefaults
   *
   *
   * @since version
   */
  public function DBConfigAndVersion(ApiRouter $router, array $getDefaults): void
  {
    // joomla part of JG (not much there)
    $router->addRoutes(
        [
          new Route(['GET'], 'v1/joomgallery/config_in_j', 'configinj.display', [], $getDefaults),
        ]
    );

    // JG config sets
    $router->createCRUDRoutes(
        'v1/joomgallery/configs',
        'configs',
        ['component' => 'com_joomgallery'],
        true // ToDo: Remove when tests finished
    );


    // JG version
    $router->addRoutes(
        [
          new Route(['GET'], 'v1/joomgallery/version', 'version.display', [], $getDefaults),
        ]
    );
  }

  /**
   * @param   ApiRouter  $router
   * @param   array      $getDefaults
   *
   * @since version
   */
  private function UploadImages(ApiRouter $router, array $getDefaults)
  {
    // Gid or name
    $router->addRoutes(
        [
          //            new Route(['GET'], 'v1/joomgallery/upload/:gid',
          //                'UploadApi.upload_img',
          //                ['id' => '(\d+)'],
          //                $getDefaults),

          new Route(
              ['GET'],
              'v1/joomgallery/latestcategory',
              'latestcategory.displayList',
              [],
              $getDefaults
          ),

          // ToDo: ? use upload_file as 'single'  command
          //            new Route(['POST'], 'v1/joomgallery/upload/:gallery_name',
          new Route(
              ['POST'],
              'v1/joomgallery/db_reserve_image_id',
              // 'UploadApi.upload_img',
              'upload.api_db_reserve_image_id',
              //['gallery_name' => '(.*)'],
              [],
              $getDefaults
          ),

          new Route(
              ['POST'],
              'v1/joomgallery/upload_image_file',
              // 'UploadApi.upload_img',
              'upload.api_upload_image_file',
              //['gallery_name' => '(.*)'],
              [],
              $getDefaults
          ),

          new Route(
              ['PATCH'],
              'v1/joomgallery/recreate_sizes',
              // 'UploadApi.upload_img',
              'upload.api_recreate_sizes',
              //['gallery_name' => '(.*)'],
              [],
              $getDefaults
          ),

          //        // image files
          //        $router->createCRUDRoutes(
          //            'v1/joomgallery/image_files',
          //            'UploadApi',
          //            ['component' => 'com_joomgallery'],
          //            $getDefaults,
          //        );

        ]
    );
  }
}
