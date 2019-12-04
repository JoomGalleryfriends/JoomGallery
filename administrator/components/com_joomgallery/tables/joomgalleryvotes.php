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
 * JoomGallery votes table class
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class TableJoomgalleryVotes extends JTable
{
  /** @var int Primary key */
  var $voteid     = null;
  /** @var int */
  var $picid      = null;
  /** @var int */
  var $userid     = null;
  /** @var string */
  var $userip     = null;
  /** @var string */
  var $datevoted  = null;
  /** @var int */
  var $vote       = null;

  function __construct($db)
  {
    parent::__construct(_JOOM_TABLE_VOTES, 'voteid', $db);
  }
}