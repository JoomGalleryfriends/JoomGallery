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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\File;

/**
 * Install method
 * is called by the installer of Joomla!
 *
 * @access  protected
 * @return  void
 * @since   2.0
 */
class Com_JoomGalleryInstallerScript
{
  /**
   * Version string of the current version
   *
   * @var string
   */
  private $version = '3.6.0';

  /**
   * Version string of the current installed version
   *
   * @var string
   */
  private $act_version = '';

  /**
   * Settings that are set in the current installation, but will be removed/changed in a newer version
   *
   * @var array  array('id1_value'=>array('id'=>value,'config-key'=>old_value),'id2_value'=>array(),...)
   */
  private $old_settings = array();

  /**
   * Extenions that have to be installed/updated or uninstalled
   * name,type,element,folder,client_id (string, columns of table #__extension)
   * install_type (string, folder or url)
   * install_source (string, source of zip file. depends on install_type. folder: path, url: external url)
   *
   * @var array
   */
  private $extensions = array(array('name'=>'GitHub Feed',
                                    'type'=>'module',
                                    'element'=>'mod_joomgithub',
                                    'folder'=>'',
                                    'client_id'=>'1',
                                    'install_type'=>'url',
                                    'install_source'=>'https://www.joomgalleryfriends.net/files/Unkategorisiert/mod_joomgithub.zip'));


  /**
   * Preflight method
   *
   * Is called afore installation and update processes
   *
   * @param   $type   string  'install', 'discover_install', or 'update'
   * @return  boolean False if installation or update shall be prevented, true otherwise
   * @since   2.1
   */
  public function preflight($type = 'install')
  {
    if(version_compare(JVERSION, '4.0', 'ge') || version_compare(JVERSION, '3.0', 'lt'))
    {
      JError::raiseWarning(500, 'JoomGallery 3.x is only compatible to Joomla! 3.x');

      return false;
    }

    //************* Read old settings that will be changed/removed *************
    if($type == 'update')
    {
      //************* Get actual installed JoomGallery version ************
      $xml = simplexml_load_file(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'joomgallery.xml');
      if(isset($xml->version))
      {
        $this->act_version = $xml->version;
      }
      //************* End et actual installed JoomGallery version ************

      // Define global constant _JOOM_TABLE_CONFIG
      define('_JOOM_TABLE_CONFIG', '#__joomgallery_config');

      // Register Config-Table
      include_once JPATH_ADMINISTRATOR.'/components/com_joomgallery/tables/joomgalleryconfig.php';

      // Load JoomGallery configuration
      $config      = JTable::getInstance('joomgalleryconfig', 'Table');
      $config_keys = $config->getFields();

      $db = JFactory::getDbo();
      $query = $db
          ->getQuery(true)
          ->select('id')
          ->from($db->quoteName(_JOOM_TABLE_CONFIG));
      $db->setQuery($query);
      $config_ids = $db->loadColumn();

      // Bring versions to a machine readable form
      $act_version = explode('.',$this->act_version);
      $new_version = explode('.',$this->version);

      // create $old_settings with all available config rows
      foreach($config_ids as $key => $id)
      {
        $this->old_settings[$id] = array('id'=>$id);
      }

      // if jg_thumbcreation still exists
      if(array_key_exists('jg_thumbcreation', $config_keys))
      {
        foreach($this->old_settings as $key => $row)
        {
          $config->load($row['id']);
          $this->old_settings[$key]['jg_thumbcreation'] = $config->jg_thumbcreation;
        }
      }

      // if jg_upload_exif_rotation still exists
      if(array_key_exists('jg_upload_exif_rotation', $config_keys))
      {
        foreach($this->old_settings as $key => $row)
        {
          $config->load($row['id']);
          $this->old_settings[$key]['jg_upload_exif_rotation'] = $config->jg_upload_exif_rotation;
        }
      }

      // act_version <= 3.5.x and new_version >= 3.6.x
      if($act_version[0] <= 3 && $act_version[1] <= 5 && $new_version[0] >= 3 && $new_version[1] >= 6)
      {
        foreach($this->old_settings as $key => $row)
        {
          $config->load($row['id']);
          $this->old_settings[$key]['jg_maxwidth'] = $config->jg_maxwidth;
          $this->old_settings[$key]['jg_resizetomaxwidth'] = $config->jg_resizetomaxwidth;
          $this->old_settings[$key]['jg_useforresizedirection'] = $config->jg_useforresizedirection;
        }
      }
    }
    //*********************** End read old settings ***********************

    return true;
  }

