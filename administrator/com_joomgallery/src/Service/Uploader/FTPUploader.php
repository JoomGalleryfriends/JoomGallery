<?php
/**
  * *********************************************************************************
  *    @package    com_joomgallery                                                 **
  *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
  *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
  *    @license    GNU General Public License version 3 or later                   **
  * *********************************************************************************
  */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;

/**
  * Uploader helper class (FTP Upload)
  *
  * @since  4.0.0
  */
class FTPUploader extends BaseUploader implements UploaderInterface
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
