<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools;

\defined('_JEXEC') or die;

use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Filesystem\Folder;
use \Joomla\CMS\Filesystem\Path;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtools as BaseIMGtools;

/**
 * JoomGallery IMGtools Class
 * Class for image processing and metadata handling
 *
 * Image processor
 * IM: https://imagemagick.org/script/convert.php
 *
 * @package JoomGallery
 *
 * @author  Manuel Häusler (tech.spuur@quickline.ch)
 *
 * @since   3.5.0
 */
class IMtools extends BaseIMGtools implements IMGtoolsInterface
{
  /**
   * Path to the ImageMagick terminal tool if its not a system variable
   * default: ''
   *
   * @var string
   */
  public $impath = '';

  /**
   * Path to the convert comment in the terminal
   *
   * @var string
   */
  public $convert_path = '';

  /**
   * ImageMagick commands
   *
   * @var array
   */
  public $commands = array();

  /**
   * Resize method (0:noresize,1:height,2:width,3:proportional,4:crop)
   *
   * @var int
   */
  public $method = 0;

  /**
   * True, if image gets watermarked
   *
   * @var bool
   */
  public $watermarking = false;

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($debugoutput, $keep_metadata=false, $keep_anim=false, $impath = '')
  {
    parent::__construct($debugoutput, $keep_metadata, $keep_anim);

    $this->impath = $impath;
  }

  /**
   * Read image from file or image string (stream)
   * Supported image-types: depending on IM version
   *
   * @param   string  $file        Path to source file or image string
   * @param   bool    $is_stream   True if $src is image string (stream) (default: false)
   * @param   bool    $base64      True if input string is base64 decoded (default: false)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function read($file, $is_stream = false, $base64 = false): bool
  {
    // Reset commands
    $this->commands = array();

    // Check, if exec command is available
    $disabled_functions = explode(',', ini_get('disabled_functions'));
    foreach($disabled_functions as $disabled_function)
    {
      if(trim($disabled_function) == 'exec')
      {
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_EXEC_DISABLED').'<br />';

        return false;
      }
    }

    // Check availability and version of ImageMagick
    @exec(trim($this->impath).'convert -version', $output_convert);
    @exec(trim($this->impath).'magick -version', $output_magick);

    if($output_magick)
    {
      // use new version (>= v7.x) if available
      $this->convert_path = trim($this->impath).'magick convert';
    }
    else
    {
      if($output_convert)
      {
        // otherwise use old version (<= v6.x)
        $this->convert_path = trim($this->impath).'convert';
      }
      else
      {
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_NOTFOUND').'<br />';

        return false;
      }
    }

    // Prepare input string
    if(!$is_stream)
    {
      $file = Path::clean($file);

      if(!\file_exists($file))
      {
        $file = JPATH_ROOT.\DIRECTORY_SEPARATOR.$file;

        $file = Path::clean($file);
      }
    }

    if($is_stream && $base64)
    {
      $file = base64_decode($file);
    }

    // Analysis and validation of the source image
    if($this->analyse($file, $is_stream) == false)
    {
      $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_FILE').'<br />';

      return false;
    }

    // Store source file
    $this->src_file = $file;

    if(!$this->keep_anim)
    {
      $this->res_imginfo['frames'] = 1;
    }

    // Delete all metadata, if needed
    if(!$this->keep_metadata)
    {
      $this->commands['strip'] = ' -strip';
    }

    $this->debugoutput .= 'Image processor: ImageMagick<br/>';

    return true;
  }

  /**
   * Write image to file
   * Supported image-types: depending on IM version
   *
   * @param   string  $file     Path to destination file
   * @param   int     $quality  Quality of the resized image (1-100)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function write($file, $quality): bool
  {
    // Define image type to write
    $type = \strtoupper(\explode('.',$file,-1));
    if($type)
    {
      $this->dst_type = $type;
    }
    else
    {
      $this->dst_type = $this->src_type;
    }

    // Set output quality
    $this->commands['quality'] = ' -quality "'.$this->dst_imginfo['quality'].'"';

    // Rotate image, if needed (use auto-orient command)
    if($this->auto_orient)
    {
      $this->commands['auto-orient'] = ' -auto-orient';

      $this->debugoutput .= Text::_('COM_JOOMGALLERY_AUTOORIENT_IMAGE').'<br />';
    }

    if($this->auto_orient && $this->method == 3)
    {
      $this->commands['repage'] = ' +repage';
    }

    // Delete all metadata, if needed
    if(!$this->keep_metadata)
    {
      $this->commands['strip'] = ' -strip';
    }

    // assemble the shell command
    $convert = $this->assemble($file);

    // strip [0] from src_file
    $this->src_file = \str_replace('[0]','',$this->src_file);

    $return_var = null;
    $dummy      = null;
    $filecheck  = true;

    // execute the resize
    @exec($convert, $dummy, $return_var);

    // Check that the resized image is valid
    if(!$this->checkValidImage($file))
    {
      $filecheck  = false;
    }

    // Workaround for servers with wwwrun problem
    if($return_var != 0 || !$filecheck)
    {
      $dir = dirname($file);
      //JoomFile::chmod($dir, '0777', true);
      Path::setPermissions(Path::clean($dir), null, '0777');

      // Execute the resize
      @exec($convert, $dummy, $return_var);

      //JoomFile::chmod($dir, '0755', true);
      Path::setPermissions(Path::clean($dir), null, '0755');

      // Check that the resized image is valid
      if(!$this->checkValidImage($file))
      {
        $filecheck = false;
      }

      if($return_var != 0 || !$filecheck)
      {
        $this->debugoutput .= Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';
        $this->rollback($this->src_file, $file);

        return false;
      }
    }

    // Clean up working area (frames and imginfo)
    $this->clearVariables();

    return true;
  }

  /**
   * Output image as string (stream)
   * Supported image-types: ??
   *
   * @param   int     $quality  Quality of the resized image (1-100)
   * @param   string  $type     Set image type to write (default: same as source)
   * @param   bool    $base64   True if output string gets base64 decoded
   *
   * @return  string  image stream
   *
   * @since   4.0.0
   */
  public function stream($quality, $type=false, $base64 = false): string
  {
    return 'Not yet implemented';
  }

