<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Refresher;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Refresher\RefresherInterface;
use \Joomla\CMS\Document\HtmlDocument;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Layout\FileLayout;

/**
 * JoomGallery Refresher Helper
 *
 * Provides handling with the filesystem where the image files are stored
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Refresher implements RefresherInterface
{
  use ServiceTrait;

  /**
   * Unix timestamp of start time
   *
   * @var int
   */
  protected $_starttime;

  /**
   * Maximum time for execution in seconds
   *
   * @var int
   */
  protected $_maxtime;

  /**
   * Controller which will be included
   * into the URL for redirection by default
   *
   * @var string
   */
  protected $_controller;

  /**
   * Task which will be included into
   * the URL for redirection by default
   *
   * @var string
   */
  protected $_task;

  /**
   * Determines whether a message should
   * be displayed for each redirect by default
   *
   * @var boolean
   */
  protected $_msg;

  /**
   * Holds the number of remaining things to do
   *
   * @var int
   */
  protected $_remaining;

  /**
   * Holds the total number of things to do
   *
   * @var int
   */
  protected $_total;

  /**
   * Determines whether the progress bar should be displayed
   *
   * @var boolean
   */
  protected $_showprogress;

  /**
   * Holds the name or a short description of the current task
   *
   * @var string
   */
  protected $_name;

  /**
   * Constructor
   *
   * @param   array  $params   An array with optional parameters
   *
   * @return  void
   *
   * @since   1.5.5
   */
  public function __construct($params = [])
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // set controller
    if(isset($params['controller']))
    {
      $this->_controller = $params['controller'];
    }
    else
    {
      $this->_controller = $this->app->input->get('controller', '', 'cmd');
    }

    // set task
    if(isset($params['task']))
    {
      $this->_task = $params['task'];
    }
    else
    {
      $this->_task = $this->app->input->get('task', '', 'cmd');
    }

    // set message
    if(isset($params['msg']))
    {
      $this->_msg = $params['msg'];
    }
    else
    {
      $this->_msg = false;
    }

    // set progress information
    if(isset($params['remaining']))
    {
      $this->_remaining = $params['remaining'];

      if(isset($params['start']) && $params['start'])
      {
        $this->_total = $params['remaining'];
        $this->app->setUserState('joom.refresher.total', $this->_total);
      }
      else
      {
        $this->_total = $this->app->getUserState('joom.refresher.total');
      }

      $this->_showprogress = $this->_total ? true : false;
    }

    // set task name
    if(isset($params['name']) && $params['name'])
    {
      $this->_name = $params['name'];
    }

    $this->init();
  }

  /**
   * Initializes the refresher by storing current time
   *
   * @return  void
   *
   * @since   1.5.5
   */
  public function init()
  {
    // Check the maximum execution time of the script
    $max_execution_time = @\ini_get('max_execution_time');

    // Set secure setting of the real execution time
    // Maximum time for the script will be set to 20 seconds
    // (max_execution_time = 0 means no limit)
    if($max_execution_time < 25 && $max_execution_time != 0)
    {
      $this->_maxtime = (int) $max_execution_time * 0.8;
    }
    else
    {
      $this->_maxtime = 20;
    }

    $this->_starttime = time();
  }

  /**
   * Resets the progressbar or the name of the current task
   *
   * @param   int     $remaining  Number of remaining steps
   * @param   bool    $start      Determines whether $remaining holds the total number of steps
   * @param   string  $name       Name of the task to display
   *
   * @return  void
   *
   * @since   1.5.6
   */
  public function reset($remaining, $start, $name)
  {
    if(!\is_null($remaining))
    {
      $this->_remaining = $remaining;

      if(!\is_null($start) && $start)
      {
        $this->_total = $remaining;
        $this->app->setUserState('joom.refresher.total', $this->_total);
      }
      else
      {
        $this->_total = $this->app->getUserState('joom.refresher.total');
      }

      $this->_showprogress = $this->_total ? true : false;
    }
    else
    {
      $this->_showprogress = false;
    }

    if(!\is_null($name) && $name)
    {
      $this->_name = $name;
    }
  }

  /**
   * Checks the remaining time
   *
   * @return  bool True: Time remains, false: No more time left
   *
   * @since   1.5.5
   */
  public function check(): bool
  {
    $timeleft = -(time() - $this->_starttime - $this->_maxtime);

    if($timeleft > 3)
    {
      return true;
    }

    return false;
  }

  /**
   * Make a redirect
   *
   * @param   int     $remaining  Number of remaining steps
   * @param   string  $task       The task which will be called after the redirect
   * @param   string  $msg        An optional message which will be enqueued (this is currently disabled)
   * @param   string  $type       Type of the message (one of 'message', 'notice', 'error')
   * @param   string  $controller The controller which will be called after the redirect
   *
   * @return  void
   *
   * @since   1.5.5
   */
  public function refresh($remaining, $task=false, $msg=false, $type=false, $controller=false)
  {
    if($remaining)
    {
      $this->_remaining = $remaining;
    }

    if($this->_msg && \is_null($task))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_REFRESH_SITE'));
    }

    if(!$task)
    {
      $task = $this->_task;
    }

    if(!$controller)
    {
      $controller = $this->_controller;
    }

    if($msg)
    {
      $this->app->enqueueMessage($msg, $type);
    }

    if(!$this->_msg || $msg)
    {
      // Persist messages if they exist
      $messages = $this->app->getMessageQueue();

      if(\count($messages))
      {
        $session = Factory::getSession();
        $session->set('application.queue', $messages);
      }
    }

    // Create html document
    $doc = new HtmlDocument();

    // Get template specific data
    switch(Factory::getApplication()->getClientId())
    {
      case 0:
          $tmpName       = 'cassiopeia';
          $tmpDir        = JPATH_BASE . DIRECTORY_SEPARATOR . 'templates';
          $tmpIndexFile  = 'component.php';
          $tmpAssetFile  = 'templates/cassiopeia/joomla.asset.json';
          $compAssetFile = 'media/com_joomgallery/joomla.asset.json';
          break;
      default:
          $tmpName  = 'atum';
          $tmpDir        = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'templates';
          $tmpIndexFile  = 'component.php';
          $tmpAssetFile  = 'administrator/templates/atum/joomla.asset.json';
          $compAssetFile = 'media/com_joomgallery/joomla.asset.json';
          break;
    }

    // Fill head of document
    $head = ['title' => Text::_('COM_JOOMGALLERY_WORK_IN_PROGRESS'),
      'description' => '',
      'link' => '',
      'assetManager' => ['registryFiles' => [$tmpAssetFile, $compAssetFile]]];
    $doc->setHeadData($head);
    $doc->setHtml5(true);

    // Add styles
    $wa = $doc->getWebAssetManager();
    $wa->useStyle('template.'.$tmpName.'.ltr')
       ->useStyle('template.user')
       ->useStyle('com_joomgallery.refresher');

    // Create html output of the component section
    $data    = ['name' => $this->_name, 'maxtime' => $this->_maxtime, 'showprogress' => $this->_showprogress, 'total' => $this->_total, 'remaining' => $this->_remaining];
    $layout  = new FileLayout('refresher', null, ['component' => 'com_joomgallery', 'client' => 1]);
    $buffer  = $layout->render($data);
    $buffer .= '<script type="text/javascript">document.location.href="index.php?option='._JOOM_OPTION.'&controller='.$controller.'&task='.$task.'"</script>';
    $doc->setBuffer($buffer, 'component');

    // Render the html document
    $tmpl = ['directory' => $tmpDir,
      'template' => $tmpName,
      'file' => $tmpIndexFile,
    ];
    echo $doc->render(false, $tmpl);

    // Return the output
    $this->app->close();
  }
}
