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
 * JoomGallery AJAX Upload Controller
 *
 * @package JoomGallery
 * @since   3.0
 */
class JoomGalleryControllerAjaxupload extends JoomGalleryController
{
  /**
   * Constructor
   *
   * @return  void
   * @since   3.0
   */
  public function __construct()
  {
    parent::__construct();

    // Set view
    JRequest::setVar('view', 'ajaxupload');
  }

  /**
   * Displays the default upload form
   *
   * @param   boolean  $cachable   If true, the view output will be cached
   * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
   * @return  void
   * @since   2.0
   */
  public function display($cachable = false, $urlparams = false)
  {
    // Access check
    if(!$this->_config->get('jg_disableunrequiredchecks') && !count(JoomHelper::getAuthorisedCategories('joom.upload')))
    {
      $this->setRedirect(JRoute::_($this->_ambit->getRedirectUrl('categories'), false), JText::_('COM_JOOMGALLERY_COMMON_MSG_NO_CATEGORIES_FOUND_ALLOWED_TO_UPLOAD'), 'notice');

      return;
    }

    parent::display($cachable, $urlparams);
  }
}