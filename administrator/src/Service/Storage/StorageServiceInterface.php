<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Storage;

\defined('JPATH_PLATFORM') or die;

/**
* The Storage service
*
* @since  4.0.0
*/
interface StorageServiceInterface
{
  /**
	 * Creates the storage helper class
   *
   * @param   string  $filesystem  Name of the filesystem to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createStorage($filesystem): void;

	/**
	 * Returns the storage helper class.
	 *
	 * @return  StorageInterface
	 *
	 * @since  4.0.0
	 */
	public function getStorage(): StorageInterface;
}
