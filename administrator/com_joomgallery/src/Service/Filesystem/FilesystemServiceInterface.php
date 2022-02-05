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

/**
* The Filesystem service
*
* @since  4.0.0
*/
interface FilesystemServiceInterface
{
  /**
	 * Storage for the filesystem helper class.
	 *
	 * @var FilesystemInterface
	 *
	 * @since  4.0.0
	 */
	private $filesystem;

  /**
	 * Creates the filesystem helper class
   *
   * @param   string  $filesystem  Name of the filesystem to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createFilesystem($filesystem): void;

	/**
	 * Returns the filesystem helper class.
	 *
	 * @return  FilesystemInterface
	 *
	 * @since  4.0.0
	 */
	public function getFilesystem(): FilesystemInterface;
}
