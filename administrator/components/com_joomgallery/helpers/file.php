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
 * JoomGallery File Class
 *
 * @static
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomFile
{
  /**
   * Cleaning of file/category name
   * optionally replace extension if present
   * replace special chars defined in backend
   *
   * @param   string  $orig               The original filename
   * @param   boolean $replace_extension  True for stripping the extension
   * @return  string  Cleaned name (with or without extension)
   * @since   1.0.0
   */
  public static function fixFilename($orig, $replace_extension = false)
  {
    $config = JoomConfig::getInstance();

    // Check if multibyte support installed
    if(in_array ('mbstring', get_loaded_extensions()))
    {
      // Get the funcs from mb
      $funcs = get_extension_funcs('mbstring');
      if(    in_array ('mb_detect_encoding', $funcs)
          && in_array ('mb_strtolower', $funcs)
         )
      {
        // Try to check if the name contains UTF-8 characters
        $isUTF = mb_detect_encoding($orig, 'UTF-8', true);
        if($isUTF)
        {
          // Try to lower the UTF-8 characters
          $orig = mb_strtolower($orig, 'UTF-8');
        }
        else
        {
          // Try to lower the one byte characters
          $orig = strtolower($orig);
        }
      }
      else
      {
        // TODO mbstring loaded but no needed functions
        // --> server misconfiguration
        $orig = strtolower($orig);
      }
    }
    else
    {
      // TODO no mbstring loaded, appropriate server for Joomla?
        $orig = strtolower($orig);
    }

    // Replace special chars
    $filenamesearch  = array();
    $filenamereplace = array();

    $filenamereplacearr = $config->get('jg_filenamereplace');
    $items = explode(',', $filenamereplacearr);
    if($items != FALSE)
    {
      // Contains pairs of <specialchar>|<replaced char(s)>
      foreach($items as $item)
      {
        if(!empty($item))
        {
          $workarray = explode('|', trim($item));
          if(    $workarray != FALSE
              && isset($workarray[0]) && !empty($workarray[0])
              && isset($workarray[1]) && !empty($workarray[1])
            )
          {
            array_push($filenamesearch, preg_quote($workarray[0]));
            array_push($filenamereplace, preg_quote($workarray[1]));
          }
        }
      }
    }

    // Replace whitespace with underscore
    array_push($filenamesearch, '\s');
    array_push($filenamereplace, '_');
    // Replace slash with underscore
    array_push($filenamesearch, '/');
    array_push($filenamereplace, '_');
    // Replace backslash with underscore
    array_push($filenamesearch, '\\\\');
    array_push($filenamereplace, '_');
    // Replace other stuff
    array_push($filenamesearch, '[^a-z_0-9-]');
    array_push($filenamereplace, '');

    // Checks for different array-length
    $lengthsearch  = count($filenamesearch);
    $lengthreplace = count($filenamereplace);
    if($lengthsearch > $lengthreplace)
    {
      while($lengthsearch > $lengthreplace)
      {
        array_push($filenamereplace, '');
        $lengthreplace = $lengthreplace + 1;
      }
    }
    else
    {
      if($lengthreplace > $lengthsearch)
      {
        while($lengthreplace > $lengthsearch)
        {
          array_push($filenamesearch, '');
          $lengthsearch = $lengthsearch + 1;
        }
      }
    }

    // Checks for extension
    $extensions = array('.jpeg','.jpg','.jpe','.gif','.png');
    $extension  = false;
    for($i = 0; $i < count($extensions); $i++)
    {
      $extensiontrue = substr_count($orig, $extensions[$i]);
      if($extensiontrue != 0 )
      {
        $extension = true;
        // If extension found, break
        break;
      }
    }
    // Replace extension if present
    if($extension)
    {
      $fileextension        = JFile::getExt($orig);
      $fileextensionlength  = strlen($fileextension);
      $filenamelength       = strlen($orig);
      $filename             = substr($orig, -$filenamelength, -$fileextensionlength - 1);
    }
    else
    {
      // No extension found (Batchupload)
      $filename = $orig;
    }
    for($i = 0; $i < $lengthreplace; $i++)
    {
      $searchstring = '!'.$filenamesearch[$i].'+!i';
      $filename     = preg_replace($searchstring, $filenamereplace[$i], $filename);
    }
    if($extension && !$replace_extension)
    {
      // Return filename with extension for regular upload
      return $filename.'.'.$fileextension;
    }
    else
    {
      // Return filename without extension for batchupload
      return $filename;
    }
  }

  /**
   * Check filename if it's valid for the filesystem
   * @param   string  $nameb        filename before any processing
   * @param   string  $namea        filename after processing in e.g. fixFilename
   * @param   boolean $checkspecial True if the filename shall be checked for
   *                                special characters only
   * @return  boolean True if the filename is valid, false otherwise
   * @since   2.0
  */
  public static function checkValidFilename($nameb, $namea = '', $checkspecial = false)
  {
    // TODO delete this function and the call of them?
    return true;

    // Check only for special characters
    if($checkspecial)
    {
      $pattern = '/[^0-9A-Za-z -_]/';
      $check = preg_match($pattern, $nameb);
      if($check == 0)
      {
        // No special characters found
        return true;
      }
      else
      {
        return false;
      }
    }
    // Remove extension from names
    $nameb = JFile::stripExt($nameb);
    $namea = JFile::stripExt($namea);

    // Check the old filename for containing only underscores
    if(strlen($nameb) - substr_count($nameb, '_') == 0)
    {
      $nameb_onlyus = true;
    }
    else
    {
      $nameb_onlyus = false;
    }
    if(    empty($namea)
        || (    !$nameb_onlyus
             && strlen($namea) == substr_count($nameb, '_')
           )
      )
    {
      return false;
    }
    else
    {
      return true;
    }
  }


  /**
   * Changes the permissions of a directory (or file)
   * either by the FTP-Layer if enabled
   * or by JPath::setPermissions (chmod()).
   *
   * Not sure but probable: J! 1.6 will use
   * FTP-Layer automatically in setPermissions
   * so JoomFile::chmod will become unnecessary.
   *
   * @param   string  $path   Directory or file for which the permissions will be changed
   * @param   string  $mode   Permissions which will be applied to $path
   * @param   boolean $is_dir True if the given path is a directory, false if it is a file
   * @return  boolean True on success, false otherwise
   * @since   1.5.0
   */
  public static function chmod($path, $mode, $is_dir = false)
  {
    static $ftpOptions;

    if(!isset($ftpOptions))
    {
      // Initialize variables
      jimport('joomla.client.helper');
      $ftpOptions = JClientHelper::getCredentials('ftp');
    }

    if($ftpOptions['enabled'] == 1)
    {
      // Connect the FTP client
      jimport('joomla.client.ftp');
      $ftp = JFTP::getInstance($ftpOptions['host'], $ftpOptions['port'], array(), $ftpOptions['user'], $ftpOptions['pass']);
      // Translate path to FTP path
      $path = JPath::clean(str_replace(JPATH_ROOT, $ftpOptions['root'], $path), '/');

      return $ftp->chmod($path, $mode);
    }
    else
    {
      if($is_dir)
      {
        return JPath::setPermissions(JPath::clean($path), null, $mode);
      }

      return JPath::setPermissions(JPath::clean($path), $mode, null);
    }
  }

  /**
   * Resize image with functions from gd/gd2/imagemagick
   *
   * Cropping function adapted from
   * 'Resize Image with Different Aspect Ratio'
   * Author: Nash
   * Website: http://nashruddin.com/Resize_Image_to_Different_Aspect_Ratio_on_the_fly
   *
   * @param   &string $debugoutput            debug information
   * @param   string  $src_file               Path to source file
   * @param   string  $dest_file              Path to destination file
   * @param   int     $settings               Resize to 0=width,1=height,2=max(width,height) or 3=crop
   * @param   int     $new_width              Width to resize
   * @param   int     $new_height             Height to resize
   * @param   int     $method                 1=gd1, 2=gd2, 3=im
   * @param   int     $dest_qual              Quality of the resized image ($config->jg_thumbquality/jg_picturequality)
   * @param   int     $cropposition           Only if $settings=3:
   *                                          image section to use for cropping
   * @param   int     $angle                  $angle to rotate the resized image anticlockwise
   * @param   boolean $metadata               true=preserve metadata in the resized image
   * @param   boolean $anim                   true=preserve animation if any
   * @return  boolean True on success, false otherwise
   * @since   1.0.0
   */
  public static function resizeImage(&$debugoutput, $src_file, $dest_file, $settings,$new_width, $new_height, $method,
                                      $dest_qual = 100, $cropposition = 0, $angle = 0, $metadata = false, $anim = false)
  {

    // animated gifs: https://phpimageworkshop.com/tutorial/5/manage-animated-gif-with-imageworkshop.html

    $config = JoomConfig::getInstance();

    // Ensure that the paths are valid and clean
    $src_file  = JPath::clean($src_file);
    $dest_file = JPath::clean($dest_file);

    // Analysis of the source image, if image is valid
    if( !($src_imginfo = JoomFile::analyseSRCimg($src_file)) )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_FILE_NOT_FOUND').'<br />';
      return false;
    }

    // GD can only handle JPG, PNG and GIF images
    if(    $src_imginfo['type'] != 'JPG'
       &&  $src_imginfo['type'] != 'PNG'
       &&  $src_imginfo['type'] != 'GIF'
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';
      return false;
    }

    if( ( $src_imginfo['width'] <= $new_width && $src_imginfo['height'] <= $new_width &&
          ($angle == 0 || $angle == 180 || $angle == -180) )
        ||
        ( $src_imginfo['height'] <= $new_width && $src_imginfo['width'] <= $new_width &&
          ($angle == 270 || $angle == -270 || $angle == 90 || $angle == -90))
        )
    {
      // If source image is already of the same size or smaller than the image
      // which shall be created only copy the source image to destination
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_NOT_NECESSARY').'<br />';
      if(!JFile::copy($src_file, $dest_file))
      {
        $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $dest_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';
        return false;
      }
      return true;
    }

    // Definition of type, dimension and origin of resized image
    if( !($dest_imginfo = JoomFile::defineDESTimg($dest_file, $src_imginfo, $settings, $new_width, $new_height, $cropposition, $angle)) )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';
      return false;
    }

    // create debugoutput
    switch($settings)
    {
    case 0:
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_HEIGHT');
      break;
    case 1:
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_WIDTH');
      break;
    case 2:
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_MAX');
      break;
    // Free resizing and cropping
    case 3:
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_CROP');
      break;
    }

    // Method for creation of the resized image
    switch($method)
    {
      case 'gd1':
        $debugoutput.='GD1...<br/>';
        // no animated gif support
        if(!function_exists('imagecreate'))
        // check, if GD is available
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED');
          return false;
        }
        // create GD Object from file
        $src_frames = JoomFile::imageCreateFrom_GD($src_file, $src_imginfo);
        // create empty GD Object for the resized image
        $dst_frames = array(array('duration'=>0));
        $dst_frames[0]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[0]['image']);
        if (in_array(false, $src_frames))
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          return false;
        }
        if($angle > 0)
        // rotate image, if needed
        {
          foreach ($src_frames as $key => $frame)
          {
            $src_frames[$key]['image'] = JoomFile::imageRotate_GD($src_frames[$key]['image'], $src_imginfo, $angle);
            $src_imginfo['width'] = imagesx($src_frames[$key]['image']);
            $src_imginfo['height'] = imagesy($src_frames[$key]['image']);
          }
        }

        foreach ($src_frames as $key => $frame)
        {
          if (!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
          // resizing with GD1
          {
            imagecopyresized( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, $dest_imginfo['offset_x'], $dest_imginfo['offset_y'],
                              $dest_imginfo['width'], $dest_imginfo['height'], $src_imginfo['width'], $src_imginfo['height']);
          }
          else
          {
            imagecopyresized( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, 0, 0,
                              $dest_imginfo['width'], $dest_imginfo['height'], $src_imginfo['width'], $src_imginfo['height']);
          }
          // write resized image to file
          $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
        }

        if ($metadata)
        // copy metadata if needed
        {
          $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
          if (!$meta_success)
          {
            $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
            return false;
          }      
        }
        if(!$success)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);
          $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
          if ($metadata)
          {
            $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
            if (!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              return false;
            }      
          }
          JoomFile::chmod($dir, '0755', true);
        }
        foreach ($src_frames as $key => $frame)
        {
          imagedestroy($src_frames[$key]['image']);
          imagedestroy($dst_frames[$key]['image']);
        }
        break;
      case 'gd2':
        $debugoutput.='GD2...<br/>';
        if(!function_exists('imagecreatefromjpeg'))
        // check, if GD is available
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED');
          return false;
        }
        if(!function_exists('imagecreatetruecolor'))
        // check, if GD2 is available
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_NO_TRUECOLOR');
          return false;
        }

        // create empty image of specified size
        $dst_frames = array();
        $dst_frames = array(array());
        if ($anim && $src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
        {
          // create GD Objects from gif-file
          JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifFrameExtractor.php');
          $gfe = new GifFrameExtractor();
          $src_frames = $gfe->extract($src_file);
          foreach ($src_frames as $key => $frame)
          {
            // create empty GD Objects for the resized frames
            $dst_frames[$key]['duration'] = $src_frames[$key]['duration'];
            $dst_frames[$key]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[$key]['image']);
          }
        }
        else
        {
          // create GD Object from file
          $src_frames = JoomFile::imageCreateFrom_GD($src_file, $src_imginfo);
          $dst_frames[0]['duration'] = 0;

          $dst_frames[0]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[0]['image']);
        }
        if (in_array(false, $src_frames))
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          return false;
        }

        if($angle > 0)
        // rotate image, if needed
        {
          foreach ($src_frames as $key => $frame)
          {
            $src_frames[$key]['image'] = JoomFile::imageRotate_GD($src_frames[$key]['image'], $src_imginfo, $angle);
            $src_imginfo['width'] = imagesx($src_frames[$key]['image']);
            $src_imginfo['height'] = imagesy($src_frames[$key]['image']);
          }
        }
        foreach ($src_frames as $key => $frame)
        {
          if($config->jg_fastgd2thumbcreation == 0)
          // use normal GD2 for resizing
          {
            if(!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
            {
              imagecopyresampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, $dest_imginfo['offset_x'], $dest_imginfo['offset_y'],
                                  $dest_imginfo['width'], $dest_imginfo['height'], $src_imginfo['width'], $src_imginfo['height'] );
            }
            else
            {
              imagecopyresampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, 0, 0,
                                  $dest_imginfo['width'], $dest_imginfo['height'], $src_imginfo['width'], $src_imginfo['height'] );
            }
          }
          else
          // use fast GD2 for resizing
          {
            if(!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
            {
              $dst_frames[$key]['image'] = JoomFile::fastImageCopyResampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0,
                                                                             $dest_imginfo['offset_x'], $dest_imginfo['offset_y'],
                                                                             $dest_imginfo['width'], $dest_imginfo['height'],
                                                                             $src_imginfo['width'], $src_imginfo['height'], 3,$src_imginfo );
            }
            else
            {
              $dst_frames[$key]['image'] = JoomFile::fastImageCopyResampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, 0, 0,
                                                                             $dest_imginfo['width'], $dest_imginfo['height'],
                                                                             $src_imginfo['width'], $src_imginfo['height'], 3,$src_imginfo );
            }
          }
        }
        // write resized image to file
        if ($anim && $src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
        {
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
          $gc = new GifCreator();
          $gc->create($dst_frames, 0);
          $success = file_put_contents($dest_file, $gc->getGif());
        }
        else
        {
          $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
        }
        
        if ($metadata)
        // copy metadata if needed
        {
          $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
          if (!$meta_success)
          {
            $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
            return false;
          }      
        }
        if(!$success)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);
          if ($anim && $src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
          {
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
            $gc = new GifCreator();
            $gc->create($dst_frames, 0);
            $success = file_put_contents($dest_file, $gc->getGif());
          }
          else
          {
            $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
          }
          if ($metadata)
          {
            $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
            if (!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              return false;
            }
          }
          JoomFile::chmod($dir, '0755', true);
        }
        foreach ($src_frames as $key => $frame)
        {
          imagedestroy($src_frames[$key]['image']);
          imagedestroy($dst_frames[$key]['image']);
        }
        break;
      case 'im':
        $debugoutput.='ImageMagick...<br/>';
        $disabled_functions = explode(',', ini_get('disabled_functions'));
        foreach($disabled_functions as $disabled_function)
        {
          if(trim($disabled_function) == 'exec')
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_EXEC_DISABLED').'<br />';
            return false;
          }
        }
        @exec(trim($config->jg_impath).'convert -version', $output_convert);
        @exec(trim($config->jg_impath).'magick -version', $output_magick);
        if ($output_convert)
        {
          $convert_path=trim($config->jg_impath).'convert';
        }
        elseif ($output_magick)
        {
          $convert_path=trim($config->jg_impath).'magick convert';
        }
        else
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_NOTFOUND').'<br />';
          return false;
        }
        $commands = '';
        // if resizing an animation but not preserving the animation, modify the src path for imagick
        if ($src_imginfo['animation']  && !$anim)
        {
          $src_file = $src_file.'[0]';
        }
        elseif ($src_imginfo['animation']  && $anim && $src_imginfo['type'] == 'GIF')
        {
          $commands .= ' -coalesce';
        }
        if($angle > 0)
        {
          $commands .= ' -auto-orient';
        }
        if(!$metadata)
        {
          $commands .= ' -strip';
        }
        // Crop the source image before resiszing if offsets setted before
        // example of crop: convert input -crop destwidthxdestheight+offsetx+offsety +repage output
        // +repage needed to delete the canvas
        if(!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
        {
          // Assembling the imagick command for cropping
          $commands .= ' -crop "'.$dest_imginfo['width'].'x'.$dest_imginfo['height'].'+'.$dest_imginfo['offset_x'].'+'.$dest_imginfo['offset_y'].'" +repage';
        }
        // Assembling the imagick command for resizing
        $commands  .= ' -resize "'.$dest_imginfo['width'].'x'.$dest_imginfo['height'].'" -quality "'.$dest_qual.'" -unsharp "3.5x1.2+1.0+0.10"';
        
        // Assembling the shell code for the resize with imagick
        $convert    = $convert_path.' '.$commands.' "'.$src_file.'" "'.$dest_file.'"';

        $return_var = null;
        $dummy      = null;
        // execute the resize
        @exec($convert, $dummy, $return_var);
        if($return_var != 0)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);
          @exec($convert, $dummy, $return_var);
          JoomFile::chmod($dir, '0755', true);
          if($return_var != 0)
          {
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';
            return false;
          }
        }
        break;
      default:
        JError::raiseError(500, JText::_('COM_JOOMGALLERY_UPLOAD_UNSUPPORTED_RESIZING_METHOD'));
        break;
    }
    // Set mode of uploaded picture
    JPath::setPermissions($dest_file);
    // Check that the resized image is valid
    if(!($src_imginfo = getimagesize($dest_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_ERROR').'<br />';
      return false;
    }
    return true;
  }

  /**
   * Rotate an image (only JPGs) with functions from gd/gd2/imagemagick (Supported image-types: JPG,PNG,GIF)
   *
   * @param   &string $debugoutput  Debug information
   * @param   string  $src          Path to source file
   * @param   int     $method       gd1/gd2/im
   * @param   int     $dest_qual    Image quality
   * @param   int     $angle        Angle to rotate the image anticlockwise
   * @param   boolean $auto_orient  If true, use the command option -auto-orient with
   *                                convert (ImageMagick), otherwise option -rotate is used
   * @param   boolean $metadata     true=preserve metadata during rotation
   * @return  boolean True on success, false otherwise
   * @since   3.4
   */
  public static function rotateImage(&$debugoutput, $src, $method = 'gd2', $dest_qual = 100, $angle = 0, $auto_orient = true, $metadata = true)
  {
    if($angle == 0)
    {
      // Nothing to do
      return true;
    }
    // Ensure that the path is valid and clean
    $src = JPath::clean($src);

    // Analysis of the source image, if image is valid
    //$imginfo = getimagesize($src_file, $src_metainfo);
    if(!($src_imginfo = JoomFile::analyseSRCimg($src)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_FILE_NOT_FOUND').'<br />';
      return false;
    }

    // GD can only handle JPG, PNG and GIF images
    if(    $src_imginfo['type'] != 'JPG'
       &&  $src_imginfo['type'] != 'PNG'
       &&  $src_imginfo['type'] != 'GIF'
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_ERROR_ROTATE_ONLY_JPG').'<br />';
      return false;
    }

    // Definition of type, dimension and origin of rotated image
    $dest_imginfo = array('width' => $src_imginfo['width'], 'height' => $src_imginfo['height'], 'type' => $src_imginfo['type'],
                          'offset_x' => 0, 'offset_y' => 0);
    if (key_exists('channels',$src_imginfo))
    {
      $dest_imginfo['channels'] = $src_imginfo['channels'];
    }
    if (key_exists('bits',$src_imginfo))
    {
      $dest_imginfo['bits'] = $src_imginfo['bits'];
    }

    switch($method)
    {
      case 'gd1':
      case 'gd2':
        if(!function_exists('imagecreatefromjpeg'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED').'<br />';
          return false;
        }
        if ($src_imginfo['animation'])
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_NO_ROTATION').'<br />';
          return false;
        }
        else
        {
          $src_frames = JoomFile::imageCreateFrom_GD($src, $src_imginfo);
          $dst_frames = array(array());
          $dst_frames[0]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[0]['image']);
        }
        if (!$src_frames)
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          return false;
        }
        // Rotate image
        $dst_frames[0]['image'] = JoomFile::imageRotate_GD($src_frames[0]['image'], $src_imginfo, $angle);
        $dst_imginfo['width'] = imagesx($src_frames[0]['image']);
        $dst_imginfo['height'] = imagesy($src_frames[0]['image']);
        
        // Rename source file so it dont gets overwritten
        $tmp = explode('.', $src);
        $src_orig = str_replace('.'.end($tmp),'_orig.'.end($tmp), $src);
        $rn_success = rename($src,$src_orig);
        // Write rotated file
        if ($rn_success)
        {
          $success = JoomFile::imageWriteFrom_GD($src,$dst_frames,$dest_qual,$dest_imginfo['type']);
        }
        else
        {
          $success = false;
        }
        if(!$success)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($src);
          JoomFile::chmod($dir, '0777', true);
          rename($src,$src_orig);
          JoomFile::imageWriteFrom_GD($src,$dst_frames,$dest_qual,$dest_imgtype);
          if ($metadata)
          // copy metadata if needed
          {
             $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);
            if (!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              return false;
            }
          }
          unlink($src_orig);
          JoomFile::chmod($dir, '0755', true);
        }
        else
        {
          if ($metadata)
          // copy metadata if needed
          {
             $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);
            if (!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              return false;
            }
          }
        }
        imagedestroy($src_frames[0]['image']);
        imagedestroy($dst_frames[0]['image']);
        unlink($src_orig);
        break;
      case 'im':
        $disabled_functions = explode(',', ini_get('disabled_functions'));
        foreach($disabled_functions as $disabled_function)
        {
          if(trim($disabled_function) == 'exec')
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_EXEC_DISABLED').'<br />';
            return false;
          }
        }
        $config = JoomConfig::getInstance();
        @exec(trim($config->jg_impath).'convert -version', $output_convert);
        @exec(trim($config->jg_impath).'magick -version', $output_magick);
        if ($output_convert)
        {
          $convert_path=trim($config->jg_impath).'convert';
        }
        elseif ($output_magick)
        {
          $convert_path=trim($config->jg_impath).'magick convert';
        }
        else
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_NOTFOUND').'<br />';
          return false;
        }
        // Finally the rotate
        if ($src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
        {
          $commands .= ' -coalesce';
        }
        if($auto_orient)
        {
          $commands = '-auto-orient';
        }
        else
        {
          $commands = '-rotate "-' . $angle . '"';
        }
        $commands  .= ' -quality '.$dest_qual;
        if ($src_imginfo['type'] == 'PNG')
        {
          // Rename source file so it dont gets overwritten
          $tmp = explode('.', $src);
          $src_orig = str_replace('.'.end($tmp),'_orig.'.end($tmp), $src);
          $rn_success = rename($src,$src_orig);
        }
        else
        {
          $src_orig = $src;
        }       
        $convert    = $convert_path.' '.$commands.' "'.$src_orig.'" "'.$src.'"';
        $return_var = null;
        $dummy      = null;
        @exec($convert, $dummy, $return_var);
        if($return_var != 0)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($src);
          JoomFile::chmod($dir, '0777', true);
          @exec($convert, $dummy, $return_var);
          if ($src_imginfo[2] == 'PNG')
          // copy metadata with GD for PNG images
          {
            $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);
            if (!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              return false;
            }
            unlink($src_orig);
          }
          JoomFile::chmod($dir, '0755', true);
          if($return_var != 0)
          {
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';
            return false;
          }
        }
        else
        {
          if ($src_imginfo['type'] == 'PNG')
          // copy metadata with GD for PNG images
          {
             $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);
            if (!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              return false;
            }
          }
        }
        if ($src_imginfo['type'] == 'PNG')
        {
          unlink($src_orig);
        }
        // Check that the resized image is valid
        if(!($src_imginfo = getimagesize($src)))
        {
          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';
          return false;
        }
        break;
      default:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_UNSUPPORTED_RESIZING_METHOD').'<br />';
        return false;
        break;
    }

    // Set mode of uploaded picture
    JPath::setPermissions($src);

    // We check that the image is valid
    if(!getimagesize($src))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_FILE_NOT_FOUND').'<br />';

      return false;
    }

    return true;
  }

  /**
   * Fast resizing of images with GD2
   * Notice: need up to 3/4 times more memory
   * http://de.php.net/manual/en/function.imagecopyresampled.php#77679
   * Plug-and-Play fastimagecopyresampled function replaces much slower
   * imagecopyresampled. Just include this function and change all
   * "imagecopyresampled" references to "fastimagecopyresampled".
   * Typically from 30 to 60 times faster when reducing high resolution
   * images down to thumbnail size using the default quality setting.
   * Author: Tim Eckel - Date: 09/07/07 - Version: 1.1 -
   * Project: FreeRingers.net - Freely distributable - These comments must remain.
   *
   * Optional "quality" parameter (defaults is 3). Fractional values are allowed,
   * for example 1.5. Must be greater than zero.
   * Between 0 and 1 = Fast, but mosaic results, closer to 0 increases the mosaic effect.
   * 1 = Up to 350 times faster. Poor results, looks very similar to imagecopyresized.
   * 2 = Up to 95 times faster.  Images appear a little sharp,
   *                              some prefer this over a quality of 3.
   * 3 = Up to 60 times faster.  Will give high quality smooth results very close to
   *                             imagecopyresampled, just faster.
   * 4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
   * 5 = No speedup.             Just uses imagecopyresampled, no advantage over
   *                             imagecopyresampled.
   *
   * @param   string  $dst_image  path to destination image
   * @param   string  $src_image  path to source image
   * @param   int     $dst_x      destination x point left above
   * @param   int     $dst_y      destination y point left above
   * @param   int     $src_x      source x point left above
   * @param   int     $src_y      source y point left above
   * @param   int     $dst_w      destination width
   * @param   int     $dst_h      destination height
   * @param   int     $src_w      source width
   * @param   int     $src_h      source height
   * @param   int     $quality    quality of destination (fix = 3) read instructions above
   * @param   array   $imginfo    imginfo-array from analysing the source image (JoomFile::analyseSRCimg)
   * @return  boolean True on success, false otherwise
   * @since   1.0.0
   */
  public static function fastImageCopyResampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h,
                                                $src_w, $src_h, $quality = 3, $imginfo)
  {
    if(empty($src_image) || empty($dst_image) || $quality <= 0)
    {
      return false;
    }

    //check, if it is a special image (transparency or animation)
    $special = false;
    if ($imginfo['animation'] || $imginfo['transparency'])
    {
      $special = true;
    }

    if($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h) && !$special)
    {
      $temp = imagecreatetruecolor($dst_w * $quality + 1, $dst_h * $quality + 1);
      imagecopyresized  ($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1,
                         $dst_h * $quality + 1, $src_w, $src_h);
      imagecopyresampled($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w,
                                      $dst_h, $dst_w * $quality, $dst_h * $quality);
      imagedestroy      ($temp);
    }
    else
    {
      imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w,
                                      $dst_h, $src_w, $src_h);
    }
    return $dst_image;
  }

  /**
   * Copies an index.html file into a specified folder
   *
   * @param   string  The folder path to copy the index.html file into
   * @return  boolean True on success, false otherwise
   * @since   1.5.5
   */
  public static function copyIndexHtml($folder)
  {
    jimport('joomla.filesystem.file');

    $src  = JPATH_ROOT.'/media/joomgallery/index.html';
    $dest = JPath::clean($folder.'/index.html');

    return JFile::copy($src, $dest);
  }

  /**
   * Analysis of an image
   *
   * @param   string  $src_img          Path to source image file
   * @return  array $imginfo[width,height,type,orientation,transparency,animation] on success, false otherwise
   * @since   3.4
   */
  public static function analyseSRCimg($src_img)
  {
    $type = $transparency = $animation = false;
    $info = getimagesize($src_img);
    if($info == false)
    {
      // image file not found
      return false;
    }
    if (key_exists('bits',$info))
    {
      $bits = $info['bits'];
    }
    if (key_exists('channels',$info))
    {
      $channels = $info['channels'];
    }
    $imagetype = array(0=>'UNKNOWN', 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF',
                       5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC',
                       10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF',
                       15=>'WBMP', 16=>'XBM', 17=>'ICO', 18=>'COUNT');

    $type = $imagetype[$info[2]];
    //get the image orientation
    if ($info[0] > $info[1])
    {
      $orientation = 'landscape';
    }
    elseif ($info[0] < $info[1])
    {
      $orientation = 'portrait';
    }
    else
    {
      $orientation = 'square';
    }
    //detect, if image is a special image
    if ($type == 'PNG')
    {
      //detect, if png has transparency
      $pngtype = ord(@file_get_contents($src_img, NULL, NULL, 25, 1));
      if ($pngtype == 4 || $pngtype == 6)
      {
        $transparency = true;
      }
    }
    if ($type == 'GIF')
    {
      //detect, if gif is animated
      $fh = @fopen($src_img, 'rb');
      $count = 0;
      while(!feof($fh) && $count < 2)
      {
        $chunk = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
      }
      fclose($fh);
      //detect, if gif has transparency
      $tmp = imagecreatefromgif($src_img);
      $tmp_trans = imagecolortransparent($tmp);

      if ($count > 1 && $tmp_trans == -1)
      {
        $animation = true;
      }
      elseif ($count > 1 && $tmp_trans >= 0) {
        $animation = true;
        $transparency = true;
      }
      elseif ($count <= 1 && $tmp_trans >= 0) {
        $transparency = true;
      }
    }
    $imginfo = array('width' => $info[0], 'height' => $info[1], 'type' => $type, 'orientation' => $orientation,
                     'transparency' => $transparency, 'animation' => $animation);
    if (key_exists('channels',$info))
    {
      $imginfo['channels'] = $info['channels'];
    }
    if (key_exists('bits',$info))
    {
      $imginfo['bits'] = $info['bits'];
    }
    return $imginfo;
  }

  /**
   * Definition of the resized destination image (dimansions,type,origin)
   *
   * @param   string  $dest_img         Path of destination image file
   * @param   array   $src_imginfo      array with image informations from analysing the source image (JoomFile::analyseSRCimg)
   * @param   int     $settings         Resize to 0=width,1=height,2=max(width,height) or 3=crop
   * @param   int     $new_width        Width to resize
   * @param   int     $new_height       Height to resize
   * @param   int     $cropposition     Only if $settings=3; image section to use for cropping
   * @param   int     $angle            angle to rotate the resized image anticlockwise
   * @return  array $imginfo[width,height,type,offset_x,offset_y] on success, false otherwise
   * @since   3.4
   */
  public static function defineDESTimg($dest_img, $src_imginfo, $settings, $new_width, $new_height, $cropposition, $angle)
  {
    // get the desired image type out of the destination path
    $tmp = explode('.', $dest_img);
    $dest_imgtype = strtolower(end($tmp));
    if ($dest_imgtype == 'jpg' || $dest_imgtype == 'jpeg' || $dest_imgtype == 'jpe' || $dest_imgtype == 'jif' || $dest_imgtype == 'jfif' || $dest_imgtype == 'jfi')
    {
      $dest_imgtype = 'JPG';
    }
    elseif ($dest_imgtype == 'gif')
    {
      $dest_imgtype = 'GIF';
    }
    elseif ($dest_imgtype == 'png')
    {
      $dest_imgtype = 'PNG';
    }
    else
    {
      $dest_imgtype = 'UNKNOWN';
      return false;
    }

    // Height/width
    if($angle == 0 || $angle == 180)
    {
      $srcWidth  = $src_imginfo['width'];
      $srcHeight = $src_imginfo['height'];
    }
    else
    {
      $srcWidth  = $src_imginfo['height'];
      $srcHeight = $src_imginfo['width'];
    }

    $offsetx = null;
    $offsety = null;
    switch($settings)
    {
    // Resize to height ratio (but keep original ratio)
    case 0:
      $ratio = ($srcHeight / $new_height);
      $testwidth = ($srcWidth / $ratio);
      // If new width exceeds setted max. width
      if($testwidth > $new_width)
      {
        $ratio = ($srcWidth / $new_width);
      }
      break;
    // Resize to width ratio (but keep original ratio)
    case 1:
      $ratio = ($srcWidth / $new_width);
      $testheight = ($srcHeight / $ratio);
      // If new height exceeds the setted max. height
      if($testheight > $new_height)
      {
        $ratio = ($srcHeight / $new_height);
      }
      break;
    // Resize to max side lenght - height or width (but keep original ratio)
    case 2:
      if ($srcHeight > $srcWidth)
      {
        $ratio = ($srcHeight / $new_height);
        $testwidth = ($srcWidth / $ratio);
      } else
      {
        $ratio = ($srcWidth / $new_width);
        $testheight = ($srcHeight / $ratio);
      }
      break;
    // Free resizing and cropping
    case 3:
      if($srcWidth < $new_width)
      {
        $new_width = $srcWidth;
      }
      if($srcHeight < $new_height)
      {
        $new_height = $srcHeight;
      }
      // Expand the thumbnail's aspect ratio
      // to fit the width/height of the image
      $ratiowidth = $srcWidth / $new_width;
      $ratioheight = $srcHeight / $new_height;
      if ($ratiowidth < $ratioheight)
      {
        $ratio = $ratiowidth;
      }
      else
      {
        $ratio = $ratioheight;
      }

      // Calculate the offsets for cropping the source image according
      // to thumbposition
      switch($cropposition)
      {
        // Left upper corner
        case 0:
          $offsetx = null;
          $offsety = null;
          break;
        // Right upper corner
        case 1:
          $offsetx = (int)floor(($srcWidth - ($new_width * $ratio)));
          $offsety = 0;
          break;
        // Left lower corner
        case 3:
          $offsetx = 0;
          $offsety = (int)floor(($srcHeight - ($new_height * $ratio)));
          break;
        // Right lower corner
        case 4:
          $offsetx = (int)floor(($srcWidth - ($new_width * $ratio)));
          $offsety = (int)floor(($srcHeight - ($new_height * $ratio)));
          break;
        // Default center
        default:
          $offsetx = (int)floor(($srcWidth - ($new_width * $ratio)) * 0.5);
          $offsety = (int)floor(($srcHeight - ($new_height * $ratio)) * 0.5);
          break;
      }
    }

    if(is_null($offsetx) && is_null($offsety))
    {
      $ratio = max($ratio, 1.0);
      $destWidth  = (int)floor($srcWidth / $ratio);
      $destHeight = (int)floor($srcHeight / $ratio);
    }
    else
    {
      $destWidth = (int)$new_width;
      $destHeight = (int)$new_height;
    }

    $dest_imginfo = array('width' => $destWidth, 'height' => $destHeight, 'type' => $dest_imgtype, 'offset_x' => $offsetx, 'offset_y' => $offsety);
    if (key_exists('channels',$src_imginfo))
    {
      $dest_imginfo['channels'] = $src_imginfo['channels'];
    }
    if (key_exists('bits',$src_imginfo))
    {
      $dest_imginfo['bits'] = $src_imginfo['bits'];
    }
    return $dest_imginfo;
  }

  /**
   * Creates GD image objects from different file types with one frame (Supported: JPG,PNG,GIF)
   *
   * @param   string  $src_file     Path to source file
   * @param   array   $imginfo      array with image informations from analysing the source image (JoomFile::analyseSRCimg)
   * @return  array   $src_frame[0: ["durtion": 0, "image": GDobject]] on success, false otherwise
   * @since   3.5.0
   */
  public static function imageCreateFrom_GD($src_file, $imginfo)
  {
    $src_frame = array(array('duration'=>0));
    switch ($imginfo['type'])
    {
      case 'PNG':
        $src_frame[0]['image'] = imagecreatefrompng($src_file);
        break;

      case 'GIF':
        $src_frame[0]['image'] = imagecreatefromgif($src_file);
        
        break;

      case 'JPG':
        $src_frame[0]['image'] = imagecreatefromjpeg($src_file);
        break;
      
      default:
        return false;
        break;
    }

    // Convert pallete images to true color images
    if (function_exists('imagepalettetotruecolor'))
    {
      //imagepalettetotruecolor($src_frame[0]['image']);
    }

    return $src_frame;
  }

  /**
   * Creates empty GD image object optionally with transparent background
   *
   * @param   int     $width        Width of the image to be created
   * @param   int     $height       Height of the image to be created
   * @param   boolean $transparency Is the image backround transparent instead of black
   * @param   obj     $src_img      GDobject of the source image file
   * @return  obj     empty GDobject on success, false otherwise
   * @since   3.5.0
   */
  public static function imageCreateEmpty_GD($imginfo, $transparency, $src_img)
  {
    if(function_exists('imagecreatetruecolor'))
    {
      $img = imagecreatetruecolor($imginfo['width'], $imginfo['height']);
    }
    else
    {
      $img = imagecreate($imginfo['width'], $imginfo['height']);
    }

    if($transparency)
    {
      switch ($imginfo['type'])
      {
        case 'PNG':
          if(function_exists('imagecolorallocatealpha'))
            {
              imagealphablending($img, false);
              $colorTransparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
              imagefill($img, 0, 0, $colorTransparent);
              imagesavealpha($img, true);
            }
          break;

        case 'GIF':
          $trnprt_indx = imagecolortransparent($src_img);
          $palletsize = imagecolorstotal($src_img);
          if ($trnprt_indx >= 0 && $trnprt_indx < $palletsize)
          {
            $trnprt_color = imagecolorsforindex($src_img, $trnprt_indx);
            $trnprt_indx = imagecolorallocate($img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
            imagefill($img, 0, 0, $trnprt_indx);
            imagecolortransparent($img, $trnprt_indx);
          }
          if ($imginfo['bits'] == 1)
          {
           if(function_exists('imagecolorallocatealpha'))
            {
              $transparentColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
              imagecolortransparent($img, $transparentColor);
              imagefill($img, 0, 0, $transparentColor);
            }
          }
          break;
        
        default:
          break;
      }
    }
    else
    {
      // set black background
      imagefill($img, 0, 0, imagecolorallocate($img, 0, 0, 0));
    }
    return $img;
  }

  /**
   * Output GD image object to file from different file types with one frame (Supported: JPG,PNG,GIF)
   *
   * @param   string  $dest_file    Path to destination file
   * @param   array   $dst_frame    array with one GD object for one frame ; array(array('duration'=>0, 'image'=>GDobject))
   * @param   int     $dest_qual    Quality of the image to be saved (1-100)
   * @param   string  $dest_imgtype Type of the destination image file
   * @return  boolean True on success, false otherwise
   * @since   3.5.0
   */
  public static function imageWriteFrom_GD($dest_file, $dst_frame, $dest_qual, $dest_imgtype)
  {
    switch ($dest_imgtype)
    {
      case 'PNG':
        $png_qual = ($dest_qual - 100) / 11.111111;
        $png_qual = round(abs($png_qual));
        $success = imagepng($dst_frame[0]['image'], $dest_file, $png_qual);
        break;

      case 'GIF':
        $success = imagegif($dst_frame[0]['image'], $dest_file);
        break;
      
      case 'JPG':
        imageinterlace($dst_frame[0]['image'], true);
        $success = imagejpeg($dst_frame[0]['image'], $dest_file, $dest_qual);
        break;

      default:
        $success = false;
    }
    return $success;
  }

  /**
   * Rotate GD image object by specified rotation angle
   *
   * @param   obj     $img          GDobject of the image to rotate
   * @param   array   $imginfo      array with image informations ($imginfo[width,height,type,offset_x,offset_y])
   * @param   int     $angle        angle to rotate the image anticlockwise
   * @return  obj rotated GDobject on success, false otherwise
   * @since   3.5.0
   */
  public static function imageRotate_GD($img, $imginfo, $angle)
  {
    // Set background color of the rotated GDobject
    if($imginfo['transparency'])
    {
      if(function_exists('imagecolorallocatealpha'))
      {
        $backgroundColor = imagecolorallocatealpha($img, 0, 0, 0, 127);
      }
    }
    else
    {
      $backgroundColor = imagecolorallocate($img, 0, 0, 0);
    }

    $new_img = imagerotate($img, $angle, $backgroundColor);

    // keeping transparency
    if($imginfo['transparency'])
    {
      switch ($imginfo['type'])
      {
        case 'PNG':
          imageAlphaBlending($new_img, false);
          imageSaveAlpha($new_img, true);
          break;

        case 'GIF':
          if(function_exists('imagecolorallocatealpha'))
          {
            imagecolortransparent($new_img, imagecolorallocatealpha($new_img, 0, 0, 0, 127));
          }
          break;

        default:
          break;
      }
    }
    return $new_img;
  }


  /**
   * Copy image metadata with GD depending on file type (Supported: JPG,PNG,GIF)
   *
   * @param   string  Path to source file
   * @param   string  Path to destination file
   * @param   string  Type of the source image file
   * @param   string  Type of the destination image file
   * @return  boolean True on success, false otherwise
   * @since   3.5.0
   */
  public static function copyImageMetadata($src_file, $dest_file, $src_imagetype, $dest_imgtype)
  {
    if ($src_imagetype == 'JPG' && $dest_imgtype == 'JPG')
    {
      $success = JoomFile::copyJPGmetadata($src_file,$dest_file);
    }
    elseif ($src_imagetype == 'PNG' && $dest_imgtype == 'PNG') {
      $success = JoomFile::copyPNGmetadata($src_file,$dest_file);
    }
    else
    {
      $success = true;
    }
    return $success;
  }

  /**
   * Copy IPTC and EXIF Data of a jpg from source to destination image
   *
   * function adapted from
   * Author: ebashkoff
   * Website: https://www.php.net/manual/de/function.iptcembed.php
   *
   * @param   string  $srcfile               Path to source file
   * @param   string  $destfile              Path to destination file
   * @return  int number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  public static function copyJPGmetadata($srcfile, $destfile)
  {
      // Function transfers EXIF (APP1) and IPTC (APP13) from $srcfile and adds it to $destfile
      // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
      // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is
      //   most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
      //   of (strlen(<data>) + 2) 

      if (file_exists($srcfile) && file_exists($destfile)) {
          $srcsize = @getimagesize($srcfile, $imageinfo);
          $dstsize = @getimagesize($destfile, $destimageinfo);
          // Check if file is jpg
          if ($srcsize[2] != 2 && $dstsize[2] != 2) return false;
          // Prepare EXIF data bytes from source file
          $exifdata = (is_array($imageinfo) && key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
          if ($exifdata) {
              $exiflength = strlen($exifdata) + 2;
              if ($exiflength > 0xFFFF) return false;
              // Construct EXIF segment
              $exifdata = chr(0xFF) . chr(0xE1) . chr(($exiflength >> 8) & 0xFF) . chr($exiflength & 0xFF) . $exifdata;
          }
          // Prepare IPTC data bytes from source file
          $iptcdata = (is_array($imageinfo) && key_exists("APP13", $imageinfo)) ? $imageinfo['APP13'] : null;
          if ($iptcdata) {
              $iptclength = strlen($iptcdata) + 2;
              if ($iptclength > 0xFFFF) return false;
              // Construct IPTC segment
              $iptcdata = chr(0xFF) . chr(0xED) . chr(($iptclength >> 8) & 0xFF) . chr($iptclength & 0xFF) . $iptcdata;
          }
          $destfilecontent = @file_get_contents($destfile);
          if (!$destfilecontent) return false;
          if (strlen($destfilecontent) > 0) {
              $destfilecontent = substr($destfilecontent, 2);
              $portiontoadd = chr(0xFF) . chr(0xD8);          // Variable accumulates new & original IPTC application segments
              $exifadded = !$exifdata;
              $iptcadded = !$iptcdata;

              while ((JoomFile::get_safe_chunk(substr($destfilecontent, 0, 2)) & 0xFFF0) === 0xFFE0) {
                  $segmentlen = (JoomFile::get_safe_chunk(substr($destfilecontent, 2, 2)) & 0xFFFF);
                  $iptcsegmentnumber = (JoomFile::get_safe_chunk(substr($destfilecontent, 1, 1)) & 0x0F);   // Last 4 bits of second byte is IPTC segment #
                  if ($segmentlen <= 2) return false;
                  $thisexistingsegment = substr($destfilecontent, 0, $segmentlen + 2);
                  if ((1 <= $iptcsegmentnumber) && (!$exifadded)) {
                      $portiontoadd .= $exifdata;
                      $exifadded = true;
                      if (1 === $iptcsegmentnumber) $thisexistingsegment = '';
                  }
                  if ((13 <= $iptcsegmentnumber) && (!$iptcadded)) {
                      $portiontoadd .= $iptcdata;
                      $iptcadded = true;
                      if (13 === $iptcsegmentnumber) $thisexistingsegment = '';
                  }
                  $portiontoadd .= $thisexistingsegment;
                  $destfilecontent = substr($destfilecontent, $segmentlen + 2);
              }
              if (!$exifadded) $portiontoadd .= $exifdata;  //  Add EXIF data if not added already
              if (!$iptcadded) $portiontoadd .= $iptcdata;  //  Add IPTC data if not added already
              $outputfile = fopen($destfile, 'w');
              if ($outputfile) return fwrite($outputfile, $portiontoadd . $destfilecontent); else return false;
          } else {
              return false;
          }
      } else {
          return false;
      }
  }

  /**
   * Get integer value of binary chunk.
   * Source: https://plugins.trac.wordpress.org/browser/image-watermark/tags/1.6.6#image-watermark.php#line:954
   *
   * @param bin $value Binary data
   * @return int
   */
  public static function get_safe_chunk( $value )
  {
    // check for numeric value
    if ( is_numeric( $value ) ) {
      // cast to integer to do bitwise AND operation
      return (int) $value;
    } else
      return 0;
  }

  /**
   * Copy iTXt,tEXt and zTXt chunks of a png from source to destination image
   *
   * read chunks; adapted from
   * Author: Andrew Moore
   * Website: https://stackoverflow.com/questions/2190236/how-can-i-read-png-metadata-from-php
   *
   * write chunks; adapted from
   * Author: leonbloy
   * Website: https://stackoverflow.com/questions/8842387/php-add-itxt-comment-to-a-png-image
   *
   * @param   string  $srcfile               Path to source file
   * @param   string  $destfile              Path to destination file
   * @return  int number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  public static function copyPNGmetadata($srcfile, $destfile)
  {
      if (file_exists($srcfile) && file_exists($destfile))
      {
        $_src_chunks = array ();
        $_fp = fopen($srcfile, 'r');
        $chunks = array ();

        if (!$_fp)
        {
          //unable to open file
          return false;
        }
        // Read the magic bytes and verify
        $header = fread($_fp, 8);

        if ($header != "\x89PNG\x0d\x0a\x1a\x0a")
        {
          //not a valid PNG image
          return false;
        }

        // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
        $chunkHeader = fread($_fp, 8);
        while ($chunkHeader)
        {
          // Extract length and type from binary data
          $chunk = @unpack('Nsize/a4type', $chunkHeader);

          // Store position into internal array
          if (is_null($_src_chunks[$chunk['type']]))
              $_src_chunks[$chunk['type']] = array ();
          $_src_chunks[$chunk['type']][] = array (
              'offset' => ftell($_fp),
              'size' => $chunk['size']
          );

          // Skip to next chunk (over body and CRC)
          fseek($_fp, $chunk['size'] + 4, SEEK_CUR);

          // Read next chunk header
          $chunkHeader = fread($_fp, 8);
        }
        //Read iTXt chunk
        if (isset($_src_chunks['iTXt']))
        {
          foreach ($_src_chunks['iTXt'] as $chunk)
          {
            if ($chunk['size'] > 0)
            {
                fseek($_fp, $chunk['offset'], SEEK_SET);
                $chunks['iTXt'] = fread($_fp, $chunk['size']);
            }
          }
        }
        //Read tEXt chunk
        if (isset($_src_chunks['tEXt']))
        {
          foreach ($_src_chunks['tEXt'] as $chunk) {
            if ($chunk['size'] > 0) {
                fseek($_fp, $chunk['offset'], SEEK_SET);
                $chunks['tEXt'] = fread($_fp, $chunk['size']);
            }
          }
        }
        //Read zTXt chunk
        if (isset($_src_chunks['zTXt']))
        {
          foreach ($_src_chunks['zTXt'] as $chunk) {
            if ($chunk['size'] > 0) {
                fseek($_fp, $chunk['offset'], SEEK_SET);
                $chunks['zTXt'] = fread($_fp, $chunk['size']);
            }
          }
        }

        //write chucks to destination image
        $_dfp = file_get_contents($destfile);
        $data = '';
        if (isset($chunks['iTXt']))
        {
          $data .= pack("N",strlen($chunks['iTXt'])) . 'iTXt' . $chunks['iTXt'] . pack("N", crc32('iTXt' . $chunks['iTXt']));
        }
        if (isset($chunks['tEXt']))
        {
          $data .= pack("N",strlen($chunks['tEXt'])) . 'tEXt' . $chunks['tEXt'] . pack("N", crc32('tEXt' . $chunks['tEXt']));
        }
        if (isset($chunks['zTXt']))
        {
          $data .= pack("N",strlen($chunks['zTXt'])) . 'zTXt' . $chunks['zTXt'] . pack("N", crc32('zTXt' . $chunks['zTXt']));
        }
        $len = strlen($_dfp);
        $png = substr($_dfp,0,$len-12) . $data . substr($_dfp,$len-12,12);
        return file_put_contents($destfile, $png);
      }
      else
      {
        //files dont exist
        return false;
      }
  }
}