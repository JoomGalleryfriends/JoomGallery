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

<form action="index.php" name="adminFormDatabase" method="post">
  <div class="well">
    <div class="row-fluid">
      <div class="span2 center">
        <button type="submit" class="btn" onclick="document.adminFormDatabase.task.value = 'optimize';"><?php echo JText::_('COM_JOOMGALLERY_MAIMAN_DB_OPTIMIZE'); ?></button>
      </div>
      <div class="span10">
          <?php echo JText::_('COM_JOOMGALLERY_MAIMAN_DB_OPTIMIZE_LONG'); ?> 
      </div>
      <input type="hidden" name="option" value="<?php echo _JOOM_OPTION; ?>" />
      <input type="hidden" name="controller" value="maintenance" />
      <input type="hidden" name="task" value="" />
    </div>
  </div>
</form>