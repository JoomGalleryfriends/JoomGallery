<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Table\Asset\GlobalAssetTableTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Task table
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class TaskTable extends Table
{
  use LegacyDatabaseTrait;
  use GlobalAssetTableTrait;

  /**
   * Task progress (0-100)
   *
   * @var  int
   *
   * @since  4.2.0
   */
  public $progress = 0;

  /**
   * True if migration of this migrateable is completed
   *
   * @var  bool
   *
   * @since  4.2.0
   */
  public $completed = false;

  /**
   * Constructor
   *
   * @param   JDatabase  &$db               A database connector object
   * @param   bool       $component_exists  True if the component object class exists
   */
  public function __construct(DatabaseDriver $db, bool $component_exists = true)
  {
    $this->component_exists = $component_exists;
    $this->typeAlias        = _JOOM_OPTION.'.task';

    parent::__construct(_JOOM_TABLE_TASKS, 'id', $db);

    // Initialize queue, successful and failed
    $this->queue      = [];
    $this->successful = new Registry();
    $this->failed     = new Registry();
    $this->counter    = new Registry();
  }

  /**
   * Get the type alias for the history table
   *
   * @return  string  The alias as described above
   *
   * @since   4.2.0
   */
  public function getTypeAlias()
  {
    return $this->typeAlias;
  }

    /**
     * Method to store a row in the database from the Table instance properties.
     *
     * If a primary key value is set the row with that primary key value will be updated with the instance property values.
     * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
     *
     * @param   boolean  $updateNulls  True to update fields even if they are null.
     *
     * @return  boolean  True on success.
     *
     * @since   4.2.0
     */
    public function store($updateNulls = true)
    {
    // Support for counter field
    if(isset($this->counter) && !\is_string($this->counter))
    {
      $registry      = new Registry($this->counter);
      $this->counter = (string) $registry;
    }

    // Support for params field
    if(isset($this->params) && !\is_string($this->params))
    {
      $registry     = new Registry($this->params);
      $this->params = (string) $registry;
    }

    return parent::store($updateNulls);
  }

  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   array  $array   Named array
   * @param   mixed  $ignore  Optional array or list of parameters to ignore
   *
   * @return  boolean  True on success.
   *
   * @since   4.2.0
   */
  public function bind($array, $ignore = '')
  {
    $date = Factory::getDate();

    // Support for title field: title
    if(\array_key_exists('title', $array))
    {
      $array['title'] = trim($array['title']);

      if(empty($array['title']))
      {
        $array['title'] = $this->getSchedulerTask((int) $array['taskid'])->title;
      }
    }

    // Support for counter field
    if(isset($array['counter']) && \is_array($array['counter']))
    {
      $registry = new Registry();
      $registry->loadArray($array['counter']);
      $array['counter'] = (string) $registry;
    }

    // Support for params field
    if(isset($array['params']) && \is_array($array['params']))
    {
      $registry = new Registry();
      $registry->loadArray($array['params']);
      $array['params'] = (string) $registry;
    }

    if($array['id'] == 0)
    {
      $array['created_time'] = $date->toSql();
    }

    return parent::bind($array, ['progress', 'completed']);
  }

  /**
   * Method to perform sanity checks on the Table instance properties to ensure they are safe to store in the database.
   *
   * Child classes should override this method to make sure the data they are storing in the database is safe and as expected before storage.
   *
   * @return  boolean  True if the instance is sane and able to be stored in the database.
   *
   * @since   4.2.0
   */
  public function check()
  {
    // Support for counter field
    if(isset($this->counter))
    {
      $this->counter = new Registry($this->counter);
    }

    // Support for params field
    if(isset($this->params))
    {
      $this->params = new Registry($this->params);
    }

    // Support for completed field
    if(isset($this->completed))
    {
      $this->completed = \intval($this->completed);
    }

    // Support for last_execution field
    if(isset($this->last_execution) && empty($this->last_execution))
    {
      $this->last_execution = null;
    }

    return parent::check();
  }

  /**
   * Method to load a row from the database by primary key and bind the fields to the Table instance properties.
   *
   * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.
   *                           If not set the instance property value is used.
   * @param   boolean  $reset  True to reset the default values before loading the new row.
   *
   * @return  boolean  True if successful. False if row not found.
   *
   * @see     Table:bind
   * @since   4.2.0
   */
  public function load($keys = null, $reset = true)
  {
    $success = parent::load($keys, $reset);

    if($success)
    {
      // Bring table to the correct form
      $this->check();

      // Calculate progress and completed state
      $this->clcProgress();
    }

    return $success;
  }

  /**
   * Method to calculate progress and completed state.
   *
   * @return  void
   *
   * @since   4.2.0
   */
  public function clcProgress()
  {
    // Calculate progress property
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    // Get Counts per status
    $query->select('status, COUNT(*) AS count')
          ->from($db->quoteName('#__joomgallery_task_items'))
          ->where($db->quoteName('task_id').' = '.(int)$this->id)
          ->group('status');

    $db->setQuery($query);
    $results = $db->loadObjectList('status');

    // Set Counts
    $this->count_pending = $results['pending']->count ?? 0;
    $this->count_success = $results['success']->count ?? 0;
    $this->count_failed  = $results['failed']->count ?? 0;
    $count_processing    = $results['processing']->count ?? 0;

    $total    = $this->count_pending + $this->count_success + $this->count_failed + $count_processing;
    $finished = $this->count_success + $this->count_failed;

    if($total > 0)
    {
      $this->progress = (int) round((100 / $total) * ($finished));
    }
    else
    {
      $this->progress = 0;
    }

    if($total > 0 && $this->count_pending === 0 && $count_processing === 0)
    {
      $this->completed = true;
    }
    else
    {
      $this->completed = false;
    }
  }

  /**
   * Method to get a task object by id
   *
   * @param   int  $id  Task type
   *
   * @return  object    The task object.
   *
   * @since   4.2.0
   */
  protected function getSchedulerTask(int $id)
  {
    // Get a db connection.
    $db = $this->getDatabase();

    // Create a new query object.
    $query = $db->getQuery(true);

    // Select all records from the scheduler tasks table where type is matching.
    $query->select('*');
    $query->from($db->quoteName('#__scheduler_tasks'));
    $query->where(($db->quoteName('id')).'='.$db->quote($id));

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    // Load the result as a stdClass object.
    return $db->loadObject();
  }
}
