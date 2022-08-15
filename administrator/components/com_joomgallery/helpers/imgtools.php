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

/**
 * JoomGallery IMGtools Class
 * Methods and functions for image manipulations and metadata handling
 *
 * @static
 * @package JoomGallery
 * @since   3.5.0
 */
class JoomIMGtools
{
  /**
   * Memory needed in bytes for manipulation of a one-frame image with GD
   * depending on resolution, color-space, file-type
   *
   * @var array
   */
  protected static $memory_needed;

  /**
   * Holds all image informations of the source image, which are relevant for
   * image manipulation and metadata handling
   *
   * @var array
   */
  protected static $src_imginfo = array('width' => 0,'height' => 0,'type' => '','orientation' => '','exif' => array('IFD0' => array(),'EXIF' => array()),
                                        'iptc' => array(),'comment' => '','transparency' => false,'animation' => false, 'frames' => 1);

  /**
   * Holds all image informations of the destination image, which are relevant for
   * image manipulation and metadata handling
   *
   * @var array
   */
  protected static $dst_imginfo = array('width' => 0,'height' => 0,'type' => '','orientation' => '', 'offset_x' => 0,'offset_y' => 0,'angle' => 0,
                                        'flip' => 'none','quality' => 100,'src' => array('width' => 0,'height' => 0));

  /**
   * Holds the GD-Objects (image) and its duration (hundredths of a second) of each frame
   * (before image manipulation)
   *
   * @var array
   */
  protected static $src_frames = array(array('duration' => 0,'image' => null));

  /**
   * Holds the GD-Objects (image) and its duration (hundredths of a second) of each frame
   * (after image manipulation)
   *
   * @var array
   */
  protected static $dst_frames = array(array('duration' => 0,'image' => null));


  //////////////////////////////////////////////////////
  //   Public functions of JoomIMGtools.
  //   This functions can be used in JoomGallery
  //   for image manipulations and metadata handling

  //   Available image processors:
  //   GD: https://www.php.net/manual/en/intro.image.php
  //   IM: https://imagemagick.org/script/convert.php

  //   Animated gif support according to ImageWorkshop
  //   'Manage animated GIF with ImageWorkshop'
  //   Author: Clément Guillemain
  //   Website: https://phpimageworkshop.com/tutorial/5/manage-animated-gif-with-imageworkshop.html
  //////////////////////////////////////////////////////

  /**
   * Resize image with GD or ImageMagick
   * Supported image-types: JPG, PNG, GIF, WEBP
   *
   * @param   &string $debugoutput            Debug information
   * @param   string  $src_file               Path to source file
   * @param   string  $dst_file               Path to destination file
   * @param   int     $settings               Resize to 0=noresize 1=height,2=width,3=crop or 4=maxdimension
   * @param   int     $new_width              Width to resize
   * @param   int     $new_height             Height to resize
   * @param   int     $method                 Image processor: gd1,gd2,im
   * @param   int     $dst_qual               Quality of the resized image (1-100)
   * @param   int     $cropposition           Only if $settings=3:
   *                                          image section to use for cropping
   * @param   int     $angle                  Angle to rotate the resized image anticlockwise
   * @param   boolean $auto_orient            Auto orient image based on exif orientation (jpg only)
   *                                          if true: overwrites the value of angle
   * @param   boolean $metadata               true=preserve metadata in the resized image
   * @param   boolean $anim                   true=preserve animation in the resized image
   * @param   boolean $unsharp                true=sharpen the image during resize
   * @return  boolean True on success, false otherwise
   * @since   1.0.0
   */
  public static function resizeImage(&$debugoutput, $src_file, $dst_file, $settings, $new_width, $new_height, $method,
                                      $dst_qual = 100, $cropposition = 0, $angle = 0, $auto_orient = false, $metadata = false,
                                      $anim = false, $unsharp = false)
  {
    self::clearVariables();

    // Ensure that the paths are valid and clean
    $src_file = JPath::clean($src_file);
    $dst_file = JPath::clean($dst_file);

    // Load GifFrameExtractor-Class
    JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifframeextractor.php');

    // Analysis and validation of the source image
    if(!(self::$src_imginfo = self::analyseImage($src_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_FILE').'<br />';

      return false;
    }

    if(!$anim)
    {
      self::$src_imginfo['frames'] = 1;
    }

    // GD can only handle JPG, PNG, GIF and WEBP images
    if(    self::$src_imginfo['type'] != 'JPG'
       &&  self::$src_imginfo['type'] != 'PNG'
       &&  self::$src_imginfo['type'] != 'GIF'
       &&  self::$src_imginfo['type'] != 'WEBP'
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';

      return false;
    }

    $config = JoomConfig::getInstance();

    // Get rotation angle
    if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
    {
      self::autoOrient(self::$src_imginfo['exif']['IFD0']['Orientation']);
    }
    else
    {
      self::$dst_imginfo['angle'] = $angle;
      self::$dst_imginfo['flip'] = 'none';
    }

    // Get destination orientation
    if(abs(self::$dst_imginfo['angle']) == 90 || abs(self::$dst_imginfo['angle']) == 270)
    {
      if(self::$src_imginfo['orientation'] == 'landscape')
      {
        self::$dst_imginfo['orientation'] = 'portrait';
      }
      elseif(self::$src_imginfo['orientation'] == 'portrait')
      {
        self::$dst_imginfo['orientation'] = 'landscape';
      }
      else
      {
        self::$dst_imginfo['orientation'] = self::$src_imginfo['orientation'];
      }
    }
    else
    {
      self::$dst_imginfo['orientation'] = self::$src_imginfo['orientation'];
    }

    // Get destination quality
    self::$dst_imginfo['quality'] = $dst_qual;

    // Conditions where no resize is needed
    $noResize = false;
    if(self::$src_imginfo['orientation'] == self::$dst_imginfo['orientation'])
    {
      // dst and src same orientation
      if($settings == 0 || (self::$src_imginfo['width'] <= $new_width && self::$src_imginfo['height'] <= $new_height))
      {
        $noResize = true;
      }
    }
    else
    {
      // dst and src different orientation
      if($settings == 0 || self::$src_imginfo['width'] <= $new_height && self::$src_imginfo['height'] <= $new_width)
      {
        $noResize = true;
      }
    }

    if($noResize && self::$dst_imginfo['angle'] == 0 && self::$dst_imginfo['flip'] == 'none')
    {
      if($src_file != $dst_file)
      {
        // If source image is already of the same size or smaller than the image
        // and no rotation/flipping is needed, only copy the source image to destination
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_NOT_NECESSARY').'<br />';

        if(!JFile::copy($src_file, $dst_file))
        {
          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $dst_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

          return false;
        }
      }

      return true;
    }

    // Create backup file, if source and destination are the same
    if($src_file == $dst_file)
    {
      if(!JFile::copy($src_file, $src_file.'bak'))
      {
        $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $src_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

        return false;
      }
    }
    else
    {
      if(JFile::exists($dst_file))
      {
        if(!JFile::copy($dst_file, $dst_file.'bak'))
        {
          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $dst_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

          return false;
        }
      }
    }

    // Generate informations about type, dimension and origin of resized image
    if(!($dst_imginfo = self::getResizeInfo($dst_file, $settings, $new_width, $new_height, $cropposition)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';
      self::rollback($src_file, $dst_file);

      return false;
    }

    // Calculation for the amount of memory needed (in bytes, GD)
    $clc_method = 'resize';
    if(self::$dst_imginfo['angle'] > 0 || self::$dst_imginfo['flip'] != 'none')
    {
      $clc_method = 'rotate';
    }

    self::$memory_needed = self::calculateMemory(self::$src_imginfo, self::$dst_imginfo, $clc_method);

    // Check if there is enough memory for the manipulation
    $memory = self::checkMemory(self::$memory_needed);
    if(!$memory['success'])
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_ERROR_MEM_EXCEED').
                      $memory['needed']." MByte, Serverlimit: ".$memory['limit']." MByte<br />" ;
      self::rollback($src_file, $dst_file);

      return false;
    }

    // Create debugoutput
    switch($settings)
    {
      case 1:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_HEIGHT');
        break;
      case 2:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_WIDTH');
        break;
      case 3:
        // Free resizing and cropping
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_CROP');
        break;
      case 4:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_RESIZE_TO_MAX');
        break;
      default:
        break;
    }

    // Method for creation of the resized image
    switch($method)
    {
      case 'gd1':
        // 'break' intentionally omitted
      case 'gd2':
        $debugoutput .= 'GD...<br/>';

        if(!function_exists('imagecreate'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        if(self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'WEBP')
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_ANIMATED_WEBP').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Create empty image of specified size
        if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          // Create GD-Objects from gif-file
          $gfe = new GifFrameExtractor();
          self::$src_frames = $gfe->extract($src_file);

          // Check for failures in GIF extraction
          if(self::checkError(self::$src_frames))
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }

          foreach(self::$src_frames as $key => $frame)
          {
            // Create empty GD-Objects for the resized frames
            self::$dst_frames[$key]['duration'] = self::$src_frames[$key]['duration'];
            self::$dst_frames[$key]['image']    = self::imageCreateEmpty_GD(self::$src_frames[$key]['image'], self::$dst_imginfo,
                                                                            self::$src_imginfo['transparency']);
          }
        }
        else
        {
          // Normal image (image with one frame)
          // Create GD-Object from file
          self::$src_frames = self::imageCreateFrom_GD($src_file, self::$src_imginfo);

          // Create empty GD-Object for the resized image
          self::$dst_frames[0]['duration'] = 0;
          self::$dst_frames[0]['image']    = self::imageCreateEmpty_GD(self::$src_frames[0]['image'], self::$dst_imginfo,
                                                                       self::$src_imginfo['transparency']);
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Orient image, if needed
        if(self::$dst_imginfo['flip'] != 'none')
        {
          foreach(self::$src_frames as $key => $frame)
          {
            self::$src_frames[$key]['image'] = self::imageFlip_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['flip']);
          }

          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_FLIP_BY', self::$dst_imginfo['flip']).'<br />';
        }

        if(self::$dst_imginfo['angle'] > 0)
        {
          foreach(self::$src_frames as $key => $frame)
          {
            self::$src_frames[$key]['image'] = self::imageRotate_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['type'],
                                                                    self::$dst_imginfo['angle'], self::$src_imginfo['transparency']);
            self::$src_imginfo['width']      = imagesx(self::$src_frames[$key]['image']);
            self::$src_imginfo['height']     = imagesy(self::$src_frames[$key]['image']);
          }

          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_ROTATE_BY_ANGLE', self::$dst_imginfo['angle']).'<br />';
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Resizing with GD
        if(!$noResize)
        {
          // Resize image, if it is needed
          foreach(self::$src_frames as $key => $frame)
          {
            $fast_resize = false;

            if($config->jg_fastgd2thumbcreation == 1)
            {
              $fast_resize = true;
            }

            self::imageResize_GD(self::$dst_frames[$key]['image'], self::$src_frames[$key]['image'], self::$src_imginfo, self::$dst_imginfo,
                                 $fast_resize, 3);
          }
        }
        else
        {
          // Copy image, if no resize is needed
          foreach(self::$src_frames as $key => $frame)
          {
            self::imageCopy_GD(self::$dst_frames[$key]['image'], self::$src_frames[$key]['image']);
          }
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Sharpen image if needed
        if($unsharp)
        {
          foreach(self::$src_frames as $key => $frame)
          {
            self::$dst_frames[$key]['image'] = self::unsharpMask_GD(self::$dst_frames[$key]['image'], 100, 4.0, 30);
          }

          // Check for failures
          if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }
        }

        // Write resized image to file
        if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifcreator.php');
          $gc = new GifCreator();
          $gc->create(self::$dst_frames, 0);
          $success = file_put_contents($dst_file, $gc->getGif());
        }
        else
        {
          // Normal image (image with one frame)
          $success = self::imageWriteFrom_GD($dst_file, self::$dst_frames, self::$dst_imginfo);
        }

        // Workaround for servers with wwwrun problem
        if(!$success)
        {
          $dir = dirname($dst_file);
          JoomFile::chmod($dir, '0777', true);

          // Write resized image to file
          if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
          {
            // Animated GIF image (image with more than one frame)
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifcreator.php');
            $gc = new GifCreator();
            $gc->create(self::$dst_frames, 0);
            $success = file_put_contents($dst_file, $gc->getGif());
          }
          else
          {
            // Normal image (image with one frame)
            $success = self::imageWriteFrom_GD($dst_file, self::$dst_frames, self::$dst_imginfo);
          }

          // Copy metadata if needed
          if($metadata)
          {
            $new_orient = false;

            if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
            {
              if($auto_orient && self::$src_imginfo['exif']['IFD0']['Orientation'] != 1)
              {
                // Make sure, the exif orientation tag is set to 1 when auto-oriented
                $new_orient = 1;
              }
            }

            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            $meta_success = self::copyImageMetadata($quelle, $dst_file, self::$src_imginfo['type'], self::$dst_imginfo['type'], $new_orient);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA').'<br />';
              self::rollback($src_file, $dst_file);

              return false;
            }
          }

          JoomFile::chmod($dir, '0755', true);
        }
        else
        {
          // Copy metadata if needed
          if($metadata)
          {
            $new_orient = false;

            if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
            {
              if($auto_orient && self::$src_imginfo['exif']['IFD0']['Orientation'] != 1)
              {
                // Make sure, the exif orientation tag is set to 1 when auto-oriented
                $new_orient = 1;
              }
            }

            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            $meta_success = self::copyImageMetadata($quelle, $dst_file, self::$src_imginfo['type'], self::$dst_imginfo['type'], $new_orient);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA').'<br />';
              self::rollback($src_file, $dst_file);

              return false;
            }
          }
        }

        // Check for failures
        if(!$success)
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Destroy GD-Objects
        foreach(self::$src_frames as $key => $frame)
        {
          if(!empty(self::$src_frames[$key]['image']))
          {
            imagedestroy(self::$src_frames[$key]['image']);
          }

          if(!empty(self::$dst_frames[$key]['image']) && self::$dst_frames[$key]['image'] != self::$src_frames[$key]['image'])
          {
            imagedestroy(self::$dst_frames[$key]['image']);
          }
        }
        break;
      case 'im':
        $debugoutput       .= 'ImageMagick...<br/>';
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

        if(self::$src_imginfo['animation']  && !$anim)
        {
          // If resizing an animation but not preserving the animation, consider only first frame
          $src_file = $src_file.'[0]';
        }
        else
        {
          if(self::$src_imginfo['animation']  && $anim && self::$src_imginfo['type'] == 'GIF')
          {
            // If resizing an animation, use coalesce for better results
            $commands .= ' -coalesce';
          }
        }

        // Rotate image, if needed (use auto-orient command)
        if($auto_orient)
        {
          $commands .= ' -auto-orient';

          $debugoutput .= JText::_('COM_JOOMGALLERY_AUTOORIENT_IMAGE').'<br />';
        }

        if($auto_orient && $settings == 3)
        {
          $commands .= ' +repage';
        }

        // Delete all metadata, if needed
        if(!$metadata)
        {
          $commands .= ' -strip';
        }

        // Crop the source image before resiszing if offsets setted before
        // example of crop: convert input -crop destwidthxdestheight+offsetx+offsety +repage output
        // +repage needed to delete the canvas
        if($settings == 3)
        {
          // Assembling the imagick command for cropping
          $commands .= ' -crop "'.self::$dst_imginfo['src']['width'].'x'.self::$dst_imginfo['src']['height'].'+'.self::$dst_imginfo['offset_x'].'+'.self::$dst_imginfo['offset_y'].'" +repage';
        }

        if(!$noResize)
        {
          // Assembling the imagick command for resizing if resizing is needed
          $commands  .= ' -resize "'.self::$dst_imginfo['width'].'x'.self::$dst_imginfo['height'].'" -quality "'.self::$dst_imginfo['quality'].'"';
        }

        if($unsharp)
        {
          // Assembling the imagick command for the unsharp masking
          $commands  .= ' -unsharp "3.5x1.2+1.0+0.10"';
        }

        // Assembling the shell code for the resize with imagick
        $convert    = $convert_path.' '.$commands.' "'.$src_file.'" "'.$dst_file.'"';

        $return_var = null;
        $dummy      = null;
        $filecheck  = true;

        // execute the resize
        @exec($convert, $dummy, $return_var);

        // Check that the resized image is valid
        if(!self::checkValidImage($dst_file))
        {
          $filecheck  = false;
        }

        // Workaround for servers with wwwrun problem
        if($return_var != 0 || !$filecheck)
        {
          $dir = dirname($dst_file);
          JoomFile::chmod($dir, '0777', true);

          // Execute the resize
          @exec($convert, $dummy, $return_var);

          JoomFile::chmod($dir, '0755', true);

          // Check that the resized image is valid
          if(!self::checkValidImage($dst_file))
          {
            $filecheck = false;
          }

          if($return_var != 0 || !$filecheck)
          {
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }
        }
        break;
      default:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_UNSUPPORTED_METHOD').'<br />';
        self::rollback($src_file, $dst_file);

        return false;
        break;
    }

