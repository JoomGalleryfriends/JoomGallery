<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Controller\FormController;
use \Joomgallery\Component\Joomgallery\Administrator\Controller\JoomFormController;

/**
 * Category form controller class.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryformController extends JoomFormController // FormController
{
  use RoutingTrait;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  protected $component;

	/**
   * Joomgallery\Component\Joomgallery\Administrator\Service\Access\Access
   *
   * @access  protected
   * @var     object
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
   * @since   4.0.0
   */
  public function __construct($config = [], $factory = null, $app = null, $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    $this->default_view = 'category';

    // JoomGallery extension class
		$this->component = $this->app->bootComponent(_JOOM_OPTION);

		// Access service class
		$this->component->createAccess();
		$this->acl = $this->component->getAccess();
  }

	/**
	 * Method to save data.
	 *
	 * @return  void
	 *
	 * @throws  \Exception
	 * @since   4.0.0
	 */
	public function save($key = NULL, $urlVar = NULL)
	{
		// Check for request forgeries.
		$this->checkToken();

    $task = Factory::getApplication()->input->get('task', '', 'cmd');

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
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend(),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('edit', 'category', $recordId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($data->id),false));

			return false;
		}

    // Initialise variables.
		$app   = Factory::getApplication();
		$model = $this->getModel('Categoryform', 'Site');

		// Validate the posted data.
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
			for($i = 0, $n = count($errors); $i < $n && $i < 3; $i++)
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
			$app->setUserState('com_joomgallery.edit.category.data', $data);

			// Redirect back to the edit screen.
			$this->setRedirect(Route::_('index.php?option=com_joomgallery&view=categoryform&'.$this->getItemAppend($recordId), false));

			$this->redirect();
		}

		// Attempt to save the data.
		if(!$model->save($validData))
		{
			// Save the data in the session.
			$app->setUserState('com_joomgallery.edit.category.data', $validData);

			// Redirect back to the edit screen.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $model->getError()), 'warning');
			$this->setRedirect(Route::_('index.php?option=com_joomgallery&view=categoryform&'.$this->getItemAppend($recordId), false));

			return false;
		}

		// Check in the profile.
		if($model->checkin($validData[$key]) === false)
		{
			// Save the data in the session.
			$app->setUserState('com_joomgallery.edit.category.data', $validData);

			// Redirect to list screen.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'warning');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($recordId), false));

			return false;
		}

		// Clear the profile id from the session.
		$app->setUserState('com_joomgallery.edit.category.id', null);
		$app->setUserState('com_joomgallery.edit.category.data', null);

		// Redirect to the list screen.
		$this->setMessage(Text::_('COM_JOOMGALLERY_ITEM_SAVE_SUCCESSFUL'));
		$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($data->id),false));
	}

	/**
	 * Method to abort current operation
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	public function cancel($key = NULL)
	{
		// Check for request forgeries.
		$this->checkToken();

		// Get the current edit id.
		$recordId = $this->input->getInt('id');

		// Get the model.
		$model = $this->getModel('Categoryform', 'Site');

		// Attempt to check-in the current record.
		if($recordId && $model->checkin($recordId) === false)
		{
			// Check-in failed, go back to the record and display a notice.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($recordId), false));

			return false;
		}

		// Clear the profile id from the session.
		$this->app->setUserState('com_joomgallery.edit.category.id', null);
		$this->app->setUserState('com_joomgallery.edit.category.data', null);

		// Redirect to the list screen.
		$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($recordId),false));
	}

	/**
	 * Method to remove data
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   4.0.0
	 */
	public function remove()
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
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend(),false));

			return false;
		}

		// Access check
		if(!$this->acl->checkACL('delete', 'category', $removeId))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId),false));

			return false;
		}

    // Get the model.
    $model = $this->getModel('Categoryform', 'Site');

		// Attempt to delete the record.
		if($model->delete($removeId) === false)
		{
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_DELETE_FAILED', $model->getError()), 'error');
			$this->app->redirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId), false));

			return false;
		}

		// Attempt to check-in the current record.
		if($model->checkin($removeId) === false)
		{
			// Check-in failed, go back to the record and display a notice.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId), false));

			return false;
		}

		$this->app->setUserState('com_joomgallery.edit.category.id', null);
		$this->app->setUserState('com_joomgallery.edit.category.data', null);

		$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_DELETE_SUCCESSFUL'), 'success');
		$this->app->redirect(Route::_($this->getReturnPage().'&'.$this->getItemAppend($removeId), false));
	}

  /**
   * Method to edit an existing record.
   *
   * @throws \Exception
   */
  public function edit($key = null, $urlVar = null)
  {
    throw new \Exception('Edit category not possible. Use category controller instead.', 503);
  }

	/**
	 * Checkin a checked-out category.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function checkin()
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
			$this->setRedirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('editstate', 'category', $id))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id),false));

			return false;
		}

		// Get the model.
		$model  = $this->getModel('Categoryform', 'Site');

		// Attempt to check-in the current record.
		if($model->checkin($id) === false)
		{
			// Check-in failed, go back to the record and display a notice.
			$this->setMessage(Text::sprintf('JLIB_APPLICATION_ERROR_CHECKIN_FAILED', $model->getError()), 'error');
			$this->setRedirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id), false));

			return false;
		}

		// Clear the profile id from the session.
		$this->app->setUserState('com_joomgallery.edit.category.id', null);
		$this->app->setUserState('com_joomgallery.edit.category.data', null);

		// Redirect to the list screen.
		$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_CHECKIN_SUCCESSFUL'), 'success');
		$this->app->redirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id), false));
	}

  /**
	 * Method to publish a category
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function publish()
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
			$this->setRedirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id),false));

			return false;
		}

    // Access check
		if(!$this->acl->checkACL('editstate', 'category', $id))
		{
			$this->setMessage(Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED'), 'error');
			$this->setRedirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id),false));

			return false;
		}

    // Available states
    $data  = ['publish' => 1, 'unpublish' => 0];

    // Get new state.
    $task  = $this->getTask();
    $value = $data[$task];

    // Get the model
    $model  = $this->getModel('Categoryform', 'Site');

    // Attempt to change state the current record.
		if($model->publish($id, $value) === false)
		{
			// Check-in failed, go back to the record and display a notice.
			$this->setMessage(Text::sprintf('COM_JOOMGALLERY_ITEM_STATE_ERROR', $model->getError()), 'error');
			$this->setRedirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id), false));

			return false;
		}

    // Redirect to the list screen.
		$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ITEM_'.\strtoupper($task).'_SUCCESSFUL'), 'success');
		$this->app->redirect(Route::_($this->getReturnPage('categories').'&'.$this->getItemAppend($id), false));
  }

  /**
	 * Method to unpublish a category
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function unpublish()
	{
    $this->publish();
  }

  /**
   * Method to run batch operations.
   *
   * @param  object  $model  The model of the component being processed.
   *
   * @throws \Exception
   */
  public function batch($model)
  {
    throw new \Exception('Batch operations are not available in the frontend.', 503);
  }

  /**
   * Method to reload a record.
   *
   * @param   string  $key     The name of the primary key of the URL variable.
   * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
   *
   * @throws \Exception
   */
  public function reload($key = null, $urlVar = null)
  {
    throw new \Exception('Reload operation not available.', 503);
  }
}
