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

/**
 * HTML View class for the FTP upload view
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryViewFtpupload extends JoomGalleryView
{
  /**
   * HTML view display method
   *
   * @param   string  $tpl  The name of the template file to parse
   * @return  void
   * @since   1.5.5
   */
  public function display($tpl = null)
  {
    JToolBarHelper::title(JText::_('COM_JOOMGALLERY_UPLOAD_FTP_UPLOAD_MANAGER'), 'upload');

    JForm::addFormPath(JPATH_COMPONENT.'/models/forms');
    $this->form = JForm::getInstance(_JOOM_OPTION.'.ftpupload', 'ftpupload');

    $subdirectory = $this->_mainframe->getUserStateFromRequest('joom.upload.ftp.subdirectory', 'subdirectory', '/', '', 'string');

    $this->form->setFieldAttribute('directory', 'default', JPath::clean($this->_ambit->get('ftp_path').$subdirectory));
    $this->form->setFieldAttribute('subdirectory', 'directory', $this->_ambit->get('ftp_path'));
    $this->form->setFieldAttribute('ftpfiles', 'directory', $this->_ambit->get('ftp_path').$subdirectory);
    $this->form->setFieldAttribute('subdirectory', 'default', $subdirectory);
    $this->form->setFieldAttribute('access', 'default', (int) JFactory::getConfig()->get('access'));

    if($this->_config->get('jg_useorigfilename'))
    {
      $this->form->setFieldAttribute('imgtitle', 'required', 'false');
    }

    $this->sidebar = JHtmlSidebar::render();

    parent::display($tpl);
  }
}