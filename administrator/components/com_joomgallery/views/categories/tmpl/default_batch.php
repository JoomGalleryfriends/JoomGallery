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

JHtml::_('formbehavior.chosen', 'select');

$options = array(
  JHtml::_('select.option', '', JText::_('COM_JOOMGALLERY_CATMAN_BATCH_KEEP_CATEGORY')),
  JHtml::_('select.option', 'c', JText::_('JLIB_HTML_BATCH_COPY')),
  JHtml::_('select.option', 'm', JText::_('JLIB_HTML_BATCH_MOVE'))
);
$published  = $this->state->get('filter.published');
?>
<div class="modal hide fade" id="collapseModal">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">x</button>
    <h3><?php echo JText::_('COM_JOOMGALLERY_CATMAN_BATCH_OPTIONS'); ?></h3>
  </div>
  <div class="modal-body">
    <div class="container-fluid">
      <p><?php echo JText::_('COM_JOOMGALLERY_CATMAN_BATCH_TIP'); ?></p>
      <div class="control-group">
        <div class="controls">
          <?php echo JHtml::_('batch.access'); ?>
        </div>
      </div>
      <!--<div class="control-group">
        <div class="controls">
          <?php echo JHtml::_('batch.language'); ?>
        </div>
      </div>-->
      <?php if($published >= 0): ?>
        <div class="control-group">
          <label id="batch-choose-action-lbl" for="batch-category-id" class="control-label">
            <?php echo JText::_('COM_JOOMGALLERY_CATMAN_BATCH_CATEGORY_LABEL'); ?>
          </label>
          <div id="batch-choose-action" class="combo controls">
            <?php echo JHtml::_('joomselect.categorylist', 0, 'batch[category_id]', null, null, '- ', 'filter', '', 'batch_category_id'); ?>
          </div>
        </div>
        <div class="control-group radio">
          <?php echo JHtml::_('select.radiolist', $options, 'batch[move_copy]', '', 'value', 'text', ''); ?>
        </div>
        <div class="control-group">
          <div class="controls">
          <label id="batchalias-lbl" for="batchalias" class="control-label">
            <?php echo JText::_('COM_JOOMGALLERY_CATMAN_BATCH_ALIAS_REG'); ?>
            <span class="icon-info-2 hasPopover" data-original-title="<?php echo JText::_('COM_JOOMGALLERY_CATMAN_BATCH_ALIAS_REG'); ?>"
                  data-content="<?php echo JText::_('COM_JOOMGALLERY_CATMAN_BATCH_ALIAS_INFO'); ?>" data-placement="top"></span>
          </label>
            <?php
              $default = 0;
              $options = array(JHTML::_('select.option', '', JText::_('COM_JOOMGALLERY_CATMAN_BATCH_ALIAS_KEEP')),JHTML::_('select.option', 'gen', JText::_('COM_JOOMGALLERY_CATMAN_BATCH_ALIAS_REG')));
              echo JHtml::_('select.genericlist',$options,'batch[alias]','class="inputbox"','value','text',$default); ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" type="button" onclick="document.id('batch_category_id').value='';document.id('batch-access').value='';/*document.id('batch-language-id').value=''*/" data-dismiss="modal">
      <?php echo JText::_('JCANCEL'); ?>
    </button>
    <button class="btn btn-primary" type="submit" onclick="Joomla.submitbutton('batch');">
      <?php echo JText::_('JGLOBAL_BATCH_PROCESS'); ?>
    </button>
  </div>
</div>
