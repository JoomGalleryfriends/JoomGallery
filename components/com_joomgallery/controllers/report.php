<?php
/****************************************************************************************\
**   JoomGallery 3                                                                   **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2019  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

jimport('joomla.application.component.controller');

/**
 * JoomGallery Report Controller
 *
 * @package JoomGallery
 * @since   2.1
 */
class JoomGalleryControllerReport extends JControllerLegacy
{
  /**
   * Sends a report
   *
   * @return  void
   * @since   2.1
   */
  public function send()
  {
    $model = $this->getModel('report');

    // Determine correct redirect URL
    $id       = JRequest::getInt('id');
    $catid    = JRequest::getInt('catid');
    $toplist  = JRequest::getVar('toplist');
    $sstring  = JRequest::getVar('sstring');

    $redirect_url = 'index.php?view=detail&id='.$id;
    if($catid)
    {
      // Request was initiated by category view
      $redirect_url = 'index.php?view=category&catid='.$catid;
      $query_append = 'catid='.$catid;
    }
    elseif($toplist !== null)
    {
      // Request was initiated from toplist view
      if(empty($toplist))
      {
        // Toplist view 'Most viewed'
        $redirect_url = 'index.php?view=toplist';
        $query_append = 'toplist=mostviewed';
      }
      else
      {
        // Any other toplist view
        $redirect_url = 'index.php?view=toplist&type='.$toplist;
        $query_append = 'toplist='.$toplist;
      }
    }
    elseif($sstring !== null)
    {
     // Request was initiated from search view
      $redirect_url = 'index.php?view=search&sstring='.$sstring;
      $query_append = 'sstring='.$sstring;
    }

    if(!$model->send($redirect_url))
    {
      $this->setRedirect(JRoute::_('index.php?view=report&tmpl=component&id='.$id.$query_append, false), $model->getError(), 'error');
    }
    else
    {
      $this->setRedirect(JRoute::_('index.php?view=report&tmpl=component&id='.$id.$query_append, false), JText::_('COM_JOOMGALLERY_COMMON_REPORT_SENT'));
    }
  }
}