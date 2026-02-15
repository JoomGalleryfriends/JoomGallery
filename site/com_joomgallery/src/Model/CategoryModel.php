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

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Pagination\Pagination;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\CMS\User\UserHelper;

/**
 * Model to get a category record.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryModel extends JoomItemModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'category';

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

    $this->setState('category.id', $id);

    $this->loadComponentParams($id);
  }

  /**
   * Method to get the category item object.
   *
   * @param   integer  $id   The id of the object to get.
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
        $id = $this->getState('category.id');
      }

      // Attempt to load the item
      $adminModel = $this->component->getMVCFactory()->createModel('category', 'administrator');
      $this->item = $adminModel->getItem($id);

      if(empty($this->item))
      {
        throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 404);
      }
    }

    // Add created by name
    if(isset($this->item->created_by) && !isset($this->item->created_by_name))
    {
      $this->item->created_by_name = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->item->created_by)->name;
    }

    // Add modified by name
    if(isset($this->item->modified_by) && !isset($this->item->modified_by_name))
    {
      $this->item->modified_by_name = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->item->modified_by)->name;
    }

    // Delete unnecessary properties
    $toDelete = ['asset_id', 'password', 'params'];

    foreach($toDelete as $property)
    {
      unset($this->item->{$property});
    }

    return $this->item;
  }

  /**
   * Method to unlock a password protected category
   *
   * @param   int     $catid    ID of the category to unlock
   * @param   string  $password Password of the category to check
   *
   * @return  boolean True on success, false otherwise
   * @since   4.0.0
   *
   * @throws \Exception
   */
  public function unlock($catid, $password)
  {
    if($catid < 1)
    {
      throw new \Exception('No category provided.');
    }

    if(empty($password))
    {
      throw new \Exception('No password provided.');
    }

    // Create a new query object.
    $db        = $this->getDatabase();
        $query = $db->createQuery();

    $query->select('id, password')
          ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
          ->where('id = ' . (int) $catid);
    $db->setQuery($query);

    if(!$category = $db->loadObject())
    {
      throw new \Exception('Provided category not found. ' . $db->getErrorMsg());
    }

    if(!$category->password)
    {
      throw new \Exception('Category is not protected.');
    }

    if(!UserHelper::verifyPassword($password, $category->password))
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_CATEGORY_PASSWORD_INCORRECT'));
    }

    $categories = $this->app->getUserState(_JOOM_OPTION . 'unlockedCategories', [0]);
    $categories = array_unique(array_merge($categories, [$catid]));
    $this->app->setUserState(_JOOM_OPTION . 'unlockedCategories', $categories);

    $this->app->triggerEvent('onJoomAfterUnlockCat', [$catid]);

    return true;
  }

  /**
   * Method to get the parent category item object.
   *
   * @param   integer  $id   The id of the parent item to get.
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @throws \Exception
   */
  public function getParent($id = null)
  {
    if($id === null && $this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load parent category model
    $parentModel = $this->component->getMVCFactory()->createModel('category', 'site');
    $parentModel->getState();

    if($id)
    {
      return $parentModel->getItem($id);
    }

    return $parentModel->getItem($this->item->parent_id);
  }

  /**
   * Method to get the children categories.
   *
   * @return  array|false    Array of children on success, false on failure.
   *
   * @throws \Exception
   */
  public function getChildren()
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('categories', 'site');
    $listModel->getState();

    // Select fields to load
    $fields = ['id', 'alias', 'title', 'description', 'thumbnail'];
    $fields = $this->addColumnPrefix('a', $fields);

    // Apply preselected filters and fields selection for children
    $this->setChildrenModelState($listModel, $fields);

    // Get children
    $items = $listModel->getItems();

    if(!empty($listModel->getError()))
    {
      $this->setError($listModel->getError());
    }

    return $items;
  }

  /**
   * Method to get a \JPagination object for the children categories.
   *
   * @return  Pagination  A Pagination object for the children categories.
   */
  public function getChildrenPagination()
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('categories', 'administrator');
    $listModel->getState();

    // Apply preselected filters and fields selection for children
    $this->setChildrenModelState($listModel);

    // Get pagination
    $pagination = $listModel->getPagination();

    // Set additional query parameter to pagination
    $pagination->setAdditionalUrlParam('contenttype', 'category');

    return $listModel->getPagination();
  }

  /**
   * Get the filter form for the children categories.
   *
   * @param   array    $data      data
   * @param   boolean  $loadData  load current data
   *
   * @return  Form|null  The Joomla Form object or null if the form can't be found
   */
  public function getChildrenFilterForm($data = [], $loadData = true)
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('categories', 'site');
    $listModel->getState();

    // Apply preselected filters and fields selection for children
    $this->setChildrenModelState($listModel);

    return $listModel->getFilterForm($data, $loadData);
  }

  /**
   * Function to get the active filters for the children categories.
   *
   * @return  array  Associative array in the format: array('filter_published' => 0)
   */
  public function getChildrenActiveFilters()
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('categories', 'site');
    $listModel->getState();

    // Apply preselected filters and fields selection for children
    $this->setChildrenModelState($listModel);

    return $listModel->getActiveFilters();
  }

  /**
   * Method to get the images in this category.
   *
   * @return  array|false    Array of images on success, false on failure.
   *
   * @throws \Exception
   */
  public function getImages()
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load images list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'site');
    $listModel->getState();

    // Select fields to load
    $fields = ['id', 'alias', 'catid', 'title', 'description', 'filename', 'filesystem', 'author', 'date', 'hits', 'votes', 'votesum'];
    $fields = $this->addColumnPrefix('a', $fields);

    // Apply preselected filters and fields selection for images
    $this->setImagesModelState($listModel, $fields);

    // Get images
    $items = $listModel->getItems();

    if(!empty($listModel->getError()))
    {
      $this->setError($listModel->getError());
    }

    return $items;
  }

  /**
   * Method to get a \JPagination object for the images in this category.
   *
   * @return  Pagination  A Pagination object for the images in this category.
   */
  public function getImagesPagination()
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'site');
    $listModel->getState();

    // Apply preselected filters and fields selection for images
    $this->setImagesModelState($listModel);

    // Get pagination
    $pagination = $listModel->getPagination();

    // Set additional query parameter to pagination
    $pagination->setAdditionalUrlParam('contenttype', 'image');

    return $pagination;
  }

  /**
   * Get the filter form for the images in this category.
   *
   * @param   array    $data      data
   * @param   boolean  $loadData  load current data
   *
   * @return  Form|null  The \Form object or null if the form can't be found
   */
  public function getImagesFilterForm($data = [], $loadData = true)
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'site');
    $listModel->getState();

    // Apply preselected filters and fields selection for images
    $this->setImagesModelState($listModel);

    return $listModel->getFilterForm($data, $loadData);
  }

  /**
   * Function to get the active filters for the images in this category.
   *
   * @return  array  Associative array in the format: array('filter_published' => 0)
   */
  public function getImagesActiveFilters()
  {
    if($this->item === null)
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    // Load categories list model
    $listModel = $this->component->getMVCFactory()->createModel('images', 'site');
    $listModel->getState();

    // Apply preselected filters and fields selection for images
    $this->setImagesModelState($listModel);

    return $listModel->getActiveFilters();
  }

  /**
   * Function to set the image list model state for the pre defined filter and fields selection
   *
   * @param   ListModel   $listModel    Images list model
   * @param   array       $fields       List of field names to be loaded (default: array())
   *
   * @return  void
   */
  protected function setImagesModelState(ListModel &$listModel, array $fields = [])
  {
    // Get current user
    $user   = $this->app->getIdentity();
    $params = $this->getParams();

    // Apply selection
    if(\count($fields) > 0)
    {
      $listModel->setState('list.select', $fields);
    }

    // Apply filters
    $listModel->setState('filter.category', $this->item->id);
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showunapproved', 0);
    $listModel->setState('filter.showhidden', 0);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->item->language);
    }

    $imgform_list       = [];
    $imgform_limitstart = $this->app->getUserState('joom.categoryview.' . $this->item->id . '.image.limitstart', 0);

    if($this->app->input->get('contenttype', '') == 'image')
    {
      // Get query variables sent by the images form
      $imgform_list       = $this->app->input->get('list', []);
      $imgform_limitstart = $this->app->getUserStateFromRequest('joom.categoryview.' . $this->item->id . '.image.limitstart', 'limitstart', 0, 'uint');
    }

    // Override number of images being loaded
    if($params['configs']->get('jg_category_view_pagination', 0, 'int') > 0)
    {
      // Load all images when not pagination active
      $listModel->setState('list.limit', '0');
    }
    else
    {
      // Load the number of images defined in the configuration
      $listModel->setState('list.limit', $params['configs']->get('jg_category_view_numb_images', 12, 'int'));

      // Apply number of images to be loaded from list in the view
      if(isset($imgform_list['limit']))
      {
        $listModel->setState('list.limit', $imgform_list['limit']);
      }
    }

    // Switch between pagination behavior and plugin behavior for
    // limit and start
    $listModel->setState('list.pages', $params['configs']->get('jg_category_view_pages', 1));

    // Disable behavior of remembering pagination position
    // if it is not explicitly given in the request
    $listModel->setState('list.start', $params['configs']->get('jg_category_view_limit_start', $imgform_limitstart));

    // Apply ordering
    $listModel->setState('list.ordering', '');
    $listModel->setState('list.fullordering', $params['configs']->get('jg_category_view_ord_images', 'a.date ASC', 'string'));
  }

  /**
   * Function to set the subcategory list model state for the pre defined filter and fields selection
   *
   * @param   ListModel   $listModel    Category list model
   * @param   array       $fields       List of field names to be loaded (default: array())
   *
   * @return  void
   */
  protected function setChildrenModelState(ListModel &$listModel, array $fields = [])
  {
    // Get current user
    $user   = $this->app->getIdentity();
    $params = $this->getParams();

    // Apply selection
    if(\count($fields) > 0)
    {
      $listModel->setState('list.select', $fields);
    }

    // Apply filters
    $listModel->setState('filter.category', $this->item->id);
    $listModel->setState('filter.level', 2);
    $listModel->setState('filter.showself', 0);
    $listModel->setState('filter.access', $user->getAuthorisedViewLevels());
    $listModel->setState('filter.published', 1);
    $listModel->setState('filter.showhidden', 0);
    $listModel->setState('filter.showempty', 1);

    if(Multilanguage::isEnabled())
    {
      $listModel->setState('filter.language', $this->item->language);
    }

    $catform_list       = [];
    $catform_limitstart = $this->app->getUserState('joom.categoryview.' . $this->item->id . '.category.limitstart', 0);

    if($this->app->input->get('contenttype', '') == 'category')
    {
      // Get query variables sent by the subcategories form
      $catform_list       = $this->app->input->get('list', []);
      $catform_limitstart = $this->app->getUserStateFromRequest('joom.categoryview.' . $this->item->id . '.category.limitstart', 'limitstart', 0, 'uint');
    }

    // Override number of subcategories being loaded
    if($params['configs']->get('jg_category_view_subcategories_pagination', 0, 'int') > 0)
    {
      // Load all subcategories when not pagination active
      $listModel->setState('list.limit', '0');
    }
    else
    {
      // Load the number of subcategories defined in the configuration
      $listModel->setState('list.limit', $params['configs']->get('jg_category_view_numb_subcategories', 12, 'int'));

      // Apply number of subcategories to be loaded from list in the view
      if(isset($catform_list['limit']))
      {
        $listModel->setState('list.limit', $catform_list['limit']);
      }
    }

    // Disable behavior of remembering pagination position
    // if it is not explicitly given in the request
    $listModel->setState('list.start', $catform_limitstart);

    // Apply ordering
    $listModel->setState(
      'list.fullordering',
      $catform_list['fullordering']
          ?? $params['configs']->get('jg_category_view_ord_images', 'a.lft ASC', 'string')
    );

  }

  /**
   * Method to add a prefix to a list of field names
   *
   * @param   string  $prefix   The prefix to apply
   * @param   array   $fields   List of fields
   *
   * @return  array   List of fields with applied prefix
   */
  protected function addColumnPrefix(string $prefix, array $fields): array
  {
    foreach($fields as $key => $field)
    {
      $field = (string) $field;

      if(strpos($field, $prefix . '.') === false)
      {
        $fields[$key] = $prefix . '.' . $field;
      }
    }

    return $fields;
  }

  /**
   * Get a list of parent categories that are not published (state = 1)
   *
   * @param   ?int    $pk         Primary key of the category
   * @param   bool   $approved   True if the parents also have to be approved
   *
   * @return  array  List of all parents that are published
   *
   * @since   4.0.0
   * @throws \Exception
   */
  public function getUnpublishedParents(?int $pk = null, bool $approved = false): array
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = \intval($this->item->id);
    }
    else
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    if(isset($this->item->unpublishedParents))
    {
      return $this->item->unpublishedParents;
    }

    // Create a new query object.
    $db        = $this->getDatabase();
        $query = $db->createQuery();
    $query->select('id');
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));
    $query->order($db->quoteName('level') . ' DESC');

    // Select parents
    $query->where($db->quoteName('lft') . ' <= ' . $this->item->lft . ' AND ' . $db->quoteName('rgt') . ' >= ' . $this->item->rgt);

    // Exclude root category
    $query->where($db->quoteName('level') . ' > 0');

    if($approved)
    {
      // Select records which are not published or not approved
      $query->where('(' . $db->quoteName('published') . ' != 1 OR ' . $db->quoteName('approved') . ' != 1)');
    }
    else
    {
      // Select records which are not published
      $query->where($db->quoteName('published') . ' != 1');
    }

    try
    {
      $db->setQuery($query);
      $list = $db->loadColumn();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog('Error in getAncestorIsPublished(). Error: ' . $e->getMessage(), 'error', 'jerror');

      return [];
    }

    $this->item->unpublishedParents = $list ? $list : [];

    return $this->item->unpublishedParents;
  }

  /**
   * Get a list of parent categories that are are protected
   *
   * @return  array  List of all parents that are protected
   *
   * @since   4.0.0
   */
  public function getProtectedParents(?int $pk = null): array
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = \intval($this->item->id);
    }
    else
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    if(isset($this->item->protectedParents))
    {
      return $this->item->protectedParents;
    }

    // Create a new query object.
    $db    = $this->getDatabase();
    $query = $db->createQuery();
    $query->select('id');
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));
    $query->order($db->quoteName('level') . ' DESC');

    // Select parents
    $query->where($db->quoteName('lft') . ' <= ' . $this->item->lft . ' AND ' . $db->quoteName('rgt') . ' >= ' . $this->item->rgt);

    // Exclude root category
    $query->where($db->quoteName('level') . ' > 0');

    // Select records which are protected and not yet unlocked
    $query->where('(' . $db->quoteName('password') . ' != ' . $db->quote('') . ' AND ' . $db->quoteName('id') . ' NOT IN (' . implode(',', $this->app->getUserState(_JOOM_OPTION . 'unlockedCategories', [0])) . '))');

    try
    {
      $db->setQuery($query);
      $list = $db->loadColumn();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog('Error in getAncestorIsProtected(). Error: ' . $e->getMessage(), 'error', 'jerror');

      return [];
    }

    $this->item->protectedParents = $list ? $list : [];

    return $this->item->protectedParents;
  }

  /**
   * Get a list of parent categories that are not accessible (view level) by the user
   *
   * @return  array  List of all parents that are not accessible
   *
   * @since   4.0.0
   */
  public function getAccessibleParents(?int $pk = null): array
  {
    if(\is_null($pk) && !\is_null($this->item) && isset($this->item->id))
    {
      $pk = \intval($this->item->id);
    }
    else
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_ITEM_NOT_LOADED'), 1);
    }

    if(isset($this->item->accessibleParents))
    {
      return $this->item->accessibleParents;
    }

    // Get current user
    $user = $this->app->getIdentity();

    // Create a new query object.
    $db        = $this->getDatabase();
        $query = $db->createQuery();
    $query->select('id');
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));
    $query->order($db->quoteName('level') . ' DESC');

    // Select parents
    $query->where($db->quoteName('lft') . ' <= ' . $this->item->lft . ' AND ' . $db->quoteName('rgt') . ' >= ' . $this->item->rgt);

    // Exclude root category
    $query->where($db->quoteName('level') . ' > 0');

    // Select records which are not accessible via users view levels
    $query->where($db->quoteName('access') . ' NOT IN (' . implode(',', $user->getAuthorisedViewLevels()) . ')');

    try
    {
      $db->setQuery($query);
      $list = $db->loadColumn();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog('Error in getAncestorIsViewLevel(). Error: ' . $e->getMessage(), 'error', 'jerror');

      return [];
    }

    $this->item->accessibleParents = $list ? $list : [];

    return $this->item->accessibleParents;
  }
}