  /**
   * Resize image
   * Supported image-types: depending on IM version
   *
   * @param   int     $method         Resize to 0:noresize,1:height,2:width,3:proportional,4:crop
   * @param   int     $width          Width to resize
   * @param   int     $height         Height to resize
   * @param   int     $cropposition   Image section to be used for cropping (if settings=3)
   *                                  (0:upperleft,1:upperright,2:center,3:lowerleft,4:lowerright)
   * @param   bool    $unsharp        true=sharpen the image during procession
   *
   * @return  boolean True on success, false otherwise
   *
   * @since   1.0.0
   */
  public function resize($method, $width, $height, $cropposition, $unsharp): bool
  {
    // Prepare working area (imginfo)
    $this->src_imginfo = $this->res_imginfo;

    // store method
    $this->method = $method;

    // Get destination orientation
    $this->dst_imginfo['orientation'] = $this->src_imginfo['orientation'];

    // Conditions where no resize is needed
    $noResize = false;
    if($this->src_imginfo['orientation'] == $this->dst_imginfo['orientation'])
    {
      // dst and src same orientation
      if($method == 0 || ($this->src_imginfo['width'] <= $width && $this->src_imginfo['height'] <= $height))
      {
        $noResize = true;
      }
    }
    else
    {
      // dst and src different orientation
      if($method == 0 || ($this->src_imginfo['width'] <= $height && $this->src_imginfo['height'] <= $width))
      {
        $noResize = true;
      }
    }

    if($noResize)
    {
      $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_RESIZE_NOT_NECESSARY').'<br />';

      return true;
    }

    // Generate informations about type, dimension and origin of resized image
    if(!($dst_imginfo = $this->getResizeInfo($this->src_type, $method, $width, $height, $cropposition)))
    {
      $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';

      return false;
    }

    // Create debugoutput
    switch($method)
    {
      case 1:
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_HEIGHT');
        break;
      case 2:
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_WIDTH');
        break;
      case 3:
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_MAX');
        break;
      case 4:
        // Free resizing and cropping
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_CROP');
        break;
      default:
        break;
    }

    if($this->src_imginfo['animation']  && !$this->keep_anim)
    {
      // If resizing an animation but not preserving the animation, consider only first frame
      $this->src_file = $this->src_file.'[0]';
    }
    else
    {
      if($this->src_imginfo['animation']  && $this->keep_anim && $this->src_imginfo['type'] == 'GIF')
      {
        // If resizing an animation, use coalesce for better results
        $this->commands['coalesce'] = ' -coalesce';
      }
    }

    // Crop the source image before resiszing if offsets setted before
    // example of crop: convert input -crop destwidthxdestheight+offsetx+offsety +repage output
    // +repage needed to delete the canvas
    if($method == 3)
    {
      // Assembling the imagick command for cropping
      $this->commands['crop'] = ' -crop "'.$this->dst_imginfo['src']['width'].'x'.$this->dst_imginfo['src']['height'].'+'.$this->dst_imginfo['offset_x'].'+'.$this->dst_imginfo['offset_y'].'" +repage';
    }

    if(!$noResize)
    {
      // Assembling the imagick command for resizing if resizing is needed
      $this->commands['resize'] = ' -resize "'.$this->dst_imginfo['width'].'x'.$this->dst_imginfo['height'].'"';
    }

    if($unsharp)
    {
      // Assembling the imagick command for the unsharp masking
      $this->commands['unsharp'] = ' -unsharp "3.5x1.2+1.0+0.10"';
    }

    // Clean up working area (imginfo)
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    return true;
  }

