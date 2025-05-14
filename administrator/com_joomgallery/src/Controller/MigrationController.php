<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Input\Input;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Response\JsonResponse;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\MVC\Controller\BaseController;
use \Joomla\CMS\Form\FormFactoryAwareTrait;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\Form\FormFactoryAwareInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;

/**
 * Migration controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MigrationController extends BaseController implements FormFactoryAwareInterface
{
  use FormFactoryAwareTrait;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @var     JoomgalleryComponent
   * @since   4.0.0
   */
  protected $component;

  /**
   * The context for storing internal data, e.g. record.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $context = _JOOM_OPTION.'.migration';

  /**
   * The URL option for the component.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $option = _JOOM_OPTION;

  /**
   * The prefix to use with controller messages.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $text_prefix = _JOOM_OPTION_UC;

  /**
   * Constructor.
   *
   * @param   array                 $config       An optional associative array of configuration settings.
   *                                              Recognized key values include 'name', 'default_task', 'model_path', and
   *                                              'view_path' (this list is not meant to be comprehensive).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   CMSApplication        $app          The Application for the dispatcher
   * @param   Input                 $input        Input
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   4.0.0
   */
  public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null, ?FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $app, $input);

    $this->setFormFactory($formFactory);
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->component->createAccess();

    // As copy should be standard on forms.
    $this->registerTask('check', 'precheck');
  }

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since   4.0.0
	 */
	public function getModel($name = 'Migration', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}

  /**
   * Execute a task by triggering a Method in the derived class.
   *
   * @param   string  $task    The task to perform. If no matching task is found, the '__default' task is executed, if
   *                           defined.
   *
   * @return  mixed   The value returned by the called Method.
   *
   * @throws  Exception
   * @since   4.0.0
   */
  public function execute($task)
  {
    // Before execution of the task
    if(!empty($task))
    {
      if(\property_exists($this, 'task'))
      {
        $this->task = $task;
      }
      
      $this->component->msgUserStateKey = 'com_joomgallery.'.$task.'.messages';
    }

    // Guess context if needed
    if(empty($this->context) || ($this->context && \strpos($this->context, $task) === false))
    {
      $this->context = _JOOM_OPTION . '.' . $this->name;

      if(\property_exists($this, 'task') && !empty($this->task))
      {
        $this->context .= '.' . $this->task;
      }
    }

    if(!$this->component->isRawTask($this->context))
    {
      // Get messages from session
      $this->component->msgFromSession();
    }
    

    // execute the task
    $res = parent::execute($task);

    // After execution of the task
    if(!$this->component->isRawTask($this->context))
    {
      // Print messages from session
      if(!$this->component->msgWithhold && $res->component->error)
      {
        $this->component->printError();
      }
      elseif(!$this->component->msgWithhold)
      {
        $this->component->printWarning();
        $this->component->printDebug();
      }
    }

    return $res;
  }

  /**
   * Method to cancel a migration.
   *
   * @return  boolean  True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function cancel()
  {
    $this->checkToken();

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);      
    }

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Get migrateables if available
    try
    {
      $migrateables = $model->getMigrateables();
    }
    catch(\Exception $e)
    {
      $migrateables = false;
    }

    // Checkin migration records of this script
    if($migrateables)
    {
      foreach($migrateables as $mig)
      {
        if($mig->checked_out || \intval($mig->checked_out) > 0)
        {
          // Check in record
          $model->checkin($mig->id);
        }
      }
    }

    // Clean the session data and redirect.
    $this->app->setUserState(_JOOM_OPTION.'.migration.script', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.params', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.noToken', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.resumed', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.data', null);    
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.results', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step3.results', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step3.success', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step4.results', null);
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.step4.success', null);

    // Redirect to the list screen.
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

    return true;
  }

  /**
   * Method to resume a previously paused or canceled migration.
   *
   * @return  boolean  True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function resume()
  {
    $this->checkToken();

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);      
    }

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start', 'error'));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Load params
    $model->setParams();

    // Get item to resume from the request.
    $cid = (array) $this->input->get('cid', [], 'int');
    $cid = \array_filter($cid);
    $id  = $cid[0];

    if($id < 1)
    {
      $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_MIGRATION_RESUME', 'error'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_MIGRATION_RESUME'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Attempt to load the migration item
    $item = $model->getItem($id);
    if(!$item || $item->script != $script)
    {
      $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_MIGRATION_RESUME', 'error'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_MIGRATION_RESUME'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Check if migration item is checked out
    $user  = Factory::getUser();
    if(isset($item->checked_out) && !($item->checked_out == 0 || $item->checked_out == $user->get('id')))
    {
      // You are not allowed to resume the migration, since it is checked out by another user
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_CHECKED_OUT_BY_ANOTHER_USER', $user->get('name')), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CHECKED_OUT_BY_ANOTHER_USER', $user->get('name')), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Set params data to user state
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.params', $item->params);

    // Set no token check to user state
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.noToken', true);

    // Set resumed to user state
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.resumed', true);

    // Redirect to the form screen (step 2).
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&task=migration.precheck&isNew=0', false));

    return true;
  }

  /**
   * Method to remove one or more item from database.
   *
   * @return  boolean True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function delete()
  {
    $this->checkToken();

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);      
    }

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Get items to remove from the request.
    $cid = (array) $this->input->get('cid', [], 'int');

    // Remove zero values resulting from input filter
    $cid = \array_filter($cid);

    if(!empty($cid))
    {
      // Get the model.
      $model = $this->getModel();

      // Load params
      $model->setParams();

      // Attempt to load the migration item
      $item = $model->getItem($cid[0]);
      if(!$item || $item->script != $script)
      {
        $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_MIGRATION_RESUME', 'error'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_MIGRATION_RESUME'), 'error', 'jerror');
        $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

        return false;
      }

      // Check if migration item is checked out
      $user  = Factory::getUser();
      if(isset($item->checked_out) && !($item->checked_out == 0 || $item->checked_out == $user->get('id')))
      {
        // You are not allowed to resume the migration, since it is checked out by another user
        $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_CHECKED_OUT_BY_ANOTHER_USER', $user->get('name')), 'error');
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CHECKED_OUT_BY_ANOTHER_USER', $user->get('name')), 'error', 'jerror');
        $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

        return false;
      }

      // Remove the items.
      if($model->delete($cid))
      {
        $this->app->enqueueMessage(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', \count($cid)));
        $this->component->addLog(Text::plural($this->text_prefix . '_N_ITEMS_DELETED', \count($cid)), 'info', 'jerror');
      }
      else
      {
        $this->app->enqueueMessage($model->getError(), 'error');
        $this->component->addLog($model->getError(), 'error', 'jerror');
      }
    }

    // Redirect to the list screen.
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

    return true;
  }

  /**
   * Method to remove migration source data (filesystem & database).
   *
   * @return  boolean True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function removesource()
  {
    $this->checkToken();

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      throw new \Exception('Requested migration script does not exist.', 1);  
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
    }

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    // Check if script allows source data removal
    if(!$model->getSourceDeletion())
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.removesource'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.removesource'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step4', false));

      return false;
    }

    $postcheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.step4.success', false);

    // Check if no errors detected in postcheck (step 4)
    if(!$postcheck)
    {
      // Post-checks not successful. Show error message.
      $msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_CHECKS_FAILED');
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP4', $msg), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP4', $msg), 'error', 'jerror');
      // Redirect to the step 4 screen
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step4', false));
    }

    // Get items to remove from the request.
    $cid = (array) $this->input->get('cid', [], 'int');

    // Remove zero values resulting from input filter
    $cid = \array_filter($cid);

    if(!empty($cid))
    {
      // Remove the source data.
      if($model->deleteSource($cid))
      {
        $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_DATA_DELETE_SUCCESSFUL'), 'message');
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_DATA_DELETE_SUCCESSFUL'), 'info', 'jerror');
      }
    }

    // Redirect to the step 4 screen.
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step4', false));

    return true;
  }

  /**
   * Step 2
   * Validate the form input data and perform the pre migration checks.
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 * @throws  \Exception
	 */
	public function precheck()
	{
    // Get script
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');

    // No token (When precheck is called on reume, no token check is needed)
    $noToken = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.noToken', false);

    // Check for request forgeries
    if(\is_null($noToken) && !$noToken)
    {
      $this->checkToken();
    }

    $model   = $this->getModel();
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);
    }

    $data    = $this->input->post->get('jform_'.$script, [], 'array');
    $context = _JOOM_OPTION.'.migration.'.$script.'.step2';
    $task    = $this->getTask();

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    if($isNew = $this->input->get('isNew', true,'bool'))
    {
      // Validate the posted data.
      $form = $model->getForm($data, false);

      // Send an object which can be modified through the plugin event
      $objData = (object) $data;
      $this->app->triggerEvent('onContentNormaliseRequestData', [$context, $objData, $form]);
      $data = (array) $objData;

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
              $this->app->enqueueMessage($errors[$i]->getMessage(), 'warning');
              $this->component->addLog($errors[$i]->getMessage(), 'error', 'jerror');
            }
            else
            {
              $this->app->enqueueMessage($errors[$i], 'warning');
              $this->component->addLog($errors[$i], 'error', 'jerror');
            }
        }

        // Save the form data in the session.
        $this->app->setUserState($context . '.data', $data);

        // Redirect back to step 1, the form.
        $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step1', false));

        return false;
      }
    }
    else
    {
      $validData = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.params', array());
    }    

    // Save the script name in the session.
    $this->app->setUserState(_JOOM_OPTION.'.migration.script', $script);

    // Save the migration parameters in the session.
    $this->app->setUserState(_JOOM_OPTION.'.migration.'.$script.'.params', $validData);

    // Perform the pre migration checks
    list($success, $res, $msg) = $model->precheck($validData, $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.resumed', false));
    if(!$success)
    {
      // Pre-checks not successful. Show error message.
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2', $msg), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2', $msg), 'error', 'jerror');
    }
    else
    {
      // Pre-checks successful. Show success message.
      $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SUCCESS_MIGRATION_STEP2'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SUCCESS_MIGRATION_STEP2'), 'info', 'jerror');

      if(!empty($msg))
      {
        // Warnings appeared. Show warning message.
        $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_WARNING_MIGRATION_STEP2', $msg), 'warning');
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_WARNING_MIGRATION_STEP2', $msg), 'error', 'jerror');
      }
    }

    // Save the results of the pre migration checks in the session.
    $this->app->setUserState($context . '.results', $res);
    $this->app->setUserState($context . '.success', $success);

    // Redirect to the screen to show the results (View of Step 2)
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step2', false));

    return;
  }

  /**
   * Step 3
   * Enter the migration view.
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 * @throws  \Exception
	 */
	public function migrate()
	{
    // Check for request forgeries
    $this->checkToken();

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);   
    }

    $precheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', false);

    // Check if no errors detected in precheck (step 2)
    if(!$precheck)
    {
      // Pre-checks not successful. Show error message.
      $msg = Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_CHECKS_FAILED');
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2', $msg), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP2', $msg), 'error', 'jerror');
      // Redirect to the step 2 screen
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step2', false));
    }

    // Redirect to the step 3 screen
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step3', false));
  }

  /**
   * Step 4
   * Perform the post migration checks.
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 * @throws  \Exception
	 */
	public function postcheck()
	{
    // Check for request forgeries
    $this->checkToken();

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);
    }

    $context = _JOOM_OPTION.'.migration.'.$script.'.step4';
    $task    = $this->getTask();

    // Perform the post migration checks
    list($success, $res, $msg) = $model->postcheck();
    if(!$success)
    {
      // Post-checks not successful. Show error message.
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP4', $msg), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_STEP4', $msg), 'error', 'jerror');
    }
    else
    {
      // Pre-checks successful. Show success message.
      $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SUCCESS_MIGRATION_STEP4'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SUCCESS_MIGRATION_STEP4'), 'info', 'jerror');

      if(!empty($msg))
      {
        // Warnings appeared. Show warning message.
        $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_WARNING_MIGRATION_STEP4', $msg), 'warning');
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_WARNING_MIGRATION_STEP4', $msg), 'warning', 'jerror');
      }
    }

    // Save the results of the post migration checks in the session.
    $this->app->setUserState($context . '.results', $res);
    $this->app->setUserState($context . '.success', $success);

    // Redirect to the screen to show the results (View of Step 4)
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step4', false));

    return;
  }

  /**
   * Perform a migration
   * Called by Ajax requests
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 */
	public function start()
	{
    // Check for request forgeries
    $this->checkToken();

    // Get request format
    $format  = strtolower($this->app->getInput()->getWord('format', 'json'));

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $response = $this->createRespond(null, false, Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.start'), 'error', 'jerror');
      $this->ajaxRespond($response, $format);

      return false;
    }

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $response = $this->createRespond(null, false, 'Requested migration script does not exist.');
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      $this->ajaxRespond($response, $format);

      return false;
    }

    // Check if no errors detected in precheck (step 2)
    $precheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', false);
    if(!$precheck)
    {
      // Pre-checks not successful. Show error message.
      $response = $this->createRespond(null, false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_CHECKS_FAILED'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_CHECKS_FAILED'), 'error', 'jerror');
      $this->ajaxRespond($response, $format);

      return false;
    }

    // Get input params for migration
    $type  = $this->app->getInput()->get('type', '', 'string');
    $id    = $this->app->getInput()->get('id', '', 'cmd');
    $json  = \json_decode(\base64_decode($this->app->getInput()->get('migrateable', '', 'string')), true);

    // Check if json was decoded properly
    if($json === null)
    {
      $response = $this->createRespond(null, false, Text::_('JSON decoding error!'));
      $this->component->addLog('Error decoding JSON: ' . json_last_error_msg(), 'error', 'jerror');
      $this->ajaxRespond($response, $format);

      return false;
    }

    // Check if a record id to be migrated is given
    if(empty($id) || $id == 0)
    {
      // No record id given. Show error message.
      $response = $this->createRespond(null, false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_RECORD_ID_MISSING'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_RECORD_ID_MISSING'), 'error', 'jerror');
      $this->ajaxRespond($response, $format);

      return false;
    }

    // Start migration
    //------------------
    
    // Attempt to load migration record from database
    $item = $model->getItem($json['id']);

    if(\is_null($item->id))
    {
      // It seems that the migration record does not yet exists in the database
      // Save migration record to database
      if(!$model->save($json))
      {
        $this->component->setError($model->getError());
        $this->component->addLog($model->getError(), 'error', 'jerror');

        return false;
      }

      // Attempt to load migration record from database
      $item = $model->getItem($model->getState('migration.id'));
    }

    // Check out migration record if not already checked out
    if(\is_null($item->checked_out) || \intval($item->checked_out) < 1)
    {
      // Check out record
      $model->checkout($item->id);
    }

    // Perform the migration
    $table = $model->migrate($type, $id);

    // Stop automatic execution if migrateable is complete
    if($table->completed)
    {
      $this->component->getMigration()->set('continue', false);

      // Check in record
      $model->checkin($table->id);
    }

    // Check for errors
    if(!empty($this->component->getError()))
    {
      // Error during migration
      $response = $this->createRespond($table, false, $this->component->getError());
    }
    else
    {
      // Migration successful
      $response = $this->createRespond($table, true);
    }

    // Send migration results
    $this->ajaxRespond($response, $format);
  }

  /**
   * Apply a migration state for a specific record manually
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 */
	public function applyState()
  {
    // Check for request forgeries
    $this->checkToken();

    // Access check.
    $acl = $this->component->getAccess();
    if(!$acl->checkACL('admin', 'com_joomgallery'))
    {
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.applyState'), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_TASK_NOT_PERMITTED', 'migration.applyState'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration', false));

      return false;
    }

    $model   = $this->getModel();
    $script  = $this->app->getUserStateFromRequest(_JOOM_OPTION.'.migration.script', 'script', '', 'cmd');
    $scripts = $model->getScripts();

    // Check if requested script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog('Requested migration script does not exist.', 'error', 'jerror');
      throw new \Exception('Requested migration script does not exist.', 1);
    }

    // Check if no errors detected in precheck (step 2)
    $precheck = $this->app->getUserState(_JOOM_OPTION.'.migration.'.$script.'.step2.success', false);
    if(!$precheck)
    {
      // Pre-checks not successful. Show error message.
      $this->setMessage(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_CHECKS_FAILED'), 'error');
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_MIGRATION_CHECKS_FAILED'), 'error', 'jerror');
      $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step2', false));

      return false;
    }

    // Get input params
    $type      = $this->app->getInput()->get('type', '', 'string');
    $new_state = $this->app->getInput()->get('state', 0, 'int');
    $src_pk    = $this->app->getInput()->get('src_pk', 0, 'int');
    $dest_pk   = $this->app->getInput()->get('dest_pk', 0, 'int');
    $error_msg = $this->app->getInput()->get('error', '', 'string');
    $cofirm    = $this->app->getInput()->get('confirmation', false, 'bool');
    $json      = \json_decode(\base64_decode($this->app->getInput()->get('migrateable', '', 'string')), true);

    if(!$cofirm || empty($src_pk) || ($new_state === 1 && empty($dest_pk)))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_APPLYSTATE_FORMCHECK'), 'warning');
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_APPLYSTATE_FORMCHECK'), 'warning', 'jerror');
    }
    else
    {
      // Attempt to load migration record from database
      $item = $model->getItem($json['id']);

      if(\is_null($item->id))
      {
        // It seems that the migration record does not yet exists in the database
        // Save migration record to database
        if(!$model->save($json))
        {
          $this->component->setError($model->getError());

          return false;
        }

        // Attempt to load migration record from database
        $item = $model->getItem($model->getState('migration.id'));
      }

      // Check out migration record if not already checked out
      if(\is_null($item->checked_out) || \intval($item->checked_out) < 1)
      {
        // Check out record
        $model->checkout($item->id);
      }

      // Mark the state of the specified record
      $model->applyState($type, $new_state, $src_pk, $dest_pk, $error_msg);

      $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_APPLYSTATE_SUCCESSFUL_'.$new_state, $type, $src_pk), 'message');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_APPLYSTATE_SUCCESSFUL_'.$new_state, $type, $src_pk), 'info', 'jerror');
    }

    // Redirect to the list screen.
    $this->setRedirect(Route::_('index.php?option=' . _JOOM_OPTION . '&view=migration&layout=step3', false));

    return true;
  }

  /**
   * Create a response object
   * {success: bool, data: mixed, continue: bool, error: string|array, debug: string|array, warning: string|array}
   * 
   * @param   mixed   $data      The data returned to the frontend
   * @param   bool    $success   True if everything was good, false otherwise
   * @param   mixed   $error     One or multiple error messages to be printed in the frontend
	 *
	 * @return  string  Response json string
	 *
   * @since   4.0.0
	 */
  protected function createRespond($data, bool $success = true, $error = null): string
  {
    $obj = new \stdClass;

    $obj->success  = $success;
    $obj->data     = $data;
    $obj->continue = true;
    $obj->error    = array();
    $obj->debug    = array();
    $obj->warning  = array();

    // Get value for continue
    if(!\is_null($this->component->getMigration()))
    {
      $obj->continue = $this->component->getMigration()->get('continue', true);
    }

    // Get debug output
    if(!empty($debug = $this->component->getDebug()))
    {
      $obj->debug = $debug;
    }

    // Get warning output
    if(!empty($warning = $this->component->getWarning()))
    {
      $obj->warning = $warning;
    }

    // Get error output
    if(!empty($error))
    {
      if(\is_array($error))
      {
        $obj->error = $error;
      }
      else
      {
        \array_push($obj->error, $error);
      }      
    }

    return \json_encode($obj, JSON_UNESCAPED_UNICODE);
  }

  /**
   * Returns an ajax response
   * 
   * @param   mixed   $results  The result to be returned
   * @param   string  $format   The format in which the result should be returned
	 *
	 * @return  void
	 *
   * @since   4.0.0
	 */
  protected function ajaxRespond($results, $format=null)
  {
    $this->app->allowCache(false);
    $this->app->setHeader('X-Robots-Tag', 'noindex, nofollow');

    if(\is_null($format))
    {
      $format = strtolower($this->app->getInput()->getWord('format', 'raw'));
    }

    // Return the results in the desired format
    switch($format)
    {
      // JSONinzed
      case 'json':
        echo new JsonResponse($results, null, false, $this->app->getInput()->get('ignoreMessages', true, 'bool'));

        break;

      // Raw format
      default:
        // Output exception
        if($results instanceof \Exception)
        {
          // Log an error
          $this->component->addLog($results->getMessage(), 'error', 'jerror');

          // Set status header code
          $this->app->setHeader('status', $results->getCode(), true);

          // Echo exception type and message
          $out = \get_class($results) . ': ' . $results->getMessage();
        }
        elseif(\is_scalar($results))
        {
          // Output string/ null
          $out = (string) $results;
        }
        else
        {
          // Output array/ object
          $out = \implode((array) $results);
        }

        echo $out;

        break;
    }
  }
}
