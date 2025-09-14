<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem;

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\Filesystem;

/**
* Trait to implement FilesystemServiceInterface
*
* @since  4.0.0
*/
trait FilesystemServiceTrait
{
  /**
	 * Storage for the filesystem helper class.
	 *
	 * @var FilesystemInterface
	 *
	 * @since  4.0.0
	 */
	private $filesystem = null;

  /**
	 * Returns the filesystem helper class.
	 *
	 * @return  FilesystemInterface
	 *
	 * @since  4.0.0
	 */
	public function getFilesystem(): FilesystemInterface
	{
		return $this->filesystem;
	}

  /**
	 * Creates the filesystem helper class
   *
   * @param   string  $filesystem  Name of the filesystem adapter to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createFilesystem($filesystem = ''): void
	{
    $this->filesystem = new Filesystem($filesystem);

    return;
	}
}
