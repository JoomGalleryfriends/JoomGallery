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
   * @return  boolean True on success, false otherwise
   * @since   1.0.0
   */
  public static function resizeImage(&$debugoutput, $src_file, $dest_file, $settings,
                                     $new_width, $new_height, $method, $dest_qual, $cropposition = false, $angle = 0, $metadata = false)
  {

    // animated gifs: https://github.com/Yuriy-Khomenko/GIF_eXG

    $config = JoomConfig::getInstance();

    // Ensure that the paths are valid and clean
    $src_file  = JPath::clean($src_file);
    $dest_file = JPath::clean($dest_file);

    // Check that the source image is valid
    $imginfo = getimagesize($src_file, $src_metainfo);
    if(!$imginfo)
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_FILE_NOT_FOUND').'<br />';
      return false;
    }

    // GD can only handle JPG & PNG images
    if(    $imginfo[2] != IMAGETYPE_JPEG
       &&  $imginfo[2] != IMAGETYPE_PNG
       &&  $imginfo[2] != IMAGETYPE_GIF
       &&  !( $imginfo[2] == IMAGETYPE_BMP && function_exists('imagebmp') )
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';
      return false;
    }

    $imagetype = array(0=>'UNKNOWN', 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF',
                       5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC',
                       10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF',
                       15=>'WBMP', 16=>'XBM', 17=>'ICO', 18=>'COUNT');

    $imginfo[2] = $imagetype[$imginfo[2]];

    // get the desired image type out of the destination path
    $dest_imgtype = strtolower(end(explode('.', $dest_file)));
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
    elseif ($dest_imgtype == 'bmp' || $dest_imgtype == 'dib')
    {
      $dest_imgtype = 'BMP';
    }
    else
    {
      $dest_imgtype = null;
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';
      return false;
    }

    // Height/width
    if($angle == 0 || $angle == 180)
    {
      $srcWidth  = $imginfo[0];
      $srcHeight = $imginfo[1];
    }
    else
    {
      $srcWidth  = $imginfo[1];
      $srcHeight = $imginfo[0];
    }

    if($srcWidth <= $new_width && $srcHeight <= $new_width)
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

    // determine resizing width and height
      $offsetx = null;
      $offsety = null;
      // Resizing to thumbnail
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_CREATE_THUMBNAIL_FROM').' '.$imginfo[2].', '.$imginfo[0].' x '.$imginfo[1].'...<br />';

      if($new_width <= 0 || $new_height <= 0)
      {
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_ERROR_NO_VALID_WIDTH_OR_HEIGHT').'<br />';

        return false;
      }

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
        $testheight = ($srcHeight/$ratio);
        // If new height exceeds the setted max. height
        if($testheight>$new_height)
        {
          $ratio = ($srcHeight/$new_height);
        }
        break;
      // Resize to max side lenght - height or width (but keep original ratio)
      case 2:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_MAX').'<br />';
        if ($srcHeight > $srcWidth)
        {
          $ratio = ($srcHeight / $new_height);
          $testwidth = ($srcWidth / $ratio);
        } else
        {
          $ratio = ($srcWidth / $new_width);
          $testheight = ($srcHeight/$ratio);
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
            $offsetx = 0;
            $offsety = 0;
            break;
          // Right upper corner
          case 1:
            $offsetx = floor(($srcWidth - ($new_width * $ratio)));
            $offsety = 0;
            break;
          // Left lower corner
          case 3:
            $offsetx = 0;
            $offsety = floor(($srcHeight - ($new_height * $ratio)));
            break;
          // Right lower corner
          case 4:
            $offsetx = floor(($srcWidth - ($new_width * $ratio)));
            $offsety = floor(($srcHeight - ($new_height * $ratio)));
            break;
          // Default center
          default:
            $offsetx = floor(($srcWidth - ($new_width * $ratio)) * 0.5);
            $offsety = floor(($srcHeight - ($new_height * $ratio)) * 0.5);
            break;
        }
      }
      if(is_null($offsetx) && is_null($offsety))
      {
        $ratio = max($ratio, 1.0);

        $destWidth  = (int)($srcWidth / $ratio);
        $destHeight = (int)($srcHeight / $ratio);
      }
      else
      {
        $destWidth = $new_width;
        $destHeight = $new_height;
        $srcWidth  = (int)($destWidth * $ratio);
        $srcHeight = (int)($destHeight * $ratio);
      }

    // Method for creation of the resized image
    switch($method)
    {
      case 'gd1':
        if(!function_exists('imagecreatefromjpeg'))
        // check, if GD is available
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED');
          return false;
        }
        // create empty image of specified size
        $dst_img = imagecreate($destWidth, $destHeight);        
        if (!$src_img = JoomFile::imageCreateFrom_GD($src_file, $dst_img, $imginfo[2]));
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          return false;
        }
        if($angle > 0)
        // rotate image, if needed
        {
          $src_img = imagerotate($src_img, $angle, 0);
        }
        if (!is_null($offsetx) && !is_null($offsety))
        // resizing with GD1
        {
          imagecopyresized( $dst_img, $src_img, 0, 0, $offsetx, $offsety,
                            $destWidth, (int)$destHeight, $srcWidth, $srcHeight);
        }
        else
        {
          imagecopyresized( $dst_img, $src_img, 0, 0, 0, 0, $destWidth,
                            (int)$destHeight, $srcWidth, $srcHeight);
        }
        // write resized image to file
        $success = JoomFile::imageWriteFrom_GD());        
        if ($metadata)
        // copy metadata if needed
        {
          JoomFile::copyJPGmetadata($src_file,$dest_file);
        }
        if(!$success)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);
          $success = JoomFile::imageWriteFrom_GD());
          if ($metadata)
            JoomFile::copyJPGmetadata($src_file,$dest_file);
          }
          JoomFile::chmod($dir, '0755', true);
        }
        imagedestroy($src_img);
        imagedestroy($dst_img);
        break;
      case 'gd2':
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
        $dst_img = imagecreatetruecolor($destWidth, $destHeight);
        if (!$src_img = JoomFile::imageCreateFrom_GD($src_file, $dst_img, $imginfo[2]));
        {
          $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          return false;
        }
        if($angle > 0)
        // rotate image, if needed
        {
          $src_img = imagerotate($src_img, $angle, 0);
        }

        if($config->jg_fastgd2thumbcreation == 0)
        // use fast GD2 for resizing
        {
          if(!is_null($offsetx) && !is_null($offsety))
          {
            imagecopyresampled( $dst_img, $src_img, 0, 0, $offsetx, $offsety,
                                $destWidth, (int)$destHeight, $srcWidth, $srcHeight);
          }
          else
          {
            imagecopyresampled( $dst_img, $src_img, 0, 0, 0, 0, $destWidth,
                                (int)$destHeight, $srcWidth, $srcHeight);
          }
        }
        else
        // use normal GD2 for resizing
        {
          if(!is_null($offsetx) && !is_null($offsety))
          {
            JoomFile::fastImageCopyResampled( $dst_img, $src_img, 0, 0, $offsetx, $offsety,
                                              $destWidth, (int)$destHeight, $srcWidth, $srcHeight);
          }
          else
          {
            JoomFile::fastImageCopyResampled( $dst_img, $src_img, 0, 0, 0, 0, $destWidth,
                                              (int)$destHeight, $srcWidth, $srcHeight);
          }
        }
        // write resized image to file
        $success = JoomFile::imageWriteFrom_GD());
        if ($metadata)
        // copy metadata if needed
        {
          JoomFile::copyJPGmetadata($src_file,$dest_file);
        }
        if(!$success)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($dest_file);
          JoomFile::chmod($dir, '0777', true);
          $success = JoomFile::imageWriteFrom_GD());
          if ($metadata)
            JoomFile::copyJPGmetadata($src_file,$dest_file);
          }
          JoomFile::chmod($dir, '0755', true);
        }
        imagedestroy($src_img);
        imagedestroy($dst_img);
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
        if(!empty($config->jg_impath))
        {
          $convert_path=$config->jg_impath.'convert';
        }
        else
        {
          $convert_path='convert';
        }
        $commands = '';

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
        if(!is_null($offsetx) && !is_null($offsety))
        {
          $commands .= ' -crop "'.$srcWidth.'x'.$srcHeight.'+'.$offsetx.'+'.$offsety.'" +repage';
        }
        // Finally the resize
        $commands  .= ' -resize "'.$destWidth.'x'.$destHeight.'" -quality "'.$dest_qual.'" -unsharp "3.5x1.2+1.0+0.10"';
        $convert    = $convert_path.' '.$commands.' "'.$src_file.'" "'.$dest_file.'"';

        $return_var = null;
        $dummy      = null;
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
    $imginfo = getimagesize($dest_file);
    if(!$imginfo)
    {
      return false;
    }
    return true;
  }

  /**
   * Rotate an image (only JPGs) with functions from gd/gd2/imagemagick
   *
   * @param   &string $debugoutput  Debug information
   * @param   string  $src          Path to source file
   * @param   int     $method       gd1/gd2/im
   * @param   int     $dest_qual    Image quality
   * @param   int     $angle        Angle to rotate the image anticlockwise
   * @param   boolean $auto_orient  If true, use the command option -auto-orient with
   *                                convert (ImageMagick), otherwise option -rotate is used
   * @return  boolean True on success, false otherwise
   * @since   3.4
   */
  public static function rotateImage(&$debugoutput, $src, $method = 'gd2', $dest_qual = 100, $angle = 0, $auto_orient = true)
  {
    if($angle == 0)
    {
      // Nothing to do
      return true;
    }

    // Ensure that the path is valid and clean
    $src = JPath::clean($src);

    if(!($imginfo = getimagesize($src)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_FILE_NOT_FOUND').'<br />';

      return false;
    }

    // Automatic rotation during upload is only supported for JPG images
    if($imginfo[2] != IMAGETYPE_JPEG)
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_ERROR_ROTATE_ONLY_JPG').'<br />';

      return false;
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

        // Beginn Rotation
        $src_img     = imagecreatefromjpeg($src);
        $rotated_img = imagerotate($src_img, $angle, 0);

        if(!@imagejpeg($rotated_img, $src, $dest_qual))
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($src);
          JoomFile::chmod($dir, '0777', true);
          imagejpeg($rotated_img, $src, $dest_qual);
          JoomFile::chmod($dir, '0755', true);
        }

        imagedestroy($src_img);
        imagedestroy($rotated_img);

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

        if(!empty($config->jg_impath))
        {
          $convert_path = $config->jg_impath.'convert';
        }
        else
        {
          $convert_path = 'convert';
        }

        // Finally the rotate
        if($auto_orient)
        {
          $commands = '-auto-orient';
        }
        else
        {
          $commands = '-rotate "-' . $angle . '"';
        }
        $commands  .= ' -quality '.$dest_qual;
        $convert    = $convert_path.' '.$commands.' "'.$src.'" "'.$src.'"';
        $return_var = null;
        $dummy      = null;
        @exec($convert, $dummy, $return_var);

        if($return_var != 0)
        {
          // Workaround for servers with wwwrun problem
          $dir = dirname($src);
          JoomFile::chmod($dir, '0777', true);
          @exec($convert, $dummy, $return_var);
          JoomFile::chmod($dir, '0755', true);

          if($return_var != 0)
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_ERROR_IM_IMAGE_NOT_ROTATED').'<br />';

            return false;
          }
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
   * @return  boolean True on success, false otherwise
   * @since   1.0.0
   */
  public static function fastImageCopyResampled(&$dst_image, $src_image, $dst_x, $dst_y,
                                  $src_x, $src_y, $dst_w, $dst_h,
                                  $src_w, $src_h, $quality = 3)
  {
    if(empty($src_image) || empty($dst_image) || $quality <= 0)
    {
      return false;
    }

    if($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h))
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
    return true;
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
   * Creates GD image objects from different file types
   *
   * @param   string  Path to source file
   * @param   object  GD image identifier created with imagecreate() or imagecreatetruecolor()
   * @param   string  Type of the source image file
   * @return  boolean True on success, false otherwise
   * @since   3.5.0
   */
  public static function imageCreateFrom_GD($src_file, $dst_img, $imgtype)
  {
    if($imgtype == 'PNG')
    {
      imageAlphaBlending($dst_img, false);
      imageSaveAlpha($dst_img, true);
      $src_img = imagecreatefrompng($src_file);
    }
    elseif($imgtype == 'GIF')
    {
      $src_img = imagecreatefromgif($src_file);
    }
    elseif($imgtype == 'JPG')
    {
      $src_img = imagecreatefromjpeg($src_file);
    }
    elseif($imgtype == 'BMP')
    {
      $src_img = imagecreatefrombmp($src_file);
    }
    else
    {
      return false;
    }
    return $src_img;
  }

  /**
   * Output GD image object to file from different file types
   *
   * @param   string  Path to destination file
   * @param   object  GD image object
   * @param   int     Quality of the image to be saved (1-100)
   * @param   string  Type of the destination image file
   * @return  boolean True on success, false otherwise
   * @since   3.5.0
   */
  public static function imageWriteFrom_GD($dest_file, $dst_img, $dest_qual, $dest_imgtype)
  {
    if($dest_imgtype == 'PNG')
      {
        $dest_qual = $dest_qual / 10 - 1;
        $success = imagepng($dst_img, $dest_file, $dest_qual);
      }
      elseif($dest_imgtype == 'GIF')
      {
        $success = imagegif($dst_img, $dest_file);
      }
      elseif($dest_imgtype == 'BMP' && function_exists('imagebmp'))
      {
        $comp = false;
        if ($dest_qual < 100)
        {
          $comp = true;
        }
        $success = imagebmp($dst_img, $dest_file, $comp);
      }
      else
      {
        $success = imagejpeg($dst_img, $dest_file, $dest_qual);
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
  public static function copyJPGmetadata($srcfile, $destfile) {
      // Function transfers EXIF (APP1) and IPTC (APP13) from $srcfile and adds it to $destfile
      // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
      // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is
      //   most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
      //   of (strlen(<data>) + 2) 

      if (file_exists($srcfile) && file_exists($destfile)) {
          $srcsize = @getimagesize($srcfile, $imageinfo);
          // Check if file is jpg
          if ($imageinfo != 2) return false;
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

              while ((substr($destfilecontent, 0, 2) & 0xFFF0) === 0xFFE0) {
                  $segmentlen = (substr($destfilecontent, 2, 2) & 0xFFFF);
                  $iptcsegmentnumber = (substr($destfilecontent, 1, 1) & 0x0F);   // Last 4 bits of second byte is IPTC segment #
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
  public static function copyPNGmetadata($srcfile, $destfile) {
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