  /**
   * Rotate image
   * Supported image-types: depending on IM version
   *
   * @param   int     $angle          Angle to rotate the image anticlockwise
   * @param   bool    $auto_orient    Auto orient image based on exif orientation (jpg only)
   *
   * @return  bool    True on success, false otherwise (false, if no rotation is needed)
   *
   * @since   3.4.0
   */
  public function rotate($angle, $auto_orient = false): bool
  {
    // Prepare working area (imginfo)
    $this->src_imginfo = $this->res_imginfo;

    if($angle == 0 && !$auto_orient)
    {
      // Nothing to do
      $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_ROTATE_NOT_NECESSARY').'<br />';

      return true;
    }

    // Definition of type, dimension and origin of rotated image
    $this->dst_imginfo['width']       = $this->dst_imginfo['src']['width'] = $this->src_imginfo['width'];
    $this->dst_imginfo['height']      = $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];
    $this->dst_imginfo['orientation'] = $this->src_imginfo['orientation'];
    $this->dst_imginfo['offset_x']    = 0;
    $this->dst_imginfo['offset_y']    =  0;

    // Get rotation angle
    if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
    {
      $this->auto_orient = true;
    }
    else
    {
      if($this->dst_imginfo['angle'] == 0 && $this->dst_imginfo['flip'] == 'none')
      {
        // Nothing to do
        $this->debugoutput .= Text::_('COM_JOOMGALLERY_UPLOAD_ROTATE_NOT_NECESSARY').'<br />';

        return true;
      }

      $this->dst_imginfo['angle'] = $angle;
      $this->dst_imginfo['flip']  = 'none';
    }

    if($this->src_imginfo['animation']  && !$this->keep_anim)
    {
      // If resizing an animation but not preserving the animation, consider only first frame
      $this->src_file = $this->src_file.'[0]';
    }
    else
    {
      if($this->src_imginfo['animation']  && $this->keep_anim && $this->src_imginfo['type'] == 'GIF')
      {
        // If resizing an animation, use coalesce for better results
        $this->commands['coalesce'] = ' -coalesce';
      }
    }

    if(!$this->auto_orient && $this->dst_imginfo['angle'] > 0)
    {
      $this->commands['rotate'] = ' -rotate "-'.$angle.'"';
      $this->debugoutput = Text::sprintf('COM_JOOMGALLERY_ROTATE_BY_ANGLE', $angle).'<br />';
    }

