<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\Registry\Registry;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\Database\DatabaseDriver;

/**
 * Migration table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MigrationTable extends Table
{
	/**
   * Migration progress (0-100)
   *
   * @var  int
   *
   * @since  4.0.0
   */
  public $progress = 0;

	/**
   * True if migration of this migrateable is completed
   *
   * @var  bool
   *
   * @since  4.0.0
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
		$this->typeAlias = _JOOM_OPTION.'.migration';

		parent::__construct(_JOOM_TABLE_MIGRATION, 'id', $db);

		// Initialize queue, successful and failed
		$this->queue      = array();
		$this->successful = new Registry();
		$this->failed     = new Registry();
    $this->counter    = new Registry();
	}

	/**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.0
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
	 * @since   4.0.0
	 */
	public function store($updateNulls = true)
	{
    // Support for queue field
    if(isset($this->queue) && !\is_string($this->queue))
		{
			$this->queue = \json_encode(array_values($this->queue), JSON_UNESCAPED_UNICODE);
		}

		// Support for successful field
    if(isset($this->successful) && !\is_string($this->successful))
		{
      $registry = new Registry($this->successful);
			$this->successful = (string) $registry;
		}

		// Support for failed field
    if(isset($this->failed) && !\is_string($this->failed))
		{
			$registry = new Registry($this->failed);
			$this->failed = (string) $registry;
		}

    // Support for counter field
    if(isset($this->counter) && !\is_string($this->counter))
		{
			$registry = new Registry($this->counter);
			$this->counter = (string) $registry;
		}

    // Support for params field
    if(isset($this->params) && !\is_string($this->params))
		{
			$registry = new Registry($this->params);
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
	 * @since   4.0.0
	 */
	public function bind($array, $ignore = '')
	{
    $date = Factory::getDate();

    // Support for queue field
    if(isset($array['queue']) && \is_array($array['queue']))
		{
			$array['queue'] = \json_encode($array['queue'], JSON_UNESCAPED_UNICODE);
		}

		// Support for successful field
    if(isset($array['successful']) && \is_array($array['successful']))
		{
      $registry = new Registry;
			$registry->loadArray($array['successful']);
			$array['successful'] = (string) $registry;
		}

		// Support for failed field
    if(isset($array['failed']) && \is_array($array['failed']))
		{
			$registry = new Registry;
			$registry->loadArray($array['failed']);
			$array['failed'] = (string) $registry;
		}

    // Support for counter field
    if(isset($array['counter']) && \is_array($array['counter']))
		{
			$registry = new Registry;
			$registry->loadArray($array['counter']);
			$array['counter'] = (string) $registry;
		}

    // Support for params field
    if(isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

    if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

    return parent::bind($array, array('progress', 'completed'));
  }

  /**
   * Method to perform sanity checks on the Table instance properties to ensure they are safe to store in the database.
   *
   * Child classes should override this method to make sure the data they are storing in the database is safe and as expected before storage.
   *
   * @return  boolean  True if the instance is sane and able to be stored in the database.
   *
   * @since   4.0.0
   */
  public function check()
  {
    // Support for queue field
    if(isset($this->queue))
    {
      if(\is_string($this->queue))
      {
        $this->queue = \json_decode($this->queue);
      }
      elseif(\is_object($this->queue))
      {
        $this->queue = ArrayHelper::fromObject($this->queue);
      }
    }

    // Support for successful field
    if(isset($this->successful))
    {
      if(\is_string($this->successful))
      {
        $this->successful = \json_decode($this->successful);
      }

      if(\is_object($this->successful))
      {
        if($this->successful instanceof Registry)
        {
          $this->successful = $this->successful->toArray();
        }
        else
        {
          $this->successful = ArrayHelper::fromObject($this->successful);
        }        
      }

      // Convert values to integer
      $this->successful = ArrayHelper::toInteger($this->successful);
      $this->successful = new Registry($this->successful);
    }

    // Support for failed field
    if(isset($this->failed))
    {
      $this->failed = new Registry($this->failed);
    }

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
   * @since   4.0.0
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
   * @since   4.0.0
   */
  public function clcProgress()
  {
    // Calculate progress property
    $total    = \count($this->queue) + $this->successful->count() + $this->failed->count();
    $finished = $this->successful->count() + $this->failed->count();

    if($total > 0)
    {
      $this->progress = (int) \round((100 / $total) * ($finished));
    }   

    // Update completed property
    if($total === $finished || $total == 0)
    {
      $this->completed = true;
    }
  }
}
