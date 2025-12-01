<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\FileManager;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
* Interface for the file manager classes
*
* File manager classes provides methods to handle image files and folders based ...
* - ... on the current available image types (#_joomgallery_img_types)
* - ... on the parameters from the configuration set of the current user (Config-Service)
* - ... on the chosen filesystem (Filesystem-Service)
* - ... on the chosen image processor (IMGtools-Service)
*
* @since  4.0.0
*/
interface FileManagerInterface
{
  /**
   * Creation of image types based on source file.
   * Source file has to be given with a full system path.
   *
   * @param   string               $source        Source file with which the image types shall be created
   * @param   string               $filename      Name for the files to be created
   * @param   object|int|string    $cat           Object, ID or alias of the corresponding category (default: 2)
   * @param   bool                 $processing    True to create imagetypes by processing source (defualt: True)
   * @param   bool                 $local_source  True if the source is a file located in a local folder (default: True)
   * @param   array                $skip          List of imagetypes to skip creation (default: [])
   * @param   string               $logfile       Name of the logfile to use
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createImages($source, $filename, $cat=2, $processing=True, $local_source=True, $skip=[], $logfile = 'jerror'): bool;

  /**
   * Deletion of image types
   *
   * @param   object|int|string    $img       Image object, image ID or image alias
   * @param   string               $logfile   Name of the logfile to use
   * 
   * @return  bool                 True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteImages($img, $logfile = 'jerror'): bool;

  /**
   * Checks image types for existence, validity and size
   *
   * @param   object|int|string    $img       Image object, image ID or image alias
   * @param   string               $logfile   Name of the logfile to use
   * 
   * @return  array                List of filetype info
   * 
   * @since   4.0.0
   */
  public function checkImages($img, $logfile = 'jerror'): array;

  /**
   * Move image files from one category to another
   *
   * @param   object|int|string    $img        Image object, image ID or image alias
   * @param   object|int|string    $dest       Category object, ID or alias of the destination category
   * @param   string|false         $filename   Filename of the moved image (default: false)
   * @param   bool                 $copy       True, if you want to copy the images (default: false)
   * @param   string               $logfile    Name of the logfile to use
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveImages($img, $dest, $filename=false, $copy=false, $logfile='jerror'): bool;

  /**
   * Copy image files from one category to another
   *
   * @param   object|int|string    $img        Image object, image ID or image alias
   * @param   object|int|string    $dest       Category object, ID or alias of the destination category
   * @param   string|false         $filename   Filename of the moved image (default: False)
   * @param   string               $logfile    Name of the logfile to use
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function copyImages($img, $dest, $filename=false, $logfile='jerror'): bool;

  /**
   * Rename files of image
   *
   * @param   object|int|string   $img        Image object, image ID or image alias
   * @param   string              $filename   New filename of the image
   * @param   string              $logfile    Name of the logfile to use
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function renameImages($img, $filename, $logfile = 'jerror'): bool;

  /**
   * Creation of a category
   *
   * @param   string              $foldername   Name of the folder to be created
   * @param   object|int|string   $parent       Object, ID or alias of the parent category (default: 1)
   * @param   string              $logfile      Name of the logfile to use
   * 
   * @return  bool                True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function createCategory($foldername, $parent=1, $logfile='jerror'): bool;

  /**
   * Deletion of a category
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be deleted
   * @param   bool                $del_images   True, if you want to delete even if there are still images in it (default: false)
   * @param   string              $logfile      Name of the logfile to use
   * 
   * @return  bool                True on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function deleteCategory($cat, $del_images=false, $logfile='jerror'): bool;

  /**
   * Checks a category for existence, correct images and file path
   *
   * @param   object|int|string   $cat       Object, ID or alias of the category to be checked
   * @param   string              $logfile   Name of the logfile to use
   * 
   * @return  array               List of folder info
   * 
   * @since   4.0.0
   */
  public function checkCategory($cat, $logfile='jerror'): array;

  /**
   * Copy category with all images from one parent category to another
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be copied
   * @param   object|int|string   $dest         Category object, ID or alias of the destination category
   * @param   string|false        $foldername   Foldername of the moved category (default: false)
   * @param   string              $logfile      Name of the logfile to use
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function copyCategory($cat, $dest, $foldername=false, $logfile='jerror'): bool;

  /**
   * Move category with all images from one parent category to another
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be moved
   * @param   object|int|string   $dest         Category object, ID or alias of the destination category
   * @param   string|false        $foldername   Foldername of the moved category (default: false)
   * @param   bool                $copy         True, if you want to copy the category (default: false)
   * @param   string              $logfile      Name of the logfile to use
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function moveCategory($cat, $dest, $foldername=false, $copy=false, $logfile='jerror'): bool;

  /**
   * Rename folder of category
   *
   * @param   object|int|string   $cat          Object, ID or alias of the category to be renamed
   * @param   string              $foldername   New foldername of the category
   * @param   string              $logfile      Name of the logfile to use
   *
   * @return  bool    true on success, false otherwise
   *
   * @since   4.0.0
   */
  public function renameCategory($cat, $foldername, $logfile='jerror'): bool;

  /**
   * Returns the path to an image
   *
   * @param   object|int|string         $img       Image object, image ID or image alias (new images: ID=0)
   * @param   string                    $type      Imagetype
   * @param   object|int|string|bool    $catid     Category object, category ID, category alias or category path (default: false)
   * @param   string|bool               $filename  The filename (default: false)
   * @param   boolean                   $root      True to add the system root to the path
   * @param   string                    $logfile   Name of the logfile to use
   * 
   * @return  mixed   Path to the image on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getImgPath($img, $type, $catid=false, $filename=false, $root=false, $logfile='jerror');

  /**
   * Returns the path to a category without root path.
   *
   * @param   object|int|string        $cat             Category object, category ID or category alias (new categories: ID=0)
   * @param   string|bool              $type            Imagetype if needed
   * @param   object|int|string|bool   $parent          Parent category object, parent category ID, parent category alias or parent category path (default: false)
   * @param   string|bool              $alias           The category alias (default: false)
   * @param   boolean                  $root            True to add the system root to the path
   * @param   boolean                  $compatibility   Take into account the compatibility mode when creating the path
   * @param   string                   $logfile         Name of the logfile to use
   * 
   * 
   * @return  mixed   Path to the category on success, false otherwise
   * 
   * @since   4.0.0
   */
  public function getCatPath($cat, $type=false, $parent=false, $alias=false, $root=false, $compatibility=true, $logfile='jerror');

  /**
   * Generates image filenames
   * e.g. <Name/Title>_<Filecounter (opt.)>_<Date>_<Random Number>.<Extension>
   *
   * @param   string    $filename     Original upload name without extension
   * @param   string    $tag          File extension e.g. 'jpg'
   * @param   int       $filecounter  Optionally a filecounter
   *
   * @return  string    The generated filename
   *
   * @since   4.0.0
   */
  public function genFilename($filename, $tag, $filecounter = null): string;

  /**
   * Regenerates image filenames
   * Input is a filename generated from genFilename()
   *
   * @param   string    $filename     Original filename created from genFilename()
   * @param   string    $logfile      Name of the logfile to use
   *
   * @return  string    The generated filename
   *
   * @since   4.0.0
   */
  public function regenFilename($filename, $logfile='jerror'): string;
}
