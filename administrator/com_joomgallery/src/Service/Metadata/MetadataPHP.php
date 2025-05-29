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

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Registry\Registry;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Metadata\Metadata as BaseMetadata;
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Filesystem\Path;

use \lsolesen\pel\Pel;
use \lsolesen\pel\PelDataWindow;
use \lsolesen\pel\PelEntryAscii;
use lsolesen\pel\PelEntryCopyright;
use lsolesen\pel\PelEntryRational;
use lsolesen\pel\PelEntrySRational;
use lsolesen\pel\PelEntryTime;
use lsolesen\pel\PelEntryUserComment;
use \lsolesen\pel\PelExif;
use \lsolesen\pel\PelFormat;
use \lsolesen\pel\PelIfd;
use \lsolesen\pel\PelJpeg;
use \lsolesen\pel\PelTag;
use \lsolesen\pel\PelTiff;

/**
 * PHP implementation of Metadata Class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MetadataPHP extends BaseMetadata implements MetadataInterface
{
  use ServiceTrait;

  /**
   * @var array
   */
  public static $entryTypes = [
    PelTag::IMAGE_DESCRIPTION => PelFormat::ASCII,
    PelTag::MAKE => PelFormat::ASCII,
    PelTag::MODEL => PelFormat::ASCII,
    PelTag::ORIENTATION => PelFormat::SHORT,
    PelTag::X_RESOLUTION => PelFormat::RATIONAL,
    PelTag::Y_RESOLUTION => PelFormat::RATIONAL,
    PelTag::RESOLUTION_UNIT => PelFormat::SHORT,
    PelTag::SOFTWARE => PelFormat::ASCII,
    PelTag::DATE_TIME => PelFormat::ASCII,
    PelTag::ARTIST => PelFormat::ASCII,
    PelTag::WHITE_POINT => PelFormat::RATIONAL,
    PelTag::PRIMARY_CHROMATICITIES => PelFormat::RATIONAL,
    PelTag::YCBCR_COEFFICIENTS => PelFormat::RATIONAL,
    PelTag::YCBCR_POSITIONING => PelFormat::SHORT,
    PelTag::COPYRIGHT => PelFormat::ASCII,
    PelTag::EXPOSURE_TIME => PelFormat::RATIONAL,
    PelTag::FNUMBER => PelFormat::RATIONAL,
    PelTag::EXPOSURE_PROGRAM => PelFormat::SHORT,
    PelTag::ISO_SPEED_RATINGS => PelFormat::SHORT,
    PelTag::EXIF_VERSION => PelFormat::UNDEFINED,
    PelTag::DATE_TIME_ORIGINAL => PelFormat::ASCII,
    PelTag::DATE_TIME_DIGITIZED => PelFormat::ASCII,
    PelTag::COMPONENTS_CONFIGURATION => PelFormat::UNDEFINED,
    PelTag::COMPRESSED_BITS_PER_PIXEL => PelFormat::RATIONAL,
    PelTag::SHUTTER_SPEED_VALUE => PelFormat::SRATIONAL,
    PelTag::APERTURE_VALUE => PelFormat::RATIONAL,
    PelTag::BRIGHTNESS_VALUE => PelFormat::SRATIONAL,
    PelTag::EXPOSURE_BIAS_VALUE => PelFormat::SRATIONAL,
    PelTag::MAX_APERTURE_VALUE => PelFormat::RATIONAL,
    PelTag::SUBJECT_DISTANCE => PelFormat::SRATIONAL,
    PelTag::METERING_MODE => PelFormat::SHORT,
    PelTag::LIGHT_SOURCE => PelFormat::SHORT,
    PelTag::FLASH => PelFormat::SHORT,
    PelTag::FOCAL_LENGTH => PelFormat::RATIONAL,
    PelTag::MAKER_NOTE => PelFormat::UNDEFINED,
    PelTag::USER_COMMENT => PelFormat::UNDEFINED,
    PelTag::FLASH_PIX_VERSION => PelFormat::UNDEFINED,
    PelTag::COLOR_SPACE => PelFormat::SHORT,
    PelTag::PIXEL_X_DIMENSION => PelFormat::LONG,
    PelTag::PIXEL_Y_DIMENSION => PelFormat::LONG,
    PelTag::RELATED_SOUND_FILE => PelFormat::ASCII,
    PelTag::INTEROPERABILITY_IFD_POINTER => PelFormat::LONG,
    PelTag::FOCAL_PLANE_X_RESOLUTION => PelFormat::RATIONAL,
    PelTag::FOCAL_PLANE_Y_RESOLUTION => PelFormat::RATIONAL,
    PelTag::FOCAL_PLANE_RESOLUTION_UNIT => PelFormat::SHORT,
    PelTag::EXPOSURE_INDEX => PelFormat::RATIONAL,
    PelTag::SENSING_METHOD => PelFormat::SHORT,
    PelTag::FILE_SOURCE => PelFormat::UNDEFINED,
    PelTag::SCENE_TYPE => PelFormat::UNDEFINED
  ];

  public function __construct() 
  {
    $this->getApp();
    $this->getComponent();
  }

  public function writeMetadata($img, $imgmetadata, $local_source=true): mixed
  {
    $tmpFolder = $this->app->get('tmp_path');
    $file = "";
    $isStream = false;
    if (\is_resource($img)) {
      $file = $img;
      $isStream = true;
    } elseif (\is_string($img) && !$local_source && \strpos($this->component->getFilesystem()->getFilesystem(), 'local') === false) {
      // The path is pointing to an external filesystem
      list($file_info, $file) = $this->component->getFilesystem()->getResource($img);
      $isStream = true;
    } elseif (\is_string($img) && ($local_source || \strpos($this->component->getFilesystem()->getFilesystem(), 'local') !== false)) {
      // The path is pointing to the local filesystem
      $img = Path::clean($img);

      if (!\file_exists($img)) {
        // Add root to the path
        $img = JPATH_ROOT . \DIRECTORY_SEPARATOR . $img;

        $img = Path::clean($img);
      }
      $file = file_get_contents($img);
    }

    $tmpPath = $tmpFolder . '/' . basename($img);

    file_put_contents($tmpPath, $file);

    $exifSuccess = self::writeToExif($tmpPath, $imgmetadata->get('exif'));
    $iptcSuccess = self::writeToIptc($tmpPath, $imgmetadata->get('iptc'));

    $data = file_get_contents($tmpPath);
    unlink($tmpPath);
    return $data;
  }

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
  public function writeToExif(string $img, $edits): bool
  {
    $file = file_get_contents($img);
    $data = new PelDataWindow($file);

    if (PelJpeg::isValid($data)) {
      // Getting initial 
      $jpeg = $file = new PelJpeg();
      $jpeg->load($data);
      $exifdata = $jpeg->getExif();
      // Check if APP1 section exists, create if not.
      if ($exifdata == null) {
        $exifdata = new PelExif();
        $jpeg->setExif($exifdata);
      }

      // Setting a blank slate for the exif data. TIFF will be set at the end of method.
      $tiff = new PelTiff();
      $ifd0 = new PelIfd(PelIfd::IFD0);
      $subIfd = new PelIfd(PelIfd::EXIF);
      $ifd0->addSubIfd($subIfd);
      $tiff->setIfd($ifd0);
    } else {
      // Invalid image format. TIFF images could be supported if desired.
      return false;
    }
    $editor = new PelDataEditor();

    // Cycle through all the necessary edits and perform them
    foreach ($edits->IFD0 as $name => $edit) {
      if (!isset(self::$entryTypes[PelTag::getExifTagByName($name)]) || $edit == "") {
        // Address does not reference a listed tag.
        continue;
      }
      $tag = PelTag::getExifTagByName($name);
      $editor->makeEdit($ifd0, $tag, self::formatForPelEntry($tag, $edit, self::$entryTypes[$tag]), self::$entryTypes[$tag]);
    }
    foreach ($edits->EXIF as $name => $edit) {
      if (!isset(self::$entryTypes[PelTag::getExifTagByName($name)]) || $edit == "") {
        // Address does not reference a listed tag.
        continue;
      }
      $tag = PelTag::getExifTagByName($name);
      $editor->makeEdit($subIfd, $tag, self::formatForPelEntry($tag, $edit, self::$entryTypes[$tag]), self::$entryTypes[$tag]);
    }

    $exifdata->setTiff($tiff);
    $file->saveFile($img);
    return true;
  }

  /**
   * Writes a list of values to the iptc metadata of an image
   * 
   * @param   string $img   Path to the image 
   * @param   mixed  $edits Array of edits to be made to the metadata
   * 
   * @return  bool          True on success, false on failure
   * 
   * @since   4.0.0
   */
  public function writeToIptc(string $img, $edits): bool
  {
    $editor = new IptcDataEditor();
    $tagString = "";
    foreach ($edits as $tag => $edit) {
      if ($edit != "") {
        $result = $editor->createEdit($tag, $edit);
        if ($result != false) {
          $tagString .= $result;
        }
      }
    }

    // If no edits were made, then don't try to embed data.
    if ($tagString == "") {
      return true;
    }

    $content = iptcembed($tagString, $img);
    $fp = fopen($img, "wb");
    fwrite($fp, $content);
    fclose($fp);
    return true;
  }

  /**
   * Reads the metadata from an image.
   * (Current supported image formats: JPEG)
   * (Current supported metadata formats: EXIF/IPTC)
   * 
   * @param  string $file  Path to the file 
   * 
   * @return array  Metadata as array
   */
  public function readMetadata(string $file)
  {
    return self::readJpegMetadata($file);
  }

  /**
   * Reads the EXIF and IPTC metadata from a JPEG.
   * 
   * @param  string $file  The image path
   * 
   * @return array         Metadata in array format
   * 
   * @since 4.0.0
   */
  public function readJpegMetadata(string $file)
  {
    // Output to the same format as before. Comment field has been left out on purpose.
    $metadata = array('exif' => array(), 'iptc' => array());
    $size = getimagesize($file, $info);

    // EXIF with PEL
    $imageObjects = self::getPelImageObjects($file);
    if ($imageObjects == false) {
      return;
    }
    $tiff = $imageObjects["tiff"];
    $ifd0 = $tiff->getIfd();
    if ($ifd0 != null) {
      $metadata['exif']['IFD0'] = array();
      foreach ($ifd0->getEntries() as $entry) {
        $metadata['exif']['IFD0'][PelTag::getName(PelIfd::IFD0, $entry->getTag())] = self::formatPELEntryForForm($entry);
      }
      $subIfd = $ifd0->getSubIfd(PelIfd::EXIF);
      if ($subIfd != null) {
        $metadata['exif']['EXIF'] = array();
        foreach ($subIfd->getEntries() as $entry) {
          $metadata['exif']['EXIF'][PelTag::getName(PelIfd::EXIF, $entry->getTag())] = self::formatPELEntryForForm($entry);
        }
      }
    }

    // IPTC
    if (isset($info["APP13"])) {
      $iptc = iptcparse($info['APP13']);
      foreach ($iptc as $key => $value) {
        $metadata['iptc'][$key] = $value[0];
      }
    }
    return $metadata;
  }

  /**
   * Gets the jpeg/tiff objects from a valid JPEG or TIFF image with PEL.
   * 
   * @param  string $img    The image data
   * 
   * @return array|false    File and tiff objects on success, false on failure.
   * 
   * @since 4.0.0
   */
  private function getPelImageObjects(string $img)
  {
    $file = file_get_contents($img);
    $data = new PelDataWindow($file);
    if (PelJpeg::isValid($data)) {
      $jpeg = $file = new PelJpeg();
      $jpeg->load($data);
      $exifdata = $jpeg->getExif();
      // Check if APP1 section exists, create if not along with tiff
      if ($exifdata == null) {
        $exifdata = new PelExif();
        $jpeg->setExif($exifdata);
        $tiff = new PelTiff();
        $exifdata->setTiff($tiff);
      }
      $tiff = $exifdata->getTiff();
    } elseif (PelTiff::isValid($data)) {
      // Data was recognized as TIFF. PelTiff/Ifd is what is being edited regardless.
      $tiff = $file = new PelTiff();
      $tiff->load($data);
    } else {
      // Handle invalid data
      return false;
    }
    return ["file" => $file, "tiff" => $tiff];
  }
  
  /**
   * Formats a PelEntry variant to be stored in a displayable format for the imgmetadata form.
   * 
   * @param  mixed $entry  Variant of the PelEntry class
   * 
   * @return mixed Value of the entry
   */
  private function formatPELEntryForForm($entry)
  {
    if ($entry instanceof PelEntryRational || $entry instanceof PelEntrySRational) {
      // Rationals are retrieved/stored as an array, they need to be reformatted for the form.
      $numbers = $entry->getValue();
      return $entry->formatNumber($numbers);
    } elseif ($entry instanceof PelEntryCopyright) {
      // Copyright is stored in PEL as an array, it needs to be reformatted for the form.
      return $entry->getText(true);
    } elseif ($entry instanceof PelEntryTime) {
      return $entry->getValue(PelEntryTime::EXIF_STRING);
    } elseif ($entry instanceof PelEntryUserComment) {
      return str_pad('ASCII', 8, chr(0)) . $entry->getValue();
    } else {
      return $entry->getValue();
    }
  }

  private function formatForPelEntry($tag, $entry, $type)
  {
    if ($type == PelFormat::RATIONAL || $type == PelFormat::SRATIONAL) {
      $explode = explode("/", $entry);
      return [intval($explode[0]), intval($explode[1])];
    } else if ($tag == PelTag::COPYRIGHT) {
      $explode = explode(" - ", $entry);
      if (!isset($explode[1])) {
        return [$explode[0], ""];
      }
      return [$explode[0], $explode[1]];
    } else {
      return $entry;
    }
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
  public function copyMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient, $bak)
  {
    $backupFile = false;

    if ($src_file == $dst_file && $bak) {
      if (!File::copy($src_file, $src_file . 'bak')) {
        return false;
      }

      $backupFile = true;
      $src_file   = $src_file . 'bak';
    }

    if ($src_imagetype == 'JPG' && $dst_imgtype == 'JPG') {
      $successExif = self::copyExifData($src_file, $dst_file, $new_orient);
      $successIptc = self::copyIptcData($src_file, $dst_file);
      $success = $successExif + $successIptc;
    } else {
      if ($src_imagetype == 'PNG' && $dst_imgtype == 'PNG') {
        $success = $this->copyPNGmetadata($src_file, $dst_file);
      } else {
        // In all other cases dont copy metadata
        $success = true;
      }
    }

    if ($backupFile) {
      File::delete($src_file);
    }

    return $success;
  }

  /**
   * Copies the metadata from one file to another with PEL.
   * 
   * @param  string $srcPath    Path to source file
   * @param  string $dstPath    Path to destination file
   * @param  int    $newOrient  New exif orientation (false: do not change exif orientation)
   * 
   * @return int
   * 
   * @since 4.0.0
   */
  public function copyExifData($srcPath, $dstPath, $newOrient = false)
  {
    $editor = new PelDataEditor();

    $srcImageObjects = self::getPelImageObjects($srcPath);
    if ($srcImageObjects == false) {
      return false;
    }
    $srcPelTiff = $srcImageObjects["tiff"];
    if ($newOrient != false) {
      $ifd0 = $srcPelTiff->getIfd(PelIfd::IFD0);
      $editor->makeEdit($ifd0, PelTag::ORIENTATION, $newOrient, PelFormat::SHORT);
    }

    $dstImageObjects = self::getPelImageObjects($dstPath);
    if ($dstImageObjects == false) {
      return false;
    }
    $dstPelFile = $dstImageObjects["file"];
    if ($dstPelFile instanceof PelJpeg) {
      $exifdata = $dstPelFile->getExif();
      $exifdata->setTiff($srcPelTiff);
    } else {
      // TIFF not currently supported
      return false;
    }
    return $dstPelFile->saveFile($dstPath);
  }

  public function copyIptcData($srcPath, $dstPath)
  {
    $editor = new IptcDataEditor();
    $srcSize = getimagesize($srcPath, $srcInfo);
    if (!isset($srcInfo['APP13'])) {
      return true;
    }
    $srcIptc = iptcparse($srcInfo['APP13']);
    $tagString = $editor->convertIptcToString($srcIptc);

    $content = iptcembed($tagString, $dstPath);
    $fp = fopen($dstPath, "wb");
    $success = fwrite($fp, $content);
    fclose($fp);
    return $success;
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
    if (\file_exists($src_file) && \file_exists($dst_file)) {
      $_src_chunks = array();
      $_fp         = \fopen($src_file, 'r');
      $chunks      = array();

      if (!$_fp) {
        // Unable to open file
        return false;
      }

      // Read the magic bytes and verify
      $header = \fread($_fp, 8);

      if ($header != "\x89PNG\x0d\x0a\x1a\x0a") {
        // Not a valid PNG image
        return false;
      }

      // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
      $chunkHeader = \fread($_fp, 8);
      while ($chunkHeader) {
        // Extract length and type from binary data
        $chunk = @\unpack('Nsize/a4type', $chunkHeader);

        // Store position into internal array
        if (!\key_exists($chunk['type'], $_src_chunks)) {
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
      if (isset($_src_chunks['iTXt'])) {
        foreach ($_src_chunks['iTXt'] as $chunk) {
          if ($chunk['size'] > 0) {
            \fseek($_fp, $chunk['offset'], SEEK_SET);
            $chunks['iTXt'] = \fread($_fp, $chunk['size']);
          }
        }
      }

      // Read tEXt chunk
      if (isset($_src_chunks['tEXt'])) {
        foreach ($_src_chunks['tEXt'] as $chunk) {
          if ($chunk['size'] > 0) {
            \fseek($_fp, $chunk['offset'], SEEK_SET);
            $chunks['tEXt'] = \fread($_fp, $chunk['size']);
          }
        }
      }

      // Read zTXt chunk
      if (isset($_src_chunks['zTXt'])) {
        foreach ($_src_chunks['zTXt'] as $chunk) {
          if ($chunk['size'] > 0) {
            \fseek($_fp, $chunk['offset'], SEEK_SET);
            $chunks['zTXt'] = \fread($_fp, $chunk['size']);
          }
        }
      }

      // Write chucks to destination image
      $_dfp = \file_get_contents($dst_file);
      $data = '';

      if (isset($chunks['iTXt'])) {
        $data .= \pack("N", \strlen($chunks['iTXt'])) . 'iTXt' . $chunks['iTXt'] . \pack("N", \crc32('iTXt' . $chunks['iTXt']));
      }

      if (isset($chunks['tEXt'])) {
        $data .= \pack("N", \strlen($chunks['tEXt'])) . 'tEXt' . $chunks['tEXt'] . \pack("N", \crc32('tEXt' . $chunks['tEXt']));
      }

      if (isset($chunks['zTXt'])) {
        $data .= \pack("N", \strlen($chunks['zTXt'])) . 'zTXt' . $chunks['zTXt'] . \pack("N", \crc32('zTXt' . $chunks['zTXt']));
      }

      $len = \strlen($_dfp);
      $png = \substr($_dfp, 0, $len - 12) . $data . \substr($_dfp, $len - 12, 12);

      return \file_put_contents($dst_file, $png);
    } else {
      // File doesn't exist
      return false;
    }
  }
}
