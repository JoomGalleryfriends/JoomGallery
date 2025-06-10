<?php

/**
 ******************************************************************************************
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
 * Metadata Base Class
 *
 * @package JoomGallery
 * @since   4.1.0
 */
class Metadata implements MetadataInterface
{
  use ServiceTrait;

  public function readMetadata(string $file)
  {
    return false;
  }

  public function copyMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient, $bak)
  {
    return false;
  }

  public function writeMetadata($img, $imgmetadata, $local_source=true): mixed
  {
    return false;
  }

  /**
   * Writes a list of values to the exif metadata of an image
   * 
   * @param   string $img    Path to the image 
   * @param   mixed  $edits  Exif object in imgmetadata
   * 
   * @return  bool           True on success, false on failure
   * 
   * @since   4.1.0
   */
  public function writeToExif(string $img, $edits): bool
  {
    return false;
  }

  /**
   * Saves an edit to the iptc metadata of an image
   * 
   * @param   string $img   Path to the image 
   * @param   mixed $edits  Array of edits to be made to the metadata
   * 
   * @return  bool          True on success, false on failure
   * 
   * @since   4.1.0
   */
  public function writeToIptc(string $img, $edits): bool
  {
    return false;
  }
}
