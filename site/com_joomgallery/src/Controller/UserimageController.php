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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Controller\JoomFormController;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Image class.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UserimageController extends JoomFormController
{
  use RoutingTrait;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   * @since   4.2.0
   */
  protected $component;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Service\Access\Access
   *
   * @access  protected
   * @var     object
   * @since   4.2.0
   */
  protected $acl;

  /**
   * Constructor.
   *
   * @param   array    $config   An optional associative array of configuration settings.
   * @param   object   $factory  The factory.
   * @param   object   $app      The Application for the dispatcher
   * @param   object   $input    Input
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function __construct($config = [], $factory = null, $app = null, $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    // parent view
    $this->default_view = 'userimages';

    // JoomGallery extension class
    $this->component = $this->app->bootComponent(_JOOM_OPTION);

    // Access service class
    $this->component->createAccess();
    $this->acl = $this->component->getAccess();
  }

  /**
   * Save the image and return to calling by calling cancel additionally
   *
   * @param $key
   * @param $urlVar
   *
   * @return bool
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function saveAndClose($key = null, $urlVar = null): bool
  {
    // Check for request forgeries.
    $this->checkToken();

    $isSaved    = $this->save($key, $urlVar) != false;
    $isCanceled = $this->cancel($key) != false;

    if(!$isSaved || !$isCanceled)
    {
      return false;
    }

    return true;
  }

  /**
   * Method to save data.
   *
   * @param $key
   * @param $urlVar
   *
   * @return  bool
   *
   * @throws  \Exception
   * @since   4.2.0
   */
  public function save($key = null, $urlVar = null): bool
  {
    // Check for request forgeries.
    $this->checkToken();

    // Get the user data.
    $data = $this->input->post->get('jform', [], 'array');

    // To avoid data collisions the urlVar may be different from the primary key.
    if(empty($urlVar))
    {
      $urlVar = 'id';
    }
    $recordId = $this->input->getInt($urlVar);

    // Data check
    if(!$data)
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend(), false));

      return false;
    }

    $baseLink = 'index.php?option=com_joomgallery&view=userimage&layout=editImg&id=' . (int) $data['id'];
    $backLink = Route::_($baseLink, false);

    // Access check
    $parent_id = JoomHelper::getParent('image', $recordId);

    if(!$this->acl->checkACL('edit', 'image', $recordId, $parent_id, true))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');
      $this->setRedirect($backLink);

      return false;
    }

    // Initialise variables.
    $app   = Factory::getApplication();
    $model = $this->getModel('Userimage', 'Site');

    // Test whether the data is valid.
    $form = $model->getForm();

    if(!$form)
    {
      $app->enqueueMessage($model->getError(), 'error');
    }

    // Validate the posted data.
    $validData = $model->validate($form, $data);

    // Check for errors.
    if($validData === false)
    {
      // Get the validation messages.
      $errors = $model->getErrors();

      // Push up to three validation messages out to the user.
      for($i = 0, $n = \count($errors); $i < $n && $i < 3; $i++)
      {
        if($errors[$i] instanceof \Exception)
        {
          $app->enqueueMessage($errors[$i]->getMessage(), 'warning');
        }
        else
        {
          $app->enqueueMessage($errors[$i], 'warning');
        }
      }

      // Save the data in the session.
      $app->setUserState('com_joomgallery.edit.image.data', $data);

      // Redirect back to the edit screen.
      $this->setRedirect($backLink);

      $this->redirect();
    }

    // Attempt to save the data.
    if(!$model->save($validData))
    {
      // Save the data in the session.
      $app->setUserState('com_joomgallery.edit.image.data', $validData);

      // Redirect back to the edit screen.
      $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'warning');
      $this->setRedirect($backLink);

      return false;
    }

    // Check in the profile.
    if($model->checkin($validData[$key]) === false)
    {
      // Save the data in the session.
      $app->setUserState('com_joomgallery.edit.image.data', $validData);

      // Redirect to list screen.
      $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'warning');
      $this->setRedirect($backLink);

      return false;
    }

    // Clear the profile id from the session.
    $app->setUserState('com_joomgallery.edit.image.id', null);
    $app->setUserState('com_joomgallery.edit.image.data', null);

    // Redirect to the list screen.
    $this->setMessage(Text::_('COM_JOOMGALLERY_ITEM_SAVE_SUCCESSFUL'));
    $this->setRedirect($backLink);

    return true;
  }

  /**
   * Method to exchange/replace an existing imagetype.
   *
   * @return  boolean  True if imagetype is successfully replaced, false if not.
   *
   * @since   4.0
   */
  public function replace(): bool
  {
    // Check for request forgeries.
    $this->checkToken();

    $app     = $this->app;
    $model   = $this->getModel();
    $data    = $this->input->post->get('jform', [], 'array');
    $context = (string) _JOOM_OPTION . '.' . $this->context . '.replace';
    $id      = \intval($data['id']);

    // Access check.
    if(!$this->allowSave($data, $id))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');
      $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error', 'jerror');

    $this->setRedirect(
        Route::_('index.php?option=' . _JOOM_OPTION . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false)
    );

      return false;
    }

    // Load form data
    $form = $model->getForm($data, false);

    if(!$form)
    {
      $this->setMessage($model->getError(), 'error');

      return false;
    }
    $form->setFieldAttribute('title', 'required', false);
    $form->setFieldAttribute('catid', 'required', false);
    $form->setFieldAttribute('replacetype', 'required', true);
    $form->setFieldAttribute('image', 'required', true);

    // Test whether the data is valid.
    $validData = $model->validate($form, $data);

    // Check for validation errors.
    if($validData === false)
    {
      // Get the validation messages.
      $errors = $model->getErrors();

      // Push up to three validation messages out to the user.
      for($i = 0, $n = \count($errors); $i < $n && $i < 3; $i++)
      {
        if($errors[$i] instanceof \Exception)
        {
          $this->setMessage($errors[$i]->getMessage(), 'warning');
        }
        else
        {
          $this->setMessage($errors[$i], 'warning');
        }
      }

      // Save the data in the session.
      $app->setUserState($context . '.data', $data);

      // Redirect back to the replace screen.
    $this->setRedirect(
        Route::_('index.php?option=' . _JOOM_OPTION . '&view=image&layout=replace&id=' . $id, false)
    );

      return false;
    }

    // Attempt to replace the image.
    if(!$model->replace($validData))
    {
      // Save the data in the session.
      $app->setUserState($context . '.data', $validData);

      // Redirect back to the replace screen.
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_REPLACE_IMAGETYPE', ucfirst($validData['replacetype']), $model->getError()), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_REPLACE_IMAGETYPE', ucfirst($validData['replacetype']), $model->getError()), 'error', 'jerror');

    $this->setRedirect(
        Route::_('index.php?option=' . _JOOM_OPTION . '&view=image&layout=replace&id=' . $id, false)
    );

      return false;
    }

    // Set message
    $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_REPLACE_IMAGETYPE', ucfirst($validData['replacetype'])));
    $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SUCCESS_REPLACE_IMAGETYPE', ucfirst($validData['replacetype'])), 'error', 'jerror');

    // Clear the data from the session.
    $app->setUserState($context . '.data', null);

    // Redirect to edit screen
    $url = 'index.php?option=' . _JOOM_OPTION . '&view=userimage&layout=editImg&id=' . $id;

    // Check if there is a return value
    $return = $this->input->get('return', null, 'base64');

    if(!\is_null($return) && Uri::isInternal(base64_decode($return)))
    {
      $url = base64_decode($return);
    }

    // Redirect to the list screen.
    $this->setRedirect(Route::_($url, false));

    return true;
  }

  /**
   * Method to abort current operation and return to calling page
   *
   * @param $key
   *
   * @return bool
   *
   * @throws \Exception
   *
   * @since   4.2.0
   */
  public function cancel($key = null): bool
  {
    // Check for request forgeries.
    $this->checkToken();

    if($this->input->get('layout', 'edit', 'cmd') == 'replace')
    {
      // Redirect to the edit screen.
      $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=userimage&layout=editImg&id=' . $this->input->getInt('id'), false));

      return true;
    }

    // Get the current edit id.
    $recordId = $this->input->getInt('id');

    // Get the model.
    $model = $this->getModel('Userimage', 'Site');

    // Attempt to check in the current record.
    if($recordId && $model->checkin($recordId) === false)
    {
      // Check-in failed, go back to the record and display a notice.
      $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($recordId), false));

      return false;
    }

    // Clear the profile id from the session.
    $this->app->setUserState('com_joomgallery.edit.image.id', null);
    $this->app->setUserState('com_joomgallery.edit.image.data', null);

    // Redirect to the list screen.
    $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($recordId), false));

    return true;
  }

  /**
   * Method to remove data
   *
   * @return  bool
   *
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  public function remove(): bool
  {
    // Check for request forgeries
    $this->checkToken();

    // Get the current edit id.
    $cid        = (array) $this->input->post->get('cid', [], 'int');
    $boxchecked = (bool) $this->input->getInt('boxchecked', 0);

    if($boxchecked)
    {
      // List view action
      $removeId = (int) $cid[0];
    }
    else
    {
      // Single view action
      $removeId = $this->input->getInt('id', 0);
    }

    // ID check
    if(!$removeId)
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend(), false));

      return false;
    }

    // Access check
    $parent_id = JoomHelper::getParent('image', $removeId);

    if(!$this->acl->checkACL('delete', 'image', $removeId, $parent_id, true))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($removeId), false));

      return false;
    }

    // Get the model.
    $model = $this->getModel('Userimage', 'Site');

    // Attempt to delete the record.
    if($model->delete($removeId) === false)
    {
      $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_DELETE_FAILED', $model->getError()), 'error');
      $this->app->redirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($removeId), false));

      return false;
    }

    // Clear the profile id from the session.
    $this->app->setUserState('com_joomgallery.edit.image.id', null);
    $this->app->setUserState('com_joomgallery.edit.image.data', null);

    // Redirect to the list screen.
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_DELETE_SUCCESSFUL'), 'success');
    $this->app->redirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($removeId), false));

    return true;
  }

  /**
   * Method to edit an existing record.
   *
   * @param $key
   * @param $urlVar
   *
   * @return  bool
   *
   * @throws \Exception
   *
   * @since   4.2.0
   */
  public function edit($key = null, $urlVar = null): bool
  {
    // Get the previous edit id (if any) and the current edit id.
    $previousId = (int) $this->app->getUserState(_JOOM_OPTION . '.edit.image.id');
    $cid        = (array) $this->input->post->get('cid', [], 'int');
    $boxchecked = (bool) $this->input->getInt('boxchecked', 0);

    if($boxchecked)
    {
      $editId = (int) $cid[0];
    }
    else
    {
      $editId = $this->input->getInt('id', 0);
    }

    // ID check
    if(!$editId)
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage() . $this->getItemAppend($editId), false));

      return false;
    }

    // Access check
    $parent_id = JoomHelper::getParent('image', $editId);

    if(!$this->acl->checkACL('edit', 'image', $editId, $parent_id, true))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage() . $this->getItemAppend($editId), false));

      return false;
    }

    // Set the current edit id in the session.
    $this->app->setUserState(_JOOM_OPTION . '.edit.image.id', $editId);

    // Get the model.
    $model = $this->getModel('Image', 'Site');

    // Check out the item
    if(!$model->checkout($editId))
    {
      // Check-out failed, display a notice but allow the user to see the record.
      $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKOUT_FAILED', $model->getError()), 'error');
      $this->setRedirect(Route::_($this->getReturnPage() . $this->getItemAppend($editId), false));

      return false;
    }

    // Check in the previous user.
    if($previousId && $previousId !== $editId)
    {
      $model->checkin($previousId);
    }

    // Redirect to the form screen.
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=userimage&layout=editImg&id=' . $editId . $this->getItemAppend()), false);

    return true;
  }

  /**
   * Check in a checked out image.
   *
   * @return  bool
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function checkin(): bool
  {
    // Check for request forgeries
    $this->checkToken();

    // Get ID
    $cid        = (array) $this->input->post->get('cid', [], 'int');
    $boxchecked = (bool) $this->input->getInt('boxchecked', 0);

    if($boxchecked)
    {
      // List view action
      $id = (int) $cid[0];
    }
    else
    {
      // Single view action
      $id = $this->input->getInt('id', 0);
    }

    // ID check
    if(!$id)
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

      return false;
    }

    // Access check
    $parent_id = JoomHelper::getParent('image', $id);

    if(!$this->acl->checkACL('editstate', 'image', $id, $parent_id, true))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

      return false;
    }

    // Get the model.
    $model = $this->getModel('Userimage', 'Site');

    // Attempt to check-in the current record.
    if($model->checkin($id) === false)
    {
      // Check-in failed, go back to the record and display a notice.
      $this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

      return false;
    }

    // Clear the profile id from the session.
    $this->app->setUserState('com_joomgallery.edit.image.id', null);
    $this->app->setUserState('com_joomgallery.edit.image.data', null);

    // Redirect to the list screen.
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_CHECKIN_SUCCESSFUL'), 'success');

    $this->app->redirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

    return true;
  }

  /**
   * Method to publish an image
   *
   * @return  bool
   *
   * @throws \Exception
   *
   * @since   4.2.0
   */
  public function publish(): bool
  {
    // Check for request forgeries
    $this->checkToken();

    // Get ID
    $cid        = (array) $this->input->post->get('cid', [], 'int');
    $boxchecked = (bool) $this->input->getInt('boxchecked', 0);

    if($boxchecked)
    {
      // List view action
      $id = (int) $cid[0];
    }
    else
    {
      // Single view action
      $id = $this->input->getInt('id', 0);
    }

    // ID check
    if(!$id)
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_ITEMID_MISSING'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

      return false;
    }

    // Access check
    $parent_id = JoomHelper::getParent('image', $id);

    if(!$this->acl->checkACL('editstate', 'image', $id, $parent_id, true))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

      return false;
    }

    // Available states
    $data = ['publish' => 1, 'unpublish' => 0];

    // Get new state.
    $task  = $this->getTask();
    $value = $data[$task];

    // Get the model
    $model = $this->getModel('Userimage', 'Site');

    // Attempt to change state the current record.
    if($model->publish($id, $value) === false)
    {
      // Check-in failed, go back to the record and display a notice.
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ITEM_STATE_ERROR', $model->getError()), 'error');
      $this->setRedirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

      return false;
    }

    // Redirect to the list screen.
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_' . strtoupper($task) . '_SUCCESSFUL'), 'success');
    $this->app->redirect(Route::_($this->getReturnPage('userimages') . $this->getItemAppend($id), false));

    return true;
  }

  /**
   * Method to unpublish an image
   *
   * @return  void
   *
   * @throws \Exception
   *
   * @since   4.2.0
   */
  public function unpublish(): void
  {
    $this->publish();
  }

  /**
   * Method to run batch operations.
   *
   * @param   object   $model  The model of the component being processed.
   *
   * @throws \Exception
   *
   * @since   4.2.0
   */
  public function batch($model): void
  {
    throw new \Exception('Batch operations are not available in the frontend.', 503);
  }

  /**
   * Method to reload a record.
   *
   * @param   string   $key     The name of the primary key of the URL variable.
   * @param   string   $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
   *
   * @throws \Exception
   *
   * @since   4.2.0
   */
  public function reload($key = null, $urlVar = null): void
  {
    throw new \Exception('Reload operation not available.', 503);
  }
}
