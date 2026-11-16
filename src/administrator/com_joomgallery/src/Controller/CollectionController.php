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

  /** Existing records are managed only through the restricted modal actions. */
  public function execute($task)
  {
    if(!\in_array($task, ['add', 'save', 'cancel', 'manage', 'download'], true))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    return parent::execute($task);
  }

  public function add()
  {
    $model = $this->getModel('Collection', 'Administrator');

    if(!$model->canCreateCollection())
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collection&layout=edit', false));

    return true;
  }

  public function save($key = null, $urlVar = null)
  {
    $this->checkToken();

    $data = $this->input->post->get('jform', [], 'array');

    if(!empty($data['id']) || $this->input->getInt('id'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $model = $this->getModel('Collection', 'Administrator');

    if(!$model->canCreateCollection((int) ($data['userid'] ?? 0)))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $form  = $model->getCreationForm();
    $valid = $form ? $model->validate($form, $data) : false;

    try
    {
      if($valid === false || !$model->createCollection($valid))
      {
        throw new \RuntimeException(implode("\n", array_map(static fn($error) => $error instanceof \Throwable ? $error->getMessage() : (string) $error, $model->getErrors())));
      }
    }
    catch(\Throwable $e)
    {
      $this->app->setUserState('com_joomgallery.collection.create.data', $data);
      $this->app->enqueueMessage($e->getMessage(), 'error');
      $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collection&layout=edit', false));

      return false;
    }

    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_COLLECTION_CREATED'));
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));

    return true;
  }

  public function cancel($key = null)
  {
    $this->checkToken();
    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));

    return true;
  }

  public function manage(): void
  {
    $this->checkToken();
    $model = $this->getModel('Collection', 'Administrator');

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
    $collectionId = $this->input->getInt('id', 0);
    $type         = strtolower($this->input->getCmd('type', ''));

    try
    {
      $model   = $this->getModel('Collection', 'Administrator');
      $archive = $model->prepareDownloadArchive($collectionId, $type);
      $this->outputDownloadArchive($archive['path'], $archive['name']);
    }
    catch(\Throwable $e)
    {
      $status = \in_array((int) $e->getCode(), [400, 403, 404], true) ? (int) $e->getCode() : 500;

      if($status === 500)
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $message = $status === 500 ? Text::_('COM_JOOMGALLERY_COLLECTION_DOWNLOAD_CREATE_FAILED') : $e->getMessage();
      $this->outputDownloadError($status, $message);
    }
  }

  /** Send a local ZIP archive as a browser attachment and finish the request. */
  protected function outputDownloadArchive(string $path, string $filename): void
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

  /** Send a plain-text archive error and finish the request. */
  protected function outputDownloadError(int $status, string $message): void
  {
    $this->app->setHeader('Status', (string) $status, true);
    $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
    $this->app->setHeader('Content-Length', (string) strlen($message), true);
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
