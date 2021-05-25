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

defined('_JEXEC') or die('Direct Access to this location is not allowed.'); ?>

<div class="jg_overlib">
  <div><!-- # style="float:left;"> -->
    <img src="<?php echo $this->image->thumb_src; ?>" />
  </div>
  <div>
    <div class="jg_title">
      <?php echo $this->escape($this->image->imgtitle); ?>
    </div>
<?php if(!$this->catid): ?>
    <div class="jg_catname">
      <?php echo $this->escape($this->image->name); ?>
    </div>
<?php endif; ?>
  </div>
</div>