<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @subpackage plg_privacyjoomgalleryimages                                           **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
namespace Joomgallery\Plugin\System\Joomgallery\Extension;

\defined('_JEXEC') or die;

use Joomla\Event\Event;
use Joomla\CMS\Form\Form;
use Joomla\Event\Priority;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\CMS\Application\CMSApplication;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\CMS\Event\Model\AfterCleanCacheEvent;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * System plugin integrating JoomGallery into the CMS core
 *
 * @package JoomGallery
 * @since   4.0.0
 */
final class Joomgallery extends CMSPlugin implements SubscriberInterface, DispatcherAwareInterface
{
  use DispatcherAwareTrait;

  /**
   * Global database object
   *
   * @var    \JDatabaseDriver
   *
   * @since  4.0.0
   */
  protected $db = null;

  /**
   * Global application object
   *
   * @var     CMSApplication
   *
   * @since   4.0.0
   */
  protected $app = null;

  /**
   * True if JoomGallery component is installed
   *
   * @var     int|bool
   *
   * @since   4.0.0
   */
  protected static $jg_exists = null;

  /**
   * Load the language file on instantiation.
   *
   * @var    boolean
   *
   * @since  4.0.0
   */
  protected $autoloadLanguage = true;

  /**
   * List of allowed form context
   *
   * @var    array
   *
   * @since  4.0.0
   */
  protected $allowedFormContext = array('com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile');

  /**
   * Constructor
   *
   * @param   DispatcherInterface  $dispatcher  The event dispatcher
   * @param   array                $config      An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.0.0
   */
  function __construct($dispatcher, $config)
  {
    parent::__construct($dispatcher, $config);

    $this->isJGExists();
  }

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return array
   *
   * @since   4.0.0
   */
  public static function getSubscribedEvents(): array
  {
    if(self::$jg_exists)
    {
      return [
        'onContentCleanCache'  => ['onContentCleanCache', Priority::NORMAL],
        'onContentPrepareForm' => ['onContentPrepareForm', Priority::NORMAL],
        'onContentPrepareData' => ['onContentPrepareData', Priority::NORMAL],
        'onUserAfterSave'      => ['onUserAfterSave', Priority::NORMAL],
        'onUserAfterDelete'    => ['onUserAfterDelete', Priority::NORMAL]
      ];
    }
    else
    {
      return array();
    }
  }

  /**
   * Event triggered before a migrated record gets saved into the db.
   * Check if owner of JG record is valid and exists.
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue the save process, false to stop it
   *
   * @since   4.0.0
   */
  public function onContentCleanCache(Event $event)
  {
    if(\version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      $arguments    = $event->getArguments();
      $defaultgroup = $arguments['defaultgroup'];
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $defaultgroup = $event->getDefaultGroup();
    }

    if(\strpos($defaultgroup, 'com_joomgallery') !== 0 && \strpos($defaultgroup, 'com_users') !== 0 && \strpos($defaultgroup, 'com_menus') !== 0)
    {
      // Do nothing if we are not handling joomgallery content
      $this->setResult($event, true, false);

      return;
    }

    // Guess cache type
    if(!$type = $this->guessType($defaultgroup))
    {
      // Type not recognized. Do nothing.
      $this->setResult($event, true, false);

      return;
    }

    switch($type)
    {
      case 'config':
        // If a configuration set is modified, delete all cache
        JoomHelper::getComponent()->createConfig();
        JoomHelper::getComponent()->getConfig()->emptyCache();
        break;

      case 'user':
        // If a user is modified, delete only usergroup cache
        $userId = $this->guessType($defaultgroup, true);
        JoomHelper::getComponent()->createConfig();
        JoomHelper::getComponent()->getConfig()->emptyCache('user.'.$userId);
        break;

      case 'category':
        // If a category is modified, delete only category cache
        JoomHelper::getComponent()->createConfig();
        JoomHelper::getComponent()->getConfig()->emptyCache('category');
        break;

      case 'image':
        // If an image is modified, delete only image cache
        JoomHelper::getComponent()->createConfig();
        JoomHelper::getComponent()->getConfig()->emptyCache('image');
        break;

      case 'menu':
        // If an image is modified, delete only image cache
        $itemid = $this->guessType($defaultgroup, true);
        JoomHelper::getComponent()->createConfig();
        JoomHelper::getComponent()->getConfig()->emptyCache('menu.'.$itemid);
        break;

      default:
        // Do nothing
        break;
    }

    // Return the result
		$this->setResult($event, true, false);
  }

  /**
   * Event triggered when loading a form.
   * Used to modify the form before populating it
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue with the form, false to stop it
   *
   * @since   4.0.0
   */
  public function onContentPrepareForm(Event $event)
  {
    if(\version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$form, $data] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $form = $event->getForm();
    }

