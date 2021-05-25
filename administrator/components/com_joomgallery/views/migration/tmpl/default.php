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

JHtml::_('bootstrap.tooltip');
JHtml::_('behavior.formvalidation');
?>
<?php if(!empty($this->sidebar)): ?>
<div id="j-sidebar-container" class="span2">
    <?php echo $this->sidebar; ?>
</div>
<div id="j-main-container" class="span10">
<?php else : ?>
<div id="j-main-container">
<?php endif; ?>
  <div class="alert alert-info">
    <?php echo JText::_(count($this->files) ? 'COM_JOOMGALLERY_MIGMAN_NOTE' : 'COM_JOOMGALLERY_MIGMAN_NOTE_NO_SCRIPTS'); ?>
  </div>
  <?php echo $this->output; ?>
  <table class="table table-bordered">
<?php
    $show_jmtablerow = true;
    foreach($this->files as $file):
      require_once $file;
      $class = 'JoomMigrate'.substr(basename($file), 7, -4);
      if(class_exists($class))
      {
        $migration = new $class();
        echo $migration->getForm();
      }
    endforeach;
?>
  </table>
  <?php JHtml::_('joomgallery.credits'); ?>
</div>
