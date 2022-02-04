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

<button type="button" class="btn" onclick="document.getElementById('rotateimagetypes').value=1;jQuery('#rotateimagetypes').trigger('liszt:updated');document.getElementById('rotateimageangle').value=90;jQuery('#rotateimageangle').trigger('liszt:updated');" data-dismiss="modal">
  <?php echo JText::_('JCANCEL'); ?>
</button>
<button type="submit" class="btn btn-success" onclick="Joomla.submitbutton('replace');">
  <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
</button>
