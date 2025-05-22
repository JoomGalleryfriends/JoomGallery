<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Object\CMSObject;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Filesystem\File as JFile;
use \Joomla\Filesystem\Path as JPath;

use \Joomla\Component\Media\Administrator\Adapter\AdapterInterface;
use \Joomla\Component\Media\Administrator\Event\FetchMediaItemEvent;
use \Joomla\Component\Media\Administrator\Event\FetchMediaItemsEvent;
use \Joomla\Component\Media\Administrator\Event\FetchMediaItemUrlEvent;
use \Joomla\Component\Media\Administrator\Exception\FileExistsException;
use \Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use \Joomla\Component\Media\Administrator\Exception\InvalidPathException;
use \Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\FilesystemInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Filesystem\Exception\AdapterNotFoundException;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* Filesystem Base Class
*
* @package JoomGallery
*
* @since  4.0.0
*/
class Filesystem implements AdapterInterface, FilesystemInterface
{
  use ServiceTrait;
  use ProviderManagerHelperTrait;

  /**
   * The adapter name.
   * Scheme: adapter-rootfolder
   *
   * @var   string
   * @since  4.0.0
   */
  protected $filesystem = 'local-images';

  /**
   * The available extensions.
   *
   * @var   string[]
   * @since  4.0.0
   */
  private $allowedExtensions = null;

  /**
   * Root folder of the local filesystem
   *
   * @var string
   */
  protected $local_root = JPATH_ROOT;

  /**
   * Constructor
   *
   * @param  string  $filesystem  Name of the filesystem to use
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct(string $filesystem = '')
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Instantiate config service
    $this->component->createConfig();

    if($filesystem != '')
    {
      // Define filesystem adapter based on service creation parameter
      $this->filesystem = $filesystem;
    }
    else
    {
      // Define filesystem adapter based on configuration 'jg_filesystem'
      $this->filesystem = $this->component->getConfig()->get('jg_filesystem','local-images');
    }

    // Load language of com_media
    Factory::getApplication()->getLanguage()->load('com_media', JPATH_ADMINISTRATOR);
  }

  /**
   * Function to strip additional / or \ in a path name.
   *
   * @param   string  $path   The path to clean
   * @param   string  $ds     Directory separator (optional)
   *
   * @return  string  The cleaned path
   *
   * @since   4.0.0
   */
  public function cleanPath(string $path, string $ds=\DIRECTORY_SEPARATOR): string
  {
    return JPath::clean($path, $ds);
  }

  /**
   * Cleaning of file/category name
   * optionally replace extension if present
   * replace special chars defined in the configuration
   *
   * @param   string    $file        The file name
   * @param   integer   $with_ext    0: strip extension, 1: force extension, 2: leave it as it is (default: 2)
   * @param   string    $def_ext     Extension to use if $file given without extension
   *
   * @return  mixed     cleaned name on success, false otherwise
   *
   * @since   4.0.0
   */
  public function cleanFilename(string $file, int $with_ext=2, string $def_ext='jpg')
  {
    $ext = $this->getExt($file);

    // Replace extension if present
    if($ext)
    {
      $filename = \substr($file, 0, -\strlen($ext) - 1);
    }
    else
    {
      // No extension found (Batchupload)
      $filename = $file;
    }

    if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
    {
      $filename = OutputFilter::stringURLUnicodeSlug(trim($filename));
    }
    else
    {
      $filename = OutputFilter::stringURLSafe(trim($filename));
    }

    switch ($with_ext)
    {
      case 0:
        // strip extension
        break;

      case 1:
        // force extension
        if($ext)
        {
          $filename = $filename.'.'. \strtolower($ext);
        }
        else
        {
          $filename = $filename.'.'. \strtolower($def_ext);
        }
        break;

      default:
        if($ext)
        {
          $filename = $filename.'.'. \strtolower($ext);
        }
        break;
    }

    return $filename;
  }

