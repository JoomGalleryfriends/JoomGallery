<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Category;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryRawView;
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
      list($file_info, $resource) = $this->component->getFilesystem()->getResource($img_path);
    }
    catch (InvalidPathException $e)
    {
      $this->outputError(404, $e->getMessage());
    }

    // Output
    $this->outputResource($resource, $file_info->mime_type, $img_path, $file_info->size);
  }
}
