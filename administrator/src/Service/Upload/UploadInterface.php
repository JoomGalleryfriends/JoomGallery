<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Upload;

\defined('JPATH_PLATFORM') or die;

/**
* Upload Interface for the helper classes
*
* @since  4.0.0
*/
interface UploadInterface
{
	/**
	 * Method to upload a new image.
	 *
	 * @return  string   Message
	 *
	 * @since  4.0.0
	 */
	public function upload(): string;
}
