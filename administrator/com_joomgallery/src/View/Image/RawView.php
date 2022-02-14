<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * Raw view class for a single Image.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends JoomGalleryView
{
  /**
	 * Raw view display method, outputs one image
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
    $type = $this->app->input->get('type', 'detail', 'word');
    $id   = $this->app->input->get('id', 0, 'int');

    // Get image path
    $img = JoomHelper::getImg($id, $type, false);

    // Create filesystem service
    $this->component->createFilesystem();

    // Clean image path
    $img = $this->component->getFilesystem()->get('local_root') . $img;
    $this->component->getFilesystem()->cleanPath($img);

    // Download image from storage
    $this->component->getFilesystem()->downloadFile($img);

    // Check file
    if(!JFile::exists($img))
    {
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_COMMON_MSG_IMAGE_NOT_EXIST'), 'error');
      $this->app->redirect(Route::_('index.php', false), 404);
    }

    // Get mime type
    $info = getimagesize($img);
    switch($info[2])
    {
      case 1:
        $mime = 'image/gif';
       break;
      case 2:
        $mime = 'image/jpeg';
        break;
      case 3:
        $mime = 'image/png';
        break;
      default:
        $this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_COMMON_MSG_MIME_NOT_ALLOWED', $info[2]), 'error');
        $this->app->redirect(Route::_('index.php', false), 404);
        break;
    }

    // Set mime encoding
    $this->document->setMimeEncoding($mime);

    // Set header to specify the file name
    $disposition = 'inline';
    $this->app->setHeader('Content-disposition', $disposition.'; filename='.basename($img));

    echo \file_get_contents($img);
  }
}
