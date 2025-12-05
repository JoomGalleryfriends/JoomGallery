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
     * @param   BeforeApiRouteEvent  $event  The event object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    public function onBeforeApiRoute(BeforeApiRouteEvent $event): void
    {
        $router = $event->getRouter();

        $defaults    = ['component' => 'com_joomgallery'];
        // $getDefaults = array_merge(['public' => false], $defaults);
        $getDefaults = array_merge(['public' => false], $defaults); // ToDo: Remove when tests finished, enables access without token

//		    new Route(['GET'], 'v1/example/items/:slug', 'item.displayItem',
//			    ['slug' => '(.*)'], ['option' => 'com_example']),

      $router->addRoutes([
        new Route(['GET'], 'v1/joomgallery/version', 'version.display', [], $getDefaults),
      ]);

        // joomla part of JG (not much there)
        $router->addRoutes([
          new Route(['GET'], 'v1/joomgallery/config_in_j', 'configinj.display', [], $getDefaults),
        ]);

//      $router->addRoutes([
//		    new Route(['GET'], 'v1/joomgallery', 'joomgallery.displayItem', [], $getDefaults),
//	    ]);

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

         $router->createCRUDRoutes(
             'v1/joomgallery/configs',
             'configs',
             ['component' => 'com_joomgallery'],
             true // ToDo: Remove when tests finished
         );


         // custom fields
        // $this->createFieldsRoutes($router);

  //      $this->createContentHistoryRoutes($router);
    }

//    /**
//     * Create fields routes
//     *
//     * @param   ApiRouter  &$router  The API Routing object
//     *
//     * @return  void
//     *
//     * @since   4.0.0
//     */
//    private function createFieldsRoutes(&$router): void
//    {
//        $router->createCRUDRoutes(
//            'v1/fields/content/articles',
//            'fields',
//            ['component' => 'com_fields', 'context' => 'com_content.article']
//        );
//
//        $router->createCRUDRoutes(
//            'v1/fields/content/categories',
//            'fields',
//            ['component' => 'com_fields', 'context' => 'com_content.categories']
//        );
//
//        $router->createCRUDRoutes(
//            'v1/fields/groups/content/articles',
//            'groups',
//            ['component' => 'com_fields', 'context' => 'com_content.article']
//        );
//
//        $router->createCRUDRoutes(
//            'v1/fields/groups/content/categories',
//            'groups',
//            ['component' => 'com_fields', 'context' => 'com_content.categories']
//        );
//    }

    /**
     * Create contenthistory routes
     *
     * @param   ApiRouter  &$router  The API Routing object
     *
     * @return  void
     *
     * @since   4.0.0
     */
    private function createContentHistoryRoutes(&$router): void
    {
        $defaults    = [
          'component'  => 'com_contenthistory',
          'type_alias' => 'com_joomgallery.joomgallery',
          'type_id'    => 1,
        ];
        $getDefaults = array_merge(['public' => false], $defaults);

        $routes = [
          new Route(['GET'], 'v1/joomgallery/:id/contenthistory', 'history.displayList', ['id' => '(\d+)'], $getDefaults),
          new Route(['PATCH'], 'v1/joomgallery/:id/contenthistory/keep', 'history.keep', ['id' => '(\d+)'], $defaults),
          new Route(['DELETE'], 'v1/joomgallery/:id/contenthistory', 'history.delete', ['id' => '(\d+)'], $defaults),
        ];

        $router->addRoutes($routes);

    }
}

