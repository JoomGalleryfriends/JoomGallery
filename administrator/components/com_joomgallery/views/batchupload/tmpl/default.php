<?php defined('_JEXEC') or die('Direct Access to this location is not allowed.');
JHtml::_('behavior.formvalidation');
JHtml::_('bootstrap.tooltip'); ?>
<form action="index.php" method="post" name="adminForm" id="upload-form" enctype="multipart/form-data" class="form-validate form-horizontal" onsubmit="if(this.task.value == 'upload' && !document.formvalidator.isValid(document.id('upload-form'))){alert('<?php echo JText::_('JGLOBAL_VALIDATION_FORM_FAILED', true); ?>');return false;}">
<?php if(!empty($this->sidebar)): ?>
  <div id="j-sidebar-container" class="span2">
    <?php echo $this->sidebar; ?>
  </div>
  <div id="j-main-container" class="span10">
<?php else : ?>
  <div id="j-main-container">
<?php endif;?>
    <div class="row-fluid">
      <div class="alert alert-block alert-info">
        <h4><?php echo JText::_('COM_JOOMGALLERY_COMMON_IMPORTANT_NOTICE'); ?></h4>
        <?php echo JText::_('COM_JOOMGALLERY_UPLOAD_BATCH_UPLOAD_NOTE'); ?>
      </div>
    </div>
    <div class="row-fluid">
      <div class="span6 well">
        <div class="legend"><?php echo JText::_('COM_JOOMGALLERY_COMMON_IMAGE_SELECTION'); ?></div>
        <div class="control-group">
          <?php echo $this->form->getLabel('zippack'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('zippack'); ?>
          </div>
        </div>
      </div>
      <div class="span6 well">
        <div class="legend"><?php echo JText::_('COM_JOOMGALLERY_COMMON_OPTIONS'); ?></div>
        <div class="control-group">
          <?php echo $this->form->getLabel('catid'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('catid'); ?>
          </div>
        </div>
        <?php if(!$this->_config->get('jg_useorigfilename')): ?>
        <div class="control-group">
          <?php echo $this->form->getLabel('imgtitle'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('imgtitle'); ?>
          </div>
        </div>
        <?php endif;
              if(!$this->_config->get('jg_useorigfilename') && $this->_config->get('jg_filenamenumber')): ?>
        <div class="control-group">
          <?php echo $this->form->getLabel('filecounter'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('filecounter'); ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="control-group form-vertical">
          <?php echo $this->form->getLabel('imgtext'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('imgtext'); ?>
          </div>
        </div>
        <div class="control-group">
          <?php echo $this->form->getLabel('imgauthor'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('imgauthor'); ?>
          </div>
        </div>
        <div class="control-group">
          <?php echo $this->form->getLabel('published'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('published'); ?>
          </div>
        </div>
        <div class="control-group">
          <?php echo $this->form->getLabel('access'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('access'); ?>
          </div>
        </div>
        <?php if(!$this->_config->get('jg_unsafe_zip_upload') == 0): ?>
        <div class="control-group">
          <?php echo $this->form->getLabel('unsafe_zip_upload'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('unsafe_zip_upload'); ?>
          </div>
        </div>
        <?php endif; ?>
        <?php if($this->_config->get('jg_delete_original') == 2): ?>
        <div class="control-group">
          <?php echo $this->form->getLabel('original_delete'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('original_delete'); ?>
          </div>
        </div>
        <?php endif; ?>
        <div class="control-group">
          <?php echo $this->form->getLabel('create_special_gif'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('create_special_gif'); ?>
          </div>
        </div>
        <div class="control-group">
          <?php echo $this->form->getLabel('debug'); ?>
          <div class="controls">
            <?php echo $this->form->getInput('debug'); ?>
          </div>
        </div>
        <div class="control-group">
          <div class="controls">
            <button id="button" class="btn btn-large btn-primary" type="submit"><i class="icon-upload icon-white"></i> <?php echo JText::_('COM_JOOMGALLERY_UPLOAD_UPLOAD'); ?></button>
          </div>
        </div>
      </div>
    </div>
    <input type="hidden" name="option" value="<?php echo _JOOM_OPTION; ?>" />
    <input type="hidden" name="controller" value="batchupload" />
    <input type="hidden" name="task" value="upload" />
    <?php JHtml::_('joomgallery.credits'); ?>
  </div>
</form>
<?php if(!$this->_config->get('jg_unsafe_zip_upload') == 0): ?>
<div id="safezipModal" class="modal hide fade" role="dialog">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3><?php echo JText::_('COM_JOOMGALLERY_CONFIG_GS_BU_ALLOW_UNSAFE_ZIP_UPLOAD'); ?></h3>
  </div>
  <div class="modal-body">
    <div class="alert alert-warning">
      <p><?php echo JText::_('COM_JOOMGALLERY_UPLOAD_BATCH_ALLOW_UNSAFE_ZIP_WARNING_MODAL'); ?></p>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" onclick="unsafezipDeactivate()"><?php echo JText::_('JTOOLBAR_CANCEL'); ?></button>
    <button class="btn btn-primary" onclick="unsafezipActivate()"><?php echo JText::_('COM_JOOMGALLERY_BTN_ACTIVATE'); ?></button>
  </div>
</div>
<script>
  jQuery(document).ready(function()
  {
    jQuery('#unsafe_zip_upload').change(function()
    {
      if(this.checked == true)
      {
        jQuery('#unsafe_zip_upload').prop("checked", false);
        jQuery('#safezipModal').modal('show');
      }
    });
  });

  function unsafezipActivate() {
    jQuery('#unsafe_zip_upload').prop("checked", true);
    jQuery('#safezipModal').modal('hide');
  }

  function unsafezipDeactivate() {
    jQuery('#unsafe_zip_upload').prop("checked", false);
    jQuery('#safezipModal').modal('hide');
  }
</script>
<?php endif; ?>