  /**
   * Install method
   *
   * @return  boolean True on success, false otherwise
   * @since   2.0
   */
  public function install()
  {
    $app = JFactory::getApplication();
    jimport('joomla.filesystem.file');

    // Create image directories
    require_once JPATH_ADMINISTRATOR.'/components/com_joomgallery/helpers/file.php';
    $thumbpath  = JPATH_ROOT.'/images/joomgallery/thumbnails';
    $imgpath    = JPATH_ROOT.'/images/joomgallery/details';
    $origpath   = JPATH_ROOT.'/images/joomgallery/originals';
    $result     = array();
    $result[]   = JFolder::create($thumbpath);
    $result[]   = JoomFile::copyIndexHtml($thumbpath);
    $result[]   = JFolder::create($imgpath);
    $result[]   = JoomFile::copyIndexHtml($imgpath);
    $result[]   = JFolder::create($origpath);
    $result[]   = JoomFile::copyIndexHtml($origpath);
    $result[]   = JoomFile::copyIndexHtml(JPATH_ROOT.'/images/joomgallery');

    if(in_array(false, $result))
    {
      $app->enqueueMessage(JText::_('Unable to create image directories!'), 'error');

      return false;
    }

    // Install extensions that are not already installed
    $install_response = $this->installExtensions();
    if($install_response !== true)
    {
      foreach ($install_response as $codes)
      {
        $app->enqueueMessage(JText::_('Unable to install additional extension! Error-Code of installation: '.$codes), 'error');
      }
    }

    // Create news feed module
    $subdomain = '';
    $language = JFactory::getLanguage();
    if(strpos($language->getTag(), 'de-') === false)
    {
      $subdomain = 'en.';
    }
    $feed_params = array('cache'=>1,
                         'cache_time'=>15,
                         'moduleclass_sfx'=>'',
                         'rssurl'=>'https://www.'.$subdomain.'joomgalleryfriends.net/?format=feed&amp;type=rss',
                         'rssrtl'=>0,
                         'rsstitle'=>1,
                         'rssdesc'=>0,
                         'rssimage'=>1,
                         'rssitems'=>3,
                         'rssitemdesc'=>1,
                         'word_count'=>200);
    $feed_params = json_encode($feed_params);
    $this->createModule('JoomGallery News','joom_cpanel','mod_feed',1,$app->getCfg('access'),1,$feed_params,1,'*');

    // Create bounty feed module
    $bounty_params = array('CachingEnabled'=>1,
                         'shortcache'=>15,
                         'moduleclass_sfx'=>'',
                         'layout'=>'_:Issues',
                         'Owner'=>'JoomGalleryfriends',
                         'repo'=>'JoomGallery',
                         'CommitImg'=>0,
                         'DispCommitter'=>0,
                         'DispRecords'=>5,
                         'IssueLabels'=>'bounty',
                         'IssueStatus'=>'open',
                         'IssueSort'=>'updated',
                         'IssueOrder'=>'desc',
                         'DivSize'=>'0',
                         'DateFormat'=>'d.F Y');
    $bounty_params = json_encode($bounty_params);
    $this->createModule('JoomGallery Open Bounties','joom_cpanel','mod_joomgithub',2,$app->getCfg('access'),1,$bounty_params,1,'*');

    // joom_settings.css
    $temp = JPATH_ROOT.'/media/joomgallery/css/joom_settings.temp.css';
    $dest = JPATH_ROOT.'/media/joomgallery/css/joom_settings.css';

    if(!JFile::move($temp, $dest))
    {
      $app->enqueueMessage(JText::_('Unable to copy joom_settings.css!'), 'error');

      return false;
    }

    // copy layouts to frontend
    $layouts      = JPATH_ROOT.'/layouts/joomgallery/';
    $layout_files = array('index.html','seotext.php');

    if(!JFolder::exists($layouts))
    {
      if(!JFolder::create($layouts))
      {
        $app->enqueueMessage(JText::_('Unable to create layouts folder for JoomGallery!'), 'error');

      return false;
      }
    }

    foreach($layout_files as $file)
    {
      if(!JFile::copy(JPATH_ADMINISTRATOR.'/components/com_joomgallery/layouts/joomgallery/'.$file, $layouts.$file))
      {
        $app->enqueueMessage(JText::_('Unable to copy file "'.$file.'"!'), 'error');

        return false;
      }
    }
?>
    <div class="hero-unit">
      <img src="../media/joomgallery/images/joom_logo.png" alt="JoomGallery Logo" />
      <div class="alert alert-success">
        <h3>JoomGallery <?php echo $this->version; ?> was installed successfully.</h3>
      </div>
      <p>You may now start using JoomGallery or download specific language files afore:</p>
      <p>
        <a title="Start" class="btn" onclick="location.href='index.php?option=com_joomgallery'; return false;" href="#">Start now!</a>
        <a title="Languages" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery&controller=help'; return false;" href="#">Languages</a>
      </p>
    </div>
  <?php
  }

