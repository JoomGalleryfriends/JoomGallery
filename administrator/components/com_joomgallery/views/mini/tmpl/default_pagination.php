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

  <div class="jg_minicount">
<?php if($this->total < 1): ?>
    <?php echo JText::_('COM_JOOMGALLERY_MINI_NO_IMAGES'); ?>
<?php endif;
      if($this->total == 1): ?>
    <?php echo JText::_('COM_JOOMGALLERY_MINI_ONE_IMAGE_FOUND'); ?>
<?php endif;
      if($this->total > 1): ?>
    <?php echo JText::sprintf('COM_JOOMGALLERY_MINI_IMAGES_FOUND', $this->total); ?>
<?php endif; ?>
  </div>
<?php if($this->totalpages > 1): ?>
  <div class="pagination">
<?php   if($this->_mainframe->isSite()):
          echo $this->pagination->getPagesLinks();
        else:
          $pagination = $this->pagination->getData(); ?>
    <ul>
<?php     if($pagination->start->base !== null): ?>
      <li><a href="<?php echo $pagination->start->link; ?>"><?php echo $pagination->start->text; ?></a></li>
<?php     else: ?>
      <li class="disabled"><a href="javascript:return false;"><?php echo $pagination->start->text; ?></a></li>
<?php     endif;
          if($pagination->previous->base !== null): ?>
      <li><a href="<?php echo $pagination->previous->link; ?>"><?php echo $pagination->previous->text; ?></a></li>
<?php     else: ?>
      <li class="disabled"><a href="javascript:return false;"><?php echo $pagination->previous->text; ?></a></li>
<?php     endif;
          foreach($pagination->pages as $page):
            if($page->base !== null): ?>
      <li><a href="<?php echo $page->link; ?>"><?php echo $page->text; ?></a></li>
<?php       else: ?>
      <li class="disabled"><a href="javascript:return false;"><?php echo $page->text; ?></a></li>
<?php       endif;
          endforeach;
          if($pagination->next->base !== null): ?>
      <li><a href="<?php echo $pagination->next->link; ?>"><?php echo $pagination->next->text; ?></a></li>
<?php     else: ?>
      <li class="disabled"><a href="javascript:return false;"><?php echo $pagination->next->text; ?></a></li>
<?php     endif;
          if($pagination->end->base !== null): ?>
      <li><a href="<?php echo $pagination->end->link; ?>"><?php echo $pagination->end->text; ?></a></li>
<?php     else: ?>
      <li class="disabled"><a href="javascript:return false;"><?php echo $pagination->end->text; ?></a></li>
<?php     endif; ?>
    </ul>
<?php   endif; ?>
  </div>
<?php endif;