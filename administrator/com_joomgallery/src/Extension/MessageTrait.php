<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Extension;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Log\Log;
use \Joomla\CMS\Language\Text;

/**
* Trait to implement messaging tools
*
* @since  4.0.0
*/
trait MessageTrait
{
  /**
   * Set to true if an error occurred
   *
   * @var bool
  */
  public $error = false;

  /**
   * Set to true if messages should be withholded
   *
   * @var bool
  */
  public $msgWithhold = false;

  /**
   * List of raw tasks without graphical output
   * and therefore without message possibility
   *
   * @var array
  */
  public $rawTasks = array('image.ajaxsave', 'tags.searchajax', 'migration.start');

  /**
	 * Session storage path
	 *
	 * @var string
	 *
	 * @since  4.0.0
	*/
  public $msgUserStateKey = 'com_joomgallery.messages';

  /**
	 * Debug information storage
	 *
	 * @var array
	 *
	 * @since  4.0.0
	*/
	protected $debug = array();

  /**
   * Warnings and messages storage
   *
   * @var array
   * 
   * @since  4.0.0
  */
  protected $warnings = array();

  /**
   * Errors storage
   *
   * @var array
   * 
   * @since  4.0.0
  */
  protected $errors = array();

  /**
   * State if logger is created
   *
   * @var bool
   * 
   * @since  4.0.0
  */
  protected $log = false;

  /**
   * Name of the logger to be used
   *
   * @var string
   * 
   * @since  4.0.0
  */
  protected $logName = null;

  /**
   * Adds the storages to the session
   * 
   * @return  void
   *
   * @since   4.0.0
  */
  public function msgToSession()
  {
    $app = Factory::getApplication();

    $app->setUserState($this->msgUserStateKey.'.debug', $this->debug);
    $app->setUserState($this->msgUserStateKey.'.warnings', $this->warnings);
    $app->setUserState($this->msgUserStateKey.'.errors', $this->errors);
  }

