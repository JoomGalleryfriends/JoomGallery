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
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\GifFrameExtractor;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMGtoolsInterface;

/**
 * IMGtools Base Class
 *
 * @package JoomGallery
 *
 * @since   3.5.0
 */
abstract class IMGtools implements IMGtoolsInterface
{
  use ServiceTrait;

  /**
   * Auto orient image based on exif orientation (jpg only)
   * default: false
   *
   * @var bool
   */
  public $auto_orient = false;

  /**
   * Keep image metadata during processing
   * default: false
   *
   * @var bool
   */
  public $keep_metadata = false;

  /**
   * Keep animation during processing (gif only)
   * default: false
   *
   * @var bool
   */
  public $keep_anim = false;

  /**
   * Holds the JoomgalleryComponent object
   *
   * @var JoomgalleryComponent
   */
  protected $jg;

  /**
   * List of all supported image types (in uppercase)
   *
   * @var array
   */
  protected $supported_types = array();

  /**
   * Keep the url or string of the source file
   *
   * @var string
   */
  protected $src_file = '';

  /**
   * Keep the image type of the source file
   *
   * @var string
   */
  protected $src_type = '';

  /**
   * Keep the image type of the destination file
   *
   * @var string
   */
  protected $dst_type = '';

  /**
   * Keep the metadata of the source file
   *
   * @var array
   */
  protected $metadata = array('exif' => array(),'iptc' => array(),'comment' => array());

  /**
   * Keeps a copy of the initial imginfo arrays
   *
   * @var array
   */
  protected $ini_imginfo;

  /**
   * Holds all image information of the source image, which are relevant for
   * image processing and metadata handling
   *
   * @var array
   */
  protected $src_imginfo = array('width' => 0,
                                 'height' => 0,
                                 'orientation' => '',
                                 'transparency' => false,
                                 'animation' => false,
                                 'truecolor' => true,
                                 'frames' => 1);

  /**
   * Holds all image information of the destination image, which are relevant for
   * image processing and metadata handling
   *
   * @var array
   */
   protected $dst_imginfo = array('width' => 0,
                                 'height' => 0,
                                 'orientation' => '',
                                 'truecolor' => true,
                                 'ratio' => 1,
                                 'offset_x' => 0,
                                 'offset_y' => 0,
                                 'angle' => 0,
                                 'flip' => 'none',
                                 'quality' => 100,
                                 'src' => array('width' => 0,'height' => 0));

  /**
   * Holds all image information of finished processed file
   *
   * @var array
   */
   protected $res_imginfo = array('width' => 0,
                                 'height' => 0,
                                 'orientation' => '',
                                 'transparency' => false,
                                 'animation' => false,
                                 'truecolor' => true,
                                 'frames' => 1);

  /**
   * Constructor
   *
   * @param   bool    $keep_metadata   True: Image keeps its metadata during processing (only: jpg, png / default: false)
   * @param   bool    $keep_anim       True: Image keeps animation during processing (only: gif / default: false)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($keep_metadata=false, $keep_anim=false)
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    $this->keep_metadata   = $keep_metadata;
    $this->keep_anim       = $keep_anim;
    $this->supported_types = $this->getTypes();

    // Backup the imginfo arrays
    $this->ini_imginfo = array('src' => $this->src_imginfo, 'dst' => $this->dst_imginfo, 'res' => $this->res_imginfo);
  }

  /**
   * Add supported image types of currently used image processor to debug output
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function types(): void
  {
    $types = \implode(', ', $this->get('supported_types'));
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_SUPPORTED_TYPES', $types));

    return;
  }

  /**
   * Validation and analysis of an image-file
   *
   * @param   string   $img         Path to source file or image string
   * @param   bool     $is_stream   True if $src is image string (stream) (default: false)
   *
   * @return  mixed    Imageinfo on success, false otherwise
   *
   * @since   3.5.0
   */
  public function analyse($img, $is_stream = false)
  {
    // Check, if file exists and is a valid image
    if(!$is_stream && !($this->checkValidImage($img)))
    {
      return false;
    }

    // create stream wrapper in case a string is passed as $img
    if($is_stream)
    {
      if(\is_resource($img))
      {
        $string_stream = \stream_get_contents($img);
      }
      else
      {
        $string_stream = $img;
      }

      $info = \getimagesizefromstring($string_stream);
    }
    else
    {
      // $img is a filepath
      $info = \getimagesize($img);
    }

    // Extract width and height from info
    $this->res_imginfo['width'] = $info[0];
    $this->res_imginfo['height'] = $info[1];

    // Extract bits and channels from info
    if(isset($info['bits']))
    {
      $this->res_imginfo['bits'] = $info['bits'];
    }

    if(isset($info['channels']))
    {
      $this->res_imginfo['channels'] = $info['channels'];
    }

    // Decrypt the imagetype
    $imagetype = array(0=>'UNKNOWN', 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF',
                       5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC',
                       10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF',
                       15=>'WBMP', 16=>'XBM', 17=>'ICO', 18=>'WEBP', 19=>'COUNT');

    $this->src_type = $imagetype[$info[2]];

    // Get the image orientation
    $this->res_imginfo['orientation'] = $this->getOrientation($info[0], $info[1]);

    // Detect, if image is a special image
    if($this->src_type == 'PNG')
    {
      // Detect if png is based on palettes
      if($is_stream)
      {
        $included = \strpos($string_stream, "PLTE");
      }
      else
      {
        $included = \strpos(\file_get_contents($img), "PLTE");
      }

      if($included !== false)
      {
        $this->res_imginfo['truecolor'] = false;
      }

      // Detect, if png has transparency
      if($is_stream)
      {
        $pngtype = \ord(\substr($string_stream, 25, 1));
      }
      else
      {
        $pngtype = \ord(\file_get_contents($img, false, null, 25, 1));
      }

      if($pngtype == 4 || $pngtype == 6)
      {
        $this->res_imginfo['transparency'] = true;
      }
    }

    // Detect, if image is a transparent or animated webp image
    if($this->src_type == 'WEBP')
    {
      // Detect, if webp has transparency or animation
      if($is_stream)
      {
        $webptype = \ord(\substr($string_stream, 25, 1));
      }
      else
      {
        $webptype = \file_get_contents($img);

        // Detect if webp is based on palettes
        $included = \strpos($webptype, "VP8L");
        if($included !== false)
        {
          // The VP8L signature which indicates lossless WebP that might use a palette
          $this->res_imginfo['truecolor'] = false;
        }

        // Detect if webp is transparent
        $included = \strpos($webptype, "ALPH");
        if($included !== false)
        {
          $this->res_imginfo['transparency'] = true;
        }
        else
        {
          $included = \strpos($webptype, "VP8L");
          if($included !== false)
          {
            $this->res_imginfo['transparency'] = true;
          }
        }

        // Detect, if webp has animation
        $included = \strpos($webptype, "ANIM");
        if($included !== false)
        {
          $this->res_imginfo['animation'] = true;
        }
        else
        {
          $included = \strpos($webptype, "ANMF");
          if($included !== false)
          {
            $this->res_imginfo['animation'] = true;
          }
        }

        if($this->res_imginfo['animation'] == true)
        {
          $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_UPLOAD_ANIMATED_WEBP'));
          $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_UPLOAD_ANIMATED_WEBP'), 'warning', 'jerror');

          return false;
        }
      }
    }

    if($this->src_type == 'GIF')
    {
      // GIF is never truecolor
      $this->res_imginfo['truecolor'] = false;

      // Detect, if gif is animated
      $count = 0;
      if(!$is_stream)
      {
        $fh    = @\fopen($img, 'rb');

        while(!\feof($fh) && $count < 2)
        {
          $chunk  = \fread($fh, 1024 * 100); //read 100kb at a time
          $count += \preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
        }

        \fclose($fh);
      }

      // Detect, if gif has transparency
      if($is_stream)
      {
        $tmp     = \imagecreatefromstring($img);
      }
      else
      {
        $tmp     = \imagecreatefromgif($img);
      }
      $tmp_trans = \imagecolortransparent($tmp);

      if($count > 1 && $tmp_trans == -1)
      {
        $this->res_imginfo['animation'] = true;

        $gfe = new GifFrameExtractor();
        $gfe->parseFramesInfo($img);
        $this->res_imginfo['frames'] = $gfe->getNumberFramens();
      }
      else
      {
        if($count > 1 && $tmp_trans >= 0)
        {
          $this->res_imginfo['animation']    = true;
          $this->res_imginfo['transparency'] = true;

          $gfe = new GifFrameExtractor();
          $gfe->parseFramesInfo($img);
          $this->res_imginfo['frames'] = $gfe->getNumberFramens();
        }
        else
        {
          if($count <= 1 && $tmp_trans >= 0)
          {
            $this->res_imginfo['transparency'] = true;
          }
        }
      }
    }

    // read image metadata
    if(!$is_stream)
    {
      $this->readImageMetadata($img);
    }


    return $this->res_imginfo;
  }

