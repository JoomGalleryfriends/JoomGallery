<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\User\UserFactoryInterface;

/**
 * Model to get an image record.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageModel extends JoomItemModel
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
		$toDelete = ['asset_id', 'params'];

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
}
