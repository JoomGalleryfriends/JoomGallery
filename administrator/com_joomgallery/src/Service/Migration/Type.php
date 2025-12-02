<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
 * Type Class
 * Providing information about a content type being migrated
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Type
{
  use ServiceTrait;

  /**
   * Name of this content type
   *
   * @var  string
   *
   * @since  4.0.0
   */
  public $name = 'image';

  /**
   * Name of the corresponding record
   *
   * @var  string
   *
   * @since  4.0.0
   */
  public $recordName = 'image';

  /**
   * Source table name of this content type
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $tablename = '#__joomgallery';

  /**
   * Name of the primary key
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $pk = 'id';

  /**
   * Name of the title field
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $title = 'title';

  /**
   * Name of the owner field
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $ownerFieldname = 'created_by';

  /**
   * True if this content type is nested
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $nested = false;

  /**
   * True if this content type has categories
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $categorized = true;

  /**
   * List of primary keys that dont need migration and can be skipped.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $pkstoskip = [0];

  /**
   * List of types this type depends on.
   * They have to be migrated before this one.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $dependent_on = [];

  /**
   * List of types depending on this type.
   * This type has to be migrated before the dependent ones.
   *
   * @var  array
   *
   * @since  4.0.0
   */
  protected $dependent_of = [];

  /**
   * Table name used to load the queue
   *
   * @var  string
   *
   * @since  4.0.0
   */
  protected $queueTablename = '#__joomgallery';

  /**
   * Do we have to create/inert new database records for this type?
   * If no this type is just an adjustment of data within the destination table.
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $insertRecord = true;

  /**
   * Do we need a db counter to be able to migrate this type?
   * $counter = {pk = counter_value}
   *
   * @var  boolean
   *
   * @since  4.0.0
   */
  protected $counterNeeded = false;

  /**
   * Constructor
   *
   * @param  string  $name  Name of this content type
   * @param  array   $list  Source types info created by Migration::defineTypes()
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($name, $list)
  {
    $this->name       = $name;
    $this->recordName = $name;

    if(\count($list) < 5)
    {
      $this->component->addLog('Invalid migration skript. Type object needs a list of at least 5 entries as the second argument.', 'error', 'jerror');
      throw new \Exception('Invalid migration skript. Type object needs a list of at least 5 entries as the second argument.', 1);
    }

    $this->tablename      = $list[0];
    $this->queueTablename = $list[0];
    $this->pk             = $list[1];
    $this->title          = $list[2];
    $this->nested         = $list[3];
    $this->categorized    = $list[4];
  }

  /**
   * Pushes types to the dependent_of based on the provided list of Type objects.
   *
   * @param   Type[]   $types    List of Type objects
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setDependentOf($types)
  {
    // search for types depending on this type
    if(!empty($types))
    {
      foreach($types as $type_name => $type)
      {
        if($type && \count($type->get('dependent_on')) > 0 && \in_array($this->name, $type->get('dependent_on')))
        {
          array_push($this->dependent_of, $type_name);
        }
      }
    }
  }

  /**
   * Modifies a property of the object, creating it if it does not already exist.
   *
   * @param   string  $property  The name of the property.
   * @param   mixed   $value     The value of the property to set.
   *
   * @return  mixed  Previous value of the property.
   *
   * @since   4.0.0
   */
  public function set($property, $value = null)
  {
    switch($property)
    {
      case 'pkstoskip':
        $previous = $this->$property ?? null;

        if(\is_array($value))
        {
          $this->$property = array_merge($this->$property, $value);
        }
        else
        {
          array_push($this->$property, $value);
        }
        break;

      default:
        $previous        = $this->$property ?? null;
        $this->$property = $value;
        break;
    }

    return $previous;
  }
}
