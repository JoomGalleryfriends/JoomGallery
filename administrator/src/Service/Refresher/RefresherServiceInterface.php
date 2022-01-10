<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Refresher;

\defined('JPATH_PLATFORM') or die;

/**
* The Storage service
*
* @since  4.0.0
*/
interface StorageServiceInterface
{
  /**
	 * Storage for the refresher class.
	 *
	 * @var RefresherInterface
	 *
	 * @since  4.0.0
	 */
	private $refresher;

  /**
	 * Creates the storage helper class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createStorage(): void;

	/**
	 * Returns the storage helper class.
	 *
	 * @return  StorageInterface
	 *
	 * @since  4.0.0
	 */
	public function getStorage(): StorageInterface;
}
