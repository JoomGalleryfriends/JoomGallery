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
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\GifFrameExtractor;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\GifCreator;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtools as BaseIMGtools;

/**
 * IMGtools Class (GD)
 *
 * Provides methods to do image processing and metadata handling
 *
 * Image processor
 * GD: https://www.php.net/manual/en/intro.image.php
 *
 * @package JoomGallery
 *
 * @author  Manuel Häusler (tech.spuur@quickline.ch)
 *
 * @since   3.5.0
 */
class GDtools extends BaseIMGtools implements IMGtoolsInterface
{
  /**
   * Using a faster resizing approach (affects GD only)
   * default: true
   *
   * @var bool
   */
  public $fastgd2thumbcreation = true;

  /**
   * Memory needed in bytes for manipulation of a one-frame image with GD
   * depending on resolution, color-space, file-type
   *
   * @var array
   */
  protected $memory_needed;

  /**
   * Holds the working GD-Objects (image) and its duration (hundredths of a second) of each frame
   * (before image manipulation)
   *
   * @var array
   */
  protected $src_frames = array(array('duration' => 0,'image' => null));

  /**
   * Holds the working GD-Objects (image) and its duration (hundredths of a second) of each frame
   * (after image manipulation)
   *
   * @var array
   */
  protected $dst_frames = array(array('duration' => 0,'image' => null));

  /**
   * Holds the finished GD-Objects (image) and its duration (hundredths of a second) of each frame
   * (finished image)
   *
   * @var array
   */
  protected $res_frames = array(array('duration' => 0,'image' => null));

  /**
   * Switch to true, when a image manipulation was performed
   * default: false
   *
   * @var bool
   */
  protected $manipulated = false;

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($keep_metadata=false, $keep_anim=false, $fastgd2thumbcreation = true)
  {
    parent::__construct($keep_metadata, $keep_anim);

    $this->fastgd2thumbcreation = $fastgd2thumbcreation;
  }