  /**
   * Update method
   *
   * @return  boolean True on success, false otherwise
   * @since   2.0
   */
  public function update()
  {
    $app = JFactory::getApplication();
    jimport('joomla.filesystem.file'); ?>

    <div class="hero-unit">
      <img src="../media/joomgallery/images/joom_logo.png" alt="JoomGallery Logo" />
      <div class="alert alert-info">
        <h3>Update JoomGallery to version: <?php echo $this->version; ?></h3>
      </div>
    </div>
    <?php

    $error = false;

    // Delete temporary joom_settings.temp.css
    if(JFile::exists(JPATH_ROOT.'/media/joomgallery/css/joom_settings.temp.css'))
    {
      if(!JFile::delete(JPATH_ROOT.'/media/joomgallery/css/joom_settings.temp.css'))
      {
        JError::raiseWarning(500, JText::_('Unable to delete temporary joom_settings.temp.css!'));

        $error = true;
      }
    }

    // Install extensions that are not already installed
    $install_response = $this->installExtensions();
    if($install_response !== true)
    {
      foreach ($install_response as $codes)
      {
        $app->enqueueMessage(JText::_('Unable to install additional extension! Error-Code of installation: '.$codes), 'error');
      }
    }

    // Create news feed module
    $subdomain = '';
    $language = JFactory::getLanguage();
    if(strpos($language->getTag(), 'de-') === false)
    {
      $subdomain = 'en.';
    }
    $feed_params = array('cache'=>1,
                         'cache_time'=>15,
                         'moduleclass_sfx'=>'',
                         'rssurl'=>'https://www.'.$subdomain.'joomgalleryfriends.net/?format=feed&amp;type=rss',
                         'rssrtl'=>0,
                         'rsstitle'=>1,
                         'rssdesc'=>0,
                         'rssimage'=>1,
                         'rssitems'=>3,
                         'rssitemdesc'=>1,
                         'word_count'=>200);
    $feed_params = json_encode($feed_params);
    $this->createModule('JoomGallery News','joom_cpanel','mod_feed',1,$app->getCfg('access'),1,$feed_params,1,'*');

    // Create bounty feed module
    $bounty_params = array('CachingEnabled'=>1,
                         'shortcache'=>15,
                         'moduleclass_sfx'=>'',
                         'layout'=>'_:Issues',
                         'Owner'=>'JoomGalleryfriends',
                         'repo'=>'JoomGallery',
                         'CommitImg'=>0,
                         'DispCommitter'=>0,
                         'DispRecords'=>5,
                         'IssueLabels'=>'bounty',
                         'IssueStatus'=>'open',
                         'IssueSort'=>'updated',
                         'IssueOrder'=>'desc',
                         'DivSize'=>'0',
                         'DateFormat'=>'d.F Y');
    $bounty_params = json_encode($bounty_params);
    $this->createModule('JoomGallery Open Bounties','joom_cpanel','mod_joomgithub',2,$app->getCfg('access'),1,$bounty_params,1,'*');

    //******************* Delete folders/files ************************************
    echo '<div class="alert alert-info">';
    echo '<h3>File system</h3>';

    $delete_folders = array();

    // MooRainbow assets
    $delete_folders[] = JPATH_ROOT.'/media/joomgallery/js/moorainbow';
    // Old vote view
    $delete_folders[] = JPATH_ROOT.'/components/com_joomgallery/views/vote';

    echo '<p>';
    echo 'Looking for orphaned files and folders from the old installation ';

    // Unzipped folder of latest auto update with cURL
    $temp_dir = false;
    $database = JFactory::getDbo();
    $query = $database->getQuery(true)
          ->select('jg_pathtemp')
          ->from('#__joomgallery_config');
    $database->setQuery($query);
    $temp_dir = $database->loadResult();
    if($temp_dir)
    {
      //$delete_folders[] = JPATH_SITE.'/'.$temp_dir.'update';

      for($i = 0; $i <= 100; $i++)
      {
        $update_folder = JPATH_SITE.'/'.$temp_dir.'update'.$i;
        if(JFolder::exists($update_folder))
        {
          $delete_folders[] = $update_folder;
        }
      }
    }

    $deleted = false;

    $jg_delete_error = false;
    foreach($delete_folders as $delete_folder)
    {
      if(JFolder::exists($delete_folder))
      {
        echo 'delete folder: '.$delete_folder.' : ';
        $result = JFolder::delete($delete_folder);
        if($result == true)
        {
          $deleted  = true;
          echo '<span class="label label-success">ok</span>';
        }
        else
        {
          $jg_delete_error = true;
          echo '<span class="label label-important">not ok</span>';
        }
        echo '<br />';
      }
    }

    // Files
    $delete_files = array();

    // Cache file of the newsfeed for the update checker JoomGallery < 3.3.5
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.joomgallery.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.en.joomgallery.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.joomgallery.net/components/com_newversion/rss/extensions3.rss').'.spc';
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('http://www.en.joomgallery.net/components/com_newversion/rss/extensions3.rss').'.spc';
    // Cache file of the newsfeed for the update checker JoomGallery >= 3.3.5
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.joomgalleryfriends.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.en.joomgalleryfriends.net/components/com_newversion/rss/extensions2.rss').'.spc';
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.joomgalleryfriends.net/components/com_newversion/rss/extensions3.rss').'.spc';
    $delete_files[] = JPATH_ADMINISTRATOR.'/cache/'.md5('https://www.en.joomgalleryfriends.net/components/com_newversion/rss/extensions3.rss').'.spc';

    // Zip file of latest auto update with cURL
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/temp/update.zip';
    // Old category form field
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/models/fields/category.php';
    // JHtml file that is not used anymore
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/helpers/html/joompopup.php';
    // JFormFields that aren't used anymore
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/models/fields/cbowner.php';
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/models/fields/owner.php';
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/models/fields/color.php';
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/models/fields/thumbnail.php';
    // Template files that aren't used anymore
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/category/tmpl/default_catpagination.php';
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/category/tmpl/default_imgpagination.php';
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/gallery/tmpl/default_pagination.php';
    // Old changelog.php
    $delete_files[] = JPATH_ROOT.'/administrator/components/com_joomgallery/changelog.php';
    // Old ordering form field
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/models/fields/ordering.php';
    // Old view file of MiniJoom
    $delete_files[] = JPATH_ADMINISTRATOR.'/components/com_joomgallery/views/mini/view.html.php';
    // Unnecessary layout XML files in views which cannot be linked from a menu
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/downloadzip/tmpl/default.xml';
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/edit/tmpl/default.xml';
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/editcategory/tmpl/default.xml';
    // Old CSS file of MiniJoom
    $delete_files[] = JPATH_ROOT.'/media/joomgallery/css/mini.css';
    // Old JavaScript files
    $delete_files[] = JPATH_ROOT.'/media/joomgallery/js/miniupload.js';
    $delete_files[] = JPATH_ROOT.'/media/joomgallery/js/thickbox3/js/jquery-latest.pack.js';
    // Old motion gallery
    $delete_files[] = JPATH_ROOT.'/media/joomgallery/js/motiongallery.js';
    // Old raw view for Cooliris
    $delete_files[] = JPATH_ROOT.'/components/com_joomgallery/views/category/view.raw.php';
    // HTC script for IE6
    $delete_files[] = JPATH_ROOT.'/media/joomgallery/js/pngbehavior.htc';
    // Override function for setting permissions via AJAX
    $delete_files[] = JPATH_ROOT.'/media/joomgallery/js/permissions.js';

    foreach($delete_files as $delete_file)
    {
      if(JFile::exists($delete_file))
      {
        echo 'delete file: '.$delete_file.' : ';
        $result = JFile::delete($delete_file);
        if($result == true)
        {
          $deleted  = true;
          echo '<span class="label label-success">ok</span>';
        }
        else
        {
          $jg_delete_error = true;
          echo '<span class="label label-important">not ok</span>';
        }
        echo '<br />';
      }
    }
   //******************* END delete folders/files ************************************

    if($deleted)
    {
      if($jg_delete_error)
      {
        echo '<span class="label label-important">problems in deletion of files/folders</span>';
        $error = true;
      }
      else
      {
        echo '<span class="label label-success">files/folders sucessfully deleted</span>';
      }
    }
    else
    {
      echo '<span class="label label-success">nothing to delete</span>';
    }

    echo '</p>';
    echo '</div>';

    //******************* Write joom_settings.css ************************************
    /*echo '<div class="alert alert-info">';
    echo '<h3>CSS</h3>';
    echo '<p>';
    echo 'Update configuration dependent CSS settings: ';

    require_once JPATH_ADMINISTRATOR.'/components/com_joomgallery/includes/defines.php';
    JLoader::register('JoomConfig', JPATH_ADMINISTRATOR.'/components/com_joomgallery/helpers/config.php');
    JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_joomgallery/tables');

    $config = JoomConfig::getInstance('admin');
    if(!$config->save())
    {
      $error = true;
      echo '<span class="label label-important">not ok</span>';
    }
    else
    {
      echo '<span class="label label-success">ok</span>';
    }

    echo '</p>';
    echo '</div>';*/
    //******************* End write joom_settings.css ************************************


    //************* Set new settings in config manager based on old settings *************
    // Bring versions to a machine readable form
    $act_version = explode('.',$this->act_version);
    $new_version = explode('.',$this->version);

    foreach($this->old_settings as $key => $old_setting)
    {
      $new_configs = new stdClass();
      $new_configs->id = $key;

      foreach ($old_setting as $key => $old)
      {
        switch ($key)
        {
          case 'jg_thumbcreation':
            if ($old == 'gd1' || $old == 'gd2')
            {
              $new_configs->jg_thumbcreation = 'gd2';
            }
            else
            {
              $new_configs->jg_thumbcreation = 'im';
            }
            break;

          case 'jg_upload_exif_rotation':
            switch ($old)
            {
              case 0:
                $new_thumbautorot  = 0;
                $new_detailautorot = 0;
                $new_origautorot   = 0;
                break;

              case 1:
                $new_thumbautorot  = 1;
                $new_detailautorot = 1;
                $new_origautorot   = 0;
                break;

              case 2:
                $new_thumbautorot  = 1;
                $new_detailautorot = 1;
                $new_origautorot   = 1;
                break;

              default:
                $new_thumbautorot  = 0;
                $new_detailautorot = 0;
                $new_origautorot   = 0;
                break;
            }
            $new_configs->jg_origautorot   = $new_origautorot;
            $new_configs->jg_detailautorot = $new_detailautorot;
            $new_configs->jg_thumbautorot  = $new_thumbautorot;
            break;

          case 'jg_useforresizedirection':
            // act_version <= 3.5.x and new_version >= 3.6.x
            if($act_version[0] <= 3 && $act_version[1] <= 5 && $new_version[0] >= 3 && $new_version[1] >= 6)
            {
              $new_configs->jg_useforresizedirection = $old + 1;
            }
            break;

          case 'jg_resizetomaxwidth':
            // act_version <= 3.5.x and new_version >= 3.6.x and resize was yes
            if( ($act_version[0] <= 3 && $act_version[1] <= 5 && $new_version[0] >= 3 && $new_version[1] >= 6) && $old == 1 )
            {
              $new_configs->jg_resizetomaxwidth = 4;
            }
            break;

          case 'jg_maxwidth':
            // act_version <= 3.5.x and new_version >= 3.6.x
            if($act_version[0] <= 3 && $act_version[1] <= 5 && $new_version[0] >= 3 && $new_version[1] >= 6)
            {
              // set jg_maxheight = jg_maxwidth
              $new_configs->jg_maxheight = $old;
            }
            break;

          default:
            // Nothing to update
            break;
        }
      }

      // Store new configs
      $store = JFactory::getDbo()->updateObject(_JOOM_TABLE_CONFIG, $new_configs, 'id');
      if( !$store )
      {
        echo '<span class="label label-important">Updating old setting-values with new configuration structure failed.</span>';
        $error = true;
      }
    }
    //********************** End set new settings in config manager **********************

    //************************** Create folders/files ************************************
    // copy layouts to frontend
    $layouts      = JPATH_ROOT.'/layouts/joomgallery/';
    $layout_files = array('index.html','seotext.php','seotextarea.php');

    if(!JFolder::exists($layouts))
    {
      if(!JFolder::create($layouts))
      {
        $app->enqueueMessage(JText::_('Unable to create layouts folder for JoomGallery!'), 'error');

      return false;
      }
    }

    foreach($layout_files as $file)
    {
      if(!JFile::copy(JPATH_ADMINISTRATOR.'/components/com_joomgallery/layouts/joomgallery/'.$file, $layouts.$file))
      {
        $app->enqueueMessage(JText::_('Unable to copy file "'.$file.'"!'), 'error');

        return false;
      }
    }
    //************************* END Create folders/files *********************************

    if($error)
    {
      echo '<div class="alert alert-error">
              <h3>Problem with the update to JoomGallery version '.$this->version.'<br />Please read the update infos above</h3>
            </div>';
      JFactory::getApplication()->enqueueMessage(JText::_('Problem with the update to JoomGallery version '.$this->version.'. Please read the update infos below'), 'error');
    }
    else
    { ?>
    <div class="hero-unit">
      <img src="../media/joomgallery/images/joom_logo.png" alt="JoomGallery Logo" />
      <div class="alert alert-success">
        <h3>JoomGallery was updated to version <?php echo $this->version; ?> successfully.</h3>
        <button class="btn btn-small btn-info" data-toggle="modal" data-target="#jg-changelog-popup"><i class="icon-list"></i> Changelog</button>
      </div>
      <p>You may now start using JoomGallery or download specific language files afore:</p>
      <p>
        <a title="Start" class="btn" onclick="location.href='index.php?option=com_joomgallery'; return false;" href="#">Go on!</a>
        <a title="Languages" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery&controller=help'; return false;" href="#">Languages</a>
      </p>
    </div>
    <?php JHtml::_('bootstrap.modal', 'jg-changelog-popup'); ?>
    <div class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="PopupChangelogModalLabel" aria-hidden="true" id="jg-changelog-popup">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h3 id="PopupChangelogModalLabel">Changelog</h3>
      </div>
      <div id="jg-changelog-popup-container">
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true"><?php echo JText::_('JTOOLBAR_CLOSE'); ?></button>
      </div>
    </div>
    <script type="text/javascript">
      jQuery('#jg-changelog-popup').modal({backdrop: true, keyboard: true, show: false});
      jQuery('#jg-changelog-popup').on('show', function ()
      {
        document.getElementById('jg-changelog-popup-container').innerHTML = '<div class="modal-body"><iframe class="iframe" frameborder="0" src="<?php echo JRoute::_('index.php?option=com_joomgallery&controller=changelog&tmpl=component'); ?>" height="400px" width="100%"></iframe></div>';
      });
    </script>
<?php
    }

    return !$error;
  }

