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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

$metadata = $displayData instanceof Registry ? $displayData : new Registry($displayData);

Factory::getApplication()->getDocument()->getWebAssetManager()->useScript('bootstrap.tab');
$groups = ['file' => [], 'exif' => [], 'iptc' => [], 'xmp' => []];
$labels = ['file' => Text::_('JLIB_FORM_VALUE_CACHE_FILE'), 'exif' => 'EXIF', 'iptc' => 'IPTC', 'xmp' => 'XMP'];

foreach($metadata->get('items', []) as $item)
{
  $path   = strtolower((string) ($item['path'] ?? ''));
  $source = explode('.', $path)[0];

  // IPTC 1:90 declares the character encoding (e.g. ESC % G for UTF-8).
  // Keep it in stored metadata, but omit this technical marker from the UI.
  if($source === 'iptc' && preg_match('/(?:^|\.)(?:1[#:]0*90|codedcharacterset)(?:\.\d+)*$/', $path))
  {
    continue;
  }

  // General JPEG comments belong to File; EXIF UserComment stays in EXIF.
  $group = \in_array($source, ['exif', 'iptc', 'xmp'], true) ? $source : 'file';

  if(preg_match('/(?:^|\.)comment(?:\.|$)/', $path))
  {
    $group         = 'file';
    $item['label'] = 'COM_JOOMGALLERY_COMMENT';
  }
  $groups[$group][] = $item;
}
$groups      = array_filter($groups);
$activeGroup = array_key_first($groups);
?>
<div class="modal fade" id="jg-metadata-modal" tabindex="-1" aria-labelledby="jg-metadata-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="jg-metadata-modal-title"><?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>
      <div class="modal-body">
        <ul class="nav nav-tabs" role="tablist" aria-label="<?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?>">
          <?php foreach($groups as $group => $items) : ?>
            <li class="nav-item" role="presentation">
              <button class="nav-link<?php echo $group === $activeGroup ? ' active' : ''; ?>" id="jg-metadata-tab-<?php echo $group; ?>" data-bs-toggle="tab" data-bs-target="#jg-metadata-panel-<?php echo $group; ?>" type="button" role="tab" aria-controls="jg-metadata-panel-<?php echo $group; ?>" aria-selected="<?php echo $group === $activeGroup ? 'true' : 'false'; ?>"><?php echo $this->escape($labels[$group]); ?></button>
            </li>
          <?php endforeach; ?>
        </ul>
        <div class="tab-content pt-3">
          <?php foreach($groups as $group => $items) : ?>
            <div class="tab-pane fade<?php echo $group === $activeGroup ? ' show active' : ''; ?>" id="jg-metadata-panel-<?php echo $group; ?>" role="tabpanel" aria-labelledby="jg-metadata-tab-<?php echo $group; ?>" tabindex="0">
              <table class="table table-sm table-striped mb-0">
                <tbody>
                  <?php foreach($items as $item) : ?>
                    <tr>
                      <th scope="row" class="w-40 text-break" style="min-width:200px"><?php echo $this->escape(Text::_($item['label'])); ?></th>
                      <td class="text-break"><?php echo $this->escape($item['value']); ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCLOSE'); ?></button>
      </div>
    </div>
  </div>
</div>
