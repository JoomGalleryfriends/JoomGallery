<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Image;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryRawView;
use Joomla\CMS\Language\Text;
use Joomla\Component\Media\Administrator\Exception\InvalidPathException;

/**
 * Raw view class for a single Image.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends JoomGalleryRawView
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
    $type   = $this->app->input->get('type', 'thumbnail', 'word');
    $id     = $this->app->input->get('id', 0);
    $base64 = $this->app->input->get('base64', 0);

    $options = new \stdClass();
    if($resize = $this->app->input->get('resize', 0))
    {
      $options->resize = $resize;
      $options->resize_type = $this->app->input->get('resize_type', 3);
    }

    if($id == 0 || $id == '0')
    {
      $id = 'null';
    }

    if($id !== 'null')
    {
      $id = $this->app->input->get('id', 0, 'int');
    }

    // Check access
    if(!$this->access($id, $type))
    {
      $this->outputError(403, Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'));
    }

    /** @var ImageModel $model */
    $model = $this->getModel();

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
      $img_obj = $model->getItem();
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
      $this->outputError(404, $e->getMessage());
    }

    // Create config service
    $this->component->createConfig('com_joomgallery.image', $id);

    // Postprocessing of the image
    if(!$this->ppImage($file_info, $resource, $type, $options))
    {
      $this->outputError(404, 'Error postprocessing the image');
    }

    // Increment hits counter
    if($this->app->isClient('site'))
    {
      $record_hits        = (bool) $this->component->getConfig()->get('jg_record_hits', 1);
      $record_hits_select = (array) $this->component->getConfig()->get('jg_record_hits_select');

      if($record_hits && \in_array($type, $record_hits_select))
      {
        $model->hit();
      }
    }

    // Output
    $this->outputResource($resource, $file_info->mime_type, $img_path, $file_info->size);
  }

  /**
   * Postprocessing the image after retrieving the image resource
   *
   * @param   \stdClass    $file_info    Object with file information
   * @param   resource     $resource     Image resource
   * @param   string       $imagetype    Type of image (original, detail, thumbnail, ...)
   * @param   null|object  $options      Additional options for post processing
   *
   * @return  bool       True on success, false otherwise
   */
  public function ppImage(&$file_info, &$resource, $imagetype, $options = null)
  {
    if(\property_exists($options, 'resize'))
    {
      // Get component object
      $com = JoomHelper::getComponent();

      // Create the IMGtools service
      $com->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));

      // Reread ressource
      $com->getIMGtools()->read($resource, true);

      // Resize image, resize by max dimension
      $com->getIMGtools()->resize($options->resize_type, $options->resize, $options->resize);
      $img_string = $com->getIMGtools()->stream(100, false);

      // Retrieve stream resource from image string
      $stream = fopen('php://temp', 'r+');
      fwrite($stream, $img_string);
      rewind($stream);

      // Override new, processed resource
      $resource = $stream;
    }

    return true;
  }

  /**
   * Check access to this image
   *
   * @param   int     $id    Image id
   * @param   string  $type  Imagetype
   *
   * @return   bool    True on success, false otherwise
   */
  protected function access($id, $type = 'thumbnail')
  {
    return true;
  }
}
