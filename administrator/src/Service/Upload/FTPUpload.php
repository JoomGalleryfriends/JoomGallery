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

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\UploadInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\Upload as BaseUpload;

/**
* Upload helper class (FTP Upload)
*
* @since  4.0.0
*/
class FTPUpload extends BaseUpload implements UploadInterface
{
	/**
	 * Method to upload a new image.
	 *
	 * @return  string   Message
	 *
	 * @since  4.0.0
	 */
	public function upload(): string
  {
    return 'FTP upload successfully!';
  }
}