    // Clean up working area (imginfo)
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    return true;
  }

  /**
   * Add watermark to an image
   * Supported image-types: ??
   *
   * @param   string  $wtm_file       Path to watermark file
   * @param   int     $wtm_pos        Positioning of the watermark
   *                                  (1:topleft,2:topcenter,3:topright,4:middleleft,5:middlecenter
   *                                   6:middleright,7:bottomleft,8:bottomcenter,9:bottomright)
   * @param   int     $wtm_resize     resize watermark (0:noresize,1:height,2:width,3:proportional)
   * @param   int     $wtm_newSize    new size of the resized watermark in percent related to the file (1-100)
   * @param   int     $opacity        opacity of the watermark on the image in percent (0-100 / 0:invisible,100:fullcoverage)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   3.5.1
   */
  public function watermark($wtm_file, $wtm_pos, $wtm_resize, $wtm_newSize, $opacity): bool
  {
    // Ensure that the watermark path is valid and clean
    $wtm_file = Path::clean($wtm_file);

    // Checks if watermark file is existent
    if(!File::exists($wtm_file))
    {
      $this->debugoutput .= Text::_('COM_JOOMGALLERY_COMMON_ERROR_WATERMARK_NOT_EXIST');

      return false;
    }

    // Analysis and validation of the source watermark-image
    if(!($this->src_imginfo = $this->analyse($wtm_file)))
    {
      $this->debugoutput .= Text::_('COM_JOOMGALLERY_COMMON_OUTPUT_INVALID_WTM_FILE').'<br />';

      return false;
    }

    // Generate informations about type, dimension and origin of resized image
    $position = $this->getWatermarkingInfo($this->res_imginfo, $wtm_pos, $wtm_resize, $wtm_newSize);

    // Create debugoutput
    $this->debugoutput .= Text::_('COM_JOOMGALLERY_COMMON_OUTPUT_WATERMARK_IMAGE');

    // Set watermark hint
    $this->watermarking = true;

    if($this->res_imginfo['animation'] && $this->keep_anim && $this->res_imginfo['type'] == 'GIF')
    {
      // TODO: resize of watermark when its animation
      // Positioning of the watermark
      $commands['wtm-pos'] = ' "'.$this->src_file.'" -coalesce -gravity "northwest" -geometry "+'.$position[0].'+'.$position[1].'" null:';

      // copy watermark on top of image
      $commands['watermark'] = ' "'.$wtm_file.'" -layers composite -layers optimize "{dst_file}"';
    }
    else
    {
      if($this->res_imginfo['animation'] && !$this->keep_anim)
      {
        // If resizing an animation but not preserving the animation, consider only first frame
        $this->src_file = $this->src_file.'[0]';
      }

      // Resize watermark file
      $this->commands['wtm-resize'] = ' "'.$wtm_file.'" -resize "'.$this->dst_imginfo['width'].'x'.$this->dst_imginfo['height'].'"';

      // Positioning of the watermark
      $commands['wtm-pos'] = ' "'.$this->src_file.'" +swap -gravity "northwest" -geometry "+'.$position[0].'+'.$position[1].'"';

      // copy watermark on top of image
      $commands['watermark'] = ' -define compose:args='.$opacity.',100 -compose dissolve -composite'.' "{dst_file}"';
    }

    return true;
  }

  protected function assemble($file): string
  {
    // assemble the commands
    $commands = '';

    if(\isset($this->commands['coalesce']))
    {
      $commands .= $this->commands['coalesce'];
    }

    if(\isset($this->commands['auto-orient']))
    {
      $commands .= $this->commands['auto-orient'];
    }

    if(\isset($this->commands['repage']))
    {
      $commands .= $this->commands['repage'];
    }

    if(\isset($this->commands['strip']))
    {
      $commands .= $this->commands['strip'];
    }

    if(\isset($this->commands['crop']))
    {
      $commands .= $this->commands['crop'];
    }

    if(\isset($this->commands['resize']))
    {
      $commands .= $this->commands['resize'];
    }

    if(\isset($this->commands['quality']))
    {
      $commands .= $this->commands['quality'];
    }

    if(\isset($this->commands['unsharp']))
    {
      $commands .= $this->commands['unsharp'];
    }

    // Assembling the shell code for the resize with imagick
    $convert = $this->convert_path.' '.$commands.' "'.$this->src_file.'" "'.$file.'"';

    return $convert;
  }
}
