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
   * Moves a file from local folder to storage
   *
   * @param   string  $src   File name at local folder
   * @param   string  $dest  File name at destination storage filesystem
   *
   * @return  mixed    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function uploadFile($src, $dest): mixed;

  /**
   * Moves a file from the storage to a local folder
   *
   * @param   string  $src   File name at destination storage filesystem
   * @param   string  $dest  File name at local folder
   *
   * @return  mixed    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function downloadFile($src, $dest): mixed;

  /**
   * Moves a file at the storage filesystem
   *
   * @param   string  $src   Source file name
   * @param   string  $dest  Destination file name
   * @param   bool    $copy  True, if you want to copy the file (default: false)
   *
   * @return  mixed    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveFile($src, $dest, $copy = false): mixed;

  /**
   * Delete a file or array of files
   *
   * @param   mixed  $file   The file name or an array of file names
   *
   * @return  mixed   true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFile($file): mixed;

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
   * Create a folder and all necessary parent folders.
   *
   * @param   string  $path   A path to create from the base path.
   *
   * @return  mixed    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function createFolder($path): mixed;

  /**
   * Moves a folder including all all files and subfolders
   *
   * @param   string  $src    The path to the source folder.
   * @param   string  $dest   The path to the destination folder.
   * @param   bool    $copy   True, if you want to copy the folder (default: false)
   *
   * @return  mixed    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveFolder($src, $dest, $copy = false): mixed;

  /**
   * Delete a folder including all all files and subfolders
   *
   * @param   string  $path   The path to the folder to delete.
   *
   * @return  mixed    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteFolder($path): mixed;

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
}
