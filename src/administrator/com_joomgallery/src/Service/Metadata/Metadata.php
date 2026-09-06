<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use Joomla\CMS\Factory;
use Joomla\Registry\Registry;

/**
 * Metadata Base Class
 *
 * @package JoomGallery
 * @since   4.1.0
 */
class Metadata implements MetadataInterface
{
  use ServiceTrait;

  /**
   * Prepares stored metadata for rendering.
   *
   * @param   mixed         $imgmetadata           Stored image metadata
   * @param   array|string  $importantMetadataKeys Metadata keys rendered outside the modal
   *
   * @return  array<int, Registry> Important and remaining metadata registries
   *
   * @since   __DEPLOY_VERSION__
   */
  public function renderPrep($imgmetadata, $importantMetadataKeys = []): array
  {
    $language = Factory::getApplication()->getLanguage();
    $language->load('com_joomgallery.exif', JPATH_ADMINISTRATOR . '/components/com_joomgallery');
    $language->load('com_joomgallery.iptc', JPATH_ADMINISTRATOR . '/components/com_joomgallery');

    require JPATH_ADMINISTRATOR . '/components/com_joomgallery/includes/exifarray.php';
    require JPATH_ADMINISTRATOR . '/components/com_joomgallery/includes/iptcarray.php';

    $definitions = [];
    foreach($exif_config_array as $group => $entries)
    {
      foreach($entries as $entry)
      {
        $attribute = (string) ($entry['Attribute'] ?? '');
        if($attribute !== '')
        {
          $definitions['exif.' . $group . '.' . $attribute] = $entry;
        }
      }
    }

    foreach($iptc_config_array as $entries)
    {
      foreach($entries as $entry)
      {
        $tag = str_replace(':', '#', (string) ($entry['IMM'] ?? ''));
        if($tag !== '')
        {
          $definitions['iptc.' . $tag] = $entry;
        }
      }
    }

    $items   = [];
    $flatten = function ($values, array $path = []) use (&$flatten, &$items, $definitions): void {
      foreach((array) $values as $key => $value)
      {
        $itemPath = [...$path, (string) $key];
        if(\is_array($value) || \is_object($value))
        {
          $flatten($value, $itemPath);
          continue;
        }
        if($value === '' || $value === null)
        {
          continue;
        }

        $pathKey         = implode('.', $itemPath);
        $definition      = $definitions[$pathKey] ?? null;
        $attribute       = (string) ($definition['Attribute'] ?? $key);
        $items[$pathKey] = [
          'path'  => $pathKey,
          'key'   => (string) $key,
          'label' => $definition['Name'] ?? (string) $key,
          'value' => preg_match('/(?:DateTime|Date Created|Time Created|Digital Creation Date)/i', $attribute)
            ? $this->formatRenderDate((string) $value)
            : (string) $value,
        ];
      }
    };
    $flatten((new Registry($imgmetadata))->toArray());

    $importantKeys = \is_array($importantMetadataKeys)
      ? $importantMetadataKeys
      : preg_split('/[\r\n,;]+/', (string) $importantMetadataKeys, -1, PREG_SPLIT_NO_EMPTY);
    $importantKeys = array_map('trim', $importantKeys ?: []);
    $important     = [];
    $remaining     = $items;
    foreach($importantKeys as $importantKey)
    {
      foreach($items as $pathKey => $item)
      {
        if($importantKey === $pathKey || $importantKey === $item['key'] || str_ends_with($pathKey, '.' . $importantKey))
        {
          $important[$pathKey] = $item;
          unset($remaining[$pathKey]);
        }
      }
    }

    return [new Registry(['items' => array_values($important)]), new Registry(['items' => array_values($remaining)])];
  }

  /** Format supported EXIF/IPTC date representations for display. */
  private function formatRenderDate(string $value): string
  {
    foreach(['!Y:m:d H:i:s', '!Y-m-d H:i:s', '!Y-m-d\\TH:i:sP', '!Ymd H:i:s', '!Ymd'] as $format)
    {
      $date   = \DateTimeImmutable::createFromFormat($format, trim($value));
      $errors = \DateTimeImmutable::getLastErrors();
      if($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)))
      {
        return $date->format('d.m.Y H:i');
      }
    }

    return $value;
  }

  public function readMetadata(string $file)
  {
    return false;
  }

  public function copyMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient, $bak)
  {
    return false;
  }

  public function writeMetadata($img, $imgmetadata, $local_source = true): mixed
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