  /**
   * Copies an index.html file into a specified folder
   *
   * @param   string   $path    The path where the index.html should be created
   *
   * @return  string
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function createIndexHtml(string $path): string
  {
    $adapter = $this->getFilesystem();

    list($service, $folder) = \explode('-', $adapter);

    if($service !== 'local')
    {
      return '';
    }

    // Content
    $html = '<html><body bgcolor="#FFFFFF"></body></html>';

    // File path
    $file = JPath::clean($this->local_root.\DIRECTORY_SEPARATOR.$folder.$path.\DIRECTORY_SEPARATOR.'index.html');

    if(\file_put_contents($file, $html))
    {
      return 'index.html';
    }
    else
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_FILE'), 'error', 'filesystem');
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_FILE'));
    }
  }

  /**
   * Sets the permission of a given file or folder recursively.
   *
   * @param   string  $path      The path to the file/folder
   * @param   string  $val       The octal representation of the value to change file/folder mode
   * @param   bool    $mode      True to use file mode. False to use folder mode. (default: true)
   *
   * @return  bool    True if successful [one fail means the whole operation failed].
   *
   * @since   4.0.0
   */
  public function chmod(string $path, string $val, bool $mode=true): bool
  {
    // complete folder path
    $path = $this->completePath($path);

    if($mode)
    {
      return JPath::setPermissions(JPath::clean($path), $val, null);
    }
    else
    {
      return JPath::setPermissions(JPath::clean($path), null, $val);
    }
  }

  /**
   * Get the file extension of a file.
   *
   * @param   string   $path   The filename or file path including extension
   *
   * @return  string   Extension (lowercase) if found, empty string otherwise.
   *
   * @since   4.0.0
   */
  public function getExt(string $file): string
  {
    try
    {
      // Joomla 5+
      $ext = JFile::getExt($file);
    }
    catch(\Throwable $th)
    {
      // Joomla 4-
      $ext = \Joomla\CMS\Filesystem\File::getExt($file);
    }    

    // Check if it is a valid extension
    $valid_rex = !\boolval(\preg_match('/[^a-zA-Z]/', $ext));  // File extension has to be only letters
    $valid_len = \strlen($ext) < 9; // File extension has to be shorter than 9 chars

    if($valid_rex && $valid_len)
    {
      return \strtolower($ext);
    }
    else
    {
      return '';
    }
  }

