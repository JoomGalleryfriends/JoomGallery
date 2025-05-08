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

use \Joomla\Database\ParameterType;

/**
 * Methods supporting a list of Configs records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigsModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'config';

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
				'id', 'a.id',
        'group_id', 'a.group_id',
				'published', 'a.published',
				'ordering', 'a.ordering',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'title', 'a.title',
        'note', 'a.note'
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
    $forcedLanguage = $this->app->input->get('forcedLanguage', '', 'cmd');

    // Adjust the context to support modal layouts.
		if ($layout = $this->app->input->get('layout'))
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
    $query->from($db->quoteName('#__joomgallery_configs', 'a'));

		// Join over the users for the checked out user
		$query->select($db->quoteName('uc.name', 'uEditor'));
		$query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

		// Join over the user field 'created_by'
		$query->select(array($db->quoteName('ua.name', 'created_by'), $db->quoteName('ua.id', 'created_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));
		
    // Join over the user field 'modified_by'
		$query->select(array($db->quoteName('um.name', 'modified_by'), $db->quoteName('um.id', 'modified_by_id')));
    $query->join('LEFT', $db->quoteName('#__users', 'um'), $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by'));

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
					'(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.note') . ' LIKE :search2)'
				)
					->bind([':search1', ':search2'], $search);
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

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'id');
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
    $query->from($db->quoteName('#__joomgallery_configs', 'a'));

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
					'(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.note') . ' LIKE :search2)'
				)
					->bind([':search1', ':search2'], $search);
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

		foreach($items as $oneItem)
		{
			if(isset($oneItem->group_id))
			{
				$values    = explode(',', $oneItem->group_id);
				$textValue = array();

				foreach($values as $value)
				{
					if(!empty($value))
					{
						$db = $this->getDatabase();
						$query = "SELECT id, title FROM #__usergroups HAVING id LIKE '" . $value . "'";
						$db->setQuery($query);
						$results = $db->loadObject();

						if($results)
						{
							$textValue[] = $results->title;
						}
					}
				}

				$oneItem->group_id = !empty($textValue) ? implode(', ', $textValue) : $oneItem->group_id;
			}
		}

		return $items;
	}
}
