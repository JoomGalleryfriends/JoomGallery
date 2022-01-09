<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Storage;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\User\UserHelper;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Folder as JFolder;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Storage\StorageInterface;

/**
 * JoomGallery Storage Helper
 *
 * Provides handling with the filesystem where the image files are stored
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class LocalStorage implements StorageInterface
{
  /**
   * Root folder of the local storage system
   *
   * @var string
   */
  protected $root = '';

  /**
   * Constructor enables the connection to the filesystem
   * in which the images should be stored
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    return true;
  }

  /**
   * Moves a file from local folder to storage
   *
   * @param   string  $src   File name at local folder
   * @param   string  $dest  File name at destination storage filesystem
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function uploadFile($src, $dest)
  {
    return JFile::upload($src, $dest);
  }

  /**
   * Moves a file from the storage to a local folder
   *
   * @param   string  $src   File name at destination storage filesystem
   * @param   string  $dest  File name at local folder
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function downloadFile($src, $dest)
  {
    return JFile::copy($src, $dest);
  }

  /**
   * Moves a file at the storage filesystem
   *
   * @param   string  $src   Source file name
   * @param   string  $dest  Destination file name
   * @param   bool    $copy  True, if you want to copy the file (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveFile($src, $dest, $copy = false)
  {
    if($copy)
    {
      return JFile::copy($src, $dest);
    }
    else
    {
      return JFile::move($src, $dest);
    }
  }

  /**
   * Delete a file or array of files
   *
   * @param   mixed  $file   The file name or an array of file names
   *
   * @return  bool   true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFile($file)
  {
    return JFile::delete($file);
  }

  /**
   * Checks a file for existence, validity and size
   *
   * @param   string  $file  The file name
   *
   * @return  mixed   file info array on success, false otherwise
   *
   * @since   4.0.0
   */
  public function checkFile($file)
  {
    if (file_exists($file))
    {
      $info     = array();
      $img_info = getimagesize($file);

      if(\is_array($img_info))
      {
        // image file type
        $info['mime']     = $img_info['mime'];
        $info['width']    = $img_info[0];
        $info['height']   = $img_info[1];
        $info['bits']     = $img_info['bits'];
        $info['channels'] = $img_info['channels'];
      }
      else
      {
        // other file type
        $info['mime'] = mime_content_type($file);
      }

      $info['size'] = filesize($file);

      return $info;
    }
    else
    {
      return false;
    }
  }

  /**
   * Create a folder and all necessary parent folders.
   *
   * @param   string  $path   A path to create from the base path.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function createFolder($path)
  {
    return JFolder::create($path);
  }

  /**
   * Moves a folder including all all files and subfolders
   *
   * @param   string  $src    The path to the source folder.
   * @param   string  $dest   The path to the destination folder.
   * @param   bool    $copy   True, if you want to copy the folder (default: false)
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveFolder($src, $dest, $copy = false)
  {
    if($copy)
    {
      return JFolder::copy($src, $dest);
    }
    else
    {
      return JFolder::move($src, $dest);
    }
  }

  /**
   * Delete a folder including all all files and subfolders
   *
   * @param   string  $path   The path to the folder to delete.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFolder($path)
  {
    return JFolder::delete($path);
  }

  /**
   * Checks a folder for existence.
   *
   * @param   string  $path      The path to the folder to check.
   * @param   bool    $files     True to return a list of files in the folder
   * @param   bool    $folders   True to return a list of subfolders of the folder
   * @param   int     $maxLevel  The maximum number of levels to recursively read (default: 3).
   *
   * @return  mixed   Array with files and folders on success, false otherwise
   *
   * @since   4.0.0
   */
  public function checkFolder($path, $files = false, $folders = false, $maxLevel = 3)
  {
    if (file_exists($path))
    {
      if ($files && !$folders)
      {
        // list only files
        return JFolder::files($path,'',$maxLevel);
      }
      elseif (!$files && $folders)
      {
        // list only folders
        return JFolder::listFolderTree($path,'',$maxLevel);
      }
      else
      {
        // list files and folders
        return $this->listFolderTree($path,'',$maxLevel);
      }
    }
    else
    {
      return false;
    }
  }

  /**
   * Lists files and folders in format suitable for tree display.
   *
   * @param   string  $path      The path of the folder to read.
   * @param   bool    $filter    A filter for folder names.
   * @param   int     $maxLevel  The maximum number of levels to recursively read, defaults to three.
   * @param   int     $level     The current level, optional.
   * @param   int     $parent    Unique identifier of the parent folder, if any.
   *
   * @return  mixed   Array with files and folders in the given folder.
   *
   * @since   4.0.0
   */
  private function listFolderTree($path, $filter, $maxLevel, $level = 0, $parent = 0)
	{
    $dirs = array();

		if ($level == 0)
		{
			$GLOBALS['_JFolder_folder_tree_index'] = 0;
		}

		if ($level < $maxLevel)
		{
			$folders    = JFolder::folders($path, $filter);
			$pathObject = new PathWrapper;

			// First path, index foldernames
			foreach ($folders as $name)
			{
				$id = ++$GLOBALS['_JFolder_folder_tree_index'];
				$fullName = $pathObject->clean($path . '/' . $name);
				$dirs['name'] = array(
            'id' => $id,
            'parent' => $parent,
            'name' => $name,
            'fullname' => $fullName,
            'relname' => str_replace(JPATH_ROOT, '', $fullName),
            'files' => JFolder::files($fullName)
				);
				$dirs2 = $this->listFolderTree($fullName, $filter, $maxLevel, $level + 1, $id);
				$dirs = array_merge($dirs, $dirs2);
			}
		}

		return $dirs;
  }
}