  /**
   * Read meta data from given image (Supported: JPG,PNG / EXIF,IPTC)
   *
   * @param   string  $img             Path to the image file
   *
   * @return  array   The array with all meta data from the image if exists
   *
   * @since   3.5.0
   */
  public function readMetadata($img): array
  {
    $this->readImageMetadata($img);

    return $this->metadata;
  }

  /**
   * Copy image metadata depending on file type (Supported: JPG,PNG / EXIF,IPTC)
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @param   string  $src_imagetype   Type of the source image file
   * @param   string  $dst_imgtype     Type of the destination image file
   * @param   int     $new_orient      New exif orientation (false: do not change exif orientation)
   * @param   bool    $bak             true, if a backup-file should be created if $src_file=$dst_file
   *
   * @return  int     number of bytes written on success, false otherwise
   *
   * @since   3.5.0
   */
  public function copyMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient, $bak): bool
  {
    $backupFile = false;

    if($src_file == $dst_file && $bak)
    {
      if(!File::copy($src_file, $src_file.'bak'))
      {
        return false;
      }

      $backupFile = true;
      $src_file   = $src_file.'bak';
    }

    if($src_imagetype == 'JPG' && $dst_imgtype == 'JPG')
    {
      $success = $this->copyJPGmetadata($src_file,$dst_file,$new_orient);
    }
    else
    {
      if($src_imagetype == 'PNG' && $dst_imgtype == 'PNG')
      {
        $success = $this->copyPNGmetadata($src_file,$dst_file);
      }
      else
      {
        // In all other cases dont copy metadata
        $success = true;
      }
    }

    if($backupFile)
    {
      File::delete($src_file);
    }

    return $success;
  }

  //////////////////////////////////////////////////
  //   Protected functions with basic features.
  //////////////////////////////////////////////////

  /**
   * Clears the class variables and bring it back to default
   *
   * @return  void
   *
   * @since   3.5.0
  */
  protected function clearVariables()
  {
    // Clear the imginfo arrays
    $this->clearImginfo(\array_keys($this->ini_imginfo));

    // Clear the frames
    $this->src_frames   = array(array('duration' => 0,'image' => null));
    $this->dst_frames   = array(array('duration' => 0,'image' => null));
  }

  /**
   * Clears the class variables and bring it back to default
   *
   * @param   string|array  $key  The name of the imginfo array to be cleared
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function clearImginfo($key)
  {
    if(!\is_array($key))
    {
      $key = array($key);
    }

    foreach ($key as $kval)
    {
      $name = $kval . '_imginfo';
      $this->{$name} = $this->ini_imginfo[$kval];
    }
  }

  /**
   * Check if the file is a valid image file
   *
   * @param   string    $img    Path to image file
   *
   * @return  bool      True if image is valid, false otherwise
   *
   * @since   3.5.0
  */
  protected function checkValidImage($img)
  {
    // Path must point to an existing file
    if(!(\is_file(Path::clean($img))))
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_EXISTING'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_EXISTING'), 'error', 'jerror');

      return false;
    }

    $imginfo = \getimagesize($img);

    // Image needs to have a valid file type
    if(!$imginfo || $imginfo[2] == 0 || !\key_exists('mime',$imginfo) || $imginfo['mime'] == '')
    {
      return false;
    }

    // If available, bits has to be between 1 and 64
    if(\key_exists('bits',$imginfo))
    {
      if($imginfo['bits'] < 1 || $imginfo['bits'] > 64)
      {
        return false;
      }
    }

    // Get width and height from $imginfo[3]
    $str    = \explode(' ', $imginfo[3]);
    $width  = \explode('=', $str[0]);
    $width  = $width[1];
    $width  = \str_replace('"', '', $width);
    $height = \explode('=', $str[1]);
    $height = $height[1];
    $height = \str_replace('"', '', $height);

    // Image width and height as to be between 1 and 1'000'000 pixel
    if( $width < 1 || $height < 1 || $imginfo[0] < 1 || $imginfo[1] < 1
        ||
        $width > 1000000 || $height > 1000000 || $imginfo[0] > 1000000  || $imginfo[1] > 1000000
      )
    {
      return false;
    }

    return true;
  }

  /**
   * Collect information for the resize (information: dimensions, type, origin)
   *
   * Cropping function adapted from
   * 'Resize Image with Different Aspect Ratio'
   * Author: Nash
   * Website: http://nashruddin.com/Resize_Image_to_Different_Aspect_Ratio_on_the_fly
   *
   *
   * @param   string  imgtype           Path of destination image file
   * @param   int     $method           Resize to 0:noresize,1:height,2:width,3:proportional,4:crop
   * @param   int     $new_width        Width to resize
   * @param   int     $new_height       Height to resize
   * @param   int     $cropposition     Only if $settings=2; image section to use for cropping
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function getResizeInfo($imgtype, $method, $new_width, $new_height, $cropposition)
  {
    // Height/width
    if($this->dst_imginfo['angle'] == 0 || $this->dst_imginfo['angle'] == 180)
    {
      $srcWidth  = $this->src_imginfo['width'];
      $srcHeight = $this->src_imginfo['height'];
    }
    else
    {
      $srcWidth  = $this->src_imginfo['height'];
      $srcHeight = $this->src_imginfo['width'];
    }

    switch($method)
    {
    case 0:
      // no resizing
      $ratio = 1;
    case 1:
      // calculate ratio by height
      $ratio = ($srcHeight / $new_height);

      break;
    case 2:
      // calculate ratio by width
      $ratio = ($srcWidth / $new_width);

      break;
    case 4:
      // Free resizing and cropping
      if($srcWidth < $new_width)
      {
        $new_width = $srcWidth;
      }

      if($srcHeight < $new_height)
      {
        $new_height = $srcHeight;
      }

      // Expand the thumbnail's aspect ratio to fit the width/height of the image
      $ratiowidth  = $srcWidth / $new_width;
      $ratioheight = $srcHeight / $new_height;

      if($ratiowidth < $ratioheight)
      {
        $ratio = $ratiowidth;
      }
      else
      {
        $ratio = $ratioheight;
      }

      // Calculate the offsets for cropping the source image according to thumbposition
      switch($cropposition)
      {
        case 0:
          // Left upper corner
          $this->dst_imginfo['offset_x'] = 0;
          $this->dst_imginfo['offset_y'] = 0;
          break;
        case 1:
          // Right upper corner
          $this->dst_imginfo['offset_x'] = (int)\floor(($srcWidth - ($new_width * $ratio)));
          $this->dst_imginfo['offset_y'] = 0;
          break;
        case 3:
          // Left lower corner
          $this->dst_imginfo['offset_x'] = 0;
          $this->dst_imginfo['offset_y'] = (int)\floor(($srcHeight - ($new_height * $ratio)));
          break;
        case 4:
          // Right lower corner
          $this->dst_imginfo['offset_x'] = (int)\floor(($srcWidth - ($new_width * $ratio)));
          $this->dst_imginfo['offset_y'] = (int)\floor(($srcHeight - ($new_height * $ratio)));
          break;
        default:
          // Default center
          $this->dst_imginfo['offset_x'] = (int)\floor(($srcWidth - ($new_width * $ratio)) * 0.5);
          $this->dst_imginfo['offset_y'] = (int)\floor(($srcHeight - ($new_height * $ratio)) * 0.5);
          break;
      }
      break;
    case 3:
      // Resize to maximum allowed dimensions but keeping original ratio
      // calculate ratio by height
      $ratio     = ($srcHeight / $new_height);
      $testwidth = ($srcWidth / $ratio);

      // If new width exceeds set max. width
      if($testwidth > $new_width)
      {
        // calculate ratio by width
        $ratio = ($srcWidth / $new_width);
      }
      break;
    default:
      echo 'undefined "settings"-parameter!';

      return false;
    }

    // Calculate widths and heights necessary for resize and bring them to integer values
    if($method != 4)
    {
      // Not cropping
      $this->dst_imginfo['ratio'] = $ratio = \max($ratio, 1.0);
      $this->dst_imginfo['width']          = (int)\floor($srcWidth / $ratio);
      $this->dst_imginfo['height']         = (int)\floor($srcHeight / $ratio);
      $this->dst_imginfo['src']['width']   = (int)$srcWidth;
      $this->dst_imginfo['src']['height']  = (int)$srcHeight;

    }
    else
    {
      // Cropping
      $this->dst_imginfo['ratio']         = $ratio;
      $this->dst_imginfo['width']         = (int)$new_width;
      $this->dst_imginfo['height']        = (int)$new_height;
      $this->dst_imginfo['src']['width']  = (int)($this->dst_imginfo['width'] * $ratio);
      $this->dst_imginfo['src']['height'] = (int)($this->dst_imginfo['height'] * $ratio);
    }

    // Preserve information about truecolor
    $this->dst_imginfo['truecolor'] = $this->src_imginfo['truecolor'];

    return true;
  }

  /**
   * Collect information for the watermarking
   * (information: dimensions, type, position)
   *
   * @param   array   $imginfo        array with image information of the background image
   * @param   int     $position       Positioning of the watermark
   * @param   int     $resize         resize watermark (0:no,1:by height,2:by width)
   * @param   float   $new_size       new size of the resized watermark in percent related to the file (1-100)
   *
   * @return  array   array with watermark positions; array(x,y)
   *
   * @since   3.6.0
   */
  protected function getWatermarkingInfo($imginfo, $position, $resize, $new_size): array
  {
    // generate information about the new width and height
    if($resize)
    {

      if($new_size <= 0)
      {
        $new_size = 1;
      }
      elseif($new_size > 100)
      {
        $new_size = 100;
      }

      $widthwm  = $this->src_imginfo['width'];
      $heightwm = $this->src_imginfo['height'];

      if($resize == 1)
      {
        // Resize by height
        $newheight_watermark = $imginfo['height'] * $new_size / 100;
        $newwidth_watermark  = $newheight_watermark * $widthwm / $heightwm;

        if($newwidth_watermark > $imginfo['width'])
        {
          $newwidth_watermark  = $imginfo['width'];
        }
      }
      else
      {
        // Resize by width
        $newwidth_watermark  = $imginfo['width'] * $new_size / 100;
        $newheight_watermark = $newwidth_watermark * $heightwm / $widthwm;

        if($newheight_watermark > $imginfo['height'])
        {
          $newheight_watermark = $imginfo['height'];
        }
      }
    }
    else
    {
      $newwidth_watermark = $this->src_imginfo['width'];
      $newheight_watermark = $this->src_imginfo['height'];
    }
    $this->dst_imginfo['width']  = (int) \round($newwidth_watermark);
    $this->dst_imginfo['height'] = (int) \round($newheight_watermark);

    // Other information of the resized watermark image
    $this->dst_imginfo['orientation']   = $this->src_imginfo['orientation'];
    $this->dst_imginfo['src']['width']  = $this->src_imginfo['width'];
    $this->dst_imginfo['src']['height'] = $this->src_imginfo['height'];

    // Generate information about position of the watermark inside the src image
    // Position x
    switch(($position - 1) % 3)
    {
      case 1:
        $pos_x = \round(($imginfo['width'] - $this->dst_imginfo['width']) / 2, 0);
        break;
      case 2:
        $pos_x = $imginfo['width'] - $this->dst_imginfo['width'];
        break;
      default:
        $pos_x = 0;
        break;
    }
    // Position y
    switch(\floor(($position - 1) / 3))
    {
      case 1:
        $pos_y = \round(($imginfo['height'] - $this->dst_imginfo['height']) / 2, 0);
        break;
      case 2:
        $pos_y = $imginfo['height'] - $this->dst_imginfo['height'];
        break;
      default:
        $pos_y = 0;
        break;
    }

    // Preserve information about truecolor
    $this->dst_imginfo['truecolor'] = $this->src_imginfo['truecolor'];

    return array($pos_x, $pos_y);
  }

  /**
   * Get orientation string based on width and height value
   *
   * @param   int      $width    image width
   * @param   int      $height   image height
   *
   * @return  string   Orientation string
   *
   * @since   4.0.0
  */
  protected function getOrientation($width, $height)
  {
    if($width > $height)
    {
      return 'landscape';
    }
    elseif($height < $width)
    {
      return 'portrait';
    }
    else
    {
      return 'square';
    }
  }

  /**
   * Get angle and flip value based on exif orientation tag
   *
   * @param   int       $orientation    exif-orientation
   *
   * @return  boolean   true on success
   *
   * @since   3.5.0
  */
  protected function autoOrient($orientation = 1)
  {
    $this->auto_orient = true;

    switch($orientation)
    {
      case 1: // Do nothing!
        $this->dst_imginfo['flip']  = 'none';
        $this->dst_imginfo['angle'] = 0;
        break;
      case 2: // Flip horizontally
        $this->dst_imginfo['flip']  = 'horizontally';
        $this->dst_imginfo['angle'] = 0;
        break;
      case 3: // Rotate 180 degrees
        $this->dst_imginfo['flip']  = 'none';
        $this->dst_imginfo['angle'] = 180;
        break;
      case 4: // Flip vertically
        $this->dst_imginfo['flip']  = 'vertically';
        $this->dst_imginfo['angle'] = 0;
        break;
      case 5: // Rotate 90 degrees clockwise and flip vertically
        $this->dst_imginfo['flip']  = 'vertically';
        $this->dst_imginfo['angle'] = 270;
        break;
      case 6: // Rotate 90 clockwise
        $this->dst_imginfo['flip']  = 'none';
        $this->dst_imginfo['angle'] = 270;
        break;
      case 7: // Rotate 90 clockwise and flip horizontally
        $this->dst_imginfo['flip']  = 'horizontally';
        $this->dst_imginfo['angle'] = 270;
        break;
      case 8: // Rotate 90 anticlockwise
        $this->dst_imginfo['flip']  = 'none';
        $this->dst_imginfo['angle'] = 90;
        break;
      default:
        $this->dst_imginfo['flip']  = 'none';
        $this->dst_imginfo['angle'] = 0;
        break;
    }

    return true;
  }

  /**
   * Get exif orientation tag based on rotation angle
   *
   * @param   int       $angle       rotation angle (anticlockwise)
   *
   * @return  int       exif-orientation
   *
   * @since   3.5.0
  */
  protected function exifOrient($angle = 0)
  {
    switch($angle)
    {
      case 0: // Do nothing!
        $orientation = false;
        break;
      case 90: // Rotate 90 anticlockwise
        $orientation = 8;
        break;
      case 180: // Rotate 180 degrees
        $orientation = 3;
        break;
      case 270: // Rotate 270 anticlockwise
        $orientation = 6;
        break;
      case 360: // Rotate 360 anticlockwise
        $orientation = 1;
        break;
      case -90: // Rotate 90 clockwise
        $orientation = 6;
        break;
      case -180: // Rotate 180 degrees
        $orientation = 3;
        break;
      case -270: // Rotate 270 clockwise
        $orientation = 8;
        break;
      case -360: // Rotate 360 clockwise
        $orientation = 1;
        break;
      default: // in all other cases
        $orientation = false;
        break;
    }

    return $orientation;
  }

  /**
   * Restore initial state, if something goes wrong
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   *
   * @return  int     number of bytes written on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function rollback($src_file, $dst_file)
  {
    // Restore src from backup file or delete corrupt dst file
    if($src_file == $dst_file && $src_file != '')
    {
      if(\is_file(Path::clean($src_file.'bak')))
      {
        File::copy($src_file.'bak',$src_file);
        File::delete($src_file.'bak');
      }
    }
    elseif($dst_file != '')
    {
      if(\is_file(Path::clean($dst_file.'bak')))
      {
        File::copy($dst_file.'bak',$dst_file);
        File::delete($dst_file.'bak');
      }
      else
      {
        if(\is_file(Path::clean($dst_file)))
        {
          File::delete($dst_file);
        }
      }
    }

    // Reset class variables
    $this->clearVariables();

    return true;
  }

  /**
   * Check, if there are any errors
   * Error: if there is a false in $value
   *
   * @param   mixed   $value    variable to be checked for errors (any datatype except Object)
   *
   * @return  boolean true, if there are any errors. False otherwise
   *
   * @since   3.5.0
   */
  protected function checkError($value)
  {
    if(\is_array($value))
    {
      return $this->in_array_r(false, $value);
    }
    else
    {
      if($value == false)
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Adds different variants of JPEG to types array
   *
   * @param   array   $types    list of supported file types
   *
   * @return  array   $types    list of supported file types
   *
   * @since   4.0.0
   */
  protected function addJpegTypes($types)
  {
    // Add different file types of JPEG files
    if(\in_array('JPEG',$types))
    {
      if(!\in_array('JPG',$types))
      {
        \array_push($types, 'JPG');
      }
      if(!\in_array('JPE',$types))
      {
        \array_push($types, 'JPE');
      }
      if(!\in_array('JFIF',$types))
      {
        \array_push($types, 'JFIF');
      }
    }

    return $types;
  }

  /**
   * Read image metadata from image
   *
   * @param   string  img  The image file to read
   *
   * @return  void
   *
   * @since   3.5.0
   */
  protected function readImageMetadata($img)
  {
    $size = \getimagesize($img, $info);

    // Check the installation of Exif
    if(\extension_loaded('exif') && \function_exists('exif_read_data') && $size[2] == 2)
    {
      // Read EXIF data (only JPG)
      $exif_tmp = \exif_read_data($img, null, 1);

      if(isset($exif_tmp['IFD0']))
      {
        $this->metadata['exif']['IFD0'] = $exif_tmp['IFD0'];
      }

      if(isset($exif_tmp['EXIF']))
      {
        $this->metadata['exif']['EXIF'] = $exif_tmp['EXIF'];
      }

      // Read COMMENT
      if(isset($exif_tmp['COMMENT']) && isset($exif_tmp['COMMENT'][0]))
      {
        $this->metadata['comment'] = $exif_tmp['COMMENT'][0];
      }
    }

    // Get IPTC data
    if(isset($info["APP13"]))
    {
      $iptc_tmp = \iptcparse($info['APP13']);

      foreach($iptc_tmp as $key => $value)
      {
        $this->metadata['iptc'][$key] = $value[0];
      }
    }

    return;
  }

  /**
   * Copy IPTC and EXIF Data of a jpg from source to destination image
   *
   * function adapted from
   * Author: ebashkoff
   * Website: https://www.php.net/manual/de/function.iptcembed.php
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @param   int     $new_orient      New exif orientation (false: do not change exif orientation)
   *
   * @return  int     number of bytes written on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function copyJPGmetadata($src_file, $dst_file, $new_orient = false)
  {
    // Function transfers EXIF (APP1) and IPTC (APP13) from $src_file and adds it to $dst_file
    // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
    // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is
    // most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
    // of (strlen(<data>) + 2)

    if(\file_exists($src_file) && \file_exists($dst_file))
    {
      $srcsize = @\getimagesize($src_file, $imageinfo);
      $dstsize = @\getimagesize($dst_file, $destimageinfo);

      // Check if file is jpg
      if($srcsize[2] != 2 && $dstsize[2] != 2)
      {
        return false;
      }

      // Prepare EXIF data bytes from source file
      $exifdata = (\is_array($imageinfo) && \key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
      if($exifdata)
      {
        // Find the image's original orientation flag, and change it to $new_oreint value
        if($new_orient != false)
        {
          list($success, $exifdata) = $this->replace_exif_orientation($exifdata, $new_orient);
          if(!$success)
          {
            return false;
          }
        }

        $exiflength = \strlen($exifdata) + 2;
        if($exiflength > 0xFFFF)
        {
          return false;
        }

        // Construct EXIF segment
        $exifdata = \chr(0xFF) . \chr(0xE1) . \chr(($exiflength >> 8) & 0xFF) . \chr($exiflength & 0xFF) . $exifdata;
      }

      // Prepare IPTC data bytes from source file
      $iptcdata = (\is_array($imageinfo) && \key_exists("APP13", $imageinfo)) ? $imageinfo['APP13'] : null;
      if($iptcdata)
      {
        $iptclength = \strlen($iptcdata) + 2;
        if($iptclength > 0xFFFF)
        {
          return false;
        }

        // Construct IPTC segment
        $iptcdata = \chr(0xFF) . \chr(0xED) . \chr(($iptclength >> 8) & 0xFF) . \chr($iptclength & 0xFF) . $iptcdata;
      }

      // Check destination file
      $destfilecontent = @\file_get_contents($dst_file);
      if(!$destfilecontent)
      {
        return false;
      }

      if(\strlen($destfilecontent) > 0)
      {
        $destfilecontent = \substr($destfilecontent, 2);
        $portiontoadd    = \chr(0xFF) . \chr(0xD8);          // Variable accumulates new & original IPTC application segments
        $exifadded       = !$exifdata;
        $iptcadded       = !$iptcdata;

        while(($this->get_safe_chunk(substr($destfilecontent, 0, 2)) & 0xFFF0) === 0xFFE0)
        {
          $segmentlen        = ($this->get_safe_chunk(substr($destfilecontent, 2, 2)) & 0xFFFF);
          $iptcsegmentnumber = ($this->get_safe_chunk(substr($destfilecontent, 1, 1)) & 0x0F);   // Last 4 bits of second byte is IPTC segment #

          if($segmentlen <= 2)
          {
            return false;
          }

          $thisexistingsegment = \substr($destfilecontent, 0, $segmentlen + 2);

          if((1 <= $iptcsegmentnumber) && (!$exifadded))
          {
            $portiontoadd .= $exifdata;
            $exifadded     = true;

            if(1 === $iptcsegmentnumber)
            {
              $thisexistingsegment = '';
            }
          }

          if((13 <= $iptcsegmentnumber) && (!$iptcadded))
          {
            $portiontoadd .= $iptcdata;
            $iptcadded     = true;

            if(13 === $iptcsegmentnumber)
            {
              $thisexistingsegment = '';
            }
          }

          $portiontoadd   .= $thisexistingsegment;
          $destfilecontent = \substr($destfilecontent, $segmentlen + 2);
        }

        if(!$exifadded) $portiontoadd .= $exifdata;  //  Add EXIF data if not added already
        if(!$iptcadded) $portiontoadd .= $iptcdata;  //  Add IPTC data if not added already

        // $outputfile = fopen($dst_file, 'w');
        // if($outputfile)
        // {
        //   return fwrite($outputfile, $portiontoadd . $destfilecontent);
        // }
        // else
        // {
        //   return false;
        // }
        return \file_put_contents($dst_file, $portiontoadd . $destfilecontent);
      }
      else
      {
        // Destination file doesn't exist
        return false;
      }
    }
    else
    {
      // Source file doesn't exist
      return false;
    }
  }

  /**
   * Copy iTXt, tEXt and zTXt chunks of a png from source to destination image
   *
   * read chunks; adapted from
   * Author: Andrew Moore
   * Website: https://stackoverflow.com/questions/2190236/how-can-i-read-png-metadata-from-php
   *
   * write chunks; adapted from
   * Author: leonbloy
   * Website: https://stackoverflow.com/questions/8842387/php-add-itxt-comment-to-a-png-image
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   *
   * @return  int     number of bytes written on success, false otherwise
   *
   * @since   3.5.0
   */
  protected function copyPNGmetadata($src_file, $dst_file)
  {
    if(\file_exists($src_file) && \file_exists($dst_file))
    {
      $_src_chunks = array();
      $_fp         = \fopen($src_file, 'r');
      $chunks      = array();

      if(!$_fp)
      {
        // Unable to open file
        return false;
      }

      // Read the magic bytes and verify
      $header = \fread($_fp, 8);

      if($header != "\x89PNG\x0d\x0a\x1a\x0a")
      {
        // Not a valid PNG image
        return false;
      }

      // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
      $chunkHeader = \fread($_fp, 8);
      while($chunkHeader)
      {
        // Extract length and type from binary data
        $chunk = @\unpack('Nsize/a4type', $chunkHeader);

        // Store position into internal array
        if(!\key_exists($chunk['type'], $_src_chunks))
        {
          $_src_chunks[$chunk['type']] = array();
        }

        $_src_chunks[$chunk['type']][] = array(
            'offset' => \ftell($_fp),
            'size' => $chunk['size']
        );

        // Skip to next chunk (over body and CRC)
        \fseek($_fp, $chunk['size'] + 4, SEEK_CUR);

        // Read next chunk header
        $chunkHeader = \fread($_fp, 8);
      }

      // Read iTXt chunk
      if(isset($_src_chunks['iTXt']))
      {
        foreach($_src_chunks['iTXt'] as $chunk)
        {
          if($chunk['size'] > 0)
          {
            \fseek($_fp, $chunk['offset'], SEEK_SET);
            $chunks['iTXt'] = \fread($_fp, $chunk['size']);
          }
        }
      }

      // Read tEXt chunk
      if(isset($_src_chunks['tEXt']))
      {
        foreach($_src_chunks['tEXt'] as $chunk)
        {
          if($chunk['size'] > 0)
          {
            \fseek($_fp, $chunk['offset'], SEEK_SET);
            $chunks['tEXt'] = \fread($_fp, $chunk['size']);
          }
        }
      }

      // Read zTXt chunk
      if(isset($_src_chunks['zTXt']))
      {
        foreach($_src_chunks['zTXt'] as $chunk)
        {
          if($chunk['size'] > 0)
          {
            \fseek($_fp, $chunk['offset'], SEEK_SET);
            $chunks['zTXt'] = \fread($_fp, $chunk['size']);
          }
        }
      }

      // Write chucks to destination image
      $_dfp = \file_get_contents($dst_file);
      $data = '';

      if(isset($chunks['iTXt']))
      {
        $data .= \pack("N",\strlen($chunks['iTXt'])) . 'iTXt' . $chunks['iTXt'] . \pack("N", \crc32('iTXt' . $chunks['iTXt']));
      }

      if(isset($chunks['tEXt']))
      {
        $data .= \pack("N",\strlen($chunks['tEXt'])) . 'tEXt' . $chunks['tEXt'] . \pack("N", \crc32('tEXt' . $chunks['tEXt']));
      }

      if(isset($chunks['zTXt']))
      {
        $data .= \pack("N",\strlen($chunks['zTXt'])) . 'zTXt' . $chunks['zTXt'] . \pack("N", \crc32('zTXt' . $chunks['zTXt']));
      }

      $len = \strlen($_dfp);
      $png = \substr($_dfp,0,$len-12) . $data . \substr($_dfp,$len-12,12);

      return \file_put_contents($dst_file, $png);
    }
    else
    {
      // File doesn't exist
      return false;
    }
  }

  /**
   * Replaces the actual exif orientation tag in
   * a given exifdata string
   *
   * @param   string  $exifdata    binary APP1-Segment of image header (TIFF or JFIF)
   *                               ( usually received by getimagesize($file, $imginfo); $imginfo['APP1'] )
   * @param   int     $newVal      numeric value for the new orientation
   *
   * @return  array   [1]: true on success false otherwise / [2]: modified $exifdata on success, debuginfo otherwise
   *
   * @since   3.5.0
   */
  protected function replace_exif_orientation($exifdata, $newVal)
  {
    $IFD_Data_Sizes = array(1 => 1,         // Unsigned Byte
                            2 => 1,         // ASCII String
                            3 => 2,         // Unsigned Short
                            4 => 4,         // Unsigned Long
                            5 => 8,         // Unsigned Rational
                            6 => 1,         // Signed Byte
                            7 => 1,         // Undefined
                            8 => 2,         // Signed Short
                            9 => 4,         // Signed Long
                            10 => 8,        // Signed Rational
                            11 => 4,        // Float
                            12 => 8 );      // Double

    $tmp_folder = $this->app->get('tmp_path');
    $tmp_file   = $tmp_folder.'/tmp.txt';

    if(isset($exifdata))
    {
      \file_put_contents($tmp_file, $exifdata);
    }

    $filehnd = @\fopen($tmp_file, 'rb');

    // Check if the file opened successfully
    if(!$filehnd)
    {
      // Delete file
      \unlink($tmp_file);

      // Could't open the file - exit
      return array(false, 'Could not open file: ' . $tmp_file);
    }

    // Overstep the EXIF header
    \fseek($filehnd, 6);

    // Read the eight bytes of the TIFF header
    $DataStr = $this->network_safe_fread($filehnd, 8);

    // Check that we did get all eight bytes
    if(\strlen($DataStr) != 8)
    {
      // Delete file
      \unlink($tmp_file);

      // Couldn't read the TIFF header properly
      return array(false, 'Couldnt read the TIFF header - EXIF is probably Corrupted');
    }

    $pos = 0;
    // First two bytes indicate the byte alignment - should be 'II' or 'MM'
    // II = Intel (LSB first, MSB last - Little Endian)
    // MM = Motorola (MSB first, LSB last - Big Endian)
    $Byte_Align = \substr($DataStr, $pos, 2);

    // Check the Byte Align Characters for validity
    if(($Byte_Align != "II") && ($Byte_Align != "MM"))
    {
      // Delete file
      \unlink($tmp_file);

      // Byte align field is invalid - we won't be able to decode file
      return array(false, 'Byte align field is invalid - EXIF is probably Corrupted');
    }

    // Skip over the Byte Align field which was just read
    $pos += 2;

    // Next two bytes are TIFF ID - should be value 42 with the appropriate byte alignment
    $TIFF_ID = \substr($DataStr, $pos, 2);

    if($this->get_IFD_Data_Type($TIFF_ID, 3, $Byte_Align) != 42)
    {
      // Delete file
      \unlink($tmp_file);

      // TIFF header ID not found
      return array(false, 'TIFF header ID not found - EXIF is probably Corrupted');
    }

    // Skip over the TIFF ID field which was just read
    $pos += 2;

    // Next four bytes are the offset to the first IFD
    $offset_str = \substr($DataStr, $pos, 4);
    $offset     = $this->get_IFD_Data_Type($offset_str, 4, $Byte_Align);

    // Done reading TIFF Header

    // Move to first IFD: IFD0

    // First 2 bytes of IFD0 are number of entries in the IFD
    $No_Entries_str = $this->network_safe_fread($filehnd, 2);
    $No_Entries     = $this->get_IFD_Data_Type($No_Entries_str, 3, $Byte_Align);

    // If the data is corrupt, the number of entries may be huge, which will cause errors
    // This is often caused by a lack of a Next-IFD pointer
    if ($No_Entries > 10000 || $No_Entries == 0)
    {
      // Delete file
      \unlink($tmp_file);

      // Huge number of entries - abort
      return array(false, 'Huge number of EXIF entries - EXIF is probably Corrupted');
    }

    // Initialise current position to the start
    $pos = \ftell($filehnd);

    // Read in the IFD structure
    $IFD_Data = $this->network_safe_fread($filehnd, 12 * $No_Entries);

    // Check if the entire IFD was able to be read
    if(strlen($IFD_Data) != (12 * $No_Entries))
    {
      // Delete file
      \unlink($tmp_file);

      // Couldn't read the IFD Data properly, Some Casio files have no Next IFD pointer, hence cause this error
      return array(false, 'Couldnt read the IFD Data properly - EXIF is probably Corrupted');
    }

    // Last 4 bytes of a standard IFD are the offset to the next IFD
    // Some NON-Standard IFD implementations do not have this, hence causing problems if it is read

    // Loop through the IFD entries and get the position of the orientation entry
    for($i = 0; $i < $No_Entries; $i++)
    {
      \fseek($filehnd, $pos);

      // First 2 bytes of IFD entry are the tag number ( Unsigned Short )
      $Tag_No_str = $this->network_safe_fread($filehnd, 2);
      $Tag_No     = $this->get_IFD_Data_Type($Tag_No_str, 3, $Byte_Align);

      if($Tag_No == 274)
      {
        $pos_274 = $pos;
        \fseek($filehnd, $pos + 12);
        $pos = \ftell($filehnd);
      }
      else
      {
        \fseek($filehnd, $pos + 12);
        $pos = \ftell($filehnd);
      }
    }

    // go to the orientation-entry position
    \fseek($filehnd, $pos_274);

    // First 2 bytes of IFD entry are the tag number ( Unsigned Short )
    $orient_Tag_No_str = $this->network_safe_fread($filehnd, 2);
    $orient_Tag_No     = $this->get_IFD_Data_Type($orient_Tag_No_str, 3, $Byte_Align);

    // Next 2 bytes of IFD entry are the data format ( Unsigned Short )
    $orient_Data_Type_str = $this->network_safe_fread($filehnd, 2);
    $orient_Data_Type     = $this->get_IFD_Data_Type($orient_Data_Type_str, 3, $Byte_Align);

    // If Datatype is not between 1 and 12, then skip this entry, it is probably corrupted or custom
    if(($orient_Data_Type > 12) || ($orient_Data_Type < 1))
    {
      // Delete file
      \unlink($tmp_file);

      return array(false, 'Couldnt identify Datatype - EXIF is probably Corrupted');
    }

    // Next 4 bytes of IFD entry are the data count ( Unsigned Long )
    $orient_Data_Count_str = $this->network_safe_fread($filehnd, 4);
    $orient_Data_Count     = $this->get_IFD_Data_Type($orient_Data_Count_str, 4, $Byte_Align);

    if($orient_Data_Count > 100000)
    {
      // Delete file
      \unlink($tmp_file);

      return array(false, 'Huge number of IFD-Entries - EXIF is probably Corrupted');
    }

    // Total Data size is the Data Count multiplied by the size of the Data Type
    $orient_Total_Data_Size = $IFD_Data_Sizes[ $orient_Data_Type ] * $orient_Data_Count;

    if($orient_Total_Data_Size > 4)
    {
      // Delete file
      \unlink($tmp_file);

      return array(false, 'To big data-size for EXIF-Orientation tag! - EXIF is probably Corrupted');
    }
    else
    {
      // The data block is less than 4 bytes, and is provided in the IFD entry, so read it
      $orient_DataStr = $this->network_safe_fread($filehnd, $orient_Total_Data_Size);
    }

    // Read the data items from the data block
    if ($orient_Data_Type == 1 || $orient_Data_Type == 3 || $orient_Data_Type == 4)
    {
      $orient_Data = $this->get_IFD_Data_Type($orient_DataStr, $orient_Data_Type, $Byte_Align);
    }
    else
    {
      // Delete file
      \unlink($tmp_file);

      return array(false, 'Couldnt identify Datatype - EXIF is probably Corrupted');
    }

    // Finish reading file
    \fclose($filehnd);

    // Open file for writing
    $filewrite = \fopen($tmp_file, 'cb');

    // Go to the data block of the IFD0->274 entry (exif orientation)
    \fseek($filewrite, $pos_274 + 2 + 2 + 4);

    // Bring $newVal to binary
    $newVal_bin = $this->put_IFD_Data_Type($newVal, $orient_Data_Type, $Byte_Align);

    // Write new orientation to file
    \fwrite($filewrite, $newVal_bin);

    // Finish writing file
    \fclose($filewrite);

    // Read file to string
    $new_exifdata = \file_get_contents($tmp_file);

    // Delete file
    \unlink($tmp_file);

    return array(true, $new_exifdata);
  }

   /**
   * in_array() for multidimensional array
   * Source: https://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
   *
   * @param   string   $needle        The searched value
   * @param   array    $haystack      The array to be searched
   * @param   boolean  $strict        If true it will also check the types of the needle in the haystack
   *
   * @return  boolean  true if needle is found in the array, false otherwise
   *
   * @since   3.5.0
   */
  protected function in_array_r($needle, $haystack, $strict = true)
  {
    foreach($haystack as $item)
    {
      if(($strict ? $item === $needle : $item == $needle) || (\is_array($item) && $this->in_array_r($needle, $item, $strict)))
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Get integer value of binary chunk.
   * Source: https://plugins.trac.wordpress.org/browser/image-watermark/tags/1.6.6#image-watermark.php#line:954
   *
   * @param   mixed   $value  Binary data
   *
   * @return  int     int value of binary data
   */
  protected function get_safe_chunk($value)
  {
    // Check for numeric value
    if(\is_numeric($value))
    {
      // Cast to integer to do bitwise AND operation
      return (int) $value;
    }
    else
    {
      return 0;
    }
  }

  /**
   * Retrieves data from a file. This function is required since
   * the fread function will not always return the requested number
   * of characters when reading from a network stream or pipe
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * @param   object  $file_handle   File system pointer that is typically created using fopen()
   * @param   int     $length        Number of bytes read
   *
   * @return  string  the data read from the file
   *
   * @since   3.5.0
   */
  protected function network_safe_fread($file_handle, $length)
  {
    // Create blank string to receive data
    $data = "";

    // Keep reading data from the file until either EOF occurs or we have
    // retrieved the requested number of bytes

    while((!\feof($file_handle)) && (\strlen($data) < $length))
    {
      $data .= \fread($file_handle, $length-\strlen($data));
    }

    // Return the data read
    return $data;
  }

  /**
   * Decodes an IFD field from a value to a binary data string, using
   * information supplied about the data type and byte alignment of
   * the stored data.
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * Data Types TIFF 6.0 specification:
   *  1 = Unsigned 8-bit Byte
   *  2 = ASCII String
   *  3 = Unsigned 16-bit Short
   *  4 = Unsigned 32-bit Long
   *  5 = Unsigned 2x32-bit Rational
   *  6 = Signed 8-bit Byte
   *  7 = Undefined
   *  8 = Signed 16-bit Short
   *  9 = Signed 32-bit Long
   *  10 = Signed 2x32-bit Rational
   *  11 = 32-bit Float
   *  12 = 64-bit Double
   *
   * Byte alignment indicators:
   *  MM = Motorola, MSB first, Big Endian
   *  II = Intel, LSB first, Little Endian
   *
   * @param   string  $input_data    Binary data string containing the IFD value, must be exact length of the value.
   * @param   int     $data_type     Number representing the IFD datatype (see above)
   * @param   string  $Byte_Align    Indicates the byte alignment of the data.
   *
   * @return  string  the value of the data (string or numeric)
   *
   * @since   3.5.0
   */
  protected function get_IFD_Data_Type($input_data, $data_type, $Byte_Align)
  {
    // Check if this is a Unsigned Byte, Unsigned Short or Unsigned Long
    if(($data_type == 1) || ($data_type == 3) || ($data_type == 4))
    {
      // This is a Unsigned Byte, Unsigned Short or Unsigned Long

      // Check the byte alignment to see if the bytes need tp be reversed
      if($Byte_Align == "II")
      {
        // This is in Intel format, reverse it
        $input_data = \strrev( $input_data );
      }

      // Convert the binary string to a number and return it
      return \hexdec(\bin2hex($input_data));
    }
    // Check if this is a ASCII string type
    elseif($data_type == 2)
    {
      // Null terminated ASCII string(s)
      // The input data may represent multiple strings, as the
      // 'count' field represents the total bytes, not the number of strings
      // Hence this should not be processed here, as it would have
      // to return multiple values instead of a single value

      echo "<p>Error - ASCII Strings should not be processed in get_IFD_Data_Type</p>\n";
      $this->component->addLog('Error - ASCII Strings should not be processed in get_IFD_Data_Type', 'error', 'jerror');

      return "Error Should never get here"; //explode( "\x00", $input_data );
    }
    // Check if this is a Unsigned rational type
    elseif($data_type == 5)
    {
      // This is a Unsigned rational type

      // Check the byte alignment to see if the bytes need to be reversed
      if($Byte_Align == "MM")
      {
        // Motorola MSB first byte alignment
        // Unpack the Numerator and denominator and return them
        return \unpack('NNumerator/NDenominator', $input_data);
      }
      else
      {
        // Intel LSB first byte alignment
        // Unpack the Numerator and denominator and return them
        return \unpack('VNumerator/VDenominator', $input_data);
      }
    }
    // Check if this is a Signed Byte, Signed Short or Signed Long
    elseif(( $data_type == 6) || ($data_type == 8) || ($data_type == 9))
    {
      // This is a Signed Byte, Signed Short or Signed Long

      // Check the byte alignment to see if the bytes need to be reversed
      if($Byte_Align == "II")
      {
        //Intel format, reverse the bytes
        $input_data = \strrev($input_data);
      }

      // Convert the binary string to an Unsigned number
      $value = \hexdec(\bin2hex( $input_data ));

      // Convert to signed number

      // Check if it is a Byte above 128 (i.e. a negative number)
      if(($data_type == 6) && ($value > 128))
      {
        // number should be negative - make it negative
        return  $value - 256;
      }

      // Check if it is a Short above 32767 (i.e. a negative number)
      if(($data_type == 8) && ($value > 32767))
      {
        // number should be negative - make it negative
        return  $value - 65536;
      }

      // Check if it is a Long above 2147483648 (i.e. a negative number)
      if(($data_type == 9) && ($value > 2147483648))
      {
        // number should be negative - make it negative
        return  $value - 4294967296;
      }

      // Return the signed number
      return $value;
    }
    // Check if this is Undefined type
    elseif($data_type == 7)
    {
      // Custom Data - Do nothing
      return $input_data;
    }
            // Check if this is a Signed Rational type
    elseif($data_type == 10)
    {
      // This is a Signed Rational type

      // Signed Long not available with endian in unpack , use unsigned and convert

      // Check the byte alignment to see if the bytes need to be reversed
      if($Byte_Align == "MM")
      {
        // Motorola MSB first byte alignment
        // Unpack the Numerator and denominator
        $value = \unpack('NNumerator/NDenominator', $input_data);
      }
      else
      {
        // Intel LSB first byte alignment
        // Unpack the Numerator and denominator
        $value = \unpack('VNumerator/VDenominator', $input_data);
      }

      // Convert the numerator to a signed number
      // Check if it is above 2147483648 (i.e. a negative number)
      if($value['Numerator'] > 2147483648)
      {
        // number is negative
        $value['Numerator'] -= 4294967296;
      }

      // Convert the denominator to a signed number
      // Check if it is above 2147483648 (i.e. a negative number)
      if($value['Denominator'] > 2147483648)
      {
        // number is negative
        $value['Denominator'] -= 4294967296;
      }

      // Return the Signed Rational value
      return $value;
    }
    // Check if this is a Float type
    elseif($data_type == 11)
    {
      // IEEE 754 Float
      // TODO - EXIF - IFD datatype Float not implemented yet
      return "FLOAT NOT IMPLEMENTED YET";
    }
    // Check if this is a Double type
    elseif($data_type == 12)
    {
      // IEEE 754 Double
      // TODO - EXIF - IFD datatype Double not implemented yet
      return "DOUBLE NOT IMPLEMENTED YET";
    }
    else
    {
      // Error - Invalid Datatype
      return "Invalid Datatype $data_type";

    }
  }

   /**
   * Encodes an IFD field from a value to a binary data string, using
   * information supplied about the data type and byte alignment of
   * the stored data.
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * Data Types TIFF 6.0 specification:
   *  1 = Unsigned 8-bit Byte
   *  2 = ASCII String
   *  3 = Unsigned 16-bit Short
   *  4 = Unsigned 32-bit Long
   *  5 = Unsigned 2x32-bit Rational
   *  6 = Signed 8-bit Byte
   *  7 = Undefined
   *  8 = Signed 16-bit Short
   *  9 = Signed 32-bit Long
   *  10 = Signed 2x32-bit Rational
   *  11 = 32-bit Float
   *  12 = 64-bit Double
   *
   * Byte alignment indicators:
   *  MM = Motorola, MSB first, Big Endian
   *  II = Intel, LSB first, Little Endian
   *
   * @param   string  $input_data    IFD data value, numeric or string
   * @param   int     $data_type     Number representing the IFD datatype (see above)
   * @param   string  $Byte_Align    Indicates the byte alignment of the data.
   *
   * @return  string  the packed binary string of the data
   *
   * @since   3.5.0
   */
  protected function put_IFD_Data_Type($input_data, $data_type, $Byte_Align)
  {
    // Process according to the datatype
    switch($data_type)
    {
      case 1: // Unsigned Byte - return character as is
        return \chr($input_data);
        break;
      case 2: // ASCII String
        // Return the string with terminating null
        return $input_data . "\x00";
        break;
      case 3: // Unsigned Short
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the short and return
          return \pack("v", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the short and return
          return \pack("n", $input_data);
        }
        break;
      case 4: // Unsigned Long
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the long and return
          return \pack("V", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the long and return
          return \pack("N", $input_data);
        }
        break;
      case 5: // Unsigned Rational
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the two longs and return
          return \pack("VV", $input_data['Numerator'], $input_data['Denominator']);
        }
        else
        {
          // Motorola/Big Endian - pack the two longs and return
          return \pack("NN", $input_data['Numerator'], $input_data['Denominator']);
        }
        break;
      case 6: // Signed Byte
        // Check if number is negative
        if( $input_data < 0)
        {
          // Number is negative - return signed character
          return \chr($input_data + 256);
        }
        else
        {
          // Number is positive - return character
          return \chr($input_data);
        }
        break;
      case 7: // Unknown - return as is
        return $input_data;
        break;

      case 8: // Signed Short
        // Check if number is negative
        if( $input_data < 0)
        {
          // Number is negative - make signed value
          $input_data = $input_data + 65536;
        }
        // Check byte alignment
        if ($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the short and return
          return \pack("v", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the short and return
          return \pack("n", $input_data);
        }
        break;
      case 9: // Signed Long
        // Check if number is negative
        if( $input_data < 0)
        {
          // Number is negative - make signed value
          $input_data = $input_data + 4294967296;
        }
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the long and return
          return \pack("v", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the long and return
          return \pack("n", $input_data);
        }
        break;
      case 10: // Signed Rational
        // Check if numerator is negative
        if($input_data['Numerator'] < 0)
        {
          // Number is numerator - make signed value
          $input_data['Numerator'] = $input_data['Numerator'] + 4294967296;
        }
        // Check if denominator is negative
        if($input_data['Denominator'] < 0)
        {
          // Number is denominator - make signed value
          $input_data['Denominator'] = $input_data['Denominator'] + 4294967296;
        }
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the two longs and return
          return \pack("VV", $input_data['Numerator'], $input_data['Denominator']);
        }
        else
        {
          // Motorola/Big Endian - pack the two longs and return
          return \pack("NN", $input_data['Numerator'], $input_data['Denominator']);
        }
        break;
      case 11: // Float
        // IEEE 754 Float
        // TODO - EXIF - IFD datatype Float not implemented yet
        return "FLOAT NOT IMPLEMENTED YET";
        break;
      case 12: // Double
        // IEEE 754 Double
        // TODO - EXIF - IFD datatype Double not implemented yet
        return "DOUBLE NOT IMPLEMENTED YET";
        break;
      default:
        // Error - Invalid Datatype
        return "Invalid Datatype $data_type";
        break;
    }

    // Shouldn't get here
    return false;
  }
}
