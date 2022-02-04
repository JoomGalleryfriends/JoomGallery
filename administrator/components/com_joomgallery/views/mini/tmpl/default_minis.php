<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2021  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

      if(!count($this->images)): ?>
    <div class="jg_bu_no_images">
      <?php echo JText::_('COM_JOOMGALLERY_MINI_NO_IMAGES'); ?>
    </div>
<?php endif;
      foreach($this->images as $row): ?>
    <div class="jg_bu_mini">
<?php if($row->thumb_src): ?>
      <a href="javascript:if(typeof window.parent.<?php echo $this->prefix; ?>_selectimage == 'function'){window.parent.<?php echo $this->prefix; ?>_selectimage(<?php echo $row->id; ?>, '<?php echo str_replace("'", "\'", $this->escape(stripslashes($row->imgtitle))); ?>', '<?php echo $this->object; ?>', '<?php echo $row->imgthumbname; ?>', document.id('imagesForm') != null ? radioGetCheckedValue(document.id('imagesForm').type) : 'thumb');}else{insertJoomPluWithId('<?php echo $row->id; ?>', '<?php echo $this->e_name; ?>');}" title="<?php echo $row->overlib; ?>" class="hasMiniTip">
        <img src="<?php echo $row->thumb_src; ?>" border="0" height="40" width="40" alt="Thumbnail" /></a>
<?php endif;
      if(!$row->thumb_src): ?>
      <div class="jg_bu_no_mini" title="<?php echo JText::_('COM_JOOMGALLERY_MINI_NO_THUMB'); ?>::<?php echo $this->escape(JText::sprintf('COM_JOOMGALLERY_MINI_NO_THUMB_TIP', $row->id, $row->imgtitle)); ?>" class="hasMiniTip">
        <?php echo JText::_('COM_JOOMGALLERY_MINI_NO_THUMB'); ?></div>
<?php endif; ?>
    </div>
<?php endforeach;