  /**
   * Loads the storages from the session
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function msgFromSession()
  {
    $app = Factory::getApplication();

    $this->debug    = $app->getUserState($this->msgUserStateKey.'.debug', array());
    $this->warnings = $app->getUserState($this->msgUserStateKey.'.warnings', array());
    $this->errors   = $app->getUserState($this->msgUserStateKey.'.errors', array());
  }

  /**
   * Add a JoomGallery logger to the JLog class
   * 
   * @param   string   Name of the specific logger
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function addLogger(?string $name = null)
  {
    if(!$this->log)
    {
      if(\is_null($name))
      {
        Log::addLogger(['text_file' =>  'com_joomgallery.log.php'], Log::ALL, ['com_joomgallery']);
      }
      else
      {
        Log::addLogger(['text_file' =>  'com_joomgallery.'.$name.'.log.php'], Log::ALL, ['com_joomgallery.'.$name]);
      }
    }
    
    $this->log = true;
  }

  /**
   * Set a default logger to be used from now on
   * 
   * @param   string   $name   Name of the logger. Empty to use the default JoomGallery logger
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setLogger(?string $name = null)
  {
    $this->addLogger($name);
    $this->logName = $name;
  }

  /**
   * Log a message
   * 
   * @param   string   $txt       The message for a new log entry.
   * @param   mixed    $priority  Message priority.
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function addLog(string $txt, $priority = 8, ?string $name = null)
  {
    $this->addLogger($name);

    if(\is_string($priority))
    {
      $priority = \strtoupper($priority);
      $constant = "Joomla\CMS\Log\Log::$priority";
      $priority = \constant($constant);
    }
    
    if(\is_null($name) && \is_null($this->logName))
    {
      Log::add($txt, $priority, 'com_joomgallery');
    }
    else
    {
      if(\is_null($name))
      {
        $name = $this->logName;
      }

      Log::add($txt, $priority, 'com_joomgallery.'.$name);
    }
  }

  /**
   * Log a message
   * 
   * @param   string   $txt       The message for a new log entry.
   * @param   mixed    $priority  Message priority.
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setLog(string $txt, $priority = 8, ?string $name = null)
  {
    return $this->addLog($txt, $priority, $name);
  }

  /**
   * Add text to the debug information storage
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   * @param   bool     $log         True to add error message to logfile (default: false)
   * @param   string   $name        Name of the logger to be used (default: null)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function addDebug($txt, $new_line=true, $margin_top=false, $log=false, $name=null)
  {
    $this->setMsg($txt, 'debug', $new_line, $margin_top);

    if($log)
    {
      $this->addLogger($name);
      $this->addLog($txt, Log::DEBUG, $name);
    }
  }

  /**
   * Add text to the debug information storage
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   * @param   bool     $log         True to add error message to logfile (default: false)
   * @param   string   $name        Name of the logger to be used (default: null)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setDebug($txt, $new_line=true, $margin_top=false, $log=false, $name=null)
  {
    return $this->addDebug($txt, $new_line, $margin_top, $log, $name);
  }

  /**
   * Add text to the warnings storage
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   * @param   bool     $log         True to add error message to logfile (default: false)
   * @param   string   $name        Name of the logger to be used (default: null)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function addWarning($txt, $new_line=true, $margin_top=false, $log=false, $name=null)
  {
    $this->setMsg($txt, 'warning', $new_line, $margin_top);

    if($log)
    {
      $this->addLogger($name);
      $this->addLog($txt, Log::WARNING, $name);
    }
  }

  /**
   * Add text to the warnings storage
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   * @param   bool     $log         True to add error message to logfile (default: false)
   * @param   string   $name        Name of the logger to be used (default: null)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setWarning($txt, $new_line=true, $margin_top=false, $log=false, $name=null)
  {
    return $this->addWarning($txt, $new_line, $margin_top, $log, $name);
  }

  /**
   * Set error and add it to the error storage
   *
   * @param   string   $txt         Text to add to the error storage
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   * @param   bool     $log         True to add error message to logfile (default: true)
   * @param   string   $name        Name of the logger to be used (default: null)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function addError($txt, $new_line=true, $margin_top=false, $log=true, $name=null)
  {
    $this->setMsg($txt, 'error', $new_line, $margin_top);
    $this->error = true;

    if($log)
    {
      $this->addLogger($name);
      $this->addLog($txt, Log::ERROR, $name);
    }
  }

  /**
   * Set error and add it to the error storage
   *
   * @param   string   $txt         Text to add to the error storage
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   * @param   bool     $log         True to add error message to logfile (default: true)
   * @param   string   $name        Name of the logger to be used (default: null)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setError($txt, $new_line=true, $margin_top=false, $log=true, $name=null)
  {
    return $this->addError($txt, $new_line, $margin_top, $log, $name);
  }

  /**
	 * Method to get the debugoutput
   * 
   * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Debugoutput
	 *
	 * @since  4.0.0
	*/
	public function getDebug($implode=false)
  {
    return $this->getMsg('debug', $implode);
  }

  /**
	 * Method to get the warningoutput
	 *
	 * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Warningoutput
	 *
	 * @since  4.0.0
	*/
	public function getWarning($implode=false)
  {
    return $this->getMsg('warning', $implode);
  }

  /**
	 * Method to get the erroroutput
	 *
	 * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Erroroutput
	 *
	 * @since  4.0.0
	*/
	public function getError($implode=false)
  {
    return $this->getMsg('error', $implode);
  }

  /**
   * Add the debug to the message queue
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printDebug()
  {
    return $this->printMsg('debug', 'warning', true);
  }

  /**
   * Add the warning to the message queue
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printWarning()
  {
    return $this->printMsg('warning', 'notice', true);
  }

  /**
   * Add the error to the message queue
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printError()
  {
    return $this->printMsg('error', 'error', true);
  }

  /**
   * Clear the debug storage
   * 
   * @param   bool  $session   True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearDebug($session=true)
  {
    return $this->clearMsgStorage('debug', $session);
  }

  /**
   * Clear the warning storage
   * 
   * @param   bool  $session   True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearWarning($session=true)
  {
    return $this->clearMsgStorage('warning', $session);
  }

  /**
   * Clear the error storage
   * 
   * @param   bool  $session   True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearError($session=true)
  {
    return $this->clearMsgStorage('error', $session);
  }

  /**
   * Add text to a storage
   *
   * @param   string   $txt         Text to add to the debugoutput
   * @param   string   $storage     Select storage to add text
   * @param   bool     $new_line    True to add text to a new line (default: true)
   * @param   bool     $margin_top  True to add an empty line in front (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function setMsg($txt, $storage, $new_line=true, $margin_top=false)
  {
    if(empty($txt))
    {
      return;
    }

    $storage = &$this->selectMsgStorage($storage);

    if(!$new_line && !empty($storage))
    {
      $last_line = \array_pop($storage);
      $txt = $last_line.$txt;
    }

    if($margin_top && $new_line && !empty($storage))
    {
      $txt = '<br />'.$txt;
    }

    \array_push($storage, $txt);
  }

  /**
	 * Get from storage
	 *
	 * @param   bool   $implode   True, if youi want to implode the array (optional)
	 *
	 * @return  string|array  Debugoutput
	 *
	 * @since  4.0.0
	*/
	public function getMsg($storage, $implode=false)
  {
    $storage = &$this->selectMsgStorage($storage);

    if($implode)
    {
      return \implode('<br />', $storage);
    }
    else
    {
      return $storage;
    }
  }

