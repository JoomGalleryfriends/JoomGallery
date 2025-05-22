<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

\defined('_JEXEC') or die;

use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Language\Text;
use \Joomla\Filesystem\Folder;
use \Joomla\Filesystem\Path;

/**
* Trait to implement MigrationServiceInterface
*
* @since  4.0.0
*/
trait MigrationServiceTrait
{
  /**
	 * Storage for the migration service class.
	 *
	 * @var MigrationInterface
	 *
	 * @since  4.0.0
	 */
	private $migration = null;

  /**
	 * Creates the migration service class
   * 
   * @param   string          $script    Name of the migration script to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
   * @throws Exception
	 */
	public function createMigration($script)
  {
    // Get list of scripts
    $scripts = $this->getScripts();

    // Check if selected script exists
    if(!\in_array($script, \array_keys($scripts)))
    {
      // Requested script does not exists
      $this->component->addLog(Text::_('COM_JOOMGALLERY_MIGRATION_SCRIPT_NOT_EXIST'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_MIGRATION_SCRIPT_NOT_EXIST'), 1);
    }

    // Create migration service based on provided migration script name
    require_once $scripts[$script]['path'];

    $namespace  = '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Service\\Migration\\Scripts';
    $fully_qualified_class_name = $namespace.'\\'.$script;
    $this->migration = new $fully_qualified_class_name;

    return;
  }

  /**
	 * Returns the migration service class.
	 *
	 * @return  MigrationInterface
	 *
	 * @since  4.0.0
	 */
	public function getMigration()
  {
    return $this->migration;
  }

  /**
	 * Method to get all available migration scripts.
	 *
	 * @return  array|boolean   List of paths of all available scripts.
	 *
	 * @since   4.0.0
	 */
  protected function getScripts()
  {
    $files = Folder::files(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/src/Service/Migration/Scripts', '.php$', false, true);

    $scripts = array();
    foreach($files as $path)
    {
      $img = Uri::base().'components/'._JOOM_OPTION.'/src/Service/Migration/Scripts/'.basename($path, '.php').'.jpg';

      $scripts[basename($path, '.php')] = array('path' => Path::clean($path), 'img' => $img);
    }

    return $scripts;
  }
}
