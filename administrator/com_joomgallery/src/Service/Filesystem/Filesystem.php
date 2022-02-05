<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* Base class for the Filesystem helper classes
*
* @since  4.0.0
*/
abstract class Filesystem implements FilesystemInterface
{
  /**
   * Cleaning of file/category name
   * optionally replace extension if present
   * replace special chars defined in the configuration
   *
   * @param   string    $file            The file name
   * @param   bool      $strip_ext       True for stripping the extension
   * @param   string    $replace_chars   Characters to be replaced
   *
   * @return  mixed     cleaned name on success, false otherwise
   *
   * @since   1.0.0
   */
  public function cleanFilename($file, $strip_ext=false, $replace_chars=''): mixed
  {
    // Check if multibyte support installed
    if(in_array ('mbstring', get_loaded_extensions()))
    {
      // Get the funcs from mb
      $funcs = get_extension_funcs('mbstring');
      if(in_array ('mb_detect_encoding', $funcs) && in_array ('mb_strtolower', $funcs))
      {
        // Try to check if the name contains UTF-8 characters
        $isUTF = mb_detect_encoding($file, 'UTF-8', true);
        if($isUTF)
        {
          // Try to lower the UTF-8 characters
          $file = mb_strtolower($file, 'UTF-8');
        }
        else
        {
          // Try to lower the one byte characters
          $file = strtolower($file);
        }
      }
      else
      {
        // TODO mbstring loaded but no needed functions
        // --> server misconfiguration
        $file = strtolower($file);
      }
    }
    else
    {
      // TODO no mbstring loaded, appropriate server for Joomla?
      $file = strtolower($file);
    }

    // Replace special chars
    $filenamesearch  = array();
    $filenamereplace = array();

    $items = explode(',', $replace_chars);
    if($items != false)
    {
      // Contains pairs of <specialchar>|<replaced char(s)>
      foreach($items as $item)
      {
        if(!empty($item))
        {
          $workarray = explode('|', trim($item));
          if($workarray != false && isset($workarray[0]) && !empty($workarray[0]) && isset($workarray[1]) && !empty($workarray[1]))
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
    $extensions = JoomHelper::getComponent()->supported_types;
    $extension  = false;
    foreach ($extensions as $i => $ext)
    {
      $ext = '.'.\strtolower($ext);
      if(\substr_count($file, $ext) != 0)
      {
        $extension = true;
        // If extension found, break
        break;
      }
    }

    // Replace extension if present
    if($extension)
    {
      $fileextension        = JFile::getExt($file);
      $fileextensionlength  = strlen($fileextension);
      $filenamelength       = strlen($file);
      $filename             = substr($file, -$filenamelength, -$fileextensionlength - 1);
    }
    else
    {
      // No extension found (Batchupload)
      $filename = $file;
    }

    // Perform the replace
    for($i = 0; $i < $lengthreplace; $i++)
    {
      $searchstring = '!'.$filenamesearch[$i].'+!i';
      $filename     = preg_replace($searchstring, $filenamereplace[$i], $filename);
    }

    if($extension && !$strip_ext)
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
   *
   * @param   string    $nameb          filename before any processing
   * @param   string    $namea          filename after processing in e.g. fixFilename
   * @param   bool      $checkspecial   True if the filename shall be checked for special characters only
   *
   * @return  bool      True if the filename is valid, false otherwise
   *
   * @since   2.0.0
  */
  public function checkFilename($nameb, $namea = '', $checkspecial = false): bool
  {
    // TODO delete this function and the call of them?
    return true;

    // Check only for special characters
    if($checkspecial)
    {
      $pattern = '/[^0-9A-Za-z -_]/';
      $check = \preg_match($pattern, $nameb);
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
    if(\strlen($nameb) - \substr_count($nameb, '_') == 0)
    {
      $nameb_onlyus = true;
    }
    else
    {
      $nameb_onlyus = false;
    }
    if(empty($namea) || (!$nameb_onlyus && strlen($namea) == substr_count($nameb, '_')))
    {
      return false;
    }
    else
    {
      return true;
    }
  }
}
