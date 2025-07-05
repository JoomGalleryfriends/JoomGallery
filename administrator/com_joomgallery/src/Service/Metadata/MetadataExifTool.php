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

use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Metadata\Metadata as BaseMetadata;

/**
 * ExifTools (CGI) implementation of Metadata Class
 *
 * @package JoomGallery
 * @since   4.1.0
 */
class MetadataExifTool extends BaseMetadata implements MetadataInterface
{
  use ServiceTrait;

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
   * @param   string $img    Path to the image 
   * @param   mixed  $edits  Iptc object in imgmetadata
   * 
   * @return  bool           True on success, false on failure
   * 
   * @since   4.1.0
   */
  public function writeToIptc(string $img, $edits): bool
  {
    // Currently unimplemented, will be implemented.
    return false;
  }
}
