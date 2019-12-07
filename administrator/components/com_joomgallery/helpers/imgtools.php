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
   * Holds all image informations of the source image, which are relevant for
   * image manipulation and metadata handling
   *
   * @var array
   */
  protected static $src_imginfo = array('width' => 0,'height' => 0,'type' => '','orentation' => '','exif' => array('IFD0' => array(),'EXIF' => array()),
  															 'iptc' => array(),'comment' => '','transparency' => false,'animation' => false);

  /**
   * Holds all image informations of the destination image, which are relevant for
   * image manipulation and metadata handling
   *
   * @var array
   */
  protected static $dst_imginfo = array('width' => 0,'height' => 0,'type' => '','offset_x' => 0,'offset_y' => 0,'angle' => 0,
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
	//////////////////////////////////////////////////////

	/**
   * Resize image with GD or ImageMagick
   * Supported image-types: JPG ,PNG, GIF
   *
   * Animated gif support according to ImageWorkshop
   * 'Manage animated GIF with ImageWorkshop'
   * Author: Clément Guillemain
   * Website: https://phpimageworkshop.com/tutorial/5/manage-animated-gif-with-imageworkshop.html
   *
   * @param   &string $debugoutput            Debug information
   * @param   string  $src_file               Path to source file
   * @param   string  $dst_file               Path to destination file
   * @param   int     $settings               Resize to 0=width,1=height,2=max(width,height) or 3=crop
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
   * @return  boolean True on success, false otherwise
   * @since   1.0.0
   */
  public static function resizeImage(&$debugoutput, $src_file, $dst_file, $settings, $new_width, $new_height, $method,
                                      $dst_qual = 100, $cropposition = 0, $angle = 0, $auto_orient = false, $metadata = false, $anim = false)
  {
  	self::clearVariables();

    // Ensure that the paths are valid and clean
    $src_file  = JPath::clean($src_file);
    $dst_file = JPath::clean($dst_file);

    // Analysis and validation of the source image
    if(!(self::$src_imginfo = self::analyseImage($src_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_FILE').'<br />';

      return false;
    }

    // GD can only handle JPG, PNG and GIF images
    if(    self::$src_imginfo['type'] != 'JPG'
       &&  self::$src_imginfo['type'] != 'PNG'
       &&  self::$src_imginfo['type'] != 'GIF'
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_ONLY_JPG_PNG').'<br />';

      return false;
    }

    // Analysis if available memory is enough
    $memory = self::checkMemory(self::$src_imginfo);
    if(!$memory['success'])
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_ERROR_MEM_EXCEED').
                      $memory['needed']." MByte, Serverlimit: ".$memory['limit']." MByte<br />" ;

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

    // Conditions where no resize is needed
    if(   (self::$src_imginfo['width'] <= $new_width && self::$src_imginfo['height'] <= $new_width && ($angle == 0 || $angle == 180 || $angle == -180))
        ||
          (self::$src_imginfo['height'] <= $new_width && self::$src_imginfo['width'] <= $new_width && ($angle == 270 || $angle == -270 || $angle == 90 || $angle == -90))
      )
    {
      if($src_file != $dst_file)
      {
        // If source image is already of the same size or smaller than the image
        // which shall be created only copy the source image to destination
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
        // 'break' intentionally omitted
      case 'gd2':
        if($method == 'gd2')
        {
          $debugoutput .= 'GD2...<br/>';
        }
        else
        {
          $debugoutput .= 'GD1...<br/>';
        }

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
          JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifFrameExtractor.php');
          $gfe = new GifFrameExtractor();
          self::$src_frames = $gfe->extract($src_file);

          foreach(self::$src_frames as $key => $frame)
          {
            // create empty GD-Objects for the resized frames
            self::$dst_frames[$key]['duration'] = self::$src_frames[$key]['duration'];
            self::$dst_frames[$key]['image'] = self::imageCreateEmpty_GD(self::$src_frames[$key]['image'], self::$dst_imginfo,
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
          self::$dst_frames[0]['image'] = self::imageCreateEmpty_GD(self::$src_frames[0]['image'], self::$dst_imginfo,
                                                                        self::$src_imginfo['transparency']);
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

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
        }

        if(self::$dst_imginfo['angle'] > 0)
        {
          foreach(self::$src_frames as $key => $frame)
          {
            self::$src_frames[$key]['image'] = self::imageRotate_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['type'],
                                                                    self::$dst_imginfo['angle'], self::$src_imginfo['transparency']);
            self::$src_imginfo['width'] = imagesx(self::$src_frames[$key]['image']);
            self::$src_imginfo['height'] = imagesy(self::$src_frames[$key]['image']);
          }
        }

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

          self::rollback($src_file, $dst_file);
          return false;
        }

        // Resizing with GD
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

        // Check for failures
        if(self::checkError(self::$src_frames) || self::checkError(self::$dst_frames))
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

          self::rollback($src_file, $dst_file);
          return false;
        }

        // Write resized image to file
        if($anim && self::$src_imginfo['animation'] && self::$src_imginfo['type'] == 'GIF')
        {
          // Animated GIF image (image with more than one frame)
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
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
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
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
            // change the exif orientation tag based on rotation angle
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
            // change the exif orientation tag based on rotation angle
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
        }

        // Check for failures
        if(!$success)
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

          self::rollback($src_file, $dst_file);
          return false;
        }

        // destroy GD-Objects
        foreach(self::$src_frames as $key => $frame)
        {
          imagedestroy(self::$src_frames[$key]['image']);
          imagedestroy(self::$dst_frames[$key]['image']);
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

        if(self::$src_imginfo['animation']  && !$anim)
        {
          // if resizing an animation but not preserving the animation, consider only first frame
          $src_file = $src_file.'[0]';
        }
        else
        {
          if(self::$src_imginfo['animation']  && $anim && self::$src_imginfo['type'] == 'GIF')
          {
            // if resizing an animation, use coalesce for better results
            $commands .= ' -coalesce';
          }
        }
        
        // Rotate image, if needed (use auto-orient command)
        if($auto_orient)
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
        if(!is_null(self::$dst_imginfo['offset_x']) && !is_null(self::$dst_imginfo['offset_y']))
        {
          // Assembling the imagick command for cropping
          $commands .= ' -crop "'.self::$dst_imginfo['src']['width'].'x'.self::$dst_imginfo['src']['height'].'+'.self::$dst_imginfo['offset_x'].'+'.self::$dst_imginfo['offset_y'].'" +repage';
        }

        // Assembling the imagick command for resizing
        $commands  .= ' -resize "'.self::$dst_imginfo['width'].'x'.self::$dst_imginfo['height'].'" -quality "'.self::$dst_imginfo['quality'].'" -unsharp "3.5x1.2+1.0+0.10"';
        
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

          // execute the resize
          @exec($convert, $dummy, $return_var);

          JoomFile::chmod($dir, '0755', true);

          // Check that the resized image is valid
          if(!self::checkValidImage($dst_file))
          {
            $filecheck  = false;
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
    	$debugoutput .= JText::sprintf('COM_JOOMGALLERY_COMMON_ERROR_RESIZE_IMAGE',$src_file).'<br />';

    	self::rollback($src_file, $dst_file);
      return false;
    }
  }

  /**
   * Rotate image with GD or ImageMagick
   * Supported image-types: JPG ,PNG, GIF
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
   * @param   boolean $anim     		          true=preserve animation during rotation
   * @return  boolean True on success, false otherwise
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
      return true;
    }

    // Ensure that the path is valid and clean
    $src_file  = JPath::clean($src_file);
    $dst_file = JPath::clean($dst_file);

    // Analysis and validation of the source image
    if(!(self::$src_imginfo = self::analyseImage($src_file)))
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_FILE').'<br />';

      return false;
    }

    // GD can only handle JPG, PNG and GIF images
    if(    self::$src_imginfo['type'] != 'JPG'
       &&  self::$src_imginfo['type'] != 'PNG'
       &&  self::$src_imginfo['type'] != 'GIF'
       &&  ($method == 'gd1' || $method == 'gd2')
      )
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_COMMON_ERROR_ROTATE_ONLY_JPG').'<br />';

      return false;
    }

    // Analysis if available memory is enough
    $memory = self::checkMemory(self::$src_imginfo);
    if(!$memory['success'])
    {
      $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_ERROR_MEM_EXCEED').
                      $memory['needed']." MByte, Serverlimit: ".$memory['limit']." MByte<br />" ;

      return false;
    }

    $config = JoomConfig::getInstance();

    // Definition of type, dimension and origin of rotated image
    self::$dst_imginfo['width'] = self::$dst_imginfo['src']['width'] = self::$src_imginfo['width'];
    self::$dst_imginfo['height'] = self::$dst_imginfo['src']['height'] = self::$src_imginfo['height'];
    self::$dst_imginfo['type'] = self::$src_imginfo['type'];
    self::$dst_imginfo['offset_x'] = 0;
    self::$dst_imginfo['offset_y'] =  0;
    self::$dst_imginfo['quality'] = $dst_qual;

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

    if(self::$dst_imginfo['angle'] == 0)
    {
      // Nothing to do
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

    // Create debugoutput
    $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_ROTATE_BY_ANGLE', self::$dst_imginfo['angle']) . '<br />';

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
          JLoader::register('GifFrameExtractor', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifFrameExtractor.php');
          $gfe = new GifFrameExtractor();
          self::$src_frames = $gfe->extract($src_file);

          foreach(self::$src_frames as $key => $frame)
          {
            // create empty GD-Objects for the rotated frames
            self::$dst_frames[$key]['duration'] = self::$src_frames[$key]['duration'];
            self::$dst_frames[$key]['image'] = self::imageCreateEmpty_GD(self::$src_frames[$key]['image'], self::$dst_imginfo,
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
          self::$dst_frames[0]['image'] = self::imageCreateEmpty_GD(self::$src_frames[0]['image'], self::$dst_imginfo,
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

        }
        else
        {
          if(self::$dst_imginfo['flip'] != 'none')
          {

            foreach(self::$src_frames as $key => $frame)
            {
              self::$src_frames[$key]['image'] = self::imageFlip_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['flip']);
            }
          }

          foreach(self::$src_frames as $key => $frame)
          {
            self::$dst_frames[$key]['image'] = self::imageRotate_GD(self::$src_frames[$key]['image'], self::$dst_imginfo['type'],
                                                                    self::$dst_imginfo['angle'], self::$src_imginfo['transparency']);
            self::$dst_imginfo['width'] = imagesx(self::$src_frames[$key]['image']);
            self::$dst_imginfo['height'] = imagesy(self::$src_frames[$key]['image']);
          }
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
          JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
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
            JLoader::register('GifCreator', JPATH_COMPONENT_ADMINISTRATOR . '/helpers/GifCreator.php');
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
            // change the exif orientation tag based on rotation angle
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
            // change the exif orientation tag based on rotation angle
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
        }

        // Check for failures
        if(!$success)
        {
          $debugoutput .= JText::_('COM_JOOMGALLERY_UPLOAD_GD_LIBARY_NOT_ABLE_RESIZING');

          self::rollback($src_file, $dst_file);
          return false;
        }

        // destroy GD-Objects
        foreach(self::$src_frames as $key => $frame)
        {
          imagedestroy(self::$src_frames[$key]['image']);
          imagedestroy(self::$dst_frames[$key]['image']);
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
            // if resizing an animation, use coalesce for better results
            $commands .= ' -coalesce';
          }
        }
        
        // Rotate image, if needed (use auto-orient command)
        if($auto_orient)
        {
          $commands .= ' -auto-orient';
        }
        else
        {
          if($angle > 0)
          {
            $commands .= ' -rotate "-'.$angle.'"';
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

          // execute the resize
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
            $debugoutput .= JText::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_IM_SERVERPROBLEM','exec('.$convert.');').'<br />';

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

            // copy metadata
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
      $debugoutput .= JText::sprintf('COM_JOOMGALLERY_COMMON_ERROR_ROTATE_IMAGE',$src_file).'<br />';

      self::rollback($src_file, $dst_file);
      return false;
    }
  }

  /**
   * Validation and analysis of an image file
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

    $info = getimagesize($img);
    $imginfo = self::$src_imginfo;

    // Extract width and height from info
    $imginfo['width'] = $info[0];
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
                       15=>'WBMP', 16=>'XBM', 17=>'ICO', 18=>'COUNT');

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

    if($imginfo['type'] == 'GIF')
    {
      // Detect, if gif is animated
      $fh = @fopen($img, 'rb');
      $count = 0;

      while(!feof($fh) && $count < 2)
      {
        $chunk = fread($fh, 1024 * 100); //read 100kb at a time
        $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00[\x2C\x21]#s', $chunk, $matches);
      }

      fclose($fh);

      // Detect, if gif has transparency
      $tmp = imagecreatefromgif($img);
      $tmp_trans = imagecolortransparent($tmp);

      if($count > 1 && $tmp_trans == -1)
      {
        $imginfo['animation'] = true;
      }
      else
      {
        if($count > 1 && $tmp_trans >= 0)
        {
          $imginfo['animation'] = true;
          $imginfo['transparency'] = true;
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
  	self::$src_imginfo = array('width' => 0,'height' => 0,'type' => '','orentation' => '','exif' => array('IFD0' => array(),'EXIF' => array()),
                               'iptc' => array(),'comment' => '','transparency' => false,'animation' => false);

 		self::$dst_imginfo = array('width' => 0,'height' => 0,'type' => '','offset_x' => 0,'offset_y' => 0,'angle' => 0,
  														 'flip' => 'none','quality' => 100,'src' => array('width' => 0,'height' => 0));

  	self::$src_frames = array(array('duration' => 0,'image' => null));

  	self::$dst_frames = array(array('duration' => 0,'image' => null));
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
    $str 	  = explode(' ', $imginfo[3]);
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
   * Calculates whether the memory limit is enough
   * to work on a specific image.
   *
   * @param   array   $imginfo      array with image informations
   * @return  array   True, if we have enough memory to work, false and memory info otherwise
   * @since   3.5.0
   */
  protected static function checkMemory($imginfo)
  {
    $config = JoomConfig::getInstance();
    if($config->get('jg_thumbcreation') == 'im')
    {
      // ImageMagick isn't dependent on memory_limit
      return array('success' => true);
    }

    if((function_exists('memory_get_usage')) && (ini_get('memory_limit')))
    {
      $jpgpic = false;
      switch($imginfo['type'])
      {
        case 'GIF':
          // Measured factor 1 is better
          $channel = 1;
          break;
        case 'JPG':
        case 'JPEG':
        case 'JPE':
          $channel = $imginfo['channels'];
          $jpgpic=true;
          break;
        case 'PNG':
          // No channel for png
          $channel = 3;
          break;
      }
      $MB  = 1048576;
      $K64 = 65536;

      if($config->get('jg_fastgd2thumbcreation') && $jpgpic && $config->get('jg_thumbcreation') == 'gd2')
      {
        // Function of fast gd2 creation needs more memory
        $corrfactor = 2.1;
      }
      else
      {
        $corrfactor = 1.7;
      }

      if(!key_exists('bits',$imginfo))
      {
        $imginfo['bits'] = 8;
      }

      $memoryNeeded = round(($imginfo['width']
                             * $imginfo['height']
                             * $imginfo['bits']
                             * $channel / 8
                             + $K64)
                             * $corrfactor);

      $memoryNeeded = memory_get_usage() + $memoryNeeded;
      // Get memory limit
      $memory_limit = @ini_get('memory_limit');
      if(!empty($memory_limit) && $memory_limit != 0)
      {
        $memory_limit = substr($memory_limit, 0, -1) * 1024 * 1024;
      }

      if($memory_limit != 0 && $memoryNeeded > $memory_limit)
      {
        $memoryNeededMB = round ($memoryNeeded / 1024 / 1024, 0);

        return array('success' => false, 'needed' => $memoryNeededMB, 'limit' => $memory_limit/$MB);
      }
    }

    return array('success' => true);
  }

  /**
   * Collect informations for the resize (informations: dimansions,type,origin)
   *
   * Cropping function adapted from
   * 'Resize Image with Different Aspect Ratio'
   * Author: Nash
   * Website: http://nashruddin.com/Resize_Image_to_Different_Aspect_Ratio_on_the_fly
   *
   *
   * @param   string  $dst_img          Path of destination image file
   * @param   int     $settings         Resize to 0=width,1=height,2=max(width,height) or 3=crop
   * @param   int     $new_width        Width to resize
   * @param   int     $new_height       Height to resize
   * @param   int     $cropposition     Only if $settings=3; image section to use for cropping
   * @return  array   true on success, false otherwise
   * @since   3.5.0
   */
  protected static function getResizeInfo($dst_img, $settings, $new_width, $new_height, $cropposition)
  {
    // Get the desired image type out of the destination path
    $tmp = explode('.', $dst_img);
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
          self::$dst_imginfo['type'] = 'UNKNOWN';

          return false;
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
    default:
      echo 'undefined "settings"-parameter!';
      return false;
    }

    // Calculate widths and heights necessary for resize and bring them to integer values
    //if($settings != 3 || (self::$dst_imginfo['offset_x'] == 0 && self::$dst_imginfo['offset_y'] == 0))
    if($settings != 3)
    {
      //cropping
      $ratio = max($ratio, 1.0);
      self::$dst_imginfo['width']  			  = (int)floor($srcWidth / $ratio);
      self::$dst_imginfo['height'] 			  = (int)floor($srcHeight / $ratio);
      self::$dst_imginfo['src']['width']  = (int)$srcWidth;
      self::$dst_imginfo['src']['height'] = (int)$srcHeight;
    }
    else
    {
      self::$dst_imginfo['width'] 				= (int)$new_width;
      self::$dst_imginfo['height'] 			  = (int)$new_height;
      self::$dst_imginfo['src']['width']  = (int)(self::$dst_imginfo['width'] * $ratio);
      self::$dst_imginfo['src']['height'] = (int)(self::$dst_imginfo['height'] * $ratio);
    }

    return true;
  }

  /**
   * Get angle and flip value based on exif orientation tag
   *
   * @param   int     	$orientation    exif-orientation
   * @return  boolean   true on success
   * @since   3.5.0
  */
  protected static function autoOrient($orientation=1)
  {
    switch($orientation)
    {
    case 1: // Do nothing!
    	self::$dst_imginfo['flip'] = 'none';
      self::$dst_imginfo['angle'] = 0;
      break;
    case 2: // Flip horizontally
    	self::$dst_imginfo['flip'] = 'hor';
      self::$dst_imginfo['angle'] = 0;
      break;
    case 3: // Rotate 180 degrees
      self::$dst_imginfo['flip'] = 'none';
    	self::$dst_imginfo['angle'] = 180;
      break;
    case 4: // Flip vertically
      self::$dst_imginfo['flip'] = 'vert';
      self::$dst_imginfo['angle'] = 0;
      break;
    case 5: // Rotate 90 degrees clockwise and flip vertically
    	self::$dst_imginfo['flip'] = 'vert';
    	self::$dst_imginfo['angle'] = 270;
      break;
    case 6: // Rotate 90 clockwise
      self::$dst_imginfo['flip'] = 'none';
      self::$dst_imginfo['angle'] = 270;
      break;
    case 7: // Rotate 90 clockwise and flip horizontally
    	self::$dst_imginfo['flip'] = 'hor';
      self::$dst_imginfo['angle'] = 270;
      break;
    case 8: // Rotate 90 anticlockwise
      self::$dst_imginfo['flip'] = 'none';
      self::$dst_imginfo['angle'] = 90;
      break;
    default:
    	self::$dst_imginfo['flip'] = 'none';
      self::$dst_imginfo['angle'] = 0;
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
  protected static function exifOrient($angle=0)
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
   * Supported: JPG, PNG, GIF
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
   * @param   obj     $src_frame    GDobject of the source image file
   * @param   array   $dst_imginfo  array with destination image informations
   * @param   boolen  $transparency true = transparent background
   * @return  obj     empty GDobject on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageCreateEmpty_GD($src_frame, $dst_imginfo, $transparency=true)
  {
    // Create empty GD-Object
    if(function_exists('imagecreatetruecolor'))
    {
      // needs at least php v4.0.6
      $src_frame = imagecreatetruecolor($dst_imginfo['width'], $dst_imginfo['height']);
    }
    else
    {
      $src_frame = imagecreate($dst_imginfo['width'], $dst_imginfo['height']);
    }

    if($transparency)
    {
      // Set transparent backgraound
      switch ($dst_imginfo['type'])
      {
        case 'GIF':
          if(function_exists('imagecolorallocatealpha'))
          {
            // needs at least php v4.3.2
            $trnprt_color = imagecolorallocatealpha($src_frame, 0, 0, 0, 127);
            imagefill($src_frame, 0, 0, $trnprt_color);
            imagecolortransparent($src_frame, $trnprt_color);            
          }
          else
          {
            $trnprt_indx = imagecolortransparent($src_img);
            $palletsize = imagecolorstotal($src_img);

            if($trnprt_indx >= 0 && $trnprt_indx < $palletsize)
            {
              $trnprt_color = imagecolorsforindex($src_img, $trnprt_indx);
              $trnprt_indx = imagecolorallocate($src_frame, $trnprt_color['red'], $trnprt_color['green'], $trnprt_color['blue']);
              imagefill($src_frame, 0, 0, $trnprt_indx);
              imagecolortransparent($src_frame, $trnprt_indx);
            }
          }

        break;
        case 'PNG':
          if(function_exists('imagecolorallocatealpha'))
          {
            // needs at least php v4.3.2
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
      // set black background
      imagefill($src_frame, 0, 0, imagecolorallocate($src_frame, 0, 0, 0));
    }

    return $src_frame;
  }

  /**
   * Output GD image object to file from different file types with one frame
   * Supported: JPG, PNG, GIF
   *
   * @param   string  $dst_file     Path to destination file
   * @param   array   $dst_frame    array with one GD object for one frame ; array(array('duration'=>0, 'image'=>GDobject))
   * @param   int     $dst_qual     Quality of the image to be saved (1-100)
   * @param   string  $dst_imgtype Type of the destination image file
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
      default:
        $success = false;
    }

    return $success;
  }

  /**
   * Flip GD image object by specified direction
   *
   * @param   obj     $img_frame    GDobject of the image to flip
   * @param   str     $direction    direction in witch the image gets flipped
   * @return  obj 		flipped GDobject on success, false otherwise
   * @since   3.5.0
  */
  protected static function imageFlip_GD($img_frame, $direction)
  {
  	switch($direction) {
    case 'hor':
      $new_img = imageflip($img_frame, IMG_FLIP_HORIZONTAL);
      break;
    case 'vert':
      $new_img = imageflip($img_frame, IMG_FLIP_VERTICAL);
      break;
    case 'both':
      $new_img = imageflip($img_frame, IMG_FLIP_BOTH);
      break;
    case 'none':
      $new_img = $img_frame;
      break;
    default:
    	$new_img = $img_frame;
    	break;
    }

    return $new_img;
  }

  /**
   * Rotate GD image object by specified rotation angle
   *
   * @param   obj     $img_frame     GDobject of the image to rotate
   * @param   str     $type          image file type
   * @param   int     $angle         rotation angle (anticlockwise)
   * @param   boolean $transparency  transparent background color instead of black
   * @return  obj 		rotated GDobject on success, false otherwise
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
   * @param   obj     $dst_img       GDobject of the destination image-frame
   * @param   obj     $src_img       GDobject of the source image-frame
   * @param   array   $src_imginfo   array with source image informations
   * @param   array   $dst_imginfo   array with destination image informations
   * @param   boolean $fast_resize   resize with fastImageCopyResampled()
   * @param   int     $fast_quality  quality of destination (fix = 3) read instructions above
   * @return  obj 		rotated GDobject on success, false otherwise
   * @since   3.5.0
   */
  protected static function imageResize_GD($dst_frame, $src_frame, $src_imginfo, $dst_imginfo, $fast_resize=true, $fast_quality=3)
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

    // encode $dst_imginfo
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
   * Read image metadata from image
   *
   * @param   string  img  The image file to read
   * @return  list    List of three arrays on success, false otherwise. list($exif,$iptc,$comment)
   * @since   3.5.0
   */
  protected static function readImageMetadata($img)
  {
    $exif = self::$src_imginfo['exif'];
    $comment = self::$src_imginfo['comment'];
    $iptc = self::$src_imginfo['iptc'];

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
    if(isset($info["APP13"])) {
      $iptc_tmp = iptcparse($info['APP13']);
      foreach($iptc_tmp as $key => $value) {
        $iptc[$key] = $value[0];
      }
    }

    return array($exif,$iptc,$comment);
  }

  /**
   * Copy image metadata depending on file type (Supported: JPG,PNG / EXIF,IPTC)
   *
   * @param   str     $src_file        Path to source file
   * @param   str     $dst_file        Path to destination file
   * @param   str     $src_imagetype   Type of the source image file
   * @param   str     $dst_imgtype     Type of the destination image file
   * @param   int     $new_orient      New exif orientation (false: do not change exif orientation)
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  protected static function copyImageMetadata($src_file, $dst_file, $src_imagetype, $dst_imgtype, $new_orient = false)
  {
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

    return $success;
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
    //   most significant 8 bits of (strlen(<data>) + 2) and 0xLL is the least significant 8 bits
    //   of (strlen(<data>) + 2) 

    if(file_exists($src_file) && file_exists($dst_file))
    {
        $srcsize = @getimagesize($src_file, $imageinfo);
        $dstsize = @getimagesize($dst_file, $destimageinfo);

        // Check if file is jpg
        if($srcsize[2] != 2 && $dstsize[2] != 2) return false;

        // Prepare EXIF data bytes from source file
        $exifdata = (is_array($imageinfo) && key_exists("APP1", $imageinfo)) ? $imageinfo['APP1'] : null;
        if($exifdata)
        {
          // Find the image's original orientation flag, and change it to $new_oreint value
          if($new_orient != false)
          {
            list($success, $exifdata) = self::replace_exif_orientation($exifdata, $new_orient);
            if(!$success) return false;
          }
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
          if($iptclength > 0xFFFF) return false;
          // Construct IPTC segment
          $iptcdata = chr(0xFF) . chr(0xED) . chr(($iptclength >> 8) & 0xFF) . chr($iptclength & 0xFF) . $iptcdata;
        }

        // Check destination File
        $destfilecontent = @file_get_contents($dst_file);
        if(!$destfilecontent) return false;
        if(strlen($destfilecontent) > 0)
        {
          $destfilecontent = substr($destfilecontent, 2);
          $portiontoadd = chr(0xFF) . chr(0xD8);          // Variable accumulates new & original IPTC application segments
          $exifadded = !$exifdata;
          $iptcadded = !$iptcdata;

          while((self::get_safe_chunk(substr($destfilecontent, 0, 2)) & 0xFFF0) === 0xFFE0)
          {
            $segmentlen = (self::get_safe_chunk(substr($destfilecontent, 2, 2)) & 0xFFFF);
            $iptcsegmentnumber = (self::get_safe_chunk(substr($destfilecontent, 1, 1)) & 0x0F);   // Last 4 bits of second byte is IPTC segment #
            if($segmentlen <= 2) return false;
            $thisexistingsegment = substr($destfilecontent, 0, $segmentlen + 2);
            
            if((1 <= $iptcsegmentnumber) && (!$exifadded))
            {
              $portiontoadd .= $exifdata;
              $exifadded = true;
              if(1 === $iptcsegmentnumber) $thisexistingsegment = '';
            }

            if((13 <= $iptcsegmentnumber) && (!$iptcadded))
            {
              $portiontoadd .= $iptcdata;
              $iptcadded = true;
              if(13 === $iptcsegmentnumber) $thisexistingsegment = '';
            }

            $portiontoadd .= $thisexistingsegment;
            $destfilecontent = substr($destfilecontent, $segmentlen + 2);
          }

          if(!$exifadded) $portiontoadd .= $exifdata;  //  Add EXIF data if not added already
          if(!$iptcadded) $portiontoadd .= $iptcdata;  //  Add IPTC data if not added already

          //$outputfile = fopen($dst_file, 'w');
          //if($outputfile) return fwrite($outputfile, $portiontoadd . $destfilecontent); else return false;
          return file_put_contents($dst_file, $portiontoadd . $destfilecontent);
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
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @return  int 		number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  protected static function copyPNGmetadata($src_file, $dst_file)
  {
      if(file_exists($src_file) && file_exists($dst_file))
      {
        $_src_chunks = array ();
        $_fp = fopen($src_file, 'r');
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
            if($chunk['size'] > 0) {
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
        // File dont exist
        return false;
      }
  }

  /**
   * Restore initial state, if something goes wrong
   *
   *
   * @param   string  $src_file        Path to source file
   * @param   string  $dst_file        Path to destination file
   * @return  int     number of bytes written on success, false otherwise
   * @since   3.5.0
   */
  protected static function rollback($src_file, $dst_file)
  {
    // destroy GD-Objects id there are any
    foreach(self::$src_frames as $key => $frame)
    {
      if(!empty(self::$src_frames[$key]['image']))
      {
        imagedestroy(self::$src_frames[$key]['image']);
      }

      if(!empty(self::$dst_frames[$key]['image']))
      {
        imagedestroy(self::$dst_frames[$key]['image']);
      }    
    }

    // restore src from backup file or delete corrupt dst file
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
   * @param   any     $value    variable to be checked for errors (any datatype except Object)
   * @return  boolean true, if there are any errors. False otherwise
   * @since   3.5.0
   */
  protected static function checkError($value)
  {
    if(is_array($value))
      {
        self::in_array_r(false,$value);
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
   * @param   str     $exifdata    binary APP1-Segement of image header (TIFF or JFIF)
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
    $tmp_file = $tmp_folder.'/tmp.txt';

    if(isset($exifdata))
    {
      file_put_contents($tmp_file, $exifdata);
    }

    $filehnd = @fopen($tmp_file, 'rb');

    // Check if the file opened successfully
    if(!$filehnd)
    {
      // delete file
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
      // delete file
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
      // delete file
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
      // delete file
      unlink($tmp_file);
      // TIFF header ID not found
      return array(false, 'TIFF header ID not found - EXIF is probably Corrupted');
    }

    // Skip over the TIFF ID field which was just read
    $pos += 2;

    // Next four bytes are the offset to the first IFD
    $offset_str = substr($DataStr, $pos, 4);
    $offset = self::get_IFD_Data_Type($offset_str, 4, $Byte_Align);

    // Done reading TIFF Header

    // Move to first IFD: IFD0

    // First 2 bytes of IFD0 are number of entries in the IFD
    $No_Entries_str = self::network_safe_fread($filehnd, 2);
    $No_Entries = self::get_IFD_Data_Type($No_Entries_str, 3, $Byte_Align);

    // If the data is corrupt, the number of entries may be huge, which will cause errors
    // This is often caused by a lack of a Next-IFD pointer
    if ($No_Entries > 10000 || $No_Entries == 0)
    {
      // delete file
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
      // delete file
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
      $Tag_No = self::get_IFD_Data_Type($Tag_No_str, 3, $Byte_Align);
      
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
    $orient_Tag_No = self::get_IFD_Data_Type($orient_Tag_No_str, 3, $Byte_Align);

    // Next 2 bytes of IFD entry are the data format ( Unsigned Short )
    $orient_Data_Type_str = self::network_safe_fread($filehnd, 2);
    $orient_Data_Type = self::get_IFD_Data_Type($orient_Data_Type_str, 3, $Byte_Align);

    // If Datatype is not between 1 and 12, then skip this entry, it is probably corrupted or custom
    if(($orient_Data_Type > 12) || ($orient_Data_Type < 1))
    {
      // delete file
      unlink($tmp_file);
      return array(false, 'Couldnt identify Datatype - EXIF is probably Corrupted');
    }

    // Next 4 bytes of IFD entry are the data count ( Unsigned Long )
    $orient_Data_Count_str = self::network_safe_fread($filehnd, 4);
    $orient_Data_Count = self::get_IFD_Data_Type($orient_Data_Count_str, 4, $Byte_Align);

    if($orient_Data_Count > 100000)
    {
      // delete file
      unlink($tmp_file);
      return array(false, 'Huge number of IFD-Entries - EXIF is probably Corrupted');
    }

    // Total Data size is the Data Count multiplied by the size of the Data Type
    $orient_Total_Data_Size = $IFD_Data_Sizes[ $orient_Data_Type ] * $orient_Data_Count;

    if($orient_Total_Data_Size > 4)
    {
      // delete file
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
      // delete file
      unlink($tmp_file);
      return array(false, 'Couldnt identify Datatype - EXIF is probably Corrupted');
    }

    // finish reading file
    fclose($filehnd);

    // open file for writing
    $filewrite = fopen($tmp_file, 'cb');

    // go to the data block of the IFD0->274 entry (exif orientation)
    fseek($filewrite, $pos_274 + 2 + 2 + 4);

    // bring $newVal to binary
    $newVal_bin = self::put_IFD_Data_Type($newVal, $orient_Data_Type, $Byte_Align);
    
    // write new orientation to file
    fwrite($filewrite, $newVal_bin);

    // finish writing file
    fclose($filewrite);

    // read file to string
    $new_exifdata = file_get_contents($tmp_file);

    // delete file
    unlink($tmp_file);

    return array(true, $new_exifdata);
  }

   /**
   * in_array() for multidimensional array
   * Source: https://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
   *
   * @param   string  $needle        The searched value
   * @param   array   $haystack      The array to be searched
   * @param   boolen  $strict        If true it will also check the types of the needle in the haystack
   * @return  boolean true if needle is found in the array, false otherwise
   * @since   3.5.0
   */
  protected static function in_array_r($needle, $haystack, $strict = false)
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
   * @param   bin     $value          Binary data
   * @return  int     int value of binary data
   */
  protected static function get_safe_chunk($value)
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
   * Retrieves data from a file. This function is required since
   * the fread function will not always return the requested number
   * of characters when reading from a network stream or pipe
   * Source: http://www.ozhiker.com/electronics/pjmt/
   *
   * @param   obj     $file_handle   File system pointer that is typically created using fopen()
   * @param   int     $length        Number of bytes read
   * @return  str     the data read from the file
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

    // return the data read
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
   * @param   str     $input_data    Binary data string containing the IFD value, must be exact length of the value.
   * @param   int     $data_type     Number representing the IFD datatype (see above)
   * @param   str     $Byte_Align    Indicates the byte alignment of the data.
   * @return  str     the value of the data (string or numeric)
   * @since   3.5.0
   */
  protected static function get_IFD_Data_Type($input_data, $data_type, $Byte_Align)
  {
    // Check if this is a Unsigned Byte, Unsigned Short or Unsigned Long
    if (( $data_type == 1 ) || ( $data_type == 3 ) || ( $data_type == 4 ))
    {
      // This is a Unsigned Byte, Unsigned Short or Unsigned Long

      // Check the byte alignment to see if the bytes need tp be reversed
      if ( $Byte_Align == "II" )
      {
        // This is in Intel format, reverse it
        $input_data = strrev ( $input_data );
      }

      // Convert the binary string to a number and return it
      return hexdec( bin2hex( $input_data ) );
    }
    // Check if this is a ASCII string type
    elseif ( $data_type == 2 )
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
    elseif ( $data_type == 5 )
    {
      // This is a Unsigned rational type

      // Check the byte alignment to see if the bytes need to be reversed
      if ( $Byte_Align == "MM" )
      {
        // Motorola MSB first byte aligment
        // Unpack the Numerator and denominator and return them
        return unpack( 'NNumerator/NDenominator', $input_data );
      }
      else
      {
        // Intel LSB first byte aligment
        // Unpack the Numerator and denominator and return them
        return unpack( 'VNumerator/VDenominator', $input_data );
      }
    }
    // Check if this is a Signed Byte, Signed Short or Signed Long
    elseif ( ( $data_type == 6 ) || ( $data_type == 8 ) || ( $data_type == 9 ) )
    {
      // This is a Signed Byte, Signed Short or Signed Long

      // Check the byte alignment to see if the bytes need to be reversed
      if ( $Byte_Align == "II" )
      {
        //Intel format, reverse the bytes
        $input_data = strrev ( $input_data );
      }

      // Convert the binary string to an Unsigned number
      $value = hexdec( bin2hex( $input_data ) );

      // Convert to signed number

      // Check if it is a Byte above 128 (i.e. a negative number)
      if ( ( $data_type == 6 ) && ( $value > 128 ) )
      {
        // number should be negative - make it negative
        return  $value - 256;
      }

      // Check if it is a Short above 32767 (i.e. a negative number)
      if ( ( $data_type == 8 ) && ( $value > 32767 ) )
      {
        // number should be negative - make it negative
        return  $value - 65536;
      }

      // Check if it is a Long above 2147483648 (i.e. a negative number)
      if ( ( $data_type == 9 ) && ( $value > 2147483648 ) )
      {
        // number should be negative - make it negative
        return  $value - 4294967296;
      }

      // Return the signed number
      return $value;
    }
    // Check if this is Undefined type
    elseif ( $data_type == 7 )
    {
      // Custom Data - Do nothing
      return $input_data;
    }
            // Check if this is a Signed Rational type
    elseif ( $data_type == 10 )
    {
      // This is a Signed Rational type

      // Signed Long not available with endian in unpack , use unsigned and convert

      // Check the byte alignment to see if the bytes need to be reversed
      if ( $Byte_Align == "MM" )
      {
        // Motorola MSB first byte aligment
        // Unpack the Numerator and denominator
        $value = unpack( 'NNumerator/NDenominator', $input_data );
      }
      else
      {
        // Intel LSB first byte aligment
        // Unpack the Numerator and denominator
        $value = unpack( 'VNumerator/VDenominator', $input_data );
      }

      // Convert the numerator to a signed number
      // Check if it is above 2147483648 (i.e. a negative number)
      if ( $value['Numerator'] > 2147483648 )
      {
        // number is negative
        $value['Numerator'] -= 4294967296;
      }

      // Convert the denominator to a signed number
      // Check if it is above 2147483648 (i.e. a negative number)
      if ( $value['Denominator'] > 2147483648 )
      {
        // number is negative
        $value['Denominator'] -= 4294967296;
      }

      // Return the Signed Rational value
      return $value;
    }
            // Check if this is a Float type
    elseif ( $data_type == 11 )
    {
      // IEEE 754 Float
      // TODO - EXIF - IFD datatype Float not implemented yet
      return "FLOAT NOT IMPLEMENTED YET";
    }
            // Check if this is a Double type
    elseif ( $data_type == 12 )
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
   * @param   str     $input_data    IFD data value, numeric or string
   * @param   int     $data_type     Number representing the IFD datatype (see above)
   * @param   str     $Byte_Align    Indicates the byte alignment of the data.
   * @return  str     the packed binary string of the data
   * @since   3.5.0
   */
  protected static function put_IFD_Data_Type( $input_data, $data_type, $Byte_Align )
  {
    // Process according to the datatype
    switch ( $data_type )
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
        if ( $Byte_Align == "II" )
        {
          // Intel/Little Endian - pack the short and return
          return pack( "v", $input_data );
        }
        else
        {
          // Motorola/Big Endian - pack the short and return
          return pack( "n", $input_data );
        }
        break;

      case 4: // Unsigned Long
        // Check byte alignment
        if ( $Byte_Align == "II" )
        {
          // Intel/Little Endian - pack the long and return
          return pack( "V", $input_data );
        }
        else
        {
          // Motorola/Big Endian - pack the long and return
          return pack( "N", $input_data );
        }
        break;

      case 5: // Unsigned Rational
        // Check byte alignment
        if ( $Byte_Align == "II" )
        {
          // Intel/Little Endian - pack the two longs and return
          return pack( "VV", $input_data['Numerator'], $input_data['Denominator'] );
        }
        else
        {
          // Motorola/Big Endian - pack the two longs and return
          return pack( "NN", $input_data['Numerator'], $input_data['Denominator'] );
        }
        break;

      case 6: // Signed Byte
        // Check if number is negative
        if ( $input_data < 0 )
        {
          // Number is negative - return signed character
          return chr( $input_data + 256 );
        }
        else
        {
          // Number is positive - return character
          return chr( $input_data );
        }
        break;

      case 7: // Unknown - return as is
        return $input_data;
        break;

      case 8: // Signed Short
        // Check if number is negative
        if (  $input_data < 0 )
        {
          // Number is negative - make signed value
          $input_data = $input_data + 65536;
        }
        // Check byte alignment
        if ( $Byte_Align == "II" )
        {
          // Intel/Little Endian - pack the short and return
          return pack( "v", $input_data );
        }
        else
        {
          // Motorola/Big Endian - pack the short and return
          return pack( "n", $input_data );
        }
        break;

      case 9: // Signed Long
        // Check if number is negative
        if (  $input_data < 0 )
        {
          // Number is negative - make signed value
          $input_data = $input_data + 4294967296;
        }
        // Check byte alignment
        if ( $Byte_Align == "II" )
        {
          // Intel/Little Endian - pack the long and return
          return pack( "v", $input_data );
        }
        else
        {
          // Motorola/Big Endian - pack the long and return
          return pack( "n", $input_data );
        }
        break;

      case 10: // Signed Rational
        // Check if numerator is negative
        if (  $input_data['Numerator'] < 0 )
        {
          // Number is numerator - make signed value
          $input_data['Numerator'] = $input_data['Numerator'] + 4294967296;
        }
        // Check if denominator is negative
        if (  $input_data['Denominator'] < 0 )
        {
          // Number is denominator - make signed value
          $input_data['Denominator'] = $input_data['Denominator'] + 4294967296;
        }
        // Check byte alignment
        if ( $Byte_Align == "II" )
        {
          // Intel/Little Endian - pack the two longs and return
          return pack( "VV", $input_data['Numerator'], $input_data['Denominator'] );
        }
        else
        {
          // Motorola/Big Endian - pack the two longs and return
          return pack( "NN", $input_data['Numerator'], $input_data['Denominator'] );
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