  /**
   * Uninstall method
   *
   * @return  boolean True on success, false otherwise
   * @since   2.0
   */
  public function uninstall()
  {
    $path = JPATH_ROOT.'/images/joomgallery';
    if(JFolder::exists($path))
    {
      JFolder::delete($path);
    }

    // uninstall extension
    $install_response = $this->uninstallExtensions();
    if($install_response !== true)
    {
      foreach ($install_response as $codes)
      {
        $app->enqueueMessage(JText::_('Unable to uninstall additional extension! Error-Code of uninstallation: '.$codes), 'error');
      }
    }

    echo '<div class="alert alert-info">JoomGallery was uninstalled successfully!<br />
          Please remember to remove your images folders manually
          if you didn\'t use JoomGallery\'s default directories.</div>';

    return true;
  }

  /**
   * Uninstalls all extensions defined in $this->extensions.
   *
   * @return  boolean True on success, array of error codes otherwise
   */
  private function uninstallExtensions()
  {
    $error = false;
    $httpcodes = array();

    foreach ($this->extensions as $extension)
    {
      // check if extension is installed
      $db = Factory::getDbo();
      $query = $db->getQuery(true)
                  ->select('extension_id')
                  ->from($db->quoteName('#__extensions'))
                  ->where($db->quoteName('name').' = '.$db->quote($extension['name']))
                  ->where($db->quoteName('type').' = '.$db->quote($extension['type']))
                  ->where($db->quoteName('element').' = '.$db->quote($extension['element']))
                  ->where($db->quoteName('folder').' = '.$db->quote($extension['folder']));
      $db->setQuery($query);
      $extension_id = $db->loadResult();

      // uninstall extension if it is installed
      if (!empty($extension_id))
      {
        //uninstall extension
        $post_data = array(
          'boxchecked' => '1',
          'cid[]' => $extension_id,
          'task' => 'manage.remove',
          JSession::getFormToken() => '1',
        );
        $session = Factory::getSession();
        $url = JUri::base()."index.php?option=com_installer&view=manage";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: '.$session->getName().'='.$session->getId()));
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // check if installation was successful
        $error_codes = array(400,401,402,403,404,405,408,500,501,502,503,504,505);
        if(in_array($httpcode,$error_codes) || !empty($response))
        {
          $error = true;
          array_push($httpcodes,$httpcode);
        }
      }
    }

    if(!$error)
    {
      return true;
    }
    else
    {
      return $httpcodes;
    }
  }

