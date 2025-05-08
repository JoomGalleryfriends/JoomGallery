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
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomListModel;

/**
 * Methods supporting a list of Images records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

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
				'ordering', 'a.ordering',
				'hits', 'a.hits',
				'downloads', 'a.downloads',
				'votes', 'a.votes',
				'votesum', 'a.votesum',
				'approved', 'a.approved',
				'useruploaded', 'a.useruploaded',
				'title', 'a.title',
				'alias', 'a.alias',
				'cattitle', 'cattitle',
				'published', 'a.published',
				'author', 'a.author',
				'language', 'a.language',
				'description', 'a.description',
				'access', 'a.access',
				'hidden', 'a.hidden',
				'featured', 'a.featured',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_time', 'a.modified_time',
				'modified_by', 'a.modified_by',
				'id', 'a.id',
				'metadesc', 'a.metadesc',
				'metakey', 'a.metakey',
				'robots', 'a.robots',
				'filename', 'a.filename',
				'date', 'a.date',
				'imgmetadata', 'a.imgmetadata',
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
	protected function populateState($ordering = 'a.id', $direction = 'ASC')
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
		$language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);
    $showunapproved = $this->getUserStateFromRequest($this->context . '.filter.showunapproved', 'filter_showunapproved', '1');
    $this->setState('filter.showunapproved', $showunapproved);
    $showhidden = $this->getUserStateFromRequest($this->context . '.filter.showhidden', 'filter_showhidden', '1');
    $this->setState('filter.showhidden', $showhidden);
    $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', array());
    $this->setState('filter.access', $access);
    $createdBy = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
    $this->setState('filter.created_by', $createdBy);
    $category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', array());
    $this->setState('filter.category', $category);
    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', array());
    $this->setState('filter.tag', $tag);

		// Force a language
		if (!empty($forcedLanguage))
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
		$id .= ':' . $this->getState('filter.language');
    $id .= ':' . $this->getState('filter.showunapproved');
    $id .= ':' . $this->getState('filter.showhidden');
    $id .= ':' . serialize($this->getState('filter.access'));
    $id .= ':' . serialize($this->getState('filter.created_by'));
    $id .= ':' . serialize($this->getState('filter.category'));
		//$id .= ':' . serialize($this->getState('filter.tag'));

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
    $query->from($db->quoteName('#__joomgallery', 'a'));

		// Join over the users for the checked out user
    $query->select($db->quoteName('uc.name', 'uEditor'));
    $query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));
    
		// Join over the foreign key 'catid'
    $query->select(array($db->quoteName('category.title', 'cattitle'), $db->quoteName('category.created_by', 'cat_uid')));
    $query->join('LEFT', $db->quoteName('#__joomgallery_categories', 'category'), $db->quoteName('category.id') . ' = ' . $db->quoteName('a.catid'));

		// Join over the access level field 'access'
    $query->select($db->quoteName('access.title', 'access'));
    $query->join('LEFT', $db->quoteName('#__viewlevels', 'access'), $db->quoteName('access.id') . ' = ' . $db->quoteName('a.access'));

		// Join over the user field 'created_by'
    $query->select(array($db->quoteName('ua.name', 'created_by'), $db->quoteName('ua.id', 'created_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

		// Join over the user field 'modified_by'
    $query->select(array($db->quoteName('um.name', 'modified_by'), $db->quoteName('um.id', 'modified_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'um'), $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by'));

		// Join over the language fields 'language_title' and 'language_image'
		$query->select(array($db->quoteName('l.title', 'language_title'), $db->quoteName('l.image', 'language_image')));
		$query->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));
	
    // Filter by access level.
		$filter_access = $this->state->get("filter.access");
    
    if(!empty($filter_access))
		{
      if(is_numeric($filter_access))
      {
        $filter_access = (int) $filter_access;
        $query->where($db->quoteName('a.access') . ' = :access')
              ->bind(':access', $filter_access, ParameterType::INTEGER);
      }
      elseif (is_array($filter_access))
      {
        $filter_access = ArrayHelper::toInteger($filter_access);
        $query->whereIn($db->quoteName('a.access'), $filter_access);
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

        if($state == 1 || $state == 2)
        { // published/unpublished

          // translate state
          $state = ($state == 1) ? 1 : 0;

          // row name
          $row = 'a.published';
        }
        elseif($state == 3 || $state == 4)
        {// approved/not approved

          // translate state
          $state = ($state == 3) ? 1 : 0;
          
          // row name
          $row = 'a.approved';
        }
        elseif($state == 5)
        {// rejected
          Factory::getApplication()->enqueueMessage('Unknown state: Rejected', 'error');
          $state = false;
        }
        elseif($state == 6 || $state == 7)
        {// featured/not featured

          // translate state
          $state = ($state == 6) ? 1 : 0;

          // row name
          $row = 'a.featured';
        }

        if($state || $state === 0)
        {
          $query->where($db->quoteName($row) . ' = :state')
					->bind(':state', $state, ParameterType::INTEGER);
        }        
			}
		}

    // Filter by hidden images
    $showhidden = (bool) $this->getState('filter.showhidden');

    if(!$showhidden)
		{
      $query->where($db->quoteName('a.hidden') . ' = 0');
		}

    // Filter by unapproved images
    $showunapproved = (bool) $this->getState('filter.showunapproved');

    if(!$showunapproved)
		{
      $query->where($db->quoteName('a.approved') . ' = 1');
		}

    // Filter by categories
    $catId = $this->getState("filter.category");

    // Convert to array
    if(isset($catId) && !\is_array($catId))
    {
      $catId = (string) preg_replace('/[^0-9\,]/i', '', $catId);
      if(\strpos($catId, ',') !== false)
      {
        $catId = \explode(',', $catId);
      }
    }

    if(!empty($catId))
    {
      if(is_numeric($catId))
      {
        $catId = (int) $catId;
        $query->where($db->quoteName('a.catid') . ' = :catId')
          ->bind(':catId', $catId, ParameterType::INTEGER);
      }
      elseif(is_array($catId))
      {
        $catId = ArrayHelper::toInteger($catId);
        $query->whereIn($db->quoteName('a.catid'), $catId);
      }
    }

    // Filter: Exclude images
    $excludedId = Factory::getApplication()->input->get('exclude', '', 'string');
    $excludedId = (string) preg_replace('/[^0-9\,]/i', '', $excludedId);
    if(\strpos($excludedId, ',') !== false)
    {
      $excludedId = \explode(',', $excludedId);
    }

    if(is_numeric($excludedId))
		{
			$excludedId = (int) $excludedId;
			$query->where($db->quoteName('a.id') . ' != :imgId')
				->bind(':imgId', $excludedId, ParameterType::INTEGER);
		}
		elseif(is_array($excludedId))
		{
			$excludedId = ArrayHelper::toInteger($excludedId);
			$query->whereNotIn($db->quoteName('a.id'), $excludedId);
		}

    // Filter on the language.
		if($language = $this->getState('filter.language'))
		{
			$query->where($db->quoteName('a.language') . ' = :language')
				->bind(':language', $language);
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.id'); 
		$orderDirn = $this->state->get('list.direction', 'ASC');
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

		// Select the required fields from the table.
		$query->select('COUNT(*)');
    $query->from($db->quoteName('#__joomgallery', 'a'));
	
    // Filter by access level.
		$filter_access = $this->state->get("filter.access");
    
    if(!empty($filter_access))
		{
      if(is_numeric($filter_access))
      {
        $filter_access = (int) $filter_access;
        $query->where($db->quoteName('a.access') . ' = :access')
              ->bind(':access', $filter_access, ParameterType::INTEGER);
      }
      elseif (is_array($filter_access))
      {
        $filter_access = ArrayHelper::toInteger($filter_access);
        $query->whereIn($db->quoteName('a.access'), $filter_access);
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

        if($state == 1 || $state == 2)
        { // published/unpublished

          // translate state
          $state = ($state == 1) ? 1 : 0;

          // row name
          $row = 'a.published';
        }
        elseif($state == 3 || $state == 4)
        {// approved/not approved

          // translate state
          $state = ($state == 3) ? 1 : 0;
          
          // row name
          $row = 'a.approved';
        }
        elseif($state == 5)
        {// rejected
          Factory::getApplication()->enqueueMessage('Unknown state: Rejected', 'error');
          $state = false;
        }
        elseif($state == 6 || $state == 7)
        {// featured/not featured

          // translate state
          $state = ($state == 6) ? 1 : 0;

          // row name
          $row = 'a.featured';
        }

        if($state || $state === 0)
        {
          $query->where($db->quoteName($row) . ' = :state')
					->bind(':state', $state, ParameterType::INTEGER);
        }        
			}
		}

    // Filter by hidden images
    $showhidden = (bool) $this->getState('filter.showhidden');

    if(!$showhidden)
		{
      $query->where($db->quoteName('a.hidden') . ' = 0');
		}

    // Filter by unapproved images
    $showunapproved = (bool) $this->getState('filter.showunapproved');

    if(!$showunapproved)
		{
      $query->where($db->quoteName('a.approved') . ' = 1');
		}

    // Filter by categories
    $catId = $this->getState("filter.category");

    // Convert to array
    if(isset($catId) && !\is_array($catId))
    {
      $catId = (string) preg_replace('/[^0-9\,]/i', '', $catId);
      if(\strpos($catId, ',') !== false)
      {
        $catId = \explode(',', $catId);
      }
    }

    if(!empty($catId))
    {
      if(is_numeric($catId))
      {
        $catId = (int) $catId;
        $query->where($db->quoteName('a.catid') . ' = :catId')
          ->bind(':catId', $catId, ParameterType::INTEGER);
      }
      elseif(is_array($catId))
      {
        $catId = ArrayHelper::toInteger($catId);
        $query->whereIn($db->quoteName('a.catid'), $catId);
      }
    }

    // Filter: Exclude images
    $excludedId = Factory::getApplication()->input->get('exclude', '', 'string');
    $excludedId = (string) preg_replace('/[^0-9\,]/i', '', $excludedId);
    if(\strpos($excludedId, ',') !== false)
    {
      $excludedId = \explode(',', $excludedId);
    }

    if(is_numeric($excludedId))
		{
			$excludedId = (int) $excludedId;
			$query->where($db->quoteName('a.id') . ' != :imgId')
				->bind(':imgId', $excludedId, ParameterType::INTEGER);
		}
		elseif(is_array($excludedId))
		{
			$excludedId = ArrayHelper::toInteger($excludedId);
			$query->whereNotIn($db->quoteName('a.id'), $excludedId);
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
}
