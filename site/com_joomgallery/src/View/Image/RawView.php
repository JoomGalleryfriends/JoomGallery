<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\View\Image;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Registry\Registry;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\Image\RawView as AdminRawView;

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
	 * Postprocessing the image after retrieving the image ressource
	 *
	 * @param   \stdClass  $file_info    Object with file information
   * @param   resource   $resource     Image resource
   * @param   string     $imagetype    Type of image (original, detail, thumbnail, ...)
	 *
	 * @return  bool       True on success, false otherwise
	 */
  public function ppImage(&$file_info, &$resource, $imagetype)
  {
    // Get the current imagetype
    foreach(JoomHelper::getRecords('imagetypes', $this->component) as $key => $type)
    {
      if($type->typename == $imagetype)
      {
        $imagetype = $type;
      }
    }

    // Get dynamicprocessing params for this imagetype
    $params = false;
    foreach($this->component->getConfig()->get('jg_dynamicprocessing') as $key => $tmp_param)
    {
      if($tmp_param->jg_imgtypename == $imagetype->typename)
      {
        $params = new Registry($tmp_param);

        break;
      }
    }

    if($params === false || !$params->get('jg_imgtype', 0))
    {
      // Dynamic image processing is deactivated for this imagetype
      return true;
    }

    // Are there any params set which leads to dynamic processing?
    if( $params->get('jg_imgtypeorinet', 0) == 1 || $params->get('jg_imgtyperesize', 0) > 0 ||
        ($params->get('jg_imgtypewatermark', 0) == 1 && $this->component->getConfig()->get('jg_dynamic_watermark', 0))
      )
    {
      // Create the IMGtools service
      $this->component->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));

      // Read image resource
      if(!$this->component->getIMGtools()->read($resource, true))
      {
        // Destroy the IMGtools service
        $this->component->delIMGtools();

        // Put an error
        $this->component->addError(Text::sprintf('COM_JOOMGALLERY_ERROR_DYNAMIC_IMAGE_PROCESSING', 'Reading image resource', $file_info->filename));

        return false;
      }

      // Do we need to auto orient?
      if($params->get('jg_imgtypeorinet', 0) == 1)
      {
        // Yes
        if(!$this->component->getIMGtools()->orient())
        {
          // Destroy the IMGtools service
          $this->component->delIMGtools();

          // Put an error
          $this->component->addError(Text::sprintf('COM_JOOMGALLERY_ERROR_DYNAMIC_IMAGE_PROCESSING', 'Auto orienting', $file_info->filename));

          return false;
        }
      }

      // Need for resize?
      if($params->get('jg_imgtyperesize', 0) > 0)
      {
        // Yes
        if(!$this->component->getIMGtools()->resize($params->get('jg_imgtyperesize', 3),
                                            $params->get('jg_imgtypewidth', 5000),
                                            $params->get('jg_imgtypeheight', 5000),
                                            $params->get('jg_cropposition', 2),
                                            $params->get('jg_imgtypesharpen', 0))
          )
        {
          // Destroy the IMGtools service
          $this->component->delIMGtools();

          // Put an error
          $this->component->addError(Text::sprintf('COM_JOOMGALLERY_ERROR_DYNAMIC_IMAGE_PROCESSING', 'Resizing image', $file_info->filename));

          return false;
        }
      }

      // Need for watermarking?
      if($params->get('jg_imgtypewatermark', 0) == 1 && $this->component->getConfig()->get('jg_dynamic_watermark', 0))
      {
        // Yes
        if(!$this->component->getIMGtools()->watermark(JPATH_ROOT.\DIRECTORY_SEPARATOR.$this->component->getConfig()->get('jg_wmfile'),
                                                $params->get('jg_imgtypewtmsettings.jg_watermarkpos', 9),
                                                $params->get('jg_imgtypewtmsettings.jg_watermarkzoom', 0),
                                                $params->get('jg_imgtypewtmsettings.jg_watermarksize', 15),
                                                $params->get('jg_imgtypewtmsettings.jg_watermarkopacity', 80))
          )
        {
          // Destroy the IMGtools service
          $this->component->delIMGtools();

          // Put an error
          $this->component->addError(Text::sprintf('COM_JOOMGALLERY_ERROR_DYNAMIC_IMAGE_PROCESSING', 'Watermarking image', $file_info->filename));

          return false;
        }
      }

      $img_string = $this->component->getIMGtools()->stream($params->get('jg_imgtypequality', 100), false);
      $new_size   = \getimagesizefromstring($img_string);

      // Retrieve stream resource from image string
      $stream = \fopen('php://temp', 'r+');
      \fwrite($stream, $img_string);
      \rewind($stream);
      $stat = \fstat($stream);

      // Override file info
      $file_info->width  = $new_size[0];
      $file_info->height = $new_size[1];
      $file_info->size   = $stat['size'];

      // Override new, processed resource
      $resource = $stream;
    }

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
    if($id === 'null') return true;

    $loaded = true;
    $access = true;

    /** @var ImageModel $model */
    $model = $this->getModel();

		try {
			$this->item = $model->getItem();
		}
		catch (\Exception $e)
		{
			$loaded = false;
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
