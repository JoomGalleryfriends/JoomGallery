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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;
use Joomla\Registry\Registry;

/**
 * Image controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomFormController
{
  protected $view_list = 'images';

  /**
   * Method to save a record.
   *
   * @param   string  $key     The name of the primary key of the URL variable.
   * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
   *
   * @return  boolean  True if successful, false otherwise.
   *
   * @since   4.0.0
   */
  public function save($key = null, $urlVar = null)
  {
    $task = $this->getTask();

    // The save2copy task needs to be handled slightly differently.
    if($task === 'save2copy')
    {
      $this->input->set('origin_id', $this->input->getInt('id'));
    }

    return parent::save($key, $urlVar);
  }

  /**
   * Deliver an allowed image type as a browser download.
   *
   * The effective image configuration controls whether downloading is enabled,
   * which stored image type is available, and whether a watermark is applied.
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

      /** @var ImageModel $model */
      $model = $this->getModel('Image', 'Administrator');
      $image = $model->getItem($imageId);

      if(!$image || (int) $image->id !== $imageId)
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_DOWNLOAD_IMAGE_NOT_FOUND'), 404);
      }

      $this->component->createConfig(_JOOM_OPTION . '.image', $imageId, true);
      $config       = $this->component->getConfig();
      $allowedTypes = $model->getDownloadTypes($image, $config);

      if(!\in_array($type, $allowedTypes, true))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_DOWNLOAD_NOT_ALLOWED'), 403);
      }

      $path = JoomHelper::getImg($image, $type, false, false);

      if(!$path)
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_DOWNLOAD_FILE_NOT_FOUND'), 404);
      }

      $this->component->createFilesystem((string) $image->filesystem);

      // Check the requested file explicitly so getResource() cannot silently
      // turn a missing download into the generic no-image placeholder.
      $file = $this->component->getFilesystem()->getFile($path);

      if(($file->type ?? '') !== 'file')
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_DOWNLOAD_FILE_NOT_FOUND'), 404);
      }

      [$fileInfo, $resource] = $this->component->getFilesystem()->getResource($path);
      $this->processDownloadResource($fileInfo, $resource, $type, $config);
      $this->outputDownloadResource($resource, $fileInfo, (string) $image->filename);
    }
    catch(\Throwable $e)
    {
      $status = \in_array((int) $e->getCode(), [400, 403, 404], true) ? (int) $e->getCode() : 500;

      if($status === 500)
      {
        $this->component->addLog($e->getMessage(), 'error', 'jerror');
      }

      $message = $status === 500 ? Text::_('COM_JOOMGALLERY_DOWNLOAD_PROCESSING_FAILED') : $e->getMessage();
      $this->outputDownloadError($status, $message);
    }
  }

  /**
   * Apply dynamic image settings and the download-watermark setting.
   *
   * @param   object    $fileInfo  Filesystem metadata, updated after processing.
   * @param   resource  $resource  Source resource, replaced by the processed resource.
   * @param   string    $type      Requested image type.
   * @param   object    $config    Effective image configuration.
   *
   * @return  void
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function processDownloadResource(object &$fileInfo, &$resource, string $type, object $config): void
  {
    $params = null;

    foreach((array) $config->get('jg_dynamicprocessing', []) as $candidate)
    {
      $candidateParams = new Registry($candidate);

      if((string) $candidateParams->get('jg_imgtypename') === $type)
      {
        $params = $candidateParams;

        break;
      }
    }

    $dynamic   = $params instanceof Registry && (int) $params->get('jg_imgtype', 0) === 1;
    $watermark = (int) $config->get('jg_downloadwithwatermark', 0) === 1;
    $orient    = $dynamic && (int) $params->get('jg_imgtypeorinet', 0) === 1;
    $resize    = $dynamic ? (int) $params->get('jg_imgtyperesize', 0) : 0;

    if(!$orient && $resize <= 0 && !$watermark)
    {
      return;
    }

    $this->component->createIMGtools($config->get('jg_imgprocessor', 'gd'));

    try
    {
      $tools = $this->component->getIMGtools();

      if(!$tools->read($resource, true))
      {
        throw new \RuntimeException('Unable to read the image resource for download processing.');
      }

      if($orient && !$tools->orient())
      {
        throw new \RuntimeException('Unable to orient the download image.');
      }

    if($resize > 0 && !$tools->resize(
        $resize,
        $params->get('jg_imgtypewidth', 5000),
        $params->get('jg_imgtypeheight', 5000),
        $params->get('jg_cropposition', 2),
        $params->get('jg_imgtypesharpen', 0)
    ))
      {
        throw new \RuntimeException('Unable to resize the download image.');
    }

      if($watermark)
      {
        $watermarkSettings = $params instanceof Registry ? $params : new Registry();

        if(!$tools->watermark(
            JPATH_ROOT . \DIRECTORY_SEPARATOR . $config->get('jg_wmfile', 'media/joomgallery/images/watermark.png'),
            $watermarkSettings->get('jg_imgtypewtmsettings.jg_watermarkpos', 9),
            $watermarkSettings->get('jg_imgtypewtmsettings.jg_watermarkzoom', 0),
            $watermarkSettings->get('jg_imgtypewtmsettings.jg_watermarksize', 15),
            $watermarkSettings->get('jg_imgtypewtmsettings.jg_watermarkopacity', 80)
        ))
        {
          throw new \RuntimeException('Unable to watermark the download image.');
        }
      }

      $quality = $dynamic ? (int) $params->get('jg_imgtypequality', 100) : 100;
      $content = $tools->stream($quality, false);

      if($content === '')
      {
        throw new \RuntimeException('The processed download image is empty.');
      }

      $processed = fopen('php://temp', 'w+b');

      if($processed === false || fwrite($processed, $content) === false)
      {
        throw new \RuntimeException('Unable to create the processed download resource.');
      }

      rewind($processed);
      $stat      = fstat($processed);
      $imageInfo = getimagesizefromstring($content);

      if(\is_resource($resource))
      {
        fclose($resource);
      }

      $resource       = $processed;
      $fileInfo->size = (int) ($stat['size'] ?? \strlen($content));

      if(\is_array($imageInfo))
      {
        $fileInfo->width     = (int) $imageInfo[0];
        $fileInfo->height    = (int) $imageInfo[1];
        $fileInfo->mime_type = $imageInfo['mime'] ?? $fileInfo->mime_type;
      }
    }
    finally
    {
      $this->component->delIMGtools();
    }
  }

  /** Send an image resource as an attachment and finish the request. */
  protected function outputDownloadResource($resource, object $fileInfo, string $filename): void
  {
    $filename = str_replace(["\r", "\n"], '', basename($filename));
    $mimeType = (string) ($fileInfo->mime_type ?? 'application/octet-stream');
    $size     = (int) ($fileInfo->size ?? 0);

    $this->app->setHeader('Content-Type', $mimeType, true);
    $this->app->setHeader(
        'Content-Disposition',
        'attachment; filename="' . addcslashes($filename, '"\\') . '"; filename*=UTF-8\'\'' . rawurlencode($filename),
        true
    );
    $this->app->setHeader('Content-Length', (string) $size, true);
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

  /** Send a plain-text download error and finish the request. */
  protected function outputDownloadError(int $status, string $message): void
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

  /**
   * Method to add multiple new image records.
   *
   * @return  boolean  True if the record can be added, false if not.
   *
   * @since   4.0
   */
  public function multipleadd()
  {
    $this->view_item = 'image';
    $layout          = 'upload';

    $context = "$this->option.upload.$this->context";

    // Access check.
    if(!$this->allowAdd())
    {
        // Set the internal error and also the redirect error.
        $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
        $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error', 'jerror');

        $this->setRedirect(
            Route::_(
                'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
                false
            )
        );

        return false;
    }

    // Clear the record edit information from the session.
    $this->app->setUserState($context . '.data', null);

    // Redirect to the edit screen.
    $this->setRedirect(
        Route::_(
            'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=' . $layout,
            false
        )
    );

    return true;
  }

  /**
   * Method to open the FTP import page.
   *
   * @return  boolean  True if the record can be added, false if not.
   *
   * @since   4.5.0
   */
  public function ftpimport()
  {
    $this->view_item = 'image';
    $layout          = 'ftp';

    $context = "$this->option.upload.$this->context";

    // Access check.
    if(!$this->allowAdd())
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
      $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error', 'jerror');

    $this->setRedirect(
        Route::_(
            'index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(),
            false
        )
    );

      return false;
    }

    // Clear the record edit information from the session.
    $this->app->setUserState($context . '.data', null);

    // Redirect to the FTP import screen.
    $this->setRedirect(
        Route::_(
            'index.php?option=' . $this->option . '&view=' . $this->view_item . '&layout=' . $layout,
            false
        )
    );

    return true;
  }

  /**
   * Method to add multiple new image records.
   *
   * @return  boolean  True if the record can be added, false if not.
   *
   * @since   4.0
   */
  public function ajaxsave()
  {
    $result = ['error' => false];

    try
    {
      if(!parent::save())
      {
        $result['success'] = false;
        $result['error']   = $this->message ?: $this->component->getError(true);
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

      return false;
    }

    return true;
  }

  /**
   * Return files available in the server-side FTP import directory.
   *
   * @return  boolean
   *
   * @since   4.5.0
   */
  public function ftplist()
  {
    $result = [
      'success' => false,
      'files'   => [],
      'path'    => '',
      'error'   => '',
    ];

    try
    {
      if(!$this->allowAdd())
      {
        throw new \Exception(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 403);
      }

      $directory      = $this->getFtpImportDirectory();
      $result['path'] = str_replace(JPATH_ROOT, '', $directory);

      if(!is_dir($directory) && !Folder::create($directory))
      {
        throw new \Exception(Text::sprintf('COM_JOOMGALLERY_FTP_IMPORT_ERROR_CREATE_DIRECTORY', $result['path']));
      }

      $allowed_extensions = $this->getFtpImportExtensions();
      $files              = [];

      foreach(new \DirectoryIterator($directory) as $file)
      {
        if(!$file->isFile() || $file->isDot())
        {
          continue;
        }

        $filename = $file->getFilename();

        if(!\in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $allowed_extensions, true))
        {
          continue;
        }

        $files[] = [
          'name'  => $filename,
          'size'  => $file->getSize(),
          'mtime' => $file->getMTime(),
        ];
      }

    usort(
        $files,
        function ($a, $b) {
          return strnatcasecmp($a['name'], $b['name']);
        }
    );

      $result['success'] = true;
      $result['files']   = $files;
    }
    catch(\Exception $e)
    {
      $result['error'] = $e->getMessage();
    }

    echo new JsonResponse($result);
    $this->app->close();

    return true;
  }

  /**
   * Get the active FTP import directory.
   *
   * @return  string
   *
   * @since   4.5.0
   */
  protected function getFtpImportDirectory(): string
  {
    $config = JoomHelper::getService('config');
    $path   = trim((string) $config->get('jg_pathftpupload', ''));

    return $this->normalizeFtpImportPath($path !== '' ? $path : 'images/joomgallery/FTP');
  }

  /**
   * Normalize a configured FTP import path.
   *
   * @param   string  $path  Configured path
   *
   * @return  string
   *
   * @since   4.5.0
   */
  protected function normalizeFtpImportPath(string $path): string
  {
    $path = trim($path);

    if($path === '')
    {
      return '';
    }

    if(!preg_match('#^([A-Z]:[\\\\/]|/)#i', $path))
    {
      $path = JPATH_ROOT . '/' . ltrim($path, '/\\');
    }

    return Path::clean(rtrim($path, '/\\'));
  }

  /**
   * Get image extensions allowed for FTP import.
   *
   * @return  array
   *
   * @since   4.5.0
   */
  protected function getFtpImportExtensions(): array
  {
    $config     = JoomHelper::getService('config');
    $extensions = array_filter(
        array_map(
            function ($extension) {
              return strtolower(ltrim(trim((string) $extension), '.'));
            },
            explode(',', (string) $config->get('jg_imagetypes', 'jpg,jpeg,png,gif,webp'))
        )
    );

    if(empty($extensions))
    {
      $extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }

    if(\in_array('jpg', $extensions, true) || \in_array('jpeg', $extensions, true) || \in_array('jpe', $extensions, true) || \in_array('jfif', $extensions, true))
    {
      $extensions = array_unique(array_merge($extensions, ['jpg', 'jpeg', 'jpe', 'jfif']));
    }

    return array_values($extensions);
  }

  /**
   * Method to exchange/replace an existing imagetype.
   *
   * @return  boolean  True if imagetype is successfully replaced, false if not.
   *
   * @since   4.0
   */
  public function replace()
  {
    // Check for request forgeries.
    $this->checkToken();

    $app = $this->app;
    /** @var ImageModel $model */
    $model   = $this->getModel();
    $data    = $this->input->post->get('jform', [], 'array');
    $context = (string) _JOOM_OPTION . '.' . $this->context . '.replace';
    $id      = \intval($data['id']);

    // Access check.
    if(!$this->allowSave($data, $id))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');
      $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error', 'jerror');

    $this->setRedirect(
        Route::_('index.php?option=' . _JOOM_OPTION . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false)
    );

      return false;
    }

    // Load form data
    $form = $model->getForm($data, false);

    if(!$form)
    {
      $this->setMessage($model->getError(), 'error');

      return false;
    }
    $form->setFieldAttribute('title', 'required', false);
    $form->setFieldAttribute('catid', 'required', false);
    $form->setFieldAttribute('replacetype', 'required', true);
    $form->setFieldAttribute('image', 'required', true);

    // Test whether the data is valid.
    $validData = $model->validate($form, $data);

    // Check for validation errors.
    if($validData === false)
    {
      // Get the validation messages.
      $errors = $model->getErrors();

      // Push up to three validation messages out to the user.
      for($i = 0, $n = \count($errors); $i < $n && $i < 3; $i++)
      {
        if($errors[$i] instanceof \Exception)
        {
          $this->setMessage($errors[$i]->getMessage(), 'warning');
        }
        else
        {
          $this->setMessage($errors[$i], 'warning');
        }
      }

      // Save the data in the session.
      $app->setUserState($context . '.data', $data);

      // Redirect back to the replace screen.
    $this->setRedirect(
        Route::_('index.php?option=' . _JOOM_OPTION . '&view=image&layout=replace&id=' . $id, false)
    );

      return false;
    }

    // Attempt to replace the image.
    if(!$model->replace($validData))
    {
      // Save the data in the session.
      $app->setUserState($context . '.data', $validData);

      // Redirect back to the replace screen.
      $this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_REPLACE_IMAGETYPE', ucfirst($validData['replacetype']), $model->getError()), 'error');
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_REPLACE_IMAGETYPE', ucfirst($validData['replacetype']), $model->getError()), 'error', 'jerror');

    $this->setRedirect(
        Route::_('index.php?option=' . _JOOM_OPTION . '&view=image&layout=replace&id=' . $id, false)
    );

      return false;
    }

    // Set message
    $this->setMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_REPLACE_IMAGETYPE', ucfirst($validData['replacetype'])));
    $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SUCCESS_REPLACE_IMAGETYPE', ucfirst($validData['replacetype'])), 'error', 'jerror');

    // Clear the data from the session.
    $app->setUserState($context . '.data', null);

    // Redirect to edit screen
    $url = 'index.php?option=' . _JOOM_OPTION . '&view=image&layout=edit&id=' . $id;

    // Check if there is a return value
    $return = $this->input->get('return', null, 'base64');

    if(!\is_null($return) && Uri::isInternal(base64_decode($return)))
    {
      $url = base64_decode($return);
    }

    // Redirect to the list screen.
    $this->setRedirect(Route::_($url, false));

    return true;
  }

  /**
   * Method to cancel an edit.
   *
   * @param   string  $key  The name of the primary key of the URL variable.
   *
   * @return  boolean  True if access level checks pass, false otherwise.
   *
   * @since   4.0.0
   */
  public function cancel($key = null)
  {
    $isOk = parent::cancel($key);

    if($this->input->get('layout', 'edit', 'cmd') == 'replace')
    {
      // Redirect to the edit screen.
      $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=image&layout=edit&id=' . $this->input->getInt('id'), false));
    }

    return $isOk;
  }

  /**
   * Method to save metadata to an image file
   *
   * @return  boolean  True if metada was saved successfully, false otherwise.
   *
   * @since   4.1.0
   */
  public function savemetadata()
  {
    // Check for request forgeries.
    $this->checkToken();

    $model = $this->getModel();
    $data  = $this->input->post->get('jform', [], 'array');

    // Access check.
    if(!$this->allowSave($data, 'id'))
    {
      $this->setMessage(Text::_('JLIB_APPLICATION_ERROR_SAVE_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));

      return false;
    }

    // Define redirect url
    $url = 'index.php?option=' . _JOOM_OPTION . '&view=image&layout=edit&id=' . $data['id'];

    // Check if there is an imagetype given with the request
    $imagetype = $this->input->get('imagetype', 'original', 'word');

    // Attempt to save the metadata.
    if(!$model->savemetadata((int) $data['id'], $imagetype))
    {
      // Redirect back to the image edit screen.
      $this->setMessage(Text::_('COM_JOOMGALLERY_ERROR_SAVE_METADATA_TO_FILE', $model->getError()), 'error');
      $this->setRedirect(Route::_($url, false));

      return false;
    }

    // Set message
    $this->setMessage(Text::_('COM_JOOMGALLERY_SUCCESS_SAVE_METADATA_TO_FILE'));

    // Check if there is a return value
    $return = $this->input->get('return', null, 'base64');

    if(!\is_null($return) && Uri::isInternal(base64_decode($return)))
    {
      $url = base64_decode($return);
    }

    // Redirect to the list screen.
    $this->setRedirect(Route::_($url, false));

    return true;
  }

  public function ajaxsavetags()
  {
    // Check for request forgeries.
    $this->checkToken();

    $result = ['error' => false, 'success' => true];

    try {
      /** @var ImageModel $model */
      $model   = $this->getModel();
      $id      = $this->input->get('id', 0, 'int');
      $tags    = $this->input->get('keywords', [], 'array');
      $action  = $this->input->get('action', '', 'string');
      $context = (string) _JOOM_OPTION . '.' . $this->context . '.savetags';


      // Access check.
      if(!$this->allowEdit(['id' => $id, 'tags' => $tags]))
      {
        // Set the internal error and also the redirect error.
        $result['success'] = false;
        $result['error']   = Text::_('JLIB_APPLICATION_ERROR_SAVE_RECORD_NOT_PERMITTED');

        return false;
      }

      if($action && $action == 'add')
      {
        // Attempt to add the tags.
        if(!$model->addTags($id, $tags))
        {
          // Redirect back to the replace screen.
          $result['success'] = false;
          $result['error']   = $model->getError();

          return false;
        }
      }
      elseif($action && $action == 'remove')
      {
        // Attempt to remove the tags.
        if(!$model->removeTags($id, $tags))
        {
          // Redirect back to the replace screen.
          $result['success'] = false;
          $result['error']   = $model->getError();

          return false;
        }
      }

      $json = json_encode($result, JSON_FORCE_OBJECT);
      echo new JsonResponse($json);

      $this->app->close();
    }
    catch(\Exception $e)
    {
      echo new JsonResponse($e);

      $this->app->close();

      return false;
    }

    return true;
  }

  /**
   * Acquire, renew or release the per-session tags generation lock.
   *
   * The owner and expiry values make the boolean session flag safe for use
   * across browser tabs and recoverable when a tab disappears unexpectedly.
   *
   * @return void
   */
  public function ajaxtagsgeneratelock()
  {
    $this->checkToken();

    $activeKey = 'jgaiinterface.tags.generate.active';
    $ownerKey  = 'jgaiinterface.tags.generate.owner';
    $expiryKey = 'jgaiinterface.tags.generate.expires';
    $leaseTime = 300;
    $session   = $this->app->getSession();
    $action    = $this->input->getCmd('action');
    $owner     = preg_replace('/[^a-zA-Z0-9-]/', '', $this->input->getString('owner'));
    $now       = time();

    $active      = (bool) $session->get($activeKey, false);
    $activeOwner = (string) $session->get($ownerKey, '');
    $expires     = (int) $session->get($expiryKey, 0);

    // Recover a lock left behind by a closed or crashed browser tab.
    if($active && $expires <= $now)
    {
      $active      = false;
      $activeOwner = '';
      $expires     = 0;
      $session->set($activeKey, false);
      $session->set($ownerKey, '');
      $session->set($expiryKey, 0);
    }

    $result = [
      'active'      => $active,
      'acquired'    => false,
      'renewed'     => false,
      'released'    => false,
      'retry_after' => $active ? 2 : 0,
    ];

    // Merely reaching this point has cleared an expired lock, if present.
    if($action === 'cleanup')
    {
      echo new JsonResponse($result);
      $this->app->close();
    }

    if($owner === '')
    {
      echo new JsonResponse($result, 'Missing lock owner.', true);
      $this->app->close();
    }

    if($action === 'acquire' && (!$active || $activeOwner === $owner))
    {
      $expires = $now + $leaseTime;
      $session->set($activeKey, true);
      $session->set($ownerKey, $owner);
      $session->set($expiryKey, $expires);

      $result['active']      = true;
      $result['acquired']    = true;
      $result['retry_after'] = 0;
      $result['expires']     = $expires;
    }
    elseif($action === 'renew' && $active && $activeOwner === $owner)
    {
      $expires = $now + $leaseTime;
      $session->set($expiryKey, $expires);

      $result['active']  = true;
      $result['renewed'] = true;
      $result['expires'] = $expires;
    }
    elseif($action === 'release' && $activeOwner === $owner)
    {
      $session->set($activeKey, false);
      $session->set($ownerKey, '');
      $session->set($expiryKey, 0);

      $result['active']      = false;
      $result['released']    = true;
      $result['retry_after'] = 0;
    }

    echo new JsonResponse($result);
    $this->app->close();
  }

  /**
   * Method to open an image in the media manager
   *
   * @return  boolean  True if metada was saved successfully, false otherwise.
   *
   * @since   4.2.0
   */
  public function openmedia()
  {
    $this->cancel();

    $path = $this->input->get('mediapath', '', 'string');

    // Redirect to media manager
    $this->setRedirect(Route::_('index.php?option=com_media&view=file&path=' . $path, false));

    return true;
  }
}
