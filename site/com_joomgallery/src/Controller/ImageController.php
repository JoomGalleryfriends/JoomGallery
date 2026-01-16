<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Controller\JoomFormController;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;

/**
 * Image controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomFormController
{
  use RoutingTrait;

  protected $view_list = 'images';

  /**
   * Edit an existing image.
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function edit($key = null, $urlVar = null)
  {
    throw new \Exception('Edit an existing image not possible. Use imageform controller instead.', 503);
  }


  /**
   * Add a new image: Not available
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function add()
  {
    throw new \Exception('Edit an existing image not possible. Use imageform controller instead.', 503);
  }

  /**
   * Load item to edit associations in com_associations
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function editAssociations()
  {
    throw new \Exception('Edit associations not possible. Use imageform controller instead.', 503);
  }

  /**
   * Method to add multiple new image records.
   *
   * @return  boolean  True if the record can be added, false if not.
   *
   * @since   4.0
   */
  public function ajaxsave(): bool
  {
    $result = ['error' => false];

    try
    {
      if(!parent::save())
      {
        $result['success'] = false;
        $result['error']   = $this->message;
      }
      else
      {
        $result['success'] = true;
        $result['record']  = $this->component->cache->get('imgObj');
      }

      $json = json_encode($result, JSON_FORCE_OBJECT);
      echo new JsonResponse($json);

      $this->app->close();
    }
    catch(\Exception $e)
    {
      echo new JsonResponse($e);

      $this->app->close();
    }

    return true;
  }

  /**
   * Remove an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function remove()
  {
    throw new \Exception('Removing image not possible. Use imageform controller instead.', 503);
  }

  /**
   * Checkin a checked out image.
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function checkin()
  {
    throw new \Exception('Check-in image not possible. Use imageform controller instead.', 503);
  }

  /**
   * Method to publish an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function publish()
  {
    throw new \Exception('Publish image not possible. Use imageform controller instead.', 503);
  }

  /**
   * Method to unpublish an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function unpublish()
  {
    throw new \Exception('Unpublish image not possible. Use imageform controller instead.', 503);
  }
}
