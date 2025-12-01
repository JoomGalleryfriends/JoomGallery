<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\FileManager;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\FileManager\FileManager;

/**
 * Trait to implement FileManagerServiceInterface
 *
 * @since  4.0.0
 */
trait FileManagerServiceTrait
{
        /**
         * Storage for the file manager class.
         *
         * @var FileManagerInterface
         *
         * @since  4.0.0
         */
        private $fileManager = null;

        /**
         * Returns the file manager helper class.
         *
         * @return  FileManagerInterface
         *
         * @since  4.0.0
         */
        public function getFileManager(): FileManagerInterface
        {
                return $this->fileManager;
        }

        /**
         * Creates the file manager helper class
         *
         * @param   int          $catid       Id of the category for which the filesystem is chosen
         * @param   array|bool   $selection   List of imagetypes to consider or false to consider all (default: False)
         *
         * @return  void
         *
         * @since  4.0.0
         */
        public function createFileManager($catid, $selection = false): void
        {
        $this->fileManager = new FileManager($catid, $selection);

        return;
        }
}
