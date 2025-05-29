<?php
/**
******************************************************************************************
**   @version    4.0.0-dev                                                              **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2024  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

\defined('_JEXEC') or die;


/**
* Interface for the metadata class
*
* @since  4.0.0
*/
interface MetadataInterface
{
  /**
   * Reads the metadata from an image.
   * (Current supported image formats: JPEG)
   * (Current supported metadata formats: EXIF/IPTC)
   * 
   * @param  string $file  Path to the file 
   * 
   * @return array  Metadata as array
   */
  public function readMetadata(string $file);

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
  public function copyMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient, $bak);

  /**
   * Writes the stored metadata to the specified image
   * 
   * @param  string $img          Path to source file or resource
   * @param  mixed  $imgmetadata  Stored image metadata
   * @param  bool   $local_source True if source is a file located in a local folder
   * 
   * @return mixed                Image data to be stored with Filemanager
   */
  public function writeMetadata($img, $imgmetadata, $local_source);

  /**
   * Writes a list of values to the exif metadata of an image
   * 
   * @param   string $img    Path to the image 
   * @param   mixed  $edits  Exif object in imgmetadata
   * 
   * @return  bool           True on success, false on failure
   * 
   * @since   4.0.0
   */
  public function writeToExif(string $img, $edits): bool;

  /**
   * Saves an edit to the iptc metadata of an image
   * 
   * @param   string $img    Path to the image 
   * @param   mixed  $edits  Iptc object in imgmetadata
   * 
   * @return  bool           True on success, false on failure
   * 
   * @since   4.0.0
   */
  public function writeToIptc(string $img, $edits): bool;
}
