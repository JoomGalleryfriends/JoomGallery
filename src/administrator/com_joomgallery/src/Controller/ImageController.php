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
use Joomla\Database\ParameterType;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

/**
 * Image controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageController extends JoomFormController
{
  protected $view_list      = 'images';
  protected $savedVariantId = 0;

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

    // Return to the variant editor after saving so it can close its popup.
    $originId          = $this->input->getInt('origin_id', 0);
    $forcedLanguage    = $this->input->getString('forcedLanguage', '');
    $isLanguageVariant = $originId > 0 && $forcedLanguage !== '';

    if($isLanguageVariant && $task === 'save')
    {
      $result = parent::save($key, $urlVar);

      if($result)
      {
        $savedId = $this->savedVariantId;

        $url = 'index.php?option=com_joomgallery&view=image&layout=edit'
          . '&id=' . $savedId
          . '&origin_id=' . $originId
          . '&forcedLanguage=' . rawurlencode($forcedLanguage)
          . '&variant_saved=1';

        $this->setRedirect(\Joomla\CMS\Router\Route::_($url, false));
      }

      return $result;
    }

    return parent::save($key, $urlVar);
  }

  /**
   * Convert an associated-image permission denial to the same blue information
   * message used by the image-list action and the category editor.
   *
   * @param   string  $text  Message to display on redirect.
   * @param   string  $type  Message type.
   *
   * @return  string  Previous message.
   *
   * @since   4.5.0
   */
  public function setMessage($text, $type = 'message')
  {
    $permissionError = Text::_('JLIB_APPLICATION_ERROR_EDIT_NOT_PERMITTED');
    $saveFailedError = Text::sprintf('JLIB_APPLICATION_ERROR_SAVE_FAILED', $permissionError);

    if($type === 'error' && $text === $saveFailedError)
    {
      $this->component->addWarning($permissionError);

      return null;
    }

    return parent::setMessage($text, $type);
  }

  protected function postSaveHook(\Joomla\CMS\MVC\Model\BaseDatabaseModel $model, $validData = [])
  {
    $originId       = $this->input->getInt('origin_id', 0);
    $forcedLanguage = $this->input->getString('forcedLanguage', '');

    if($originId > 0 && $forcedLanguage !== '')
    {
    $this->savedVariantId = (int) $model->getState(
        $model->getName() . '.id',
        0
    );
    }

    parent::postSaveHook($model, $validData);
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

  /**
   * Delete a language variant that is associated with the given source image.
   *
   * @return  void
   *
   * @since   4.5.0
   */
  public function deleteVariant()
  {
    // Check for request forgeries.
    $this->checkToken('post');

    $model          = $this->getModel();
    $variantId      = $this->input->post->getInt('id', 0);
    $originId       = $this->input->post->getInt('origin_id', 0);
    $forcedLanguage = $this->input->post->getString('forcedLanguage', '');

    try
    {
      if($variantId < 1 || $originId < 1 || $variantId === $originId || $forcedLanguage === '')
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_VARIANT_INVALID'));
      }

      $variantTable = $model->getTable();

      if(!$variantTable->load($variantId) || $variantTable->language !== $forcedLanguage)
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_VARIANT_INVALID'));
      }

      $originTable = $model->getTable();

      if(!$originTable->load($originId) || $originTable->language === $forcedLanguage)
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_VARIANT_INVALID'));
      }

      // Only allow deletion when both records belong to the same association group.
      $db       = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
      $context1 = 'com_joomgallery.image';
      $context2 = 'com_joomgallery.image';
      $query    = $db->getQuery(true)
        ->select($db->quoteName('a1.key'))
        ->from($db->quoteName('#__associations', 'a1'))
        ->join(
            'INNER',
            $db->quoteName('#__associations', 'a2'),
            $db->quoteName('a1.key') . ' = ' . $db->quoteName('a2.key')
        )
        ->where(
            [
              $db->quoteName('a1.context') . ' = :context1',
              $db->quoteName('a2.context') . ' = :context2',
              $db->quoteName('a1.id') . ' = :originId',
              $db->quoteName('a2.id') . ' = :variantId',
            ]
        );

      // Joomla's bind() expects variables passed by reference.
      $query->bind(':context1', $context1, ParameterType::STRING);
      $query->bind(':context2', $context2, ParameterType::STRING);
      $query->bind(':originId', $originId, ParameterType::INTEGER);
      $query->bind(':variantId', $variantId, ParameterType::INTEGER);

      $db->setQuery($query);

      if(!$db->loadResult())
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_ERROR_DELETE_VARIANT_INVALID'));
      }

      $pks = [$variantId];

      if(!$model->delete($pks))
      {
        throw new \RuntimeException(
            $model->getError() ?: Text::_('COM_JOOMGALLERY_ERROR_DELETE_VARIANT_FAILED')
        );
      }

    echo new JsonResponse(
        ['id' => $variantId],
        Text::_('COM_JOOMGALLERY_SUCCESS_DELETE_VARIANT')
    );
    }
    catch(\Throwable $e)
    {
    echo new JsonResponse(
        null,
        Text::_('COM_JOOMGALLERY_ERROR_DELETE_VARIANT_FAILED'),
        true
    );
    }

    $this->app->close();
  }

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
