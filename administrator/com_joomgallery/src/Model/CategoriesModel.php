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
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Categories records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoriesModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'category';

	/**
   * Constructor
   * 
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.0.0
   */
  function __construct($config = array())
	{
		if(empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'lft', 'a.lft',
				'rgt', 'a.rgt',
				'level', 'a.level',
				'path', 'a.path',
				'in_hidden', 'a.in_hidden',
				'title', 'a.title',
				'alias', 'a.alias',
				'parent_id', 'a.parent_id',
				'parent_title', 'a.parent_title',
				'published', 'a.published',
				'access', 'a.access',
				'password', 'a.password',
				'language', 'a.language',
				'description', 'a.description',
				'hidden', 'a.hidden',
				'exclude_toplist', 'a.exclude_toplist',
				'exclude_search', 'a.exclude_search',
				'thumbnail', 'a.thumbnail',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'modified_time', 'a.modified_time',
				'id', 'a.id',
				'img_count', 'a.img_count',
				'child_count', 'a.child_count',
				'metadesc', 'a.metadesc',
				'metakey', 'a.metakey',
				'robots', 'a.robots',
				'params', 'a.params',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = 'a.lft', $direction = 'asc')
	{
    $app = Factory::getApplication();

		$forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');

    // Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

    // Adjust the context to support forced languages.
		if ($forcedLanguage)
		{
			$this->context .= '.' . $forcedLanguage;
		}

    // List state information.
		parent::populateState($ordering, $direction);

    // Load the filter state.
		$search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
		$this->setState('filter.search', $search);
    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '*');
		$this->setState('filter.published', $published);
    $level = $this->getUserStateFromRequest($this->context . '.filter.level', 'filter_level', '*');
		$this->setState('filter.level', $level);
    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);
    $showself = $this->getUserStateFromRequest($this->context . '.filter.showself', 'filter_showself', '1');
    $this->setState('filter.showself', $showself);
    $showhidden = $this->getUserStateFromRequest($this->context . '.filter.showhidden', 'filter_showhidden', '1');
    $this->setState('filter.showhidden', $showhidden);
    $showempty = $this->getUserStateFromRequest($this->context . '.filter.showempty', 'filter_showempty', '1');
    $this->setState('filter.showempty', $showempty);
    $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', array());
    $this->setState('filter.access', $access);
    $createdBy = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
    $this->setState('filter.created_by', $createdBy);
    $category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', array());
    $this->setState('filter.category', $category);
    $exclude = $this->getUserStateFromRequest($this->context . '.filter.exclude', 'filter_exclude', array());
    $this->setState('filter.exclude', $exclude);

    // Force a language
		if(!empty($forcedLanguage))
		{
			$this->setState('filter.language', $forcedLanguage);
			$this->setState('filter.forcedLanguage', $forcedLanguage);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string A store id.
	 *
	 * @since   4.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
    $id .= ':' . $this->getState('filter.published');
    $id .= ':' . $this->getState('filter.level');
    $id .= ':' . $this->getState('filter.language');
    $id .= ':' . $this->getState('filter.showself');
    $id .= ':' . $this->getState('filter.showhidden');
    $id .= ':' . $this->getState('filter.showempty');
		$id .= ':' . serialize($this->getState('filter.access'));
    $id .= ':' . serialize($this->getState('filter.created_by'));
		$id .= ':' . serialize($this->getState('filter.category'));
    $id .= ':' . serialize($this->getState('filter.exclude'));

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.0.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'a.*'));
    $query->from($db->quoteName('#__joomgallery_categories', 'a'));

		// Join over the users for the checked out user
		$query->select($db->quoteName('uc.name', 'uEditor'));
    $query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));
		$query->where($db->quoteName('a.level') . ' <> 0');

		// Join over the access level field 'access'
    $query->select($db->quoteName('ag.title', 'access'));
    $query->join('LEFT', $db->quoteName('#__viewlevels', 'ag'), $db->quoteName('ag.id') . ' = ' . $db->quoteName('a.access'));

		// Join over the user field 'created_by'
    $query->select(array($db->quoteName('ua.name', 'created_by'), $db->quoteName('ua.id', 'created_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

		// Join over the user field 'modified_by'
    $query->select($db->quoteName('um.name', 'modified_by'));
    $query->join('LEFT', $db->quoteName('#__users', 'um'), $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by'));

    // Join over the category field 'parent_title'
    $query->select($db->quoteName('parent.title', 'parent_title'));
    $query->join('LEFT', $db->quoteName('#__joomgallery_categories', 'parent'), $db->quoteName('parent.id') . ' = ' . $db->quoteName('a.parent_id'));

    // Get img_count
    $imgQuery = $db->getQuery(true);
    $imgQuery->select('COUNT(`img`.id)')
             ->from($db->quoteName('#__joomgallery', 'img'))
             ->where($db->quoteName('img.catid') . ' = ' . $db->quoteName('a.id'));
    $query->select('(' . $imgQuery->__toString() . ') AS ' . $db->quoteName('img_count'));

    // Get child_count
    $subQuery = $db->getQuery(true);
    $subQuery->select('COUNT(`child`.id)')
             ->from($db->quoteName('#__joomgallery_categories', 'child'))
             ->where($db->quoteName('child.parent_id') . ' = ' . $db->quoteName('a.id'));
    $query->select('(' . $subQuery->__toString() . ') AS ' . $db->quoteName('child_count'));

    // Join over the language fields 'language_title' and 'language_image'
		$query->select(array($db->quoteName('l.title', 'language_title'), $db->quoteName('l.image', 'language_image')));
		$query->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

    // Filter by access level.
		$access = $this->getState('filter.access');

    if(!empty($access))
		{
      if(is_numeric($access))
      {
        $access = (int) $access;
        $query->where($db->quoteName('a.access') . ' = :access')
          ->bind(':access', $access, ParameterType::INTEGER);
      }
      elseif(is_array($access))
      {
        $access = ArrayHelper::toInteger($access);
        $query->whereIn($db->quoteName('a.access'), $access);
      }
    }
    
    // Filter by owner
		$userId = $this->getState('filter.created_by');

    if(!empty($userId))
		{
      if(is_numeric($userId))
      {
        $userId = (int) $userId;
        $type = $this->getState('filter.created_by.include', true) ? ' = ' : ' <> ';
        $query->where($db->quoteName('a.created_by') . $type . ':userId')
          ->bind(':userId', $userId, ParameterType::INTEGER);
      }
      elseif(is_array($userId))
      {
        $userId = ArrayHelper::toInteger($userId);
        $query->whereIn($db->quoteName('a.created_by'), $userId);
      }
    }

		// Filter by search
		$search = $this->getState('filter.search');

    if(!empty($search))
		{
			if(stripos($search, 'id:') === 0)
			{
				$search = (int) substr($search, 3);
				$query->where($db->quoteName('a.id') . ' = :search')
					->bind(':search', $search, ParameterType::INTEGER);
			}
			else
			{
				$search = '%' . str_replace(' ', '%', trim($search)) . '%';
				$query->where(
					'(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.alias') . ' LIKE :search2'
						. ' OR ' . $db->quoteName('a.description') . ' LIKE :search3)'
				)
					->bind([':search1', ':search2', ':search3'], $search);
			}
		}

    // Filter by published state
		$published = (string) $this->getState('filter.published');

		if($published !== '*')
		{
			if(is_numeric($published))
			{
				$state = (int) $published;
				$query->where($db->quoteName('a.published') . ' = :state')
					->bind(':state', $state, ParameterType::INTEGER);
			}
		}

    // Filter by hidden categories
    $showhidden = (bool) $this->getState('filter.showhidden');

    if(!$showhidden)
		{
      $query->where($db->quoteName('a.hidden') . ' = 0');
		}

    // Filter by empty categories
    $showempty = (bool) $this->getState('filter.showempty');

    if(!$showempty)
    {
      // Add EXISTS subquery
      $existsQuery = $db->getQuery(true);
      $existsQuery->select('1')
                  ->from($db->quoteName('#__joomgallery', 'img'))
                  ->where($db->quoteName('img.catid') . ' = ' . $db->quoteName('a.id'));

      $query->where('EXISTS (' . $existsQuery->__toString() . ')');
    }

    // Filter by categories and by level
		$categoryId = $this->getState('filter.category', array());
		$level      = (int) $this->getState('filter.level');

		if(!is_array($categoryId))
		{
			$categoryId = $categoryId ? array($categoryId) : array();
		}

    // Case: Using both categories filter and by level filter
		if(count($categoryId))
		{
      $this->categoriesFilterQuery($query, $categoryId, $level);
		}
    // Case: Using only the by level filter
		elseif($level = (int) $level)
		{
			$query->where($db->quoteName('a.level') . ' <= :level')
				->bind(':level', $level, ParameterType::INTEGER);
		}

    // Filter: Exclude categories
    $excludeId = $this->getState('filter.exclude', array());
    if(!is_array($excludeId))
		{
			$excludeId = $excludeId ? array($excludeId) : array();
		}

    // Case: Exclude categories filter
    if(count($excludeId))
		{
      $this->categoriesFilterQuery($query, $excludeId, false, true);
    }

    // Filter self (remove the filtered category)
    $showself = (bool) $this->getState('filter.showself');

    if(count($categoryId) && !$showself)
    {
      foreach($categoryId as $catId)
      {
        $query->where($db->quoteName('a.id'). ' != :catid')
          ->bind(':catid', $catId, ParameterType::INTEGER);
      }
    }

    // Filter on the language.
		if($language = $this->getState('filter.language'))
		{
			$query->where($db->quoteName('a.language') . ' = :language')
				->bind(':language', $language);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', "a.lft");
		$orderDirn = $this->state->get('list.direction', "ASC");
    if($orderCol && $orderDirn)
    {
      $query->order($db->escape($orderCol . ' ' . $orderDirn));
    }
    else
    {
      $query->order($db->escape($this->state->get('list.fullordering', 'a.lft ASC')));
    }

		return $query;
	}

  /**
   * Build an SQL query to load the list data for counting.
   *
   * @return  DatabaseQuery
   *
   * @since   4.1.0
   */
  protected function getCountListQuery()
  {
    // Create a new query object.
    $db    = $this->getDbo();
    $query = $db->getQuery(true);

    // Select only COUNT(*)
    $query->select('COUNT(*)');
    $query->from($db->quoteName('#__joomgallery_categories', 'a'));

    // Filter by access level.
    $access = $this->getState('filter.access');

    if(!empty($access))
    {
      if(is_numeric($access))
      {
        $access = (int) $access;
        $query->where($db->quoteName('a.access') . ' = :access')
          ->bind(':access', $access, ParameterType::INTEGER);
      }
      elseif(is_array($access))
      {
        $access = ArrayHelper::toInteger($access);
        $query->whereIn($db->quoteName('a.access'), $access);
      }
    }

    // Filter by owner
    $userId = $this->getState('filter.created_by');

    if(!empty($userId))
    {
      if(is_numeric($userId))
      {
        $userId = (int) $userId;
        $type = $this->getState('filter.created_by.include', true) ? ' = ' : ' <> ';
        $query->where($db->quoteName('a.created_by') . $type . ':userId')
          ->bind(':userId', $userId, ParameterType::INTEGER);
      }
      elseif(is_array($userId))
      {
        $userId = ArrayHelper::toInteger($userId);
        $query->whereIn($db->quoteName('a.created_by'), $userId);
      }
    }

    // Filter by search
    $search = $this->getState('filter.search');

    if(!empty($search))
    {
      if(stripos($search, 'id:') === 0)
      {
        $search = (int) substr($search, 3);
        $query->where($db->quoteName('a.id') . ' = :search')
          ->bind(':search', $search, ParameterType::INTEGER);
      }
      else
      {
        $search = '%' . str_replace(' ', '%', trim($search)) . '%';
        $query->where(
          '(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.alias') . ' LIKE :search2'
            . ' OR ' . $db->quoteName('a.description') . ' LIKE :search3)'
        )
          ->bind([':search1', ':search2', ':search3'], $search);
      }
    }

    // Filter by published state
    $published = (string) $this->getState('filter.published');

    if($published !== '*')
    {
      if(is_numeric($published))
      {
        $state = (int) $published;
        $query->where($db->quoteName('a.published') . ' = :state')
          ->bind(':state', $state, ParameterType::INTEGER);
      }
    }

    // Filter by hidden categories
    $showhidden = (bool) $this->getState('filter.showhidden');

    if(!$showhidden)
    {
      $query->where($db->quoteName('a.hidden') . ' = 0');
    }

    // Filter by empty categories
    $showempty = (bool) $this->getState('filter.showempty');

    if(!$showempty)
    {
      // Add EXISTS subquery
      $existsQuery = $db->getQuery(true);
      $existsQuery->select('1')
                  ->from($db->quoteName('#__joomgallery', 'img'))
                  ->where($db->quoteName('img.catid') . ' = ' . $db->quoteName('a.id'));

      $query->where('EXISTS (' . $existsQuery->__toString() . ')');
    }

    // Filter by categories and by level
    $categoryId = $this->getState('filter.category', array());
    $level      = (int) $this->getState('filter.level');

    if(!is_array($categoryId))
    {
      $categoryId = $categoryId ? array($categoryId) : array();
    }

    // Case: Using both categories filter and by level filter
    if(count($categoryId))
    {
      $this->categoriesFilterQuery($query, $categoryId, $level);
    }
    // Case: Using only the by level filter
    elseif($level = (int) $level)
    {
      $query->where($db->quoteName('a.level') . ' <= :level')
        ->bind(':level', $level, ParameterType::INTEGER);
    }

    // Filter: Exclude categories
    $excludeId = $this->getState('filter.exclude', array());
    if(!is_array($excludeId))
    {
      $excludeId = $excludeId ? array($excludeId) : array();
    }

    // Case: Exclude categories filter
    if(count($excludeId))
    {
      $this->categoriesFilterQuery($query, $excludeId, false, true);
    }

    // Filter self (remove the filtered category)
    $showself = (bool) $this->getState('filter.showself');

    if(count($categoryId) && !$showself)
    {
      foreach($categoryId as $catId)
      {
        $query->where($db->quoteName('a.id'). ' != :catid')
          ->bind(':catid', $catId, ParameterType::INTEGER);
      }
    }

    // Filter on the language.
    if($language = $this->getState('filter.language'))
    {
      $query->where($db->quoteName('a.language') . ' = :language')
        ->bind(':language', $language);
    }

    return $query;
  }

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

  /**
	 * Get an array of data items
	 *
	 * @return void
	 */
  protected function categoriesFilterQuery(&$query, $categoryId, $level=false, $exclude=false)
  {
    $db = $this->getDatabase();

    $categoryId = ArrayHelper::toInteger($categoryId);
    $categoryTable = $this->getMVCFactory()->createTable('Category', 'administrator');
    $subCatItemsWhere = array();

    foreach($categoryId as $key => $filter_catid)
    {
      $categoryTable->load($filter_catid);

      // Because values to $query->bind() are passed by reference, using $query->bindArray() here instead to prevent overwriting.
      $valuesToBind = [$categoryTable->lft, $categoryTable->rgt];

      if($level)
      {
        $valuesToBind[] = $level + $categoryTable->level - 1;
      }

      // Bind values and get parameter names.
      $bounded = $query->bindArray($valuesToBind);

      if($exclude)
      {
        // select all categories except this category and its children
        $categoryWhere = $db->quoteName('a.lft') . ' < ' . $bounded[0] . ' OR ' . $db->quoteName('a.lft') . ' > ' . $bounded[1];
      }
      else
      {
        // select only this category and its children
        $categoryWhere = $db->quoteName('a.lft') . ' >= ' . $bounded[0] . ' AND ' . $db->quoteName('a.rgt') . ' <= ' . $bounded[1];
      }

      if($level)
      {
        $categoryWhere .= ' AND ' . $db->quoteName('a.level') . ' <= ' . $bounded[2];
      }

      $subCatItemsWhere[] = '(' . $categoryWhere . ')';
    }

    $query->where('(' . implode(' OR ', $subCatItemsWhere) . ')');  
  }
}
