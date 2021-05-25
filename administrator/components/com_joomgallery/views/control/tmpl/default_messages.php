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

      if($this->state->get('message')): ?>
  <div class="row-fluid">
    <div class="alert alert-info">
      <?php echo $this->state->get('message'); ?>
    </div>
  </div>
<?php endif;if($this->state->get('extension_message')): ?>
  <div class="row-fluid">
    <div class="span12 well">
      <?php echo $this->state->get('extension_message'); ?>
    </div>
  </div>
<?php endif;
