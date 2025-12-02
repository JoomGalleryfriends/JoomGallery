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

use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\NoAssetTableTrait;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\User\UserFactoryInterface;
use \Joomla\Database\DatabaseDriver;
use \Joomla\Registry\Registry;

/**
 * User table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class UserTable extends Table
{
  use JoomTableTrait;
  use NoAssetTableTrait;
  use MigrationTableTrait;

  /**
   * Constructor
   *
   * @param   JDatabase  &$db               A database connector object
   * @param   bool       $component_exists  True if the component object class exists
   */
  public function __construct(DatabaseDriver $db, bool $component_exists = true)
  {
    $this->component_exists = $component_exists;
    $this->typeAlias        = _JOOM_OPTION.'.user';

    parent::__construct(_JOOM_TABLE_USERS, 'id', $db);
  }

  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   array  $array   Named array
   * @param   mixed  $ignore  Optional array or list of parameters to ignore
   *
   * @return  boolean  True on success.
   *
   * @see     Table:bind
   * @since   4.0.0
   * @throws  \InvalidArgumentException
   */
  public function bind($array, $ignore = '')
  {
    $date = Factory::getDate();

    if($array['id'] == 0)
    {
      $array['created_time'] = $date->toSql();
    }

    if(!key_exists('cmsuser', $array) || empty($array['cmsuser']))
    {
      $array['cmsuser'] = Factory::getApplication()->getIdentity()->id;
    }

    if(isset($array['params']) && \is_array($array['params']))
    {
      $registry = new Registry;
      $registry->loadArray($array['params']);
      $array['params'] = (string) $registry;
    }

    if(isset($array['files']) && \is_array($array['files']))
    {
      $registry = new Registry;
      $registry->loadArray($array['files']);
      $array['files'] = (string) $registry;
    }

    // Support for collections
    if(!isset($this->collections))
    {
      $this->collections = [];
    }

    // Support for favourites
    if(!isset($this->favourites))
    {
      $this->favourites = [];
    }

    return parent::bind($array, $ignore);
  }

  /**
   * Overloaded check function
   *
   * @return bool
   */
  public function check()
  {
    // Support for subform field params
    if(empty($this->params))
    {
      $this->params = $this->loadDefaultField('params');
    }

    if(isset($this->params))
    {
      $this->params = new Registry($this->params);
    }

    // Support for subform field files
    if(empty($this->files))
    {
      $this->files = $this->loadDefaultField('files');
    }

    if(isset($this->files))
    {
      $this->files = new Registry($this->files);
    }

    // Support for field description
    if(empty($this->description))
    {
      $this->description = $this->loadDefaultField('description');
    }

    return parent::check();
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
    // Support for params field
    if(isset($this->params) && !\is_string($this->params))
    {
      $registry     = new Registry($this->params);
      $this->params = (string) $registry;
    }

    // Support for files field
    if(isset($this->files) && !\is_string($this->files))
    {
      $registry    = new Registry($this->files);
      $this->files = (string) $registry;
    }

    return parent::store($updateNulls);
  }

  /**
   * Method to return the title to use for the asset table.
   *
   * @return  string
   *
   * @since 4.0.0
   * @see Joomla\CMS\Table\Table::_getAssetTitle
   */
  protected function _getAssetTitle($itemtype = null)
  {
    $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->cmsuser);

    return $user->name;
  }
}
