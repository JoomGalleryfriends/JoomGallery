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

JHtml::_('bootstrap.modal', 'jg-changelog-popup'); ?>
<div class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="PopupChangelogModalLabel" aria-hidden="true" id="jg-changelog-popup">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3 id="PopupChangelogModalLabel"><?php echo JText::_('COM_JOOMGALLERY_HLPIFO_CHANGELOG'); ?></h3>
  </div>
  <div id="jg-changelog-popup-container">
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JTOOLBAR_CLOSE'); ?></button>
  </div>
</div>
<script type="text/javascript">
  jQuery('#jg-changelog-popup').on('show', function ()
  {
    document.getElementById('jg-changelog-popup-container').innerHTML = '<div class="modal-body"><iframe class="iframe" frameborder="0" src="<?php echo JRoute::_('index.php?option='._JOOM_OPTION.'&controller=changelog&tmpl=component'); ?>" height="400px" width="100%"></iframe></div>';
  });
</script>