  /**
	 * Destructor
	 *
	 * @return  void
   *
	 * @since  4.0.0
	 */
	public function __destruct()
	{
    $this->deleteFrames_GD(array('src_frames', 'dst_frames', 'res_frames'));
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
    if(\function_exists('gd_info'))
    {
      $version = \str_replace(array('bundled (', ')'), array('',''), gd_info()['GD Version']);

      return 'GD '.$version;
    }
    else
    {
      return false;
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
    if($version = $this->version())
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_USED_PROCESSOR', $version));

      return;
    }
    else
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GD_NOTFOUND'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_GD_NOTFOUND'), 'info', 'jerror');

      return;
    }
  }

  /**
   * Read image from file or image string (stream)
   * Supported image-types: jpg, png, gif, webp
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
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_INVALID_IMAGE_FILE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_INVALID_IMAGE_FILE'), 'error', 'jerror');

      return false;
    }

    // Store source file and type
    $this->src_file = $file;

    if(!$this->keep_anim)
    {
      $this->res_imginfo['frames'] = 1;
    }

    // Check GD installation
    if(!\function_exists('imagecreate'))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_GD_NOT_INSTALLED'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_GD_NOT_INSTALLED'), 'error', 'jerror');
      $this->rollback($file, '', true);

      return false;
    }

    // Check for supported imge files
    if(!\in_array($this->src_type, $this->supported_types))
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_SUPPORTED_TYPES', \implode(',', $this->supported_types)));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_SUPPORTED_TYPES', \implode(',', $this->supported_types)), 'error', 'jerror');

      return false;
    }

    // Prepare frames and imginfo
    $this->deleteFrames_GD(array('src_frames', 'dst_frames', 'res_frames'));

    // Create GD Objects from source
      if($this->keep_anim && $this->res_imginfo['animation'] && $this->src_type == 'GIF')
      {
        // Animated GIF image (image with more than one frame)
        // Create GD-Objects from gif-file
        $gfe = new GifFrameExtractor();
        $this->res_frames = $gfe->extract($file);
      }
      else
      {
        // Normal image (image with one frame)
        // Create GD-Object from file
        $this->res_frames = $this->imageCreateFrom_GD($file, $this->res_imginfo);
      }

    // Check for failures
    if($this->checkError($this->res_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_READING_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_READING_ERROR'), 'error', 'jerror');
      $this->rollback($file, '', true);

      return false;
    }

    // Add used image processor to debug
    $this->info();

    return true;
  }

  /**
   * Write image to file
   * Supported image-types: jpg, png, gif, webp
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
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Clean path to file
    $file = Path::clean($file);
    if(\strpos($file, JPATH_ROOT) === false)
    {
      $file = JPATH_ROOT.\DIRECTORY_SEPARATOR.$file;

      $file = Path::clean($file);
    }

    // Define image type to write
    $filesystem = JoomHelper::getService('Filesystem');
    $type = \strtoupper($filesystem->getExt($file));
    if(!empty($type))
    {
      if(\in_array($type, $this->supported_types))
      {
        $this->dst_type = $type;
      }
      else
      {
        // unsupported file type
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_SUPPORTED_TYPES', \implode(',', $this->supported_types)));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_SUPPORTED_TYPES', \implode(',', $this->supported_types)), 'error', 'jerror');

        return false;
      }
    }
    else
    {
      $this->dst_type = $this->src_type;
    }

    // copy transparent and animated images not to loose transparency
    if(!$this->manipulated && $this->res_imginfo['transparency'] && $this->res_imginfo['animation'])
    {
      $tmp_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
      $this->deleteFrames_GD(array('res_frames'));

      $this->res_frames  = $this->copyFrames_GD($tmp_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
      // Destroy GD-Objects if there are any
      foreach($tmp_frames as $key => $frame)
      {
        if($this->isImage_GD($tmp_frames[$key]['image']))
        {
          \imagedestroy($tmp_frames[$key]['image']);
        }
      }
    }

    // write successful
    $success  = false;
    $bak_file = '';

    // Create backup file, if source and destination are the same
    if($this->src_file == $file)
    {
      $bak_file = $this->src_file.'bak';
      $success  = File::copy($this->src_file, $bak_file);
    }
    else
    {
      if(\is_file(Path::clean($file)))
      {
        $bak_file = $file.'bak';
        $success  = File::copy($file, $bak_file);
      }
      else
      {
        $success = true;
      }
    }

    // Write processed image to file
    if($this->keep_anim && $this->res_imginfo['animation'] && $this->dst_type == 'GIF' && $this->src_type == 'GIF')
    {
      // Animated GIF image (image with more than one frame)
      $gc = new GifCreator();
      $gc->create($this->res_frames, 0);
      $success = \file_put_contents($file, $gc->getGif());
    }
    else
    {
      // Normal image (image with one frame)
      $success = $this->imageWriteFrom_GD($file, $this->res_frames, $quality);
    }

    // Workaround for servers with wwwrun problem
    if(!$success)
    {
      $dir = \dirname($file);
      //JoomFile::chmod($dir, '0777', true);
      Path::setPermissions(Path::clean($dir), null, '0777');

      // Create backup file, if source and destination are the same
      if($this->src_file == $file)
      {
        $bak_file = $this->src_file.'bak';
        $success  = File::copy($this->src_file, $bak_file);
      }
      else
      {
        if(\is_file(Path::clean($file)))
        {
          $bak_file = $file.'bak';
          $success  = File::copy($file, $bak_file);
        }
        else
        {
          $success = true;
        }
      }

      // Write processed image to file
      if($this->keep_anim && $this->res_imginfo['animation'] && $this->dst_type == 'GIF' && $this->src_type == 'GIF')
      {
        // Animated GIF image (image with more than one frame)
        $gc = new GifCreator();
        $gc->create($this->res_frames, 0);
        $success = \file_put_contents($file, $gc->getGif());
      }
      else
      {
        // Normal image (image with one frame)
        $success = $this->imageWriteFrom_GD($file, $this->res_frames, $quality);
      }

      if(!$success)
      {
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPYING_FILE', $file));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPYING_FILE', $file), 'error', 'jerror');

        return false;
      }

      // Copy metadata if needed
      if($this->keep_metadata)
      {
        $new_orient = false;

        if($this->auto_orient && isset($this->metadata['exif']['IFD0']['Orientation']))
        {
          if($this->auto_orient && $this->metadata['exif']['IFD0']['Orientation'] != 1)
          {
            // Make sure, the exif orientation tag is set to 1 when auto-oriented
            $new_orient = 1;
          }
        }

        if($this->src_file == $file)
        {
          $quelle = $this->src_file.'bak';
        }
        else
        {
          $quelle = $this->src_file;
        }

        $meta_success = $this->copyMetadata($quelle, $file, $this->src_type, $this->dst_type, $new_orient, false);

        if(!$meta_success)
        {
          $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_COPY_METADATA'));
          $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_COPY_METADATA'), 'error', 'jerror');
          $this->rollback('', $file);

          return false;
        }
      }

      //JoomFile::chmod($dir, '0755', true);
      Path::setPermissions(Path::clean($dir), null, '0755');
    }
    else
    {
      // Copy metadata if needed
      if($this->keep_metadata)
      {
        $new_orient = false;

        if($this->auto_orient && isset($this->metadata['exif']['IFD0']['Orientation']))
        {
          if($this->auto_orient && $this->metadata['exif']['IFD0']['Orientation'] != 1)
          {
            // Make sure, the exif orientation tag is set to 1 when auto-oriented
            $new_orient = 1;
          }
        }

        $meta_success = $this->copyMetadata($file, $file, $this->src_type, $this->dst_type, $new_orient, false);

        if(!$meta_success)
        {
          $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_COPY_METADATA'));
          $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_COPY_METADATA'), 'error', 'jerror');
          $this->rollback($this->src_file, $file);

          return false;
        }
      }
    }

    // Check for failures
    if(!$success || !$this->checkValidImage($file))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_OUTPUT_IMAGE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_OUTPUT_IMAGE'), 'error', 'jerror');
      $this->rollback($this->src_file, $file);

      return false;
    }

    // Clean up working area (frames and imginfo)
    $this->deleteFrames_GD(array('src_frames', 'dst_frames', 'res_frames'));
    $this->clearVariables();

    // Delete backup files
    File::delete($bak_file);

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
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    // Define image type to write
    if($type)
    {
      if(\in_array($type, $this->supported_types))
      {
        $this->dst_type = $type;
      }
      else
      {
        // unsupported file type
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_SUPPORTED_TYPES', \implode(',', $this->supported_types)));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_SUPPORTED_TYPES', \implode(',', $this->supported_types)), 'error', 'jerror');

        return false;
      }
    }
    else
    {
      $this->dst_type = $this->src_type;
    }

    // copy transparent and animated images not to loose transparency
    if(!$this->manipulated && $this->res_imginfo['transparency'] && $this->res_imginfo['animation'])
    {
      $tmp_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
      $this->deleteFrames_GD(array('res_frames'));

      $this->res_frames  = $this->copyFrames_GD($tmp_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
      // Destroy GD-Objects if there are any
      foreach($tmp_frames as $key => $frame)
      {
        if($this->isImage_GD($tmp_frames[$key]['image']))
        {
          \imagedestroy($tmp_frames[$key]['image']);
        }
      }
    }

    // Generate stream
    if($this->keep_anim && $this->res_imginfo['animation'] && $this->dst_type == 'GIF' && $this->src_type == 'GIF')
    {
      // Animated GIF image (image with more than one frame)
      $gc = new GifCreator();
      $gc->create($this->res_frames, 0);
      $stream = $gc->getGif();
    }
    else
    {
      // Normal image (image with one frame)
      $stream = $this->imageWriteFrom_GD(null, $this->res_frames, $quality);
    }

    // Check for failures
    if($this->checkError($this->res_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_OUTPUT_IMAGE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_OUTPUT_IMAGE'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Add metadata if needed
    if($this->keep_metadata)
    {
      // ToDo: $stream = $this->copyMetadata() using the new metadata service
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

      case 'WEBP':
        $stream = 'data:image/webp;base64,'.$stream;
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
  }

  /**
   * Resize image
   * Supported image-types: jpg, png, gif, webp
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
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    if($method == 0 || ($this->res_imginfo['width'] <= $width && $this->res_imginfo['height'] <= $height))
    {
      // Nothing to do
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_NOT_NEEDED'));
      $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

      return true;
    }

    // Prepare working area (frames and imginfo)
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));
    $this->src_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
    $this->src_imginfo = $this->res_imginfo;

    // Generate information about type, dimension and origin of resized image
    if(!($this->getResizeInfo($this->src_type, $method, $width, $height, $cropposition)))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_INVALID_IMAGEFILE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_INVALID_IMAGEFILE'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Calculation for the amount of memory needed (in bytes, GD)
    $this->memory_needed = $this->calculateMemory($this->src_imginfo, $this->dst_imginfo, 'resize');

    // Check if there is enough memory for the manipulation
    $memory = $this->checkMemory($this->memory_needed);
    if(!$memory['success'])
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'), 'error', 'jerror');
      $this->rollback('', '');

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

    // Create empty destination GD-Objects of specified size
    if($this->keep_anim && $this->src_imginfo['animation'] && $this->src_type == 'GIF')
    {
      // Animated GIF image (image with more than one frame)
      // Create GD-Objects from gif-file
      foreach($this->src_frames as $key => $frame)
      {
        // Create empty GD-Objects for the resized frames
        $this->dst_frames[$key]['duration'] = $this->src_frames[$key]['duration'];
        $this->dst_frames[$key]['image']    = $this->imageCreateEmpty_GD($this->src_frames[$key]['image'], $this->dst_imginfo,
                                                                         $this->src_imginfo['transparency']);
      }
    }
    else
    {
      // Normal image (image with one frame)
      // Create empty GD-Object for the resized image
      $this->dst_frames[0]['duration'] = 0;
      $this->dst_frames[0]['image']    = $this->imageCreateEmpty_GD($this->src_frames[0]['image'], $this->dst_imginfo,
                                                                    $this->src_imginfo['transparency']);
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_RESIZE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_RESIZE'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Resize image
    foreach($this->src_frames as $key => $frame)
    {
      $fast_resize = false;
      if($this->fastgd2thumbcreation == 1)
      {
        $fast_resize = true;
      }

      $this->imageResize_GD($this->dst_frames[$key]['image'], $this->src_frames[$key]['image'], $this->src_imginfo,
                            $this->dst_imginfo, $fast_resize, 3);
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_RESIZE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_RESIZE'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Sharpen image if needed
    if($unsharp)
    {
      foreach($this->dst_frames as $key => $frame)
      {
        $this->dst_frames[$key]['image'] = $this->unsharpMask_GD($this->dst_frames[$key]['image'], 100, 4.0, 30);
      }

      // Check for failures
      if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
      {
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_RESIZE'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_RESIZE'), 'error', 'jerror');
        $this->rollback('', '');

        return false;
      }
    }

    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_RESIZE_SUCCESSFUL'));

    // switch manipulated to true
    $this->manipulated = true;

    // Clean up working area (frames and imginfo)
    $this->res_frames = $this->copyFrames_GD($this->dst_frames, $this->dst_imginfo, $this->src_imginfo['transparency']);

    $this->clearImginfo('res');
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    $this->clearImginfo(array('src', 'dst'));
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

    return true;
  }

  /**
   * Rotate image
   * Supported image-types: jpg, png, gif, webp
   *
   * @param   int     $angle          Angle to rotate the image anticlockwise
   *
   * @return  bool    True on success, false otherwise (false, if no rotation is needed)
   *
   * @since   3.4.0
   */
  public function rotate($angle): bool
  {
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    if($angle == 0)
    {
      // Nothing to do
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ROTATE_NOT_NEEDED'));
      $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

      return true;
    }

    // Prepare working area (frames and imginfo)
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));
    $this->src_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
    $this->src_imginfo = $this->res_imginfo;

    // Get info for destination frames
    $this->dst_imginfo['angle']     = $angle;
    $this->dst_imginfo['flip']      = 'none';
    $this->dst_imginfo['width']     = $this->dst_imginfo['src']['width']  = $this->src_imginfo['width'];
    $this->dst_imginfo['height']    = $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];
    $this->dst_imginfo['offset_x']  = 0;
    $this->dst_imginfo['offset_y']  = 0;
    $this->dst_imginfo['truecolor'] = $this->src_imginfo['truecolor'];

    // Calculation for the amount of memory needed (in bytes, GD)
    $this->memory_needed = $this->calculateMemory($this->src_imginfo, $this->dst_imginfo, 'rotate');

    // Check if there is enough memory for the manipulation (assuming one frame)
    $memory = $this->checkMemory($this->memory_needed);
    if(!$memory['success'])
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'), 'error', 'jerror');

      return false;
    }

    // Create empty destination GD-Objects of specified size
    if($this->keep_anim && $this->src_imginfo['animation'] && $this->src_type == 'GIF')
    {
      // Animated GIF image (image with more than one frame)
      // Create GD-Objects from gif-file
      foreach($this->src_frames as $key => $frame)
      {
        // Create empty GD-Objects for the resized frames
        $this->dst_frames[$key]['duration'] = $this->src_frames[$key]['duration'];
        $this->dst_frames[$key]['image']    = $this->imageCreateEmpty_GD($this->src_frames[$key]['image'], $this->dst_imginfo,
                                                                         $this->src_imginfo['transparency']);
      }
    }
    else
    {
      // Normal image (image with one frame)
      // Create empty GD-Object for the resized image
      $this->dst_frames[0]['duration'] = 0;
      $this->dst_frames[0]['image']    = $this->imageCreateEmpty_GD($this->src_frames[0]['image'], $this->dst_imginfo,
                                                                    $this->src_imginfo['transparency']);
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ROTATE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ROTATE'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Rotate image
    foreach($this->src_frames as $key => $frame)
    {
      $this->dst_frames[$key]['image'] = $this->imageRotate_GD($this->src_frames[$key]['image'], $this->src_type,
                                                                $this->dst_imginfo['angle'], $this->src_imginfo['transparency']);

      $this->dst_imginfo['width']      = \imagesx($this->dst_frames[$key]['image']);
      $this->dst_imginfo['height']     = \imagesy($this->dst_frames[$key]['image']);
    }

    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ROTATE_BY_ANGLE', $this->dst_imginfo['angle']));

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ROTATE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ROTATE'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ROTATE_SUCCESSFUL'));

    // switch manipulated to true
    $this->manipulated = true;

    // Clean up working area (frames and imginfo)
    $this->res_frames = $this->copyFrames_GD($this->dst_frames, $this->dst_imginfo, $this->src_imginfo['transparency']);

    $this->clearImginfo('res');
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    $this->clearImginfo(array('src', 'dst'));
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

    return true;
  }

  /**
   * Flip image
   * Supported image-types: jpg, png, gif, webp
   *
   * @param   int     $direction       Direction to flip the image (0:none,1:horizontal,2:vertical,3:both)
   *
   * @return  bool    True on success, false otherwise (false, if no flipping is needed)
   *
   * @since   4.0.0
   */
  public function flip($direction): bool
  {
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    if($direction == 0)
    {
      // Nothing to do
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_FLIP_NOT_NEEDED'));
      $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

      return true;
    }

    // Prepare working area (frames and imginfo)
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));
    $this->src_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
    $this->src_imginfo = $this->res_imginfo;

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
    $this->dst_imginfo['width']       = $this->dst_imginfo['src']['width']  = $this->src_imginfo['width'];
    $this->dst_imginfo['height']      = $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];
    $this->dst_imginfo['orientation'] = $this->src_imginfo['orientation'];
    $this->dst_imginfo['truecolor']   = $this->src_imginfo['truecolor'];
    $this->dst_imginfo['offset_x']    = 0;
    $this->dst_imginfo['offset_y']    = 0;
    $this->dst_imginfo['angle']       = 0;
    $this->dst_imginfo['quality']     = 100;

    // Calculation for the amount of memory needed (in bytes, GD)
    $this->memory_needed = $this->calculateMemory($this->src_imginfo, $this->dst_imginfo, 'rotate');

    // Check if there is enough memory for the manipulation (assuming one frame)
    $memory = $this->checkMemory($this->memory_needed);
    if(!$memory['success'])
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'), 'error', 'jerror');

      return false;
    }

    // Create empty destination GD-Objects of specified size
    if($this->keep_anim && $this->src_imginfo['animation'] && $this->src_type == 'GIF')
    {
      // Animated GIF image (image with more than one frame)
      // Create GD-Objects from gif-file
      foreach($this->src_frames as $key => $frame)
      {
        // Create empty GD-Objects for the resized frames
        $this->dst_frames[$key]['duration'] = $this->src_frames[$key]['duration'];
        $this->dst_frames[$key]['image']    = $this->imageCreateEmpty_GD($this->src_frames[$key]['image'], $this->dst_imginfo,
                                                                         $this->src_imginfo['transparency']);
      }
    }
    else
    {
      // Normal image (image with one frame)
      // Create empty GD-Object for the resized image
      $this->dst_frames[0]['duration'] = 0;
      $this->dst_frames[0]['image']    = $this->imageCreateEmpty_GD($this->src_frames[0]['image'], $this->dst_imginfo,
                                                                    $this->src_imginfo['transparency']);
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FLIP'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FLIP'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Flip image
    if($this->dst_imginfo['flip'] != 'none')
    {
      foreach($this->src_frames as $key => $frame)
      {
        $this->dst_frames[$key]['image'] = $this->imageFlip_GD($this->src_frames[$key]['image'], $this->dst_imginfo['flip']);
      }

      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FLIP_BY', $this->dst_imginfo['flip']));
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FLIP'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FLIP'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_FLIP_SUCCESSFUL'));

    // switch manipulated to true
    $this->manipulated = true;

    // Clean up working area (frames and imginfo)
    $this->res_frames= $this->copyFrames_GD($this->dst_frames, $this->dst_imginfo, $this->src_imginfo['transparency']);

    $this->clearImginfo(array('res'));
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    $this->clearImginfo(array('src', 'dst'));
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

    return true;
  }

  /**
   * Auto orientation of the image based on EXIF meta data
   * Supported image-types: jpg
   *
   * @return  bool    True on success, false otherwise (true, if no orientation is needed)
   *
   * @since   4.0.0
   */
  public function orient(): bool
  {
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_WORKSPACE_MISSING'));
    }

    if(!isset($this->metadata['exif']['IFD0']['Orientation']))
    {
      // Nothing to do
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_AUTOORIENT_ONLY_JPG'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_AUTOORIENT_ONLY_JPG'), 'info', 'jerror');
      $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

      return true;
    }

    // Prepare working area (frames and imginfo)
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));
    $this->src_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
    $this->src_imginfo = $this->res_imginfo;

    // Get info for destination frames
    if(isset($this->metadata['exif']['IFD0']['Orientation']))
    {
      $this->autoOrient($this->metadata['exif']['IFD0']['Orientation']);
    }
    $this->dst_imginfo['width']     = $this->dst_imginfo['src']['width']  = $this->src_imginfo['width'];
    $this->dst_imginfo['height']    = $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];
    $this->dst_imginfo['offset_x']  = 0;
    $this->dst_imginfo['offset_y']  = 0;
    $this->dst_imginfo['truecolor'] = $this->src_imginfo['truecolor'];

    // Create empty destination GD-Objects of specified size
    if($this->keep_anim && $this->src_imginfo['animation'] && $this->src_type == 'GIF')
    {
      // Animated GIF image (image with more than one frame)
      // Create GD-Objects from gif-file
      foreach($this->src_frames as $key => $frame)
      {
        // Create empty GD-Objects for the resized frames
        $this->dst_frames[$key]['duration'] = $this->src_frames[$key]['duration'];
        $this->dst_frames[$key]['image']    = $this->imageCreateEmpty_GD($this->src_frames[$key]['image'], $this->dst_imginfo,
                                                                         $this->src_imginfo['transparency']);
      }
    }
    else
    {
      // Normal image (image with one frame)
      // Create empty GD-Object for the resized image
      $this->dst_frames[0]['duration'] = 0;
      $this->dst_frames[0]['image']    = $this->imageCreateEmpty_GD($this->src_frames[0]['image'], $this->dst_imginfo,
                                                                    $this->src_imginfo['transparency']);
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ORIENT'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ORIENT'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Orient image
    if($this->dst_imginfo['flip'] != 'none' && $this->dst_imginfo['angle'] == 0)
    {
      // only flipping
      foreach($this->src_frames as $key => $frame)
      {
        $this->dst_frames[$key]['image'] = $this->imageFlip_GD($this->src_frames[$key]['image'], $this->dst_imginfo['flip']);
      }

      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FLIP_BY', $this->dst_imginfo['flip']));
    }
    else
    {
      if($this->dst_imginfo['flip'] != 'none')
      {
        // flipping ...
        foreach($this->src_frames as $key => $frame)
        {
          $this->src_frames[$key]['image'] = $this->imageFlip_GD($this->src_frames[$key]['image'], $this->dst_imginfo['flip']);
        }

        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FLIP_BY', $this->dst_imginfo['flip']));
      }

      // ... and rotating
      foreach($this->src_frames as $key => $frame)
      {
        $this->dst_frames[$key]['image'] = $this->imageRotate_GD($this->src_frames[$key]['image'], $this->src_type,
                                                                 $this->dst_imginfo['angle'], $this->src_imginfo['transparency']);

        $this->dst_imginfo['width']       = imagesx($this->dst_frames[$key]['image']);
        $this->dst_imginfo['height']      = imagesy($this->dst_frames[$key]['image']);
        $this->dst_imginfo['orientation'] = $this->getOrientation($this->dst_imginfo['width'], $this->dst_imginfo['height']);
      }

      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ROTATE_BY_ANGLE', $this->dst_imginfo['angle']));
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ORIENT'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_ORIENT'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ORIENT_SUCCESSFUL'));

    // switch manipulated to true
    $this->manipulated = true;

    // Clean up working area (frames and imginfo)
    $this->res_frames = $this->copyFrames_GD($this->dst_frames, $this->dst_imginfo, $this->src_imginfo['transparency']);

    $this->clearImginfo(array('res'));
    $this->res_imginfo                = $this->src_imginfo;
    $this->res_imginfo['width']       = $this->dst_imginfo['width'];
    $this->res_imginfo['height']      = $this->dst_imginfo['height'];
    $this->res_imginfo['orientation'] = $this->dst_imginfo['orientation'];

    $this->clearImginfo(array('src', 'dst'));
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

    return true;
  }

  /**
   * Add watermark to an image
   * Supported image-types: jpg, png, gif, webp
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
    // Check working area (frames and imginfo)
    if(empty($this->res_imginfo['width']) || empty($this->res_imginfo['height']) ||
       empty($this->res_frames[0]['image']) || !$this->isImage_GD($this->res_frames[0]['image']))
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
    if(!\file_exists($wtm_file))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARK_NOT_EXIST'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARK_NOT_EXIST'), 'error', 'jerror');

      return false;
    }

    // Prepare working area (frames and imginfo)
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));
    $bkg_frames  = $this->copyFrames_GD($this->res_frames, $this->res_imginfo, $this->res_imginfo['transparency']);
    $bkg_imginfo = $this->res_imginfo;
    $bkg_type    = $this->src_type;

    // Analysis and validation of the source watermark-image
    if(!($this->src_imginfo = $this->analyse($wtm_file)))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_INVALID_IMAGE_FILE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_INVALID_IMAGE_FILE'), 'error', 'jerror');

      return false;
    }
    $this->src_imginfo['frames'] = 1;

    if(!$this->keep_anim)
    {
      $bkg_imginfo['frames'] = 1;
    }

    // Generate information about type, dimension and origin of resized image
    $position = $this->getWatermarkingInfo($bkg_imginfo, $wtm_pos, $wtm_resize, $wtm_newSize);

    // Calculation for the amount of memory needed (in bytes, GD)
    $this->memory_needed = $this->calculateMemory($this->src_imginfo, $this->dst_imginfo, 'resize');

    // Check if there is enough memory for the manipulation
    $memory = $this->checkMemory($this->memory_needed);
    if(!$memory['success'])
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MEMORY_EXCEED', $memory['needed'].' MByte, Serverlimit: '.$memory['limit'].' MByte'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Create debugoutput
    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_WATERMARKING'));

    // Create GD Object of the watermark file
    $this->src_frames = $this->imageCreateFrom_GD($wtm_file, $this->src_imginfo);

    // Create GD Object for the resized watermark
    $this->dst_frames[0]['duration'] = 0;
    $this->dst_frames[0]['image']    = $this->imageCreateEmpty_GD($this->src_frames[0]['image'], $this->dst_imginfo,
                                                                  $this->src_imginfo['transparency']);

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // Check if resize is needed
    $resizeWatermark = false;
    if ($wtm_resize != 0 || $this->src_imginfo['width'] != $this->dst_imginfo['width'] || $this->src_imginfo['height'] != $this->dst_imginfo['height'])
    {
      $resizeWatermark = true;
    }

    // Resizing with GD
    if($resizeWatermark)
    {
      $this->imageResize_GD($this->dst_frames[0]['image'], $this->src_frames[0]['image'], $this->src_imginfo,
                            $this->dst_imginfo, $this->fastgd2thumbcreation, 3);
    }
    else
    {
      // Copy watermark, if no resize is needed
      $this->dst_frames = $this->copyFrames_GD($this->src_frames, $this->src_imginfo, $this->src_imginfo['transparency']);
    }

    // Check for failures
    if($this->checkError($this->src_frames) || $this->checkError($this->dst_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    // src_frames will not be used anymore
    // Destroy GD-Objects if there are any
    $this->deleteFrames_GD(array('src_frames'));

    // Copy background image back to source working area
    $this->src_frames  = $this->copyFrames_GD($bkg_frames, $bkg_imginfo, $bkg_imginfo['transparency']);
    $this->src_imginfo = $bkg_imginfo;

    // Destroy GD-Objects if there are any
    foreach($bkg_frames as $key => $frame)
    {
      if($this->isImage_GD($bkg_frames[$key]['image']))
      {
        \imagedestroy($bkg_frames[$key]['image']);
      }
    }

    // Watermarking with GD
    foreach($this->src_frames as $key => $frame)
    {
      $this->imageWatermark_GD($this->src_frames[$key]['image'], $this->dst_frames[0]['image'], $this->src_imginfo,
                               $this->dst_imginfo, $position, $opacity);
    }

    // Check for failures
    if($this->checkError($this->src_frames))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_WATERMARKING'), 'error', 'jerror');
      $this->rollback('', '');

      return false;
    }

    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_WATERMARKING_SUCCESSFUL'));

    // switch manipulated to true
    $this->manipulated = true;

    // Clean up working area (frames and imginfo)
    $this->res_frames = $this->copyFrames_GD($this->src_frames, $this->src_imginfo, $this->src_imginfo['transparency']);

    $this->clearImginfo(array('res'));
    $this->res_imginfo = $this->src_imginfo;
    $this->src_type    = $bkg_type;

    $this->clearImginfo(array('src', 'dst'));
    $this->deleteFrames_GD(array('src_frames', 'dst_frames'));

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
    if(\function_exists('gd_info'))
    {
      $types = array();

      foreach (gd_info() as $key => $value)
      {
        if($value === true)
        {
          $arr = \explode(' ', $key);

          if(!\in_array($arr[0], $types))
          {
            \array_push($types, \strtoupper($arr[0]));
          }
        }
      }

      $types = $this->addJpegTypes($types);

      return $types;
    }
    else
    {
      return array();
    }
  }

  //////////////////////////////////////////////////
  //   Protected functions with basic features.
  //////////////////////////////////////////////////

  /**
   * Calculates the amaount of memory (in bytes)
   * needed for manipulating a one-frame image with GD
   *
   * @param   array   $src_imginfo      array with source image information
   * @param   array   $dst_imginfo      array with destination image information
   * @param   string  $method           manipulation method (resize or rotate)
   *
   * @return  int     memory needed
   *
   * @since   3.5.0
   */
  protected function calculateMemory($src_imginfo, $dst_imginfo, $method)
  {
    // Quantify number of bits per channel
    if(\key_exists('bits',$src_imginfo))
    {
      $bits = $src_imginfo['bits'];
    }
    else
    {
      $bits = 8;
    }

    // Check, if it is a special image (transparency or animation)
    $special = false;
    if($src_imginfo['animation'] || $src_imginfo['transparency'])
    {
      $special = true;
    }

    // Check, if GD2 is available
    $gd2 = false;
    if(\function_exists('imagecopyresampled'))
    {
      $gd2 = true;
    }

    // Quantify the tweakfactor coefficients and the number of channels per pixel
    // based on image-type and manipulation method
    // Formula for tweakfactor calculation: tweakfactor = m*pixel + c
    switch($this->src_type)
    {
      case 'GIF':
        switch($method)
        {
          case 'resize':
            if(!$src_imginfo['animation'])
            {
              // Tweakfactor dependent on number of pixels (~0.5)
              $m = -0.0000000020072;
              $c = 0.60054;
            }
            else
            {
              // Tweakfactor dependent on number of pixels (~1.8)
              $m = -0.000000030872;
              $c = 1.9125;
            }
            break;
          case 'rotate':
            if(!$src_imginfo['animation'])
            {
              // Tweakfactor dependent on number of pixels (~3.0)
              $m = -0.000000019085;
              $c = 2.70515;
            }
            else
            {
              // Tweakfactor dependent on number of pixels (~2.3)
              $m = -0.00000006174;
              $c = 2.41248;
            }
            break;
          default:
            // Constant tweakfactor of 0.5
            $m = 0;
            $c = 0.5;
            break;
        }

        // GIF has always 3 channels (RGB)
        $channels = 3;
        break;
      case 'JPG':
      case 'JPEG':
      case 'JPE':
        switch($method)
        {
          case 'resize':
            if($this->fastgd2thumbcreation && $gd2 && !$special)
            {
              // Tweakfactor dependent on number of pixels (~1.6)
              $m = -0.000000007157;
              $c = 2.00193;
            }
            else
            {
              // Tweakfactor dependent on number of pixels (~1.5)
              $m = -0.000000003579;
              $c = 1.60097;
            }
            break;
          case 'rotate':
            // Tweakfactor dependent on number of pixels (~3.0)
            $m = -0.000000007157;
            $c = 3.2019;
            break;
          default:
            // Constant tweakfactor of 1.5
            $m = 0;
            $c = 1.5;
            break;
        }

        // Get channel number from imginfo
        $channels = $src_imginfo['channels'];
        break;
      case 'PNG':
        switch($method)
        {
          case 'resize':
            // Tweakfactor dependent on number of pixels (~2.5)
            $m = -0.000000007157;
            $c = 2.70193;
            break;
          case 'rotate':
            // Tweakfactor dependent on number of pixels (~3.3)
            $m = -0.000000011928;
            $c = 3.50322;
            break;
          default:
            // Constant tweakfactor of 2.5
            $m = 0;
            $c = 2.5;
            break;
        }

        // PNG has always 3 channels (RGB)
        $channels = 3;
        break;
      case 'WEBP':
        // Todo
        switch($method)
        {
          case 'resize':
            // Tweakfactor dependent on number of pixels (~2.5)
            $m = -0.000000007157;
            $c = 2.70193;
            break;
          case 'rotate':
            // Tweakfactor dependent on number of pixels (~3.3)
            $m = -0.000000011928;
            $c = 3.50322;
            break;
          default:
            // Constant tweakfactor of 2.5
            $m = 0;
            $c = 2.5;
            break;
        }

        // WEBP has always ??
        $channels = 3;
        break;
    }

    // Pixel calculation for source and destination GD-Frame
    $src_pixel = $src_imginfo['width'] * $src_imginfo['height'];
    $dst_pixel = $dst_imginfo['width'] * $dst_imginfo['height'];

    $securityfactor = 1.08;
    $powerfactor    = 1.02;
    $tweakfactor    = $this->tweakfactor($m, $c, $src_pixel);

    $oneMB = 1048576;

    $memoryUsage = \round(( ((($bits * $channels) / 8) * $src_pixel * $tweakfactor + (($bits * $channels) / 8) * $dst_pixel * $tweakfactor)
                           * \pow($src_imginfo['frames'], $powerfactor) + 2 * $oneMB
                          ) * $securityfactor);

    // Calculate needed memory in bytes (1byte = 8bits).
    // We need to calculate the usage for both source and destination GD-Frame
    return $memoryUsage;
  }

  /**
   * Calculates the tweakfactor for the GD memory usage.
   *
   * @param   double  $m         linear dependency coefficient
   * @param   double  $c         constant coefficient
   * @param   int     $pixel     number of pixels of the frame
   *
   * @return  double  tweakfactor
   *
   * @since   3.5.0
   */
  protected function tweakfactor($m, $c, $pixel)
  {
    return ($m * $pixel) + $c;
  }

  /**
   * Calculates whether there is enough memory
   * to work on a specific image.
   *
   * @param   int     $memory_needed    memory (in bytes) that is needed for manipulating
   *
   * @return  array   True, if we have enough memory to work, false and memory info otherwise
   *
   * @since   3.5.0
   */
  protected function checkMemory($memory_needed)
  {
    $byte_values = array('K' => 1024, 'M' => 1048576, 'G' => 1073741824);

    if((\function_exists('memory_get_usage')) && (\ini_get('memory_limit')))
    {
      $memoryNeeded = \memory_get_usage() + $memory_needed;

      // Get memory limit in bytes
      $memory_limit = @\ini_get('memory_limit');
      if(!empty($memory_limit) && !is_numeric($memory_limit))
      {
        $val          = \substr($memory_limit, -1);
        $memory_limit = \substr($memory_limit, 0, -1) * $byte_values[$val];
      }

      if($memory_limit >= 0 && $memoryNeeded > $memory_limit)
      {
        $memoryNeededMB = \round($memoryNeeded / $byte_values['M'], 0);
        $memoryLimitMB  = \round(intval($memory_limit) / $byte_values['M'], 0);

        return array('success' => false, 'needed' => $memoryNeededMB, 'limit' => $memoryLimitMB);
      }
    }

    return array('success' => true);
  }

  /**
   * Restore initial state, if something goes wrong
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @param   bool    $cpl             True: destroy all GD frames (inkl. res_frames)
   *
   * @return  int     number of bytes written on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function rollback($src_file, $dst_file, $cpl = false)
  {
    $del_frames = array('src_frames', 'dst_frames');

    if($cpl)
    {
      \array_push($del_frames,'res_frames');
    }

    // Destroy GD-Objects id there are any
    $this->deleteFrames_GD($del_frames);

    // Restore src from backup file or delete corrupt dst file
    // Reset class variables
    parent::rollback($src_file, $dst_file);

    return true;
  }

  /**
   * Checks if the passed frame is either a resource of type gd or a GdImage object instance.
   *
   * @param   resource|GdImage|false   $frame   A frame to check the type for.
   *
   * @return  bool   True on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function isImage_GD($frame)
  {
    if(\is_resource($frame) && 'gd' === \get_resource_type($frame) || \is_object($frame) && $frame instanceof \GdImage)
    {
      return true;
    }

    return false;
  }

  /**
   * Delete GD-Objects in frames
   *
   * @param   array  $frames  Array with frame names to delete
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function deleteFrames_GD($frames)
  {
    if(\in_array('src_frames', $frames))
    {
      // Destroy source GD-Objects
      foreach($this->src_frames as $key => $frame)
      {
        if($this->isImage_GD($this->src_frames[$key]['image']))
        {
          \imagedestroy($this->src_frames[$key]['image']);
        }
      }

      $this->src_frames = array(array('duration' => 0,'image' => null));
    }

    if(\in_array('dst_frames', $frames))
    {
      // Destroy destination GD-Objects
      foreach($this->dst_frames as $key => $frame)
      {
        if($this->isImage_GD($this->dst_frames[$key]['image']))
        {
          \imagedestroy($this->dst_frames[$key]['image']);
        }
      }

      $this->dst_frames = array(array('duration' => 0,'image' => null));
    }

    if(\in_array('res_frames', $frames))
    {
      // Destroy destination GD-Objects
      foreach($this->res_frames as $key => $frame)
      {
        if($this->isImage_GD($this->res_frames[$key]['image']))
        {
          \imagedestroy($this->res_frames[$key]['image']);
        }
      }

      $this->res_frames = array(array('duration' => 0,'image' => null));
    }
  }

  /**
   * Copy frames-array without reference
   *
   * @param   array  $src_frames  Array with source frames
   * @param   array   $imginfo  array with destination image information
   * @param   boolean $transparency true = transparent background
   *
   * @return  array  Array with destination frames
   *
   * @since   4.0.0
   */
  protected function copyFrames_GD($src_frames, $imginfo, $transparency = true)
  {
    $dst_frames = array();

    // Loop through all frames
    foreach($src_frames as $key => $frame)
    {
      $new_frame = array('duration' => 0, 'image' => null);

      $new_frame['duration'] = $frame['duration'];
      $new_frame['image']    = $this->imageCreateEmpty_GD($new_frame['image'], $imginfo, $transparency);
      $new_frame['image']    = $this->imageCopy_GD($new_frame['image'], $frame['image']);

      \array_push($dst_frames,$new_frame);
    }

    return $dst_frames;
  }

  /**
   * Creates GD image objects from different file types with one frame
   * Supported: JPG, PNG, GIF, WEBP
   *
   * @param   string|resource  $file      Path to source file or image string or resource
   * @param   array            $imginfo   Array with source image information
   *
   * @return  array            $src_frame[0: ["durtion": 0, "image": GDobject]] on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function imageCreateFrom_GD($src_file, $src_imginfo)
  {
    $src_frame = array(array('duration'=>0));

    if(\is_resource($src_file))
    {
      if(\get_resource_type($src_file) == 'stream')
      {
        $src_file              = \stream_get_contents($src_file, null, 0);
        $src_frame[0]['image'] = \imagecreatefromstring($src_file);
      }
      elseif(\get_resource_type($src_file) == 'gd')
      {
        $src_frame[0]['image'] = $src_file;
      }
    }
    else
    {
      switch ($this->src_type)
      {
        case 'PNG':
          $src_frame[0]['image'] = \imagecreatefrompng($src_file);
          break;
        case 'GIF':
          $src_frame[0]['image'] = \imagecreatefromgif($src_file);
          break;
        case 'JPG':
          $src_frame[0]['image'] = \imagecreatefromjpeg($src_file);
          break;
        case 'WEBP':
          $src_frame[0]['image'] = \imagecreatefromwebp($src_file);
          break;
        default:
          return false;
          break;
      }
    }

    // Convert palette images to true color images
    if(\function_exists('imagepalettetotruecolor') && $this->src_type != 'GIF')
    {
      \imagepalettetotruecolor($src_frame[0]['image']);
    }

    return $src_frame;
  }

  /**
   * Creates empty GD image object optionally with transparent background
   *
   * @param   object  $src_frame    GDobject of the source image file
   * @param   array   $dst_imginfo  array with destination image information
   * @param   boolean $transparency true = transparent background
   *
   * @return  object  empty GDobject on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function imageCreateEmpty_GD($src_frame, $dst_imginfo, $transparency=true)
  {
    // Create empty GD-Object
    if(\function_exists('imagecreatetruecolor'))
    {
      // Needs at least php v4.0.6
      $src_frame = \imagecreatetruecolor($dst_imginfo['width'], $dst_imginfo['height']);
    }
    else
    {
      $src_frame = \imagecreate($dst_imginfo['width'], $dst_imginfo['height']);
    }

    if($transparency)
    {
      // Set transparent backgraound
      $src_frame = $this->keepTransparency_GD($src_frame, $dst_imginfo['truecolor']);
    }
    else
    {
      // Set black background
      \imagefill($src_frame, 0, 0, \imagecolorallocate($src_frame, 0, 0, 0));
    }

    return $src_frame;
  }

  /**
   * Output GD image object to file from different file types with one frame
   * Supported: JPG, PNG, GIF, WEBP
   *
   * @param   string  $dst_file     Path to destination file
   * @param   array   $dst_frame    array with one GD object for one frame ; array(array('duration'=>0, 'image'=>GDobject))
   * @param   int     $quality      Quality of the image to be saved (1-100)
   *
   * @return  mixed   True on success, false otherwise (string if $dst_file==null)
   *
   * @since   3.5.0
   */
  protected function imageWriteFrom_GD($dst_file, $dst_frame, $quality)
  {
    switch ($this->dst_type)
    {
      case 'PNG':
        // Calculate png quality, since it should be between 1 and 9
        $png_qual = ($quality - 100) / 11.111111;
        $png_qual = \round(\abs($png_qual));

        // Save transparency -- needs at least php v4.3.2
        \imagealphablending($dst_frame[0]['image'], false);
        \imagesavealpha($dst_frame[0]['image'], true);

        // Enable interlancing (progressive image transmission)
        //imageinterlace($im, true);

        if(\is_null($dst_file))
        {
          // Begin capturing the byte stream
          \ob_start();
        }

        // Write file
        $success = \imagepng($dst_frame[0]['image'], $dst_file, $png_qual);

        if(\is_null($dst_file))
        {
          // retrieve the byte stream
          $rawImageBytes = \ob_get_contents();
          \ob_end_clean();
        }
        break;
      case 'GIF':
        if(\is_null($dst_file))
        {
          // Begin capturing the byte stream
          \ob_start();
        }

        // Write file
        $success = \imagegif($dst_frame[0]['image'], $dst_file);

        if(\is_null($dst_file))
        {
          // retrieve the byte stream
          $rawImageBytes = \ob_get_contents();
          \ob_end_clean();
        }
        break;
      case 'JPG':
        // Enable interlancing (progressive image transmission)
        //imageinterlace($im, true);

        if(\is_null($dst_file))
        {
          // Begin capturing the byte stream
          \ob_start();
        }

        // Write file
        $success = \imagejpeg($dst_frame[0]['image'], $dst_file, $quality);

        if(\is_null($dst_file))
        {
          // retrieve the byte stream
          $rawImageBytes = \ob_get_contents();
          \ob_end_clean();
        }
        break;
      case 'WEBP':
        if(\is_null($dst_file))
        {
          // Begin capturing the byte stream
          \ob_start();
        }

        // Write file
        $success = \imagewebp($dst_frame[0]['image'], $dst_file, $quality);

        if(\is_null($dst_file))
        {
          // retrieve the byte stream
          $rawImageBytes = \ob_get_contents();
          \ob_end_clean();
        }
        break;
      default:
        $success = false;
        break;
    }

    if(\is_null($dst_file))
    {
      return $rawImageBytes;
    }
    else
    {
      return $success;
    }
  }

  /**
   * Flip GD image object by specified direction
   *
   * @param   object   $img_frame    GDobject of the image to flip
   * @param   string   $direction    direction in witch the image gets flipped
   *
   * @return  mixed    flipped GDobject on success, false otherwise
   *
   * @since   3.5.0
  */
  protected function imageFlip_GD($img_frame, $direction)
  {
    switch($direction)
    {
      case 'horizontally':
        $success = \imageflip($img_frame, IMG_FLIP_HORIZONTAL);
        break;
      case 'vertically':
        $success = \imageflip($img_frame, IMG_FLIP_VERTICAL);
        break;
      case 'both':
        $success = \imageflip($img_frame, IMG_FLIP_BOTH);
        break;
      case 'none':
        // 'break' intentionally omitted
      default:
        $success = true;
        break;
    }

    if($success)
    {
      return $img_frame;
    }
    else
    {
      return false;
    }
  }

  /**
   * Copy GD image object resource from one frame to another
   *
   * @param   object  $dst_img       GDobject of the destination image-frame
   * @param   object  $src_img       GDobject of the source image-frame
   *
   * @return  mixed   copied GDobject on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function imageCopy_GD($dst_img, $src_img)
  {
    // Get width from image.
    $w = \imagesx($src_img);

    // Get height from image.
    $h = \imagesy($src_img);

    // Copy the image
    \imagecopy($dst_img, $src_img, 0, 0, 0, 0, $w, $h);

    return $dst_img;
  }

  /**
   * Rotate GD image object by specified rotation angle
   *
   * @param   object     $img_frame     GDobject of the image to rotate
   * @param   string     $type          image file type
   * @param   int        $angle         rotation angle (anticlockwise)
   * @param   boolean    $transparency  transparent background color instead of black
   *
   * @return  mixed      rotated GDobject on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function imageRotate_GD($img_frame, $type, $angle, $transparency)
  {
    if($angle == 0)
    {
      return $img_frame;
    }

    // Set background color of the rotated GDobject
    if($transparency)
    {
      if(\function_exists('imagecolorallocatealpha'))
      {
        $backgroundColor = \imagecolorallocatealpha($img_frame, 0, 0, 0, 127);
      }
    }
    else
    {
      $backgroundColor = \imagecolorallocate($img_frame, 0, 0, 0);
    }

    // Rotate image
    $new_img = \imagerotate($img_frame, $angle, $backgroundColor);

    // Keeping transparency
    if($transparency)
    {
      switch ($type)
      {
        case 'PNG':
          // Special treatment for png files
          if(\function_exists('imagealphablending'))
          {
            \imagealphablending($new_img, false);
            \imagesavealpha($new_img, true);
          }
          break;
        default:
          if(\function_exists('imagecolorallocatealpha'))
          {
            \imagecolortransparent($new_img, \imagecolorallocatealpha($new_img, 0, 0, 0, 127));
          }
          break;
      }
    }

    return $new_img;
  }

  /**
   * Make sure background is still transparent
   *
   * @param   object     $img_frame     GDobject of the image to rotate
   * @param   bool       $truecolor     True if the image is a truecolor image
   *
   * @return  mixed      GDobject on success, false otherwise
   *
   * @since   4.0.0
   */
  protected function keepTransparency_GD($img_frame, $truecolor = true)
  {
    if($truecolor)
    {
      if(\function_exists('imagecolorallocatealpha'))
      {
        // Needs at least php v4.3.2
        \imagealphablending($img_frame, false);
        \imagesavealpha($img_frame, true);
        $trnprt_color = \imagecolorallocatealpha($img_frame, 0, 0, 0, 127);
        \imagefill($img_frame, 0, 0, $trnprt_color);
      }
      else
      {
        // Transparency can not be kept using GD
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_GD_NOT_KEEP_TRANSPARENCY', $this->src_type));
      }
    }
    else
    {
      if(\function_exists('imagecolorallocatealpha'))
      {
        // Needs at least php v4.3.2
        $trnprt_color = \imagecolorallocatealpha($img_frame, 0, 0, 0, 127);
        \imagefill($img_frame, 0, 0, $trnprt_color);
        \imagecolortransparent($img_frame, $trnprt_color);
      }
      else
      {
        $trnprt_indx = \imagecolortransparent($img_frame);
        $palletsize  = \imagecolorstotal($img_frame);

        if($trnprt_indx >= 0 && $trnprt_indx < $palletsize)
        {
          $trnprt_color = \imagecolorsforindex($img_frame, $trnprt_indx);
          $trnprt_indx  = \imagecolorallocate($img_frame, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
          \imagefill($img_frame, 0, 0, $trnprt_indx);
          \imagecolortransparent($img_frame, $trnprt_indx);
        }
      }
    }

    return $img_frame;
  }

  /**
   * Watermark GD image object (copy watermark on top of image)
   *
   * @param   object     $img_frame     GDobject of the image
   * @param   object     $wtm_frame     GDobject of the watermark
   * @param   array      $imginfo       array with image information
   * @param   array      $wtminfo       array with watermark information
   * @param   array      $position      position (in pixel) of watermark on image, array(x,y)
   * @param   integer    $opacity       opacity of the watermark in percent (0-100)
   *
   * @return  mixed      watermarked GDobject on success, false otherwise
   *
   * @since   3.6.0
   */
  protected function imageWatermark_GD($img_frame, $wtm_frame, $imginfo, $wtminfo, $position, $opacity)
  {
    // temporary transparent empty plane in the size of the image
    $tmp = null;
    $tmpinfo = $imginfo;
    $tmpinfo['type'] = $this->src_type;

    $tmpinfo['truecolor'] = true;
    if($this->src_type == 'GIF')
    {
      $tmpinfo['truecolor'] = false;
    }

    // Create empty GD-Object
    $tmp = $this->imageCreateEmpty_GD($tmp, $tmpinfo, true);

    // positioning watermark
    if(\function_exists('imagecopyresampled'))
    {
      \imagecopyresampled($tmp, $wtm_frame, $position[0], $position[1], 0, 0, $wtminfo['width'], $wtminfo['height'],$wtminfo['width'], $wtminfo['height']);
    }
    else
    {
      \imagecopy($tmp, $wtm_frame, $position[0], $position[1], 0, 0, $wtminfo['width'], $wtminfo['height']);
    }

    // make sure background is still transparent
    $tmp = $this->keepTransparency_GD($tmp, $wtminfo['truecolor']);

    // copy resized watermark on image
    $this->imageCopyMergeAlpha_GD($img_frame, $tmp, 0, 0, 0, 0, $imginfo['width'], $imginfo['height'], $opacity);

    return $img_frame;
  }

  /**
   * Resize GD image based on infos from $dst_imginfo
  *
   * Fast resizing of images with GD2
   * Notice: need up to 3/4 times more memory
   * http://de.php.net/manual/en/function.imagecopyresampled.php#77679
   * Plug-and-Play fastimagecopyresampled function replaces much slower
   * imagecopyresampled. Just include this function and change all
   * "imagecopyresampled" references to "fastimagecopyresampled".
   * Typically from 30 to 60 times faster when reducing high resolution
   * images down to thumbnail size using the default quality setting.
   * Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 -
   * Project: FreeRingers.net - Freely distributable - These comments must remain.
   *
   * Optional "fast_quality" parameter (defaults is 3). Fractional values are allowed,
   * for example 1.5. Must be greater than zero.
   * Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
   * 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
   * 2 = Up to 95 times faster.  Images appear a little sharp,
   *                              some prefer this over a quality of 3.
   * 3 = Up to 60 times faster.  Will give high quality smooth results very close to
   *                             imagecopyresampled, just faster.
   * 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
   * 5 = No speedup.             Just uses imagecopyresampled, no advantage over
   *                             imagecopyresampled.
   *
   * @param   object  $dst_img       GDobject of the destination image-frame
   * @param   object  $src_img       GDobject of the source image-frame
   * @param   array   $src_imginfo   array with source image information
   * @param   array   $dst_imginfo   array with destination image information
   * @param   bool    $fast_resize   resize with fastImageCopyResampled()
   * @param   int     $fast_quality  quality of destination (fix = 3) read instructions above
   *
   * @return  mixed   rotated GDobject on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function imageResize_GD($dst_frame, $src_frame, $src_imginfo, $dst_imginfo, $fast_resize = true, $fast_quality = 3)
  {
    // Check, if it is a special image (transparency or animation)
    $special = false;

    if($src_imginfo['animation'] || $src_imginfo['transparency'])
    {
      $special = true;
    }

    // Check, if GD2 is available
    $gd2 = false;
    if(\function_exists('imagecopyresampled'))
    {
      $gd2 = true;
    }

    // Encode $dst_imginfo
    $dst_x = 0;
    $dst_y = 0;
    $src_x = $dst_imginfo['offset_x'];
    $src_y = $dst_imginfo['offset_y'];
    $dst_w = $dst_imginfo['width'];
    $dst_h = $dst_imginfo['height'];
    $src_w = $dst_imginfo['src']['width'];
    $src_h = $dst_imginfo['src']['height'];


    // Perform the resize
    if($gd2 && $fast_resize && !$special && $fast_quality < 5 && (($dst_w * $fast_quality) < $src_w || ($dst_h * $fast_quality) < $src_h))
    {
      // fastimagecopyresampled
      $temp = \imagecreatetruecolor($dst_w * $fast_quality + 1, $dst_h * $fast_quality + 1);
      \imagecopyresized($temp, $src_frame, 0, 0, $src_x, $src_y, $dst_w * $fast_quality + 1,$dst_h * $fast_quality + 1, $src_w, $src_h);
      \imagecopyresampled($dst_frame, $temp, $dst_x, $dst_y, 0, 0, $dst_w,$dst_h, $dst_w * $fast_quality, $dst_h * $fast_quality);
      \imagedestroy($temp);
    }
    else
    {
      // Normal resizing
      if($gd2)
      {
        \imagecopyresampled($dst_frame, $src_frame, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
      }
      else
      {
        \imagecopyresized($dst_frame, $src_frame, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
      }
    }

    return $dst_frame;
  }

  /**
   * Same as PHP's imagecopymerge, but works with transparent images. Used internally for overlay.
   * Source: https://github.com/claviska/SimpleImage/blob/93b6df27e1d844a90d52d21a200d91b16371af0f/src/claviska/SimpleImage.php#L482
   *
   * @param  object     $dstIm Destination image link resource.
   * @param  object     $srcIm Source image link resource.
   * @param  integer    $dstX x-coordinate of destination point.
   * @param  integer    $dstY y-coordinate of destination point.
   * @param  integer    $srcX x-coordinate of source point.
   * @param  integer    $srcY y-coordinate of source point.
   * @param  integer    $srcW Source width.
   * @param  integer    $srcH Source height.
   * @param  integer    $pct
   *
   * @return boolean true if success.
   *
   * @since   3.6.0
   */
  protected function imageCopyMergeAlpha_GD($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct)
  {
    // Are we merging with transparency?
    if($pct < 100 && \function_exists('imagefilter'))
    {
      // Disable alpha blending and "colorize" the image using a transparent color
      \imagealphablending($srcIm, false);
      \imagefilter($srcIm, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * ((100 - $pct) / 100));
    }

    // if(\function_exists('imagecopyresampled'))
    // {
    //   \imagecopyresampled($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $srcW, $srcH);
    // }
    // else
    // {
      \imagecopy($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH);
    // }

    return true;
  }

  /**
   * Unsharp Mask for PHP
   * Source: https://github.com/trepmag/unsharp-mask
   *
   * @param   object   $img         GDobject of the image to filter (has to be truecolor)
   * @param   int      $amount      Amount of increasing the sharpness (0-500%)
   * @param   int      $radius      Radius of neighboring pixels that the filter affects (0-50)
   * @param   float    $threshold   How different in value an area must be to be affected (0-255)
   *
   * @return  object true if success.
   *
   * @since   3.6.0
   */
  protected function unsharpMask_GD($img, $amount, $radius, $threshold)
  {
    // Attempt to calibrate the parameters to Photoshop:
    if($amount > 500) $amount = 500;
    $amount = $amount * 0.016;

    if($radius > 50) $radius = 50;
    $radius = $radius * 2;

    if($threshold > 255) $threshold = 255;
    $radius = \abs(\round($radius));     // Only integers make sense.

    if($radius == 0)
    {
      return $img;
    }

    $w = \imagesx($img);
    $h = \imagesy($img);

    if(\function_exists('imagecreatetruecolor'))
    {
      $imgCanvas = \imagecreatetruecolor($w, $h);
      $imgBlur   = \imagecreatetruecolor($w, $h);
    }
    else
    {
      $imgCanvas = \imagecreate($w, $h);
      $imgBlur   = \imagecreate($w, $h);
    }


    // Gaussian blur matrix:
    //
    //    1    2    1
    //    2    4    2
    //    1    2    1
    //
    //////////////////////////////////////////////////


    if(\function_exists('imageconvolution'))
    { // PHP >= 5.1
      $matrix = array(
        array(1, 2, 1),
        array(2, 4, 2),
        array(1, 2, 1)
      );

      if(\function_exists('imagecopyresampled'))
      {
        \imagecopyresampled($imgBlur, $img, 0, 0, 0, 0, $w, $h, $w, $h);
      }
      else
      {
        \imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
      }

      \imageconvolution($imgBlur, $matrix, 16, 0);
    }
    else
    {

      // Move copies of the image around one pixel at the time and merge them with weight
      // according to the matrix. The same matrix is simply repeated for higher radii.
      for($i = 0; $i < $radius; $i++)
      {
        if(\function_exists('imagecopyresampled'))
        {
          \imagecopyresampled($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h, $w - 1, $h); // left
        }
        else
        {
          \imagecopy($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
        }
        $this->imageCopyMergeAlpha_GD($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
        $this->imageCopyMergeAlpha_GD($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
        if(\function_exists('imagecopyresampled'))
        {
          \imagecopyresampled($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h, $w, $h);
        }
        else
        {
          \imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);
        }
        $this->imageCopyMergeAlpha_GD($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333); // up
        $this->imageCopyMergeAlpha_GD($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
      }
    }

    if($threshold > 0)
    {
      // Calculate the difference between the blurred pixels and the original
      // and set the pixels
      for($x = 0; $x < $w - 1; $x++)
      { // each row
        for($y = 0; $y < $h; $y++)
        { // each pixel
          $rgbOrig = \imageColorAt($img, $x, $y);
          $rOrig = (($rgbOrig >> 16) & 0xFF);
          $gOrig = (($rgbOrig >> 8) & 0xFF);
          $bOrig = ($rgbOrig & 0xFF);

          $rgbBlur = \imageColorAt($imgBlur, $x, $y);

          $rBlur = (($rgbBlur >> 16) & 0xFF);
          $gBlur = (($rgbBlur >> 8) & 0xFF);
          $bBlur = ($rgbBlur & 0xFF);

          // When the masked pixels differ less from the original
          // than the threshold specifies, they are set to their original value.
          $rNew = (\abs($rOrig - $rBlur) >= $threshold) ? \max(0, \min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
          $gNew = (\abs($gOrig - $gBlur) >= $threshold) ? \max(0, \min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
          $bNew = (\abs($bOrig - $bBlur) >= $threshold) ? \max(0, \min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;



          if(($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
          {
            $pixCol = \imageColorAllocate($img, (int) $rNew, (int) $gNew, (int) $bNew);
            \imageSetPixel($img, $x, $y, $pixCol);
          }
        }
      }
    }
    else
    {
      for($x = 0; $x < $w; $x++)
      { // each row
        for($y = 0; $y < $h; $y++)
        { // each pixel
          $rgbOrig = \imageColorAt($img, $x, $y);
          $rOrig = (($rgbOrig >> 16) & 0xFF);
          $gOrig = (($rgbOrig >> 8) & 0xFF);
          $bOrig = ($rgbOrig & 0xFF);

          $rgbBlur = \imageColorAt($imgBlur, $x, $y);

          $rBlur = (($rgbBlur >> 16) & 0xFF);
          $gBlur = (($rgbBlur >> 8) & 0xFF);
          $bBlur = ($rgbBlur & 0xFF);

          $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
          if($rNew > 255)
          {
            $rNew = 255;
          }
          elseif($rNew < 0)
          {
            $rNew = 0;
          }
          $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
          if($gNew > 255)
          {
            $gNew = 255;
          }
          elseif($gNew < 0)
          {
            $gNew = 0;
          }
          $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
          if($bNew > 255)
          {
            $bNew = 255;
          }
          elseif($bNew < 0)
          {
            $bNew = 0;
          }
          $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
          \imageSetPixel($img, $x, $y, (int) $rgbNew);
        }
      }
    }

    \imagedestroy($imgCanvas);
    \imagedestroy($imgBlur);

    return $img;
  }

  /**
   * Output image to screen and close script
   * (use for debugging only)
   *
   * @param   object     $img   GDobject of the image to filter (has to be truecolor)
   * @param   string     $type  Image type (PNG, GIF, JPG or WEBP)
   *
   * @return  display image on success.
   *
   * @since   3.6.0
   */
  protected function dump_GD($img, $type)
  {
    \ob_start();

    switch ($type)
    {
      case 'PNG':
        // Save transparency -- needs at least php v4.3.2
        \imagealphablending($img, false);
        \imagesavealpha($img, true);

        $src = 'image/png';
        \imagepng($img);
        break;
      case 'GIF':
        $src = 'image/gif';
        \imagegif($img);
        break;
      case 'JPG':
        $src = 'image/jpeg';
        \imagejpeg($img);
        break;
      case 'WEBP':
        $src = 'image/webp';
        \imagejpeg($img);
        break;
      default:
        $src = 'image/jpeg';
        \imagejpeg($img);
        break;
    }

    \imagedestroy($img);
    $i = \ob_get_clean();

    echo "<img src='data:".$src.";base64," . \base64_encode( $i )."'>";

    die;
  }
}