  /**
   * Returns the requested file or folder information. More information
   * can be found in AdapterInterface::getFile().
   *
   * @param   string     $path     The path to the file or folder
   * @param   array      $options  The options
   *
   * @return  \stdClass  Object with file information
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::getFile()
   */
  public function getFile(string $path = '/', array $options = []): \stdClass
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    // Add adapter prefix to the file returned
    try
    {
      $file = $this->getAdapter($adapter)->getFile($path);
    }
    catch (FileNotFoundException $e)
    {
      $this->component->addLog('FileNotFoundException in function getFile in Filesystem.php: ' . Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'), 'warning', 'filesystem');
      $this->component->addLog('$adapter: ' . $adapter, 'warning', 'filesystem');
      $this->component->addLog('$path: ' . $path, 'warning', 'filesystem');
      throw new FileNotFoundException(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'));
    }
    catch (\Exception $e)
    {
      if(\strpos(\strtolower($e->getMessage()), 'account'))
      {
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_FOUND', $adapter), 'error', 'filesystem');
        throw new AdapterNotFoundException(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_FOUND', $adapter));
      }
      elseif(\strpos(\strtolower($e->getMessage()), 'not found') !== false || \strpos(\strtolower($e->getMessage()), 'no such file') !== false)
      {
        $this->component->addLog('FileNotFoundException in function getFile in Filesystem.php: ' . Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'), 'warning', 'filesystem');
        $this->component->addLog('$adapter: ' . $adapter, 'warning', 'filesystem');
        $this->component->addLog('$path: ' . $path, 'warning', 'filesystem');
        throw new FileNotFoundException(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'));
      }
      else
      {
        $this->component->addLog($e->getMessage(), 'error', 'filesystem');
        throw new \Exception($e->getMessage());
      }
    }

    // Check if it is an allowed file
    if($file->type == 'file' && !$this->isAllowedFile($file->path))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'), 'error', 'filesystem');
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'));
    }

    if(isset($options['url']) && $options['url'] && $file->type == 'file')
    {
        $file->url = $this->getUrl($file->path);
    }

    if(isset($options['content']) && $options['content'] && $file->type == 'file')
    {
      list($file_info, $resource) = $this->getAdapter($adapter)->getResource($file->path);

        if($resource)
        {
            $file->content = base64_encode(stream_get_contents($resource));
        }
    }

    $file->path    = $adapter . ":" . $file->path;
    $file->adapter = $adapter;

    $event = new FetchMediaItemEvent('onFetchMediaItem', ['item' => $file]);
    $this->app->getDispatcher()->dispatch($event->getName(), $event);

    return $event->getArgument('item');
  }

  /**
   * Returns the folders and files for the given path. More information
   * can be found in AdapterInterface::getFiles().
   *
   * @param   string       $path     The folder
   * @param   array        $options  The options
   *
   * @return  \stdClass[]  List of objects with file information
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::getFiles()
   */
  public function getFiles(string $path = '/', array $options = []): array
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    // Check whether user searching
    if(isset($options['search']) && $options['search'] != null)
    {
      // Do search
      $files = $this->search($options['search'], $path, $options['recursive']);
    }
    else
    {
      // Grab files for the path
      $files = $this->getAdapter($adapter)->getFiles($path);
    }

    // Add adapter prefix to all the files to be returned
    foreach
    ($files as $key => $file)
    {
      // Check if the file is valid
      if($file->type == 'file' && !$this->isAllowedFile($file->path))
      {
        // Remove the file from the data
        unset($files[$key]);
        continue;
      }

      // Check if we need more information
      if(isset($options['url']) && $options['url'] && $file->type == 'file')
      {
        $file->url = $this->getUrl($file->path);
      }

      if(isset($options['content']) && $options['content'] && $file->type == 'file')
      {
        list($file_info, $resource) = $this->getAdapter($adapter)->getResource($file->path);

        if($resource)
        {
          $file->content = base64_encode(stream_get_contents($resource));
        }
      }

      $file->path    = $adapter . ":" . $file->path;
      $file->adapter = $adapter;
    }

    // Make proper indexes
    $files = array_values($files);

    $event = new FetchMediaItemsEvent('onFetchMediaItems', ['items' => $files]);
    $this->app->getDispatcher()->dispatch($event->getName(), $event);

    return $event->getArgument('items');
  }

  /**
   * Creates a folder with the given name in the given path. More information
   * can be found in AdapterInterface::createFolder().
   *
   * @param   string   $name      The name
   * @param   string   $path      The folder
   * @param   boolean  $override  Should the folder being overridden when it exists (default: true)
   * @param   boolean  $loop      True, if we are in a recursive loop (default: false)
   *
   * @return  string   The folder name
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::createFolder()
   */
  public function createFolder(string $name, string $path, bool $override = true, bool $loop = false): string
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    try
    {
      $file = $this->getFile($path . '/' . $name);
    }
    catch (FileNotFoundException $e)
    {
      // Folder not found; proceed to create it
    }

    if(isset($file) && !$override)
    {
      // No need to create folders
      throw new FileExistsException('Folder already exists: ' . $path . '/' . $name);
    }

    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->name      = $name;
    $object->path      = $path;

    PluginHelper::importPlugin('content');

    $result = $this->app->triggerEvent('onContentBeforeSave', ['com_media.folder', $object, true, $object]);

    if(\in_array(false, $result, true))
    {
      throw new \Exception($object->getError());
    }

    try
    {
      // Try to create folders recursively
      $object->name = $this->getAdapter($object->adapter)->createFolder($object->name, $object->path);
    }
    catch (\Exception $e)
    {
      if(!$loop)
      {
        // If it doesnt work like that, try again by creating them one by one
        $folders       = \explode('/', trim($object->path, '/'));
        $leading_slash = \strpos($object->path, '/') === 0;
        $currentPath   = $leading_slash ? '/' : '';

        // Append the current folder to the folders array
        $folders[] = $object->name;

        foreach($folders as $folder)
        {
          try
          {
            // Create each folder one by one
            $object->name = $this->createFolder($folder, $currentPath, $override, true);
          }
          catch (FileExistsException $fee)
          {
            // Folder already exists; no action needed
          }
          catch (\Exception $e)
          {
            if(\strpos(\strtolower($e->getMessage()), 'file exists') !== false)
            {
              // Handle the case where the adapter does not throw a proper FileExistsException
              // Folder already exists; no action needed
            }
            else
            {
              // Rethrow other exceptions for proper error handling
              throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }
          }

          // Adjust the currently existing path
          $currentPath .= ($currentPath === '/' ? '' : '/') . $folder;
        }
      }
      else
      {
        throw new \Exception($e->getMessage());
      }
    }

    $this->app->triggerEvent('onContentAfterSave', ['com_media.folder', $object, true, $object]);

    return $object->name;
  }

  /**
   * Creates a file with the given name in the given path with the data. More information
   * can be found in AdapterInterface::createFile().
   *
   * @param   string   $name      The name
   * @param   string   $path      The folder
   * @param   string   $data      The data
   * @param   boolean  $override  Should the file being overridden when it exists (default: true)
   *
   * @return  string   The filename
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::createFile()
   */
  public function createFile(string $name, string $path, $data, bool $override = true): string
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    try
    {
      $file = $this->getFile($path . '/' . $name);
    }
    catch (FileNotFoundException $e)
    {
      // Do nothing
    }

    // Check if the file exists
    if(isset($file) && !$override)
    {
      throw new FileExistsException();
    }

    // Check if it is an allowed file
    if(!$this->isAllowedFile($path . '/' . $name))
    {
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'));
    }

    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->name      = $name;
    $object->path      = $path;
    $object->data      = $data;
    try
    {
      // Joomla 5+
      $object->extension = strtolower(JFile::getExt($name));
    }
    catch(\Throwable $th)
    {
      // Joomla 4-
      $object->extension = strtolower(\Joomla\CMS\Filesystem\File::getExt($name));
    }    

    PluginHelper::importPlugin('content');

    // Also include the filesystem plugins, perhaps they support batch processing too
    PluginHelper::importPlugin('media-action');

    $result = $this->app->triggerEvent('onContentBeforeSave', ['com_media.file', $object, true, $object]);

    if(in_array(false, $result, true))
    {
      throw new \Exception($object->getError());
    }

    $object->name = $this->getAdapter($object->adapter)->createFile($object->name, $object->path, $object->data);

    $this->app->triggerEvent('onContentAfterSave', ['com_media.file', $object, true, $object]);

    return $object->name;
  }

