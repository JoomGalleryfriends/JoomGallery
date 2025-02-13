<?php
/**
******************************************************************************************
**   @version    4.0.0-beta1                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\Database\DatabaseDriver;
use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\GlobalAssetTableTrait;

/**
 * Vote table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class VoteTable extends Table
{
  use JoomTableTrait;
  use GlobalAssetTableTrait;
  use MigrationTableTrait;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db             A database connector object
	 * @param   bool       $with_component  True to attach component object to class
	 */
	public function __construct(DatabaseDriver $db, bool $with_component = true)
	{
		if($with_component)
		{
		  $this->component = Factory::getApplication()->bootComponent('com_joomgallery');
		}
		else
		{
		  $this->addMessageTrait();
		}
    
		$this->typeAlias = _JOOM_OPTION.'.vote';

		parent::__construct(_JOOM_TABLE_VOTES, 'id', $db);
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

    if($array['id'] == 0 && (!\key_exists('created_by', $array) || empty($array['created_by'])))
		{
			$array['created_by'] = Factory::getApplication()->getIdentity()->id;
		}

		return parent::bind($array, $ignore);
	}

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->_trackAssets = false;
    
    return parent::delete($pk);
  }
}