    if(!($form instanceof Form))
    {
      $this->setError($event, 'JERROR_NOT_A_FORM');
      $this->setResult($event, true);

      return;
    }

    $context = $form->getName();
    if(!\in_array($context, $this->allowedFormContext) || !$this->getApplication()->isClient('administrator'))
    {
      // Modify only forms in the backend that have the correct context
      $this->setResult($event, true);

      return;
    }

    // Load extra input fields to the form
    Form::addFormPath(JPATH_PLUGINS . '/system/joomgallery/forms');
    $form->loadFile('form', false);

    $this->setResult($event, true);

    return;
  }

  /**
   * Event triggered when populating a form.
   * Used to populating a form with extra data.
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue with the form, false to stop it
   *
   * @since   4.0.0
   */
  public function onContentPrepareData(Event $event)
  {
    if(\version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$context, $data] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $context = $event->getContext();
      $data    = $event->getData();
    }

    if(!\in_array($context, $this->allowedFormContext) || !$this->getApplication()->isClient('administrator'))
    {
      // Modify only forms in the backend that have the correct context
      $this->setResult($event, true);

      return;
    }

    if(\is_object($data))
    {
      $userId = isset($data->id) ? $data->id : 0;

      if(!isset($data->joomgallery) and $userId > 0)
      {
        try
        {
          $fields = $this->getFields($userId);
        }
        catch(\Exception $e)
        {
          $this->setError($event, $e->getMessage());
          $this->setResult($event, false);

          return;
        }

        $data->joomgallery = array();
        foreach($fields as $field)
        {
          $fieldName = str_replace('joomgallery.', '', $field[0]);
          $data->joomgallery[$fieldName] = json_decode($field[1], true);

          if($data->joomgallery[$fieldName] === null)
          {
            $data->joomgallery[$fieldName] = $field[1];
          }
        }
      }
    }
  }

  /**
   * Event triggered after saving a user form.
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue with the storing process, false to stop it
   *
   * @since   4.0.0
   */
  public function onUserAfterSave(Event $event)
  {
    if(\version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$data, $isNew, $result, $error] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $data    = $event->getUser();
      $result  = $event->getSavingResult();
    }

    // Save the extra input into the database
    $userId = isset($data['id']) ? (int) $data['id'] : 0;

    if($userId && $result && isset($data['joomgallery']) && (count($data['joomgallery'])))
    {
      $options = [
        'defaultgroup' => 'com_users.user.'.$userId,
        'cachebase'    => $this->app->get('cache_path', JPATH_CACHE),
        'result'       => true,
      ];

      if(\version_compare(JVERSION, '5.0.0', '<'))
      {
        // Joomla 4
        $cacheEvent = new Event('onContentCleanCache', $options);

        // Perform the onContentCleanCache event
        $this->onContentCleanCache($cacheEvent);
        if($cacheEvent->getArgument('error', false))
        {
          $this->setError($event, $cacheEvent->getArgument('error', ''));
          $this->setResult($event, true);

          return;
        }
      }
      else
      {
        // Joomla 5
        $cacheEvent = new AfterCleanCacheEvent('onContentCleanCache', $options);
        $this->getDispatcher()->dispatch('onContentCleanCache', $cacheEvent);
      }

      // Update user fields
      try
      {
        if(!$isNew)
        {
          $this->deleteFields($userId);
        }

        $ordering = 0;
        foreach($data['joomgallery'] as $fName => $fValue)
        {
          $this->insertField($userId, $fName, $fValue, $ordering);
          $ordering++;
        }
      }
      catch (\Exception $e)
      {
        $this->setError($event, $e->getMessage());
        $this->setResult($event, true);

        return;
      }
    }
  }

  /**
   * Event triggered after deleting a user.
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue with the form, false to stop it
   *
   * @since   4.0.0
   */
  public function onUserAfterDelete(Event $event)
  {
    if(\version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$data, $result, $error] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $data    = $event->getUser();
      $result  = $event->getDeletingResult();
    }

    if(!$result)
    {
      $this->setResult($event, true);

      return;
    }

    $userId = isset($data['id']) ? (int) $data['id'] : 0;

    if($userId)
    {
      try
      {
        $this->deleteFields($userId);
      }
      catch(\Exception $e)
      {
        $this->setError($event, $e->getMessage());
        $this->setResult($event, true);

        return;
      }
    }

    return true;
  }

  /**
   * Check if JoomGallery component is installed.
   *
   * @return  int|bool   Extension id on success, false otherwise
   *
   * @since   4.0.0
   */
  protected function isJGExists()
  {
    if(\is_null(self::$jg_exists))
    {
      $query = $this->db->getQuery(true);

      $query->select('extension_id')
            ->from('#__extensions')
            ->where( array( 'type LIKE ' . $this->db->quote('component'),
                            'element LIKE ' . $this->db->quote('com_joomgallery')
                          ));

      $this->db->setQuery($query);

      if(!$res = $this->db->loadResult())
      {
        $res = false;
      }

      self::$jg_exists = $res;
    }

    return self::$jg_exists;
  }

  /**
   * Guess the content type based on a dot separated string.
   *
   * @param   string        $string  Context like string
   * @param   bool          $id      Return id (second value)
   *
   * @return  string|false  Guessed type on success, false otherwise
   *
   * @since   4.0.0
   */
  protected function guessType(string $string, $id=false)
  {
    // Detect type from menuitem
    if(\strpos($string, 'com_menus') === 0)
    {
      // Get menuitem id from JInput
      if(!$itemid = $this->app->input->get('id', 0, 'int'))
      {
        return false;
      }

      // Get menuitem model
      $menuModel = $this->app->bootComponent('com_menus')->getMVCFactory()->createModel('item', 'administrator');
      $menuItem  = $menuModel->getItem($itemid);

      if(!$menuItem || \strpos($menuItem->link, 'com_joomgallery') === false)
      {
        // Menuitem is not related to joomgallery
        return false;
      }

      // We have a menuitem that is related to joomgallery
      foreach(\explode('&', $menuItem->link) as $key => $value)
      {
        // Read type from the link variable: 'view'
        if(\strpos($value, 'view') !== false)
        {
          return \str_replace('view=', '', $value);
        }
      }
    }

    $pieces = \explode('.', $string);

    if(\count($pieces) > 1)
    {
      if($id && \count($pieces) > 2)
      {
        return \strtolower($pieces[2]);
      }
      else
      {
        return \strtolower($pieces[1]);
      }
    }

    return false;
  }

  /**
   * Returns the plugin result
   *
   * @param   Event  $event  The event object
   * @param   mixed  $value  The value to be added to the result
   * @param   bool   $array  True, if the result has to be added/set to the result array. False to override the boolean result value.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  private function setResult(Event $event, $value, $array=true): void
	{
		if($event instanceof ResultAwareInterface)
    {
			$event->addResult($value);

			return;
		}

    if($array)
    {
      $result   = $event->getArgument('result', []) ?: [];
		  $result   = \is_array($result) ? $result : [];
		  $result[] = $value;
    }
    else
    {
      $result   = $event->getArgument('result', true) ?: true;
      $result   = ($result == false) ? false : $value;
    }

		$event->setArgument('result', $result);
	}

 /**
   * Returns the plugin error
   *
   * @param   Event  $event    The event object
   * @param   mixed  $message  The message to be added to the error
   *
   * @return  void
   *
   * @since   4.0.0
   */
  private function setError(Event $event, $message): void
	{
		if($event instanceof EventInterface)
    {

      $event->setArgument('error', $message);
      $event->setArgument('errorMessage', $message);

			return;
		}
	}

  /**
   * Delete user fields in DB
   *
   * @param   int  $userId  User id
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function deleteFields($userId)
  {
    $query = $this->db->getQuery(true)
        ->delete($this->db->quoteName('#__user_profiles'))
        ->where($this->db->quoteName('user_id') . '=' . (int) $userId)
        ->where($this->db->quoteName('profile_key') . ' LIKE ' . $this->db->quote('joomgallery.%'));

    $this->db->setQuery($query);
    $this->db->execute();
  }

  /**
   * Insert new user fields in DB
   *
   * @param   int     $userId    User id
   * @param   string  $name      Field name
   * @param   string  $value     Field value
   * @param   int     $ordering  Field ordering number
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function insertField($userId, $name, $value, $ordering)
  {
    $columns = array('user_id', 'profile_key', 'profile_value', 'ordering');
    $values  = array( $userId, $this->db->quote('joomgallery.' . $name), $this->db->quote($value), $ordering);

    $query = $this->db->getQuery(true)
        ->insert($this->db->quoteName('#__user_profiles'))
        ->columns($this->db->quoteName($columns))
        ->values(implode(',', $values));

    $this->db->setQuery($query);
    $this->db->execute();
  }

  /**
   * Get user fields from DB
   *
   * @param   int    $userId  User id
   *
   * @return  array  List of user fields
   *
   * @since   4.0.0
   */
  protected function getFields($userId)
  {
    $columns = array('profile_key', 'profile_value');

    $query = $this->db->getQuery(true)
        ->select($this->db->quoteName($columns))
        ->from($this->db->quoteName('#__user_profiles'))
        ->where($this->db->quoteName('profile_key') . ' LIKE ' . $this->db->quote('joomgallery.%'))
        ->where($this->db->quoteName('user_id') . '=' . (int) $userId)
        ->order('ordering ASC');

    $this->db->setQuery($query);

    return $this->db->loadRowList();
  }
}
