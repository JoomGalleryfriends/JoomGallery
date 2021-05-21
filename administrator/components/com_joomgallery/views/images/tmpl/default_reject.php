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

JHtml::_('bootstrap.tooltip', '.modalTooltip', array('container' => '.modal-body'));
?>
<div class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="PopupRejectModalLabel" aria-hidden="true" id="jg-reject-popup">
  <script type="text/javascript">
    jQuery('#jg-reject-popup').on('shown', function ()
    {
      jQuery('#jg-message').focus();
    });
  </script>
  <form id="formReject" method="post" action="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&controller=images&task=reject'); ?>">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h3 id="PopupRejectModalLabel"><?php echo JText::_('COM_JOOMGALLERY_IMGMAN_REJECT_IMAGE_HEADING'); ?></h3>
    </div>
    <div class="modal-body">
      <div class="row-fluid">
        <div class="span4">
          <img src="../media/system/images/blank.png" class="img-polaroid" id="jg-reject-image" alt="Thumbnail" />
        </div>
        <div class="span8">
          <p id="jg-reject-no-owner"><?php echo JText::_('COM_JOOMGALLERY_IMGMAN_REJECT_IMAGE_NOOWNER'); ?></p>
          <div id="jg-reject-owner">
            <p><?php echo JText::sprintf('COM_JOOMGALLERY_IMGMAN_REJECT_IMAGE_OWNER', '<span id="jg-reject-owner-name"></span>'); ?></p>
            <div class="control-group">
              <div class="control-label">
                <label for="jg-message" class="modalTooltip" title="<?php echo JText::_('COM_JOOMGALLERY_IMGMAN_REJECT_IMAGE_MESSAGE_TIP'); ?>">
                  <?php echo JText::_('COM_JOOMGALLERY_IMGMAN_REJECT_IMAGE_MESSAGE_LABEL'); ?>
                </label>
              </div>
              <div class="controls">
                <textarea id="jg-message" name="message" cols="30" rows="6" class="span12"></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JTOOLBAR_CANCEL'); ?></button>
      <button class="btn btn-primary" type="submit"><?php echo JText::_('COM_JOOMGALLERY_IMGMAN_REJECT_IMAGE_BUTTON'); ?></button>
      <input type="hidden" id="jg-reject-cid" name="cid" value="" />
    </div>
  </form>
</div>