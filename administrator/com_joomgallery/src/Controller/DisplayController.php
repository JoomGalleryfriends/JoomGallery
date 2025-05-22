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
use \Joomla\Filesystem\Path;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\MVC\Controller\BaseController;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * Joomgallery master display controller.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class DisplayController extends BaseController
{
  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  protected $component;

  /**
   * The context for storing internal data, e.g. record.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $context;

	/**
	 * The default view.
	 *
	 * @var    string
	 * @since  4.0.0
	 */
	protected $default_view = 'images';

  /**
   * Constructor.
   *
   * @param   array                $config   An optional associative array of configuration settings.
   *                                         Recognized key values include 'name', 'default_task', 'model_path', and
   *                                         'view_path' (this list is not meant to be comprehensive).
   * @param   MVCFactoryInterface  $factory  The factory.
   * @param   CMSApplication       $app      The Application for the dispatcher
   * @param   Input                $input    Input
   *
   * @since   4.0.0
   */
  public function __construct($config = array(), ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    // Guess the context based on the view input variable
    if(empty($this->context))
    {
      // Get view variable
      $view  = Factory::getApplication()->input->get('view', $this->default_view);

      // Check if view exists
      if(!\in_array($view, $this->getAvailableViews()))
      {
        // The guessed view does not exist. Use the default instead.
        $view = $this->default_view;

        // Set the view
        $this->input->set('view', $this->default_view);
      }

      // Conduct the context
      $this->context = _JOOM_OPTION.'.'.$view.'.display';
    }

    $this->component = $this->app->bootComponent(_JOOM_OPTION);
  }

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe URL parameters and their variable types, for valid values see {@link InputFilter::clean()}.
	 *
	 * @return  BaseController|boolean  This object to support chaining.
	 *
	 * @since   4.0.0
	 */
	public function display($cachable = false, $urlparams = array())
	{
    // Before execution of the task
    if(!empty($task))
    {
      $this->component->msgUserStateKey = 'com_joomgallery.'.$task.'.messages';
    }
    
    if(!$this->component->isRawTask($this->context))
    {
      // Get messages from session
      $this->component->msgFromSession();
    }

		$res = parent::display();

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
	 * Method to get a list of available views based on the available folders in
   * Joomgallery\Component\Joomgallery\<App>\View.
   * 
	 * @return  array  List of available views.
	 *
	 * @since   4.0.0
	 */
  protected function getAvailableViews(): array
  {
    $appName = Factory::getApplication()->getName();

    // Get folder path of Joomgallery\Component\Joomgallery\<App>\View
    if($appName == 'site')
    {
      $path = Path::clean(JPATH_ROOT . '/components/com_joomgallery/src/View');
    }
    else
    {
      $path = Path::clean(JPATH_ROOT . '/' . \strtolower($appName) . '/components/com_joomgallery/src/View');
    }

    // Get directories
    $dirs = glob($path . '/*' , GLOB_ONLYDIR);

    // Convert directory paths to view names
    foreach($dirs as $key => $dir)
    {
      $dirs[$key] = \trim(\strtolower(\basename($dir)));
    }

    return $dirs;
  }
}
