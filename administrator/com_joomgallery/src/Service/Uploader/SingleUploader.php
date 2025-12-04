<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\File as JFile;
use Joomla\Filesystem\Path as JPath;

/**
 * Uploader helper class (Single Image Upload)
 *
 * @since  4.0.0
 */
class SingleUploader extends BaseUploader implements UploaderInterface
{
  /**
   * The imagetype to create
   *
   * @var string
   */
  public $type = 'original';

  /**
   * Does images have to be processed?
   *
   * @var bool
   */
  public $processImage = false;

    /**
     * Method to retrieve an uploaded image. Step 1.
     * (check upload, check user upload limit, create filename, onJoomBeforeUpload)
     *
     * @param   array    $data        Form data (as reference)
     * @param   bool     $filename    True, if the filename has to be created (default: True)
     *
     * @return  bool     True on success, false otherwise
     *
     * @since  4.0.0
     */
    public function retrieveImage(&$data, $filename = true): bool
    {
    $user = Factory::getUser();

    if(\count($data['images']) > 1)
    {
      if($this->filecounter >= 1)
      {
        $this->component->addDebug('<hr />');
      }
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_IMAGE_NBR_PROCESSING', $this->filecounter + 1));
    }

    $image = $data['images'][$this->filecounter - 1];

    // Check for upload error codes
    if($image['error'] > 0)
    {
      if($image['error'] == 4)
      {
        $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'), 'error', 'jerror');

        return false;
      }
      $this->component->addDebug($this->checkError($image['error']));
      $this->error = true;

      return false;
    }

    $this->src_tmp  = $image['tmp_name'];
    $this->src_name = $image['name'];
    $this->src_size = $image['size'];

    // Create filesystem service
    $this->component->createFilesystem();

    // Get extension
    $tag = $this->component->getFilesystem()->getExt($this->src_name);

    // Get supported formats of image processor
    $this->component->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));
    $supported_ext    = $this->component->getIMGtools()->get('supported_types');
    $allowed_imgtools = \in_array(strtoupper($tag), $supported_ext);
    $this->component->delIMGtools();

    // Get supported formats of filesystem
    $allowed_filesystem = $this->component->getFilesystem()->isAllowedFile($this->src_name);

    // Check for supported image format
    if(!$allowed_imgtools || !$allowed_filesystem || \strlen($this->src_tmp) == 0 || $this->src_tmp == 'none')
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_IMAGEFILE_TYPE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_IMAGEFILE_TYPE'), 'error', 'jerror');
      $this->error = true;

      return false;
    }

    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FILENAME', $this->src_name));

    // Trigger onJoomBeforeUpload
    $plugins = $this->app->triggerEvent('onJoomBeforeUpload', [$data['filename']]);

    if(\in_array(false, $plugins, true))
    {
      return false;
    }

    // Upload file to temp file
    $this->src_file = JPath::clean(\dirname($this->src_tmp) . \DIRECTORY_SEPARATOR . $this->src_name);
    $return         = JFile::upload($this->src_tmp, $this->src_file);

    if(!$return)
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file), 'error', 'jerror');
      $this->rollback();
      $this->error = true;

      return false;
    }

    // Set permissions of uploaded file
    $return = JPath::setPermissions($this->src_file, '0644', null);
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_UPLOAD_COMPLETE', filesize($this->src_file) / 1000));

    return true;
    }

  /**
   * Override form data with image metadata
   * according to configuration. Step 2.
   *
   * @param   array   $data       The form data (as a reference)
   *
   * @return  bool    True on success, false otherwise
   *
   * @since   1.5.7
   */
  public function overrideData(&$data): bool
  {
    return true;
  }

    /**
     * Method to create uploaded image files. Step 3.
     * (create imagetype, upload imagetypes to storage, onJoomAfterUpload)
     *
     * @param   ImageTable   $data_row     Image object
     *
     * @return  bool         True on success, false otherwise
     *
     * @since  4.0.0
     */
    public function createImage($data_row): bool
    {
    // Check if filename was set
    if(!isset($data_row->filename) || empty($data_row->filename))
    {
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_UPLOAD_CHECK_FILENAME'), 'error', 'jerror');
      throw new \Exception(Text::_('COM_JOOMGALLERY_SERVICE_UPLOAD_CHECK_FILENAME'));
    }

    // Create file manager service
    $this->component->createFileManager($data_row->catid, [$this->type]);

    // Process image to create imagetype
    if(!$this->component->getFileManager()->createImages($this->src_file, $data_row->filename, $data_row->catid, $this->processImage))
    {
      $this->rollback($data_row);
      $this->error = true;

      return false;
    }

    $this->component->addDebug(' ');
    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_SUCCESS_CREATE_IMAGETYPE_END'));
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FILENAME', $data_row->filename));

    $this->app->triggerEvent('onJoomAfterUpload', [$data_row]);

    // Reset user states
    $this->resetUserStates();

    return !$this->error;
    }

  /**
   * Analyses an error code and returns its text
   *
   * @param   int     $uploaderror  The errorcode
   *
   * @return  string  The error message
   *
   * @since   4.0.0
   */
  public function checkError($uploaderror): string
  {
    // Common PHP errors
    $uploadErrors = [
      1 => Text::_('COM_JOOMGALLERY_ERROR_PHP_MAXFILESIZE'),
      2 => Text::_('COM_JOOMGALLERY_ERROR_HTML_MAXFILESIZE'),
      3 => Text::_('COM_JOOMGALLERY_ERROR_FILE_PARTLY_UPLOADED'),
      4 => Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'),
    ];

    if(\in_array($uploaderror, $uploadErrors))
    {
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', $uploadErrors[$uploaderror]), 'error', 'jerror');

      return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', $uploadErrors[$uploaderror]);
    }


      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN')), 'error', 'jerror');

      return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN'));
  }

  /**
   * Detect if there is an image uploaded
   *
   * @param   array    $data      Form data
   *
   * @return  bool     True if file is detected, false otherwise
   *
   * @since   4.0.0
   */
  public function isImgUploaded($data): bool
  {
    $app = Factory::getApplication();

    if(\array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image'])
    && $app->input->files->get('jform')['image']['error'] != 4 &&  $app->input->files->get('jform')['image']['size'] > 0)
        {
      return true;
    }


      return false;
  }
}