  /**
   * Enqueue messages from storage to be printed
   *
   * @param   string   $storage    Storage to be printed
   * @param   string   $type       Type of the message (optional)
   * @param   bool     $title      True if a title should be printed (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function printMsg($storage, $type='warning', $title=true)
  {
    // Create output title
    $storage_title = 'COM_JOOMGALLERY_';
    switch($storage)
    {
      case 'debug':
        $storage_title .= 'DEBUG_';
        break;

      case 'error':
      case 'errors':
        $storage_title .= 'ERROR_';
        break;
      
      default:
        break;
    }
    $storage_title .= 'INFORMATION';

    // Collect storage info
    $storage = &$this->selectMsgStorage($storage);

    // Check if there is anything in the storage to be printed
    if(empty($storage))
    {
      return;
    }

    // Assemble the output
    $output = '';
    if($title)
    {
      $output .= '<strong>'.Text::_($storage_title).':</strong><br />';
      $output .= '---------------------------------<br />';
    }
    $output .= \implode('<br />', $storage);

    // Use warning if type not existent
    $existing_types = array('message', 'success', 'notice', 'note', 'warning', 'error');
    if(!\in_array($type, $existing_types))
    {
      $type = 'warning';
    }

    // Add output to message queue
    Factory::getApplication()->enqueueMessage($output, $type);

    // Reset storage array
    $storage = array();
  }

  /**
   * Clears a message storage
   *
   * @param   string   $storage    Storage to be cleared
   * @param   bool     $session    True if the session storage should be cleared too (optional)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function clearMsgStorage($storage, $session=true)
  {
    $session_path = $this->selectMsgStoragePath($storage);
    $storage      = &$this->selectMsgStorage($storage);

    $storage = array();

    if($session)
    {
      Factory::getApplication()->setUserState($session_path, array());
    }
  }

  /**
	 * Select the storage
	 *
	 * @param   string   $selection  The storage name to select
	 *
	 * @return  array    The selected storage array
	 *
	 * @since   4.0.0 
   * @throws  Exception
	*/
  protected function &selectMsgStorage($selection)
  {
    switch($selection)
    {
      case 'debug':
        return $this->debug;
        break;

      case 'warnings':
      case 'warning':
        return $this->warnings;
        break;

      case 'error':
      case 'errors':
        return $this->errors;
        break;

      default:
        throw new \Exception("Selected storage does not exist.");
        return false;
    }
  }

  /**
	 * Select the session path of the storage
	 *
	 * @param   string   $selection  The storage name to select
	 *
	 * @return  string   The selected session path
	 *
	 * @since   4.0.0 
   * @throws  \Exception
	*/
  protected function selectMsgStoragePath($selection)
  {
    switch($selection)
    {
      case 'debug':
        return $this->msgUserStateKey.'.debug';
        break;

      case 'warnings':
      case 'warning':
        return $this->msgUserStateKey.'.warnings';
        break;

      case 'error':
      case 'errors':
        return $this->msgUserStateKey.'.errors';
        break;

      default:
        throw new \Exception("Selected storage does not exist.");
        return false;
    }
  }

  /**
	 * Checks if the current task is a raw tasks
   * --> without message possibility
	 *
	 * @param   string   $context  controller.task
	 *
	 * @return  bool  True on success, false otherwise
	 *
	 * @since   4.0.0 
	*/
  public function isRawTask($context)
  {
    $parts = \explode('.', $context);

    // Remove the option if present
    if(\strpos($parts[0], 'com_') !== false)
    {
      unset($parts[0]);
    }

    // Join the remaining parts
    $context = \strtolower(\implode('.', $parts));

    if(\in_array($context, $this->rawTasks))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}
