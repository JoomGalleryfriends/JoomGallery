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

/**
 * JoomGallery Configuration Controller
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryControllerConfig extends JoomGalleryController
{
  /**
   * Constructor
   *
   * @return  void
   * @since   1.5.5
   */
  public function __construct()
  {
    parent::__construct();

    // Access check
    if(!JFactory::getUser()->authorise('core.admin', _JOOM_OPTION))
    {
      $this->setRedirect(JRoute::_($this->_ambit->getRedirectUrl(''), false), JText::_('COM_JOOMGALLERY_COMMON_MSG_NOT_ALLOWED_TO_CONFIGURE'), 'notice');
      $this->redirect();
    }

    // Register tasks
    $this->registerTask('new',          'edit');
    $this->registerTask('apply',        'save');
    $this->registerTask('orderup',      'order');
    $this->registerTask('orderdown',    'order');

    // Set view
    if($this->_config->isExtended())
    {
      JRequest::setVar('view', 'configs');
    }
    else
    {
      JRequest::setVar('view', 'config');
    }
  }

  /**
   * Displays the edit form of a config row
   *
   * @return  void
   * @since   2.0
   */
  public function edit()
  {
    $id  = JRequest::getInt('id');
    $cid = JRequest::getVar('cid', array(), 'post', 'array');

    if(!$id && count($cid) && $cid[0])
    {
      JRequest::setVar('id', (int) $cid[0]);
    }

    JRequest::setVar('view', 'config');
    JRequest::setVar('hidemainmenu', 1);

    parent::display();
  }

  /**
   * Saves the configuration
   *
   * @return  void
   * @since   1.5.5
   */
  public function save()
  {
    $config = JoomConfig::getInstance('admin');

    $id = false;
    $existing_row = 0;
    $group_id = 0;
    if($config->isExtended())
    {
      $id = JRequest::getInt('id');
      $existing_row = JRequest::getInt('based_on');

      if(!$id)
      {
        $group_id = JRequest::getInt('group_id');
      }
    }
    else
    {
      $id = 1;
    }

    $post = JRequest::get('post');

    if(!$id = $config->save($post, $id, $existing_row, $group_id))
    {
      $this->setRedirect($this->_ambit->getRedirectUrl(), JText::_('COM_JOOMGALLERY_CONFIG_MSG_SETTINGS_ERROR'), 'error');

      return;
    }

    $propagate_changes = JRequest::getBool('propagate_changes');

    // The changes have to be propagated to the other config rows
    // if the default row was changed or if propagation is requested
    $success = true;
    if(!$id || $id == 1 || $propagate_changes)
    {
      $success = $this->getModel('configs')->propagateChanges($post, $id, $propagate_changes);
    }

    if($success)
    {
      $controller = '';
      if(!$config->isExtended())
      {
        if(JRequest::getCmd('task') == 'apply')
        {
          $controller = 'config';
        }
      }
      else
      {
        $controller = null;
      }
      $this->setRedirect($this->_ambit->getRedirectUrl($controller, $id, 'id'), JText::_('COM_JOOMGALLERY_CONFIG_MSG_SETTINGS_SAVED'));
    }
    else
    {
      $this->setRedirect($this->_ambit->getRedirectUrl(), $this->getModel('configs')->getError(), 'error');
    }
  }

  /**
   * Removes one or more config rows
   *
   * @return  void
   * @since   2.0
   */
  public function remove()
  {
    $config = JoomConfig::getInstance('admin');
    $cid    = JRequest::getVar('cid', array(), 'post', 'array');

    if(!count($cid))
    {
      $this->setRedirect($this->_ambit->getRedirectUrl(), JText::_('COM_JOOMGALLERY_CONFIGS_NO_ROWS_SELECTED'), 'notice');

      return;
    }

    $count = 0;
    foreach($cid as $config_id)
    {
      if($config->delete($config_id))
      {
        $count++;
      }
      else
      {
        JError::raiseWarning(500, $config->getError());
      }
    }

    if(!$count)
    {
      $msg  = JText::_('COM_JOOMGALLERY_CONFIGS_MSG_ERROR_DELETING');
      $type = 'error';
    }
    else
    {
      $type = 'message';
      $msg  = JText::plural('COM_JOOMGALLERY_CONFIGS_MSG_ROWS_DELETED', $count);
    }

    $this->setRedirect($this->_ambit->getRedirectUrl(), $msg, $type);
  }

  /**
   * Moves the order of a config row
   *
   * @return  void
   * @since   2.0
   */
  public function order()
  {
    $cid = JRequest::getVar('cid', array(), 'post', 'array');

    // Direction
    $dir  = 1;
    $task = JRequest::getCmd('task');
    if($task == 'orderup')
    {
      $dir = -1;
    }

    if(isset($cid[0]))
    {
      $row = JTable::getInstance('joomgalleryconfig', 'Table');
      $row->load((int)$cid[0]);
      $row->move($dir);
      //$row->reorder();
    }

    $this->setRedirect($this->_ambit->getRedirectUrl());
  }

  /**
   * Saves the order of the config rows
   *
   * @return  void
   * @since   2.0
   */
  public function saveOrder()
  {
    $cid    = JRequest::getVar('cid', array(0), 'post', 'array');
    $order  = JRequest::getVar('order', array(0), 'post', 'array');

    // Create and load the categories table object
    $row = JTable::getInstance('joomgalleryconfig', 'Table');

    // Update the ordering for items in the cid array
    for($i = 0; $i < count($cid); $i ++)
    {
      $row->load((int)$cid[$i]);
      if($row->ordering != $order[$i])
      {
        $row->ordering = $order[$i];
        $row->check();
        if(!$row->store())
        {
          JError::raiseError( 500, $this->_db->getErrorMsg());

          return false;
        }
      }
    }

    //$row->reorder();

    $msg = JText::_('COM_JOOMGALLERY_COMMON_MSG_NEW_ORDERING_SAVED');
    $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
  }

  /**
   * Cancel editing or creating a config row
   *
   * @access  public
   * @return  void
   * @since   2.0
   */
  function cancel()
  {
    $this->setRedirect($this->_ambit->getRedirectUrl());
  }

  /**
   * Reset of the entire configuration
   *
   * @return  void
   * @since   3.4.0
   */
  public function resetconfig()
  {
    $path = JPATH_ADMINISTRATOR.'/components/com_joomgallery/sql/setdefault.install.mysql.utf8.sql';

    if(file_exists($path))
    {
      $config = JoomConfig::getInstance('admin');

      // Get all configuration sets
      $query = $this->_db->getQuery(true)
            ->select('id')
            ->from(_JOOM_TABLE_CONFIG)
            ->order('id DESC');
      $this->_db->setQuery($query);

      try
      {
        $ids = $this->_db->loadObjectList();
      }
      catch(Exception $e)
      {
        $this->setRedirect($this->_ambit->getRedirectUrl(), $e->getMessage(), 'error');
        return false;
      }

      // Delete all configuration sets
      foreach($ids as $id)
      {
        if(!$config->delete((int)$id->id, true))
        {
          $this->setRedirect($this->_ambit->getRedirectUrl(), $config->getError(), 'error');
          return false;
        }
      }

      // Create a new configuration set with id = 1 with default values
      $query = file_get_contents($path);
      $this->_db->setQuery($query);

      try
      {
        $this->_db->execute();

        // Load additional alternative configuration options
        $resetTo = JRequest::getInt('reset_to');
        $path    = JPATH_ADMINISTRATOR.'/components/com_joomgallery/sql/';

        switch($resetTo)
        {
          case 1:
            $path .= 'setdefault.mini.mysql.utf8.sql';
            break;
          case 2:
            $path .= 'setdefault.middle.mysql.utf8.sql';
            break;
          case 3:
            $path .= 'setdefault.full.mysql.utf8.sql';
            break;
          case 99:
            $path .= 'setdefault.user.mysql.utf8.sql';
            break;
          default:
            $path = '';
            break;
        }

        if(!empty($path) && file_exists($path))
        {
          $query = file_get_contents($path);
          $this->_db->setQuery($query);

          $this->_db->execute();
        }

        // Load configuration set with id = 1 and save the CSS file joom_settings.css
        $config = new JoomAdminConfig(1);
        $config->saveCSS();
      }
      catch(Exception $e)
      {
        $this->setRedirect($this->_ambit->getRedirectUrl(), $e->getMessage(), 'error');
        return false;
      }

      $this->setRedirect($this->_ambit->getRedirectUrl(), JText::_('COM_JOOMGALLERY_CONFIG_MSG_RESETCONFIG_SUCCESSFUL'), 'message');
    }
    else
    {
      $this->setRedirect($this->_ambit->getRedirectUrl(), JText::_('COM_JOOMGALLERY_CONFIG_MSG_RESETCONFIG_NOT_SUCCESSFUL'), 'error');
    }
  }

  /**
   * Upload an image to test image manipulation settings
   *
   * @return  void
   * @since   3.6.0
   */
  public function upload()
  {
    $input = Factory::getApplication()->input;
    $img_file = $input->files->get('imageupload');

    $msg = '';

    // Check for upload errors
    if($img_file['error'] > 0)
    {
      // Common PHP errors
      $uploadErrors = array(
        1 => JText::_('COM_JOOMGALLERY_UPLOAD_ERROR_PHP_MAXFILESIZE'),
        2 => JText::_('COM_JOOMGALLERY_UPLOAD_ERROR_HTML_MAXFILESIZE'),
        3 => JText::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_PARTLY_UPLOADED'),
        4 => JText::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_NOT_UPLOADED')
      );

      if(in_array($img_file['error'], $uploadErrors))
      {
        $msg = JText::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_CODE', $uploadErrors[$img_file['error']]);
      }
      else
      {
        $msg =  JText::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_CODE', JText::_('COM_JOOMGALLERY_UPLOAD_ERROR_UNKNOWN'));
      }

      $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
      return;
    }

    // Get file extension
    $imgname = JFile::makeSafe($img_file['name']);
    $tag     = strtolower(JFile::getExt($img_file['name']));

    // Check for right format
    if(   (($tag != 'jpeg') && ($tag != 'jpg') && ($tag != 'jpe') && ($tag != 'gif') && ($tag != 'png'))
    || strlen($img_file['tmp_name']) == 0
    || $img_file['tmp_name'] == 'none'
    )
    {
      $msg  = JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_TYPE');

      $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
      return;
    }

    // create path to store uploaded image
    $dst_path  = $this->_ambit->get('temp_path') . 'configtestimg_orig.' . $tag;
    $href_path = str_replace(JPATH_ROOT,'',$dst_path);
    $href_path = str_replace('\\','/',$href_path);

    // upload image to tmp folder
    $return = JFile::upload($img_file['tmp_name'], $dst_path);

    if(!$return)
    {
      $msg = JText::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_UPLOADING', $imgname);

      $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
      return;
    }

    // Write json infos
    $img_info  = JoomIMGtools::analyseImage($dst_path);
    $info_obj  = array('path' => $dst_path, 'href' => $href_path, 'filesize' => filesize($dst_path), 'filetype' => $img_info['type'], 'frames' => $img_info['frames'], 'dimension' => $img_info['width'].'x'.$img_info['height']);
    $info_obj  = (object) $info_obj;
    $json_path = $this->_ambit->get('temp_path').'configtestimg.json';
    $model = $this->getModel('config');
    $success = $model->writeInfoToJson($json_path, array('orig' => $info_obj));

    if(!$success)
    {
      $msg = JText::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_JSON', $imgname);

      $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
      return;
    }

    $msg = JText::_('COM_JOOMGALLERY_UPLOAD_MSG_SUCCESSFULL').': '.$imgname;

    $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
  }

  /**
   * Recreate an image (detail or thumb) to visualize manipulation settings
   *
   * @return  void
   * @since   3.6.0
   */
  public function recreate()
  {
    $input = Factory::getApplication()->input;
    $type = $input->get('gen_type','','STRING');
    $side = $input->get('gen_side','','STRING');

    $msg = '';

    $model = $this->getModel('config');
    $info = $model->readInfoFromJson($this->_ambit->get('temp_path').'configtestimg.json');

    if(!$info)
    {
      $msg = JText::_('COM_JOOMGALLERY_CONFIG_FS_IP_JSON_INEXISTENT');

      $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
      return;
    }

    $success = $model->resizeImage($info->orig->path,$type,$side);

    if(!$success)
    {
      $msg = JText::_('COM_JOOMGALLERY_CONFIG_FS_IP_GENERATION_ERROR');

      $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
      return;
    }

    $msg = JText::_('COM_JOOMGALLERY_CONFIG_FS_IP_GENERATION_SUCCESSFULL');

    $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
  }

  /**
   * Delete images to test image manipulation settings
   *
   * @return  void
   * @since   3.6.0
   */
  public function delete()
  {
    // files to delete
    $files = array('configtestimg_orig.jpg','configtestimg.json','configtestimg_detailL.jpg','configtestimg_detailR.jpg','configtestimg_thumbL.jpg','configtestimg_thumbR.jpg');

    foreach ($files as $file)
    {
      $file = $this->_ambit->get('temp_path').$file;

      if(file_exists($file))
      {
        if(!JFile::delete($file))
        {
          $msg = JText::_('COM_JOOMGALLERY_CONFIG_FS_IP_DELETE_ERROR');

          $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
          return;
        }
      }
    }

    $msg = JText::_('COM_JOOMGALLERY_CONFIG_FS_IP_DELETE_SUCCESSFULL');

    $this->setRedirect($this->_ambit->getRedirectUrl(), $msg);
  }
}
