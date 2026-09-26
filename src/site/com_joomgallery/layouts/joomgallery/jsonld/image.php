<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;

// Return JSON only; the calling template adds the script element to the document head.
$data = (array) $displayData;
$item = (object) ($data['item'] ?? []);
$info = (object) ($data['imageInfo'] ?? []);

// Normalize input metadata and remove HTML markup from descriptive text.
$metadata = new Registry($data['metadata'] ?? $item->imgmetadata ?? []);
$plain    = static function ($value): string {
  return \is_scalar($value) ? trim(html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
};

// Resolve relative URLs against the site root and accept only HTTP(S) URLs.
$url = static function ($value): string {
  $value = \is_string($value) ? trim(html_entity_decode($value, ENT_QUOTES, 'UTF-8')) : '';

  if($value === '') return '';

  if(str_starts_with($value, '//')) $value = Uri::getInstance(Uri::root())->getScheme() . ':' . $value;
  elseif(!preg_match('#^[a-z][a-z0-9+.-]*:#i', $value))
  {
    $value = str_starts_with($value, '/')
      ? Uri::getInstance(Uri::root())->toString(['scheme', 'host', 'port']) . $value
      : Uri::root() . ltrim($value, '/');
  }

  return preg_match('#^https?://[^/]+#i', $value) ? $value : '';
};

// Parse supported database/EXIF/IPTC dates; omit invalid dates.
$date = static function ($value, bool $utc = false): string {
  if(!\is_string($value) || trim($value) === '') return '';

  foreach(['!Y-m-d H:i:s', '!Y:m:d H:i:s', '!Y-m-d\\TH:i:sP', '!Y-m-d', '!Ymd'] as $format)
  {
    $parsed = \DateTimeImmutable::createFromFormat($format, trim($value), new \DateTimeZone('UTC'));
    $errors = \DateTimeImmutable::getLastErrors();

    if($parsed && ($errors === false || (!$errors['warning_count'] && !$errors['error_count'])))
    {
      // EXIF often has no offset: do not invent a timezone for the capture time.
      return $parsed->format($utc || $format === '!Y-m-d\\TH:i:sP' ? DATE_ATOM : 'Y-m-d');
    }
  }

  return '';
};

// A valid page URL identifies and links both nodes in the graph.
$pageUrl = $url($data['pageUrl'] ?? '');

if($pageUrl === '') return;

// Build the image node using item fields, with metadata fallbacks where available.
$title       = $plain($item->title ?? '');
$description = $plain($item->description ?? '');
$image       = [
  '@type' => 'ImageObject', '@id' => $pageUrl . '#image',
  'mainEntityOfPage' => $pageUrl,
  'contentUrl' => $url($data['imageUrl'] ?? ''),
  'thumbnailUrl' => $url($data['thumbnailUrl'] ?? ''),
  'name' => $title,
  'caption' => $plain($item->caption ?? $metadata->get('iptc.2#120', '')),
  'description' => $description,
  'encodingFormat' => $plain($info->mime_type ?? ''),
  'uploadDate' => $date($item->created_time ?? '', true),
  'dateCreated' => $date($metadata->get('exif.EXIF.DateTimeOriginal', ''))
    ?: $date($item->date ?? '', true),
];

// Dimensions describe the displayed image file, explicitly measured in pixels.
foreach(['width', 'height'] as $dimension)
{
  if((int) ($info->{$dimension} ?? 0) > 0)
    $image[$dimension] = ['@type' => 'QuantitativeValue', 'value' => (int) $info->{$dimension}, 'unitText' => 'px'];
}

// Combine tag titles and SEO keywords, removing duplicates before output.
$keywords = [];

foreach((array) ($item->tags ?? []) as $tag)
{
  $tag   = (object) $tag;
  $value = $plain($tag->title ?? '');

  if($value !== '') $keywords[] = $value;
}

foreach(explode(',', $plain($item->metakey ?? '')) as $keyword)
{
  if(trim($keyword) !== '') $keywords[] = trim($keyword);
}

if($keywords) $image['keywords'] = implode(', ', array_unique($keywords));

// Prefer the explicit author, then embedded creator metadata, then the uploader.
$author = $plain($item->author ?? '') ?: $plain($metadata->get('exif.IFD0.Artist', '')) ?: $plain($metadata->get('iptc.2#080', '')) ?: $plain($item->created_by_name ?? '');

if($author !== '')
{
  $image['author'] = ['@type' => 'Person', 'name' => $author];
  $sameAs          = $url($item->author_url ?? '');

  if($sameAs !== '') $image['author']['sameAs'] = $sameAs;
}

// Include an explicit rights holder; the notice falls back to the image author.
$holder = $plain($item->copyrightHolder ?? $item->copyright_holder ?? '');

if($holder !== '') $image['copyrightHolder'] = ['@type' => 'Person', 'name' => $holder];

$image['copyrightNotice'] = $plain($metadata->get('exif.IFD0.Copyright', '')) ?: $plain($metadata->get('iptc.2#116', '')) ?: $plain($item->author ?? '') ?: $plain($item->created_by ?? '');

// Include licensing links only when an existing value resolves to an HTTP(S) URL.
foreach(['license', 'acquireLicensePage'] as $key) $image[$key] = $url($item->{$key} ?? $metadata->get($key, ''));

// Accept decimal coordinates or EXIF degrees/minutes/seconds rational arrays.
$number = static function ($value): ?float {
  if(is_numeric($value)) return (float) $value;

  if(\is_string($value) && preg_match('#^(-?\d+(?:\.\d+)?)/(\d+(?:\.\d+)?)$#', trim($value), $parts) && (float) $parts[2] != 0)
    return (float) $parts[1] / (float) $parts[2];

  return null;
};

// Convert coordinates to decimal degrees, apply hemisphere signs and check bounds.
$coordinate = static function ($value, $ref, $limit) use ($number): ?float {
  if(\is_object($value)) $value = (array) $value;

  if(\is_string($value) && preg_match('/[, ]/', trim($value))) $value = preg_split('/[,\s]+/', trim($value));

  if(\is_array($value))
  {
    $parts = array_map($number, array_values($value));

    if(\count($parts) !== 3 || \in_array(null, $parts, true)) return null;
    $value = $parts[0] + $parts[1] / 60 + $parts[2] / 3600;
  }
  else $value = $number($value);

  if($value === null) return null;

  if(\in_array(strtoupper((string) $ref), ['S', 'W'], true)) $value = -abs($value);

  return abs($value) <= $limit ? $value : null;
};

// Emit a location only when both coordinates are valid; altitude is optional.
$latitude  = $coordinate($metadata->get('exif.GPS.GPSLatitude'), $metadata->get('exif.GPS.GPSLatitudeRef'), 90);
$longitude = $coordinate($metadata->get('exif.GPS.GPSLongitude'), $metadata->get('exif.GPS.GPSLongitudeRef'), 180);

if($latitude !== null && $longitude !== null)
{
  $geo      = ['@type' => 'GeoCoordinates', 'latitude' => $latitude, 'longitude' => $longitude];
  $altitude = $number($metadata->get('exif.GPS.GPSAltitude'));

  if($altitude !== null) $geo['elevation'] = (int) $metadata->get('exif.GPS.GPSAltitudeRef', 0) === 1 ? -abs($altitude) : $altitude;
  $image['contentLocation']                = ['@type' => 'Place', 'geo' => $geo];
}

// Reuse prepared, translated metadata values and limit EXIF to relevant fields.
$relevantExif = ['Make', 'Model', 'Orientation', 'DateTime', 'DateTimeOriginal', 'DateTimeDigitized', 'Compression'];

foreach($data['metadataItems'] ?? [] as $entry)
{
  if(!str_starts_with(strtolower($entry['path'] ?? ''), 'exif.')) continue;

  if(!\in_array($entry['key'] ?? '', $relevantExif, true)) continue;
  $value = $plain($entry['value'] ?? '');
  $name  = $plain(Text::_($entry['label'] ?? $entry['key'] ?? ''));

  if($name !== '' && $value !== '') $image['exifData'][] = ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value];
}

// Omit absent properties while preserving zero values and link the page to its image.
$nonempty = static fn($value) => $value !== '' && $value !== null && $value !== [];
$page     = array_filter(
    [
      '@type' => 'WebPage', '@id' => $pageUrl, 'url' => $pageUrl,
      'name' => $plain($data['pageTitle'] ?? '') ?: $title,
      'description' => $plain($item->metadesc ?? '') ?: $description,
      'mainEntity' => ['@id' => $pageUrl . '#image'],
    ],
    $nonempty
);

// Encode safely for an inline script, including text containing HTML delimiters.
echo json_encode(
    [
      '@context' => 'https://schema.org',
      '@graph' => [$page, array_filter($image, $nonempty)],
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
);
