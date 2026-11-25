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
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;

/**
 * Image controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomFormController
{
  use RoutingTrait;

  protected $view_list = 'images';

  /**
   * Deliver an authorized image type as a browser download.
   *
   * @return  void
   *
   * @since   __DEPLOY_VERSION__
   */
  public function download(): void
  {
    $imageId = $this->input->getInt('id', 0);
    $type    = strtolower($this->input->getCmd('type', ''));

    try
    {
      if($imageId <= 0 || $type === '')
      {
        throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_DOWNLOAD_INVALID_REQUEST'), 400);
      }

      $image      = $this->getAccessibleImage($imageId, $type);
      $downloader = $this->component->getDownloader();
      $decision   = $downloader->authorizeImage($image, $type, 'download');

      if($this->redirectForAuthorization($decision, 'image', $imageId, $type))
      {
        return;
      }

      [$fileInfo, $resource] = $downloader->prepareImageDownload($image, $type);
      $downloader->recordDownloads($imageId);
      $this->outputDownloadResource($resource, $fileInfo, (string) $image->filename);
    }
    catch(\Throwable $e)
    {
      $status = \in_array((int) $e->getCode(), [400, 403, 404, 503], true) ? (int) $e->getCode() : 500;

      if($status === 500)
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $message = $status === 500 ? Text::_('COM_JOOMGALLERY_DOWNLOAD_PROCESSING_FAILED') : $e->getMessage();
      $this->outputError($status, $message);
    }
  }

  /**
   * Interactive gateway used by Sesame and the native terms confirmation.
   *
   * @return  void
   *
   * @since   __DEPLOY_VERSION__
   */
  public function license(): void
  {
    try
    {
      [$decision, $request] = $this->resolveLicenseRequest();

      if($decision['status'] === DownloaderInterface::STATUS_ALLOWED)
      {
        $this->setRedirect(Route::_($this->getLicenseReturnPage($request), false));

        return;
      }

      if($decision['status'] === DownloaderInterface::STATUS_PURCHASE_REQUIRED)
      {
        if(!empty($decision['purchase_url']))
        {
          $this->setRedirect((string) $decision['purchase_url']);

          return;
        }

        throw new \RuntimeException($decision['message'] ?: Text::_('COM_JOOMGALLERY_LICENSE_REQUIRED'), 403);
      }

      if($decision['status'] === DownloaderInterface::STATUS_TERMS_REQUIRED)
      {
        $request['terms'] = $this->component->getDownloader()->getTerms();
        $this->app->setUserState('com_joomgallery.license.request', $request);
        $this->input->set('view', 'license');
        $this->input->set('layout', 'default');
        $this->display();

        return;
      }

      $status = $decision['status'] === DownloaderInterface::STATUS_PROVIDER_UNAVAILABLE ? 503 : 403;
      throw new \RuntimeException($decision['message'] ?: Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), $status);
    }
    catch(\Throwable $e)
    {
      $status = \in_array((int) $e->getCode(), [400, 403, 404, 503], true) ? (int) $e->getCode() : 500;
      $this->outputError($status, $e->getMessage());
    }
  }

  /**
   * Accept the configured native terms and resume the protected request.
   *
   * @return  void
   *
   * @since   __DEPLOY_VERSION__
   */
  public function acceptLicense(): void
  {
    $this->checkToken();

    try
    {
      if($this->input->post->getInt('accept_terms', 0) !== 1)
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_LICENSE_TERMS_NOT_ACCEPTED'), 400);
      }

      [$decision, $request] = $this->resolveLicenseRequest();

      // Paid authorization must succeed before native terms can unlock access.
      if($decision['status'] === DownloaderInterface::STATUS_PURCHASE_REQUIRED)
      {
        if(!empty($decision['purchase_url']))
        {
          $this->setRedirect((string) $decision['purchase_url']);

          return;
        }

        throw new \RuntimeException($decision['message'] ?: Text::_('COM_JOOMGALLERY_LICENSE_REQUIRED'), 403);
      }

    if(!\in_array(
        $decision['status'],
        [DownloaderInterface::STATUS_ALLOWED, DownloaderInterface::STATUS_TERMS_REQUIRED],
        true
    ))
      {
        $status = $decision['status'] === DownloaderInterface::STATUS_PROVIDER_UNAVAILABLE ? 503 : 403;
        throw new \RuntimeException($decision['message'] ?: Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), $status);
    }

      $this->component->getDownloader()->acceptTerms();
      $this->setRedirect(Route::_($this->getLicenseReturnPage($request), false));
    }
    catch(\Throwable $e)
    {
      $resource = strtolower($this->input->getCmd('resource', 'image'));
      $id       = $this->input->getInt('id', 0);
      $type     = strtolower($this->input->getCmd('type', ''));
      $purpose  = strtolower($this->input->getCmd('purpose', 'download'));
      $return   = $this->input->get('return', '', 'base64');
      $url      = 'index.php?option=com_joomgallery&task=image.license'
        . '&resource=' . rawurlencode($resource)
        . '&id=' . $id
        . '&type=' . rawurlencode($type)
        . '&purpose=' . rawurlencode($purpose)
        . '&return=' . rawurlencode($return);
    $this->setRedirect(
        Route::_($url, false),
        $e->getMessage(),
        'error'
    );
    }
  }

  /**
   * Add the displayed image to one of the current user's collections.
   *
   * @return  boolean  True when the mapping exists after the request.
   *
   * @since   DEPLOY_VERSION
   */
  public function addToCollection(): bool
  {
    $this->checkToken();

    $imageId      = $this->input->post->getInt('id');
    $collectionId = $this->input->post->getInt('collection_id');
    $redirect     = Route::_($this->getReturnPage('image'), false);
    $identity     = $this->app->getIdentity();

    if($identity->guest)
    {
      $this->setRedirect($redirect, Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'warning');

      return false;
    }

    try
    {
      if($imageId <= 0 || $collectionId <= 0)
      {
        throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_SELECTION'), 400);
      }

      $this->assertCollectibleImage($imageId);

      $collectionModel = $this->component->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);

      if(!$collectionModel->ownsCollection($collectionId))
      {
        throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
      }

      if(!$collectionModel->addMapping($collectionId, $imageId, true))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_ADD_FAILED'));
      }

      $table = $collectionModel->getTable();
      $table->load($collectionId);
      $title = htmlspecialchars((string) $table->title, ENT_QUOTES, 'UTF-8');
      $this->setRedirect($redirect, Text::sprintf('COM_JOOMGALLERY_COLLECTION_IMAGE_ADDED', $title), 'success');

      return true;
    }
    catch(\Throwable $e)
    {
      $safeMessage = \in_array((int) $e->getCode(), [400, 403], true)
        ? $e->getMessage() : Text::_('COM_JOOMGALLERY_COLLECTION_ADD_FAILED');

      if(!\in_array((int) $e->getCode(), [400, 403], true))
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $this->setRedirect($redirect, $safeMessage, 'error');

      return false;
    }
  }

  /**
   * Toggle the displayed image in one of the current user's collections.
   *
   * @return  boolean  True when the request was processed successfully.
   *
   * @since   DEPLOY_VERSION
   */
  public function toggleCollection(): bool
  {
    $this->checkToken();

    $imageId      = $this->input->post->getInt('id');
    $collectionId = $this->input->post->getInt('collection_id');
    $redirect     = Route::_($this->getReturnPage('image'), false);
    $identity     = $this->app->getIdentity();

    if($identity->guest)
    {
      $this->setRedirect($redirect, Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'), 'warning');

      return false;
    }

    try
    {
      $this->assertCollectibleImage($imageId);
      $collectionModel = $this->component->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);
      $mapped          = $collectionModel->toggleMapping($collectionId, $imageId);
      $table           = $collectionModel->getTable();
      $table->load($collectionId);
      $title   = htmlspecialchars(Text::_((string) $table->title), ENT_QUOTES, 'UTF-8');
      $message = $mapped ? 'COM_JOOMGALLERY_COLLECTION_IMAGE_ADDED' : 'COM_JOOMGALLERY_COLLECTION_IMAGE_REMOVED';
      $this->setRedirect($redirect, Text::sprintf($message, $title), 'success');

      return true;
    }
    catch(\Throwable $e)
    {
      $safeMessage = \in_array((int) $e->getCode(), [400, 403, 404], true)
        ? $e->getMessage() : Text::_('COM_JOOMGALLERY_COLLECTION_UPDATE_FAILED');

      if(!\in_array((int) $e->getCode(), [400, 403, 404], true))
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $this->setRedirect($redirect, $safeMessage, 'error');

      return false;
    }
  }

  /**
   * Reject collection requests for unpublished, protected or inaccessible images.
   *
   * @param   int  $imageId  Image ID.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  protected function assertCollectibleImage(int $imageId): void
  {
    if($imageId <= 0)
    {
      throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_SELECTION'), 400);
    }

    $imageModel = $this->component->getMVCFactory()->createModel('Image', 'Site', ['ignore_request' => true]);
    $image      = $imageModel->getItem($imageId);
    $viewLevels = $this->app->getIdentity()->getAuthorisedViewLevels();

    if((int) $image->published !== 1 || (int) $image->approved !== 1
      || !\in_array((int) $image->access, $viewLevels, true)
      || !$imageModel->getCategoryPublished() || !$imageModel->getCategoryAccess()
      || $imageModel->getCategoryProtected())
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
    }
  }

  /**
   * Edit an existing image.
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function edit($key = null, $urlVar = null)
  {
    throw new \Exception('Edit an existing image not possible. Use userimage controller instead.', 503);
  }


  /**
   * Add a new image: Not available
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function add()
  {
    throw new \Exception('Edit an existing image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Load item to edit associations in com_associations
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function editAssociations()
  {
    throw new \Exception('Edit associations not possible. Use userimage controller instead.', 503);
  }

  /**
   * Method to add multiple new image records.
   *
   * @return  boolean  True if the record can be added, false if not.
   *
   * @since   4.0
   */
  public function ajaxsave(): bool
  {
    $result = ['error' => false];

    try
    {
      if(!parent::save())
      {
        $result['success'] = false;
        $result['error']   = $this->message;
      }
      else
      {
        $result['success'] = true;
        $result['record']  = $this->component->cache->get('imgObj');
      }

      $json = json_encode($result, JSON_FORCE_OBJECT);
      echo new JsonResponse($json);

      $this->app->close();
    }
    catch(\Exception $e)
    {
      echo new JsonResponse($e);

      $this->app->close();
    }

    return true;
  }

  /**
   * Remove an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function remove()
  {
    throw new \Exception('Removing image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Checkin a checked out image.
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function checkin()
  {
    throw new \Exception('Check-in image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Method to publish an image.
   *
   * @return  bool
   *
   * @throws  \Exception
   *
   * @since   __DEPLOY_VERSION__
   */
  public function publish(): bool
  {
    throw new \Exception('Publishing an image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Method to unpublish an image
   *
   * @throws \Exception
   * @since   4.2.0
   */
  public function unpublish(): bool
  {
    throw new \Exception('Unpublishing an image not possible. Use userimage controller instead.', 503);
  }

  /**
   * Resolve and ordinarily authorize an image from trusted gallery state.
   *
   * @param   int     $imageId  Image ID.
   * @param   string  $type     Requested imagetype.
   *
   * @return  object  Accessible image record.
   */
  protected function getAccessibleImage(int $imageId, string $type): object
  {
    if($imageId <= 0 || $type === '')
    {
      throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_LICENSE_INVALID_REQUEST'), 400);
    }

    $model = $this->component->getMVCFactory()->createModel('Image', 'Site', ['ignore_request' => true]);

    try
    {
      $image = $model->getItem($imageId);
    }
    catch(\Throwable $e)
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_DOWNLOAD_IMAGE_NOT_FOUND'), 404, $e);
    }

    $viewLevels = $this->app->getIdentity()->getAuthorisedViewLevels();

    if(!$image || (int) ($image->id ?? 0) !== $imageId
      || (int) $image->published !== 1 || (int) $image->approved !== 1
      || !\in_array((int) $image->access, $viewLevels, true)
      || !$model->getCategoryPublished() || !$model->getCategoryAccess()
      || $model->getCategoryProtected())
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
    }

    return $image;
  }

  /**
   * Return an ordinary-access result suitable for collection callbacks.
   *
   * @param   object  $image  Image candidate.
   * @param   string  $type   Requested imagetype.
   *
   * @return  boolean  True when ordinary site access passes.
   */
  protected function canAccessImage(object $image, string $type): bool
  {
    try
    {
      $this->getAccessibleImage((int) $image->id, $type);

      return true;
    }
    catch(\Throwable $e)
    {
      return false;
    }
  }

  /**
   * Resolve the resource represented by an image.license request.
   *
   * @return  array{0: array, 1: array}  Decision and normalized request.
   */
  protected function resolveLicenseRequest(): array
  {
    $resource = strtolower($this->input->getCmd('resource', 'image'));
    $id       = $this->input->getInt('id', 0);
    $type     = strtolower($this->input->getCmd('type', ''));
    $purpose  = strtolower($this->input->getCmd('purpose', 'download'));
    $return   = $this->input->get('return', '', 'base64');

    if($id <= 0 || $type === ''
      || !\in_array($resource, ['image', 'collection'], true)
      || !\in_array($purpose, ['display', 'download'], true)
      || ($resource === 'collection' && $purpose !== 'download'))
    {
      throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_LICENSE_INVALID_REQUEST'), 400);
    }

    $request = [
      'resource' => $resource,
      'id'       => $id,
      'type'     => $type,
      'purpose'  => $purpose,
      'return'   => $return,
    ];

    $service = $this->component->getDownloader();

    if($resource === 'image')
    {
      $image = $this->getAccessibleImage($id, $type);

      return [$service->authorizeImage($image, $type, $purpose), $request];
    }

    $collectionModel = $this->component->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);

    if(!$collectionModel->ownsCollection($id))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
    }

    $manifest = $service->inspectCollectionDownload($id, $type, (bool) $this->input->getInt('force_recreate', 0));
    $archive  = $service->authorizeArchive($manifest, $type, fn(object $image, string $imageType): bool => $this->canAccessImage($image, $imageType));

    return [$archive, $request];
  }

  /**
   * Redirect missing entitlement or terms decisions to image.license.
   *
   * @param   array   $decision  Authorization decision.
   * @param   string  $resource  Resource kind.
   * @param   int     $id        Resource ID.
   * @param   string  $type      Requested imagetype.
   *
   * @return  boolean  True when a redirect was configured.
   */
  protected function redirectForAuthorization(array $decision, string $resource, int $id, string $type): bool
  {
    if($decision['status'] === DownloaderInterface::STATUS_ALLOWED)
    {
      return false;
    }

    if(\in_array(
        $decision['status'],
        [DownloaderInterface::STATUS_TERMS_REQUIRED, DownloaderInterface::STATUS_PURCHASE_REQUIRED],
        true
    ))
    {
      $return = base64_encode(Uri::getInstance()->toString(['path', 'query', 'fragment']));
      $url    = 'index.php?option=com_joomgallery&task=image.license'
        . '&resource=' . rawurlencode($resource)
        . '&id=' . $id
        . '&type=' . rawurlencode($type)
        . '&purpose=download'
        . '&return=' . rawurlencode($return);
      $this->setRedirect(Route::_($url, false));

      return true;
    }

    $status = $decision['status'] === DownloaderInterface::STATUS_PROVIDER_UNAVAILABLE ? 503 : 403;
    throw new \RuntimeException($decision['message'] ?: Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), $status);
  }

  /**
   * Resolve a validated internal return route for the license gateway.
   *
   * @param   array  $request  Normalized license request.
   *
   * @return  string  Internal return route.
   */
  protected function getLicenseReturnPage(array $request): string
  {
    $encoded = (string) ($request['return'] ?? '');
    $decoded = $encoded !== '' ? base64_decode($encoded, true) : false;

    if(\is_string($decoded) && $decoded !== '' && Uri::isInternal($decoded))
    {
      return $decoded;
    }

    if(($request['resource'] ?? '') === 'image')
    {
      return 'index.php?option=com_joomgallery&view=image&id=' . (int) ($request['id'] ?? 0);
    }

    return 'index.php?option=com_joomgallery&view=gallery';
  }

  /**
   * Send a prepared image resource as an attachment.
   *
   * @param   resource  $resource  Readable image stream.
   * @param   object    $fileInfo  File metadata.
   * @param   string    $filename  Browser filename.
   *
   * @return  void
   */
  protected function outputDownloadResource($resource, object $fileInfo, string $filename): void
  {
    $filename = str_replace(["\r", "\n"], '', basename($filename));
    $mimeType = (string) ($fileInfo->mime_type ?? 'application/octet-stream');

    $this->app->setHeader('Content-Type', $mimeType, true);
    $this->app->setHeader(
        'Content-Disposition',
        'attachment; filename="' . addcslashes($filename, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
        true
    );
    $this->app->setHeader('Content-Length', (string) ((int) ($fileInfo->size ?? 0)), true);
    $this->app->setHeader('Cache-Control', 'private, no-store, no-cache, must-revalidate', true);
    $this->app->setHeader('Pragma', 'no-cache', true);
    $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);

    while(ob_get_level() > 0)
    {
      ob_end_clean();
    }

    $this->app->sendHeaders();
    fpassthru($resource);
    fclose($resource);
    $this->app->close();
  }

  /**
   * Send a plain-text error for a raw delivery endpoint.
   *
   * @param   int     $status   HTTP status.
   * @param   string  $message  Safe response message.
   *
   * @return  void
   */
  protected function outputError(int $status, string $message): void
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