  /**
   * Installs all extensions defined in $this->extensions.
   *
   * @return  boolean True on success, array of error codes otherwise
   */
  private function installExtensions()
  {
    $error = false;
    $httpcodes = array();

    foreach ($this->extensions as $extension)
    {
      // check if extension is already installed
      $db = Factory::getDbo();
      $query = $db->getQuery(true)
                  ->select('extension_id')
                  ->from($db->quoteName('#__extensions'))
                  ->where($db->quoteName('name').' = '.$db->quote($extension['name']))
                  ->where($db->quoteName('type').' = '.$db->quote($extension['type']))
                  ->where($db->quoteName('element').' = '.$db->quote($extension['element']))
                  ->where($db->quoteName('folder').' = '.$db->quote($extension['folder']));
      $db->setQuery($query);
      $extension_id = $db->loadResult();

      // install extension if it is not yet installed
      if (empty($extension_id))
      {
        //install extension
        $post_data = array(
          'installtype' => $extension['install_type'],
          'install_url' => $extension['install_source'],
          'task' => 'install.install',
          JSession::getFormToken() => '1',
          'return' => JSession::getFormToken(),
        );
        $session = Factory::getSession();
        $url = JUri::base()."index.php?option=com_installer&view=install";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Cookie: '.$session->getName().'='.$session->getId()));
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // check if installation was successful
        $error_codes = array(400,401,402,403,404,405,408,500,501,502,503,504,505);
        if(in_array($httpcode,$error_codes) || !empty($response))
        {
          $error = true;
          array_push($httpcodes,$httpcode);
        }
      }
    }

    if(!$error)
    {
      return true;
    }
    else
    {
      return $httpcodes;
    }
  }

