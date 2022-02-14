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
   * Root folder of the local filesystem
   *
   * @var string
   */
  protected $local_root = JPATH_ROOT;

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

  /**
	 * Sets a default value if not already assigned
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed
	 *
	 * @since   4.0.0
	 */
	public function def($property, $default = null)
	{
		$value = $this->get($property, $default);

		return $this->set($property, $value);
	}

  /**
	 * Returns a property of the object or the default value if the property is not set.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $default   The default value.
	 *
	 * @return  mixed    The value of the property.
	 *
	 * @since   4.0.0
	 */
	public function get($property, $default = null)
	{
		if (isset($this->$property))
		{
			return $this->$property;
		}

		return $default;
	}

  /**
	 * Returns an associative array of object properties.
	 *
	 * @param   boolean  $public  If true, returns only the public properties.
	 *
	 * @return  array
	 *
	 * @since   4.0.0
	 */
	public function getProperties($public = true)
	{
		$vars = get_object_vars($this);

		if ($public)
		{
			foreach ($vars as $key => $value)
			{
				if ('_' == substr($key, 0, 1))
				{
					unset($vars[$key]);
				}
			}
		}

		return $vars;
	}

  /**
	 * Modifies a property of the object, creating it if it does not already exist.
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $value     The value of the property to set.
	 *
	 * @return  mixed  Previous value of the property.
	 *
	 * @since   4.0.0
	 */
	public function set($property, $value = null)
	{
		$previous = $this->$property ?? null;
		$this->$property = $value;

		return $previous;
	}

  /**
	 * Set the object properties based on a named array/hash.
	 *
	 * @param   mixed  $properties  Either an associative array or another object.
	 *
	 * @return  boolean
	 *
	 * @since   1.7.0
	 */
	public function setProperties($properties)
	{
		if (\is_array($properties) || \is_object($properties))
		{
			foreach ((array) $properties as $k => $v)
			{
				// Use the set function which might be overridden.
				$this->set($k, $v);
			}

			return true;
		}

		return false;
	}
}
