<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Controller\JoomFormController;
use Joomgallery\Component\Joomgallery\Administrator\Service\Downloader\DownloaderInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Site controller for protected collection downloads.
 *
 * @package JoomGallery
 * @since   __DEPLOY_VERSION__
 */
class CollectionController extends JoomFormController
{
  /**
   * Restrict the site collection controller to its download endpoint.
   *
   * @param   string  $task  Requested task.
   *
   * @return  mixed  Parent controller result.
   */
  public function execute($task)
  {
    if($task !== 'download')
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    return parent::execute($task);
  }

  /**
   * Deliver all images in an owned collection as an authorized ZIP archive.
   *
   * @return  void
   */
  public function download(): void
  {
    $collectionId  = $this->input->getInt('id', 0);
    $type          = strtolower($this->input->getCmd('type', ''));
    $forceRecreate = (bool) $this->input->getInt('force_recreate', 0);

    try
    {
      if($collectionId <= 0 || $type === '')
      {
        throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_INVALID_REQUEST'), 400);
      }

    $collectionModel = $this->component->getMVCFactory()->createModel(
        'Collection',
        'Administrator',
        ['ignore_request' => true]
    );

      if(!$collectionModel->ownsCollection($collectionId))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
      }

      $downloader = $this->component->getDownloader();
      $access     = fn(object $image, string $imageType): bool => $this->canAccessImage($image, $imageType);
      $archive    = $downloader->prepareCollectionDownload($collectionId, $type, $forceRecreate, $access);
      $decision   = $downloader->authorizeArchive($archive, $type, $access);

    if(\in_array(
        $decision['status'],
        [DownloaderInterface::STATUS_TERMS_REQUIRED, DownloaderInterface::STATUS_PURCHASE_REQUIRED],
        true
    ))
      {
        $return = base64_encode(Uri::getInstance()->toString(['path', 'query', 'fragment']));
        $url    = 'index.php?option=com_joomgallery&task=image.license&resource=collection'
          . '&id=' . $collectionId
          . '&type=' . rawurlencode($type)
          . '&purpose=download'
          . '&return=' . rawurlencode($return);
        $this->setRedirect(Route::_($url, false));

        return;
    }

      if($decision['status'] !== DownloaderInterface::STATUS_ALLOWED)
      {
        $status = $decision['status'] === DownloaderInterface::STATUS_PROVIDER_UNAVAILABLE ? 503 : 403;
        throw new \RuntimeException($decision['message'] ?: Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), $status);
      }

      $downloader->recordDownloads($archive['image_ids']);
      $this->outputDownloadArchive($archive['path'], $archive['name']);
    }
    catch(\Throwable $e)
    {
      $status = \in_array((int) $e->getCode(), [400, 403, 404, 503], true) ? (int) $e->getCode() : 500;

      if($status === 500)
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $message = $status === 500 ? Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED') : $e->getMessage();
      $this->outputError($status, $message);
    }
  }

  /**
   * Check ordinary site visibility for one archive member.
   *
   * @param   object  $image  Image record.
   * @param   string  $type   Requested imagetype.
   *
   * @return  boolean  True when site access passes.
   */
  private function canAccessImage(object $image, string $type): bool
  {
    try
    {
      $model  = $this->component->getMVCFactory()->createModel('Image', 'Site', ['ignore_request' => true]);
      $item   = $model->getItem((int) $image->id);
      $levels = $this->app->getIdentity()->getAuthorisedViewLevels();

      return $item
        && (int) $item->published === 1
        && (int) $item->approved === 1
        && \in_array((int) $item->access, $levels, true)
        && $model->getCategoryPublished()
        && $model->getCategoryAccess()
        && !$model->getCategoryProtected();
    }
    catch(\Throwable $e)
    {
      return false;
    }
  }

  /**
   * Stream a prepared ZIP archive as an attachment.
   *
   * @param   string  $path      Absolute archive path.
   * @param   string  $filename  Browser filename.
   *
   * @return  void
   */
  private function outputDownloadArchive(string $path, string $filename): void
  {
    if(!is_file($path) || !is_readable($path))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_FILE_NOT_FOUND'), 404);
    }

    $filename = str_replace(["\r", "\n"], '', basename($filename));
    $this->app->setHeader('Content-Type', 'application/zip', true);
    $this->app->setHeader(
        'Content-Disposition',
        'attachment; filename="' . addcslashes($filename, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
        true
    );
    $this->app->setHeader('Content-Length', (string) filesize($path), true);
    $this->app->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate', true);
    $this->app->setHeader('Pragma', 'no-cache', true);
    $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);

    while(ob_get_level() > 0)
    {
      ob_end_clean();
    }

    $this->app->sendHeaders();
    readfile($path);
    $this->app->close();
  }

  /**
   * Send a plain-text endpoint error.
   *
   * @param   int     $status   HTTP status.
   * @param   string  $message  Safe message.
   *
   * @return  void
   */
  private function outputError(int $status, string $message): void
  {
    $this->app->setHeader('Status', (string) $status, true);
    $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
    $this->app->setHeader('Content-Length', (string) \strlen($message), true);
    $this->app->setHeader('Cache-Control', 'no-store', true);
    $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);

    while(ob_get_level() > 0)
    {
      ob_end_clean();
    }

    $this->app->sendHeaders();
    echo $message;
    $this->app->close();
  }
}
