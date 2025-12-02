<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Category;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Router\Route;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\Component\Media\Administrator\Exception\InvalidPathException;
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
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
    // Get request variables
    $type = $this->app->input->get('type', 'thumbnail', 'word');
    $id   = $this->app->input->get('id', 0, 'int');

    // Get image path
    $img_path = JoomHelper::getCatImg($id, $type, false, false);

    // Create filesystem service
    $adapter = '';
    if($id === 0)
    {
      // Force local-images adapter to load the no-image file
      $adapter = 'local-images';
    }
    $this->component->createFilesystem($adapter);

    // Get image resource
    try
    {
      list($file_info, $ressource) = $this->component->getFilesystem()->getResource($img_path);
    }
    catch (InvalidPathException $e)
    {
      $this->app->enqueueMessage($e, 'error');
      $this->app->redirect(Route::_('index.php', false), 404);
    }

    // Set mime encoding
    $this->getDocument()->setMimeEncoding($file_info->mime_type);

    // Set header to specify the file name
    $this->app->setHeader('Cache-Control','no-cache, must-revalidate');
    $this->app->setHeader('Pragma','no-cache');
    $this->app->setHeader('Content-disposition','inline; filename='.\basename($img_path));
    $this->app->setHeader('Content-Length',\strval($file_info->size));

    \ob_end_clean(); //required here or large files will not work
    \fpassthru($ressource);
  }
}
