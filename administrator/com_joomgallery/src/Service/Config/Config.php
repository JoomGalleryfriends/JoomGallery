<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\User\UserHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Config\ConfigInterface;

/**
 * JoomGallery Configuration Helper
 *
 * Provides handling with all configuration settings of the gallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class Config implements ConfigInterface
{
  /**
   * Determines whether extended configuration
   * manager is enabled
   *
   * @var boolean
   */
  protected $extended = false;

  /**
   * Array with key values of subforms
   *
   * @var array
   */
  protected $subforms = array('jg_replaceinfo', 'jg_staticprocessing', 'jg_dynamicprocessing', 'jg_imgtypewtmsettings');

  /**
   * Table object of the `#_joomgallery_configs` db table
   *
   * @var \Joomla\CMS\Table\Table
   */
  protected $table = null;

  /**
   * Constructor
   *
   * @param   int  $id  row id of the config record to be loaded
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($id = 1)
  {
    if(ComponentHelper::getParams(_JOOM_OPTION)->get('extended_config', 0))
    {
      $this->extended = true;
    }

    // get global configuration set
    $glob_params = $this->getParamsByID($id);

    if($glob_params == false || empty($glob_params))
    {
      Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_COMMON_ERROR_LOAD_CONFIG'), 'error');

      return;
    }

    // write config values to class properties
    $this->setParamsToClass($glob_params);

    // get user specific configuration set
    $user_params = $this->getParamsByGroups();

    // override class properties where needed
    if($user_params != false && !empty($user_params))
    {
      $this->setParamsToClass($user_params);
    }

    if(Factory::getApplication()->isClient('site'))
    {
      // get config values from current category

      // override class properties where needed

      // get config values from current image

      // override class properties where needed

      // get config values from current manuitem

      // override class properties where needed
    }
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
	private function setParamsToClass($params)
	{
    foreach($params as $key => $value)
    {
      if(strncmp($key, 'jg_', strlen('jg_')) === 0)
      {
        // param key starts with 'jg_'

        if(in_array($key,$this->subforms))
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
	private function getParamsByID($id = 1)
	{
    if($this->table == null)
    {
      $this->table = Table::getInstance('ConfigTable', '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\');
    }

    $id     = intval($id);

    $this->table->load($id);
    $params = $this->table->getProperties();
    $this->table->reset();

    return $params;
  }

  /**
	 * Read out the row from `#_joomgallery_configs` table
   * with the biggest group_id number
   * and correspond to the user group of the current user
	 *
	 * @return  array  record values
	 *
	 * @since   4.0.0
	 */
  private function getParamsByGroups()
  {
    // get array of all user groups the current user is in
    $user    = Factory::getUser();
    $groups  = $user->get('groups');

    // get a db connection
    $db = Factory::getDbo();

    // create the query
    $query = $db->getQuery(true);
    $query->select($db->quoteName('id').', MAX('.$db->quoteName('group_id').')');
    $query->from($db->quoteName(_JOOM_TABLE_CONFIGS));
    $query->where($db->quoteName('group_id').' IN '.' (\''.implode('\', \'', $groups).'\')');

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
	 * Sets a default value if not already assigned
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed
	 *
	 * @since   4.0.0
	 */
	public function def($property, $default = null)
	{
		$value = $this->get($property, $default);

		return $this->set($property, $value);
	}

  /**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since   4.0.0
	 */
	public function get($property, $default = null)
	{
		if (isset($this->$property))
		{
			return $this->$property;
		}

		return $default;
	}

  /**
	 * Returns an associative array of object properties.
	 *
	 * @param   boolean  $public  If true, returns only the public properties.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public function getProperties($public = true)
	{
		$vars = get_object_vars($this);

		if ($public)
		{
			foreach ($vars as $key => $value)
			{
				if ('_' == substr($key, 0, 1))
				{
					unset($vars[$key]);
				}
			}
		}

		return $vars;
	}

  /**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $value     The value of the property to set.
	 *
	 * @return  mixed  Previous value of the property.
	 *
	 * @since   4.0.0
	 */
	public function set($property, $value = null)
	{
		$previous = $this->$property ?? null;
		$this->$property = $value;

		return $previous;
	}

  /**
	 * Set the object properties based on a named array/hash.
	 *
	 * @param   mixed  $properties  Either an associative array or another object.
	 *
	 * @return  boolean
	 *
	 * @since   1.7.0
	 */
	public function setProperties($properties)
	{
		if (\is_array($properties) || \is_object($properties))
		{
			foreach ((array) $properties as $k => $v)
			{
				// Use the set function which might be overridden.
				$this->set($k, $v);
			}

			return true;
		}

		return false;
	}
}
