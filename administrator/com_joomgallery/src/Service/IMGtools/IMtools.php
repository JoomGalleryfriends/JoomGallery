<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\Filesystem\File;
use \Joomla\Filesystem\Path;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtools as BaseIMGtools;

/**
 * IMGtools Class (IM)
 * 
 * Provides methods to do image processing and metadata handling
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
   * Random number between 1000 and 9999
   * Used for generating the temp files
   *
   * @var string
   */
  protected $rndNumber = '0';

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($keep_metadata=false, $keep_anim=false, $impath = '')
  {
    parent::__construct($keep_metadata, $keep_anim);

    $this->impath    = $impath;
    $this->rndNumber = \strval(\mt_rand(1000, 9999));
  }

  /**
   * Version notes
   *
   * @return  false|string  Version string on success false otherwise
   *
   * @since   4.0.0
   */
  public function version()
  {
    // Check, if exec command is available
    $disabled_functions = \explode(',', \ini_get('disable_functions'));

    foreach($disabled_functions as $disabled_function)
    {
      if(trim($disabled_function) == 'exec')
      {
        return false;
      }
    }

    // Check availability and version of ImageMagick v7.x
    @\exec(\trim($this->impath).'magick -version', $output);

    if($output)
    {
      // new version (>= v7.x)
      return \str_replace(array('Version: ', ' http://www.imagemagick.org'), array('',''), $output[0]);
    }
    else
    {
      // Check availability and version of ImageMagick v6.x
      @\exec(\trim($this->impath).'convert -version', $output);

      if($output)
      {
        // old version (<= v6.x)
        return \str_replace(array('Version: ', ' http://www.imagemagick.org'), array('',''), $output[0]);
      }
      else
      {
        return false;
      }
    }
  }

  /**
   * Add information of currently used image processor to debug output
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function info(): void
  {
    if($version = $this->version)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_USED_PROCESSOR', $version));

      return;
    }
    else
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_IM_NOTFOUND'));

      return;
    }
  }

  /**
   * Read image from file or image string (stream)
   * Supported image-types: depending on IM version
   *
   * @param   string|resource  $file        Path to source file or image string or resource
   * @param   bool             $is_stream   True if $src is image string (stream) (default: false)
   * @param   bool             $base64      True if input string is base64 decoded (default: false)
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
    $disabled_functions = \explode(',', \ini_get('disable_functions'));

    foreach($disabled_functions as $disabled_function)
    {
      if(\trim($disabled_function) == 'exec')
      {
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_EXEC_DISABLED'));

        return false;
      }
    }

    // Check availability and version of ImageMagick
    @\exec(\trim($this->impath).'convert -version', $output_convert);
    @\exec(\trim($this->impath).'magick -version', $output_magick);

    if($output_magick)
    {
      // use new version (>= v7.x) if available
      $this->convert_path = \trim($this->impath).'magick convert';

      $version = \str_replace(array('Version: ', ' http://www.imagemagick.org'), array('',''), $output_magick[0]);
    }
    else
    {
      if($output_convert)
      {
        // otherwise use old version (<= v6.x)
        $this->convert_path = \trim($this->impath).'convert';

        $version = \str_replace(array('Version: ', ' http://www.imagemagick.org'), array('',''), $output_convert[0]);
      }
      else
      {
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_IM_NOTFOUND'));

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
      $file = \base64_decode($file);
    }

    // Analysis and validation of the source image
    if($this->analyse($file, $is_stream) == false)
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_INVALID_IMAGEFILE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_IMAGEFILE'), 'error', 'jerror');

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

    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_USED_PROCESSOR', $version));

    return true;
  }

  /**
   * Write image to file
   * Supported image-types: depending on IM version
   *
   * @param   string  $file     Path to destination file
   * @param   int     $quality  Quality of the resized image (1-100, default: 100)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function write($file, $quality=100): bool
  {
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Define image type to write
    $path_parts = \pathinfo($file);
    $type = $path_parts['extension'];
    $type = \strtoupper($type);
    if($type)
    {
      $this->dst_type = $type;
    }
    else
    {
      $this->dst_type = $this->src_type;
    }

    // Create destination file path
    if(\strpos($file, JPATH_ROOT) === false)
    {
      $file = JPATH_ROOT . '/' . $file;
    }
    $file = Path::clean($file);

    // Set output quality
    $this->commands['quality'] = ' -quality "'.$quality.'"';

    // Rotate image, if needed (use auto-orient command)
    if($this->auto_orient)
    {
      $this->commands['auto-orient'] = ' -auto-orient';

      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_AUTOORIENT_IMAGE'));
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

    /// Remove '[0]' from src_file
    $this->src_file = \str_replace('[0]','',$this->src_file);

    $return_var = null;
    $dummy      = null;
    $filecheck  = true;

    // execute the resize
    @\exec($convert, $dummy, $return_var);

    // Check that the resized image is valid
    if(!$this->checkValidImage($file))
    {
      $filecheck  = false;
    }

    // Workaround for servers with wwwrun problem
    if($return_var != 0 || !$filecheck)
    {
      $dir = \dirname($file);
      //JoomFile::chmod($dir, '0777', true);
      Path::setPermissions(Path::clean($dir), null, '0777');

      // Execute the resize
      @\exec($convert, $dummy, $return_var);

      //JoomFile::chmod($dir, '0755', true);
      Path::setPermissions(Path::clean($dir), null, '0755');

      // Check that the resized image is valid
      if(!$this->checkValidImage($file))
      {
        $filecheck = false;
      }

      if($return_var != 0 || !$filecheck)
      {
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SERVERPROBLEM_EXEC','exec('.$convert.');'));
        $this->rollback($this->src_file, $file);

        return false;
      }
    }

    // Debugoutput: shell command
    if($this->app->get('debug', false))
    {
      $this->component->addDebug('<strong>Shell command:</strong><br />'.$convert);
    }

    // If there is watermarking perform this last
    if($this->watermarking)
    {
      // Perform watermarking
      $wtm_files = $this->execWatermarking($file, $file);

      if(!\is_file(Path::clean($wtm_files['dst_file'])))
      {
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'), 'error', 'jerror');

        return false;
      }

      // Overwrite src_file property
      $this->src_file = $wtm_files['dst_file'];
    }

    // Debugoutput: success
    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_MANIPULATION_SUCCESSFUL'));

    // Delete watermarked temp file if existing
    if($this->watermarking && \is_file(Path::clean($wtm_files['dst_file'])) && \strpos($wtm_files['dst_file'], 'tmp_wtm_img') !== false)
    {
      File::delete($wtm_files['dst_file']);
    }

    // Delete resized watermark file
    if($this->watermarking && \is_file(Path::clean($wtm_files['wtm_file'])))
    {
      File::delete($wtm_files['wtm_file']);
    }

    // Clean up working area (frames and imginfo)
    $this->clearVariables();

    return true;
  }

  /**
   * Output the image as string (stream)
   * Supported image-types: ??
   *
   * @param   int     $quality  Quality of the resized image (1-100, default: 100)
   * @param   bool    $base64   String encoded with base64 (default: false)
   * @param   bool    $html     Return html string for direct output (default: false)
   * @param   string  $type     Set image type to write (default: same as source)
   *
   * @return  string  base64 encoded image string or html string
   *
   * @since   4.0.0
   */
  public function stream($quality=100, $base64=false, $html=false, $type=false): string
  {
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Define image type to write
    if($type)
    {
      $this->dst_type = \strtoupper($type);
    }
    else
    {
      $this->dst_type = $this->src_type;
    }

    // Temporary remove '[0]' from src_file
    $first_frame_only = false;
    if(\strpos($this->src_file, '[0]') !== false)
    {
      $this->src_file = \str_replace('[0]','',$this->src_file);
      $first_frame_only = true;
    }    

    // Define temporary image file to be created
    $tmp_folder   = $this->app->get('tmp_path');
    $tmp_dst_file = $tmp_folder.'/tmp_dst_img_'.$this->rndNumber.'.'.\strtolower($this->dst_type);

    // Create temporary source file from stream
    $tmp_src_file = $tmp_folder.'/tmp_src_img_'.$this->rndNumber.'.'.\strtolower($this->src_type);    
    if(\is_resource($this->src_file))
    {
      // We are dealing with a streamed resource
      \rewind($this->src_file);
    }
    if(\is_string($this->src_file) && \file_exists($this->src_file))
    {
      // We are dealing with a file path - copy it.
      File::copy($this->src_file, $tmp_src_file);
    }
    else
    {
      // We are dealing with a file content in a string - write it.
      \file_put_contents($tmp_src_file, $this->src_file);
    }
    
    // Redefine scr_file variable
    $this->src_file = $tmp_src_file;

    // Select only first frame if to not keep animation
    if($first_frame_only)
    {
      // If resizing an animation but not preserving the animation, consider only first frame
      $this->onlyFirstFrame();
    }

    if(!$this->write($tmp_dst_file, $quality))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_OUTPUT_IMAGE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_OUTPUT_IMAGE'), 'error', 'jerror');
      $this->rollback($tmp_src_file, $tmp_dst_file);

      return false;
    }

    $stream = \file_get_contents($tmp_dst_file);

    // Delete temporary image files
    if(\file_exists($tmp_dst_file))
    {
      File::delete($tmp_dst_file);
    }
    if(\file_exists($tmp_src_file))
    {
      File::delete($tmp_src_file);
    }

    if(!$base64)
    {
      // Output plain image stream
      return $stream;
    }

    // Base64 encoding
    $stream = \base64_encode($stream);
    if($stream === false)
    {
      // Error
      return false;
    }

    // Completing the image string
    switch ($this->dst_type)
    {
      case 'PNG':
        $stream = 'data:image/png;base64,'.$stream;
        break;

      case 'GIF':
        $stream = 'data:image/gif;base64,'.$stream;
        break;

      case 'JPEG':
      case 'JPG':
      default:
        $stream = 'data:image/jpeg;base64,'.$stream;
        break;
    }

    if($html)
    {
      return '<img src="'.$stream.'" />';
    }
    else
    {
      return $stream;
    }

    return '';
  }

  /**
   * Resize image
   * Supported image-types: depending on IM version
   *
   * @param   int     $method         Resize to 0:noresize,1:height,2:width,3:proportional,4:crop
   * @param   int     $width          Width to resize
   * @param   int     $height         Height to resize
   * @param   int     $cropposition   Image section to be used for cropping (if settings=3)
   *                                  (0:upperleft,1:upperright,2:center,3:lowerleft,4:lowerright) default:2
   * @param   bool    $unsharp        true=sharpen the image during procession (default:false)
   *
   * @return  boolean True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function resize($method, $width, $height, $cropposition=2, $unsharp=false): bool
  {
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

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
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_NOT_NEEDED'));

      return true;
    }

    // Generate information about type, dimension and origin of resized image
    if(!($this->getResizeInfo($this->src_type, $method, $width, $height, $cropposition)))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_INVALID_IMAGEFILE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_IMAGEFILE'), 'error', 'jerror');

      return false;
    }

    // Create debugoutput
    switch($method)
    {
      case 1:
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_TO_HEIGHT'));
        break;
      case 2:
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_TO_WIDTH'));
        break;
      case 3:
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_TO_MAX'));
        break;
      case 4:
        // Free resizing and cropping
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_TO_CROP'));
        break;
      default:
        break;
    }

    if($this->src_imginfo['animation']  && !$this->keep_anim)
    {
      // If resizing an animation but not preserving the animation, consider only first frame
      $this->onlyFirstFrame();
    }
    else
    {
      if($this->src_imginfo['animation']  && $this->keep_anim && $this->src_type == 'GIF')
      {
        // If resizing an animation, use coalesce for better results
        $this->commands['coalesce'] = ' -coalesce';
      }
    }

    // Crop the source image before resiszing if offsets set before
    // example of crop: convert input -crop destwidthxdestheight+offsetx+offsety +repage output
    // +repage needed to delete the canvas
    if($method == 4)
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
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Prepare working area (imginfo)
    $this->src_imginfo = $this->res_imginfo;

    if($angle == 0 && !$auto_orient)
    {
      // Nothing to do
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ROTATE_NOT_NEEDED'));

      return true;
    }

    // Definition of type, dimension and origin of rotated image
    $this->dst_imginfo['width']       = $this->dst_imginfo['src']['width'] = $this->src_imginfo['width'];
    $this->dst_imginfo['height']      = $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];
    $this->dst_imginfo['orientation'] = $this->src_imginfo['orientation'];
    $this->dst_imginfo['offset_x']    = 0;
    $this->dst_imginfo['offset_y']    = 0;

    // Get rotation angle
    if($auto_orient && isset($this->src_imginfo['exif']['IFD0']['Orientation']))
    {
      $this->auto_orient = true;

      return true;
    }
    else
    {
      if($angle == 0 && $this->dst_imginfo['flip'] == 'none')
      {
        // Nothing to do
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ROTATE_NOT_NEEDED'));

        return true;
      }

      $this->dst_imginfo['angle'] = $angle;
      $this->dst_imginfo['flip']  = 'none';
    }

    if($this->src_imginfo['animation']  && !$this->keep_anim)
    {
      // If resizing an animation but not preserving the animation, consider only first frame
      $this->onlyFirstFrame();
    }
    else
    {
      if($this->src_imginfo['animation']  && $this->keep_anim && $this->src_type == 'GIF')
      {
        // If resizing an animation, use coalesce for better results
        $this->commands['coalesce'] = ' -coalesce';
      }
    }

    if(!$this->auto_orient && $this->dst_imginfo['angle'] > 0)
    {
      $this->commands['rotate'] = ' -rotate "-'.$angle.'"';
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ROTATE_BY_ANGLE', $angle));
    }

    // Clean up working area (imginfo)
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    return true;
  }

  /**
   * Flip image
   * Supported image-types: depending on IM version
   *
   * @param   int     $direction       Direction to flip the image (0:none,1:horizontal,2:vertical,3:both)
   *
   * @return  bool    True on success, false otherwise (false, if no flipping is needed)
   *
   * @since   4.0.0
   */
  public function flip($direction): bool
  {
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Prepare working area (imginfo)
    $this->src_imginfo = $this->res_imginfo;

    if($direction == 0)
    {
      // Nothing to do
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_FLIP_NOT_NEEDED'));

      return true;
    }

    // Get info for destination frames
    switch ($direction)
    {
      case 1:
        $this->dst_imginfo['flip']  = 'horizontally';
        break;

      case 2:
        $this->dst_imginfo['flip']  = 'vertically';
        break;

      case 3:
        $this->dst_imginfo['flip']  = 'both';
        break;

      default:
        $this->dst_imginfo['flip']  = 'none';
        break;
    }
    $this->dst_imginfo['width']       = $this->dst_imginfo['src']['width'] = $this->src_imginfo['width'];
    $this->dst_imginfo['height']      = $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];
    $this->dst_imginfo['orientation'] = $this->src_imginfo['orientation'];
    $this->dst_imginfo['offset_x']    = 0;
    $this->dst_imginfo['offset_y']    = 0;
    $this->dst_imginfo['angle']       = 0;
    $this->dst_imginfo['quality']     = 100;

    if($this->src_imginfo['animation']  && !$this->keep_anim)
    {
      // If resizing an animation but not preserving the animation, consider only first frame
      $this->onlyFirstFrame();
    }
    else
    {
      if($this->src_imginfo['animation']  && $this->keep_anim && $this->src_type == 'GIF')
      {
        // If resizing an animation, use coalesce for better results
        $this->commands['coalesce'] = ' -coalesce';
      }
    }

    // Capture commands
    $this->commands['flip'] = '';

    if($this->dst_imginfo['flip'] == 'vertically' || $this->dst_imginfo['flip'] == 'both')
    {
      $this->commands['flip'] .= ' -flip';
    }

    if($this->dst_imginfo['flip'] == 'horizontally' || $this->dst_imginfo['flip'] == 'both')
    {
      $this->commands['flip'] .= ' -flop';
    }

    // Add debugoutput
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FLIP_BY', $this->dst_imginfo['flip']));

    // Clean up working area (imginfo)
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    return true;
  }

  /**
   * Auto orientation of the image based on EXIF meta data
   * Supported image-types: depending on IM version
   *
   * @return  bool    True on success, false otherwise (true, if no orientation is needed)
   *
   * @since   4.0.0
   */
  public function orient(): bool
  {
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    $this->auto_orient = true;

    if(isset($this->metadata['exif']['IFD0']['Orientation']))
    {
      $this->autoOrient($this->metadata['exif']['IFD0']['Orientation']);
    }

    return true;
  }

  /**
   * Add watermark to an image
   * Supported image-types: depending on IM version
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
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }
    
    // Ensure that the watermark path is valid and clean
    $wtm_file = Path::clean($wtm_file);
    if(!\file_exists($wtm_file))
    {
      $wtm_file = JPATH_ROOT.\DIRECTORY_SEPARATOR.$wtm_file;

      $wtm_file = Path::clean($wtm_file);
    }

    // Checks if watermark file is existent
    if(!\is_file(Path::clean($wtm_file)))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARK_NOT_EXIST'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARK_NOT_EXIST'), 'error', 'jerror');

      return false;
    }

    // Analysis and validation of the source watermark-image
    $tmp_res_imginfo = $this->res_imginfo;
    $tmp_src_type    = $this->src_type;
    if(!($this->src_imginfo = $this->analyse($wtm_file)))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_INVALID_WTMFILE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_WTMFILE'), 'error', 'jerror');

      return false;
    }

    // Restore important info
    $this->res_imginfo = $tmp_res_imginfo;
    $this->wtm_type    = $this->src_type;
    $this->src_type    = $tmp_src_type;
    $this->dst_type    = $tmp_src_type;

    // Create debugoutput
    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_WATERMARKING'));

    // Set watermark hint
    $this->watermarking = true;

    // Set watermarking settings
    $this->wtm_imginfo = array('wtm_pos'=>$wtm_pos, 'wtm_resize'=>$wtm_resize, 'wtm_newSize'=>$wtm_newSize, 'imginfo'=>$this->src_imginfo);

    if($this->res_imginfo['animation'] && $this->keep_anim && $this->dst_type == 'GIF')
    {
      // Resize watermark file
      $this->commands['wtm-resize'] = ' -resize "{widthxheight}" "'.$wtm_file.'" "{tmp_wtm_file}"';

      // Positioning of the watermark
      $this->commands['wtm-pos'] = ' "{src_file}" -coalesce -gravity "northwest" -geometry "{+position[0]+$position[1]}" null:';

      // copy watermark on top of image
      $this->commands['watermark'] = ' "{tmp_wtm_file}" -layers composite -layers optimize "{dst_file}"';
    }
    else
    {
      if($this->res_imginfo['animation'] && !$this->keep_anim)
      {
        // If resizing an animation but not preserving the animation, consider only first frame
        $this->onlyFirstFrame();
      }

      // Resize watermark file
      $this->commands['wtm-resize'] = ' "'.$wtm_file.'" -resize "{widthxheight}"';

      // Positioning of the watermark
      $this->commands['wtm-pos'] = ' "{src_file}" +swap -gravity "northwest" -geometry "{+position[0]+$position[1]}"';

      // copy watermark on top of image
      $this->commands['watermark'] = ' -define compose:args='.$opacity.',100 -compose dissolve -composite'.' "{dst_file}"';
    }

    return true;
  }

  /**
   * Get supported image types
   *
   * @return  array   list of supported image types (uppercase)
   *
   * @since   4.0.0
   */
  public function getTypes(): array
  {
    // Check, if exec command is available
    $disabled_functions = \explode(',', \ini_get('disable_functions'));

    foreach($disabled_functions as $disabled_function)
    {
      if(trim($disabled_function) == 'exec')
      {
        return array();
      }
    }

    // Get supported types of ImageMagick v7.x
    @\exec(\trim($this->impath).'magick -list format', $output);

    if(!$output)
    {
      // Get supported types of ImageMagick v6.x
      @\exec(\trim($this->impath).'convert -list format', $output);
    }

    if(!$output)
    {
      return array();
    }

    // skip first two lines of output
    \array_splice($output, 0, 2);

    // skip last four lines of output
    \array_splice($output, -4);

    $types = array();
    foreach ($output as $key => $line)
    {
      // skip empty line
      if($line === '')
      {
        continue;
      }

      $pos = \strpos($line, '           ');

      // skip lines starting with huge space
      if(\strpos($line, '           ') === 0)
      {
        continue;
      }

      // replace spaces with ';'
      $line = \preg_replace('!\s+!', ';', $line);

      // split string by separator ';'
      $temp_arr = explode(';', $line, 3);

      // remove '*' from type
      $type = \str_replace('*', '', $temp_arr[1]);

      // add second value of array to types
      array_push($types, $type);
    }

    return $types;
  }

  //////////////////////////////////////////////////
  //   Protected functions with basic features.
  //////////////////////////////////////////////////

  /**
   * Assemble the convert command
   * Supported image-types: depending on IM version
   *
   * @param   string   $file     Path to the destination file
   * 
   * @return  string   Convert command
   *
   * @since   4.0.0
   */
  protected function assemble($file): string
  {
    // assemble the commands
    $commands = '';

    if(isset($this->commands['coalesce']))
    {
      $commands .= $this->commands['coalesce'];
    }

    if(isset($this->commands['auto-orient']))
    {
      $commands .= $this->commands['auto-orient'];
    }

    if(isset($this->commands['repage']))
    {
      $commands .= $this->commands['repage'];
    }

    if(isset($this->commands['strip']))
    {
      $commands .= $this->commands['strip'];
    }

    if(isset($this->commands['rotate']))
    {
      $commands .= $this->commands['rotate'];
    }

    if(isset($this->commands['flip']))
    {
      $commands .= $this->commands['flip'];
    }

    if(isset($this->commands['crop']))
    {
      $commands .= $this->commands['crop'];
    }

    if(isset($this->commands['resize']))
    {
      $commands .= $this->commands['resize'];
    }

    if(isset($this->commands['quality']))
    {
      $commands .= $this->commands['quality'];
    }

    if(isset($this->commands['unsharp']))
    {
      $commands .= $this->commands['unsharp'];
    }

    // Assembling the shell code for the resize with imagick
    $convert = $this->convert_path.' '.$commands.' "'.$this->src_file.'" "'.$file.'"';

    return $convert;
  }

  /**
   * Watermarking an image and store it to the given path.
   * -- A temporary watermarked image will be created if no path is given.
   * -- The global source file is used if no source if given
   * Supported image-types: depending on IM version
   * 
   * @param   string   $src_file   File to be watermarked (default: $this->src_file)
   * @param   string   $dst_file   Path where the watermarked file is stored (default: temp-file creation)
   *
   * @return  mixed   Paths to the created files on success, false otherwise
   *
   * @since   4.0.0
   */
  protected function execWatermarking($src_file=false, $dst_file=false)
  {
    // Check image availability
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Prepare working area (imginfo)
    $this->src_imginfo = $this->wtm_imginfo['imginfo'];

    // Generate information about type, dimension and origin of watermark
    $position = $this->getWatermarkingInfo($this->res_imginfo, $this->wtm_imginfo['wtm_pos'], $this->wtm_imginfo['wtm_resize'], $this->wtm_imginfo['wtm_newSize']);

    // If we are manipulating a animated image and watermaks needs resize
    // do first a resize
    $wtm_file = '';
    if($this->res_imginfo['animation'] && $this->keep_anim && $this->dst_type == 'GIF' && isset($this->commands['wtm-resize']))
    {
      $wtm_file = $this->execWatermarkResize();
    }

    // assemble the commands
    $commands = '';

    if(isset($this->commands['wtm-resize']))
    {
      
      $this->commands['wtm-resize'] = \str_replace('{widthxheight}', $this->dst_imginfo['width'].'x'.$this->dst_imginfo['height'], $this->commands['wtm-resize']);
      $commands .= $this->commands['wtm-resize'];
    }

    if(isset($this->commands['wtm-pos']))
    {
      if(!$src_file)
      {
        // Use src_file if no other is given
        $src_file   = $this->src_file; 
      }

      // Get watermarking source file
      Path::clean($src_file);
      $this->commands['wtm-pos'] = \str_replace('{src_file}', $src_file, $this->commands['wtm-pos']);
      
      $this->commands['wtm-pos'] = \str_replace('{+position[0]+$position[1]}', '+'.$position[0].'+'.$position[1], $this->commands['wtm-pos']);

      $commands .= $this->commands['wtm-pos'];
    }


    if(isset($this->commands['watermark']))
    {
      if(!$dst_file)
      {
        // Define temporary image file to be created
        $tmp_folder = $this->app->get('tmp_path');
        $dst_file   = $tmp_folder.'/tmp_wtm_img_'.$this->rndNumber.'.'.\strtolower($this->src_type); 
      }

      Path::clean($dst_file);
      $this->commands['watermark'] = \str_replace('{dst_file}', $dst_file, $this->commands['watermark']);

      $commands .= $this->commands['watermark'];
    }

    // Assembling the shell code for the resize with imagick
    $convert = $this->convert_path.' '.$commands;

    $return_var = null;
    $dummy      = null;
    $filecheck  = true;

    // execute the resize
    @\exec($convert, $dummy, $return_var);

    // Check that the resized image is valid
    if(!$this->checkValidImage($dst_file))
    {
      $filecheck  = false;
    }

    // Workaround for servers with wwwrun problem
    if($return_var != 0 || !$filecheck)
    {
      $dir = \dirname($dst_file);
      //JoomFile::chmod($dir, '0777', true);
      Path::setPermissions(Path::clean($dir), null, '0777');

      // Execute the resize
      @\exec($convert, $dummy, $return_var);

      //JoomFile::chmod($dir, '0755', true);
      Path::setPermissions(Path::clean($dir), null, '0755');

      // Check that the resized image is valid
      if(!$this->checkValidImage($dst_file))
      {
        $filecheck = false;
      }

      if($return_var != 0 || !$filecheck)
      {
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SERVERPROBLEM_EXEC','exec('.$convert.');'));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_SERVERPROBLEM_EXEC','exec('.$convert.');'), 'error', 'jerror');
        $this->rollback($this->src_file, $dst_file);

        return false;
      }
    }

    // Debugoutput: shell command
    if($this->app->get('debug', false))
    {
      $this->component->addDebug('<strong>Shell command (watermarking):</strong><br />'.$convert);
    }

    return array('dst_file'=>$dst_file, 'wtm_file'=>$wtm_file);
  }

  /**
   * Watermarking image and store it as temp file
   * Supported image-types: depending on IM version
   *
   * @return  string   Path to the created temp file
   *
   * @since   4.0.0
   */
  protected function execWatermarkResize(): string
  {
    // Define temporary image file to be created
    $tmp_folder = $this->app->get('tmp_path');
    $tmp_file   = $tmp_folder.'/tmp_wtm_'.$this->rndNumber.'.'.\strtolower($this->wtm_type);

    // Apply temp file to commands
    $this->commands['wtm-resize'] = \str_replace('{tmp_wtm_file}', $tmp_file, $this->commands['wtm-resize']);
    $this->commands['wtm-resize'] = \str_replace('{widthxheight}', $this->dst_imginfo['width'].'x'.$this->dst_imginfo['height'], $this->commands['wtm-resize']);
    $this->commands['watermark'] = \str_replace('{tmp_wtm_file}', $tmp_file, $this->commands['watermark']);

    // Assembling the shell code for the resize with imagick
    $convert = $this->convert_path.' '.$this->commands['wtm-resize'];

    $return_var = null;
    $dummy      = null;
    $filecheck  = true;

    // execute the resize
    @\exec($convert, $dummy, $return_var);

    // Check that the resized image is valid
    if(!$this->checkValidImage($tmp_file))
    {
      $filecheck  = false;
    }

    // Workaround for servers with wwwrun problem
    if($return_var != 0 || !$filecheck)
    {
      $dir = \dirname($tmp_file);
      //JoomFile::chmod($dir, '0777', true);
      Path::setPermissions(Path::clean($dir), null, '0777');

      // Execute the resize
      @\exec($convert, $dummy, $return_var);

      //JoomFile::chmod($dir, '0755', true);
      Path::setPermissions(Path::clean($dir), null, '0755');

      // Check that the resized image is valid
      if(!$this->checkValidImage($tmp_file))
      {
        $filecheck = false;
      }

      if($return_var != 0 || !$filecheck)
      {
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SERVERPROBLEM_EXEC','exec('.$convert.');'));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_SERVERPROBLEM_EXEC','exec('.$convert.');'), 'error', 'jerror');
        $this->rollback($this->src_file, $tmp_file);

        return false;
      }
    }

    // Debugoutput: shell command
    if($this->app->get('debug', false))
    {
      $this->component->addDebug('<strong>Shell command (watermark-resize):</strong><br />'.$convert);
    }

    // unset wtm-resize command
    unset($this->commands['wtm-resize']);

    return $tmp_file;
  }

  /**
   * Add [0] to src_file path in order to process only
   * the first image frame
   *
   * @since   4.0.0
   */
  protected function onlyFirstFrame(): void
  {
    if(\strpos($this->src_file, '[0]') === false)
    {
      $this->src_file = $this->src_file.'[0]';
    }
  }
}
