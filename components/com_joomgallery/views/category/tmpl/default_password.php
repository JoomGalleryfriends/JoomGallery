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

<form action="<?php echo JRoute::_('index.php?task=category.unlock&catid='.$this->cat->cid);?>" method="post" class="form-inline" onsubmit="joomUnlock();return false;" autocomplete="off">
  <div class="well" style="text-align:center;">
    <h3><?php echo JText::_('COM_JOOMGALLERY_CATEGORY_PASSWORD_PROTECTED'); ?></h3>
    <label for="jg_password"><?php echo JText::_('COM_JOOMGALLERY_CATEGORY_PASSWORD'); ?></label>
    <input type="password" name="password" id="jg_password" />
    <button type="submit" class="btn btn-primary" id="jg_unlock_button" data-loading-text="<?php echo JText::_('COM_JOOMGALLERY_CATEGORY_BUTTON_UNLOCKING'); ?>"><?php echo JText::_('COM_JOOMGALLERY_CATEGORY_BUTTON_UNLOCK'); ?></button>
    <div id="jg_password_response"> </div>
    <script type="text/javascript">
    function joomUnlock()
    {
      jQuery('#jg_unlock_button').button('loading');
      jQuery('#jg_password_response').text('');
      jQuery.ajax('<?php echo JRoute::_('index.php?task=categories.unlock&catid='.$this->cat->cid, false); ?>&format=json', {
        data: 'password=' + jQuery('#jg_password').val(),
        type: 'post',
        dataType: 'json',
        success: function(r)
        {
          if(r.success)
          {
            document.location.reload();
          }
          else
          {
            jQuery('#jg_password_response').html('<span class="label label-important">' + r.message + '</span>');
            jQuery('#jg_password').val('').focus();
            jQuery('#jg_unlock_button').button('reset');
          }
        }
      })};
    </script>
  </div>
</form>