  /**
   * Updates the file with the given name in the given path with the data. More information
   * can be found in AdapterInterface::updateFile().
   *
   * @param   string  $name     The name
   * @param   string  $path     The folder
   * @param   string  $data     The data
   *
   * @return  void
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::updateFile()
   */
  public function updateFile(string $name, string $path, $data)
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    // Check if it is an allowed file
    if(!$this->isAllowedFile($path . '/' . $name))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'), 'error', 'filesystem');
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'));
    }

    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->name      = $name;
    $object->path      = $path;
    $object->data      = $data;
    try
    {
      // Joomla 5+
      $object->extension = strtolower(JFile::getExt($name));
    }
    catch(\Throwable $th)
    {
      // Joomla 4-
      $object->extension = strtolower(\Joomla\CMS\Filesystem\File::getExt($name));
    }

    PluginHelper::importPlugin('content');

    // Also include the filesystem plugins, perhaps they support batch processing too
    PluginHelper::importPlugin('media-action');

    $result = $this->app->triggerEvent('onContentBeforeSave', ['com_media.file', $object, false, $object]);

    if(in_array(false, $result, true))
    {
      $this->component->addLog($object->getError(), 'error', 'filesystem');
      throw new \Exception($object->getError());
    }

    $this->getAdapter($object->adapter)->updateFile($object->name, $object->path, $object->data);

    $this->app->triggerEvent('onContentAfterSave', ['com_media.file', $object, false, $object]);
  }

  /**
   * Deletes the folder or file of the given path. More information
   * can be found in AdapterInterface::delete().
   *
   * @param   string  $path     The path to the file or folder
   *
   * @return  void
   *
   * @since   4.0.0
   * @throws  \Exception
   * @see     AdapterInterface::delete()
   */
  public function delete(string $path)
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    $file = $this->getFile($path);

    // Check if it is an allowed file
    if($file->type == 'file' && !$this->isAllowedFile($file->path))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'), 'error', 'filesystem');
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'));
    }

    $type              = $file->type === 'file' ? 'file' : 'folder';
    $object            = new CMSObject();
    $object->adapter   = $adapter;
    $object->path      = $path;

    PluginHelper::importPlugin('content');

    // Also include the filesystem plugins, perhaps they support batch processing too
    PluginHelper::importPlugin('media-action');

    $result = $this->app->triggerEvent('onContentBeforeDelete', ['com_media.' . $type, $object]);

    if(in_array(false, $result, true))
    {
      $this->component->addLog($object->getError(), 'error', 'filesystem');
      throw new \Exception($object->getError());
    }

    $this->getAdapter($object->adapter)->delete($object->path);

    $this->app->triggerEvent('onContentAfterDelete', ['com_media.' . $type, $object]);
  }

  /**
   * Copies file or folder from source path to destination path
   * If forced, existing files/folders would be overwritten
   *
   * @param   string  $sourcePath       Source path of the file or folder (relative)
   * @param   string  $destinationPath  Destination path(relative)
   * @param   bool    $force            Force to overwrite
   *
   * @return  string
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function copy(string $sourcePath, string $destinationPath, bool $force = false): string
  {
    $adapter         = $this->getFilesystem();
    $sourcePath      = $this->cleanPath($this->adjustPath($sourcePath), '/');
    $destinationPath = $this->cleanPath($this->adjustPath($destinationPath), '/');

    return $this->getAdapter($adapter)->copy($sourcePath, $destinationPath, $force);
  }

  /**
   * Moves file or folder from source path to destination path
   * If forced, existing files/folders would be overwritten
   *
   * @param   string  $sourcePath       Source path of the file or folder (relative)
   * @param   string  $destinationPath  Destination path(relative)
   * @param   bool    $force            Force to overwrite
   *
   * @return  string
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function move(string $sourcePath, string $destinationPath, bool $force = false): string
  {
    $adapter         = $this->getFilesystem();
    $sourcePath      = $this->cleanPath($this->adjustPath($sourcePath), '/');
    $destinationPath = $this->cleanPath($this->adjustPath($destinationPath), '/');

    return $this->getAdapter($adapter)->move($sourcePath, $destinationPath, $force);
  }

  /**
   * Returns a url for serve media files from adapter.
   * Url must provide a valid image type to be displayed on Joomla! site.
   *
   * @param   string  $path     The relative path for the file
   *
   * @return  string  Permalink to the relative file
   *
   * @since   4.0.0
   * @throws  FileNotFoundException
   */
  public function getUrl(string $path): string
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    // Check if it is an allowed file
    if(!$this->isAllowedFile($path))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'), 'error', 'filesystem');
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'));
    }

    try
    {
      $url = $this->getAdapter($adapter)->getUrl($path);
    }
    catch (FileNotFoundException $e)
    {
      $this->component->addLog('FileNotFoundException in function getUrl in Filesystem.php: ' . Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'), 'warning', 'filesystem');
      $this->component->addLog('$adapter: ' . $adapter, 'warning', 'filesystem');
      $this->component->addLog('$path: ' . $path, 'warning', 'filesystem');
      throw new FileNotFoundException(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'));
    }
    catch (\Exception $e)
    {
      if(\strpos(\strtolower($e->getMessage()), 'account'))
      {
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_FOUND', $adapter), 'error', 'filesystem');
        throw new AdapterNotFoundException(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_FOUND', $adapter));
      }
      elseif(\strpos(\strtolower($e->getMessage()), 'not found') !== false || \strpos(\strtolower($e->getMessage()), 'no such file') !== false)
      {
        $this->component->addLog('FileNotFoundException in function getUrl in Filesystem.php: ' . Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'), 'warning', 'filesystem');
        $this->component->addLog('$adapter: ' . $adapter, 'warning', 'filesystem');
        $this->component->addLog('$path: ' . $path, 'warning', 'filesystem');
        throw new FileNotFoundException(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILENOTFOUND'));
      }
      else
      {
        $this->component->addLog($e->getMessage(), 'error', 'filesystem');
        throw new \Exception($e->getMessage());
      }
    }

    $event = new FetchMediaItemUrlEvent('onFetchMediaFileUrl', ['adapter' => $adapter, 'path' => $path, 'url' => $url]);
    $this->app->getDispatcher()->dispatch($event->getName(), $event);

    return $event->getArgument('url');
  }

  /**
   * Search for a pattern in a given path
   *
   * @param   string  $path       The base path for the search
   * @param   string  $needle     The path to file
   * @param   bool    $recursive  Do a recursive search
   *
   * @return \stdClass[]
   *
   * @since   4.0.0
   * @throws \Exception
   */
  public function search(string $path, string $needle, bool $recursive = false): array
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    return $this->getAdapter($adapter)->search($path, $needle, $recursive);
  }

  /**
   * Returns a resource for the given path.
   *
   * @param   string  $path  The path
   *
   * @return  array   array(info, resource)
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function getResource(string $path): array
  {
    $adapter = $this->getFilesystem();
    $path    = $this->cleanPath($this->adjustPath($path), '/');

    try
    {
      $file = $this->getFile($path);
    }
    catch (FileNotFoundException $e)
    {
      # Get the 'no-image' instead
      $adapter = 'local-images';
      $type    = JoomHelper::getImagetypeFromPath($path);
      $path    = $this->cleanPath($this->adjustPath(JoomHelper::getImgZero($type, false, false)), '/');
      $file    = $this->getFile($path);
    }

    // Check if it is an allowed file
    if($file->type != 'file' || !$this->isAllowedFile($file->path))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'), 'error', 'filesystem');
      throw new InvalidPathException(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_FILE_TYPE'));
    }

    return array($file, $this->getAdapter($adapter)->getResource($path));
  }

  /**
   * Returns the name of the adapter.
   * It will be shown in the Media Manager
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function getAdapterName(): string
  {
    $adapter = $this->getFilesystem();

    return $this->getAdapter($adapter)->getAdapterName();
  }

  /**
   * Checks if the given path is an allowed file.
   *
   * @param   string  $path  The path to file
   *
   * @return boolean
   *
   * @since   4.0.0
   */
  public function isAllowedFile(string $path): bool
  {
    // Check if there is an extension available
    if(!strrpos($path, '.'))
    {
      return false;
    }

    // Initialize the allowed extensions
    if($this->allowedExtensions === null)
    {
      // Get options from the input or fallback to images only
      $fileTypes  = ['0', '1', '2', '3'];
      $types      = [];
      $extensions = [];

      array_map(
        function ($fileType) use (&$types)
        {
          switch ($fileType) {
            case '0':
              $types[] = 'images';
              break;
            case '1':
              $types[] = 'audios';
              break;
            case '2':
              $types[] = 'videos';
              break;
            case '3':
              $types[] = 'documents';
              break;
            default:
              break;
          }
        },
        $fileTypes
      );

      $images = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_imagetypes', '')),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      $audios = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_audiotypes', '')),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      $videos = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_videotypes', '')),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      $documents = array_map(
        'trim',
        array_filter(
          explode(',',$this->component->getConfig()->get('jg_documenttypes', '')),
          fn($value) => !is_null($value) && $value !== ''
        )
      );

      foreach($types as $type)
      {
        if(in_array($type, ['images', 'audios', 'videos', 'documents']))
        {
          $extensions = array_merge($extensions, ${$type});
        }
      }

      // Make them an array
      $this->allowedExtensions = $extensions;
    }

    // Extract the extension
    $extension = strtolower(substr($path, strrpos($path, '.') + 1));

    // Check if the extension exists in the allowed extensions
    return in_array($extension, $this->allowedExtensions);
  }

  /**
   * Get the filesystem property.
   *
   * @return string  The filesystem
   *
   * @since   4.0.0
   */
  public function getFilesystem(): string
  {
    return $this->filesystem;
  }

  /**
   * Adjusting the path if needed.
   *
   * @param  string  The path to be adjusted
   *
   * @return string  The adjusted path
   *
   * @since   4.0.0
   */
  public function adjustPath(string $path=''): string
  {
    if(empty($path))
    {
      return $path;
    }

    list($provider, $account) = \array_pad(\explode('-', $this->getFilesystem(), 2), 2, null);

    if($provider == 'local')
    {
      if(\strpos(\substr($path, 0, \strlen($account) + 1), $account) !== false)
      {
        // path needs to be adjusted
        // delete account from path
        $path = \substr($path, \strlen($account) + 1);
      }
    }

    return $path;
  }

  /**
   * Obtain list of supported providers
   *
   * @return array
   *
   * @since 4.0.0
   */
  public function getProviders()
  {
    $results = array();

    foreach($this->getProviderManager()->getProviders() as $provider)
    {
      $result               = new \stdClass();
      $result->name         = $provider->getID();
      $result->displayName  = $provider->getDisplayName();
      $result->adapterNames = array();

      foreach($provider->getAdapters() as $adapter)
      {
        $result->adapterNames[] = $adapter->getAdapterName();
      }

      $results[] = $result;
    }

    return $results;
  }
}
