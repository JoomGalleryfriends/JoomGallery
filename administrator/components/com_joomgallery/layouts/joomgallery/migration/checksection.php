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

  <tr>
    <td colspan="3">
      <h4><?php echo $displayData->title; ?></h4>
    </td>
  </tr>
<?php foreach($displayData->checks as $check): ?>
  <tr>
    <td width="80%"><?php echo $check['title']; ?></td>
    <td width="10%" class="center">
      <?php echo $check['state'] ? JHTML::_('jgrid.published', true, 0, '', false) : '&nbsp;'; ?>
    </td>
    <td class="center">
      <?php echo !$check['state'] ? JHTML::_('jgrid.published', false, 0, '', false) : '&nbsp;'; ?>
    </td>
  </tr>
<?php endforeach;