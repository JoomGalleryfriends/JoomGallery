<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File;
use Joomla\Filesystem\Folder;
use Joomla\Filesystem\Path;

/**
 * Uploader helper class (FTP Upload)
 *
 * @since  4.0.0
 */
class FTPUploader extends BaseUploader implements UploaderInterface
{
  protected $src_name;
  protected $src_size;
  protected $src_tmp;
  protected $src_file;
  protected $source_file;
  protected $source_action = 'keep';

  /**
   * Detect if there is an image selected from the FTP import directory.
   *
   * @param   array  $data  Form data
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  public function isImgUploaded($data): bool
  {
    return !empty($data['ftp_file']);
  }

  /**
   * Retrieve an image already present on the server.
   *
   * @param   array  $data      Form data
   * @param   bool   $filename  Create the destination filename
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  public function retrieveImage(&$data, $filename = true): bool
  {
    $source = $this->resolveSourceFile((string) $data['ftp_file']);

    if(!$source)
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_FTP_IMPORT_ERROR_INVALID_SOURCE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_FTP_IMPORT_ERROR_INVALID_SOURCE'), 'error', 'jerror');
      $this->error = true;

      return false;
    }

    $this->source_file   = $source;
    $this->source_action = $data['ftp_action'] ?? 'keep';
    $this->src_name      = basename($source);
    $this->src_size      = filesize($source);
    $this->src_tmp       = $source;

    if(empty($data['title']))
    {
      $data['title'] = pathinfo($this->src_name, PATHINFO_FILENAME);
    }

    if(!parent::retrieveImage($data, $filename))
    {
      return false;
    }

    $tmp_dir = Path::clean(Factory::getApplication()->get('tmp_path') . '/joomgalleryftp');

    if(!Folder::exists($tmp_dir) && !Folder::create($tmp_dir))
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_FOLDER', $tmp_dir));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_FOLDER', $tmp_dir), 'error', 'jerror');
      $this->error = true;

      return false;
    }

    $this->src_file = Path::clean($tmp_dir . '/' . uniqid('ftp_', true) . '_' . $this->src_name);

    if(!File::copy($this->source_file, $this->src_file))
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPYING_FILE', $this->src_file));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPYING_FILE', $this->src_file), 'error', 'jerror');
      $this->rollback();
      $this->error = true;

      return false;
    }

    Path::setPermissions($this->src_file, '0644', null);
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_UPLOAD_COMPLETE', filesize($this->src_file) / 1000));

    return true;
  }

  /**
   * Override form data with image metadata according to configuration.
   *
   * @param   array  $data  Form data
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  public function overrideData(&$data): bool
  {
    if(empty($data['date']) || strpos($data['date'], '1900-01-01') !== false)
    {
      $data['date'] = $data['created_time'] ?? date('Y-m-d H:i:s');
    }

    return parent::overrideData($data);
  }

  /**
   * Create image files and clean up the FTP source if requested.
   *
   * @param   \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable  $data_row  Image object
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  public function createImage($data_row): bool
  {
    if(!parent::createImage($data_row))
    {
      return false;
    }

    $this->cleanupSource();

    return !$this->error;
  }

  /**
   * Return an upload error message.
   *
   * @param   int  $uploaderror  Upload error code
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function checkError($uploaderror): string
  {
    $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN')), 'error', 'jerror');

    return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN'));
  }

  /**
   * Delete temporary files created during the FTP import.
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  public function deleteTmp(): bool
  {
    if(isset($this->src_file) && !empty($this->src_file) && file_exists($this->src_file))
    {
      return File::delete($this->src_file);
    }

    return true;
  }

  /**
   * Resolve a submitted filename to an allowed source path.
   *
   * @param   string  $file  Source filename
   *
   * @return  string|false
   *
   * @since   4.0.0
   */
  protected function resolveSourceFile(string $file)
  {
    $file = basename($file);

    if($file === '' || $file === '.' || $file === '..')
    {
      return false;
    }

    foreach($this->getSourceDirectories() as $directory)
    {
      $candidate = realpath(Path::clean($directory . '/' . $file));

      if($candidate && is_file($candidate) && $this->isInsideDirectory($candidate, $directory))
      {
        return $candidate;
      }
    }

    return false;
  }

  /**
   * Get allowed FTP import directories.
   *
   * @return  array
   *
   * @since   4.0.0
   */
  protected function getSourceDirectories(): array
  {
    $config = JoomHelper::getService('config');
    $paths  = [
      $config->get('jg_pathftpupload', ''),
      'images/joomgallery/FTP',
      'administrator/components/com_joomgallery/temp/ftp_upload',
    ];

    $directories = [];

    foreach($paths as $path)
    {
      $directory = $this->normalizeSourcePath((string) $path);

      if($directory !== '' && !\in_array($directory, $directories, true))
      {
        $directories[] = $directory;
      }
    }

    return $directories;
  }

  /**
   * Normalize a configured FTP import path.
   *
   * @param   string  $path  Configured path
   *
   * @return  string
   *
   * @since   4.0.0
   */
  protected function normalizeSourcePath(string $path): string
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
   * Check if a file belongs to a directory after realpath normalization.
   *
   * @param   string  $file       Absolute file path
   * @param   string  $directory  Absolute directory path
   *
   * @return  bool
   *
   * @since   4.0.0
   */
  protected function isInsideDirectory(string $file, string $directory): bool
  {
    $real_dir = realpath($directory);

    return $real_dir && strpos($file, rtrim($real_dir, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR) === 0;
  }

  /**
   * Delete or move the imported source after image creation.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function cleanupSource(): void
  {
    if(empty($this->source_file) || !is_file($this->source_file))
    {
      return;
    }

    if($this->source_action === 'delete')
    {
      File::delete($this->source_file);

      return;
    }

    if($this->source_action === 'move')
    {
      $processed_dir = Path::clean(\dirname($this->source_file) . '/processed');

      if(!Folder::exists($processed_dir))
      {
        Folder::create($processed_dir);
      }

      $destination = Path::clean($processed_dir . '/' . basename($this->source_file));

      if(is_file($destination))
      {
        $destination = Path::clean(
            $processed_dir . '/' . pathinfo($this->source_file, PATHINFO_FILENAME) . '_' . date('YmdHis') . '.' . pathinfo($this->source_file, PATHINFO_EXTENSION)
        );
      }

      File::move($this->source_file, $destination);
    }
  }
}