  /**
   * Creates and publishes a module (extension need to be installed)
   *
   * @param   string   $title      title of the module
   * @param   string   $position   position fo the module to be placed
   * @param   string   $module     installation name of the module extension
   * @param   integer  $ordering   number of the sort order
   * @param   integer  $access     id of the access level
   * @param   integer  $showTitle  show or hide module title (0: hide, 1: show)
   * @param   string   $params     module params (json)
   * @param   integer  $client_id  module of which client (0: client, 1: admin)
   * @param   string   $lang       langage tag (language filter / *: all languages)
   *
   * @return  boolean True on success, false otherwise
   */
  private function createModule($title,$position,$module,$ordering,$access,$showTitle,$params,$client_id,$lang)
  {
    // check if the module already exists
    $db = Factory::getDbo();
    $query = $db->getQuery(true)
                ->select('id')
                ->from($db->quoteName('#__modules'))
                ->where($db->quoteName('position').' = '.$db->quote($position))
                ->where($db->quoteName('module').' = '.$db->quote($module));
    $db->setQuery($query);
    $module_id = $db->loadResult();

    // create module if it is not yet created
    if (empty($module_id))
    {
      $row = JTable::getInstance('module');
      $row->title     = $title;
      $row->ordering  = $ordering;
      $row->position  = $position;
      $row->published = 1;
      $row->module    = $module;
      $row->access    = $access;
      $row->showtitle = $showTitle;
      $row->params    = $params;
      $row->client_id = $client_id;
      $row->language  = $lang;
      if(!$row->store())
      {
        $app->enqueueMessage(JText::_('Unable to create "'.$title.'" module!'), 'error');

        return false;
      }

      $db = JFactory::getDbo();
      $query = $db->getQuery(true);
      $query->insert('#__modules_menu');
      $query->set('moduleid = '.$row->id);
      $query->set('menuid = 0');
      $db->setQuery($query);
      if(!$db->query())
      {
        $app->enqueueMessage(JText::_('Unable to assign "'.$title.'" module!'), 'error');

        return false;
      }
    }

    return true;
  }
}
