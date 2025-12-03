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

/**
 * The Uploader service
 *
 * @since  4.0.0
 */
interface UploaderServiceInterface
{
    /**
     * Creates the Uploader helper class based on the selected upload method
     *
     * @param   string  $uploadMethod   Name of the upload method to be used
     * @param   bool    $multiple       True, if it is a multiple upload  (default: false)
     * @param   bool    $async          True, if it is a asynchronous upload  (default: false)
     *
     * @return  void
     *
     * @since  4.0.0
     */
    public function createUploader($uploadMethod, $multiple=false, $async=false): void;

    /**
     * Returns the Uploader helper class.
     *
     * @return  UploaderInterface
     *
     * @since  4.0.0
     */
    public function getUploader(): UploaderInterface;
}
