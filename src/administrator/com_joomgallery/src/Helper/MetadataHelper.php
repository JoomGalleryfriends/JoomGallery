<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;

/**
 * JoomGallery Helper for EXIF/IPTC fields
 *
 * @static
 * @package JoomGallery
 * @since   4.5.0
 */
class MetadataHelper
{
  /**
   * Translated metadata definitions, cached by language tag and metadata type.
   *
   * @var array<string, array>
   */
  private static array $definitions = [];

  /**
   * Resolve a numeric tag, attribute name, IPTC IMM code or full metadata path.
   * The optional group disambiguates tags belonging to different IFDs.
   * Matching is case-insensitive and accepts numeric suffixes for repeated
   * values (e.g. iptc.2#025.0). Without a group, the first match is returned.
   * Definitions and their translations are loaded once per language and type.
   *
   * @param   string       $type   Metadata type: 'exif' or 'iptc' (case-insensitive).
   * @param   int|string   $tag    Definition ID, attribute name, IPTC IMM code (2:025 or 2#025),
   *                               or metadata path (e.g. exif.IFD0.Orientation).
   * @param   string|null  $group  Optional definition section, e.g. IFD0, EXIF, GPS or IPTC.
   *
   * @return  array        Matching definition, including translated names and enumerations;
   *                       an empty array when the type or tag is unknown.
   *
   * @since   4.5.0
   */
  public static function getDefinition(string $type, $tag, ?string $group = null): array
  {
    $type = strtolower($type);

    if(!\in_array($type, ['exif', 'iptc'], true)) return [];
    $language = Factory::getApplication()->getLanguage();
    $cacheKey = $language->getTag() . ':' . $type;

    if(!isset(self::$definitions[$cacheKey]))
    {
      $base = \dirname(__DIR__, 2);
      $language->load('com_joomgallery.' . $type, JPATH_ADMINISTRATOR . '/components/com_joomgallery');
      require $base . '/includes/' . $type . 'array.php';
      self::$definitions[$cacheKey] = $type === 'exif' ? $exif_config_array : $iptc_config_array;
    }

    $tag = preg_replace('/^' . $type . '\\./i', '', (string) $tag);

    foreach(self::$definitions[$cacheKey] as $section => $entries)
    {
      if($group !== null && strcasecmp($group, $section) !== 0) continue;

      foreach($entries as $id => $entry)
      {
        $attribute = (string) ($entry['Attribute'] ?? '');
        $imm       = str_replace(':', '#', (string) ($entry['IMM'] ?? ''));
        $aliases   = [(string) $id, $attribute, $section . '.' . $attribute, $section . '.' . $id];

        if($imm !== '') $aliases = array_merge($aliases, [$imm, str_replace('#', ':', $imm), $section . '.' . $imm]);

        foreach($aliases as $alias)
        {
          if($alias !== '' && (strcasecmp($tag, $alias) === 0 || preg_match('/^' . preg_quote($alias, '/') . '(?:\\.\\d+)+$/i', $tag)))
            return $entry;
        }
      }
    }

    return [];
  }

  /**
   * Return the translated name of the specified metadata tag.
   *
   * @param   string       $type   Metadata type: 'exif' or 'iptc'.
   * @param   int|string   $tag    Tag identifier or path accepted by getDefinition().
   * @param   string|null  $group  Optional definition section used to disambiguate the tag.
   *
   * @return  string       Translated field name, or the supplied tag as a string if no name is found.
   *
   * @since   4.5.0
   */
  public static function getName(string $type, $tag, ?string $group = null): string
  {
    return (string) (self::getDefinition($type, $tag, $group)['Name'] ?? $tag);
  }

  /**
   * Format a metadata value using its definition and translated enumerations.
   *
   * Enumerated values are resolved from the definition or an array-valued Units
   * field. Denum fractions with nonzero denominators are converted to decimal
   * values rounded to six places. String units are appended to non-enumerated
   * values. Arrays and object properties are formatted recursively and joined
   * with commas; unknown codes and free text retain their trimmed values.
   *
   * Note: This method returns unescaped display text. Callers must escape it
   * for HTML or encode it for JSON. It does not format dates or calculate
   * degrees/minutes/seconds coordinates.
   *
   * @param   string       $type   Metadata type: 'exif' or 'iptc'.
   * @param   int|string   $tag    Tag identifier or path accepted by getDefinition().
   * @param   mixed        $value  Raw scalar value, or an array/object containing values.
   * @param   string|null  $group  Optional definition section used to disambiguate the tag.
   *
   * @return  string       Formatted display value; an empty string for empty text, null
   *                       or other unsupported non-scalar values.
   *
   * @since   4.5.0
   */
  public static function getValue(string $type, $tag, $value, ?string $group = null): string
  {
    if(\is_array($value) || \is_object($value))
      return implode(', ', array_map(static fn($part) => self::getValue($type, $tag, $part, $group), (array) $value));

    if(!\is_scalar($value)) return '';
    $definition = self::getDefinition($type, $tag, $group);
    $raw        = trim((string) $value);

    if($raw === '') return '';

    // Enumerations are siblings of Name/Units in the existing definition files.
    $reserved = ['Attribute', 'Name', 'Description', 'Calculation', 'Format', 'Units', 'Group', 'IMM', 'Length'];

    if(!\in_array($raw, $reserved, true) && isset($definition[$raw]) && \is_scalar($definition[$raw]))
      return (string) $definition[$raw];
    $units = $definition['Units'] ?? '';

    if(\is_array($units) && isset($units[$raw])) return (string) $units[$raw];

    if(($definition['Calculation'] ?? '') === 'Denum'
      && preg_match('#^(-?\\d+(?:\\.\\d+)?)/(\\d+(?:\\.\\d+)?)$#', $raw, $fraction)
      && (float) $fraction[2] != 0)
      $raw = (string) round((float) $fraction[1] / (float) $fraction[2], 6);

    // Unknown enumeration codes stay intact; free-text metadata is not translated.
    if(($definition['Calculation'] ?? '') === 'Array') return $raw;

    return \is_string($units) && $units !== '' ? $raw . ' ' . $units : $raw;
  }
}
