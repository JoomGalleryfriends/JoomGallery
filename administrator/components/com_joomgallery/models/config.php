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
 * Configuration model
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryModelConfig extends JoomGalleryModel
{
  /**
   * Attempts to determine if gd is configured, and if so,
   * what version is installed
   *
   * @return  string  The result of request
   * @since   1.0.0
   */
  public function getGDVersion()
  {
    if(!extension_loaded('gd'))
    {
      return;
    }

    $phpver = substr(phpversion(), 0, 3);
    // gd_info came in at 4.3
    if($phpver < 4.3)
    {
      return -1;
    }

    if(function_exists('gd_info'))
    {
      $ver_info = gd_info();
      preg_match('/\d/', $ver_info['GD Version'], $match);
      $gd_ver = $match[0];
      return $match[0];
    }
    else
    {
      return;
    }
  }

  /**
   * Checks if exec is disabled in php.ini
   *
   * @return  boolean true if exec exists in array of disabled fuctions
   * @since   1.0.0
   */
  public function getDisabledExec()
  {
    $disable_functions = explode(',', ini_get('disable_functions'));
    foreach($disable_functions as $disable_function)
    {
      if(trim($disable_function) == 'exec')
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Attempts to determine if ImageMagick is configured, and if so,
   * what version is installed
   *
   * @return  string  The result of request
   * @since   1.0.0
   */
  public function getIMVersion()
  {
    $config = JoomConfig::getInstance();
    $status = null;

    @exec(trim($config->get('jg_impath')).'convert -version', $output_convert, $status);
    @exec(trim($config->get('jg_impath')).'magick -version', $output_magick, $status);

    if($output_magick)
    {
      return $output_magick[0];
    }
    elseif($output_convert)
    {
      return $output_convert[0];
    }
    else
    {
      return 0;
    }
  }

  /**
   * Returns the title of the current config row
   *
   * @return  string  The title of the current config row
   * @since   2.0
   */
  public function getConfigTitle()
  {
    $query = $this->_db->getQuery(true)
          ->select('g.title')
          ->from(_JOOM_TABLE_CONFIG.' AS c')
          ->from('#__usergroups AS g')
          ->where('c.group_id = g.id')
          ->where('c.id = '.JRequest::getInt('id'));
    $this->_db->setQuery($query);

    return $this->_db->loadResult();
  }

  /**
   * Creates detail or thumbnail image based on settings
   *
   * @param   string    $src   path to the source image file
   * @param   string    $type  type of image to create
   * @param   string    $side  create R(ight) or L(eft) image?
   * @return  true on success, false otherwise
   * @since   3.6
   */
  public function resizeImage($src,$type,$side)
  { 
    $input       = Factory::getApplication()->input;

    $debugoutput = '';
    $dst_path    = $this->_ambit->get('temp_path').'configtestimg_'.$type.$side.'.'.JFile::getExt($src);
    $json_path   = $this->_ambit->get('temp_path').'configtestimg.json';    

    // resize image
    $timeStart = microtime(true);
    switch ($type)
    {
      case 'detail':
        // Create new detail image
        $return = JoomIMGtools::resizeImage($debugoutput,
                                            $src,
                                            $dst_path,
                                            $this->_config->get('jg_resizetomaxwidth'),
                                            $this->_config->get('jg_maxwidth'),
                                            $this->_config->get('jg_maxheight'),
                                            $this->_config->get('jg_thumbcreation'),
                                            $this->_config->get('jg_picturequality'),
                                            false,
                                            0,
                                            $this->_config->get('jg_detailautorot'),
                                            false,
                                            true,
                                            false
                                            );
        if(!$return)
        {
          return false;
        }
        $quality = $input->get('jg_picturequality','','STRING');

        break;

      case 'thumb':
        // Create thumb
        $return = JoomIMGtools::resizeImage($debugoutput,
                                            $src,
                                            $dst_path,
                                            $this->_config->get('jg_useforresizedirection'),
                                            $this->_config->get('jg_thumbwidth'),
                                            $this->_config->get('jg_thumbheight'),
                                            $this->_config->get('jg_thumbcreation'),
                                            $this->_config->get('jg_thumbquality'),
                                            $this->_config->get('jg_cropposition'),
                                            0,
                                            $this->_config->get('jg_thumbautorot'),
                                            false,
                                            false,
                                            true
                                          );
        if(!$return)
        {
          return false;
        }
        $quality = $input->get('jg_thumbquality','','STRING');

        break;

      default:
        // nothing to do
        break;
    }
    $timeEnd = microtime(true);
    $memoryMax = memory_get_peak_usage(); //memory usage in bytes
    $execTime = $timeEnd - $timeStart; //time in seconds and milliseconds

    // create href path
    $href_path = str_replace(JPATH_ROOT,'',$dst_path);
    $href_path = str_replace('\\','/',$href_path);

    // get image infos
    $img_info = JoomIMGtools::analyseImage($dst_path);
    $info_obj = array('path' => $dst_path, 'href' => $href_path, 'filesize' => filesize($dst_path), 'filetype' => $img_info['type'], 'frames' => $img_info['frames'], 'dimension' => $img_info['width'].'x'.$img_info['height'], 'processing_time' => $execTime, 'used_memory' => $memoryMax, 'quality' => $quality);
    $info_obj = (object) $info_obj;
    $this->writeInfoToJson($json_path, array($type.$side => $info_obj));

    return true;
  }

  /**
   * Writes infos to json file
   *
   * @param   string    $json   path to the json file
   * @param   array     $data   assoziative array with infos to store into json
   * @return  true on success, false otherwise
   * @since   3.6
   */
  public function writeInfoToJson($json, $data)
  {
    $content = null;
    if(file_exists($json))
    {
      // json already exists. Add data to existing json
      $json_string = file_get_contents($json);
      $content = json_decode($json_string, true);

      if($content)
      {
        foreach($data as $key => $value)
        {
          // update json content
          $content[$key] = $value;
        }
      }
    }

    if (!$content)
    {
      // set data as json data
      $content = $data;
    }

    $json_string = json_encode($content);
    file_put_contents($json, $json_string);

    return true;
  }

  /**
   * Read infos from json file
   *
   * @param   string    $json   path to the json file
   * @return  object with the stored infos, false otherwise
   * @since   3.6
   */
  public function readInfoFromJson($json)
  {
    if(file_exists($json))
    {
      $json_string = file_get_contents($json);
      return json_decode($json_string);
    }

    return false;
  }
}
