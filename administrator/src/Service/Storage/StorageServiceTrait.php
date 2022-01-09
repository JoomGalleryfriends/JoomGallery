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

use \Joomgallery\Component\Joomgallery\Administrator\Service\Storage\LocalStorage;

/**
* Trait to implement StorageServiceInterface
*
* @since  4.0.0
*/
trait StorageServiceTrait
{
  /**
	 * The storage class.
	 *
	 * @var StorageInterface
	 *
	 * @since  4.0.0
	 */
	private $storage = null;

  /**
	 * Returns the storage helper class.
	 *
	 * @return  StorageInterface
	 *
	 * @since  4.0.0
	 */
	public function getStorage(): StorageInterface
	{
		return $this->storage;
	}

  /**
	 * Creates the storage helper class
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
      $this->storage = new LocalStorage;
        break;
    }

    return;
	}
}
