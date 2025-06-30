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

/**
 * Methods supporting a list of Tasks records.
 * 
 * @package JoomGallery
 * @since   4.2.0
 */
class TasksModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'task';

	/**
   * Constructor
   * 
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.2.0
   */
  function __construct($config = array())
	{
		if(empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'ordering', 'a.ordering',
				'published', 'a.published',
        'failed', 'a.failed',
        'completed', 'a.completed',
				'created_time', 'a.created_time',
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
	protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
	{
    $app = Factory::getApplication();

    // Adjust the context to support modal layouts.
		if ($layout = $app->input->get('layout'))
		{
			$this->context .= '.' . $layout;
		}

    // List state information.
		parent::populateState($ordering, $direction);

    // Load the filter state.
    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
		$this->setState('filter.search', $search);
    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '');
		$this->setState('filter.published', $published);
    $failed = $this->getUserStateFromRequest($this->context . '.filter.failed', 'filter_failed', '');
		$this->setState('filter.failed', $failed);
    $completed = $this->getUserStateFromRequest($this->context . '.filter.completed', 'filter_completed', '');
		$this->setState('filter.completed', $completed);
	}

  /**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
   * 
   * @since   4.2.0
	 */
	public function getItems()
	{
		$items_arr = parent::getItems();
    $items = ['instant' => [], 'planned' => []];

    foreach($items_arr as $item)
    {
      // Add type if missing
      if(!\array_key_exists($item->type, $items))
      {
        $items[$item->type] = [];
      }

      // Add item to the corresponding type
      \array_push($items[$item->type], $item);
    }

		return $items;
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
    $id .= ':' . $this->getState('filter.failed');
    $id .= ':' . $this->getState('filter.completed');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.2.0
	 */
	protected function getListQuery()
	{
		// Create a new query object. 
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
    $query->select($this->getState('list.select', 'a.*'));
    $query->from($db->quoteName('#__joomgallery_tasks', 'a'));

		// Join over the users for the checked out user
    $query->select($db->quoteName('uc.name', 'uEditor'));
    $query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

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

    // Filter by failed state
		$failed = (string) $this->getState('filter.failed');

		if($failed !== '*')
		{
			if(is_numeric($failed))
			{
				$failed = (int) $failed;
        if($failed > 0)
        {
          // Show only records with failed tasks (non-empty JSON arrays)
          $query->where($db->quoteName('a.failed') . ' != ' . $db->quote(''))
			          ->where($db->quoteName('a.failed') . ' != ' . $db->quote('{}'));
        }
        else
        {
          // Show only records with no failed tasks (empty or empty JSON array)
          $query->where([
            $db->quoteName('a.failed') . ' = ' . $db->quote(''),
            $db->quoteName('a.failed') . ' = ' . $db->quote('{}')
          ], 'OR');
        }
			}
		}

    // Filter by completed state
		$completed = (string) $this->getState('filter.completed');

		if($completed !== '*')
		{
			if(is_numeric($completed))
			{
				$completed = (int) $completed;
				$query->where($db->quoteName('a.completed') . ' = :completed')
					->bind(':completed', $completed, ParameterType::INTEGER);
			}
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'a.ordering'); 
		$orderDirn = $this->state->get('list.direction', 'ASC');
    if($orderCol && $orderDirn)
    {
      $query->order($db->escape($orderCol . ' ' . $orderDirn));
    }
    else
    {
      $query->order($db->escape($this->state->get('list.fullordering', 'a.ordering ASC')));
    }

		return $query;
	}

  /**
	 * Build an SQL query to load the list data for counting.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.2.0
	 */
	protected function getCountListQuery()
	{
		// Create a new query object. 
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
    $query->select('COUNT(*)');
    $query->from($db->quoteName('#__joomgallery_tasks', 'a'));

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

    // Filter by failed state
		$failed = (string) $this->getState('filter.failed');

		if($failed !== '*')
		{
			if(is_numeric($failed))
			{
				$failed = (int) $failed;
        if($failed > 0)
        {
          // Show only records with failed tasks (non-empty JSON arrays)
          $query->where($db->quoteName('a.failed') . ' != ' . $db->quote(''))
			          ->where($db->quoteName('a.failed') . ' != ' . $db->quote('{}'));
        }
        else
        {
          // Show only records with no failed tasks (empty or empty JSON array)
          $query->where([
            $db->quoteName('a.failed') . ' = ' . $db->quote(''),
            $db->quoteName('a.failed') . ' = ' . $db->quote('{}')
          ], 'OR');
        }
			}
		}

    // Filter by completed state
		$completed = (string) $this->getState('filter.completed');

		if($completed !== '*')
		{
			if(is_numeric($completed))
			{
				$completed = (int) $completed;
				$query->where($db->quoteName('a.completed') . ' = :completed')
					->bind(':completed', $completed, ParameterType::INTEGER);
			}
		}

		return $query;
	}
}
