<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Plugin\System\Joomgallery\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\CacheHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Event\Result\ResultAwareInterface;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Event\DispatcherAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\EventInterface;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;

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
  protected $allowedFormContext = ['com_users.profile', 'com_users.user', 'com_users.registration', 'com_admin.profile'];

  /**
   * Before-write snapshots keyed by table object.
   *
   * @var \WeakMap|null
   *
   * @since 4.5.0
   */
  private ?\WeakMap $coreCacheInputs = null;

  /**
   * Prevents repeated invalidation when Joomla clears several cache groups
   *
   * @var     bool
   *
   * @since   4.5.0
   */
  private bool $cacheMaintenanceHandled = false;

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
        'onAfterPurge'           => ['onAfterPurge', Priority::NORMAL],
        'onContentPrepareForm'   => ['onContentPrepareForm', Priority::NORMAL],
        'onContentPrepareData'   => ['onContentPrepareData', Priority::NORMAL],
        'onUserAfterSave'        => ['onUserAfterSave', Priority::NORMAL],
        'onUserAfterDelete'      => ['onUserAfterDelete', Priority::NORMAL],
        'onContentAfterSave'     => ['onContentAfterSave', Priority::NORMAL],
        'onTableBeforeStore'     => ['captureCoreCacheInputs', Priority::NORMAL],
        'onTableAfterStore'      => ['invalidateCoreCacheInputs', Priority::NORMAL],
        'onTableBeforeDelete'    => ['captureCoreCacheInputs', Priority::NORMAL],
        'onTableAfterDelete'     => ['invalidateCoreCacheInputs', Priority::NORMAL],
      ];
    }


      return [];
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
    if(version_compare(JVERSION, '5.0.0', '<'))
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
    if(version_compare(JVERSION, '5.0.0', '<'))
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

      if(!isset($data->joomgallery) && $userId > 0)
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

        $data->joomgallery = [];

        foreach($fields as $field)
        {
          $fieldName                     = str_replace('joomgallery.', '', $field[0]);
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
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$data, $isNew, $result, $error] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $data   = $event->getUser();
      $result = $event->getSavingResult();
    }

    // Save the extra input into the database
    $userId = isset($data['id']) ? (int) $data['id'] : 0;

    if($userId && $result && isset($data['joomgallery']) && (\count($data['joomgallery'])))
    {
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
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$data, $result, $error] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      extract($event->getArguments());
      $data   = $event->getUser();
      $result = $event->getDeletingResult();
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
   * Detect tables and fields relevant to the cache policy
   *
   * @param   Event   $event
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function captureCoreCacheInputs(Event $event): void
  {
    $table = $event->getArgument('subject');
    $kind  = null;

    foreach(['assets', 'extensions', 'menu', 'usergroups', 'viewlevels'] as $candidate)
    {
      if(\in_array($table->getTableName(), ['#__' . $candidate, $this->db->replacePrefix('#__' . $candidate)], true))
      {
        $kind = $candidate;
        break;
      }
    }

    if($kind === null) return;

    if($kind === 'extensions' && (($table->element ?? '') !== 'com_joomgallery' || ($table->type ?? '') !== 'component')) return;

    $key = $table->getKeyName();
    $id  = (int) $event->getArgument('pk', $table->$key ?? 0);
    $row = CacheHelper::row($this->db, $table->getTableName(), $id, $key);

    if($kind === 'assets')
    {
      // Include the persisted name for renamed assets and deletes by primary key.
      $names = [(string) ($row['name'] ?? ''), (string) ($table->name ?? '')];

      if(!array_filter($names, static fn($name) => $name === 'root.1' || preg_match('/^com_joomgallery(?:\.|$)/', $name))) return;
    }

    JoomHelper::getComponent();
    $rows = $row ? [$id => $row] : [];

    // Deleting a non-gallery parent can also delete gallery menu children.
    if( $kind === 'menu' && $event->getName() === 'onTableBeforeDelete' &&
        $event->getArgument('children', true) && isset($row['lft'], $row['rgt'])
      )
    {
      $query = $this->db->getQuery(true)->select('*')->from($this->db->quoteName($table->getTableName()))
        ->where($this->db->quoteName('lft') . ' >= ' . (int) $row['lft'])
        ->where($this->db->quoteName('rgt') . ' <= ' . (int) $row['rgt']);
      $rows  = $this->db->setQuery($query)->loadAssocList($key);
    }

    $this->coreCacheInputs       ??= new \WeakMap();
    $this->coreCacheInputs[$table] = [$kind, $key, $rows];
  }

  /**
   * Compare persisted values after writes, including nested-table deletes.
   * Root and JoomGallery asset rules/parent links cover inherited permissions.
   *
   * @param   Event   $event
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function invalidateCoreCacheInputs(Event $event): void
  {
    $table = $event->getArgument('subject');

    if($this->coreCacheInputs === null || !isset($this->coreCacheInputs[$table])) return;

    [$kind, $key, $before] = $this->coreCacheInputs[$table];
    unset($this->coreCacheInputs[$table]);

    $ids    = array_unique(array_merge(array_keys($before), [(int) ($table->$key ?? 0)]));
    $scopes = [];

    foreach($ids as $id)
    {
      $after  = CacheHelper::row($this->db, $table->getTableName(), (int) $id, $key);
      $scopes = array_merge($scopes, CacheHelper::coreScopes($kind, $before[$id] ?? [], $after));
    }

    foreach(array_unique($scopes) as $scope)
    {
      JoomHelper::getComponent()->getCacheRevision()->invalidate($scope);
    }
  }

  /**
   * Event triggered after saving an item form.
   *
   * @param   Event   $event
   *
   * @return  boolean  True to continue with the storing process, false to stop it
   *
   * @since   4.3.0
   */
  public function onContentAfterSave(Event $event)
  {
    // J4x and J5x (5x: $context = getContext (); $table = $event->getArgument ('subject');
    [$context, $table, $isNew] = array_values($event->getArguments());


    if(!\in_array($context, ['com_menus.item']) || !$this->app->isClient('administrator'))
    {
      return;
    }

    // Only continue when we are saving a new frontend menu item for a component
    if($isNew && $table && $table->client_id == 0 && $table->type == 'component')
    {
      $uri = new Uri($table->link);

      if($uri && $uri->getVar('option', '') == 'com_joomgallery' && $uri->getVar('view', '') == 'userpanel')
      {
        $jgmenuitems = $this->app->getMenu('site')->getItems(['component'], ['com_joomgallery']);

        // Check if already a usercategories menu item exists
        $exists = false;

        foreach($jgmenuitems as $menuitem)
        {
          if( isset($menuitem->query['view']) && $menuitem->query['view'] == 'usercategories' &&
              isset($menuitem->query['id']) && $menuitem->query['id'] == '1'
            )
          {
            $exists = true;
          }
        }

        // Only continue when we are saving a userpanel manu item
        if(!$exists)
        {
          // Create menuitem automatically as a child of this menuitem
          $com_menu  = $this->app->bootComponent('com_menus');
          $new_table = $com_menu->getMVCFactory()->createTable('menu', 'administrator');

          if(!$new_table)
          {
            return;
          }

          // Gallery menuitem
          $data                 = [];
          $data['id']           = null;
          $data['parent_id']    = $table->id;
          $data['menutype']     = $table->menutype;
          $data['title']        = Text::_('JCATEGORIES');
          $data['path']         = $table->path;
          $data['language']     = $table->language;
          $data['link']         = 'index.php?option=com_joomgallery&view=usercategories&id=1';
          $data['type']         = $table->type;
          $data['published']    = $table->published;
          $data['level']        = $table->level + 1;
          $data['component_id'] = $table->component_id;
          $data['access']       = $table->access;
          $data['img']          = $table->img;
          $data['params']       = '{"menu_show":0}';

          if(!$new_table->bind($data))
          {
            return;
          }

          $new_table->setLocation($data['parent_id'], 'last-child');

          if($new_table->store($data))
          {
            $this->app->enqueueMessage(Text::_('PLG_SYSTEM_JOOMGALLERY_MSG_USERCATEGORIES_SUCCESS'), 'notice');
          }
          else
          {
            $this->app->enqueueMessage(Text::_('PLG_SYSTEM_JOOMGALLERY_MSG_USERCATEGORIES_FAILED'), 'notice');
          }
        }
      }
    }
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
            ->where(
                [
                  'type LIKE ' . $this->db->quote('component'),
                  'element LIKE ' . $this->db->quote('com_joomgallery'),
                ]
            );

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
  private function setResult(Event $event, $value, $array = true): void
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
      $result = $event->getArgument('result', true) ?: true;
      $result = ($result == false) ? false : $value;
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
    $columns = ['user_id', 'profile_key', 'profile_value', 'ordering'];
    $values  = [$userId, $this->db->quote('joomgallery.' . $name), $this->db->quote($value), $ordering];

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
    $columns = ['profile_key', 'profile_value'];

    $query = $this->db->getQuery(true)
        ->select($this->db->quoteName($columns))
        ->from($this->db->quoteName('#__user_profiles'))
        ->where($this->db->quoteName('profile_key') . ' LIKE ' . $this->db->quote('joomgallery.%'))
        ->where($this->db->quoteName('user_id') . '=' . (int) $userId)
        ->order('ordering ASC');

    $this->db->setQuery($query);

    return $this->db->loadRowList();
  }

  /**
   * Mirrors successful administrator cache operations in gallery session caches
   *
   * @param   EventInterface  $event  the cache purge event
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function onAfterPurge(EventInterface $event): void
  {
    $app = $this->getApplication();

    if( !$app->isClient('administrator') || $this->cacheMaintenanceHandled ||
        $app->input->getCmd('option') !== 'com_cache' || !$app->getIdentity()->authorise('core.manage', 'com_cache')
      )
    {
      return;
    }

    $task = strtolower($app->input->getCmd('task'));
    $task = substr($task, strrpos('.' . $task, '.'));

    if(!\in_array($task, ['delete', 'deleteall', 'purge'], true)) return;

    JoomHelper::getComponent()->clearCaches($task === 'purge');
    $this->cacheMaintenanceHandled = true;
  }
}
