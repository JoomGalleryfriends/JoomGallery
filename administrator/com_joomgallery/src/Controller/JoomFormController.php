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

use \Joomla\Input\Input;
use \Joomla\CMS\User\CurrentUserInterface;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomla\CMS\MVC\Controller\FormController as BaseFormController;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * JoomGallery Base of Joomla Form Controller
 * 
 * Controller (controllers are where you put all the actual code) Provides basic
 * functionality, such as rendering views (aka displaying templates).
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class JoomFormController extends BaseFormController
{
  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  protected $component;

  /**
   * JoomGallery access service
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
   */
  protected $acl = null;

  /**
   * The context for storing internal data, e.g. record.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $context;

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
  public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null, FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $app, $input, $formFactory);

    $this->component = $this->app->bootComponent(_JOOM_OPTION);
  }

  /**
	 * Method to get the access service class.
	 *
	 * @return  AccessInterface   Object on success, false on failure.
   * @since   4.0.0
	 */
	public function getAcl(): AccessInterface
	{
    // Create access service
    if(\is_null($this->acl))
    {
      $this->component->createAccess();
      $this->acl = $this->component->getAccess();
    }

		return $this->acl;
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
      if(!$this->component->msgWithhold && isset($res->component->error) && $res->component->error)
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
   * Method to get a model object, loading it if required.
   *
   * @param   string  $name    The model name. Optional.
   * @param   string  $prefix  The class prefix. Optional.
   * @param   array   $config  Configuration array for model. Optional.
   *
   * @return  BaseDatabaseModel  The model.
   *
   * @since   4.0.0
   */
  public function getModel($name = '', $prefix = '', $config = ['ignore_request' => true])
  {
    if(empty($name))
    {
      $parts = \explode('.', $this->context);
      $key   = 0;

      if(\strpos($parts[0], 'com_') !== false)
      {
        $key = 1;
      }

      $name = $parts[$key];
    }

    return parent::getModel($name, $prefix, $config);
  }

  /**
   * Method to check if you can add a new record.   *
   * Extended classes can override this if necessary.
   *
   * @param   array  $data  An array of input data.
   *
   * @return  boolean
   *
   * @since   4.0.0
   */
  protected function allowAdd($data = [])
  {
    $parts = \explode('.', $this->context);
    $key   = 0;

    if(\strpos($parts[0], 'com_') !== false)
    {
      $key = 1;
    }

    if(!isset($data['id']))
    {
      $data['id'] = null;
    }

    switch($parts[$key])
    {
      case 'category':
        if($this->task == 'add')
        {
          // We try to open an empty category edit view, always allow this
          return true;
        }

        $parent_id = $data['parent_id'] ?: 1;
        $cat_id    = $data['id'] ?: 0;
        return $this->getAcl()->checkACL('add','category', $cat_id, $parent_id, true);
        break;

      case 'image':
        if($this->task == 'add' || $this->task == 'multipleadd')
        {
          // We try to open an empty image edit view, always allow this
          return true;
        }

        $catid = $data['catid'] ?: 1;
        $imgid = $data['id'] ?: 0;
        return $this->getAcl()->checkACL('add','image', $imgid, $catid, true);
        break;
      
      default:
        $id = $data['id'] ?: 0;
        return $this->getAcl()->checkACL('add', $this->context, $id);
        break;
    }
  }

  /**
   * Method to check if you can edit an existing record.   *
   * Extended classes can override this if necessary.
   *
   * @param   array   $data  An array of input data.
   * @param   string  $key   The name of the key for the primary key; default is id.
   *
   * @return  boolean
   *
   * @since   4.0.0
   */
  protected function allowEdit($data = [], $key = 'id')
  {
    $id         = $data['id'];
    $use_parent = false;
    $parent_id  = 0;
    $assetname  = $this->context;

    foreach($this->getAcl()->get('parent_dependent_types') as $type)
    {
      if(\strpos($this->context, $type) !== false && $data['id'] > 0)
      {
        $parent_id  = isset($data['catid']) ? $data['catid'] : JoomHelper::getParent($type, $data['id']);
        $use_parent = true;
        $assetname  = $type;
      }
    }
    

    return $this->getAcl()->checkACL('edit', $assetname, $id, $parent_id, $use_parent);
  }

  /**
   * Method to load and return a model object.
   *
   * @param   string  $name    The name of the model.
   * @param   string  $prefix  Optional model prefix.
   * @param   array   $config  Configuration array for the model. Optional.
   *
   * @return  BaseDatabaseModel|boolean   Model object on success; otherwise false on failure.
   *
   * @since   4.0.0
   */
  protected function createModel($name, $prefix = '', $config = [])
  {
    $model = parent::createModel($name, $prefix, $config);

    if($model instanceof CurrentUserInterface)
    {
      $model->setCurrentUser($this->component->getMVCFactory()->getIdentity());
    }

    return $model;
  }

  /**
   * Method to load and return a view object.
   *
   * @param   string  $name    The name of the view.
   * @param   string  $prefix  Optional prefix for the view class name.
   * @param   string  $type    The type of view.
   * @param   array   $config  Configuration array for the view. Optional.
   *
   * @return  ViewInterface|null  View object on success; null or error result on failure.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function createView($name, $prefix = '', $type = '', $config = [])
  {
    $view = parent::createView($name, $prefix, $type, $config);

    if($view instanceof CurrentUserInterface)
    {
      $view->setCurrentUser($this->component->getMVCFactory()->getIdentity());
    }

    return $view;
  }
}
