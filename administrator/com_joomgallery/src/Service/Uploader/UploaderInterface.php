<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;

/**
 * Uploader Interface for the helper classes
 *
 * @since  4.0.0
 */
interface UploaderInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct();

  /**
   * Analyses an error code and returns its text
   *
   * @param   int     $uploaderror  The errorcode
   *
   * @return  string  The error message
   *
   * @since   4.0.0
   */
  public function checkError($uploaderror): string;

    /**
     * Method to retrieve an uploaded image. Step 1.
     * (check upload, check user upload limit, create filename, onJoomBeforeUpload)
     *
     * @param   array    $data      Form data (as reference)
     * @param   bool     $filename    True, if the filename has to be created (default: True)
     *
     * @return  bool     True on success, false otherwise
     *
     * @since  4.0.0
     */
    public function retrieveImage(&$data, $filename = true): bool;

  /**
   * Override form data with image metadata
   * according to configuration. Step 2.
   *
   * @param   array   $data       The form data (as a reference)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   1.5.7
   */
  public function overrideData(&$data): bool;

    /**
     * Method to create uploaded image files. Step 3.
     * (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
     *
     * @param   ImageTable   $data_row     Image object
     *
     * @return  bool         True on success, false otherwise
     *
     * @since  4.0.0
     */
    public function createImage($data_row): bool;

  /**
   * Rollback an erroneous upload
   *
   * @param   CMSObject|\stdClass   $data_row     Image object containing at least catid and filename (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function rollback($data_row = false);

  /**
   * Detect if there is an image uploaded
   *
   * @param   array    $data      Form data
   *
   * @return  bool     True if file is detected, false otherwise
   *
   * @since   4.0.0
   */
  public function isImgUploaded($data): bool;

  /**
   * Delete all temporary created files which were created during upload
   *
   * @return  bool     True if files are deleted, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteTmp(): bool;
}
