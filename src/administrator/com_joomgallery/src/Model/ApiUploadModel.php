<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

class ApiUploadModel
{
  private function genFilename(string $srcFilename, $srcExtension, $catId)
  {
    $fileCounter = 0;

    try
    {
      // Generate image filename
      $this->component->createFileManager($catId);
      $filename = $this->component->getFileManager()->genFilename($srcFilename, $srcExtension, $fileCounter);
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
    }

    return $filename;
  }
}
