<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Table\Table;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Language\Text;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\Database\ParameterType;
use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\User\CurrentUserInterface;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Access\AccessInterface;

/**
 * Base model class for JoomGallery administration views
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class JoomAdminModel extends AdminModel
{
  /**
	 * Alias to manage history control
	 *
	 * @access  public
   * @var     string
	 */
	public $typeAlias = '';

  /**
   * Joomla application class
   *
   * @access  protected
   * @var     Joomla\CMS\Application\AdministratorApplication
   */
  protected $app;

  /**
   * Joomla user object
   *
   * @access  protected
   * @var     Joomla\CMS\User\User
   */
  protected $user;

  /**
   * JoomGallery extension class
   *
   * @access  protected
   * @var     Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
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
	 * The prefix to use with controller messages.
	 *
	 * @access  protected
   * @var     string
	 */
	protected $text_prefix = _JOOM_OPTION_UC;

	/**
   * Item object
   *
   * @access  protected
   * @var     object
   */
	protected $item = null;

  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Constructor.
   *
   * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function __construct($config = [], MVCFactoryInterface $factory = null, FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $formFactory);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = $this->component->getMVCFactory()->getIdentity();
    $this->typeAlias = _JOOM_OPTION.'.'.$this->type;
  }

  /**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($this->type, $prefix, $config);
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
	 * Method to save image from form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   4.0.0
	 */
	public function save($data)
	{
    $table = $this->getTable();
    $key   = $table->getKeyName();
		$pk    = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

    // Change language to 'All' if multilangugae is not enabled
    if (!Multilanguage::isEnabled())
    {
      $data['language'] = '*';
    }

    if($pk > 0)
		{
      $table->load($pk);

      // Check if the state was changed
      if($table->published != $data['published'])
      {
        if(!$this->getAcl()->checkACL('core.edit.state', $this->type, $table->id))
        {
          // We are not allowed to change the published state
          $this->component->addWarning(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
          $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'error', 'jerror');
          $data['published'] = $table->published;
        }
      }
    }

    return parent::save($data);
  }

  /**
   * Method override to check-in a record or an array of record
   *
   * @param   mixed  $pks  The ID of the primary key or an array of IDs
   *
   * @return  integer|boolean  Boolean false if there is an error, otherwise the count of records checked in.
   *
   * @since   4.0.0
   */
  public function checkin($pks = [])
  {
    $pks   = (array) $pks;
    $table = $this->getTable();
    $count = 0;

    if(empty($pks))
    {
      $pks = [(int) $this->getState($this->getName() . '.id')];
    }

    $checkedOutField = $table->getColumnAlias('checked_out');

    // Check in all items.
    foreach ($pks as $pk)
    {
        if ($table->load($pk))
        {
          if($table->{$checkedOutField} > 0)
          {
            if(!$this->checkinOne($pk))
            {
              return false;
            }

            $count++;
          }
        }
        else
        {
          $this->component->setError($table->getError());
          $this->component->addLog($table->getError(), 'error', 'jerror');

          return false;
        }
    }

    return $count;
  }

  /**
   * Method to checkin a row.
   *
   * @param   integer  $pk  The numeric id of the primary key.
   *
   * @return  boolean  False on failure or error, true otherwise.
   *
   * @since   4.0.0
   */
  public function checkinOne($pk = null)
  {
    // Only attempt to check the row in if it exists.
    if($pk)
    {
      // Get an instance of the row to checkin.
      $table = $this->getTable();

      if(!$table->load($pk))
      {
        $this->setError($table->getError());
        $this->component->addLog($table->getError(), 'error', 'jerror');

        return false;
      }

      // If there is no checked_out or checked_out_time field, just return true.
      if(!$table->hasField('checked_out') || !$table->hasField('checked_out_time'))
      {
        return true;
      }

      $checkedOutField = $table->getColumnAlias('checked_out');

      // Check if this is the user having previously checked out the row.
      if( $table->$checkedOutField > 0 && $table->$checkedOutField != $this->user->id &&
          !$this->user->authorise('core.manage', 'com_checkin')
        )
      {
        $this->component->setError(Text::_('JLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'));
        $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'), 'error', 'jerror');

        return false;
      }

      // Attempt to check the row in.
      if(!$table->checkIn($pk))
      {
        $this->component->setError($table->getError());
        $this->component->addLog($table->getError(), 'error', 'jerror');

        return false;
      }
    }

    return true;
  }

  /**
   * Method to initialize member variables used by batch methods
   * and other methods like saveorder()
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function initBatch()
  {
    // Store type (to avoid conflics with UCM)
    $type = $this->type;

    parent::initBatch();

    // Reset stored type (to avoid conflics with UCM)
    $this->type = $type;

    // Get current user
    $this->user = $this->component->getMVCFactory()->getIdentity();
  }

  /**
   * Method to check the validity of the category ID for batch copy and move
   *
   * @param   integer  $categoryId  The category ID to check
   *
   * @return  boolean
   *
   * @since   4.0.0
   */
  protected function checkCategoryId($categoryId)
  {
    // Check that the category exists
    if($categoryId)
    {
      $categoryTable = $this->component->getMVCFactory()->createTable('Category', 'administrator');

      if(!$categoryTable->load($categoryId))
      {
        if($error = $categoryTable->getError())
        {
          // Fatal error
          $this->component->setError($error);
          $this->component->addLog($error, 'error', 'jerror');

          return false;
        }
        else
        {
          $this->component->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));
          $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'), 'error', 'jerror');

          return false;
        }
      }
    }

    if(empty($categoryId))
    {
      $this->component->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'));
      $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_BATCH_MOVE_CATEGORY_NOT_FOUND'), 'error', 'jerror');

      return false;
    }

    // Check that the user has create permission for the component
    if(!$this->getAcl()->checkacl('create', 'category', 0, $categoryId, true))
    {
      $this->component->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));
      $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'), 'error', 'jerror');

      return false;
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

  /**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function prepareTable($table)
	{
		return;
	}

  /**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm($this->typeAlias, $this->type, array('control' => 'jform', 'load_data' => $loadData));

		if(empty($form))
		{
			return false;
		}

    // On edit, we get ID from state, but on save, we use data from input
		$id = (int) $this->getState($this->type.'.id', $this->app->getInput()->getInt('id', null));

		// Object uses for checking edit state permission of item
		$record = new \stdClass();
		$record->id = $id;

    // Modify the form based on Edit State access controls.
		if(!$this->canEditState($record))
		{
			// Disable fields for display.
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an article you can edit.
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('published', 'filter', 'unset');
		}

    // Don't allow to change the created_user_id user if not allowed to access com_users.
    if(!$this->user->authorise('core.manage', 'com_users'))
    {
      $form->setFieldAttribute('created_by', 'filter', 'unset');
    }

		return $form;
	}

  /**
	 * Allows preprocessing of the JForm object.
	 *
	 * @param   Form    $form   The form object
	 * @param   array   $data   The data to be merged into the form object
	 * @param   string  $group  The plugin group to be executed
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function preprocessForm(Form $form, $data, $group = 'joomgallery')
	{
		if(!Multilanguage::isEnabled())
		{
			$form->setFieldAttribute('language', 'type', 'hidden');
			$form->setFieldAttribute('language', 'default', '*');
		}

		parent::preprocessForm($form, $data, $group);
	}

  /**
	 * Set or update associations.
	 *
   * @param   Table  &$table        Table object (with reference)
	 * @param   array  $associations  List of associated ids
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  protected function createAssociations(Table &$table, array $associations)
  {
    $key = $table->getKeyName();

    // Unset any invalid associations
    $associations = ArrayHelper::toInteger($associations);

    // Unset any invalid associations
    foreach($associations as $tag => $id)
    {
      if(!$id)
      {
        unset($associations[$tag]);
      }
    }

    // Show a warning if the item isn't assigned to a language but we have associations.
    if($associations && $table->language === '*')
    {
      Factory::getApplication()->enqueueMessage(Text::_(strtoupper($this->option) . '_ERROR_ALL_LANGUAGE_ASSOCIATED'),	'warning');
      $this->component->addLog(Text::_(strtoupper($this->option) . '_ERROR_ALL_LANGUAGE_ASSOCIATED'), 'warning', 'jerror');
    }

    // Get associationskey for edited item
    $db    = $this->getDatabase();
    $id    = (int) $table->$key;
    $query = $db->getQuery(true)
      ->select($db->quoteName('key'))
      ->from($db->quoteName('#__associations'))
      ->where($db->quoteName('context') . ' = :context')
      ->where($db->quoteName('id') . ' = :id')
      ->bind(':context', $this->associationsContext)
      ->bind(':id', $id, ParameterType::INTEGER);
    $db->setQuery($query);
    $oldKey = $db->loadResult();

    if($associations || $oldKey !== null)
    {
      // Deleting old associations for the associated items
      $query = $db->getQuery(true)
        ->delete($db->quoteName('#__associations'))
        ->where($db->quoteName('context') . ' = :context')
        ->bind(':context', $this->associationsContext);

      $where = [];

      if($associations)
      {
        $where[] = $db->quoteName('id') . ' IN (' . implode(',', $query->bindArray(array_values($associations))) . ')';
      }

      if($oldKey !== null)
      {
        $where[] = $db->quoteName('key') . ' = :oldKey';
        $query->bind(':oldKey', $oldKey);
      }

      $query->extendWhere('AND', $where, 'OR');
      $db->setQuery($query);
      $db->execute();
    }

    // Adding self to the association
    if($table->language !== '*')
    {
      $associations[$table->language] = (int) $table->$key;
    }

    if(\count($associations) > 1)
    {
      // Adding new association for these items
      $key   = md5(json_encode($associations));
      $query = $db->getQuery(true)
        ->insert($db->quoteName('#__associations'))
        ->columns(
          [
            $db->quoteName('id'),
            $db->quoteName('context'),
            $db->quoteName('key'),
          ]
        );

      foreach($associations as $id)
      {
        $query->values(
          implode(
            ',',
            $query->bindArray(
              [$id, $this->associationsContext, $key],
              [ParameterType::INTEGER, ParameterType::STRING, ParameterType::STRING]
            )
          )
        );
      }

      $db->setQuery($query);
      $db->execute();
    }
  }

  /**
   * Clean the cache
   *
   * @param   string  $group  The cache group
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function cleanCache($group = null)
  {
    return parent::cleanCache($this->typeAlias);
  }
  
  /**
   * Method to test whether a record can be deleted.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to delete the record. Defaults to the permission for the component.
   *
   * @since   4.0.0
   */
  protected function canDelete($record)
  {
    $id         = $record->id;
    $parent_id  = 0;
    $use_parent = false;
    $type       = $this->type;

    if(\is_object($this->type))
    {
      list($option, $type) = \explode('.', $this->type->type_alias, 2);
    }

    if(\in_array($type, $this->getAcl()->get('parent_dependent_types')) && isset($record->catid))
    {
      // We have a parent dependent content type, so parent_id is needed
      $parent_id = $record->catid;
      $use_parent = true;
    }

    return $this->getAcl()->checkACL('delete', $type, $id, $parent_id, $use_parent);
  }

  /**
   * Method to test whether a record can have its state changed.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to change the state of the record. Defaults to the permission for the component.
   *
   * @since   4.0.0
   */
  protected function canEditState($record)
  {
    $id         = $record->id;
    $parent_id  = 0;
    $use_parent = false;
    $type       = $this->type;

    if(\is_object($this->type))
    {
      list($option, $type) = \explode('.', $this->type->type_alias, 2);
    }      

    if(\in_array($type, $this->getAcl()->get('parent_dependent_types')) && $record->id > 0)
    {
      // We have a parent dependent content type, so parent_id is needed
      $parent_id  = isset($record->catid) ? $record->catid : JoomHelper::getParent($type, $record->id);
      $use_parent = true;
    }

    return $this->getAcl()->checkACL('editstate', $type, $id, $parent_id, $use_parent);
  }

  /**
   * Method to load and return a table object.
   *
   * @param   string  $name    The name of the view
   * @param   string  $prefix  The class prefix. Optional.
   * @param   array   $config  Configuration settings to pass to Table::getInstance
   *
   * @return  Table|boolean  Table object or boolean false if failed
   *
   * @since   4.0.0
   */
  protected function _createTable($name, $prefix = 'Table', $config = [])
  {
    $table = parent::_createTable($name, $prefix, $config);

    if($table instanceof CurrentUserInterface)
    {
      $table->setCurrentUser($this->component->getMVCFactory()->getIdentity());
    }

    return $table;
  }

  /**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since   4.1.0
	 */
	public function get($property, $default = null)
	{
		if(isset($this->$property))
		{
			return $this->$property;
		}

		return $default;
	}
}
