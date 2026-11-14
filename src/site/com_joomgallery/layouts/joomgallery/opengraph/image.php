<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

\defined('_JEXEC') || die;

// The template supplies plain text and absolute URLs; escape at the HTML boundary.
$data = (array) $displayData;
$info = (object) ($data['imageInfo'] ?? []);
$tags = [
  'og:type' => 'website',
  'og:title' => $data['title'] ?? '',
  'og:description' => $data['description'] ?? '',
  'og:url' => $data['url'] ?? '',
  'og:site_name' => $data['siteName'] ?? '',
  'og:locale' => $data['locale'] ?? '',
];

if(!empty($data['image']))
{
  $tags += [
    'og:image' => $data['image'],
    'og:image:width' => (int) ($info->width ?? 0) > 0 ? (int) $info->width : '',
    'og:image:height' => (int) ($info->height ?? 0) > 0 ? (int) $info->height : '',
    'og:image:type' => $info->mime_type ?? '',
    'og:image:alt' => $data['alt'] ?? '',
  ];
}

foreach($tags as $property => $content)
{
  if(trim((string) $content) !== '')
  {
    echo '<meta property="' . $this->escape($property) . '" content="' . $this->escape((string) $content) . '">' . "\n";
  }
}
