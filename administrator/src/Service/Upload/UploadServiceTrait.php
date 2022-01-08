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

use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\SingleUpload;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\AjaxUpload;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\BatchUpload;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Upload\FTPUpload;

/**
* Trait to implement UploadServiceInterface
*
* @since  4.0.0
*/
trait UploadServiceTrait
{
  /**
	 * The upload class.
	 *
	 * @var UploadInterface
	 *
	 * @since  4.0.0
	 */
	private $upload = null;

  /**
	 * Returns the upload helper class.
	 *
	 * @return  UploadInterface
	 *
	 * @since  4.0.0
	 */
	public function getUpload(): UploadInterface
	{
		return $this->upload;
	}

  /**
	 * Creates the upload helper class based on the selected upload method
	 *
   * @param   string  $uploadMethod  Name of the upload method to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createUpload($uploadMethod): void
	{
    switch ($uploadMethod)
    {
      case 'ajax':
        $this->upload = new AjaxUpload;
        break;

      case 'batch':
        $this->upload = new BatchUpload;
        break;

      case 'FTP':
      case 'ftp':
        $this->upload = new FTPUpload;
        break;

      default:
        $this->upload = new SingleUpload;
        break;
    }

    return;
	}
}
