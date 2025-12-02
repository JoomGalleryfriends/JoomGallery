<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Scheduler\Administrator\Helper\SchedulerHelper;
use \Joomla\Database\ParameterType;
use \Joomla\Registry\Registry;

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
  function __construct($config = [])
  {
    if(empty($config['filter_fields']))
    {
      $config['filter_fields'] = [
        'ordering', 'a.ordering',
        'published', 'a.published',
        'failed', 'a.failed',
        'completed', 'a.completed',
        'created_time', 'a.created_time',
        'id', 'a.id',
      ];
    }

    parent::__construct($config);
  }

  /**
   * Method to get the scheduler tasks to be viewed in the tasks view.
   *
   * @return  array|false    Array of tasks on success, false on failure.
   *
   * @throws Exception
   */
  public function getScheduledTasks()
  {
    // Load scheduler tasks model
    $listModel = $this->app->bootComponent('com_scheduler')->getMVCFactory()->createModel('tasks', 'administrator', ['ignore_request' => true]);
    $listModel->getState();

    // Select fields to load
    $fields = ['id', 'title', 'type', 'safeTypeTitle', 'state', 'last_exit_code', 'last_execution', 'times_executed', 'locked', 'params', 'note'];
    $fields = $this->addColumnPrefix('a', $fields);

    // Apply preselected filters and fields selection for images
    $this->setTasksModelState($listModel, $fields);

    // Get images
    $items = $listModel->getItems();

    if(!empty($listModel->getError()))
    {
      $this->setError($listModel->getError());
    }

    // Apply type filter
    try
    {
      $filteredItems = array_filter($items, function($obj) {
        return isset($obj->type) && stripos($obj->type, 'joomgallery') !== false;
      });
    }
    catch(\Exception $e)
    {
      return false;
    }

    return $filteredItems;
  }

  /**
   * Method to get an array of data items.
   *
   * @return  mixed  An array of data items on success, false on failure.
   *
   * @since   4.2
   */
  public function getItems()
  {
    $std_items = parent::getItems();

    if($std_items)
    {
      // Initialize
      $items      = [];
      $table_base = $this->component->getMVCFactory()->createTable('Task', 'Administrator');

      // Turn items to table objects
      foreach($std_items as $item)
      {
        $table = clone $table_base;
        $table->reset();

        if(\is_object($item))
        {
          $table->bind(get_object_vars($item));
        }
        else
        {
          $table->bind($item);
        }

        $table->check();
        $table->clcProgress();

        array_push($items, $table);
      }

      return $items;
    }

    return false;
  }

  /**
   * Function to set the scheduler tasks list model state for the pre defined filter and fields selection
   *
   * @param   ListModel   $listModel    Scheduler tasks list model
   * @param   array       $fields       List of field names to be loaded (default: array())
   *
   * @return  void
   */
  protected function setTasksModelState(ListModel &$listModel, array $fields = [])
  {
    // Apply filters
    $listModel->setState('filter.state', 1);

    // Apply limit & ordering
    $listModel->setState('list.limit', 20);
    $listModel->setState('list.ordering', 'a.ordering');
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
            $db->quoteName('a.failed') . ' = ' . $db->quote('{}'),
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
            $db->quoteName('a.failed') . ' = ' . $db->quote('{}'),
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

      if(strpos($field, $prefix.'.') === false)
      {
        $fields[$key] = $prefix . '.' . $field;
      }
    }

    return $fields;
  }
}
