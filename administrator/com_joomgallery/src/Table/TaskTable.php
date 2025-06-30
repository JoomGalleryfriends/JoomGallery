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
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\Registry\Registry;
use \Joomla\Database\DatabaseDriver;

/**
 * Task table
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class TaskTable extends MigrationTable
{
  use GlobalAssetTableTrait;
  
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db               A database connector object
	 * @param   bool       $component_exists  True if the component object class exists
	 */
	public function __construct(DatabaseDriver $db, bool $component_exists = true)
	{
		$this->component_exists = $component_exists;
		$this->typeAlias = _JOOM_OPTION.'.task';

		parent::__construct(_JOOM_TABLE_TASKS, 'id', $db);

    // Initialize queue, successful and failed
		$this->queue      = array();
		$this->successful = new Registry();
		$this->failed     = new Registry();
    $this->counter    = new Registry();
	}
}
