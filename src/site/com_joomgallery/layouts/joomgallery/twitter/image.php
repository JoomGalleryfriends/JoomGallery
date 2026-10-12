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
$tags = [
  'twitter:card' => !empty($data['image']) ? 'summary_large_image' : 'summary',
  'twitter:title' => $data['title'] ?? '',
  'twitter:description' => $data['description'] ?? '',
  'twitter:url' => $data['url'] ?? '',
  'twitter:image' => $data['image'] ?? '',
  'twitter:image:alt' => !empty($data['image']) ? ($data['alt'] ?? '') : '',
];

foreach($tags as $name => $content)
{
  if(trim((string) $content) !== '')
  {
    echo '<meta name="' . $this->escape($name) . '" content="' . $this->escape((string) $content) . '">' . "\n";
  }
}
