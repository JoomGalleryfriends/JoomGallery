<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\TUSUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\FTPUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\HTMLUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\SingleUploader;

/**
* Trait to implement UploaderServiceInterface
*
* @since  4.0.0
*/
trait UploaderServiceTrait
{
  /**
	 * Storage for the Uploader class.
	 *
	 * @var UploaderInterface
	 *
	 * @since  4.0.0
	 */
	private $uploader = null;

  /**
	 * Returns the Uploader helper class.
	 *
	 * @return  UploaderInterface
	 *
	 * @since  4.0.0
	 */
	public function getUploader(): UploaderInterface
	{
		return $this->uploader;
	}

	/**
	 * Creates the Uploader helper class based on the selected upload method
	 *
   * @param   string  $uploadMethod   Name of the upload method to be used
	 * @param   bool    $multiple       True, if it is a multiple upload  (default: false)
	 * @param   bool    $async          True, if it is a asynchronous upload  (default: false)
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createUploader($uploadMethod, $multiple=false, $async=false): void
	{
    switch ($uploadMethod)
    {
      case 'TUS':
      case 'tus':
        $this->uploader = new TUSUploader($multiple, $async);
        break;

      case 'single':
        $this->uploader = new SingleUploader($multiple);
        break;

      case 'FTP':
      case 'ftp':
        $this->uploader = new FTPUploader($multiple, $async);
        break;

      default:
        $this->uploader = new HTMLUploader($multiple, $async);
        break;
    }

    return;
	}
}
