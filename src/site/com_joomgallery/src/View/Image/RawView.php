<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View\Image;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomgallery\Component\Joomgallery\Administrator\View\Image\RawView as AdminRawView;
use Joomla\CMS\Language\Text;

/**
 * Raw view class for a single Image.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class RawView extends AdminRawView
{
  /**
   * The media object
   *
   * @var  \stdClass
   */
  protected $item;

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
    try
    {
      // Use the administrator image model that owns the shared processing pipeline.
      /** @var ImageModel $model */
    $model = $this->component->getMVCFactory()->createModel(
        'Image',
        'Administrator',
        ['ignore_request' => true]
    );
    $model->processImageResource(
        $file_info,
        $resource,
        (string) $imagetype,
        $this->component->getConfig(),
        'display',
        $options
    );
    }
    catch(\Throwable $e)
    {
    $this->component->addError(
        Text::sprintf(
            'COM_JOOMGALLERY_ERROR_DYNAMIC_IMAGE_PROCESSING',
            $e->getMessage(),
            (string) ($file_info->filename ?? '')
        )
    );

      return false;
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
    if($id === 'null') return true;

    $loaded = true;
    $access = true;

    /** @var ImageModel $model */
    $model = $this->getModel();

    try
    {
      $this->item = $model->getItem();
    }
    catch (\Exception $e)
    {
      $loaded = false;
    }

    // Check if the current user is the owner of the image
    if($loaded && $type == 'thumbnail' && $this->item->created_by == $this->getCurrentUser()->id)
    {
      // Current user is the owner. Show thumbnails anyway.
      return true;
    }

    // Check if category is protected?
    if($loaded && $model->getCategoryProtected())
    {
      $access = false;
    }

    // Check published state
    if(!$loaded || !$model->getCategoryPublished() || $this->item->published !== 1 || $this->item->approved !== 1)
    {
      $access = false;
    }

    // Check access view level
    if(!$model->getCategoryAccess() || !\in_array($this->item->access, $this->getCurrentUser()->getAuthorisedViewLevels()))
    {
      $access = false;
    }

    return $access;
  }
}
