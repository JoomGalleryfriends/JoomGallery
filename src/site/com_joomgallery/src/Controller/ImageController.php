<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Controller\JoomFormController;
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
   * Add the displayed image to one of the current user's collections.
   *
   * @return  boolean  True when the mapping exists after the request.
   *
   * @since   DEPLOY_VERSION
   */
  public function addToCollection(): bool
  {
    $this->checkToken();

    $imageId      = $this->input->post->getInt('id');
    $collectionId = $this->input->post->getInt('collection_id');
    $redirect     = Route::_($this->getReturnPage('image'), false);
    $identity     = $this->app->getIdentity();

    if($identity->guest)
    {
      $this->setRedirect($redirect, Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'warning');

      return false;
    }

    try
    {
      if($imageId <= 0 || $collectionId <= 0)
      {
        throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_SELECTION'), 400);
      }

      $this->assertCollectibleImage($imageId);

      $collectionModel = $this->component->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);

      if(!$collectionModel->ownsCollection($collectionId))
      {
        throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
      }

      if(!$collectionModel->addMapping($collectionId, $imageId, true))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_ADD_FAILED'));
      }

      $table = $collectionModel->getTable();
      $table->load($collectionId);
      $title = htmlspecialchars((string) $table->title, ENT_QUOTES, 'UTF-8');
      $this->setRedirect($redirect, Text::sprintf('COM_JOOMGALLERY_COLLECTION_IMAGE_ADDED', $title), 'success');

      return true;
    }
    catch(\Throwable $e)
    {
      $safeMessage = \in_array((int) $e->getCode(), [400, 403], true)
        ? $e->getMessage() : Text::_('COM_JOOMGALLERY_COLLECTION_ADD_FAILED');

      if(!\in_array((int) $e->getCode(), [400, 403], true))
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $this->setRedirect($redirect, $safeMessage, 'error');

      return false;
    }
  }

  /**
   * Toggle the displayed image in one of the current user's collections.
   *
   * @return  boolean  True when the request was processed successfully.
   *
   * @since   DEPLOY_VERSION
   */
  public function toggleCollection(): bool
  {
    $this->checkToken();

    $imageId      = $this->input->post->getInt('id');
    $collectionId = $this->input->post->getInt('collection_id');
    $redirect     = Route::_($this->getReturnPage('image'), false);
    $identity     = $this->app->getIdentity();

    if($identity->guest)
    {
      $this->setRedirect($redirect, Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'warning');

      return false;
    }

    try
    {
      $this->assertCollectibleImage($imageId);
      $collectionModel = $this->component->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);
      $mapped          = $collectionModel->toggleMapping($collectionId, $imageId);
      $table           = $collectionModel->getTable();
      $table->load($collectionId);
      $title   = htmlspecialchars(Text::_((string) $table->title), ENT_QUOTES, 'UTF-8');
      $message = $mapped ? 'COM_JOOMGALLERY_COLLECTION_IMAGE_ADDED' : 'COM_JOOMGALLERY_COLLECTION_IMAGE_REMOVED';
      $this->setRedirect($redirect, Text::sprintf($message, $title), 'success');

      return true;
    }
    catch(\Throwable $e)
    {
      $safeMessage = \in_array((int) $e->getCode(), [400, 403, 404], true)
        ? $e->getMessage() : Text::_('COM_JOOMGALLERY_COLLECTION_UPDATE_FAILED');

      if(!\in_array((int) $e->getCode(), [400, 403, 404], true))
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $this->setRedirect($redirect, $safeMessage, 'error');

      return false;
    }
  }

  /**
   * Reject collection requests for unpublished, protected or inaccessible images.
   *
   * @param   int  $imageId  Image ID.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  protected function assertCollectibleImage(int $imageId): void
  {
    if($imageId <= 0)
    {
      throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_SELECTION'), 400);
    }

    $imageModel = $this->component->getMVCFactory()->createModel('Image', 'Site', ['ignore_request' => true]);
    $image      = $imageModel->getItem($imageId);
    $viewLevels = $this->app->getIdentity()->getAuthorisedViewLevels();

    if((int) $image->published !== 1 || (int) $image->approved !== 1
      || !\in_array((int) $image->access, $viewLevels, true)
      || !$imageModel->getCategoryPublished() || !$imageModel->getCategoryAccess()
      || $imageModel->getCategoryProtected())
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
    }
  }

  /**
   * Edit an existing image.
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function edit($key = null, $urlVar = null)
  {
    throw new \Exception('Edit an existing image not possible. Use userimage controller instead.', 503);
  }


  /**
   * Add a new image: Not available
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function add()
  {
    throw new \Exception('Edit an existing image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Load item to edit associations in com_associations
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function editAssociations()
  {
    throw new \Exception('Edit associations not possible. Use userimage controller instead.', 503);
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
    throw new \Exception('Removing image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Checkin a checked out image.
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function checkin()
  {
    throw new \Exception('Check-in image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Method to publish an image.
   *
   * @return  bool
   *
   * @throws  \Exception
   *
   * @since   __DEPLOY_VERSION__
   */
  public function publish(): bool
  {
    throw new \Exception('Publishing an image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Method to unpublish an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function unpublish(): bool
  {
    throw new \Exception('Unpublishing an image not possible. Use userimage controller instead.', 503);
  }
}
