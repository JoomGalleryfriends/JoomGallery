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
    $extensions = array('.jpeg','.jpg','.jpe','.gif','.png','.webp');
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

}