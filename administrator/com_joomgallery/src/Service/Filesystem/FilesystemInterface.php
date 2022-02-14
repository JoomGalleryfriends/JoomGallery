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

\defined('JPATH_PLATFORM') or die;

/**
* Filesystem Interface for the Configuration Helper
*
* @since  4.0.0
*/
interface FilesystemInterface
{
	/**
   * Constructor enables the connection to the filesystem
   * in which the images should be stored
   *
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct();

  /**
   * Function to strip additional / or \ in a path name.
   *
   * @param   string  $path   The path to clean
   * @param   string  $ds     Directory separator (optional)
   *
   * @return  string  The cleaned path
   *
   * @since   4.0.0
   */
  public function cleanPath($path, $ds=\DIRECTORY_SEPARATOR): string;

  /**
   * Moves a file from local folder to storage
   *
   * @param   string  $src   File name at local folder
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function uploadFile($src): bool;

  /**
   * Moves a file from the storage to a local folder
   *
   * @param   string  $dest  File name at local folder
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function downloadFile($dest): bool;

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
  public function moveFile($src, $dest, $copy = false): bool;

  /**
   * Delete a file or array of files
   *
   * @param   mixed  $file   The file name or an array of file names
   *
   * @return  bool   true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFile($file): bool;

  /**
   * Checks a file for existence, validity and size
   *
   * @param   string  $file  The file name
   *
   * @return  mixed   file size on success, false otherwise
   *
   * @since   4.0.0
   */
  public function checkFile($file): mixed;

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
  public function checkFilename($nameb, $namea = '', $checkspecial = false): bool;

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
  public function cleanFilename($file, $strip_ext=false, $replace_chars=''): mixed;

  /**
   * Create a folder and all necessary parent folders.
   *
   * @param   string  $path   A path to create from the base path.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function createFolder($path): bool;

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
  public function moveFolder($src, $dest, $copy = false): bool;

  /**
   * Delete a folder including all all files and subfolders
   *
   * @param   string  $path   The path to the folder to delete.
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFolder($path): bool;

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
  public function checkFolder($path, $files = false, $folders = false, $maxLevel = 3): mixed;

  /**
   * Sets the permission of a given file or folder recursively.
   *
   * @param   string  $path      The path to the file/folder
   * @param   string  $val       The octal representation of the value to change file/folder mode
   * @param   bool    $mode      True to use file mode. False to use folder mode. (default: true)
   *
   * @return  bool    True if successful [one fail means the whole operation failed].
   *
   * @since   4.0.0
   */
  public function chmod($path, $val, $mode=true): bool;
}