    // Set mode of uploaded picture
    JPath::setPermissions($dst_file);

    //$tmp_nmb_frames = count(self::$src_frames);
    //$debugoutput .= 'number of frames: '.$tmp_nmb_frames.'<br/>';
    //$tmp_memory_used = round(memory_get_peak_usage(true) / 1048576,3);
    //$debugoutput .= 'used memory: '.$tmp_memory_used.' MB<br/>';

    // Check, if file exists and is a valid image
    if(self::checkValidImage($dst_file))
    {
      if(JFile::exists($src_file.'bak'))
      {
        JFile::delete($src_file.'bak');
      }

      if(JFile::exists($dst_file.'bak'))
      {
        JFile::delete($dst_file.'bak');
      }

      return true;
    }
    else
    {
      $debugoutput .= JText::sprintf('COM_JOOMGALLERY_COMMON_ERROR_RESIZE_IMAGE', $src_file).'<br />';
      self::rollback($src_file, $dst_file);

      return false;
    }
  }

  /**
   * Rotate image with GD or ImageMagick
   * Supported image-types: JPG, PNG, GIF, WEBP
   *
   * @param   &string $debugoutput            Debug information
   * @param   string  $src_file               Path to source file
   * @param   string  $dst_file               Path to destination file
   * @param   int     $method                 Image processor: gd1,gd2,im
   * @param   int     $dst_qual               Quality of the rotated image (1-100)
   * @param   int     $angle                  Angle to rotate the image anticlockwise
   * @param   boolean $auto_orient            Auto orient image based on exif orientation (jpg only)
   *                                          if true: overwrites the value of angle
   * @param   boolean $metadata               true=preserve metadata during rotation
   * @param   boolean $anim                   true=preserve animation during rotation
   * @return  boolean True on success, false otherwise (false, if no rotation is needed)
   * @since   3.4.0
   */
  public static function rotateImage(&$debugoutput, $src_file, $dst_file, $method,
                                      $dst_qual = 100, $angle = 0, $auto_orient = true, $metadata = true, $anim = true)
  {
    self::clearVariables();
    $config = JoomConfig::getInstance();

    if($angle == 0 && !$auto_orient)
    {
      // Nothing to do
      return false;
    }

    // Load GifFrameExtractor-Class
    JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifframeextractor.php');

    // Ensure that the path is valid and clean
    $src_file = JPath::clean($src_file);
    $dst_file = JPath::clean($dst_file);

    // Analysis and validation of the source image
    if(!(self::$src_imginfo = self::analyseImage($src_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_FILE').'<br />';

      return false;
    }

    // GD can only handle JPG, PNG, GIF and WEBP images
    if(    self::$src_imginfo['type'] != 'JPG'
       &&  self::$src_imginfo['type'] != 'PNG'
       &&  self::$src_imginfo['type'] != 'GIF'
       &&  self::$src_imginfo['type'] != 'WEBP'
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_ERROR_ROTATE_ONLY_JPG').'<br />';

      return false;
    }

    $config = JoomConfig::getInstance();

    // Definition of type, dimension and origin of rotated image
    self::$dst_imginfo['width']    = self::$dst_imginfo['src']['width'] = self::$src_imginfo['width'];
    self::$dst_imginfo['height']   = self::$dst_imginfo['src']['height'] = self::$src_imginfo['height'];
    self::$dst_imginfo['type']     = self::$src_imginfo['type'];
    self::$dst_imginfo['offset_x'] = 0;
    self::$dst_imginfo['offset_y'] =  0;
    self::$dst_imginfo['quality']  = $dst_qual;

    // Get rotation angle
    if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
    {
      self::autoOrient(self::$src_imginfo['exif']['IFD0']['Orientation']);
    }
    else
    {
      self::$dst_imginfo['angle'] = $angle;
      self::$dst_imginfo['flip']  = 'none';
    }

    if(self::$dst_imginfo['angle'] == 0 && self::$dst_imginfo['flip'] == 'none')
    {
      // Nothing to do
      return false;
    }

    // Calculation for the amount of memory needed (in bytes, GD)
    self::$memory_needed = self::calculateMemory(self::$src_imginfo, self::$dst_imginfo, 'rotate');

    // Check if there is enough memory for the manipulation (assuming one frame)
    $memory = self::checkMemory(self::$memory_needed);
    if(!$memory['success'])
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_ERROR_MEM_EXCEED').
                      $memory['needed']." MByte, Serverlimit: ".$memory['limit']." MByte<br />" ;

      return false;
    }

    //$tmp_memory_needed = round(self::$memory_needed / 1048576,3);
    //$output = 'calculated memory: '.$tmp_memory_needed.' MB<br/>';

    // Create backup file, if source and destination are the same
    if($src_file == $dst_file)
    {
      if(!JFile::copy($src_file, $src_file.'bak'))
      {
        $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $src_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

        return false;
      }
    }
    else
    {
      if(JFile::exists($dst_file))
      {
        if(!JFile::copy($dst_file, $dst_file.'bak'))
        {
          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $dst_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

          return false;
        }
      }
    }

    // Method for creation of the rotated image
    switch($method)
    {
      case 'gd1':
        // 'break' intentionally omitted
      case 'gd2':
        if(!function_exists('imagecreate'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED');
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Create empty image of specified size
        if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          // Create GD-Objects from gif-file
          $gfe = new GifFrameExtractor();
          self::$src_frames = $gfe->extract($src_file);

          // Check for failures in GIF extraction
          if(self::checkError(self::$src_frames))
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }

          foreach(self::$src_frames as $key => $frame)
          {
            // Create empty GD-Objects for the rotated frames
            self::$dst_frames[$key]['duration'] = self::$src_frames[$key]['duration'];
            self::$dst_frames[$key]['image']    = self::imageCreateEmpty_GD(self::$src_frames[$key]['image'], self::$dst_imginfo,
                                                                            self::$src_imginfo['transparency']);
          }
        }
        else
        {
          // Normal image (image with one frame)
          // Create GD-Object from file
          self::$src_frames = self::imageCreateFrom_GD($src_file, self::$src_imginfo);

          // Create empty GD-Object for the resized image
          self::$dst_frames[0]['duration'] = 0;
          self::$dst_frames[0]['image']    = self::imageCreateEmpty_GD(self::$src_frames[0]['image'], self::$dst_imginfo,
                                                                       self::$src_imginfo['transparency']);
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Rotate image
        if(self::$dst_imginfo['flip'] != 'none' && self::$dst_imginfo['angle'] == 0)
        {
          foreach(self::$src_frames as $key => $frame)
          {
            self::$dst_frames[$key]['image'] = self::imageFlip_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['flip']);
          }

          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_FLIP_BY', self::$dst_imginfo['flip']).'<br />';
        }
        else
        {
          if(self::$dst_imginfo['flip'] != 'none')
          {
            foreach(self::$src_frames as $key => $frame)
            {
              self::$src_frames[$key]['image'] = self::imageFlip_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['flip']);
            }

            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_FLIP_BY', self::$dst_imginfo['flip']).'<br />';
          }

          foreach(self::$src_frames as $key => $frame)
          {
            self::$dst_frames[$key]['image'] = self::imageRotate_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['type'],
                                                                    self::$dst_imginfo['angle'], self::$src_imginfo['transparency']);
            self::$dst_imginfo['width']      = imagesx(self::$src_frames[$key]['image']);
            self::$dst_imginfo['height']     = imagesy(self::$src_frames[$key]['image']);
          }

          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_ROTATE_BY_ANGLE', self::$dst_imginfo['angle']).'<br />';
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Write rotated image to file
        if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifcreator.php');
          $gc = new GifCreator();
          $gc->create(self::$dst_frames, 0);
          $success = file_put_contents($dst_file, $gc->getGif());
        }
        else
        {
          // Normal image (image with one frame)
          $success = self::imageWriteFrom_GD($dst_file, self::$dst_frames, self::$dst_imginfo);
        }

        // Workaround for servers with wwwrun problem
        if(!$success)
        {
          $dir = dirname($dst_file);
          JoomFile::chmod($dir, '0777', true);

          // Write resized image to file
          if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
          {
            // Animated GIF image (image with more than one frame)
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifcreator.php');
            $gc = new GifCreator();
            $gc->create(self::$dst_frames, 0);
            $success = file_put_contents($dst_file, $gc->getGif());
          }
          else
          {
            // Normal image (image with one frame)
            $success = self::imageWriteFrom_GD($dst_file, self::$dst_frames, self::$dst_imginfo);
          }

          // Copy metadata if needed
          if($metadata)
          {
            $new_orient = false;

            if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
            {
              if($auto_orient && self::$src_imginfo['exif']['IFD0']['Orientation'] != 1)
              {
                // if image was auto oriented, change exif orientation tag to 1
                $new_orient = 1;
              }
            }

            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            $meta_success = self::copyImageMetadata($quelle, $dst_file, self::$src_imginfo['type'], self::$dst_imginfo['type'], $new_orient);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              self::rollback($src_file, $dst_file);

              return false;
            }
          }

          JoomFile::chmod($dir, '0755', true);
        }
        else
        {
          // Copy metadata if needed
          if($metadata)
          {
            $new_orient = false;

            if($auto_orient && isset(self::$src_imginfo['exif']['IFD0']['Orientation']))
            {
              if($auto_orient && self::$src_imginfo['exif']['IFD0']['Orientation'] != 1)
              {
                // If image was auto oriented, change exif orientation tag to 1
                $new_orient = 1;
              }
            }

            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            $meta_success = self::copyImageMetadata($quelle, $dst_file, self::$src_imginfo['type'], self::$dst_imginfo['type'], $new_orient);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');
              self::rollback($src_file, $dst_file);

              return false;
            }
          }
        }

        // Check for failures
        if(!$success)
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Destroy GD-Objects
        foreach(self::$src_frames as $key => $frame)
        {
          if(!empty(self::$src_frames[$key]['image']))
          {
            imagedestroy(self::$src_frames[$key]['image']);
          }

          if(!empty(self::$dst_frames[$key]['image']) && self::$dst_frames[$key]['image'] != self::$src_frames[$key]['image'])
          {
            imagedestroy(self::$dst_frames[$key]['image']);
          }
        }
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

        if(self::$src_imginfo['animation']  && !$anim)
        {
          // if resizing an animation but not preserving the animation, consider only first frame
          $src_file = $src_file.'[0]';
        }
        else
        {
          if(self::$src_imginfo['animation']  && $anim && self::$src_imginfo['type'] == 'GIF')
          {
            // If resizing an animation, use coalesce for better results
            $commands .= ' -coalesce';
          }
        }

        // Rotate image, if needed (use auto-orient command)
        if($auto_orient)
        {
          $commands    .= ' -auto-orient';
          $debugoutput .= JText::_('COM_JOOMGALLERY_AUTOORIENT_IMAGE').'<br />';
        }
        else
        {
          if($angle > 0)
          {
            $commands    .= ' -rotate "-'.$angle.'"';
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_ROTATE_BY_ANGLE', $angle).'<br />';
          }
        }

        // Rotation quality
        $commands  .= ' -quality '.$dst_qual;

        // Delete all metadata, if needed
        if(!$metadata)
        {
          $commands .= ' -strip';
        }

        // Assembling the shell code for the resize with imagick
        $convert = $convert_path.' '.$commands.' "'.$src_file.'" "'.$dst_file.'"';

        $return_var = null;
        $dummy      = null;
        $filecheck  = true;

        // Execute the resize
        @exec($convert, $dummy, $return_var);

        // Check that the resized image is valid
        if(!self::checkValidImage($dst_file))
        {
          $filecheck  = false;
        }

        // Workaround for servers with wwwrun problem
        if($return_var != 0 || !$filecheck)
        {
          $dir = dirname($dst_file);
          JoomFile::chmod($dir, '0777', true);

          // Execute the resize
          @exec($convert, $dummy, $return_var);

          // Preserve metadata of png files with php functions
          if(self::$src_imginfo['type'] == 'PNG')
          {
            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            // copy metadata
            $meta_success = self::copyImageMetadata($quelle, $dst_file, self::$src_imginfo['type'], self::$dst_imginfo['type']);

            if(!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

              return false;
            }
          }

          JoomFile::chmod($dir, '0755', true);

          // Check that the resized image is valid
          if(!self::checkValidImage($dst_file))
          {
            $filecheck  = false;
          }

          if($return_var != 0 || !$filecheck)
          {
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM', 'exec('.$convert.');').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }
        }
        else
        {
          // Preserve metadata of png files with php functions
          if(self::$src_imginfo['type'] == 'PNG')
          {
            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            // Copy metadata
            $meta_success = self::copyImageMetadata($quelle, $dst_file, self::$src_imginfo['type'], self::$dst_imginfo['type']);

            if(!$meta_success)
            {
              $debugoutput.=JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA');

              return false;
            }
          }
        }
        break;
      default:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_UNSUPPORTED_METHOD').'<br />';
        self::rollback($src_file, $dst_file);

        return false;
        break;
    }

    // Set mode of uploaded picture
    JPath::setPermissions($dst_file);

    //$tmp_nmb_frames = count(self::$src_frames);
    //$debugoutput .= 'number of frames: '.$tmp_nmb_frames.'<br/>';
    //$tmp_memory_used = round(memory_get_peak_usage(true) / 1048576,3);
    //$output .= 'used memory: '.$tmp_memory_used.' MB<br/>';
    //JFactory::getApplication()->enqueueMessage($output);


    // Check, if file exists and is a valid image
    if(self::checkValidImage($dst_file))
    {
      if(JFile::exists($src_file.'bak'))
      {
        JFile::delete($src_file.'bak');
      }

      if(JFile::exists($dst_file.'bak'))
      {
        JFile::delete($dst_file.'bak');
      }

      return true;
    }
    else
    {
      $debugoutput .= JText::sprintf('COM_JOOMGALLERY_COMMON_ERROR_ROTATE_IMAGE', $src_file).'<br />';
      self::rollback($src_file, $dst_file);

      return false;
    }
  }

  /**
   * Add watermark to image using GD or ImageMagick
   * Supported image-types: JPG, PNG, GIF, WEBP
   *
   * @param   string  $src_file               Path to source file
   * @param   string  $dst_file               Path to destination file
   * @param   string  $wtm_file               Path to watermark file
   * @param   int     $method                 Image processor: gd1,gd2,im
   * @param   int     $wtm_pos                Positioning of the watermark
   *                                          (1:topleft,2:topcenter,3:topright,4:middleleft,5:middlecenter
   *                                           6:middleright,7:bottomleft,8:bottomcenter,9:bottomright)
   * @param   int     $wtm_resize             resize watermark (0:no,1:by height,2:by width)
   * @param   float   $wtm_newSize            new size of the resized watermark in percent related to the file (1-100)
   * @param   int     $opacity                opacity of the watermark on the image in percent (0-100), 0: waternak invisible, 100: full coverage
   * @param   boolean $metadata               true=preserve metadata during watermarking
   * @param   boolean $anim                   true=preserve animation during watermarking
   * @return  boolean True on success, false otherwise
   * @since   3.5.1
   */
  public static function watermarkImage(&$debugoutput, $src_file, $dst_file, $wtm_file, $method,
                                         $wtm_pos, $wtm_resize, $wtm_newSize, $opacity = 0, $metadata = true, $anim = true)
  {
    self::clearVariables();

    // Ensure that the paths are valid and clean
    $src_file = JPath::clean($src_file);
    $dst_file = JPath::clean($dst_file);
    $wtm_file = JPath::clean($wtm_file);

    // Checks if watermark file is existent
    if(!JFile::exists($wtm_file))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_ERROR_WATERMARK_NOT_EXIST');

      return false;
    }

    // Load GifFrameExtractor-Class
    JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifframeextractor.php');

    // Analysis and validation of the source image
    if(!($imginfo = self::analyseImage($src_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_FILE').'<br />';

      return false;
    }

    // Analysis and validation of the source watermark-image
    if(!(self::$src_imginfo = self::analyseImage($wtm_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_OUTPUT_INVALID_WTM_FILE').'<br />';

      return false;
    }
    self::$src_imginfo['frames'] = 1;


    if(!$anim)
    {
      $imginfo['frames'] = 1;
    }

    // GD can only handle JPG, PNG, GIF and WEBP images
    if(   ($imginfo['type'] != 'JPG' && $imginfo['type'] != 'PNG' && $imginfo['type'] != 'GIF' && $imginfo['type'] != 'WEBP')
       || (self::$src_imginfo['type'] != 'JPG' && self::$src_imginfo['type'] != 'PNG' && self::$src_imginfo['type'] != 'GIF' && self::$src_imginfo['type'] != 'WEBP')
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';

      return false;
    }

    $config = JoomConfig::getInstance();

    // Create backup file, if source and destination are the same
    if($src_file == $dst_file)
    {
      if(!JFile::copy($src_file, $src_file.'bak'))
      {
        $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $src_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

        return false;
      }
    }
    else
    {
      if(JFile::exists($dst_file))
      {
        if(!JFile::copy($dst_file, $dst_file.'bak'))
        {
          $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_COPYING', $dst_file).' '.JText::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS').'<br />';

          return false;
        }
      }
    }

    // Generate informations about type, dimension and origin of resized image
    $position = self::getWatermarkingInfo($imginfo, $wtm_pos, $wtm_resize, $wtm_newSize);

    // Calculation for the amount of memory needed (in bytes, GD)
    self::$memory_needed = self::calculateMemory(self::$src_imginfo, self::$dst_imginfo, 'resize');

    // Check if there is enough memory for the manipulation
    $memory = self::checkMemory(self::$memory_needed);
    if(!$memory['success'])
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_ERROR_MEM_EXCEED').
                      $memory['needed']." MByte, Serverlimit: ".$memory['limit']." MByte<br />" ;
      self::rollback($src_file, $dst_file);

      return false;
    }

    // Create debugoutput
    $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_OUTPUT_WATERMARK_IMAGE');

    // Method for the manipulation
    switch($method)
    {
      case 'gd1':
        // 'break' intentionally omitted
      case 'gd2':
        $debugoutput .= 'GD...<br/>';

        if(!function_exists('imagecreate'))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_INSTALLED').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Create empty watermark-image of specified size
        // Create GD-Object from file
        self::$src_frames = self::imageCreateFrom_GD($wtm_file, self::$src_imginfo);

        // Create empty GD-Object for the resized watermark
        self::$dst_frames[0]['duration'] = 0;
        self::$dst_frames[0]['image']    = self::imageCreateEmpty_GD(self::$src_frames[0]['image'], self::$dst_imginfo,
                                                                     self::$src_imginfo['transparency']);

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_WATERMARKING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Check if resize is needed
        $resizeWatermark = false;
        if ($wtm_resize != 0 || self::$src_imginfo['width'] == self::$dst_imginfo['width'] || self::$src_imginfo['height'] == self::$dst_imginfo['height'])
        {
          $resizeWatermark = true;
        }

        // Resizing with GD
        if($resizeWatermark)
        {
          self::imageResize_GD(self::$dst_frames[0]['image'], self::$src_frames[0]['image'], self::$src_imginfo, self::$dst_imginfo,
                               false, 3);
        }
        else
        {
          // Copy watermark, if no resize is needed
          self::$dst_frames[0]['image'] = self::$src_frames[0]['image'];
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_WATERMARKING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // src_frames will not be used anymore
        // Destroy GD-Objects if there are any
        foreach(self::$src_frames as $key => $frame)
        {
          if(!empty(self::$src_frames[$key]['image']))
          {
            imagedestroy(self::$src_frames[$key]['image']);
          }
        }
        // Reset src_frames to default
        self::$src_frames = array(array('duration' => 0,'image' => null));

        // Create empty image of specified size
        if($anim && $imginfo['animation'] && $imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          // Create GD-Objects from gif-file
          $gfe = new GifFrameExtractor();
          self::$src_frames = $gfe->extract($src_file, true);

          // Check for failures in GIF extraction
          if(self::checkError(self::$src_frames))
          {
            $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_WATERMARKING').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }
        }
        else
        {
          // Normal image (image with one frame)
          // Create GD-Object from file
          self::$src_frames = self::imageCreateFrom_GD($src_file, $imginfo);
        }

        // Watermarking with GD
        foreach(self::$src_frames as $key => $frame)
        {
          self::imageWatermark_GD(self::$src_frames[$key]['image'], self::$dst_frames[0]['image'], $imginfo, self::$dst_imginfo, $position, $opacity);
        }

        // Check for failures
        if(self::checkError(self::$src_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_WATERMARKING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Write resized image to file
        $imginfo['quality'] = 100;
        if($anim && $imginfo['animation'] && $imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifcreator.php');
          $gc = new GifCreator();
          $gc->create(self::$src_frames, 0);
          $success = file_put_contents($dst_file, $gc->getGif());
        }
        else
        {
          // Normal image (image with one frame)
          $success = self::imageWriteFrom_GD($dst_file, self::$src_frames, $imginfo);
        }

        // Workaround for servers with wwwrun problem
        if(!$success)
        {
          $dir = dirname($dst_file);
          JoomFile::chmod($dir, '0777', true);

          // Write resized image to file
          if($anim && $imginfo['animation'] && $imginfo['type'] == 'GIF')
          {
            // Animated GIF image (image with more than one frame)
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifcreator.php');
            $gc = new GifCreator();
            $gc->create(self::$src_frames, 0);
            $success = file_put_contents($dst_file, $gc->getGif());
          }
          else
          {
            // Normal image (image with one frame)
            $success = self::imageWriteFrom_GD($dst_file, self::$src_frames, $imginfo);
          }

          // Copy metadata if needed
          if($metadata)
          {
            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            $meta_success = self::copyImageMetadata($quelle, $dst_file, $imginfo['type'], $imginfo['type'], false);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA').'<br />';
              self::rollback($src_file, $dst_file);

              return false;
            }
          }

          JoomFile::chmod($dir, '0755', true);
        }
        else
        {
          // Copy metadata if needed
          if($metadata)
          {
            if($src_file == $dst_file)
            {
              $quelle = $src_file.'bak';
            }
            else
            {
              $quelle = $src_file;
            }

            $meta_success = self::copyImageMetadata($quelle, $dst_file, $imginfo['type'], $imginfo['type'], false);
            if(!$meta_success)
            {
              $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ERROR_COPY_METADATA').'<br />';
              self::rollback($src_file, $dst_file);

              return false;
            }
          }
        }

        // Check for failures
        if(!$success)
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_WATERMARKING').'<br />';
          self::rollback($src_file, $dst_file);

          return false;
        }

        // Destroy GD-Objects
        foreach(self::$src_frames as $key => $frame)
        {
          if(!empty(self::$src_frames[$key]['image']))
          {
            imagedestroy(self::$src_frames[$key]['image']);
          }

          if(!empty(self::$dst_frames[$key]['image']) && self::$dst_frames[$key]['image'] != self::$src_frames[$key]['image'])
          {
            imagedestroy(self::$dst_frames[$key]['image']);
          }
        }
        break;
      case 'im':
        $debugoutput       .= 'ImageMagick...<br/>';
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

        // Delete all metadata, if needed
        if(!$metadata)
        {
          $commands .= ' -strip ';
        }

        if($imginfo['animation'] && $anim && $imginfo['type'] == 'GIF')
        {
          // TODO: resize of watermark when its animation
          // Positioning of the watermark
          $commands .= ' "'.$src_file.'" -coalesce -gravity "northwest" -geometry "+'.$position[0].'+'.$position[1].'" null:';

          // copy watermark on top of image
          $commands .= ' "'.$wtm_file.'" -layers composite -layers optimize '.$dst_file.'"';
        }
        else
        {
          if($imginfo['animation'] && !$anim)
          {
            // If resizing an animation but not preserving the animation, consider only first frame
            $src_file = $src_file.'[0]';
          }

          // Resize watermark file
          $commands .= ' "'.$wtm_file.'" -resize "'.self::$dst_imginfo['width'].'x'.self::$dst_imginfo['height'].'"';

          // Positioning of the watermark
          $commands .= ' "'.$src_file.'" +swap -gravity "northwest" -geometry "+'.$position[0].'+'.$position[1].'"';

          // copy watermark on top of image
          $commands .= ' -define compose:args='.$opacity.',100 -compose dissolve -composite'.' "'.$dst_file.'"';
        }

        // Assembling the shell code for the watermarking with imagick
        $convert    = $convert_path.$commands;

        $return_var = null;
        $dummy      = null;
        $filecheck  = true;

        // execute the resize
        @exec($convert, $dummy, $return_var);

        // Check that the resized image is valid
        if(!self::checkValidImage($dst_file))
        {
          $filecheck  = false;
        }

        // Workaround for servers with wwwrun problem
        if($return_var != 0 || !$filecheck)
        {
          $dir = dirname($dst_file);
          JoomFile::chmod($dir, '0777', true);

          // Execute the resize
          @exec($convert, $dummy, $return_var);

          JoomFile::chmod($dir, '0755', true);

          // Check that the resized image is valid
          if(!self::checkValidImage($dst_file))
          {
            $filecheck = false;
          }

          if($return_var != 0 || !$filecheck)
          {
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';
            self::rollback($src_file, $dst_file);

            return false;
          }
        }
        break;
      default:
        $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_UNSUPPORTED_METHOD').'<br />';
        self::rollback($src_file, $dst_file);

        return false;
        break;
    }

    // Set mode of uploaded picture
    JPath::setPermissions($dst_file);

    //$tmp_nmb_frames = count(self::$src_frames);
    //$debugoutput .= 'number of frames: '.$tmp_nmb_frames.'<br/>';
    //$tmp_memory_used = round(memory_get_peak_usage(true) / 1048576,3);
    //$debugoutput .= 'used memory: '.$tmp_memory_used.' MB<br/>';

    // Check, if file exists and is a valid image
    if(self::checkValidImage($dst_file))
    {
      if(JFile::exists($src_file.'bak'))
      {
        JFile::delete($src_file.'bak');
      }

      if(JFile::exists($dst_file.'bak'))
      {
        JFile::delete($dst_file.'bak');
      }

      return true;
    }
    else
    {
      $debugoutput .= JText::sprintf('COM_JOOMGALLERY_COMMON_ERROR_WATERMARKING_IMAGE', $src_file).'<br />';
      self::rollback($src_file, $dst_file);

      return false;
    }
  }

  /**
   * Validation and analysis of an image-file
   *
   * @param   string    $img        Path to the image file
   * @return  array     Imageinfo on success, false otherwise
   * @since   3.5.0
   */
  public static function analyseImage($img)
  {
    // Check, if file exists and is a valid image
    if(!(self::checkValidImage($img)))
    {
      return false;
    }

    $info    = getimagesize($img);
    $imginfo = self::$src_imginfo;

    // Extract width and height from info
    $imginfo['width']  = $info[0];
    $imginfo['height'] = $info[1];

    // Extract bits and channels from info
    if(isset($info['bits']))
    {
      $imginfo['bits'] = $info['bits'];
    }

    if(isset($info['channels']))
    {
      $imginfo['channels'] = $info['channels'];
    }

    // Decrypt the imagetype
    $imagetype = array(0=>'UNKNOWN', 1 => 'GIF', 2 => 'JPG', 3 => 'PNG', 4 => 'SWF',
                       5 => 'PSD', 6 => 'BMP', 7 => 'TIFF', 8 => 'TIFF', 9 => 'JPC',
                       10 => 'JP2', 11 => 'JPX', 12 => 'JB2', 13 => 'SWC', 14 => 'IFF',
                       15=>'WBMP', 16=>'XBM', 17=>'ICO', 18=>'WEBP', 19=>'COUNT');

    $imginfo['type'] = $imagetype[$info[2]];

    // Get the image orientation
    if($info[0] > $info[1])
    {
      $imginfo['orientation'] = 'landscape';
    }
    else
    {
      if($info[0] < $info[1])
      {
        $imginfo['orientation'] = 'portrait';
      }
      else
      {
        $imginfo['orientation'] = 'square';
      }
    }

    // Detect, if image is a special image
    if($imginfo['type'] == 'PNG')
    {
      // Detect, if png has transparency
      $pngtype = ord(@file_get_contents($img, NULL, NULL, 25, 1));

      if($pngtype == 4 || $pngtype == 6)
      {
        $imginfo['transparency'] = true;
      }
    }

    // Detect, if image is a animated or transparency webp image
    if($imginfo['type'] == 'WEBP')
    {
      // Detect, if WebP is animated
      $webptype = file_get_contents($img);
      $included = strpos($webptype, "ANMF");
      if($included !== FALSE)
      {
        // animated WebP is not supported by gdlib
        $imginfo['animation'] = true;
      }
      else
      {
        $imginfo['animation'] = false;

        // Detect, if webp has transparency
        $included = strpos($webptype, "ALPH");
        if($included !== FALSE)
        {
          $imginfo['transparency'] = true;
        }
        else
        {
          $included = strpos($webptype, "VP8L");
          if($included !== FALSE)
          {
            $imginfo['transparency'] = true;
          }
        }
      }
    }

    if($imginfo['type'] == 'GIF')
    {
      // Detect, if gif is animated
      $fh    = @fopen($img, 'rb');
      $count = 0;

      while(!feof($fh) && $count < 2)
      {
        $chunk  = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
      }

      fclose($fh);

      // Detect, if gif has transparency
      $tmp       = imagecreatefromgif($img);
      $tmp_trans = imagecolortransparent($tmp);

      if($count > 1 && $tmp_trans == -1)
      {
        // Load GifFrameExtractor-Class
        JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifframeextractor.php');

        $imginfo['animation'] = true;

        $gfe = new GifFrameExtractor();
        $gfe->parseFramesInfo($img);
        $imginfo['frames'] = $gfe->getNumberFramens();
      }
      else
      {
        if($count > 1 && $tmp_trans >= 0)
        {
          // Load GifFrameExtractor-Class
          JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/gifframeextractor.php');

          $imginfo['animation']    = true;
          $imginfo['transparency'] = true;

          $gfe = new GifFrameExtractor();
          $gfe->parseFramesInfo($img);
          $imginfo['frames'] = $gfe->getNumberFramens();
        }
        else
        {
          if($count <= 1 && $tmp_trans >= 0)
          {
            $imginfo['transparency'] = true;
          }
        }
      }
    }

    list($imginfo['exif'],$imginfo['iptc'],$imginfo['comment']) = self::readImageMetadata($img);

    return $imginfo;
  }

  /**
   * Copy image metadata depending on file type (Supported: JPG,PNG / EXIF,IPTC)
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @param   string  $src_imagetype   Type of the source image file
   * @param   string  $dst_imgtype     Type of the destination image file
   * @param   int     $new_orient      New exif orientation (false: do not change exif orientation)
   * @param   boolean $bak             true, if a backup-file should be created when $src_file=$dst_file
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  public static function copyImageMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient = false, $bak = false)
  {
    $backupFile = false;

    if($src_file == $dst_file && $bak)
    {
      if(!JFile::copy($src_file, $src_file.'bak'))
      {
        return false;
      }

      $backupFile = true;
      $src_file   = $src_file.'bak';
    }

    if($src_imagetype == 'JPG' && $dst_imgtype == 'JPG')
    {
      $success = self::copyJPGmetadata($src_file,$dst_file,$new_orient);
    }
    else
    {
      if($src_imagetype == 'PNG' && $dst_imgtype == 'PNG')
      {
        $success = self::copyPNGmetadata($src_file,$dst_file);
      }
      else
      {
        // In all other cases dont copy metadata
        $success = true;
      }
    }

    if($backupFile)
    {
      JFile::delete($src_file);
    }

    return $success;
  }


  //////////////////////////////////////////////////
  //   Protected functions with basic features.
  //   Can be used of the public functions
  //   of JoomIMGtools
  //////////////////////////////////////////////////

  /**
   * Clears the class variables and bring it back to default
   *
   * @return  boolean   true on success, false otherwise
   * @since   3.5.0
  */
  protected static function clearVariables()
  {
    self::$src_imginfo  = array('width' => 0,'height' => 0,'type' => '','orientation' => '','exif' => array('IFD0' => array(),'EXIF' => array()),
                                'iptc' => array(),'comment' => '','transparency' => false,'animation' => false, 'frames' => 1);
    self::$dst_imginfo  = array('width' => 0,'height' => 0,'type' => '','orientation' => '', 'offset_x' => 0,'offset_y' => 0,'angle' => 0,
                                'flip' => 'none','quality' => 100,'src' => array('width' => 0,'height' => 0));
    self::$src_frames   = array(array('duration' => 0,'image' => null));
    self::$dst_frames   = array(array('duration' => 0,'image' => null));
  }

  /**
   * Check image if it is a valid image file
   *
   * @param   string    $img          Path to image file
   * @return  boolean   True if image is valid, false otherwise
   * @since   3.5.0
  */
  protected static function checkValidImage($img)
  {
    // Path must point to an existing file
    if(!(JFile::exists($img)))
    {
      return false;
    }

    $imginfo = getimagesize($img);

    // Image needs to have a valid file type
    if(!$imginfo || $imginfo[2] == 0 || !key_exists('mime',$imginfo) || $imginfo['mime'] == '')
    {
      return false;
    }

    // If available, bits has to be between 1 and 64
    if(key_exists('bits',$imginfo))
    {
      if($imginfo['bits'] < 1 || $imginfo['bits'] > 64)
      {
        return false;
      }
    }

    // Get width and height from $imginfo[3]
    $str    = explode(' ', $imginfo[3]);
    $width  = explode('=', $str[0]);
    $width  = $width[1];
    $width  = str_replace('"', '', $width);
    $height = explode('=', $str[1]);
    $height = $height[1];
    $height = str_replace('"', '', $height);

    // Image width and height as to be between 1 and 1'000'000 pixel
    if( $width < 1 || $height < 1 || $imginfo[0] < 1 || $imginfo[1] < 1
        ||
        $width > 1000000 || $height > 1000000 || $imginfo[0] > 1000000  || $imginfo[1] > 1000000
      )
    {
      return false;
    }

    return true;
  }

  /**
   * Calculates the amaount of memory (in bytes)
   * needed for manipulating a one-frame image with GD
   *
   * @param   array   $src_imginfo      array with source image informations
   * @param   array   $dst_imginfo      array with destination image informations
   * @param   string  $method           manipulation method (resize or rotate)
   * @return  int     memory needed
   * @since   3.5.0
   */
  protected static function calculateMemory($src_imginfo, $dst_imginfo, $method)
  {
    $config = JoomConfig::getInstance();

    // Quantify number of bits per channel
    if(key_exists('bits',$src_imginfo))
    {
      $bits = $src_imginfo['bits'];
    }
    else
    {
      $bits = 8;
    }

    // Check, if it is a special image (transparency or animation)
    $special = false;
    if($src_imginfo['animation'] || $src_imginfo['transparency'])
    {
      $special = true;
    }

    // Check, if GD2 is available
    $gd2 = false;
    if(function_exists('imagecopyresampled'))
    {
      $gd2 = true;
    }

    // Quantify the tweakfactor coefficients and the number of channels per pixel
    // based on image-type and manipulation method
    // Formula for tweakfactor calculation: tweakfactor = m*pixel + c
    switch($src_imginfo['type'])
    {
      case 'GIF':
        switch($method)
        {
          case 'resize':
            if(!$src_imginfo['animation'])
            {
              // Tweakfactor dependent on number of pixels (~0.5)
              $m = -0.0000000020072;
              $c = 0.60054;
            }
            else
            {
              // Tweakfactor dependent on number of pixels (~1.8)
              $m = -0.000000030872;
              $c = 1.9125;
            }
            break;
          case 'rotate':
            if(!$src_imginfo['animation'])
            {
              // Tweakfactor dependent on number of pixels (~3.0)
              $m = -0.000000019085;
              $c = 2.70515;
            }
            else
            {
              // Tweakfactor dependent on number of pixels (~2.3)
              $m = -0.00000006174;
              $c = 2.41248;
            }
            break;
          default:
            // Constant tweakfactor of 0.5
            $m = 0;
            $c = 0.5;
            break;
        }

        // GIF has always 3 channels (RGB)
        $channels = 3;
        break;
      case 'JPG':
      case 'JPEG':
      case 'JPE':
        switch($method)
        {
          case 'resize':
            if($config->get('jg_fastgd2thumbcreation') && $gd2 && !$special)
            {
              // Tweakfactor dependent on number of pixels (~1.6)
              $m = -0.000000007157;
              $c = 2.00193;
            }
            else
            {
              // Tweakfactor dependent on number of pixels (~1.5)
              $m = -0.000000003579;
              $c = 1.60097;
            }
            break;
          case 'rotate':
            // Tweakfactor dependent on number of pixels (~3.0)
            $m = -0.000000007157;
            $c = 3.2019;
            break;
          default:
            // Constant tweakfactor of 1.5
            $m = 0;
            $c = 1.5;
            break;
        }

        // Get channel number from imginfo
        $channels = $src_imginfo['channels'];
        break;
      case 'PNG':
        switch($method)
        {
          case 'resize':
            // Tweakfactor dependent on number of pixels (~2.5)
            $m = -0.000000007157;
            $c = 2.70193;
            break;
          case 'rotate':
            // Tweakfactor dependent on number of pixels (~3.3)
            $m = -0.000000011928;
            $c = 3.50322;
            break;
          default:
            // Constant tweakfactor of 2.5
            $m = 0;
            $c = 2.5;
            break;
        }

        // PNG has always 3 channels (RGB)
        $channels = 3;
        break;
      case 'WEBP':
// Todo
        switch($method)
        {
          case 'resize':
            // Tweakfactor dependent on number of pixels (~2.5)
            $m = -0.000000007157;
            $c = 2.70193;
            break;
          case 'rotate':
            // Tweakfactor dependent on number of pixels (~3.3)
            $m = -0.000000011928;
            $c = 3.50322;
            break;
          default:
            // Constant tweakfactor of 2.5
            $m = 0;
            $c = 2.5;
            break;
        }

        // WEBP has always ??
        $channels = 3;
        break;
    }

    // Pixel calculation for source and destination GD-Frame
    $src_pixel = $src_imginfo['width'] * $src_imginfo['height'];
    $dst_pixel = $dst_imginfo['width'] * $dst_imginfo['height'];

    $securityfactor = 1.08;
    $powerfactor    = 1.02;
    $tweakfactor    = self::tweakfactor($m, $c, $src_pixel);

    $oneMB = 1048576;

    $memoryUsage = round(( ((($bits * $channels) / 8) * $src_pixel * $tweakfactor + (($bits * $channels) / 8) * $dst_pixel * $tweakfactor)
                           * pow($src_imginfo['frames'], $powerfactor) + 2 * $oneMB
                          ) * $securityfactor);

    // Calculate needed memory in bytes (1byte = 8bits).
    // We need to calculate the usage for both source and destination GD-Frame
    return $memoryUsage;
  }

  /**
   * Calculates the tweakfactor for the GD memory usage.
   *
   * @param   double  $m         linear dependency coefficient
   * @param   double  $c         constant coefficient
   * @param   int     $pixel     number of pixels of the frame
   * @return  double  tweakfactor
   * @since   3.5.0
   */
  protected static function tweakfactor($m, $c, $pixel)
  {
    return ($m * $pixel) + $c;
  }

  /**
   * Calculates whether there is enough memory
   * to work on a specific image.
   *
   * @param   int     $memory_needed    memory (in bytes) that is needed for manipulating
   * @return  array   True, if we have enough memory to work, false and memory info otherwise
   * @since   3.5.0
   */
  protected static function checkMemory($memory_needed)
  {
    $config = JoomConfig::getInstance();
    if($config->get('jg_thumbcreation') == 'im')
    {
      // ImageMagick isn't dependent on memory_limit
      return array('success' => true);
    }

    $byte_values = array('K' => 1024, 'M' => 1048576, 'G' => 1073741824);

    if((function_exists('memory_get_usage')) && (ini_get('memory_limit')))
    {
      $memoryNeeded = memory_get_usage() + $memory_needed;

      // Get memory limit in bytes
      $memory_limit = @ini_get('memory_limit');
      if(!empty($memory_limit) && $memory_limit != 0)
      {
        $val          = substr($memory_limit, -1);
        $memory_limit = substr($memory_limit, 0, -1) * $byte_values[$val];
      }

      if($memory_limit != 0 && $memoryNeeded > $memory_limit)
      {
        $memoryNeededMB = round($memoryNeeded / $byte_values['M'], 0);
        $memoryLimitMB  = round($memory_limit / $byte_values['M'], 0);

        return array('success' => false, 'needed' => $memoryNeededMB, 'limit' => $memoryLimitMB);
      }
    }

    return array('success' => true);
  }

  /**
   * Collect informations for the resize (informations: dimensions, type, origin)
   *
   * Cropping function adapted from
   * 'Resize Image with Different Aspect Ratio'
   * Author: Nash
   * Website: http://nashruddin.com/Resize_Image_to_Different_Aspect_Ratio_on_the_fly
   *
   *
   * @param   string  $dst_img          Path of destination image file
   * @param   int     $settings         Resize to 0=width,1=height,2=crop or 3=max(width,height)
   * @param   int     $new_width        Width to resize
   * @param   int     $new_height       Height to resize
   * @param   int     $cropposition     Only if $settings=2; image section to use for cropping
   * @return  bool    true on success, false otherwise
   * @since   3.5.0
   */
  protected static function getResizeInfo($dst_img, $settings, $new_width, $new_height, $cropposition)
  {
    // Get the desired image type out of the destination path
    $tmp     = explode('.', $dst_img);
    $imgtype = strtolower(end($tmp));

    if($imgtype == 'jpg' || $imgtype == 'jpeg' || $imgtype == 'jpe' || $imgtype == 'jif' || $imgtype == 'jfif' || $imgtype == 'jfi')
    {
      self::$dst_imginfo['type'] = 'JPG';
    }
    else
    {
      if($imgtype == 'gif')
      {
        self::$dst_imginfo['type'] = 'GIF';
      }
      else
      {
        if($imgtype == 'png')
        {
          self::$dst_imginfo['type'] = 'PNG';
        }
        else
        {
          if($imgtype == 'webp')
          {
            self::$dst_imginfo['type'] = 'WEBP';
          }
          else
          {
            self::$dst_imginfo['type'] = 'UNKNOWN';

            return false;
          }
        }
      }
    }

    // Height/width
    if(self::$dst_imginfo['angle'] == 0 || self::$dst_imginfo['angle'] == 180)
    {
      $srcWidth  = self::$src_imginfo['width'];
      $srcHeight = self::$src_imginfo['height'];
    }
    else
    {
      $srcWidth  = self::$src_imginfo['height'];
      $srcHeight = self::$src_imginfo['width'];
    }

    switch($settings)
    {
    case 0:
      // no resizing
      $ratio = 1;
    case 1:
      // calculate ratio by height
      $ratio = ($srcHeight / $new_height);

      break;
    case 2:
      // calculate ratio by width
      $ratio = ($srcWidth / $new_width);

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
      $ratiowidth  = $srcWidth / $new_width;
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
          self::$dst_imginfo['offset_x'] = 0;
          self::$dst_imginfo['offset_y'] = 0;
          break;
        case 1:
          // Right upper corner
          self::$dst_imginfo['offset_x'] = (int)floor(($srcWidth - ($new_width * $ratio)));
          self::$dst_imginfo['offset_y'] = 0;
          break;
        case 3:
          // Left lower corner
          self::$dst_imginfo['offset_x'] = 0;
          self::$dst_imginfo['offset_y'] = (int)floor(($srcHeight - ($new_height * $ratio)));
          break;
        case 4:
          // Right lower corner
          self::$dst_imginfo['offset_x'] = (int)floor(($srcWidth - ($new_width * $ratio)));
          self::$dst_imginfo['offset_y'] = (int)floor(($srcHeight - ($new_height * $ratio)));
          break;
        default:
          // Default center
          self::$dst_imginfo['offset_x'] = (int)floor(($srcWidth - ($new_width * $ratio)) * 0.5);
          self::$dst_imginfo['offset_y'] = (int)floor(($srcHeight - ($new_height * $ratio)) * 0.5);
          break;
      }
      break;
    case 4:
      // Resize to maximum allowed dimensions but keeping original ratio
      // calculate ratio by height
      $ratio     = ($srcHeight / $new_height);
      $testwidth = ($srcWidth / $ratio);

      // If new width exceeds setted max. width
      if($testwidth > $new_width)
      {
        // calculate ratio by width
        $ratio = ($srcWidth / $new_width);
      }
      break;
    default:
      echo 'undefined "settings"-parameter!';

      return false;
    }

    // Calculate widths and heights necessary for resize and bring them to integer values
    if($settings != 3)
    {
      // Not cropping
      $ratio                               = max($ratio, 1.0);
      self::$dst_imginfo['width']          = (int)floor($srcWidth / $ratio);
      self::$dst_imginfo['height']         = (int)floor($srcHeight / $ratio);
      self::$dst_imginfo['src']['width']   = (int)$srcWidth;
      self::$dst_imginfo['src']['height']  = (int)$srcHeight;

    }
    else
    {
      // Cropping
      self::$dst_imginfo['width']         = (int)$new_width;
      self::$dst_imginfo['height']        = (int)$new_height;
      self::$dst_imginfo['src']['width']  = (int)(self::$dst_imginfo['width'] * $ratio);
      self::$dst_imginfo['src']['height'] = (int)(self::$dst_imginfo['height'] * $ratio);
    }

    return true;
  }

  /**
   * Collect informations for the watermarking (informations: dimensions, type, position)
   *
   * @param   array   $imginfo        array with image informations of the background image
   * @param   int     $position       Positioning of the watermark
   * @param   int     $resize         resize watermark (0:no,1:by height,2:by width)
   * @param   float   $new_size       new size of the resized watermark in percent related to the file (1-100)
   * @return  array   array with watermark positions; array(x,y)
   * @since   3.6.0
   */
  protected static function getWatermarkingInfo($imginfo, $position, $resize, $new_size)
  {
    // generate information about the new width and height
    if($resize)
    {

      if($new_size <= 0)
      {
        $new_size = 1;
      }
      elseif($new_size > 100)
      {
        $new_size = 100;
      }

      $widthwm  = self::$src_imginfo['width'];
      $heightwm = self::$src_imginfo['height'];

      if($resize == 1)
      {
        // Resize by height
        $newheight_watermark = $imginfo['height'] * $new_size / 100;
        $newwidth_watermark  = $newheight_watermark * $widthwm / $heightwm;

        if($newwidth_watermark > $imginfo['width'])
        {
          $newwidth_watermark  = $imginfo['width'];
        }
      }
      else
      {
        // Resize by width
        $newwidth_watermark  = $imginfo['width'] * $new_size / 100;
        $newheight_watermark = $newwidth_watermark * $heightwm / $widthwm;

        if($newheight_watermark > $imginfo['height'])
        {
          $newheight_watermark = $imginfo['height'];
        }
      }
    }
    else
    {
      $newwidth_watermark = self::$src_imginfo['width'];
      $newheight_watermark = self::$src_imginfo['height'];
    }
    self::$dst_imginfo['width']  = (int) round($newwidth_watermark);
    self::$dst_imginfo['height'] = (int) round($newheight_watermark);

    // Other informations of the resized watermark image
    self::$dst_imginfo['type']          = self::$src_imginfo['type'];
    self::$dst_imginfo['orientation']   = self::$src_imginfo['orientation'];
    self::$dst_imginfo['src']['width']  = self::$src_imginfo['width'];
    self::$dst_imginfo['src']['height'] = self::$src_imginfo['height'];

    // Generate informations about position of the watermark inside the src image
    // Position x
    switch(($position - 1) % 3)
    {
      case 1:
        $pos_x = round(($imginfo['width'] - self::$dst_imginfo['width']) / 2, 0);
        break;
      case 2:
        $pos_x = $imginfo['width'] - self::$dst_imginfo['width'];
        break;
      default:
        $pos_x = 0;
        break;
    }
    // Position y
    switch(floor(($position - 1) / 3))
    {
      case 1:
        $pos_y = round(($imginfo['height'] - self::$dst_imginfo['height']) / 2, 0);
        break;
      case 2:
        $pos_y = $imginfo['height'] - self::$dst_imginfo['height'];
        break;
      default:
        $pos_y = 0;
        break;
    }

    return array($pos_x, $pos_y);
  }

  /**
   * Get angle and flip value based on exif orientation tag
   *
   * @param   int       $orientation    exif-orientation
   * @return  boolean   true on success
   * @since   3.5.0
  */
  protected static function autoOrient($orientation = 1)
  {
    switch($orientation)
    {
      case 1: // Do nothing!
        self::$dst_imginfo['flip']  = 'none';
        self::$dst_imginfo['angle'] = 0;
        break;
      case 2: // Flip horizontally
        self::$dst_imginfo['flip']  = 'horizontally';
        self::$dst_imginfo['angle'] = 0;
        break;
      case 3: // Rotate 180 degrees
        self::$dst_imginfo['flip']  = 'none';
        self::$dst_imginfo['angle'] = 180;
        break;
      case 4: // Flip vertically
        self::$dst_imginfo['flip']  = 'vertically';
        self::$dst_imginfo['angle'] = 0;
        break;
      case 5: // Rotate 90 degrees clockwise and flip vertically
        self::$dst_imginfo['flip']  = 'vertically';
        self::$dst_imginfo['angle'] = 270;
        break;
      case 6: // Rotate 90 clockwise
        self::$dst_imginfo['flip']  = 'none';
        self::$dst_imginfo['angle'] = 270;
        break;
      case 7: // Rotate 90 clockwise and flip horizontally
        self::$dst_imginfo['flip']  = 'horizontally';
        self::$dst_imginfo['angle'] = 270;
        break;
      case 8: // Rotate 90 anticlockwise
        self::$dst_imginfo['flip']  = 'none';
        self::$dst_imginfo['angle'] = 90;
        break;
      default:
        self::$dst_imginfo['flip']  = 'none';
        self::$dst_imginfo['angle'] = 0;
        break;
    }

    return true;
  }

  /**
   * Get exif orientation tag based on rotation angle
   *
   * @param   int       $angle       rotation angle (anticlockwise)
   * @return  int       exif-orientation
   * @since   3.5.0
  */
  protected static function exifOrient($angle = 0)
  {
    switch($angle)
    {
      case 0: // Do nothing!
        $orientation = false;
        break;
      case 90: // Rotate 90 anticlockwise
        $orientation = 8;
        break;
      case 180: // Rotate 180 degrees
        $orientation = 3;
        break;
      case 270: // Rotate 270 anticlockwise
        $orientation = 6;
        break;
      case 360: // Rotate 360 anticlockwise
        $orientation = 1;
        break;
      case -90: // Rotate 90 clockwise
        $orientation = 6;
        break;
      case -180: // Rotate 180 degrees
        $orientation = 3;
        break;
      case -270: // Rotate 270 clockwise
        $orientation = 8;
        break;
      case -360: // Rotate 360 clockwise
        $orientation = 1;
        break;
      default: // in all other cases
        $orientation = false;
        break;
    }

    return $orientation;
  }

  /**
   * Creates GD image objects from different file types with one frame
   * Supported: JPG, PNG, GIF, WEBP
   *
   * @param   string  $src_file     Path to source file
   * @param   array   $imginfo      array with source image informations
   * @return  array   $src_frame[0: ["durtion": 0, "image": GDobject]] on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageCreateFrom_GD($src_file, $src_imginfo)
  {
    $src_frame = array(array('duration'=>0));

    switch ($src_imginfo['type'])
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
      case 'WEBP':
        if($src_imginfo['animation'] == true)
        {
          // not supported by gdlib
          return false;
        }
        else
        {
          $src_frame[0]['image'] = imagecreatefromwebp($src_file);
        }
        break;
      default:
        return false;
        break;
    }

    // Convert pallete images to true color images
    if(function_exists('imagepalettetotruecolor') && $src_imginfo['type'] != 'GIF')
    {
      imagepalettetotruecolor($src_frame[0]['image']);
    }

    return $src_frame;
  }

  /**
   * Creates empty GD image object optionally with transparent background
   *
   * @param   object  $src_frame    GDobject of the source image file
   * @param   array   $dst_imginfo  array with destination image informations
   * @param   boolean $transparency true = transparent background
   * @return  object  empty GDobject on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageCreateEmpty_GD($src_frame, $dst_imginfo, $transparency=true)
  {
    // Create empty GD-Object
    if(function_exists('imagecreatetruecolor'))
    {
      // Needs at least php v4.0.6
      $src_frame = imagecreatetruecolor($dst_imginfo['width'], $dst_imginfo['height']);
    }
    else
    {
      $src_frame = imagecreate($dst_imginfo['width'], $dst_imginfo['height']);
    }

    if($transparency)
    {
      // Set transparent background
      switch ($dst_imginfo['type'])
      {
        case 'GIF':
          if(function_exists('imagecolorallocatealpha'))
          {
            // Needs at least php v4.3.2
            $trnprt_color = imagecolorallocatealpha($src_frame, 0, 0, 0, 127);
            imagefill($src_frame, 0, 0, $trnprt_color);
            imagecolortransparent($src_frame, $trnprt_color);
          }
          else
          {
            $trnprt_indx = imagecolortransparent($src_frame);
            $palletsize  = imagecolorstotal($src_frame);

            if($trnprt_indx >= 0 && $trnprt_indx < $palletsize)
            {
              $trnprt_color = imagecolorsforindex($src_frame, $trnprt_indx);
              $trnprt_indx  = imagecolorallocate($src_frame, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
              imagefill($src_frame, 0, 0, $trnprt_indx);
              imagecolortransparent($src_frame, $trnprt_indx);
            }
          }
        break;
        case 'PNG':
          if(function_exists('imagecolorallocatealpha'))
          {
            // Needs at least php v4.3.2
            imagealphablending($src_frame, false);
            $trnprt_color = imagecolorallocatealpha($src_frame, 0, 0, 0, 127);
            imagefill($src_frame, 0, 0, $trnprt_color);
          }
          break;
        case 'WEBP':
          if(function_exists('imagecolorallocatealpha'))
          {
            // Needs at least php v4.3.2
            imagealphablending($src_frame, false);
            $trnprt_color = imagecolorallocatealpha($src_frame, 0, 0, 0, 127);
            imagefill($src_frame, 0, 0, $trnprt_color);
          }
        break;
        default:
          $src_frame = false;

          return $src_frame;
        break;
      }
    }
    else
    {
      // Set black background
      imagefill($src_frame, 0, 0, imagecolorallocate($src_frame, 0, 0, 0));
    }

    return $src_frame;
  }

  /**
   * Output GD image object to file from different file types with one frame
   * Supported: JPG, PNG, GIF, WEBP
   *
   * @param   string  $dst_file     Path to destination file
   * @param   array   $dst_frame    array with one GD object for one frame ; array(array('duration'=>0, 'image'=>GDobject))
   * @param   int     $dst_qual     Quality of the image to be saved (1-100)
   * @param   string  $dst_imgtype  Type of the destination image file
   * @return  boolean True on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageWriteFrom_GD($dst_file, $dst_frame, $dst_imginfo)
  {
    switch ($dst_imginfo['type'])
    {
      case 'PNG':
        // Calculate png quality, since it should be between 1 and 9
        $png_qual = ($dst_imginfo['quality'] - 100) / 11.111111;
        $png_qual = round(abs($png_qual));

        // Save transparency -- needs at least php v4.3.2
        imagealphablending($dst_frame[0]['image'], false);
        imagesavealpha($dst_frame[0]['image'], true);

        // Enable interlancing (progressive image transmission)
        //imageinterlace($im, true);

        // Write file
        $success = imagepng($dst_frame[0]['image'], $dst_file, $png_qual);
        break;
      case 'GIF':
        // Write file
        $success = imagegif($dst_frame[0]['image'], $dst_file);
        break;
      case 'JPG':
        // Enable interlancing (progressive image transmission)
        //imageinterlace($im, true);

        // Write file
        $success = imagejpeg($dst_frame[0]['image'], $dst_file, $dst_imginfo['quality']);
        break;
      case 'WEBP':
        // Save transparency -- needs at least php v4.3.2
        imagealphablending($dst_frame[0]['image'], false);
        imagesavealpha($dst_frame[0]['image'], true);

        // Write file
        $success = imagewebp($dst_frame[0]['image'], $dst_file, $dst_imginfo['quality']);
        break;
      default:
        $success = false;
        break;
    }

    return $success;
  }

  /**
   * Flip GD image object by specified direction
   *
   * @param   object   $img_frame    GDobject of the image to flip
   * @param   string   $direction    direction in witch the image gets flipped
   * @return  mixed    flipped GDobject on success, false otherwise
   * @since   3.5.0
  */
  protected static function imageFlip_GD($img_frame, $direction)
  {
    switch($direction)
    {
      case 'horizontally':
        $success = imageflip($img_frame, IMG_FLIP_HORIZONTAL);
        break;
      case 'vertically':
        $success = imageflip($img_frame, IMG_FLIP_VERTICAL);
        break;
      case 'both':
        $success = imageflip($img_frame, IMG_FLIP_BOTH);
        break;
      case 'none':
        // 'break' intentionally omitted
      default:
        $success = true;
        break;
    }

    if($success)
    {
      return $img_frame;
    }
    else
    {
      return false;
    }
  }

  /**
   * Copy GD image object resource from one frame to another
   *
   * @param   object  $dst_img       GDobject of the destination image-frame
   * @param   object  $src_img       GDobject of the source image-frame
   * @return  mixed   copied GDobject on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageCopy_GD($dst_img, $src_img)
  {
    // Get width from image.
    $w = imagesx($src_img);

    // Get height from image.
    $h = imagesy($src_img);

    // Copy the image
    imagecopy($dst_img, $src_img, 0, 0, 0, 0, $w, $h);

    return $dst_img;
  }

  /**
   * Rotate GD image object by specified rotation angle
   *
   * @param   object     $img_frame     GDobject of the image to rotate
   * @param   string     $type          image file type
   * @param   int        $angle         rotation angle (anticlockwise)
   * @param   boolean    $transparency  transparent background color instead of black
   * @return  mixed      rotated GDobject on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageRotate_GD($img_frame, $type, $angle, $transparency)
  {
    if($angle == 0)
    {
      return $img_frame;
    }

    // Set background color of the rotated GDobject
    if($transparency)
    {
      if(function_exists('imagecolorallocatealpha'))
      {
        $backgroundColor = imagecolorallocatealpha($img_frame, 0, 0, 0, 127);
      }
    }
    else
    {
      $backgroundColor = imagecolorallocate($img_frame, 0, 0, 0);
    }

    // Rotate image
    $new_img = imagerotate($img_frame, $angle, $backgroundColor);

    // Keeping transparency
    if($transparency)
    {
      switch ($type)
      {
        case 'PNG':
          // Special threatment for png files
          if(function_exists('imagealphablending'))
          {
            imagealphablending($new_img, false);
            imagesavealpha($new_img, true);
          }
          break;
        case 'WEBP':
          // Special threatment for webp files
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
   * Watermark GD image object (copy watermark on top of image)
   *
   * @param   object     $img_frame     GDobject of the image
   * @param   object     $wtm_frame     GDobject of the watermark
   * @param   array      $imginfo       array with image informations
   * @param   array      $wtminfo       array with watermark informations
   * @param   array      $position      position (in pixel) of watermark on image, array(x,y)
   * @param   integer    $opacity       opacity of the watermark in percent (0-100)
   * @return  mixed      watermarked GDobject on success, false otherwise
   * @since   3.6.0
   */
  protected static function imageWatermark_GD($img_frame, $wtm_frame, $imginfo, $wtminfo, $position, $opacity)
  {
    // temporary transparent empty plane in the size of the image
    $tmp = null;
    $tmpinfo = $imginfo;
    $tmpinfo['type'] = $wtminfo['type'];

    // Create empty GD-Object
    $tmp = self::imageCreateEmpty_GD($tmp, $tmpinfo, true);

    // positioning watermark
    if(function_exists('imagecopyresampled'))
    {
      imagecopyresampled($tmp, $wtm_frame, $position[0], $position[1], 0, 0, $wtminfo['width'], $wtminfo['height'],$wtminfo['width'], $wtminfo['height']);
    }
    else
    {
      imagecopy($tmp, $wtm_frame, $position[0], $position[1], 0, 0, $wtminfo['width'], $wtminfo['height']);
    }

    // make sure background is still transparent
    if(function_exists('imagecolorallocatealpha'))
    {
      // Needs at least php v4.3.2
      $trnprt_color = imagecolorallocatealpha($tmp, 0, 0, 0, 127);
      imagefill($tmp, 0, 0, $trnprt_color);
      imagecolortransparent($tmp, $trnprt_color);
    }
    else
    {
      $trnprt_indx = imagecolortransparent($tmp);
      $palletsize  = imagecolorstotal($tmp);

      if($trnprt_indx >= 0 && $trnprt_indx < $palletsize)
      {
        $trnprt_color = imagecolorsforindex($tmp, $trnprt_indx);
        $trnprt_indx  = imagecolorallocate($tmp, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
        imagefill($tmp, 0, 0, $trnprt_indx);
        imagecolortransparent($tmp, $trnprt_indx);
      }
    }

    // copy resized watermark on image
    self::imageCopyMergeAlpha_GD($img_frame, $tmp, 0, 0, 0, 0, $imginfo['width'], $imginfo['height'], $opacity);

    return $img_frame;
  }

  /**
   * Resize GD image based on infos from $dst_imginfo
  *
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
   * Optional "fast_quality" parameter (defaults is 3). Fractional values are allowed,
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
   * @param   object  $dst_img       GDobject of the destination image-frame
   * @param   object  $src_img       GDobject of the source image-frame
   * @param   array   $src_imginfo   array with source image informations
   * @param   array   $dst_imginfo   array with destination image informations
   * @param   boolean $fast_resize   resize with fastImageCopyResampled()
   * @param   int     $fast_quality  quality of destination (fix = 3) read instructions above
   * @return  mixed   rotated GDobject on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageResize_GD($dst_frame, $src_frame, $src_imginfo, $dst_imginfo, $fast_resize = true, $fast_quality = 3)
  {
    // Check, if it is a special image (transparency or animation)
    $special = false;

    if($src_imginfo['animation'] || $src_imginfo['transparency'])
    {
      $special = true;
    }

    // Check, if GD2 is available
    $gd2 = false;
    if(function_exists('imagecopyresampled'))
    {
      $gd2 = true;
    }

    // Encode $dst_imginfo
    $dst_x = 0;
    $dst_y = 0;
    $src_x = $dst_imginfo['offset_x'];
    $src_y = $dst_imginfo['offset_y'];
    $dst_w = $dst_imginfo['width'];
    $dst_h = $dst_imginfo['height'];
    $src_w = $dst_imginfo['src']['width'];
    $src_h = $dst_imginfo['src']['height'];


    // Perform the resize
    if($gd2 && $fast_resize && !$special && $fast_quality < 5 && (($dst_w * $fast_quality) < $src_w || ($dst_h * $fast_quality) < $src_h))
    {
      // fastimagecopyresampled
      $temp = imagecreatetruecolor($dst_w * $fast_quality + 1, $dst_h * $fast_quality + 1);
      imagecopyresized($temp, $src_frame, 0, 0, $src_x, $src_y, $dst_w * $fast_quality + 1,$dst_h * $fast_quality + 1, $src_w, $src_h);
      imagecopyresampled($dst_frame, $temp, $dst_x, $dst_y, 0, 0, $dst_w,$dst_h, $dst_w * $fast_quality, $dst_h * $fast_quality);
      imagedestroy($temp);
    }
    else
    {
      // Normal resizing
      if($gd2)
      {
        imagecopyresampled($dst_frame, $src_frame, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
      }
      else
      {
        imagecopyresized($dst_frame, $src_frame, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
      }
    }

    return $dst_frame;
  }

    /**
   * Same as PHP's imagecopymerge, but works with transparent images. Used internally for overlay.
   * Source: https://github.com/claviska/SimpleImage/blob/93b6df27e1d844a90d52d21a200d91b16371af0f/src/claviska/SimpleImage.php#L482
   *
   * @param resource $dstIm Destination image link resource.
   * @param resource $srcIm Source image link resource.
   * @param integer $dstX x-coordinate of destination point.
   * @param integer $dstY y-coordinate of destination point.
   * @param integer $srcX x-coordinate of source point.
   * @param integer $srcY y-coordinate of source point.
   * @param integer $srcW Source width.
   * @param integer $srcH Source height.
   * @param integer $pct
   * @return boolean true if success.
   * @since   3.6.0
   */
  protected static function imageCopyMergeAlpha_GD($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $pct)
  {
    // Are we merging with transparency?
    if($pct < 100 && function_exists('imagefilter'))
    {
      // Disable alpha blending and "colorize" the image using a transparent color
      imagealphablending($srcIm, false);
      imagefilter($srcIm, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * ((100 - $pct) / 100));
    }

    if(function_exists('imagecopyresampled'))
    {
      imagecopyresampled($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH, $srcW, $srcH);
    }
    else
    {
      imagecopy($dstIm, $srcIm, $dstX, $dstY, $srcX, $srcY, $srcW, $srcH);
    }

    return true;
  }

  /**
   * Unsharp Mask for PHP
   * Source: https://github.com/trepmag/unsharp-mask
   *
   * @param   resource   $img         GDobject of the image to filter (has to be truecolor)
   * @param   int        $amount      Amount of increasing the sharpness (0-500%)
   * @param   int        $radius      Radius of neighboring pixels that the filter affects (0-50)
   * @param   float      $threshold   How different in value an area must be to be affected (0-255)
   * @return  resource true if success.
   * @since   3.6.0
   */
  protected static function unsharpMask_GD($img, $amount, $radius, $threshold)
  {
    // Attempt to calibrate the parameters to Photoshop:
    if($amount > 500)
      $amount = 500;
    $amount = $amount * 0.016;

    if($radius > 50)
      $radius = 50;
    $radius = $radius * 2;

    if($threshold > 255)
      $threshold = 255;

    $radius = abs(round($radius));     // Only integers make sense.

    if($radius == 0)
    {
      return $img;
    }

    $w = imagesx($img);
    $h = imagesy($img);

    if(function_exists('imagecreatetruecolor'))
    {
      $imgCanvas = imagecreatetruecolor($w, $h);
      $imgBlur = imagecreatetruecolor($w, $h);
    }
    else
    {
      $imgCanvas = imagecreate($w, $h);
      $imgBlur = imagecreate($w, $h);
    }


    // Gaussian blur matrix:
    //
    //    1    2    1
    //    2    4    2
    //    1    2    1
    //
    //////////////////////////////////////////////////


    if(function_exists('imageconvolution'))
    { // PHP >= 5.1
      $matrix = array(
        array(1, 2, 1),
        array(2, 4, 2),
        array(1, 2, 1)
      );

      if(function_exists('imagecopyresampled'))
      {
        imagecopyresampled($imgBlur, $img, 0, 0, 0, 0, $w, $h, $w, $h);
      }
      else
      {
        imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
      }

      imageconvolution($imgBlur, $matrix, 16, 0);
    }
    else
    {

      // Move copies of the image around one pixel at the time and merge them with weight
      // according to the matrix. The same matrix is simply repeated for higher radii.
      for($i = 0; $i < $radius; $i++)
      {
        if(function_exists('imagecopyresampled'))
        {
          imagecopyresampled($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h, $w - 1, $h); // left
        }
        else
        {
          imagecopy($imgBlur, $img, 0, 0, 1, 0, $w - 1, $h); // left
        }
        self::imageCopyMergeAlpha_GD($imgBlur, $img, 1, 0, 0, 0, $w, $h, 50); // right
        self::imageCopyMergeAlpha_GD($imgBlur, $img, 0, 0, 0, 0, $w, $h, 50); // center
        if(function_exists('imagecopyresampled'))
        {
          imagecopyresampled($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h, $w, $h);
        }
        else
        {
          imagecopy($imgCanvas, $imgBlur, 0, 0, 0, 0, $w, $h);
        }
        self::imageCopyMergeAlpha_GD($imgBlur, $imgCanvas, 0, 0, 0, 1, $w, $h - 1, 33.33333); // up
        self::imageCopyMergeAlpha_GD($imgBlur, $imgCanvas, 0, 1, 0, 0, $w, $h, 25); // down
      }
    }

    if($threshold > 0)
    {
      // Calculate the difference between the blurred pixels and the original
      // and set the pixels
      for($x = 0; $x < $w - 1; $x++)
      { // each row
        for($y = 0; $y < $h; $y++)
        { // each pixel
          $rgbOrig = ImageColorAt($img, $x, $y);
          $rOrig = (($rgbOrig >> 16) & 0xFF);
          $gOrig = (($rgbOrig >> 8) & 0xFF);
          $bOrig = ($rgbOrig & 0xFF);

          $rgbBlur = ImageColorAt($imgBlur, $x, $y);

          $rBlur = (($rgbBlur >> 16) & 0xFF);
          $gBlur = (($rgbBlur >> 8) & 0xFF);
          $bBlur = ($rgbBlur & 0xFF);

          // When the masked pixels differ less from the original
          // than the threshold specifies, they are set to their original value.
          $rNew = (abs($rOrig - $rBlur) >= $threshold) ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig)) : $rOrig;
          $gNew = (abs($gOrig - $gBlur) >= $threshold) ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig)) : $gOrig;
          $bNew = (abs($bOrig - $bBlur) >= $threshold) ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig)) : $bOrig;



          if(($rOrig != $rNew) || ($gOrig != $gNew) || ($bOrig != $bNew))
          {
            $pixCol = ImageColorAllocate($img, $rNew, $gNew, $bNew);
            ImageSetPixel($img, $x, $y, $pixCol);
          }
        }
      }
    }
    else
    {
      for($x = 0; $x < $w; $x++)
      { // each row
        for($y = 0; $y < $h; $y++)
        { // each pixel
          $rgbOrig = ImageColorAt($img, $x, $y);
          $rOrig = (($rgbOrig >> 16) & 0xFF);
          $gOrig = (($rgbOrig >> 8) & 0xFF);
          $bOrig = ($rgbOrig & 0xFF);

          $rgbBlur = ImageColorAt($imgBlur, $x, $y);

          $rBlur = (($rgbBlur >> 16) & 0xFF);
          $gBlur = (($rgbBlur >> 8) & 0xFF);
          $bBlur = ($rgbBlur & 0xFF);

          $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
          if($rNew > 255)
          {
            $rNew = 255;
          }
          elseif($rNew < 0)
          {
            $rNew = 0;
          }
          $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
          if($gNew > 255)
          {
            $gNew = 255;
          }
          elseif($gNew < 0)
          {
            $gNew = 0;
          }
          $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
          if($bNew > 255)
          {
            $bNew = 255;
          }
          elseif($bNew < 0)
          {
            $bNew = 0;
          }
          $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
          ImageSetPixel($img, $x, $y, $rgbNew);
        }
      }
    }

    imagedestroy($imgCanvas);
    imagedestroy($imgBlur);

    return $img;
  }

  /**
   * Output image to screen and close script
   * (use for debugging only)
   *
   * @param   resource   $img   GDobject of the image to filter (has to be truecolor)
   * @param   string     $type  Image type (PNG, GIF or JPG)
   *
   * @return  display image on success.
   * @since   3.6.0
   */
  protected static function dump_GD($img, $type)
  {
    ob_start();

    switch ($type)
    {
      case 'PNG':
        // Save transparency -- needs at least php v4.3.2
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $src = 'image/png';
        imagepng($img);
        break;
      case 'WEBP':
        // Save transparency -- needs at least php v4.3.2
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $src = 'image/webp';
        imagepng($img);
        break;
      case 'GIF':
        $src = 'image/gif';
        imagegif($img);
        break;
      case 'JPG':
        $src = 'image/jpeg';
        imagejpeg($img);
        break;
      default:
        $src = 'image/jpeg';
        imagejpeg($img);
        break;
    }

    imagedestroy($img);
    $i = ob_get_clean();

    echo "<img src='data:".$src.";base64," . base64_encode( $i )."'>";

    die;
  }

  /**
   * Read image metadata from image
   *
   * @param   string  img  The image file to read
   * @return  mixed   List of three arrays on success, false otherwise. list($exif, $iptc, $comment)
   * @since   3.5.0
   */
  protected static function readImageMetadata($img)
  {
    $exif    = self::$src_imginfo['exif'];
    $comment = self::$src_imginfo['comment'];
    $iptc    = self::$src_imginfo['iptc'];

    $size = getimagesize($img, $info);

    // Check the installation of Exif
    if(extension_loaded('exif') && function_exists('exif_read_data') && $size[2] == 2)
    {
      // Read EXIF data (only JPG)
      $exif_tmp = exif_read_data($img, null, 1);

      if(isset($exif_tmp['IFD0']))
      {
        $exif['IFD0'] = $exif_tmp['IFD0'];
      }

      if(isset($exif_tmp['EXIF']))
      {
        $exif['EXIF'] = $exif_tmp['EXIF'];
      }

      // Read COMMENT
      if(isset($exif_tmp['COMMENT']) && isset($exif_tmp['COMMENT'][0]))
      {
        $comment = $exif_tmp['COMMENT'][0];
      }
    }

    // Get IPTC data
    if(isset($info["APP13"]))
    {
      $iptc_tmp = iptcparse($info['APP13']);

      foreach($iptc_tmp as $key => $value)
      {
        $iptc[$key] = $value[0];
      }
    }

    return array($exif, $iptc, $comment);
  }

  /**
   * Copy IPTC and EXIF Data of a jpg from source to destination image
   *
   * function adapted from
   * Author: ebashkoff
   * Website: https://www.php.net/manual/de/function.iptcembed.php
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @param   int     $new_orient      New exif orientation (false: do not change exif orientation)
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  protected static function copyJPGmetadata($src_file, $dst_file, $new_orient = false)
  {
    // Function transfers EXIF (APP1) and IPTC (APP13) from $src_file and adds it to $dst_file
    // JPEG file has format 0xFFD8 + [APP0] + [APP1] + ... [APP15] + <image data> where [APPi] are optional
    // Segment APPi (where i=0x0 to 0xF) has format 0xFFEi + 0xMM + 0xLL + <data> (where 0xMM is
    // most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
    // of (strlen(<data>) + 2)

    if(file_exists($src_file) && file_exists($dst_file))
    {
      $srcsize = @getimagesize($src_file, $imageinfo);
      $dstsize = @getimagesize($dst_file, $destimageinfo);

      // Check if file is jpg
      if($srcsize[2] != 2 && $dstsize[2] != 2)
      {
        return false;
      }

      // Prepare EXIF data bytes from source file
      $exifdata = (is_array($imageinfo) && key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
      if($exifdata)
      {
        // Find the image's original orientation flag, and change it to $new_oreint value
        if($new_orient != false)
        {
          list($success, $exifdata) = self::replace_exif_orientation($exifdata, $new_orient);
          if(!$success)
          {
            return false;
          }
        }

        $exiflength = strlen($exifdata) + 2;
        if($exiflength > 0xFFFF)
        {
          return false;
        }

        // Construct EXIF segment
        $exifdata = chr(0xFF) . chr(0xE1) . chr(($exiflength >> 8) & 0xFF) . chr($exiflength & 0xFF) . $exifdata;
      }

      // Prepare IPTC data bytes from source file
      $iptcdata = (is_array($imageinfo) && key_exists("APP13", $imageinfo)) ? $imageinfo['APP13'] : null;
      if($iptcdata)
      {
        $iptclength = strlen($iptcdata) + 2;
        if($iptclength > 0xFFFF)
        {
          return false;
        }

        // Construct IPTC segment
        $iptcdata = chr(0xFF) . chr(0xED) . chr(($iptclength >> 8) & 0xFF) . chr($iptclength & 0xFF) . $iptcdata;
      }

      // Check destination file
      $destfilecontent = @file_get_contents($dst_file);
      if(!$destfilecontent)
      {
        return false;
      }

      if(strlen($destfilecontent) > 0)
      {
        $destfilecontent = substr($destfilecontent, 2);
        $portiontoadd    = chr(0xFF) . chr(0xD8);          // Variable accumulates new & original IPTC application segments
        $exifadded       = !$exifdata;
        $iptcadded       = !$iptcdata;

        while((self::get_safe_chunk(substr($destfilecontent, 0, 2)) & 0xFFF0) === 0xFFE0)
        {
          $segmentlen        = (self::get_safe_chunk(substr($destfilecontent, 2, 2)) & 0xFFFF);
          $iptcsegmentnumber = (self::get_safe_chunk(substr($destfilecontent, 1, 1)) & 0x0F);   // Last 4 bits of second byte is IPTC segment #

          if($segmentlen <= 2)
          {
            return false;
          }

          $thisexistingsegment = substr($destfilecontent, 0, $segmentlen + 2);

          if((1 <= $iptcsegmentnumber) && (!$exifadded))
          {
            $portiontoadd .= $exifdata;
            $exifadded     = true;

            if(1 === $iptcsegmentnumber)
            {
              $thisexistingsegment = '';
            }
          }

          if((13 <= $iptcsegmentnumber) && (!$iptcadded))
          {
            $portiontoadd .= $iptcdata;
            $iptcadded     = true;

            if(13 === $iptcsegmentnumber)
            {
              $thisexistingsegment = '';
            }
          }

          $portiontoadd   .= $thisexistingsegment;
          $destfilecontent = substr($destfilecontent, $segmentlen + 2);
        }

        if(!$exifadded) $portiontoadd .= $exifdata;  //  Add EXIF data if not added already
        if(!$iptcadded) $portiontoadd .= $iptcdata;  //  Add IPTC data if not added already

        // $outputfile = fopen($dst_file, 'w');
        // if($outputfile)
        // {
        //   return fwrite($outputfile, $portiontoadd . $destfilecontent);
        // }
        // else
        // {
        //   return false;
        // }
        return file_put_contents($dst_file, $portiontoadd . $destfilecontent);
      }
      else
      {
        // Destination file doesn't exist
        return false;
      }
    }
    else
    {
      // Source file doesn't exist
      return false;
    }
  }

  /**
   * Copy iTXt, tEXt and zTXt chunks of a png from source to destination image
   *
   * read chunks; adapted from
   * Author: Andrew Moore
   * Website: https://stackoverflow.com/questions/2190236/how-can-i-read-png-metadata-from-php
   *
   * write chunks; adapted from
   * Author: leonbloy
   * Website: https://stackoverflow.com/questions/8842387/php-add-itxt-comment-to-a-png-image
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  protected static function copyPNGmetadata($src_file, $dst_file)
  {
    if(file_exists($src_file) && file_exists($dst_file))
    {
      $_src_chunks = array ();
      $_fp         = fopen($src_file, 'r');
      $chunks      = array ();

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
        {
          $_src_chunks[$chunk['type']] = array ();
        }

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
          if($chunk['size'] > 0)
          {
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
      $_dfp = file_get_contents($dst_file);
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

      return file_put_contents($dst_file, $png);
    }
    else
    {
      // File doesn't exist
      return false;
    }
  }

  /**
   * Restore initial state, if something goes wrong
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  protected static function rollback($src_file, $dst_file)
  {
    // Destroy GD-Objects id there are any
    foreach(self::$src_frames as $key => $frame)
    {
      if(!empty(self::$src_frames[$key]['image']))
      {
        imagedestroy(self::$src_frames[$key]['image']);
      }

      if(!empty(self::$dst_frames[$key]['image']) && self::$dst_frames[$key]['image'] != self::$src_frames[$key]['image'])
      {
        imagedestroy(self::$dst_frames[$key]['image']);
      }
    }

    // Restore src from backup file or delete corrupt dst file
    if($src_file == $dst_file)
    {
      if(JFile::exists($src_file.'bak'))
      {
        JFile::copy($src_file.'bak',$src_file);
        JFile::delete($src_file.'bak');
      }
    }
    else
    {
      if(JFile::exists($dst_file.'bak'))
      {
        JFile::copy($dst_file.'bak',$dst_file);
        JFile::delete($dst_file.'bak');
      }
      else
      {
        if(JFile::exists($dst_file))
        {
          JFile::delete($dst_file);
        }
      }
    }

    self::clearVariables();

    return true;
  }

  /**
   * Check, if there are any errors
   * Error: if there is a false in $value
   *
   * @param   mixed   $value    variable to be checked for errors (any datatype except Object)
   * @return  boolean true, if there are any errors. False otherwise
   * @since   3.5.0
   */
  protected static function checkError($value)
  {
    if(is_array($value))
    {
      return self::in_array_r(false, $value);
    }
    else
    {
      if($value == false)
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Replaces the actual exif orientation tag in
   * a given exifdata string
   *
   * @param   string  $exifdata    binary APP1-Segement of image header (TIFF or JFIF)
   *                               ( usually received by getimagesize($file, $imginfo); $imginfo['APP1'] )
   * @param   int     $newVal      numeric value for the new orientation
   * @return  array   [1]: true on success false otherwise / [2]: modified $exifdata on success, debuginfo otherwise
   * @since   3.5.0
   */
  protected static function replace_exif_orientation($exifdata, $newVal)
  {
    $IFD_Data_Sizes = array(1 => 1,         // Unsigned Byte
                            2 => 1,         // ASCII String
                            3 => 2,         // Unsigned Short
                            4 => 4,         // Unsigned Long
                            5 => 8,         // Unsigned Rational
                            6 => 1,         // Signed Byte
                            7 => 1,         // Undefined
                            8 => 2,         // Signed Short
                            9 => 4,         // Signed Long
                            10 => 8,        // Signed Rational
                            11 => 4,        // Float
                            12 => 8 );      // Double

    $tmp_folder = JFactory::getApplication()->get('tmp_path');
    $tmp_file   = $tmp_folder.'/tmp.txt';

    if(isset($exifdata))
    {
      file_put_contents($tmp_file, $exifdata);
    }

    $filehnd = @fopen($tmp_file, 'rb');

    // Check if the file opened successfully
    if(!$filehnd)
    {
      // Delete file
      unlink($tmp_file);

      // Could't open the file - exit
      return array(false, 'Could not open file: ' . $tmp_file);
    }

    // Overstep the EXIF header
    fseek($filehnd, 6);

    // Read the eight bytes of the TIFF header
    $DataStr = self::network_safe_fread($filehnd, 8);

    // Check that we did get all eight bytes
    if(strlen($DataStr) != 8)
    {
      // Delete file
      unlink($tmp_file);

      // Couldn't read the TIFF header properly
      return array(false, 'Couldnt read the TIFF header - EXIF is probably Corrupted');
    }

    $pos = 0;
    // First two bytes indicate the byte alignment - should be 'II' or 'MM'
    // II = Intel (LSB first, MSB last - Little Endian)
    // MM = Motorola (MSB first, LSB last - Big Endian)
    $Byte_Align = substr($DataStr, $pos, 2);

    // Check the Byte Align Characters for validity
    if(($Byte_Align != "II") && ($Byte_Align != "MM"))
    {
      // Delete file
      unlink($tmp_file);

      // Byte align field is invalid - we won't be able to decode file
      return array(false, 'Byte align field is invalid - EXIF is probably Corrupted');
    }

    // Skip over the Byte Align field which was just read
    $pos += 2;

    // Next two bytes are TIFF ID - should be value 42 with the appropriate byte alignment
    $TIFF_ID = substr($DataStr, $pos, 2);

    if(self::get_IFD_Data_Type($TIFF_ID, 3, $Byte_Align) != 42)
    {
      // Delete file
      unlink($tmp_file);

      // TIFF header ID not found
      return array(false, 'TIFF header ID not found - EXIF is probably Corrupted');
    }

    // Skip over the TIFF ID field which was just read
    $pos += 2;

    // Next four bytes are the offset to the first IFD
    $offset_str = substr($DataStr, $pos, 4);
    $offset     = self::get_IFD_Data_Type($offset_str, 4, $Byte_Align);

    // Done reading TIFF Header

    // Move to first IFD: IFD0

    // First 2 bytes of IFD0 are number of entries in the IFD
    $No_Entries_str = self::network_safe_fread($filehnd, 2);
    $No_Entries     = self::get_IFD_Data_Type($No_Entries_str, 3, $Byte_Align);

    // If the data is corrupt, the number of entries may be huge, which will cause errors
    // This is often caused by a lack of a Next-IFD pointer
    if ($No_Entries > 10000 || $No_Entries == 0)
    {
      // Delete file
      unlink($tmp_file);

      // Huge number of entries - abort
      return array(false, 'Huge number of EXIF entries - EXIF is probably Corrupted');
    }

    // Initialise current position to the start
    $pos = ftell($filehnd);

    // Read in the IFD structure
    $IFD_Data = self::network_safe_fread($filehnd, 12 * $No_Entries);

    // Check if the entire IFD was able to be read
    if(strlen($IFD_Data) != (12 * $No_Entries))
    {
      // Delete file
      unlink($tmp_file);

      // Couldn't read the IFD Data properly, Some Casio files have no Next IFD pointer, hence cause this error
      return array(false, 'Couldnt read the IFD Data properly - EXIF is probably Corrupted');
    }

    // Last 4 bytes of a standard IFD are the offset to the next IFD
    // Some NON-Standard IFD implementations do not have this, hence causing problems if it is read

    // Loop through the IFD entries and get the position of the orientation entry
    for($i = 0; $i < $No_Entries; $i++)
    {
      fseek($filehnd, $pos);

      // First 2 bytes of IFD entry are the tag number ( Unsigned Short )
      $Tag_No_str = self::network_safe_fread($filehnd, 2);
      $Tag_No     = self::get_IFD_Data_Type($Tag_No_str, 3, $Byte_Align);

      if($Tag_No == 274)
      {
        $pos_274 = $pos;
        fseek($filehnd, $pos + 12);
        $pos = ftell($filehnd);
      }
      else
      {
        fseek($filehnd, $pos + 12);
        $pos = ftell($filehnd);
      }
    }

    // go to the orientation-entry position
    fseek($filehnd, $pos_274);

    // First 2 bytes of IFD entry are the tag number ( Unsigned Short )
    $orient_Tag_No_str = self::network_safe_fread($filehnd, 2);
    $orient_Tag_No     = self::get_IFD_Data_Type($orient_Tag_No_str, 3, $Byte_Align);

    // Next 2 bytes of IFD entry are the data format ( Unsigned Short )
    $orient_Data_Type_str = self::network_safe_fread($filehnd, 2);
    $orient_Data_Type     = self::get_IFD_Data_Type($orient_Data_Type_str, 3, $Byte_Align);

    // If Datatype is not between 1 and 12, then skip this entry, it is probably corrupted or custom
    if(($orient_Data_Type > 12) || ($orient_Data_Type < 1))
    {
      // Delete file
      unlink($tmp_file);

      return array(false, 'Couldnt identify Datatype - EXIF is probably Corrupted');
    }

    // Next 4 bytes of IFD entry are the data count ( Unsigned Long )
    $orient_Data_Count_str = self::network_safe_fread($filehnd, 4);
    $orient_Data_Count     = self::get_IFD_Data_Type($orient_Data_Count_str, 4, $Byte_Align);

    if($orient_Data_Count > 100000)
    {
      // Delete file
      unlink($tmp_file);

      return array(false, 'Huge number of IFD-Entries - EXIF is probably Corrupted');
    }

    // Total Data size is the Data Count multiplied by the size of the Data Type
    $orient_Total_Data_Size = $IFD_Data_Sizes[ $orient_Data_Type ] * $orient_Data_Count;

    if($orient_Total_Data_Size > 4)
    {
      // Delete file
      unlink($tmp_file);

      return array(false, 'To big data-size for EXIF-Orientation tag! - EXIF is probably Corrupted');
    }
    else
    {
      // The data block is less than 4 bytes, and is provided in the IFD entry, so read it
      $orient_DataStr = self::network_safe_fread($filehnd, $orient_Total_Data_Size);
    }

    // Read the data items from the data block
    if ($orient_Data_Type == 1 || $orient_Data_Type == 3 || $orient_Data_Type == 4)
    {
      $orient_Data = self::get_IFD_Data_Type($orient_DataStr, $orient_Data_Type, $Byte_Align);
    }
    else
    {
      // Delete file
      unlink($tmp_file);

      return array(false, 'Couldnt identify Datatype - EXIF is probably Corrupted');
    }

    // Finish reading file
    fclose($filehnd);

    // Open file for writing
    $filewrite = fopen($tmp_file, 'cb');

    // Go to the data block of the IFD0->274 entry (exif orientation)
    fseek($filewrite, $pos_274 + 2 + 2 + 4);

    // Bring $newVal to binary
    $newVal_bin = self::put_IFD_Data_Type($newVal, $orient_Data_Type, $Byte_Align);

    // Write new orientation to file
    fwrite($filewrite, $newVal_bin);

    // Finish writing file
    fclose($filewrite);

    // Read file to string
    $new_exifdata = file_get_contents($tmp_file);

    // Delete file
    unlink($tmp_file);

    return array(true, $new_exifdata);
  }

   /**
   * in_array() for multidimensional array
   * Source: https://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
   *
   * @param   string   $needle        The searched value
   * @param   array    $haystack      The array to be searched
   * @param   boolean  $strict        If true it will also check the types of the needle in the haystack
   * @return  boolean  true if needle is found in the array, false otherwise
   * @since   3.5.0
   */
  protected static function in_array_r($needle, $haystack, $strict = true)
  {
    foreach($haystack as $item)
    {
      if(($strict ? $item === $needle : $item == $needle) || (is_array($item) && self::in_array_r($needle, $item, $strict)))
      {
        return true;
      }
    }

    return false;
  }

  /**
   * Get integer value of binary chunk.
   * Source: https://plugins.trac.wordpress.org/browser/image-watermark/tags/1.6.6#image-watermark.php#line:954
   *
   * @param   mixed   $value  Binary data
   * @return  int     int value of binary data
   */
  protected static function get_safe_chunk($value)
  {
    // Check for numeric value
    if(is_numeric($value))
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
   * Retrieves data from a file. This function is required since
   * the fread function will not always return the requested number
   * of characters when reading from a network stream or pipe
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * @param   object  $file_handle   File system pointer that is typically created using fopen()
   * @param   int     $length        Number of bytes read
   * @return  string  the data read from the file
   * @since   3.5.0
   */
  protected static function network_safe_fread($file_handle, $length)
  {
    // Create blank string to receive data
    $data = "";

    // Keep reading data from the file until either EOF occurs or we have
    // retrieved the requested number of bytes

    while((!feof($file_handle)) && (strlen($data) < $length))
    {
      $data .= fread($file_handle, $length-strlen($data));
    }

    // Return the data read
    return $data;
  }

  /**
   * Decodes an IFD field from a value to a binary data string, using
   * information supplied about the data type and byte alignment of
   * the stored data.
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * Data Types TIFF 6.0 specification:
   *  1 = Unsigned 8-bit Byte
   *  2 = ASCII String
   *  3 = Unsigned 16-bit Short
   *  4 = Unsigned 32-bit Long
   *  5 = Unsigned 2x32-bit Rational
   *  6 = Signed 8-bit Byte
   *  7 = Undefined
   *  8 = Signed 16-bit Short
   *  9 = Signed 32-bit Long
   *  10 = Signed 2x32-bit Rational
   *  11 = 32-bit Float
   *  12 = 64-bit Double
   *
   * Byte alignment indicators:
   *  MM = Motorola, MSB first, Big Endian
   *  II = Intel, LSB first, Little Endian
   *
   * @param   string  $input_data    Binary data string containing the IFD value, must be exact length of the value.
   * @param   int     $data_type     Number representing the IFD datatype (see above)
   * @param   string  $Byte_Align    Indicates the byte alignment of the data.
   * @return  string  the value of the data (string or numeric)
   * @since   3.5.0
   */
  protected static function get_IFD_Data_Type($input_data, $data_type, $Byte_Align)
  {
    // Check if this is a Unsigned Byte, Unsigned Short or Unsigned Long
    if(($data_type == 1) || ($data_type == 3) || ($data_type == 4))
    {
      // This is a Unsigned Byte, Unsigned Short or Unsigned Long

      // Check the byte alignment to see if the bytes need tp be reversed
      if($Byte_Align == "II")
      {
        // This is in Intel format, reverse it
        $input_data = strrev ( $input_data );
      }

      // Convert the binary string to a number and return it
      return hexdec(bin2hex($input_data));
    }
    // Check if this is a ASCII string type
    elseif($data_type == 2)
    {
      // Null terminated ASCII string(s)
      // The input data may represent multiple strings, as the
      // 'count' field represents the total bytes, not the number of strings
      // Hence this should not be processed here, as it would have
      // to return multiple values instead of a single value

      echo "<p>Error - ASCII Strings should not be processed in get_IFD_Data_Type</p>\n";

      return "Error Should never get here"; //explode( "\x00", $input_data );
    }
    // Check if this is a Unsigned rational type
    elseif($data_type == 5)
    {
      // This is a Unsigned rational type

      // Check the byte alignment to see if the bytes need to be reversed
      if($Byte_Align == "MM")
      {
        // Motorola MSB first byte aligment
        // Unpack the Numerator and denominator and return them
        return unpack('NNumerator/NDenominator', $input_data);
      }
      else
      {
        // Intel LSB first byte aligment
        // Unpack the Numerator and denominator and return them
        return unpack('VNumerator/VDenominator', $input_data);
      }
    }
    // Check if this is a Signed Byte, Signed Short or Signed Long
    elseif(( $data_type == 6) || ($data_type == 8) || ($data_type == 9))
    {
      // This is a Signed Byte, Signed Short or Signed Long

      // Check the byte alignment to see if the bytes need to be reversed
      if($Byte_Align == "II")
      {
        //Intel format, reverse the bytes
        $input_data = strrev ($input_data);
      }

      // Convert the binary string to an Unsigned number
      $value = hexdec(bin2hex( $input_data ));

      // Convert to signed number

      // Check if it is a Byte above 128 (i.e. a negative number)
      if(($data_type == 6) && ($value > 128))
      {
        // number should be negative - make it negative
        return  $value - 256;
      }

      // Check if it is a Short above 32767 (i.e. a negative number)
      if(($data_type == 8) && ($value > 32767))
      {
        // number should be negative - make it negative
        return  $value - 65536;
      }

      // Check if it is a Long above 2147483648 (i.e. a negative number)
      if(($data_type == 9) && ($value > 2147483648))
      {
        // number should be negative - make it negative
        return  $value - 4294967296;
      }

      // Return the signed number
      return $value;
    }
    // Check if this is Undefined type
    elseif($data_type == 7)
    {
      // Custom Data - Do nothing
      return $input_data;
    }
            // Check if this is a Signed Rational type
    elseif($data_type == 10)
    {
      // This is a Signed Rational type

      // Signed Long not available with endian in unpack , use unsigned and convert

      // Check the byte alignment to see if the bytes need to be reversed
      if($Byte_Align == "MM")
      {
        // Motorola MSB first byte aligment
        // Unpack the Numerator and denominator
        $value = unpack('NNumerator/NDenominator', $input_data);
      }
      else
      {
        // Intel LSB first byte aligment
        // Unpack the Numerator and denominator
        $value = unpack('VNumerator/VDenominator', $input_data);
      }

      // Convert the numerator to a signed number
      // Check if it is above 2147483648 (i.e. a negative number)
      if($value['Numerator'] > 2147483648)
      {
        // number is negative
        $value['Numerator'] -= 4294967296;
      }

      // Convert the denominator to a signed number
      // Check if it is above 2147483648 (i.e. a negative number)
      if($value['Denominator'] > 2147483648)
      {
        // number is negative
        $value['Denominator'] -= 4294967296;
      }

      // Return the Signed Rational value
      return $value;
    }
    // Check if this is a Float type
    elseif($data_type == 11)
    {
      // IEEE 754 Float
      // TODO - EXIF - IFD datatype Float not implemented yet
      return "FLOAT NOT IMPLEMENTED YET";
    }
    // Check if this is a Double type
    elseif($data_type == 12)
    {
      // IEEE 754 Double
      // TODO - EXIF - IFD datatype Double not implemented yet
      return "DOUBLE NOT IMPLEMENTED YET";
    }
    else
    {
      // Error - Invalid Datatype
      return "Invalid Datatype $data_type";

    }
  }

   /**
   * Encodes an IFD field from a value to a binary data string, using
   * information supplied about the data type and byte alignment of
   * the stored data.
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * Data Types TIFF 6.0 specification:
   *  1 = Unsigned 8-bit Byte
   *  2 = ASCII String
   *  3 = Unsigned 16-bit Short
   *  4 = Unsigned 32-bit Long
   *  5 = Unsigned 2x32-bit Rational
   *  6 = Signed 8-bit Byte
   *  7 = Undefined
   *  8 = Signed 16-bit Short
   *  9 = Signed 32-bit Long
   *  10 = Signed 2x32-bit Rational
   *  11 = 32-bit Float
   *  12 = 64-bit Double
   *
   * Byte alignment indicators:
   *  MM = Motorola, MSB first, Big Endian
   *  II = Intel, LSB first, Little Endian
   *
   * @param   string  $input_data    IFD data value, numeric or string
   * @param   int     $data_type     Number representing the IFD datatype (see above)
   * @param   string  $Byte_Align    Indicates the byte alignment of the data.
   * @return  string  the packed binary string of the data
   * @since   3.5.0
   */
  protected static function put_IFD_Data_Type($input_data, $data_type, $Byte_Align)
  {
    // Process according to the datatype
    switch($data_type)
    {
      case 1: // Unsigned Byte - return character as is
        return chr($input_data);
        break;
      case 2: // ASCII String
        // Return the string with terminating null
        return $input_data . "\x00";
        break;
      case 3: // Unsigned Short
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the short and return
          return pack("v", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the short and return
          return pack("n", $input_data);
        }
        break;
      case 4: // Unsigned Long
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the long and return
          return pack("V", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the long and return
          return pack("N", $input_data);
        }
        break;
      case 5: // Unsigned Rational
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the two longs and return
          return pack("VV", $input_data['Numerator'], $input_data['Denominator']);
        }
        else
        {
          // Motorola/Big Endian - pack the two longs and return
          return pack("NN", $input_data['Numerator'], $input_data['Denominator']);
        }
        break;
      case 6: // Signed Byte
        // Check if number is negative
        if( $input_data < 0)
        {
          // Number is negative - return signed character
          return chr($input_data + 256);
        }
        else
        {
          // Number is positive - return character
          return chr($input_data);
        }
        break;
      case 7: // Unknown - return as is
        return $input_data;
        break;

      case 8: // Signed Short
        // Check if number is negative
        if( $input_data < 0)
        {
          // Number is negative - make signed value
          $input_data = $input_data + 65536;
        }
        // Check byte alignment
        if ($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the short and return
          return pack("v", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the short and return
          return pack("n", $input_data);
        }
        break;
      case 9: // Signed Long
        // Check if number is negative
        if( $input_data < 0)
        {
          // Number is negative - make signed value
          $input_data = $input_data + 4294967296;
        }
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the long and return
          return pack("v", $input_data);
        }
        else
        {
          // Motorola/Big Endian - pack the long and return
          return pack("n", $input_data);
        }
        break;
      case 10: // Signed Rational
        // Check if numerator is negative
        if($input_data['Numerator'] < 0)
        {
          // Number is numerator - make signed value
          $input_data['Numerator'] = $input_data['Numerator'] + 4294967296;
        }
        // Check if denominator is negative
        if($input_data['Denominator'] < 0)
        {
          // Number is denominator - make signed value
          $input_data['Denominator'] = $input_data['Denominator'] + 4294967296;
        }
        // Check byte alignment
        if($Byte_Align == "II")
        {
          // Intel/Little Endian - pack the two longs and return
          return pack("VV", $input_data['Numerator'], $input_data['Denominator']);
        }
        else
        {
          // Motorola/Big Endian - pack the two longs and return
          return pack("NN", $input_data['Numerator'], $input_data['Denominator']);
        }
        break;
      case 11: // Float
        // IEEE 754 Float
        // TODO - EXIF - IFD datatype Float not implemented yet
        return "FLOAT NOT IMPLEMENTED YET";
        break;
      case 12: // Double
        // IEEE 754 Double
        // TODO - EXIF - IFD datatype Double not implemented yet
        return "DOUBLE NOT IMPLEMENTED YET";
        break;
      default:
        // Error - Invalid Datatype
        return "Invalid Datatype $data_type";
        break;
    }

    // Shouldn't get here
    return FALSE;
  }
}
