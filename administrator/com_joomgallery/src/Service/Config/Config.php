<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\User\User;
use \Joomla\CMS\Language\Text;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigInterface;

/**
 * Configuration Class
 *
 * Provides methods to handle configuration sets of the gallery
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class Config extends \stdClass implements ConfigInterface
{
  use ServiceTrait;

  /**
   * Name of the config service
   *
   * @var string
   */
  protected $name = 'Config';

  /**
   * Array with key values of subforms
   *
   * @var array
   */
  protected $subforms = array('jg_replaceinfo', 'jg_staticprocessing', 'jg_dynamicprocessing', 'jg_imgtypewtmsettings');

  /**
   * Content for which the settings has to be calculated
   *
   * @var string
   */
  protected $context = 'com_joomgallery';

  /**
   * IDs of the different content
   *
   * @var array
   */
  protected $ids = array('user' => null, 'gallery' => null, 'category' => null, 'image' => null, 'menu' => null);

  /**
   * Simple unique string for this parameter combination
   *
   * @var string
   */
  protected $storeId = null;

  /**
   * Array of cached parameter by usergroup and context.
   *
   * @var    array
   */
  protected static $cache = array();

  /**
   * Loading the calculated settings for a specific content
   * to class properties
   *
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the content if needed (default: null)
   * @param   bool		 $inclOwn   True, if you want to include settings of current item (default: true)
   * @param   bool     $useCache  True, to load params from cache if available (default: true)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($context = 'com_joomgallery', $id = null, $inclOwn = true, $useCache = true)
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Check context
    $context_array = \explode('.', $context);

    if( $context_array[0] != 'com_joomgallery' || 
        (\count($context_array) > 1 && !\array_key_exists($context_array[1], $this->ids)) || 
        (\count($context_array) > 2 && $context_array[2] != 'id')
      )
    {
      $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_CONFIG_INVALID_CONTEXT', $context), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CONFIG_INVALID_CONTEXT', $context), 'error', 'jerror');

      $this->context = false;
    }
    else
    {
      $this->context = $context;
    }

    // Load cache from session
    $cache = Factory::getApplication()->getSession()->get('com_joomgallery.configcache.'.$this->name);
    if(!empty($cache))
    {
      self::$cache = $cache;
    }

    // Get current user
    $user = Factory::getApplication()->getIdentity();

    // Completing $this->ids based on given context
    if(\count($context_array) > 1)
    {
      switch($context_array[1])
      {
        case 'user':
          $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $id);
          $this->ids['user'] = (int) $id;
          break;
        
        case 'gallery':
          $this->ids['user']     = $user->id;
          $this->ids['category'] = 1;
          break;

        case 'category':
          $this->ids['user']     = $user->id;
          $this->ids['category'] = (int) $id;
          break;

        case 'image':
          $img = $this->component->getMVCFactory()->createModel('image', 'administrator')->getItem($id);

          $this->ids['user']     = $user->id;
          $this->ids['image']    = (int) $id;
          $this->ids['category'] = (int) $img->catid;
          break;

        case 'menu':
          $this->ids['user'] = $user->id;
          $this->ids['menu'] = (int) $id;
          // TBD
          // Depending on frontend views and router
          break;
        
        default:
          $this->ids['user'] = $user->id;
          break;
      }
    }
    else
    {
      $this->ids['user'] = $user->id;
    }

    // Add menuid if called from the frontend
    if(Factory::getApplication()->isClient('site'))
    {
      $menuitem = Factory::getApplication()->getMenu()->getActive();

      if(isset($menuitem->id) && $menuitem->component == 'com_joomgallery')
      {
        $this->ids['menu'] = (int) $menuitem->id;
      }
    }
    
    // Creates a simple unique string for each parameter combination
    $group         = $this->getUsergroup($user);
    $contentId     = \is_null($id) ? '' : ':'.$id;
    $own           = \is_null($inclOwn) ? '' : ':1';
    $this->storeId = $this->name.':'.$this->context.':'.$group.$contentId.$own;
    // ConfigName:context:usergroup:own
  }

  /**
   * Store the current available caches to the session
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function storeCacheToSession()
  {
    // Store current caches to session
    if(!empty(self::$cache))
    {
      $res = \array_merge(Factory::getApplication()->getSession()->get('com_joomgallery.configcache.'.$this->name, array()), self::$cache);
      Factory::getApplication()->getSession()->set('com_joomgallery.configcache.'.$this->name, $res);
    }
  }

  /**
   * Empty all the cache
   *
   * @param   string|false   $type   Type name of types to delete the cache from. False: Delete all types
   * 
   * @return  void
   *
   * @since   4.0.0
   */
  public function emptyCache($type=false)
  {
    $configServices = array('Config', 'DefaultConfig');

    foreach($configServices as $service)
    {
      if(\strpos($type, 'user') === 0)
      {
        // Delete only cache which is related to one of the usergoups this user is part of
        $user_array = \explode('.', $type);
        $user_id    = (\count($user_array) > 1) ? $user_array[1] : 0;
        $user       = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $user_id);
        $usergroups = $user->get('groups');
        $regex      = '/^'.$service.':com_joomgallery.*:\b('.\implode('|', $usergroups).')\b:.*/';
      }
      elseif(\strpos($type, 'image') === 0)
      {
        // Delete only cache which is related to context of type image
        $context = 'com_joomgallery.image';
        $regex = '/^'.$service.':'.$context.'.*:.*/';
      }
      elseif(\strpos($type, 'category') === 0)
      {
        // Delete only cache which is related to context of type image or category
        $context = 'com_joomgallery.\b(image|category)\b';
        $regex = '/^'.$service.':'.$context.'.*:.*/';
      }
      else
      {
        // Delete all cache
        $regex   = false;
      }

      // Delete cache based on regex
      $this->deleteCache($regex, $service);
    }
  }

  /**
   * Delete object & session cache
   *
   * @param   string|false   $storeId   ID of the cache to be deleted. Can be a regex pattern to delete all matching items. False: Delete everything
   * @param   string         $name      Name of the config service which cache gets deleted
   * 
   * @return  void
   *
   * @since   4.0.0
   */
  protected function deleteCache($storeId=false, $name=false)
  {
    if(!$name)
    {
      // If no config service name is provided, use the name of the current service instance
      $name = $this->name;
    }

    if($storeId)
    {
      // Delete matching entries in static object property
      self::$cache = $this->del_preg_keys($storeId, self::$cache);

      // Get session cache
      $session = Factory::getApplication()->getSession()->get('com_joomgallery.configcache.'.$name);
      if($session && \is_array($session))
      {
        // Delete matching entries in session
        Factory::getApplication()->getSession()->set('com_joomgallery.configcache.'.$name, $this->del_preg_keys($storeId, $session));
      }
    }
    else
    {
      // No storeId provided. Delete everything.
      self::$cache = array();
      Factory::getApplication()->getSession()->set('com_joomgallery.configcache.'.$name, array());
    }
  }

  /**
   * Set properties to object cache
   *
   * @param   string   $storeId   The id under which to store the properties
   * 
   * @return  void
   *
   * @since   4.0.0
   */
  protected function setCache(string $storeId)
  {
    /**
    * Cashing the calculated params allows us to store
    * one instance of the Config object for contexts that have
    * the same exact configs.
    */
    self::$cache[\base64_encode($this->storeId)] = $this->getProperties();
  }

  /**
	 * Writes params from database record to class properties
	 *
	 * @param   array  $params  Array of all configs
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	public function setParamsToClass($params)
	{
    foreach($params as $key => $value)
    {
      if(\strncmp($key, 'jg_', \strlen('jg_')) === 0)
      {
        // param key starts with 'jg_'

        if(\in_array($key, $this->subforms))
        {
          // param is a subform
          $object = \json_decode($value, false);

          // convert to object if needed
          if(\is_array($object))
          {
            $object = (object) $object;
          }

          // set object to class property
          if(!isset($this->$key) || $object != $this->$key)
          {
            $this->set($key, $object);
          }
        }
        else
        {
          // set param to class property
          if(!isset($this->$key) || $value !== $this->$key)
          {
            if($value == '-1' || $value == '')
            {
              continue;
            }
            elseif(\is_integer($value) || \is_bool($value) || $value === '1' || $value === '0')
            {
              $value = \intval($value);
            }
            elseif(\is_numeric($value))
            {
              $value = \floatval($value);
            }

            $this->set($key, $value);
          }
        }
      }
    }
  }

  /**
	 * Read out a row by id from `#_joomgallery_configs` table
	 *
	 * @param   int    $id  id of param row to be loaded (default: 1)
	 *
	 * @return  array  record values
	 *
	 * @since   4.0.0
	 */
	protected function getParamsByID($id = 1)
	{
    $com_obj = $this->app->bootComponent('com_joomgallery');
    $model   = $com_obj->getMVCFactory()->createModel('Config', 'administrator');

    $id   = intval($id);
    $item = $model->getItem($id);

    if(\method_exists($item, 'getProperties'))
    {
      $properties = $item->getProperties();
    }
    else
    {
      $properties = \get_object_vars($item);
    }

    return $properties;
  }

  /**
	 * Read out the row from `#_joomgallery_configs` table
   * with the biggest group_id number
   * and correspond to the user group of the given user
	 *
   * @param   int|string|User   $user   User id, username or userobject
   * 
	 * @return  array  record values
	 *
	 * @since   4.0.0
	 */
  protected function getParamsByUser($user)
  {
    $usergroup = $this->getUsergroup($user);

    // get a db connection
    $db = Factory::getContainer()->get(DatabaseInterface::class);

    // create the query
    $query = $db->getQuery(true);
    $query->select($db->quoteName('id'));
    $query->from($db->quoteName(_JOOM_TABLE_CONFIGS));
    $query->where($db->quoteName('group_id') . ' = ' . $db->quote($usergroup));
    $query->where($db->quoteName('published') . ' = 1');

    // set the query
    $db->setQuery($query);

    // load the matching param-set as an associated array
    $match = $db->loadAssoc();

    // load matching configuration set
    $params = false;
    if(!empty($match['id']))
    {
      $params = $this->getParamsByID($match['id']);
    }

    return $params;
  }

  /**
	 * Get the usergoup id to use for loading the config set
	 *
   * @param   int|string|User   $user   User id, username or userobject
   * 
	 * @return  int               ID of the usergroup
	 *
	 * @since   4.0.0
	 */
  protected function getUsergroup($user)
  {
    // Load user if needed
    if(!($user instanceof User))
    {
      if(\is_numeric($user))
      {
        // We assume that $user is a user id
        $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $user);
      }
      else
      {
        // We assume that $user is a username
        $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserByUsername($user);
      }
    }

    $userGroups  = \array_values($user->get('groups'));
    $configGroup = $this->getUserSetting($user->get('id'));
    
    if(\in_array($configGroup, $userGroups))
    {
      // Great there is a valid usergroup selected in the user options
      return $configGroup;
    }
    elseif(!empty($userGroups))
    {
      // There is no possible usergroup set in the user options
      // Use the first usergroup in the list
      return $userGroups[0];
    }
    else
    {
      // This user has no usergoup
      return 1;
    }
  }

  /**
   * Get user field 'config_usergroup' from DB
   *
   * @param   int   $userId  User id
   *
   * @return  int   Group id to use for clc config set
   *
   * @since   4.0.0
   */
  protected function getUserSetting($userId) 
  {
    // get a db connection
    $db = Factory::getContainer()->get(DatabaseInterface::class);

    $query = $db->getQuery(true)
        ->select($db->quoteName('profile_value'))
        ->from($db->quoteName('#__user_profiles'))
        ->where($db->quoteName('profile_key') . ' = ' . $db->quote('joomgallery.config_usergroup'))
        ->where($db->quoteName('user_id') . '=' . (int) $userId);

    $db->setQuery($query);

    return (int) $db->loadResult();
  }

  /**
	 * Deletes entriey of key or id in the array matching the given regex pattern
	 *
   * @param   string   $pattern   The pattern to search for, as a string.
   * @param   array    $array    An array containing base64 encoded keys to delete. 
   * 
	 * @return  array    The emptied array.
	 *
	 * @since   4.0.0
	 */
  protected function del_preg_keys(string $pattern, array $array)
  {
    // Check if the pattern provided is valid
    if(@\preg_match($pattern, '') === false)
    {
      // Return the complete if the pattern is not valid
      return $array;
    }

    foreach(\array_keys($array) as $key)
    {
      // Decode the key
      $decodedKey = \base64_decode($key);

      // Check if the decoded key matches the pattern
      if(\preg_match($pattern, $decodedKey))
      {
        // If it matches, unset the original (encoded) key from the array
        unset($array[$key]);
      }
    }

    return $array;
  }
}
