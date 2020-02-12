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
   * Supported image-types: JPG ,PNG, GIF
   *
   * Animated gif support according to ImageWorkshop
   * 'Manage animated GIF with ImageWorkshop'
   * Author: Clément Guillemain
   * Website: https://phpimageworkshop.com/tutorial/5/manage-animated-gif-with-imageworkshop.html
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
    $config = JoomConfig::getInstance();

    // Ensure that the paths are valid and clean
    $src_file  = JPath::clean($src_file);
    $dest_file = JPath::clean($dest_file);

    // Analysis of the source image, if image is valid
    if(!($src_imginfo = JoomFile::analyseSRCimg($src_file)))
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

    // Conditions where no resize is needed
    if(   ($src_imginfo['width'] <= $new_width && $src_imginfo['height'] <= $new_width && ($angle == 0 || $angle == 180 || $angle == -180))
        ||
          ($src_imginfo['height'] <= $new_width && $src_imginfo['width'] <= $new_width && ($angle == 270 || $angle == -270 || $angle == 90 || $angle == -90))
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

    // Get informations about type, dimension and origin of resized image
    if(!($dest_imginfo = JoomFile::getResizeInfo($dest_file, $src_imginfo, $settings, $new_width, $new_height, $cropposition, $angle)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';

      return false;
    }

    // Create debugoutput
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
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED');

          return false;
        }

        // Create GD-Object from file
        $src_frames = JoomFile::imageCreateFrom_GD($src_file, $src_imginfo);

        // Create empty GD-Object for the resized image
        $dst_frames = array(array('duration' => 0));
        $dst_frames[0]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[0]['image']);
        if(in_array(false, $src_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

          return false;
        }

        // Rotate image, if needed
        if($angle > 0)
        {
          foreach($src_frames as $key => $frame)
          {
            $src_frames[$key]['image'] = JoomFile::imageRotate_GD($src_frames[$key]['image'], $src_imginfo, $angle);
            $src_imginfo['width'] = imagesx($src_frames[$key]['image']);
            $src_imginfo['height'] = imagesy($src_frames[$key]['image']);
          }
        }

        // Resizing with GD1
        foreach($src_frames as $key => $frame)
        {
          if (!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
          {
            imagecopyresized($dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, $dest_imginfo['offset_x'], $dest_imginfo['offset_y'],
                             $dest_imginfo['width'], $dest_imginfo['height'], $dest_imginfo['src']['width'], $dest_imginfo['src']['height']);
          }
          else
          {
            imagecopyresized($dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, 0, 0,
                             $dest_imginfo['width'], $dest_imginfo['height'], $dest_imginfo['src']['width'], $dest_imginfo['src']['height']);
          }

          // Write resized image to file
          $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
        }

        // Copy metadata if needed
        if($metadata)
        {
          $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
          if(!$meta_success)
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

            return false;
          }      
        }

        // Workaround for servers with wwwrun problem
        if(!$success)
        {
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);
          // Write resized image to file
          $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);

          // Copy metadata if needed
          if($metadata)
          {
            $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

              return false;
            }      
          }

          JoomFile::chmod($dir, '0755', true);
        }

        // destroy GD-Objects
        foreach($src_frames as $key => $frame)
        {
          imagedestroy($src_frames[$key]['image']);
          imagedestroy($dst_frames[$key]['image']);
        }

        break;
      case 'gd2':
        $debugoutput .= 'GD2...<br/>';

        if(!function_exists('imagecreatefromjpeg'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED');

          return false;
        }

        if(!function_exists('imagecreatetruecolor'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_NO_TRUECOLOR');

          return false;
        }

        // Create empty image of specified size
        $dst_frames = array();
        $dst_frames = array(array());

        if ($anim && $src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          // Create GD-Objects from gif-file
          JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifFrameExtractor.php');
          $gfe = new GifFrameExtractor();
          $src_frames = $gfe->extract($src_file);

          foreach($src_frames as $key => $frame)
          {
            // create empty GD-Objects for the resized frames
            $dst_frames[$key]['duration'] = $src_frames[$key]['duration'];
            $dst_frames[$key]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[$key]['image']);
          }

        }
        else
        {
          // Normal image (image with one frame)
          // Create GD-Object from file
          $src_frames = JoomFile::imageCreateFrom_GD($src_file, $src_imginfo);

          // Create empty GD-Object for the resized image
          $dst_frames[0]['duration'] = 0;
          $dst_frames[0]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[0]['image']);
        }

        if (in_array(false, $src_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

          return false;
        }

        // Rotate image, if needed
        if($angle > 0)
        {
          foreach($src_frames as $key => $frame)
          {
            $src_frames[$key]['image'] = JoomFile::imageRotate_GD($src_frames[$key]['image'], $src_imginfo, $angle);
            $src_imginfo['width'] = imagesx($src_frames[$key]['image']);
            $src_imginfo['height'] = imagesy($src_frames[$key]['image']);
          }
        }

        // Resizing with GD2
        foreach($src_frames as $key => $frame)
        {
          if($config->jg_fastgd2thumbcreation == 0)
          // Use normal GD2 for resizing
          {
            if(!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
            {
              imagecopyresampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, $dest_imginfo['offset_x'], $dest_imginfo['offset_y'],
                                  $dest_imginfo['width'], $dest_imginfo['height'], $dest_imginfo['src']['width'], $dest_imginfo['src']['height'] );
            }
            else
            {
              imagecopyresampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, 0, 0,
                                  $dest_imginfo['width'], $dest_imginfo['height'], $dest_imginfo['src']['width'], $dest_imginfo['src']['height'] );
            }
          }
          else
          // Use fast GD2 for resizing
          {
            if(!is_null($dest_imginfo['offset_x']) && !is_null($dest_imginfo['offset_y']))
            {
              $dst_frames[$key]['image'] = JoomFile::fastImageCopyResampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0,
                                                                             $dest_imginfo['offset_x'], $dest_imginfo['offset_y'],
                                                                             $dest_imginfo['width'], $dest_imginfo['height'],
                                                                             $dest_imginfo['src']['width'], $dest_imginfo['src']['height'], 3,$src_imginfo );
            }
            else
            {
              $dst_frames[$key]['image'] = JoomFile::fastImageCopyResampled( $dst_frames[$key]['image'], $src_frames[$key]['image'], 0, 0, 0, 0,
                                                                             $dest_imginfo['width'], $dest_imginfo['height'],
                                                                             $dest_imginfo['src']['width'], $dest_imginfo['src']['height'], 3,$src_imginfo );
            }
          }
        }

        // Write resized image to file
        if($anim && $src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
          $gc = new GifCreator();
          $gc->create($dst_frames, 0);
          $success = file_put_contents($dest_file, $gc->getGif());
        }
        else
        {
          // Normal image (image with one frame)
          $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
        }
        
        // Copy metadata if needed
        if($metadata)
        {
          $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);
          if(!$meta_success)
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

            return false;
          }
        }

        // Workaround for servers with wwwrun problem
        if(!$success)
        {
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);

          // Write resized image to file
          if($anim && $src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
          {
            // Animated GIF image (image with more than one frame)
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
            $gc = new GifCreator();
            $gc->create($dst_frames, 0);
            $success = file_put_contents($dest_file, $gc->getGif());
          }
          else
          {
            // Normal image (image with one frame)
            $success = JoomFile::imageWriteFrom_GD($dest_file,$dst_frames,$dest_qual,$dest_imginfo['type']);
          }

          // Copy metadata if needed
          if($metadata)
          {
            $meta_success = JoomFile::copyImageMetadata($src_file, $dest_file, $src_imginfo['type'], $dest_imginfo['type']);

            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

              return false;
            }
          }

          JoomFile::chmod($dir, '0755', true);
        }

        // destroy GD-Objects
        foreach($src_frames as $key => $frame)
        {
          imagedestroy($src_frames[$key]['image']);
          imagedestroy($dst_frames[$key]['image']);
        }

        break;
      case 'im':
        $debugoutput .= 'ImageMagick...<br/>';
        $disabled_functions = explode(',', ini_get('disabled_functions'));

        // Check, if exec command is available
        foreach($disabled_functions as $disabled_function)
        {
          if(trim($disabled_function) == 'exec')
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_EXEC_DISABLED').'<br />';

            return false;
          }
        }

        // Check availability and version of ImageMagick
        @exec(trim($config->jg_impath).'convert -version', $output_convert);
        @exec(trim($config->jg_impath).'magick -version', $output_magick);

        if($output_convert)
        {
          $convert_path = trim($config->jg_impath).'convert';
        }
        else
        {
          if($output_magick)
          {
            $convert_path = trim($config->jg_impath).'magick convert';
          }
          else
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_NOTFOUND').'<br />';

            return false;
          }
        }

        // Create imagick command
        $commands = '';

        if ($src_imginfo['animation']  && !$anim)
        {
          // if resizing an animation but not preserving the animation, consider only first frame
          $src_file = $src_file.'[0]';
        }
        else
        {
          if ($src_imginfo['animation']  && $anim && $src_imginfo['type'] == 'GIF')
          {
            // if resizing an animation, use coalesce for better results
            $commands .= ' -coalesce';
          }
        }
        
        // Rotate image, if needed (use auto-orient command)
        if($angle > 0)
        {
          $commands .= ' -auto-orient';
        }

        // Delete all metadata, if needed
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
          $commands .= ' -crop "'.$dest_imginfo['src']['width'].'x'.$dest_imginfo['src']['height'].'+'.$dest_imginfo['offset_x'].'+'.$dest_imginfo['offset_y'].'" +repage';
        }

        // Assembling the imagick command for resizing
        $commands  .= ' -resize "'.$dest_imginfo['width'].'x'.$dest_imginfo['height'].'" -quality "'.$dest_qual.'" -unsharp "3.5x1.2+1.0+0.10"';
        
        // Assembling the shell code for the resize with imagick
        $convert    = $convert_path.' '.$commands.' "'.$src_file.'" "'.$dest_file.'"';

        $return_var = null;
        $dummy      = null;

        // execute the resize
        @exec($convert, $dummy, $return_var);

        // Workaround for servers with wwwrun problem
        if($return_var != 0)
        {
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);

          // execute the resize
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
   * Rotate image with functions from gd/gd2/imagemagick
   * Supported image-types: JPG ,PNG, GIF
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
   * @since   3.4.0
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
                          'offset_x' => 0, 'offset_y' => 0, 'src' => array('width' => $src_imginfo['width'], 'height' => $src_imginfo['height']));

    if(key_exists('channels',$src_imginfo))
    {
      $dest_imginfo['channels'] = $src_imginfo['channels'];
    }

    if(key_exists('bits',$src_imginfo))
    {
      $dest_imginfo['bits'] = $src_imginfo['bits'];
    }

    // Method for creation of the rotated image
    switch($method)
    {
      case 'gd1':
        // 'break' intentionally omitted
      case 'gd2':

        if(!function_exists('imagecreatefromjpeg'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED').'<br />';

          return false;
        }

        if($src_imginfo['animation'])
        {
          // Animated GIF image (image with more than one frame)
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_NO_ROTATION').'<br />';

          return false;
        }
        else
        {
          // Normal image (image with one frame)
          // Create GD-Object from file
          $src_frames = JoomFile::imageCreateFrom_GD($src, $src_imginfo);

          // Create empty GD-Object for the resized image
          $dst_frames = array(array());
          $dst_frames[0]['image'] = JoomFile::imageCreateEmpty_GD($dest_imginfo, $src_imginfo['transparency'], $src_frames[0]['image']);
        }

        if(!$src_frames)
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

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

        // Workaround for servers with wwwrun problem
        if(!$success)
        {  
          $dir = dirname($src);
          JoomFile::chmod($dir, '0777', true);

          // Rename source file so it dont gets overwritten 
          rename($src,$src_orig);

          // Write rotated file
          JoomFile::imageWriteFrom_GD($src,$dst_frames,$dest_qual,$dest_imgtype);

          // Copy metadata if needed
          if($metadata)
          {
            $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);

            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

              return false;
            }

          }

          unlink($src_orig);
          JoomFile::chmod($dir, '0755', true);
        }
        else
        {
          // Copy metadata if needed
          if($metadata)
          {
            $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);

            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

              return false;
            }

          }
        }

        // destroy GD-Objects
        imagedestroy($src_frames[0]['image']);
        imagedestroy($dst_frames[0]['image']);
        
        unlink($src_orig);
        break;
      case 'im':
        $disabled_functions = explode(',', ini_get('disabled_functions'));

        // Check, if exec command is available
        foreach($disabled_functions as $disabled_function)
        {
          if(trim($disabled_function) == 'exec')
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_EXEC_DISABLED').'<br />';

            return false;
          }
        }

        $config = JoomConfig::getInstance();

        // Check availability and version of ImageMagick
        @exec(trim($config->jg_impath).'convert -version', $output_convert);
        @exec(trim($config->jg_impath).'magick -version', $output_magick);

        if ($output_convert)
        {
          $convert_path = trim($config->jg_impath).'convert';
        }
        else
          if($output_magick)
          {
            $convert_path = trim($config->jg_impath).'magick convert';
          }        
        else
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_NOTFOUND').'<br />';

          return false;
        }

        // Create imagick command
        $commands = '';

        if($src_imginfo['animation'] && $src_imginfo['type'] == 'GIF')
        {
          // if resizing an animation, use coalesce for better results
          $commands .= ' -coalesce';
        }

        if($auto_orient)
        {
          // Rotate image with auto-orien if needed
          $commands .= '-auto-orient';
        }
        else
        {
          // Else rotate by angle
          $commands .= '-rotate "-'.$angle.'"';
        }

        // Rotation quality
        $commands  .= ' -quality '.$dest_qual;

        if($src_imginfo['type'] == 'PNG')
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

        // Assembling the shell code for the rotation with imagick 
        $convert    = $convert_path.' '.$commands.' "'.$src_orig.'" "'.$src.'"';

        $return_var = null;
        $dummy      = null;

        // execute the rotation
        @exec($convert, $dummy, $return_var);

        // Workaround for servers with wwwrun problem
        if($return_var != 0)
        {
          $dir = dirname($src);
          JoomFile::chmod($dir, '0777', true);

          // execute the rotation
          @exec($convert, $dummy, $return_var);

          // Preserve metadata of png files with php functions
          if($src_imginfo[2] == 'PNG')
          {
            // copy metadata
            $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);

            if(!$meta_success)
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
          // Preserve metadata of png files with php functions
          if($src_imginfo['type'] == 'PNG')
          {
            // copy metadata
            $meta_success = JoomFile::copyImageMetadata($src_orig, $src, $src_imginfo['type'], $dest_imginfo['type']);

            if(!$meta_success)
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

    // Check that the rotated image is valid
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

    // Check, if it is a special image (transparency or animation)
    $special = false;
    if($imginfo['animation'] || $imginfo['transparency'])
    {
      $special = true;
    }

    // Perform the resize
    if($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h) && !$special)
    {
      // Fast resizing
      $temp = imagecreatetruecolor($dst_w * $quality + 1, $dst_h * $quality + 1);
      imagecopyresized($temp, $src_image, 0, 0, $src_x, $src_y, $dst_w * $quality + 1,$dst_h * $quality + 1, $src_w, $src_h);
      imagecopyresampled($dst_image, $temp, $dst_x, $dst_y, 0, 0, $dst_w,$dst_h, $dst_w * $quality, $dst_h * $quality);
      imagedestroy($temp);
    }
    else
    {
      // Normal resizing
      imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w,$dst_h, $src_w, $src_h);
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
   * Structure of the array $imginfo:
   * array('width' => int,'height' => int,'type' => str,'orientation' => str, 'transparency' => boolean, 'animation' => boolean,
   *       'channels' => int,'bits' => int)
   *
   * @param   string    $src_img        Path to source image file
   * @return  array     $imginfo[] on success, false otherwise
   * @since   3.5.0
   */
  public static function analyseSRCimg($src_img)
  {
    $type = $transparency = $animation = false;
    $info = getimagesize($src_img);

    // Check, if image exists
    if($info == false)
    {

      return false;
    }

    // Extract bits and channels from info
    if(key_exists('bits',$info))
    {
      $bits = $info['bits'];
    }

    if(key_exists('channels',$info))
    {
      $channels = $info['channels'];
    }

    // Decrypt the imagetype
    $imagetype = array(0=>'UNKNOWN', 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF',
                       5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC',
                       10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF',
                       15=>'WBMP', 16=>'XBM', 17=>'ICO', 18=>'COUNT');

    $type = $imagetype[$info[2]];

    // Get the image orientation
    if($info[0] > $info[1])
    {
      $orientation = 'landscape';
    }
    else
    {
      if($info[0] < $info[1])
      {
        $orientation = 'portrait';
      }
      else
      {
        $orientation = 'square';
      }
    }    

    // Detect, if image is a special image
    if($type == 'PNG')
    {
      // Detect, if png has transparency
      $pngtype = ord(@file_get_contents($src_img, NULL, NULL, 25, 1));

      if($pngtype == 4 || $pngtype == 6)
      {
        $transparency = true;
      }
    }

    if($type == 'GIF')
    {
      // Detect, if gif is animated
      $fh = @fopen($src_img, 'rb');
      $count = 0;

      while(!feof($fh) && $count < 2)
      {
        $chunk = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
      }

      fclose($fh);

      // Detect, if gif has transparency
      $tmp = imagecreatefromgif($src_img);
      $tmp_trans = imagecolortransparent($tmp);

      if($count > 1 && $tmp_trans == -1)
      {
        $animation = true;
      }
      else
      {
        if($count > 1 && $tmp_trans >= 0)
        {
          $animation = true;
          $transparency = true;
        }
        else
        {
          if($count <= 1 && $tmp_trans >= 0)
          {
            $transparency = true;
          }
        }
      }
    }

    // Assemble the imginfo array
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
   * Collect informations for the resize (informations: dimansions,type,origin)
   *
   * Cropping function adapted from
   * 'Resize Image with Different Aspect Ratio'
   * Author: Nash
   * Website: http://nashruddin.com/Resize_Image_to_Different_Aspect_Ratio_on_the_fly
   *
   * Structure of the array $imginfo:
   * array('width' => int,'height' => int,'type' => str,'offset_x' => int, 'offset_y' => int, 'channels' => int,'bits' => int,
   *       'src' => array('width' => int,'height' => int))
   *
   * @param   string  $dest_img         Path of destination image file
   * @param   array   $src_imginfo      array with image informations from analysing the source image (JoomFile::analyseSRCimg)
   * @param   int     $settings         Resize to 0=width,1=height,2=max(width,height) or 3=crop
   * @param   int     $new_width        Width to resize
   * @param   int     $new_height       Height to resize
   * @param   int     $cropposition     Only if $settings=3; image section to use for cropping
   * @param   int     $angle            angle to rotate the resized image anticlockwise
   * @return  array   $imginfo[] on success, false otherwise
   * @since   3.5.0
   */
  public static function getResizeInfo($dest_img, $src_imginfo, $settings, $new_width, $new_height, $cropposition, $angle)
  {
    // Get the desired image type out of the destination path
    $tmp = explode('.', $dest_img);
    $dest_imgtype = strtolower(end($tmp));

    if($dest_imgtype == 'jpg' || $dest_imgtype == 'jpeg' || $dest_imgtype == 'jpe' || $dest_imgtype == 'jif' || $dest_imgtype == 'jfif' || $dest_imgtype == 'jfi')
    {
      $dest_imgtype = 'JPG';
    }
    else
    {
      if($dest_imgtype == 'gif')
      {
        $dest_imgtype = 'GIF';
      }
      else
      {
        if ($dest_imgtype == 'png')
        {
          $dest_imgtype = 'PNG';
        }
        else
        {
          $dest_imgtype = 'UNKNOWN';

          return false;
        }
      }
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
    case 0:
      // Resize to height ratio (but keep original ratio)
      $ratio = ($srcHeight / $new_height);
      $testwidth = ($srcWidth / $ratio);

      // If new width exceeds setted max. width
      if($testwidth > $new_width)
      {
        $ratio = ($srcWidth / $new_width);
      }

      break;
    case 1:
      // Resize to width ratio (but keep original ratio)
      $ratio = ($srcWidth / $new_width);
      $testheight = ($srcHeight / $ratio);

      // If new height exceeds the setted max. height
      if($testheight > $new_height)
      {
        $ratio = ($srcHeight / $new_height);
      }

      break;
    case 2:
      // Resize to max side lenght - height or width (but keep original ratio)
      if($srcHeight > $srcWidth)
      {
        $ratio = ($srcHeight / $new_height);
        $testwidth = ($srcWidth / $ratio);
      }
      else
      {
        $ratio = ($srcWidth / $new_width);
        $testheight = ($srcHeight / $ratio);
      }
      break;
    case 3:
      // Free resizing and cropping
      if($srcWidth < $new_width)
      {
        $new_width = $srcWidth;
      }

      if($srcHeight < $new_height)
      {
        $new_height = $srcHeight;
      }

      // Expand the thumbnail's aspect ratio to fit the width/height of the image
      $ratiowidth = $srcWidth / $new_width;
      $ratioheight = $srcHeight / $new_height;

      if($ratiowidth < $ratioheight)
      {
        $ratio = $ratiowidth;
      }
      else
      {
        $ratio = $ratioheight;
      }

      // Calculate the offsets for cropping the source image according to thumbposition
      switch($cropposition)
      {
        case 0:
          // Left upper corner
          $offsetx = null;
          $offsety = null;
          break;
        case 1:
          // Right upper corner
          $offsetx = (int)floor(($srcWidth - ($new_width * $ratio)));
          $offsety = 0;
          break;
        case 3:
          // Left lower corner
          $offsetx = 0;
          $offsety = (int)floor(($srcHeight - ($new_height * $ratio)));
          break;
        case 4:
          // Right lower corner
          $offsetx = (int)floor(($srcWidth - ($new_width * $ratio)));
          $offsety = (int)floor(($srcHeight - ($new_height * $ratio)));
          break;
        default:
          // Default center
          $offsetx = (int)floor(($srcWidth - ($new_width * $ratio)) * 0.5);
          $offsety = (int)floor(($srcHeight - ($new_height * $ratio)) * 0.5);
          break;
      }
    }

    // Calculate widths and heights necessary for resize and bring them to integer values
    if(is_null($offsetx) && is_null($offsety))
    {
      $ratio = max($ratio, 1.0);
      $destWidth  = (int)floor($srcWidth / $ratio);
      $destHeight = (int)floor($srcHeight / $ratio);
      $srcWidth  = (int)$srcWidth;
      $srcHeight = (int)$srcHeight;
    }
    else
    {
      $destWidth = (int)$new_width;
      $destHeight = (int)$new_height;
      $srcWidth  = (int)($destWidth * $ratio);
      $srcHeight = (int)($destHeight * $ratio);
    }

    // Assemble imginfo array
    $dest_imginfo = array('width' => $destWidth, 'height' => $destHeight, 'type' => $dest_imgtype, 'offset_x' => $offsetx, 'offset_y' => $offsety,
                          'src' => array('width' => $srcWidth, 'height' => $srcHeight));

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
   * Creates GD image objects from different file types with one frame
   * Supported: JPG, PNG, GIF
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
    if (function_exists('imagepalettetotruecolor') && $imginfo['type'] != 'GIF')
    {
      imagepalettetotruecolor($src_frame[0]['image']);
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
    // Create empty GD-Object
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
      // Set transparent backgraound
      switch ($imginfo['type'])
      {
        case 'GIF':
          if(function_exists('imagecolorallocatealpha'))
          {
            $trnprt_color = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $trnprt_color);
            imagecolortransparent($img, $trnprt_color);            
          }
          else
          {
            $trnprt_indx = imagecolortransparent($src_img);
            $palletsize = imagecolorstotal($src_img);

            if($trnprt_indx >= 0 && $trnprt_indx < $palletsize)
            {
              $trnprt_color = imagecolorsforindex($src_img, $trnprt_indx);
              $trnprt_indx = imagecolorallocate($img, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
              imagefill($img, 0, 0, $trnprt_indx);
              imagecolortransparent($img, $trnprt_indx);
            }
          }

        break;
        case 'PNG':
          if(function_exists('imagecolorallocatealpha'))
          {
            imagealphablending($img, false);
            $trnprt_color = imagecolorallocatealpha($img, 0, 0, 0, 127);
            imagefill($img, 0, 0, $trnprt_color);
            //imagesavealpha($img, true);
          }

          break;
          default:

            return fals;
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
   * Output GD image object to file from different file types with one frame
   * Supported: JPG, PNG, GIF
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
        // Calculate png quality, since it should be between 1 and 9
        $png_qual = ($dest_qual - 100) / 11.111111;
        $png_qual = round(abs($png_qual));

        // Save transparency
        imagealphablending($dst_frame[0]['image'], false);
        imagesavealpha($dst_frame[0]['image'], true);

        // Write file
        $success = imagepng($dst_frame[0]['image'], $dest_file, $png_qual);
        break;
      case 'GIF':
        // Write file
        $success = imagegif($dst_frame[0]['image'], $dest_file);
        break;      
      case 'JPG':
        // Enable progressive image creation
        if(function_exists('imageistruecolor'))
        {
          if (imageistruecolor($dst_frame[0]['image']))
          {
            imageinterlace($dst_frame[0]['image'], true);
          }
        }

        // Write file
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

    // Rotate image
    $new_img = imagerotate($img, $angle, $backgroundColor);

    // Keeping transparency
    if($imginfo['transparency'])
    {
      switch ($imginfo['type'])
      {
        case 'PNG':
          // Special threatment for png files
          if(function_exists('imagealphablending'))
          {
            imagealphablending($new_img, false);
            imagesavealpha($new_img, true);
          }
          break;
        default:
          if(function_exists('imagecolorallocatealpha'))
          {
            imagecolortransparent($new_img, imagecolorallocatealpha($new_img, 0, 0, 0, 127));
          }
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
    if($src_imagetype == 'JPG' && $dest_imgtype == 'JPG')
    {
      $success = JoomFile::copyJPGmetadata($src_file,$dest_file);
    }
    else
    {
      if ($src_imagetype == 'PNG' && $dest_imgtype == 'PNG')
      {
        $success = JoomFile::copyPNGmetadata($src_file,$dest_file);
      }
      else
      {
        // In all other cases dont copy metadata
        $success = true;
      }
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
   * @param   string  $srcfile         Path to source file
   * @param   string  $destfile        Path to destination file
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  public static function copyJPGmetadata($srcfile, $destfile)
  {
    // Function transfers EXIF (APP1) and IPTC (APP13) from $srcfile and adds it to $destfile
    // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
    // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is
    //   most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
    //   of (strlen(<data>) + 2) 

    if(file_exists($srcfile) && file_exists($destfile))
    {
        $srcsize = @getimagesize($srcfile, $imageinfo);
        $dstsize = @getimagesize($destfile, $destimageinfo);

        // Check if file is jpg
        if($srcsize[2] != 2 && $dstsize[2] != 2) return false;

        // Prepare EXIF data bytes from source file
        $exifdata = (is_array($imageinfo) && key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
        if($exifdata)
        {
          $exiflength = strlen($exifdata) + 2;
          if($exiflength > 0xFFFF) return false;
          // Construct EXIF segment
          $exifdata = chr(0xFF) . chr(0xE1) . chr(($exiflength >> 8) & 0xFF) . chr($exiflength & 0xFF) . $exifdata;
        }

        // Prepare IPTC data bytes from source file
        $iptcdata = (is_array($imageinfo) && key_exists("APP13", $imageinfo)) ? $imageinfo['APP13'] : null;
        if($iptcdata)
        {
          $iptclength = strlen($iptcdata) + 2;
          if ($iptclength > 0xFFFF) return false;
          // Construct IPTC segment
          $iptcdata = chr(0xFF) . chr(0xED) . chr(($iptclength >> 8) & 0xFF) . chr($iptclength & 0xFF) . $iptcdata;
        }

        // Check destination File
        $destfilecontent = @file_get_contents($destfile);
        if(!$destfilecontent) return false;
        if(strlen($destfilecontent) > 0)
        {
          $destfilecontent = substr($destfilecontent, 2);
          $portiontoadd = chr(0xFF) . chr(0xD8);          // Variable accumulates new & original IPTC application segments
          $exifadded = !$exifdata;
          $iptcadded = !$iptcdata;

          while((JoomFile::get_safe_chunk(substr($destfilecontent, 0, 2)) & 0xFFF0) === 0xFFE0)
          {
            $segmentlen = (JoomFile::get_safe_chunk(substr($destfilecontent, 2, 2)) & 0xFFFF);
            $iptcsegmentnumber = (JoomFile::get_safe_chunk(substr($destfilecontent, 1, 1)) & 0x0F);   // Last 4 bits of second byte is IPTC segment #
            if($segmentlen <= 2) return false;
            $thisexistingsegment = substr($destfilecontent, 0, $segmentlen + 2);
            
            if((1 <= $iptcsegmentnumber) && (!$exifadded))
            {
              $portiontoadd .= $exifdata;
              $exifadded = true;
              if (1 === $iptcsegmentnumber) $thisexistingsegment = '';
            }

            if((13 <= $iptcsegmentnumber) && (!$iptcadded))
            {
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
        }
        else
        {
          // Destination file dont exist
          return false;
        }

    }
    else
    {
      // Source file dont exist
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
    // Check for numeric value
    if(is_numeric( $value))
    {
      // Cast to integer to do bitwise AND operation
      return (int) $value;
    }
    else
    {
      return 0;
    }
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
      if(file_exists($srcfile) && file_exists($destfile))
      {
        $_src_chunks = array ();
        $_fp = fopen($srcfile, 'r');
        $chunks = array ();

        if(!$_fp)
        {
          // Unable to open file
          return false;
        }

        // Read the magic bytes and verify
        $header = fread($_fp, 8);

        if($header != "\x89PNG\x0d\x0a\x1a\x0a")
        {
          // Not a valid PNG image
          return false;
        }

        // Loop through the chunks. Byte 0-3 is length, Byte 4-7 is type
        $chunkHeader = fread($_fp, 8);
        while($chunkHeader)
        {
          // Extract length and type from binary data
          $chunk = @unpack('Nsize/a4type', $chunkHeader);

          // Store position into internal array
          if(!key_exists($chunk['type'], $_src_chunks))
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

        // Read iTXt chunk
        if(isset($_src_chunks['iTXt']))
        {
          foreach($_src_chunks['iTXt'] as $chunk)
          {
            if($chunk['size'] > 0)
            {
                fseek($_fp, $chunk['offset'], SEEK_SET);
                $chunks['iTXt'] = fread($_fp, $chunk['size']);
            }
          }
        }

        // Read tEXt chunk
        if(isset($_src_chunks['tEXt']))
        {
          foreach($_src_chunks['tEXt'] as $chunk)
          {
            if ($chunk['size'] > 0) {
                fseek($_fp, $chunk['offset'], SEEK_SET);
                $chunks['tEXt'] = fread($_fp, $chunk['size']);
            }
          }
        }

        // Read zTXt chunk
        if(isset($_src_chunks['zTXt']))
        {
          foreach($_src_chunks['zTXt'] as $chunk)
          {
            if($chunk['size'] > 0)
            {
                fseek($_fp, $chunk['offset'], SEEK_SET);
                $chunks['zTXt'] = fread($_fp, $chunk['size']);
            }
          }
        }

        // Write chucks to destination image
        $_dfp = file_get_contents($destfile);
        $data = '';

        if(isset($chunks['iTXt']))
        {
          $data .= pack("N",strlen($chunks['iTXt'])) . 'iTXt' . $chunks['iTXt'] . pack("N", crc32('iTXt' . $chunks['iTXt']));
        }

        if(isset($chunks['tEXt']))
        {
          $data .= pack("N",strlen($chunks['tEXt'])) . 'tEXt' . $chunks['tEXt'] . pack("N", crc32('tEXt' . $chunks['tEXt']));
        }

        if(isset($chunks['zTXt']))
        {
          $data .= pack("N",strlen($chunks['zTXt'])) . 'zTXt' . $chunks['zTXt'] . pack("N", crc32('zTXt' . $chunks['zTXt']));
        }

        $len = strlen($_dfp);
        $png = substr($_dfp,0,$len-12) . $data . substr($_dfp,$len-12,12);

        return file_put_contents($destfile, $png);
      }
      else
      {
        // File dont exist
        return false;
      }
  }
}