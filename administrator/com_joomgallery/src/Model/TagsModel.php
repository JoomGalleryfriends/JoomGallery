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
 * Methods supporting a list of Tags records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class TagsModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'tag';

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
				'title', 'a.title',
				'published', 'a.published',
				'access', 'a.access',
				'language', 'a.language',
				'description', 'a.description',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_time', 'a.modified_time',
				'modified_by', 'a.modified_by',
				'id', 'a.id',
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
    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
		$this->setState('filter.language', $language);
    $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', array());
    $this->setState('filter.access', $access);

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
    $id .= ':' . serialize($this->getState('filter.access'));

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
    $query->from($db->quoteName('#__joomgallery_tags', 'a'));

		// Join over the users for the checked out user
    $query->select($db->quoteName('uc.name', 'uEditor'));
    $query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

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

    // Count Items
		$countQuery = $db->getQuery(true);
		$countQuery->select('COUNT(' . $db->quoteName('tags_ref.imgid') . ')')
			         ->from($db->quoteName(_JOOM_TABLE_TAGS_REF, 'tags_ref'))
			         ->where($db->quoteName('tags_ref.tagid') . ' = ' . $db->quoteName('a.id'));
		$query->select('(' . $countQuery->__toString() . ') AS ' . $db->quoteName('countTaggedItems'));

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
    $query->from($db->quoteName('#__joomgallery_tags', 'a'));

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
   * 
   * @since   4.0.0
	 */
	public function getItems()
	{
		$items = parent::getItems();

		return $items;
	}

  /**
	 * Search for data items
   * 
   * @param   array  $filters  Filter to apply to the search
	 *
	 * @return  array
   * 
   * @since   4.0.0
	 */
	public function searchItems($filters = array())
	{
    $db = $this->getDatabase();
    $query = $db->getQuery(true)
            ->select([
                        $db->quoteName('a.id', 'value'),
                        $db->quoteName('a.title', 'text'),
                      ])
            ->from($db->quoteName(_JOOM_TABLE_TAGS, 'a'));

    // Filter language
    if(!empty($filters['flanguage']))
    {
        $query->whereIn($db->quoteName('a.language'), [$filters['flanguage'], '*'], ParameterType::STRING);
    }

    // Search in title or path
    if(!empty($filters['like']))
    {
        $search = '%' . trim($filters['like']) . '%';
        $query->where(
                        '(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.alias') . ' LIKE :search2)'
                      )
              ->bind([':search1', ':search2'], $search);
    }

    // Filter title
    if(!empty($filters['title']))
    {
        $query->where($db->quoteName('a.title') . ' = :title')
              ->bind(':title', $filters['title']);
    }

    // Filter on the published state
    if(isset($filters['published']) && is_numeric($filters['published']))
    {
        $published = (int) $filters['published'];
        $query->where($db->quoteName('a.published') . ' = :published')
              ->bind(':published', $published, ParameterType::INTEGER);
    }

    // Filter on the access level
    if(isset($filters['access']) && \is_array($filters['access']) && \count($filters['access']))
    {
        $groups = ArrayHelper::toInteger($filters['access']);
        $query->whereIn($db->quoteName('a.access'), $groups);
    }

    $query->group([
            $db->quoteName('a.id'),
            $db->quoteName('a.title'),
            $db->quoteName('a.ordering'),
            $db->quoteName('a.published'),
            $db->quoteName('a.access'),
          ])
          ->order($db->quoteName('a.ordering') . ' ASC');

    // Get the options.
    $db->setQuery($query);

    try
    {
      $items = $db->loadObjectList();
    }
    catch(\RuntimeException $e)
    {
      return array();
    }

    return $items;
  }

  /**
	 * Build an SQL query to load a list of all items mapped to an image.
   * 
   * @param   int  $img_id  ID of the mapped image
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.0.0
	 */
	protected function getMappedListQuery($img_id)
	{
    // Create a new query object. 
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

    // Select the required fields from the table.
    $query->select('a.*');
    $query->from($db->quoteName(_JOOM_TABLE_TAGS, 'a'));

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
		$query->select(array($db->quoteName('l.title', 'language_code')));
		$query->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

    // Apply the mapping
		$query->join('INNER', $db->quoteName(_JOOM_TABLE_TAGS_REF, 'ref'), $db->quoteName('a.id') . ' = ' . $db->quoteName('ref.tagid'));
    $query->where($db->quoteName('ref.imgid') . ' = ' . $db->quote($img_id));

    $query->order('a.id ASC');

    return $query;
  }

  /**
	 * Get an array of data items mapped to an an image.
   * 
   * @param   int  $img_id  ID of the mapped image
	 *
	 * @return mixed Array of data items on success, false on failure.
   * 
   * @since   4.0.0
	 */
  public function getMappedItems($img_id)
  {
    try
    {
      // Load the list items
      $query = $this->getMappedListQuery($img_id);
      $items = $this->_getList($query);
    }
    catch(\RuntimeException $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }

    return $items;
  }

	/**
	 * Store items based on list generated by tags select field
	 * 
	 * @param   array  $tags  List of tags
	 *
	 * @return  array  List of tags on success, False otherwise
	 *
	 * @since   4.0.0
	 */
	public function storeTagsList($tags)
	{
		$com_obj   = Factory::getApplication()->bootComponent('com_joomgallery');
    $tag_model = $com_obj->getMVCFactory()->createModel('Tag', 'administrator');

    foreach($tags as $key => $tag)
    {
      if(\is_object($tag))
      {
        $tag_title = $tag->title;
      }
      elseif(\is_array($tag))
      {
        $tag_title = $tag['title'];
      }
      else
      {
        $tag_title = $tag;
      }

      if(strpos($tag_title, '#new#') !== false)
      {
        $tag_title = \str_replace('#new#', '', $tag_title);

        // Create tag object
        $data = array();
        $data['id']          = '0';
        $data['title']       = $tag_title;
        $data['published']   = '1';
        $data['access']      = '1';
        $data['language']    = '*';
        $data['description'] = '';

        if(!$tag_model->save($data))
        {
          $this->setError($tag_model->getError());
          $this->component->addLog($tag_model->getError(), 'error', 'jerror');

          return false;
        }
        else
        {
          // Update tags list entry on success
          $tags[$key] = \strval($tag_model->getItem($tag_title)->id);
        }
      }
    }

		return $tags;
	}

	/**
	 * Update mapping between tags and image
	 * 
	 * @param   array  $new_tags   List of tags to be mapped to the image
	 * @param   int    $img_id     Id of the image
	 *
	 * @return  True on success, False otherwise
	 *
	 * @since   4.0.0
	 */
	public function updateMapping($new_tags, $img_id)
	{
    $new_tags = ArrayHelper::toInteger($new_tags);

		$current_tags = $this->idArray($this->getMappedItems($img_id));
    $current_tags = ArrayHelper::toInteger($current_tags);

    $com_obj   = Factory::getApplication()->bootComponent('com_joomgallery');
    $tag_model = $com_obj->getMVCFactory()->createModel('Tag', 'administrator');

    $success = true;
    foreach($new_tags as $tag_id)
    {
      if(!\in_array($tag_id, $current_tags))
      {
        // add tag from mapping
        if(!$tag_model->addMapping($tag_id, $img_id))
        {
          $this->setError($tag_model->getError());
          $this->component->addLog($tag_model->getError(), 'error', 'jerror');
          $success = false;
        }
      }
    }

    foreach($current_tags as $tag_id)
    {
      if(!\in_array($tag_id, $new_tags))
      {
        // remove tag from mapping
        if(!$tag_model->removeMapping($tag_id, $img_id))
        {
          $this->setError($tag_model->getError());
          $this->component->addLog($tag_model->getError(), 'error', 'jerror');
          $success = false;
        }
      }
    }

		return $success;
	}


  /**
	 * Convert a list of tag objects to a list of tag ids
	 * 
	 * @param   array  $objectlist   List of tag objects
	 *
	 * @return  array  List of tag ids
	 *
	 * @since   4.0.0
	 */
  protected function idArray($objectlist)
  {
    $array = array();

    foreach($objectlist as $obj)
    {
      \array_push($array, $obj->id);
    }

    return $array;
  }
}
