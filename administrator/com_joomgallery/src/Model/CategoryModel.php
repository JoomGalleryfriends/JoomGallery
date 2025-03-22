<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Language\Multilanguage;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Category model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'category';

  /**
   * True if a password is set
   *
   * @access  protected
   * @var     bool
   */
  protected $is_password = true;

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
		$form = $this->loadForm($this->typeAlias, 'category', array('control' => 'jform', 'load_data' => $loadData ));

    if(empty($form))
		{
			return false;
		}

    // On edit, we get ID from state, but on save, we use data from input
		$id = (int) $this->getState('category.id', $this->app->getInput()->getInt('id', 0));

		// Object uses for checking edit state permission of image
		$record = new \stdClass();
		$record->id = $id;

    // Apply filter to exclude child categories
    $children = $form->getFieldAttribute('parent_id', 'children', 'true');
    $children = \filter_var($children, FILTER_VALIDATE_BOOLEAN);
    if(!$children)
    {
      $form->setFieldAttribute('parent_id', 'exclude', $id);
    }

		// Apply filter for current category on thumbnail field
    $form->setFieldAttribute('thumbnail', 'categories', $id);

    // Disable remove password field if no password is set
    if(!$this->is_password)
    {
      $form->setFieldAttribute('rm_password', 'disabled', 'true');
      $form->setFieldAttribute('rm_password', 'filter', 'unset');
      $form->setFieldAttribute('rm_password', 'hidden', 'true');
      $form->setFieldAttribute('rm_password', 'class', 'hidden');
    }    

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
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$data = Factory::getApplication()->getUserState(_JOOM_OPTION.'.edit.category.data', array());

		if(empty($data))
		{
			if($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;

      // Support for password field
      if(\property_exists($data, 'password') && empty($data->password))
      {
        $this->is_password = false;
      }
      $data->password = '';

			// Support for multiple or not foreign key field: robots
			$array = array();

			foreach((array) $data->robots as $value)
			{
				if(!is_array($value))
				{
					$array[] = $value;
				}
			}
			if(!empty($array))
      {
			  $data->robots = $array;
			}
		}

		return $data;
	}

  /**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  Object|boolean Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{		
    if($this->item === null)
		{
			$this->item = false;

      if(empty($pk))
			{
				$pk = $this->getState('category.id');
			}

      if($this->item = parent::getItem($pk))
      {
        if(isset($this->item->params))
        {
          $this->item->params = json_encode($this->item->params);
        }
        
        // Do any processing on fields here if needed
      }
    }

    return $this->item;
	}

  /**
	 * Method to delete one or more categories.
	 *
	 * @param   array  &$pks  An array of record primary keys.
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since   4.0.0
	 */
	public function delete(&$pks)
	{
		$pks   = ArrayHelper::toInteger((array) $pks);
		$table = $this->getTable();

    // Check if the deletion is forced
    $force_delete = Factory::getApplication()->input->get('del_force', false, 'BOOL');

		// Include the plugins for the delete events.
		PluginHelper::importPlugin($this->events_map['delete']);

		// Iterate the items to delete each one.
		foreach($pks as $i => $pk)
		{
			if($table->load($pk))
			{
				if($this->canDelete($table)) 
				{
					$context = $this->option . '.' . $this->name;

					// Trigger the before delete event.
					$result = Factory::getApplication()->triggerEvent($this->event_before_delete, array($context, $table));

					if(\in_array(false, $result, true))
					{
						$this->setError($table->getError());
						$this->component->addLog($table->getError(), 'error', 'jerror');

						return false;
					}

					// Create file manager service
					$manager = JoomHelper::getService('FileManager', array($table->id));

          // Delete corresponding folders
					if(!$manager->deleteCategory($table, $force_delete))
					{
						$this->setError($this->component->getDebug(true));
						$this->component->addLog($this->component->getDebug(true) . '; Category ID: ' . $pk, 'error', 'jerror');

						return false;
					}

					// Multilanguage: if associated, delete the item in the _associations table
					if($this->associationsContext && Associations::isEnabled())
					{
						$db = $this->getDbo();
						$query = $db->getQuery(true)
							->select(
								[
									'COUNT(*) AS ' . $db->quoteName('count'),
									$db->quoteName('as1.key'),
								]
							)
							->from($db->quoteName('#__associations', 'as1'))
							->join('LEFT', $db->quoteName('#__associations', 'as2'), $db->quoteName('as1.key') . ' = ' . $db->quoteName('as2.key'))
							->where(
								[
									$db->quoteName('as1.context') . ' = :context',
									$db->quoteName('as1.id') . ' = :pk',
								]
							)
							->bind(':context', $this->associationsContext)
							->bind(':pk', $pk, ParameterType::INTEGER)
							->group($db->quoteName('as1.key'));

						$db->setQuery($query);
						$row = $db->loadAssoc();

						if(!empty($row['count']))
						{
							$query = $db->getQuery(true)
								->delete($db->quoteName('#__associations'))
								->where(
									[
										$db->quoteName('context') . ' = :context',
										$db->quoteName('key') . ' = :key',
									]
								)
								->bind(':context', $this->associationsContext)
								->bind(':key', $row['key']);

							if($row['count'] > 2)
							{
								$query->where($db->quoteName('id') . ' = :pk')
									->bind(':pk', $pk, ParameterType::INTEGER);
							}

							$db->setQuery($query);
							$db->execute();
						}
					}

					if(!$table->delete($pk))
					{
						$this->setError($table->getError());
						$this->component->addLog($table->getError(), 'error', 'jerror');

						return false;
					}

					// Trigger the after event.
					Factory::getApplication()->triggerEvent($this->event_after_delete, array($context, $table));
				}
				else
				{
					// Prune items that you can't change.
					unset($pks[$i]);
					$error = $this->getError();

					if($error)
					{
						$this->component->addLog($error, 'warning', 'jerror');

						return false;
					}
					else
					{
						$this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'), 'warning', 'jerror');

						return false;
					}
				}
			}
			else
			{
				$this->setError($table->getError());
				$this->component->addLog($table->getError(), 'error', 'jerror');

				return false;
			}
		}

		// Output messages
		if(\count($this->component->getWarning()) > 1)
		{
			$this->component->printWarning();
		}

		// Output debug data
		if(\count($this->component->getDebug()) > 1)
		{
			$this->component->printDebug();
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}
	
  /**
   * Method to save the form data.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function save($data)
  { 
    $table          = $this->getTable();
    $context        = $this->option . '.' . $this->name;
    $app            = Factory::getApplication();
    $isNew          = true;
    $catMoved       = false;
    $isCopy         = false;
    $aliasChanged   = false;
    $hasChildren    = false;
    $hasImages      = false;
    $adapterChanged = false;

    $key = $table->getKeyName();
    $pk  = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
    
    // Are we going to copy the image record?
    if($app->input->get('task') == 'save2copy')
		{
			$isCopy = true;
		}

    // Create tags
    if(\array_key_exists('tags', $data) && \is_array($data['tags']) && \count($data['tags']) > 0)
    {
      $table->newTags = $data['tags'];
    }

    // Password
    if(isset($data['rm_password']) && $data['rm_password'] == true)
    {
      $table->rm_pw = true;
    }
    elseif(isset($data['password']) && !empty($data['password']))
    {
      $table->new_pw = $data['password'];
    }
    unset($data['rm_password']);
    unset($data['password']);

    // Change language to 'All' if multilangugae is not enabled
    if (!Multilanguage::isEnabled())
    {
      $data['language'] = '*';
    }

    // Include the plugins for the save events.
    PluginHelper::importPlugin($this->events_map['save']);

    // Allow an exception to be thrown.
    try
    {
        // Load the row if saving an existing record.
        if($pk > 0)
        {
          $table->load($pk);
          $isNew = false;

          // Check if the parent category was changed
          if($table->parent_id != $data['parent_id'])
          {
            $catMoved = true;
          }

          // Check if the alias was changed
          if($table->alias != $data['alias'])
          {
            $aliasChanged = true;
          }

          // Check if the state was changed
          if($table->published != $data['published'])
          {
            if(!$this->getAcl()->checkACL('core.edit.state', _JOOM_OPTION.'.category.'.$table->id))
            {
              // We are not allowed to change the published state
              $this->component->addWarning(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
              $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'warning', 'jerror');
              $data['published'] = $table->published;
            }
          }
          
          // Check if category has subcategories (children)
          if($this->getChildren($pk))
          {
            $hasChildren = true;
          }

          // Check if category has images
          if($this->getNumImages($pk) != 0)
          {
            $hasImages = true;
          }

          // Check if filesystem adapter has changed
          $old_params = \json_decode($table->params);
          if($old_params->{'jg_filesystem'} != $data['params']['jg_filesystem'])
          {
            $adapterChanged = true;
          }
        }

        // Check that filesystem field content is allowed
        if($adapterChanged && $data['parent_id'] != 1)
        {
          // Only allowed in toplevel categories
          $this->setError(Text::_('COM_JOOMGALLERY_ERROR_FILESYSTEM_ONLY_TOP_LEVEL_CAT'));
          
          return false;
        }
        elseif($adapterChanged && ($hasChildren || $hasImages))
        {
          // Only allowed if there are no images and no subcategories
          $this->setError(Text::_('COM_JOOMGALLERY_ERROR_FILESYSTEM_ONLY_EMPTY_CAT'));
          
          return false;
        }

        // Handle folders if category was changed
        if(!$isNew && ($catMoved || $aliasChanged))
        {
          // Douplicate old data
          $old_table = clone $table;
        }

        if($catMoved || $isNew)
        {
          $table->setLocation($data['parent_id'], 'last-child');
        }
        elseif($aliasChanged)
        {
          // Make sure paths get updated correctly when alias is changed
          $table->setLocation($data['parent_id'], '');
        }

        // Create file manager service
				$manager = JoomHelper::getService('FileManager', array($data['parent_id']));

        // Bind the data.
        if(!$table->bind($data))
        {
          $this->setError($table->getError());

          return false;
        }

        // Prepare the row for saving
        $this->prepareTable($table);

        // Check the data.
        if(!$table->check())
        {
          $this->setError($table->getError());

          return false;
        }

        // Check that there are rules set for new categories
        // It can happen for users without 'core.admin' permission that there are no rules in the request
        if($isNew && empty($table->getRules('all')))
        {
          $form = $this->getForm();
          $table->setEmptyRules($form);
        }

        // Trigger the before save event.
        $result = $app->triggerEvent($this->event_before_save, array($context, $table, $isNew, $data));

        // Stop storing data if one of the plugins returns false
        if(\in_array(false, $result, true))
        {
          $this->setError($table->getError());
          $this->component->addLog($table->getError(), 'error', 'jerror');

          return false;
        }

        // Filesystem changes
			  $filesystem_success = true;

        if( (!$isNew && $catMoved) || (!$isNew && $aliasChanged) )
        {
          // Action will be performed after storing
        }
        else
        {
          // Create folders
          $filesystem_success = $manager->createCategory($table->alias, $table->parent_id);
        }

        // Dont store the table if filesystem changes was not successful
        if(!$filesystem_success)
        {
          $this->component->addError(Text::_('COM_JOOMGALLERY_ERROR_SAVE_FILESYSTEM_ERROR'));

          return false;
        }

        // Store the data.
        if(!$table->store())
        {
          $this->setError($table->getError());
          $this->component->addLog($table->getError(), 'error', 'jerror');

          return false;
        }

        // Handle folders if parent category was changed
        if(!$isNew && $catMoved)
			  {
          // Get path back from old location temporarily
          $table->setPathWithLocation(true);

          // Move folder (including files and subfolders)
          if(!$manager->moveCategory($old_table, $table->parent_id))
          {
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_MOVE_CATEGORY', $manager->paths['src'], $manager->paths['dest']));
            $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_MOVE_CATEGORY', $manager->paths['src'], $manager->paths['dest']), 'error', 'jerror');
            return false;
          }

          // Reset path
          $table->setPathWithLocation(false);

          // Adjust path of subcategory records
          if(!$this->fixChildrenPath($table, $old_table))
          {
            return false;
          }
        }
        // Handle folders if alias was changed
        elseif(!$isNew && $aliasChanged)
        {
          // Get path back from old location temporarily
          $table->setPathWithLocation(true);

          // Rename folder
          if(!$manager->renameCategory($old_table, $table->alias))
          {
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_ERROR_RENAME_CATEGORY', $manager->paths['src'], $manager->paths['dest']));
            $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_RENAME_CATEGORY', $manager->paths['src'], $manager->paths['dest']), 'error', 'jerror');
            return false;
          }

          // Reset path
          $table->setPathWithLocation(false);

          // Adjust path of subcategory records
          if(!$this->fixChildrenPath($table, $old_table))
          {
            return false;
          }
        }
        else
        {
          // Action already perfromed
        }

        // Handle folders if record gets copied
        if($isNew && $isCopy)
        {
          // Get source image id
          $source_id = $app->input->get('origin_id', false, 'INT');

          // Copy folder (including files and subfolders)
          //$manager->copyCategory($source_id, $table);
        }

        // Clean the cache.
        $this->cleanCache();

        // Trigger the after save event.
        $app->triggerEvent($this->event_after_save, array($context, $table, $isNew, $data));
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }

    // Output warning messages
		if(\count($this->component->getWarning()) > 0)
		{
			$this->component->printWarning();
		}

		// Output debug data
		if(\count($this->component->getDebug()) > 0)
		{
			$this->component->printDebug();
    }

    // Set state
    if(isset($table->$key))
    {
      $this->setState($this->getName() . '.id', $table->$key);
    }

    $this->setState($this->getName() . '.new', $isNew);

    // Create/update associations
    if($this->associationsContext && Associations::isEnabled() && !empty($data['associations']))
    {
      $this->createAssociations($table, $data['associations']);
    }

    // Redirect to associations
    if($app->input->get('task') == 'editAssociations')
    {
      return $this->redirectToAssociations($data);
    }

    return true;
  }

	/**
	* Method rebuild the entire nested set tree.
	* @return  boolean  False on failure or error, true otherwise.
	* @since   4.0.0
	*/
	public function rebuild()
	{
		$table = $this->getTable();

		if(!$table->rebuild())
		{
			$this->setError($table->getError());
			$this->component->addLog($table->getError(), 'error', 'jerror');

			return false;
		}
    
		$this->cleanCache();

		return true;
	}

  /**
	 * Method to save the reordered nested set tree.
	 * First we save the new order values in the lft values of the changed ids.
	 * Then we invoke the table rebuild to implement the new ordering.
	 *
	 * @param   array    $idArray   An array of primary key ids.
	 * @param   integer  $lftArray  The lft value
	 *
	 * @return  boolean  False on failure or error, True otherwise
	 *
	 * @since   4.0.0
	 */
	public function saveorder($idArray = null, $lftArray = null)
	{
		// Get an instance of the table object.
		$table = $this->getTable();

		if(!$table->saveorder($idArray, $lftArray))
		{
			$this->setError($table->getError());
			$this->component->addLog($table->getError(), 'error', 'jerror');

			return false;
		}

		// Clear the cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to duplicate an Category
	 *
	 * @param   array  &$pks  An array of primary key IDs.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
	public function duplicate(&$pks)
	{
		$app  = Factory::getApplication();
		$user = Factory::getUser();
    $task = $app->input->get('task');

		// Access checks.
		if(!$user->authorise('core.create', _JOOM_OPTION))
		{
			throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

    // Set task to be save2copy
    $app->input->set('task', 'save2copy');

		$table = $this->getTable();

		foreach($pks as $pk)
		{
      if($table->load($pk, true))
      {
        // Reset the id to create a new record.
        $table->id = 0;

        // Remove unnecessary fields
        unset($table->form);
        $table->level            = null;
        $table->lft              = null;
        $table->rgt              = null;
        $table->alias            = null;
        $table->asset_id         = null;
        $table->published        = null;
        $table->in_hidden        = null;
        $table->created_time     = null;
        $table->created_by       = null;
        $table->modified_by      = null;
        $table->modified_time    = null;
        $table->checked_out      = null;
        $table->checked_out_time = null;

        // Export data from table
        $data = (array) $table->getFieldsValues();

        // Set the id of the origin category
        $app->input->set('origin_id', $pk);

        // Save the copy
        $this->save($data);
      }
      else
      {
        throw new \Exception($table->getError());
      }			
		}

    // Reset official task
    $app->input->set('task', $task);

		// Clean cache
		$this->cleanCache();

		return true;
	}

  /**
	 * Method to adjust path of child categories based on new path
	 *
	 * @param   Table    $table      Table object of the current category.
	 * @param   Table    $old_table  Old table object of the current category.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  Exception
	 */
  public function fixChildrenPath($table, $old_table)
  {
    if(\is_null($table) || empty($table->id))
    {
      $this->component->addLog('To fix child category paths, table has to be loaded.', 'error', 'jerror');
      throw new Exception('To fix child category paths, table has to be loaded.');
    }

    // Get a list of children ids
    $children = $this->getChildren($table->id, false, true);

    foreach($children as $key => $cat)
    {
      $child_table = $this->getTable();
      $child_table->load($cat['id']);

      // Change path
      $pos = \strpos($child_table->path, $old_table->path);
      if($pos !== false) 
      {
        $child_table->path = \substr_replace($child_table->path, $table->path, $pos, \strlen($old_table->path));
      }

      // Change static path
      if($this->component->getConfig()->get('jg_compatibility_mode', 0))
      {
        $static_pos = \strpos($child_table->static_path, $old_table->static_path);
        if($static_pos !== false)
        {
          $child_table->static_path = \substr_replace($child_table->static_path, $table->static_path, $static_pos, \strlen($old_table->static_path));
        }
      }

      // Store the data.
      if(!$child_table->store())
      {
        $this->setError('Child category (ID='.$cat['id'].') tells: ' . $child_table->getError());
        $this->component->addLog('Child category (ID='.$cat['id'].') tells: ' . $child_table->getError(), 'error', 'jerror');

        return false;
      }
    }

    return true;
  }

  /**
   * Get children categories.
   * 
   * @param   integer  $pk        The id of the primary key.
   * @param   bool     $self      Include current node id (default: false)
   * @param   bool     $setError  True to set an Error if no children found (default: false)
   *
   * @return  mixed    An array of categories or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getChildren($pk = null, $self = false, $setError=false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');

      return false;
    }

    // add root category
    $root = false;
    if($pk == 1 && $self)
    {
      $root = true;
    }

    $children = $table->getNodeTree('children', $self, $root);
    if(!$children)
    {
      if($setError)
      {
        $this->setError($table->getError());
        $this->component->addLog($table->getError(), 'error', 'jerror');
      }

      return false;
    }
    
    return $children;
  }

  /**
   * Get parent categories.
   * 
   * @param   integer  $pk        The id of the primary key.
   * @param   bool     $self      Include current node id (default: false)
   * @param   bool     $root      Include root node (default: false)
   * @param   bool     $setError  True to set an Error if no parents found (default: false)
   *
   * @return  mixed    An array of categories or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getParents($pk = null, $self = false, $root = false, $setError=false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');

      return false;
    }

    $parents = $table->getNodeTree('parents', $self, $root);
    if(!$parents)
    {
      if($setError)
      {
        $this->setError($table->getError());
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');
      }

      return false;
    }
    
    return $parents;
  }

  /**
   * Get category tree
   * 
   * @param   integer  $pk        The id of the primary key.
   * @param   bool     $self      Include current node id (default: false)
   * @param   bool     $root      Include root node (default: false)
   * @param   bool     $setError  True to set an Error if tree is empty (default: false)
   *
   * @return  mixed    An array of categories or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getTree($pk = null, $root = false, $setError=false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');

      return false;
    }

    $tree = $table->getNodeTree('cpl', true, $root);
    if(!$tree)
    {
      if($setError)
      {
        $this->setError($table->getError());
        $this->component->addLog($table->getError(), 'error', 'jerror');
      }

      return false;
    }
    
    return $tree;
  }

  /**
   * Get direct left or right sibling (adjacent) of the category.
   * 
   * @param   integer  $pk        The id of the primary key.
   * @param   string   $side      Left or right side ribling.
   * @param   bool     $setError  True to set an Error if no sibling found (default: false) 
   *
   * @return  mixed    List of sibling or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getSibling($pk, $side, $setError=false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = intval($this->item->id);
    }

    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');

      return false;
    }
    
    $sibling = $table->getSibling($side, true);

    if(!$sibling)
    {
      if($setError)
      {
        $this->setError($table->getError());
        $this->component->addLog($table->getError(), 'error', 'jerror');
      }

      return false;
    }
    
    return $sibling;
  }

  /**
   * Get all left and/or right siblings (adjacent) of the category.
   * 
   * @param   integer  $pk        The id of the primary key.
   * @param   string   $side      Left, right or both sides siblings.
   * @param   bool     $setError  True to set an Error if no siblings found (default: false)
   *
   * @return  mixed    List of siblings or false if an error occurs.
   *
   * @since   4.0.0
   */
  public function getSiblings($pk, $side, $setError=false)
  {
    $parent_id = null;
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk        = intval($this->item->id);
      $parent_id = intval($this->item->parent_id);
    }

    // Load category table
    $table = $this->getTable();
    if($table->load($pk) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');

      return false;
    }

    if(\is_null($parent_id))
    {
      $parent_id = intval($table->parent_id);
    }

    // Load parent table
    $ptable = $this->getTable();
    if($ptable->load($parent_id) === false)
    {
      $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $parent_id));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CATEGORY_NOT_EXIST', $pk), 'error', 'jerror');

      return false;
    }
    
    $sibling = $table->getSibling($side, false, $ptable);

    if(!$sibling)
    {
      if($setError)
      {
        $this->setError($table->getError());
        $this->component->addLog($table->getError(), 'error', 'jerror');
      }

      return false;
    }
    
    return $sibling;
  }

  /**
   * Get the number of images in this category
   * 
   * @param   integer  $pk        The id of the primary key.
   * @param   bool     $setError  True to set an Error if no images are found (default: false)
   *
   * @return  integer  Number of images in this category
   *
   * @since   4.0.0
   */
  public function getNumImages($pk, $setError=false)
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = \intval($this->item->id);
    }

    // Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

    $query->select('COUNT(*)')
          ->from($db->quoteName(_JOOM_TABLE_IMAGES))
          ->where($db->quoteName('catid') . " = " . $db->quote($pk));

    try
    {
      $db->setQuery($query);
      $count = \intval($db->loadResult());
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
    }

    if(!$count && $setError)
    {
      $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_IMAGES_FOUND'));
    }

    return $count;
  }
}
