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

use \Joomla\CMS\Factory;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\UploadInterface;

/**
* Base class for the upload helper classes
*
* @since  4.0.0
*/
class Upload implements UploadInterface
{
  /**
	 * Method to get the direction for a given item.
	 *
	 * @return  void
	 *
	 * @since  4.0.0
	 */
	public function debugoutput(): void
  {
    Factory::getApplication()->enqueueMessage('These are debug information of the upload.', 'message');

    return;
  }

	/**
	 * Method to upload a new image.
	 *
	 * @return  string   Message
	 *
	 * @since  4.0.0
	 */
	public function upload(): string
  {
    return 'Error: Please choose an upload method!';
  }
}
