<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem;

\defined('JPATH_PLATFORM') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\LocalFilesystem;

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
	public function getStorage(): FilesystemInterface
	{
		return $this->storage;
	}

  /**
	 * Creates the filesystem helper class
   *
   * @param   string  $filesystem  Name of the filesystem to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createStorage($filesystem = 'localhost'): void
	{
    switch ($filesystem)
    {
      default:
      $this->filesystem = new LocalFilesystem;
        break;
    }

    return;
	}
}
