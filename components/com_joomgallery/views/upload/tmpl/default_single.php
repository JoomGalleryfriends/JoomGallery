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

<form action="<?php echo JRoute::_('index.php?type=single'); ?>" method="post" name="adminForm" id="SingleUploadForm" enctype="multipart/form-data" class="form-validate form-horizontal" onsubmit="if(this.task.value == 'upload.upload' && !document.formvalidator.isValid(document.id('SingleUploadForm'))){alert('<?php echo JText::_('JGLOBAL_VALIDATION_FORM_FAILED', true); ?>');return false;} return joomOnSubmit();">
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('catid'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('catid'); ?>
    </div>
  </div>
      <?php if(!$this->_config->get('jg_useruseorigfilename')): ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('imgtitle'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('imgtitle'); ?>
    </div>
  </div>
      <?php endif;
            if(!$this->_config->get('jg_useruseorigfilename') && $this->_config->get('jg_useruploadnumber')): ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('filecounter'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('filecounter'); ?>
    </div>
  </div>
      <?php endif; ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('single-imgtext'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('single-imgtext'); ?>
    </div>
  </div>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('imgauthor'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('imgauthor'); ?>
    </div>
  </div>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('owner'); ?>
    </div>
    <div class="controls">
      <div class="jg-uploader"><?php echo JHtml::_('joomgallery.displayname', $this->_user->get('id'), 'upload'); ?></div>
    </div>
  </div>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('published'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('published'); ?>
    </div>
  </div>
    <?php /*
      <?php echo $this->single_form->getLabel('access'); ?>
      <?php echo $this->single_form->getInput('access'); ?>
          */ ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('arrscreenshot'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('arrscreenshot'); ?>
    </div>
  </div>
      <?php if($this->_config->get('jg_delete_original_user') == 2): ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('original_delete'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('original_delete'); ?>
    </div>
  </div>
      <?php endif;
            if($this->_config->get('jg_special_gif_upload') == 1): ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('create_special_gif'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('create_special_gif'); ?>
    </div>
  </div>
      <?php endif;
            if($this->_config->get('jg_redirect_after_upload')): ?>
  <div class="control-group">
    <div class="control-label">
      <?php echo $this->single_form->getLabel('debug'); ?>
    </div>
    <div class="controls">
      <?php echo $this->single_form->getInput('debug'); ?>
    </div>
  </div>
      <?php endif; ?>
  <div class="control-group">
    <div class="control-label">
      <label for="button"></label>
    </div>
    <div class="controls">
      <button type="submit" class="btn btn-primary"><i class="icon-upload"></i> <?php echo JText::_('COM_JOOMGALLERY_UPLOAD_UPLOAD'); ?></button>
      <button type="button" class="btn" onclick="javascript:location.href='<?php echo JRoute::_('index.php?view=userpanel', false); ?>';return false;"><i class="icon-cancel"></i> <?php echo JText::_('COM_JOOMGALLERY_COMMON_CANCEL'); ?></button>
    </div>
    <input type="hidden" name="task" value="upload.upload" />
  </div>
</form>