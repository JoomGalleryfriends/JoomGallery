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

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;

/**
* Uploader helper class (Batch Upload)
*
* @since  4.0.0
*/
class BatchUploader extends BaseUploader implements UploaderInterface
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
    return 'Batch upload successfully!';
  }
}
