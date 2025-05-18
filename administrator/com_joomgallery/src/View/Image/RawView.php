<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Router\Route;
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
	 * @throws \Exception
	 */
	public function display($tpl = null)
	{
    // Get request variables
    $type  = $this->app->input->get('type', 'thumbnail', 'word');
    $id    = $this->app->input->get('id', 0);
    if($id !== 'null') {$id = $this->app->input->get('id', 0, 'int');}

    // Check access
    if(!$this->access($id))
    {
      $this->app->redirect(Route::_('index.php', false), 403);
    }

    // Choose the filesystem adapter
    $adapter = '';
    if($id === 0 || $id === 'null')
    {
      // Force local-images adapter to load the no-image file
      $id      = 0;
      $adapter = 'local-images';
    }
    else
    {
      // Take the adapter from the image object
      $img_obj = $this->get('Item');
      $adapter = $img_obj->filesystem;
    }
    
    // Get image path
    if(isset($img_obj))
    {
      $img = $img_obj;
    }
    else
    {
      $img = $id;
    }
    $img_path = JoomHelper::getImg($img, $type, false, false);

    // Create filesystem service    
    $this->component->createFilesystem($adapter);

    // Get image resource
    try
    {
      list($file_info, $resource) = $this->component->getFilesystem()->getResource($img_path);
    }
    catch (InvalidPathException $e)
    {
      $this->app->enqueueMessage($e, 'error');
      $this->app->redirect(Route::_('index.php', false), 404);
    }

    // Create config service
    $this->component->createConfig('com_joomgallery.image', $id);

    // Postprocessing of the image
    if(!$this->ppImage($file_info, $resource, $type))
    {
      $this->app->redirect(Route::_('index.php', false), 404);
    }    

    // Set mime encoding to document
    $this->getDocument()->setMimeEncoding($file_info->mime_type);

    // Set header to specify the file name
    $this->app->setHeader('Content-Type', $file_info->mime_type);
    $this->app->setHeader('Content-Disposition','inline; filename='.\basename($img_path));
    $this->app->setHeader('Content-Length',\strval($file_info->size));
    $this->app->setHeader('Cache-Control','no-cache, must-revalidate');
    $this->app->setHeader('Pragma','no-cache');

    // Required for large files to work properly
    if(\ob_get_level() > 0) \ob_end_clean();

    \fpassthru($resource);
    \fclose($resource);
  }

  /**
	 * Postprocessing the image after retrieving the image resource
	 *
	 * @param   \stdClass  $file_info    Object with file information
   * @param   resource   $resource     Image resource
   * @param   string     $imagetype    Type of image (original, detail, thumbnail, ...)
	 *
	 * @return  bool       True on success, false otherwise
	 */
  public function ppImage(&$file_info, &$resource, $imagetype)
  {
    return true;
  }

  /**
	 * Check access to this image
	 *
	 * @param   int  $id    Image id
	 *
	 * @return   bool    True on success, false otherwise
	 */
  protected function access($id)
  {
    return true;
  }
}
