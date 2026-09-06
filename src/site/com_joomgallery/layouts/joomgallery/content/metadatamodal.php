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

use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

$metadata = $displayData instanceof Registry ? $displayData : new Registry($displayData);
?>
<div class="modal fade" id="jg-metadata-modal" tabindex="-1" aria-labelledby="jg-metadata-modal-title" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title fs-5" id="jg-metadata-modal-title"><?php echo Text::_('COM_JOOMGALLERY_IMGMETADATA'); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php echo Text::_('JCLOSE'); ?>"></button>
      </div>
      <div class="modal-body">
        <dl class="row mb-0">
          <?php foreach($metadata->get('items', []) as $item) : ?>
            <dt class="col-sm-5"><?php echo $this->escape(Text::_($item['label'])); ?></dt>
            <dd class="col-sm-7 text-break"><?php echo $this->escape($item['value']); ?></dd>
          <?php endforeach; ?>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo Text::_('JCLOSE'); ?></button>
      </div>
    </div>
  </div>
</div>
