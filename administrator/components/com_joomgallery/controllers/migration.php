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
 * JoomGallery Migration Controller
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryControllerMigration extends JoomGalleryController
{
  /**
   * Constructor
   *
   * @access  protected
   * @return  void
   * @since   1.5.5
   */
  function __construct()
  {
    parent::__construct();

    // Require the migration helper class
    require_once JPATH_COMPONENT.'/helpers/migration.php';

    // Set view
    JRequest::setVar('view', 'migration');

    // Register tasks
    $this->registerTask('check',  'migrate');
    $this->registerTask('start',  'migrate');
  }

  /**
   * Migrates another gallery to JoomGallery
   *
   * @access  public
   * @return  void
   * @since   1.5.5
   */
  function migrate()
  {
    jimport('joomla.filesystem.file');

    $migration = JRequest::getCmd('migration', '');
    $task      = JRequest::getCmd('task');

    if(!JFile::exists(JPATH_COMPONENT.'/helpers/migration/migrate'.$migration.'.php'))
    {
      $this->setRedirect('index.php?option='._JOOM_OPTION.'&controller=migration');

      return;
    }

    require_once JPATH_COMPONENT.'/helpers/migration/migrate'.$migration.'.php';
    $classname    = 'JoomMigrate'.$migration;
    $migrateclass = new $classname();

    ob_start();
    $migrateclass->$task();
    JFactory::getApplication()->setUserState('joom.migration.output', ob_get_contents());
    ob_end_clean();

    if($task == 'check')
    {
      parent::display();
    }
  }
}