<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2019  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * JoomGallery user table class
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class TableJoomgalleryUser extends JTable 
{
  /** @var int Primary key */
  var $uid      = null;
  /** @var int */
  var $uuserid  = null;
  /** @var string */
  var $piclist  = null;
  /** @var int */
  var $layout   = null;
  /** @var string */
  var $time     = null;
  /** @var string */
  var $zipname  = null;

  function __construct($db)
  {
    parent::__construct(_JOOM_TABLE_USER, 'id', $db);
  }
}