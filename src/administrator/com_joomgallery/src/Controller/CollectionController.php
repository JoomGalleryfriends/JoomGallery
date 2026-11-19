<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Model\CollectionModel;
use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Form controller for collection operations.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class CollectionController extends JoomFormController
{
  /**
   * List view used after a collection form operation completes.
   *
   * @var    string
   *
   * @since  DEPLOY_VERSION
   */
  protected $view_list = 'collections';

  /**
   * Execute an allowed collection task.
   *
   * Existing collection records are managed only through the restricted modal
   * operations exposed by this controller.
   *
   * @param   string  $task  Task name to execute.
   *
   * @return  mixed  The result returned by the parent controller.
   *
   * @throws  \RuntimeException  When the requested task is not allowed.
   *
   * @since   DEPLOY_VERSION
   */
  public function execute($task)
  {
    // Reject tasks that could edit existing collections outside the modal workflow.
    if(!\in_array($task, ['add', 'save', 'cancel', 'manage', 'download'], true))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    // Delegate the allowed task to Joomla's form controller.
    return parent::execute($task);
  }

  /**
   * Open the creation form for a new collection.
   *
   * @return  boolean  True after the redirect has been configured.
   *
   * @throws  \RuntimeException  When the current user cannot create a collection.
   *
   * @since   DEPLOY_VERSION
   */
  public function add()
  {
    // Load the collection model used for the creation permission check.
    $model = $this->getModel('Collection', 'Administrator');

    // Deny access when the current user cannot create a collection.
    if(!$model->canCreateCollection())
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    // Clear stale form data and redirect to the creation-only edit layout.
    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collection&layout=edit', false));

    return true;
  }

  /**
   * Validate and create a new collection.
   *
   * Existing collections cannot be edited through this form endpoint.
   *
   * @param   string|null  $key     Record key name supplied by Joomla.
   * @param   string|null  $urlVar  Request variable containing the record ID.
   *
   * @return  boolean  True on success, false when validation or creation fails.
   *
   * @throws  \RuntimeException  When an existing record is submitted or creation is not permitted.
   *
   * @since   DEPLOY_VERSION
   */
  public function save($key = null, $urlVar = null)
  {
    // Reject forged form submissions.
    $this->checkToken();

    // Read the submitted collection form data.
    $data = $this->input->post->get('jform', [], 'array');

    // Keep this endpoint restricted to new collections.
    if(!empty($data['id']) || $this->input->getInt('id'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    // Check whether the requested owner may receive the new collection.
    $model = $this->getModel('Collection', 'Administrator');

    if(!$model->canCreateCollection((int) ($data['userid'] ?? 0)))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    // Filter the submitted values through the creation form.
    $form  = $model->getCreationForm();
    $valid = $form ? $model->validate($form, $data) : false;

    // Create the collection and convert model validation errors into one exception.
    try
    {
      if($valid === false || !$model->createCollection($valid))
      {
        throw new \RuntimeException(implode("\n", array_map(static fn($error) => $error instanceof \Throwable ? $error->getMessage() : (string) $error, $model->getErrors())));
      }
    }
    catch(\Throwable $e)
    {
      // Preserve the submitted data and return the user to the form on failure.
      $this->app->setUserState('com_joomgallery.collection.create.data', $data);
      $this->app->enqueueMessage($e->getMessage(), 'error');
      $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collection&layout=edit', false));

      return false;
    }

    // Clear saved form data and return to the list after successful creation.
    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_COLLECTION_CREATED'));
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));

    return true;
  }

  /**
   * Cancel collection creation and return to the collections list.
   *
   * @param   string|null  $key  Record key name supplied by Joomla.
   *
   * @return  boolean  True after the redirect has been configured.
   *
   * @since   DEPLOY_VERSION
   */
  public function cancel($key = null)
  {
    // Reject forged cancellation requests.
    $this->checkToken();

    // Discard saved form data and return to the collection list.
    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));

    return true;
  }

  /**
   * Apply one restricted management operation to an existing collection.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function manage(): void
  {
    // Reject forged management requests.
    $this->checkToken();

    // Load the model that validates and executes the requested operation.
    $model = $this->getModel('Collection', 'Administrator');

    // Execute the operation and report its result through Joomla messages.
    try
    {
      $model->manageCollection(
        $this->input->post->getInt('id', 0),
        $this->input->post->getCmd('operation', ''),
        $this->input->post->getInt('userid', 0)
      );
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_COLLECTION_UPDATED'));
    }
    catch(\Throwable $e)
    {
      $this->app->enqueueMessage($e->getMessage(), 'error');
    }

    // Always return to the collection list after the modal operation.
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));
  }

  /**
   * Deliver all images in a collection as a cached ZIP archive.
   *
   * @return  void
   *
   * @since   __DEPLOY_VERSION__
   */
  public function download(): void
  {
    // Read and normalize the archive request parameters.
    $collectionId  = $this->input->getInt('id', 0);
    $type          = strtolower($this->input->getCmd('type', ''));
    $forceRecreate = (bool) $this->input->getInt('force_recreate', $this->input->getInt('froce_recreate', 0));

    try
    {
      // Reject incomplete archive requests.
      if($collectionId <= 0 || $type === '')
      {
        throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_DOWNLOAD_INVALID_REQUEST'), 400);
      }

      // Check access to the requested collection.
      if(!$this->accessCollection($collectionId))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 403);
      }

      // Load or create an archive containing only images that pass access checks.
      /** @var CollectionModel $model */
      $model   = $this->getModel('Collection', 'Administrator');
      $archive = $model->prepareDownloadArchive(
        $collectionId,
        $type,
        $forceRecreate,
        fn(object $image, string $imageType): bool => $this->access((int) $image->id, $imageType)
      );

      // Count every image represented by this download request.
      /** @var ImageModel $imageModel */
      $imageModel = $this->getModel('Image', 'Administrator');
      $imageModel->incrementDownloads($archive['image_ids']);
      $this->outputDownloadArchive($archive['path'], $archive['name']);
    }
    catch(\Throwable $e)
    {
      // Convert expected request failures into their matching HTTP status.
      $status = \in_array((int) $e->getCode(), [400, 403, 404], true) ? (int) $e->getCode() : 500;

      // Log unexpected archive failures without exposing details to the browser.
      if($status === 500)
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      // Send a plain-text response suitable for this raw download endpoint.
      $message = $status === 500 ? Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED') : $e->getMessage();
      $this->outputError($status, $message);
    }
  }

  /**
   * Check whether the current application permits accessing this collection.
   *
   * Site controllers may override this method with their visibility checks.
   *
   * @param   int  $id  Collection ID.
   *
   * @return  boolean  True when the collection may be accessed.
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function accessCollection($id)
  {
    // Allow administrator access by default; site controllers may override this decision.
    return true;
  }

  /**
   * Check whether the current application permits adding an image to an archive.
   *
   * Site controllers may override this method with their visibility checks.
   *
   * @param   int     $id    Image ID.
   * @param   string  $type  Requested image type.
   *
   * @return  boolean  True when the image may be added to the archive.
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function access($id, $type = 'thumbnail')
  {
    // Allow administrator access by default; site controllers may override this decision.
    return true;
  }

  /**
   * Send a local ZIP archive as a browser attachment and finish the request.
   *
   * @param   string  $path      Absolute path to the ZIP archive.
   * @param   string  $filename  Filename presented to the browser.
   *
   * @return  void
   *
   * @throws  \RuntimeException  When the archive cannot be read.
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function outputDownloadArchive(string $path, string $filename): void
  {
    // Verify that the prepared archive can still be read.
    if(!is_file($path) || !is_readable($path))
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_FILE_NOT_FOUND'), 404);
    }

    // Strip path and control characters from the browser filename.
    $filename = str_replace(["\r", "\n"], '', basename($filename));

    // Configure attachment, size, cache and content-safety headers.
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

    // Remove buffered template, warning or debug output before the ZIP bytes.
    while(ob_get_level() > 0)
    {
      ob_end_clean();
    }

    // Stream the archive and terminate the raw request.
    $this->app->sendHeaders();
    readfile($path);
    $this->app->close();
  }

  /**
   * Send an error response for a raw endpoint.
   *
   * @param   int     $status   HTTP status code.
   * @param   string  $message  Plain-text error message.
   *
   * @return  void
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function outputError(int $status, string $message): void
  {
    // Configure the status, content, cache and content-safety headers.
    $this->app->setHeader('Status', (string) $status, true);
    $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
    $this->app->setHeader('Content-Length', (string) \strlen($message), true);
    $this->app->setHeader('Cache-Control', 'no-store', true);
    $this->app->setHeader('X-Content-Type-Options', 'nosniff', true);

    // Remove buffered output that could corrupt the error response.
    while(ob_get_level() > 0)
    {
      ob_end_clean();
    }

    // Send the plain-text error and terminate the raw request.
    $this->app->sendHeaders();
    echo $message;
    $this->app->close();
  }
}
