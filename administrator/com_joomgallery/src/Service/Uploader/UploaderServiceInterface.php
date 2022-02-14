<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

\defined('JPATH_PLATFORM') or die;

/**
* The Uploader service
*
* @since  4.0.0
*/
interface UploaderServiceInterface
{
  /**
	 * Storage for the Uploader class.
	 *
	 * @var UploaderInterface
	 *
	 * @since  4.0.0
	 */
	private $uploader;

  /**
	 * Creates the Uploader helper class based on the selected upload method
	 *
   * @param   string  $uploadMethod  Name of the upload method to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createUploader($uploadMethod,): void;

	/**
	 * Returns the Uploader helper class.
	 *
	 * @return  UploadInterface
	 *
	 * @since  4.0.0
	 */
	public function getUploader(): UploadInterface;
}
