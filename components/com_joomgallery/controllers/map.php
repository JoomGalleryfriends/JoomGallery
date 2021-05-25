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

jimport('joomla.application.component.controller');

/**
 * JoomGallery Map Controller
 *
 * @package JoomGallery
 * @since   3.5.0
 */
class JoomGalleryControllerMap extends JControllerLegacy
{
  /**
   * Enables the display of maps in the detail view
   *
   * @return  void
   * @since   3.5.0
   */
  public function show()
  {
    JFactory::getApplication()->setUserState('joom.geomap.show', 1);
    $id = JRequest::getInt('id');
    $this->setRedirect(JRoute::_('index.php?view=detail&id='.$id, false));
  }
}