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

echo $this->loadTemplate('header'); ?>
  <div class="well well-small jg-header">
    <?php echo $this->output('DOWNLOAD'); ?>
  </div>
  <div class="jg_createzip">
    <a href="<?php echo $this->zipname; ?>"><?php echo JText::_('COM_JOOMGALLERY_DOWNLOADZIP_DOWNLOAD_READY'); ?></a>
    <br />
    <a href="<?php echo $this->zipname; ?>">
      <img src="<?php echo $this->_ambit->getIcon('disk.png'); ?>" border="0" align="middle" alt="<?php echo JText::_('COM_JOOMGALLERY_DOWNLOADZIP_DOWNLOAD'); ?>" />
    </a>
    <br />
    <?php echo JText::sprintf('COM_JOOMGALLERY_DOWNLOADZIP_FILESIZE', $this->zipsize); ?> 
    <br /><br />
    <a href="<?php echo JRoute::_('index.php?task=removeall'); ?>">
      <?php echo $this->output('CREATEZIP_REMOVE_ALL'); ?> 
    </a>
  </div>
  <div class="jg-footer">
    &nbsp;
  </div>
<?php echo $this->loadTemplate('footer');