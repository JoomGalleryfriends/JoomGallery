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
   * Registers com_joomgallery API's routes in the application
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

	  $this->DBGalleriesImages($router, $getDefaults);

	  $this->DBConfigAndVersion($router, $getDefaults);

	  $this->UploadImages($router, $getDefaults);

  //    $this->($router);

  //    $this->createContentHistoryRoutes($router);
  }

  /**
   * DB galleries
   *
   * @param   ApiRouter  $router
   * @param   array      $getDefaults
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
      $getDefaults
    );

    $router->createCRUDRoutes(
      'v1/joomgallery/images',
      'images',
      ['component' => 'com_joomgallery'],
      $getDefaults
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
    //--- J! config part of JG -------------------------------------------

	  // joomla part of JG (not much there)
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

  /**
   * @param   ApiRouter  $router
   * @param   array      $getDefaults
   *
   * @since version
   */
  private function UploadImages(ApiRouter $router, array $getDefaults): void
  {
    // Gid or name
    $router->addRoutes(
      [
        new Route(
            ['GET'],
            'v1/joomgallery/latestcategory',
            'latestcategory.displayItem',
            [],
            $getDefaults
        ),

        new Route(
          ['POST'],
          'v1/joomgallery/db_reserve_image_id',
          'reserveimgid.db_reserve_image_id',
          ['catid' => '(\d+)'],
          $getDefaults
        ),

        new Route(
            ['POST'],
            'v1/joomgallery/upload_image_file',
            'uploadimgfile.image_data_upload',
            [],
            $getDefaults
        ),

        new Route(
            ['PATCH'],
            'v1/joomgallery/upload_image_file/:image_id',
            'uploadimgfile.patch_image_upload_file',
            ['imgid' => '(\d+)'],
            $getDefaults
        ),

        new Route(
            ['PATCH'],
            'v1/joomgallery/save_metadata',
            'savemetadata.save_metadata',
            [],
            $getDefaults
        ),

        // ToDo: recreate sizes
//        new Route(
//            ['PATCH'],
//            'v1/joomgallery/recreate_sizes',
//            'recreatesizes.recreate_sizes',
//            [],
//            $getDefaults
//        ),

            // ToDo: apply meta data
//        new Route(
//            ['PATCH'],
//            'v1/joomgallery/apply_meta_data',
//            'applymetadata.apply_meta_data',
//            [],
//            $getDefaults
//        ),

      ]
    );
  }
}
