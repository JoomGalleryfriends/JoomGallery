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

\defined('JPATH_PLATFORM') or die;

/**
* Interface for the IMGtools helper class
*
* @since  4.0.0
*/
interface IMGtoolsInterface
{
  /**
   * Constructor
   *
   * @param   string  $debugoutput     Debug information
   * @param   bool    $keep_metadata   True: Image keeps its metadata during processing (only: jpg, png / default: false)
   * @param   bool    $keep_anim       True: Image keeps animation during processing (only: gif / default: false)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($debugoutput, $keep_metadata, $keep_anim);

  /**
   * Read image from file or image string (stream)
   * Supported image-types: ??
   *
   * @param   string  $file        Path to source file or image string
   * @param   bool    $is_stream   True if $src is image string (stream) (default: false)
   * @param   bool    $base64      True if input string is base64 decoded (default: false)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function read($file, $is_stream = false, $base64 = false): bool;

  /**
   * Write image to file
   * Supported image-types: ??
   *
   * @param   string  $file     Path to destination file
   * @param   int     $quality  Quality of the resized image (1-100, default: 100)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function write($file, $quality=100): bool;

  /**
   * Output image as string (stream)
   * Supported image-types: jpg,png,gif
   *
   * @param   int     $quality  Quality of the resized image (1-100, default: 100)
   * @param   string  $type     Set image type to write (default: same as source)
   *
   * @return  string  base64 encoded image string
   *
   * @since   4.0.0
   */
  public function stream($quality=100, $type=false): string;

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
  public function analyse($img, $is_stream = false);

	/**
   * Resize image
   * Supported image-types: ??
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
   * @since   1.0.0
   */
  public function resize($method, $width, $height, $cropposition=2, $unsharp=false): bool;

  /**
   * Rotate image
   * Supported image-types: ??
   *
   * @param   int     $angle          Angle to rotate the image anticlockwise
   *
   * @return  bool    True on success, false otherwise (false, if no rotation is needed)
   *
   * @since   3.4.0
   */
  public function rotate($angle): bool;

  /**
   * Flip image
   * Supported image-types: ??
   *
   * @param   int     $direction       Direction to flip the image (0:none,1:horizontal,2:vertical,3:both)
   *
   * @return  bool    True on success, false otherwise (false, if no flipping is needed)
   *
   * @since   4.0.0
   */
  public function flip($direction): bool;

  /**
   * Auto orientation of the image based on EXIF meta data
   * Supported image-types: JPG
   *
   * @return  bool    True on success, false otherwise (false, if no flipping is needed)
   *
   * @since   4.0.0
   */
  public function orient(): bool;

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
  public function watermark($wtm_file, $wtm_pos, $wtm_resize, $wtm_newSize, $opacity): bool;

  /**
   * Copy image metadata depending on file type (Supported: JPG,PNG / EXIF,IPTC)
   *
   * @param   string  $img             Path to the image file
   *
   * @return  array   The array with all meta data from the image if exists
   *
   * @since   3.5.0
   */
  public function readMetadata($img): array;

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
  public function copyMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient, $bak): bool;

}
