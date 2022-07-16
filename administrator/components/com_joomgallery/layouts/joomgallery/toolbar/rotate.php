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

JHtml::_('behavior.core');

$title = $displayData['title'];
JText::script('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST');
$message = "alert(Joomla.JText._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST'));";
?>
<button type="button" data-toggle="modal" onclick="if (document.adminForm.boxchecked.value==0){<?php echo $message; ?>}else{jQuery( '#jg-rotate-popup' ).modal('show'); return true;}" class="btn btn-small">
  <span class="icon-loop" aria-hidden="true"></span>
  <?php echo $title; ?>
</button>
