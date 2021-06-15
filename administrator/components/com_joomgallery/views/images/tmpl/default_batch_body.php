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

?>
<div class="container-fluid">
  <div class="row-fluid">

    <h4><?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_EDIT'); ?></h4>
    <div class="control-group">
      <div class="controls">
        <button class="btn button-edit" onclick="if (document.adminForm.boxchecked.value == 0) { alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')); } else { Joomla.submitbutton('edit'); }"><span class="icon-edit"></span> Edit</button>
      </div>
    </div>

    <br />

    <h4><?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_REPLACE_TITLE'); ?></h4>
    <div class="control-group">
      <div class="control-label">
        <label id="batch_search-lbl" class="hasPopover" title="" data-content="<?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_SEARCH_DESC'); ?>" data-original-title="<?php echo JText::_('COM_JOOMGALLERY_COMMON_SEARCH'); ?>">
          <?php echo JText::_('COM_JOOMGALLERY_COMMON_SEARCH'); ?>
        </label>
      </div>
      <div class="controls">
        <input id="batch_search" type="text" name="batch_search" value="">
      </div>
    </div>

    <div class="control-group">
      <div class="control-label">
        <label id="batch_replace-lbl" class="hasPopover" title="" data-content="<?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_REPLACE_DESC'); ?>" data-original-title="<?php echo JText::_('COM_JOOMGALLERY_COMMON_REPLACE'); ?>">
          <?php echo JText::_('COM_JOOMGALLERY_COMMON_REPLACE'); ?>
        </label>
      </div>
      <div class="controls">
        <input id="batch_replace" type="text" name="batch_replace" value="">
      </div>
    </div>

    <div class="control-group">
      <fieldset>
        <div class="control-label">
          <label id="batch_fields-lbl" class="hasPopover" title="" data-content="<?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_REPLACE_FIELDS_DESC'); ?>" data-original-title="<?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_REPLACE_FIELDS'); ?>">
            <?php echo JText::_('COM_JOOMGALLERY_IMGMAN_BATCH_REPLACE_FIELDS'); ?>
          </label>
        </div>

        <div class="row-fluid">
          <div class="span3">
            <label class="checkbox"><input type="checkbox" name="batch_fields[]" value="imgtitle"> <?php echo JText::_('COM_JOOMGALLERY_COMMON_TITLE'); ?></label>
            <label class="checkbox"><input type="checkbox" name="batch_fields[]" value="imgtext"> <?php echo JText::_('COM_JOOMGALLERY_COMMON_DESCRIPTION'); ?></label>
            <label class="checkbox"><input type="checkbox" name="batch_fields[]" value="imgauthor"> <?php echo JText::_('COM_JOOMGALLERY_COMMON_AUTHOR'); ?></label>
          </div>
          <div class="span3">
            <label class="checkbox"><input type="checkbox" name="batch_fields[]" value="imgtitle"> <?php echo JText::_('COM_JOOMGALLERY_COMMON_METADESC_TIP'); ?></label>
            <label class="checkbox"><input type="checkbox" name="batch_fields[]" value="imgtext"> <?php echo JText::_('COM_JOOMGALLERY_COMMON_METAKEYS_TIP'); ?></label>
            <?php if(JPluginHelper::isEnabled('joomgallery','joomadditionalimagefields')) :?>
              <label class="checkbox"><input type="checkbox" name="batch_fields[]" value="additional"> <?php echo JText::_('COM_JOOMGALLERY_COMMON_ADDITIONALIELDS'); ?></label>
            <?php endif; ?>
          </div>
        </div>
      </fieldset>
    </div>
  </div>
</div>
