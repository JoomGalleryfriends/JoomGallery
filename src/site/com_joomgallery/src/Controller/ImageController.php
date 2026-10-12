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
    $this->checkToken();

    $cid = (array) $this->input->post->get('cid', [], 'int');
    $id  = (int) ($cid[0] ?? $this->input->getInt('id', 0));

    if(!$id)
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('gallery'), false));

      return false;
    }

    $parentId = JoomHelper::getParent('image', $id);

    if(!$this->acl->checkACL('editstate', 'image', $id, $parentId, true))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('gallery'), false));

      return false;
    }

    $task  = $this->getTask();
    $value = $task === 'publish' ? 1 : 0;
    $pks   = [$id];
    $model = $this->component->getMVCFactory()->createModel('Image', 'Administrator');

    if(!$model || $model->publish($pks, $value) === false)
    {
      $message = $model ? $model->getError() : Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE');
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ITEM_STATE_ERROR', $message), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('gallery'), false));

      return false;
    }

    $this->setMessage(Text::_('COM_JOOMGALLERY_ITEM_' . strtoupper($task) . '_SUCCESSFUL'), 'success');
    $this->setRedirect(Route::_($this->getReturnPage('gallery'), false));

    return true;
  }

  /**
   * Method to unpublish an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function unpublish(): bool
  {
    return $this->publish();
  }
}
