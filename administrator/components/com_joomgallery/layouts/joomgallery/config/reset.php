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

<div class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="PopupResetConfigModalLabel" aria-hidden="true" id="jg-reset-popup">
  <form id="FormResetConfig" method="post" action="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&amp;controller=config&amp;task=resetconfig'); ?>">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      <h3 id="PopupResetConfigModalLabel"><?php echo JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_HEADING'); ?></h3>
    </div>
    <div class="modal-body">
      <div class="alert alert-warning">
        <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_INFO'); ?></p>
        <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_WARNING'); ?></p>
        </div>
      <div>
        <p><?php echo JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG_SELECT'); ?></p>
        <p><?php echo JHtml::_('joomconfig.reset'); ?></p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JTOOLBAR_CANCEL'); ?></button>
      <button class="btn btn-warning" type="submit"><?php echo JText::_('COM_JOOMGALLERY_CONFIG_RESETCONFIG'); ?></button>
    </div>
  </form>
</div>