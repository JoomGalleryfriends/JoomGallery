<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\User\UserFactoryInterface;

/**
 * Model to get an image record.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

	/**
   * Category model
   *
   * @access  protected
   * @var     object
   */
  protected $category = null;

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws \Exception
	 */
	protected function populateState()
	{
		// Check published state
		if((!$this->getAcl()->checkACL('core.edit.state', 'com_joomgallery')) && (!$this->getAcl()->checkACL('core.edit', 'com_joomgallery')))
		{
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}

		// Load state from the request userState on edit or from the passed variable on default
		$id = $this->app->input->getInt('id', null);
		if($id)
		{
			$this->app->setUserState('com_joomgallery.edit.image.id', $id);
		}
		else
		{
			$id = (int) $this->app->getUserState('com_joomgallery.edit.image.id', null);
		}

		if(\is_null($id))
		{
			throw new \Exception('No ID provided to the model!', 500);
		}

		$this->setState('image.id', $id);

		$this->loadComponentParams($id);
	}

	/**
	 * Method to get an object.
	 *
	 * @param   integer  $id The id of the object to get.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @throws \Exception
	 */
	public function getItem($id = null)
	{
		if($this->item === null || $this->item->id != $id)
		{
			$this->item = false;

			if(empty($id))
			{
				$id = $this->getState('image.id');
			}

			// Attempt to load the item
			$adminModel = $this->component->getMVCFactory()->createModel('image', 'administrator');
			$this->item = $adminModel->getItem($id);

			if(empty($this->item))
			{
				throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 404);
			}
		}

		if(isset($this->item->catid) && $this->item->catid != '')
		{
			$this->item->cattitle = $this->getCategoryName($this->item->catid);
		}

		// Add created by name
		if(isset($this->item->created_by))
		{
			$this->item->created_by_name = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->item->created_by)->name;
		}

		// Add modified by name
		if(isset($this->item->modified_by))
		{
			$this->item->modified_by_name = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->item->modified_by)->name;
		}

    // Adjust tags
    if(isset($this->item->tags))
		{
      foreach($this->item->tags as $key => $tag)
      {
        if(\is_object($tag) && $tag->published < 1)
        {
          // Remove unpublished items
          unset($this->item->tags->{$key});
        }
        elseif(\is_object($tag) && !$this->component->getAccess()->checkViewLevel($tag->access))
        {
          // Remove items that are not viewable for current user
          unset($this->item->tags->{$key});
        }
      }
    }

    // Delete unnecessary properties
		$toDelete = array('asset_id', 'params');
		foreach($toDelete as $property)
		{
			unset($this->item->{$property});
		}

		return $this->item;
	}

  /**
   * Increment the hit counter for the article.
   *
   * @param   integer  $id  Optional primary key of the article to increment.
   *
   * @return  boolean  True if successful; false otherwise and internal error set.
   */
  public function hit($id = 0)
  {
    $id = (!empty($id)) ? $id : (int) $this->getState('image.id');

    $table = $this->getTable();
    $table->hit($id);

    return true;
  }

  /**
	 * Method to load the title of a category
	 *
	 * @param   int  $catid  Category id
	 *
	 * @return  string|bool  The category title on success, false otherwise
	 */
  protected function getCategoryName(int $catid)
  {
		if(!$catid && $this->item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

		// Get id
		$catid = $catid ? $catid : $this->item->catid;

		if(!$this->category)
		{
			// Create model
			$this->category = $this->component->getMVCFactory()->createModel('category', 'site');		
		}

		// Load category
		$cat_item = $this->category->getItem($catid);

		return $cat_item->title;
  }

	/**
	 * Method to check if any parent category is protected
	 *
	 * @param   int  $catid  Category id
	 *
	 * @return  bool  True if categories are protected, false otherwise
	 * 
	 * @throws \Exception
	 */
	public function getCategoryProtected(int $catid = 0)
	{
		if(!$catid && $this->item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

		if(!isset($this->item->protectedParents))
		{
			// Get id
			$catid = $catid ? $catid : $this->item->catid;

			if(!$this->category)
			{
				// Create model
				$this->category = $this->component->getMVCFactory()->createModel('category', 'site');
			}

			// Load category
			$this->category->getItem($catid);
			
			$this->item->protectedParents = $this->category->getProtectedParents();
		}

		return !empty($this->item->protectedParents);
	}

	/**
	 * Method to check if all parent categories are published
	 *
	 * @param   int  $catid  Category id
	 *
	 * @return  bool  True if all categories are published, false otherwise
	 * 
	 * @throws \Exception
	 */
	public function getCategoryPublished(int $catid = 0, bool $approved = false)
	{
		if(!$catid && $this->item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

		if(!isset($this->item->unpublishedParents))
		{
			// Get id
			$catid = $catid ? $catid : $this->item->catid;

			if(!$this->category)
			{
				// Create model
				$this->category = $this->component->getMVCFactory()->createModel('category', 'site');
			}

			// Load category
			$this->category->getItem($catid);

			$this->item->unpublishedParents = $this->category->getUnpublishedParents(null, $approved);
		}

		return empty($this->item->unpublishedParents);
	}

  /**
	 * Method to check if all parent categories are accessible (view levels)
	 *
	 * @param   int  $catid  Category id
	 *
	 * @return  bool  True if all categories are accessible, false otherwise
	 * 
	 * @throws \Exception
	 */
	public function getCategoryAccess(int $catid = 0)
	{
		if(!$catid && $this->item === null)
		{
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

		if(!isset($this->item->accessibleParents))
		{
			// Get id
			$catid = $catid ? $catid : $this->item->catid;

			if(!$this->category)
			{
				// Create model
				$this->category = $this->component->getMVCFactory()->createModel('category', 'site');
			}

			// Load category
			$this->category->getItem($catid);

			$this->item->accessibleParents = $this->category->getAccessibleParents();
		}

		return empty($this->item->accessibleParents);
	}

  /**
   * Method to get the record form.
   *
   * @param   array    $data      An optional array of data for the form to interogate.
   * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  Form|boolean  A \JForm object on success, false on failure
   *
   * @since   4.0.0
   */
  public function getForm($data = array(), $loadData = true)
  {
    // Get the form.
    $form = $this->loadForm($this->typeAlias, 'image',	array('control' => 'jform',	'load_data' => $loadData));

    if(empty($form))
    {
      return false;
    }

    // On edit, we get ID from state, but on save, we use data from input
    $id = (int) $this->getState('image.id', $this->app->getInput()->getInt('id', null));

    // Object uses for checking edit state permission of image
    $record = new \stdClass();
    $record->id = $id;

    // Modify the form based on Edit State access controls.
    if(!$this->canEditState($record))
    {
      // Disable fields for display.
      $form->setFieldAttribute('featured', 'disabled', 'true');
      $form->setFieldAttribute('ordering', 'disabled', 'true');
      $form->setFieldAttribute('published', 'disabled', 'true');

      // Disable fields while saving.
      // The controller has already verified this is an article you can edit.
      $form->setFieldAttribute('featured', 'filter', 'unset');
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
    $table        = $this->getTable();
    $context      = $this->option . '.' . $this->name;
    $app          = Factory::getApplication();
    $imgUploaded  = false;
    $catMoved     = false;
    $isNew        = true;
    $isCopy       = false;
    $isAjax       = false;
    $aliasChanged = false;

    $key = $table->getKeyName();
    $pk  = (isset($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');

    // Are we going to copy the image record?
    if(\strpos($app->input->get('task'), 'save2copy') !== false)
    {
      $isCopy = true;
    }

    // Are we going to save image in an ajax request?
    if(\strpos($app->input->get('task'), 'ajaxsave') !== false)
    {
      $isAjax = true;
    }

    // Change language to 'All' if multilangugae is not enabled
    if (!Multilanguage::isEnabled())
    {
      $data['language'] = '*';
    }

    // Include the plugins for the save events.
    PluginHelper::importPlugin($this->events_map['save']);

    // Record editing and image creation
    try
    {
      // Load the row if saving an existing record.
      if($pk > 0)
      {
        $table->load($pk);
        $isNew = false;

        // Check if the category was changed
        if($table->catid != $data['catid'])
        {
          $catMoved = true;
        }

        // Check if the alias was changed
        if($table->alias != $data['alias'])
        {
          $aliasChanged = true;
          $old_alias    = $table->alias;
        }

        // Check if the state was changed
        if($table->published != $data['published'])
        {
          if(!$this->getAcl()->checkACL('core.edit.state', _JOOM_OPTION.'.image.'.$table->id, $table->id, $table->catid))
          {
            // We are not allowed to change the published state
            $this->component->addWarning(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
            $data['published'] = $table->published;
          }
        }
      }

      // Save form data in session
      $app->setUserState(_JOOM_OPTION.'.image.upload', $data);

      // Detect uploader service
      $upload_service  = 'html';
      if(isset($data['uploader']) && !empty($data['uploader']))
      {
        $upload_service = $data['uploader'];
      }

      // Detect multiple upload service
      $upload_multiple  = false;
      if(isset($data['multiple']) && !empty($data['multiple']))
      {
        $upload_multiple = \boolval($data['multiple']);
      }

      // Create uploader service
      $uploader = JoomHelper::getService('uploader', array($upload_service, $upload_multiple, $isAjax));

      // Detect uploaded file
      $imgUploaded = $uploader->isImgUploaded($data);

      // Retrieve image from request
      if($imgUploaded)
      {
        // Determine if we have to create new filename
        $createFilename = false;
        if($isNew || empty($data['filename']))
        {
          $createFilename = true;
        }

        // Retrieve image
        // (check upload, check user upload limit, create filename, onJoomBeforeSave)
        if(!$uploader->retrieveImage($data, $createFilename))
        {
          $this->setError($this->component->getDebug(true));
          $uploader->rollback();

          return false;
        }

        // Override data with image metadata
        if(!$uploader->overrideData($data))
        {
          $this->setError($this->component->getDebug(true));
          $uploader->rollback();

          return false;
        }
      }

      // Create file manager service
      $manager = JoomHelper::getService('FileManager', array($data['catid']));

      // Get source image id
      $source_id = $app->input->get('origin_id', false, 'INT');

      // Handle images if category was changed
      if(!$isNew && ($catMoved || $aliasChanged))
      {
        // Duplicate old data
        $old_table = clone $table;
      }

      // Bind data to table object
      if(!$table->bind($data))
      {
        $this->setError($table->getError());
        if($imgUploaded)
        {
          $uploader->rollback($table);
        }

        return false;
      }

      // Prepare the row for saving
      $this->prepareTable($table);

      // Check the data.
      if(!$table->check())
      {
        $this->setError($table->getError());
        if($imgUploaded)
        {
          $uploader->rollback($table);
        }

        return false;
      }

      // Cancel if file needs two filesystems to be saved
      // Can be deleted if filesystem service supports two filesystems
      $two_filesystems = [];
      if($isCopy)
      {
        // Get source img object
        $src_img = JoomHelper::getRecord('image', $source_id);

        if($src_img->filesystem !== $table->filesystem)
        {
          $two_filesystems = [$src_img->filesystem, $table->filesystem];
        }
}
      elseif($catMoved)
      {
        // Get filesystem for new category
        $tmp_config = new \Joomgallery\Component\Joomgallery\Administrator\Service\Config\DefaultConfig('com_joomgallery.category', $table->catid);

        if($tmp_config->get('jg_filesystem','local-images') !== $table->filesystem)
        {
          $two_filesystems = [$table->filesystem, $tmp_config->get('jg_filesystem','local-images')];
        }
      }
      if(!empty($two_filesystems))
      {
        $this->component->addError(Text::sprintf('COM_JOOMGALLERY_ERROR_IMAGE_SAVE_TWO_FILESYSTEMS', $two_filesystems[0], $two_filesystems[1]));
        if($imgUploaded)
        {
          $uploader->rollback($table);
        }

        return false;
      }

      // Handle images if record gets copied
      if($isNew && $isCopy && !$imgUploaded)
      {
        // Regenerate filename
        $table->filename = $manager->regenFilename($data['filename']);
      }

      // Handle images if alias has changed
      if(!$isNew && $aliasChanged && !$imgUploaded)
      {
        if(!$this->component->getConfig()->get('jg_useorigfilename'))
        {
          // Replace alias in filename if filename is title dependent
          $table->filename = \str_replace($old_alias, $table->alias, $table->filename);
        }
      }

      // Trigger the before save event.
      $result = $app->triggerEvent($this->event_before_save, array($context, $table, $isNew, $data));

      // Stop storing data if one of the plugins returns false
      if(\in_array(false, $result, true))
      {
        if($imgUploaded)
        {
          $uploader->rollback($table);
        }
        $this->setError($table->getError());

        return false;
      }

      // Filesystem changes
      $filesystem_success = true;

      // Handle images if category was changed
      if(!$isNew && $catMoved)
      {
        if($imgUploaded)
        {
          // Delete old images
          $filesystem_success = $manager->deleteImages($old_table);
        }
        else
        {
          // Move old images to new location
          $filesystem_success = $manager->moveImages($old_table, $table->catid);
        }
      }

      // Handle images if record gets copied
      if($isNew && $isCopy && !$imgUploaded)
      {
        // Copy Images
        $filesystem_success = $manager->copyImages($source_id, $table->catid, $table->filename);
      }

      // Handle images if alias has changed
      if(!$isNew && $aliasChanged && !$imgUploaded)
      {
        if($catMoved)
        {
          // modify old_table object to fit with new image location
          $old_table->catid = $table->catid;
        }

        // Rename files
        $filesystem_success = $manager->renameImages($old_table, $table->filename);
      }

      // Don't store the table if filesystem changes was not successful
      if(!$filesystem_success)
      {
        $this->component->addError(Text::_('COM_JOOMGALLERY_ERROR_SAVE_FILESYSTEM_ERROR'));
        if($imgUploaded)
        {
          $uploader->rollback($table);
        }

        return false;
      }

      // Store the data.
      if(!$table->store())
      {
        $this->setError($table->getError());
        if($imgUploaded)
        {
          $uploader->rollback($table);
        }

        return false;
      }

      // Create images
      if($imgUploaded)
      {
        // Create images
        // (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
        if(!$uploader->createImage($table))
        {
          $this->setError($this->component->getDebug(true));
          $uploader->rollback($table);

          if($isNew)
          {
            // Delete the already stored new record if image creation failed
            if(!$table->delete($table->$key))
            {
              $this->component->setError($table->getError());
            }
          }

          return false;
        }
      }

      // Handle ajax uploads
      if($isAjax)
      {
        $this->component->cache->set('imgObj', $table->getFieldsValues(array('form', 'imgmetadata', 'params', 'created_by', 'modified_by', 'checked_out')));
      }

      // All done. Clean created temp files
      $uploader->deleteTmp();

      // Clean the cache.
      $this->cleanCache();

      // Trigger the after save event.
      $app->triggerEvent($this->event_after_save, array($context, $table, $isNew, $data));
    }
    catch (\Exception $e)
    {
      if($imgUploaded)
      {
        $uploader->rollback($table);
      }
      $this->setError($e->getMessage());

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




} // class
