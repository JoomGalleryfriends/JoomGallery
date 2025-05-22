<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Registry\Registry;
use \Joomla\CMS\MVC\Model\ItemModel;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Base model class for JoomGallery items
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomItemModel extends ItemModel
{
  /**
   * Joomla application class
   *
   * @access  protected
   * @var     Joomla\CMS\Application\AdministratorApplication
   */
  protected $app;

  /**
   * JoomGallery extension class
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   */
  protected $component;

  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Item object
   *
   * @access  protected
   * @var     object
   */
	protected $item = null;

  /**
   * JoomGallery access service
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface
   */
  protected $acl = null;

  /**
	 * Constructor
	 *
	 * @param   array                $config   An array of configuration options (name, state, dbo, table_path, ignore_request).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 *
	 * @since   4.0.0
	 * @throws  \Exception
	 */
	public function __construct($config = [], $factory = null)
  {
    parent::__construct($config, $factory);

    $this->app       = Factory::getApplication('site');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
  }

  /**
	 * Method to get parameters from model state.
	 *
	 * @return  Registry[]   List of parameters
   * @since   4.0.0
	 */
	public function getParams(): array
	{
		$params = array('component' => $this->getState('parameters.component'),
										'menu'      => $this->getState('parameters.menu'),
									  'configs'   => $this->getState('parameters.configs')
									);

		return $params;
	}

  /**
   * Method to override a parameter in the model state
   * 
   * @param   string  $property  The parameter name.
   * @param   string  $value     The parameter value.
   * @param   string  $type      The parameter type. Optional. Default='configs'
   *
   * @return  void
   * @since   4.0.0
   */
  public function setParam(string $property, string $value, $type = 'configs')
  {
    // Get params
    $params = $this->getState('parameters.' . $type);

    // Set new value
    $params->set($property, $value);

    // Set params to state
    $this->setState('parameters.' . $type, $params);
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
	 * Get an instance of Table class
	 *
	 * @param   string  $type     Name of the Table class to get an instance of.
	 * @param   string  $prefix   Prefix for the table class name. Optional.
	 * @param   array   $config   Array of configuration values for the Table object. Optional.
	 *
	 * @return  Table|bool Table if success, false on failure.
	 */
	public function getTable($type = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($this->type, $prefix, $config);
	}

  /**
	 * Method to check in an item.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function checkin($id = null)
	{
		// Get the id.
		$id = (!empty($id)) ? $id : (int) $this->getState('image.id');

		if($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Attempt to check the row in.
			if(\method_exists($table, 'checkin'))
			{
				if(!$table->checkin($id))
				{
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Method to check out an item for editing.
	 *
	 * @param   integer $id The id of the row to check out.
	 *
	 * @return  boolean True on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function checkout($id = null)
	{
		// Get the user id.
		$id = (!empty($id)) ? $id : (int) $this->getState('image.id');

		if($id)
		{
			// Initialise the table
			$table = $this->getTable();

			// Get the current user object.
			$user = $this->app->getIdentity();

			// Attempt to check the row out.
			if(\method_exists($table, 'checkout'))
			{
				if(!$table->checkout($user->id, $id))
				{
					return false;
				}
			}
		}

		return true;
	}

	

  /**
	 * Method to load component specific parameters into model state.
   * 
   * @param   int   $id   ID of the content if needed (default: 0)
	 *
	 * @return  void
   * @since   4.0.0
	 */
  protected function loadComponentParams(int $id=0)
  {
    // Load the parameters.
		$params       = Factory::getApplication('com_joomgallery')->getParams();
		$params_array = $params->toArray();

		if(isset($params_array['item_id']))
		{
			$this->setState($this->type.'.id', $params_array['item_id']);
		}

		$this->setState('parameters.component', $params);

    // Load the configs from config service
    $id = ($id === 0) ? null : $id;

		$this->component->createConfig(_JOOM_OPTION.'.'.$this->type, $id, true);
		$configArray = $this->component->getConfig()->getProperties();
		$configs     = new Registry($configArray);

		$this->setState('parameters.configs', $configs);
  }
}
