<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

abstract class JoomGalleryRawView extends JoomGalleryView
{
  /**
   * Send a binary resource to the client.
   *
   * @param   resource  $resource
   * @param   string    $mimeType
   * @param   string    $filename
   * @param   int       $size
   *
   * @return  void
   */
  protected function outputResource($resource, string $mimeType, string $filename, int $size): void
  {
    // Set mime encoding to document
    $this->getDocument()->setMimeEncoding($mimeType);

    // Set header to specify the file name
    $this->app->setHeader('Content-Type', $mimeType, true);
    $this->app->setHeader('Content-Disposition', 'inline; filename="' . addcslashes(basename($filename), '"\\') . '"', true);
    $this->app->setHeader('Content-Length', (string) $size, true);
    $this->app->setHeader('Cache-Control', 'no-cache, must-revalidate', true);
    $this->app->setHeader('Pragma', 'no-cache', true);

    // Required for large files to work properly
    if(ob_get_level() > 0) ob_end_clean();

    fpassthru($resource);
    fclose($resource);
  }

  /**
   * Send an error response for a raw request.
   *
   * @param   int      $status
   * @param   string   $message
   *
   * @return  void
   */
  protected function outputError(int $status, string $message): void
  {
    $this->app->setHeader('Status', (string) $status, true);
    $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
    $this->app->setHeader('Cache-Control', 'no-store', true);
    $this->app->setHeader('Content-Length', (string) \strlen($message), true);

    // Required for large files to work properly
    if(ob_get_level() > 0) ob_end_clean();

    echo $message;

    $this->app->close();
  }
}
