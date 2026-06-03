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

use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;
use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

use Joomla\Filesystem\File as JFile;
use Joomla\Filesystem\Path as JPath;

/**
 * Uploader helper class (Standard HTML Upload)
 *
 * @since  4.0.0
 */
class APIUploader extends BaseUploader implements UploaderInterface
{
  /**
   * Method to retrieve an uploaded image. Step 1.
   * (check upload, check user upload limit, create filename, onJoomBeforeUpload)
   *
   * @param   array    $data        Form data (as reference)
   * @param   bool   $createFilename  True, if the filename has to be created (default: True)
   *
   * @return  bool     True on success, false otherwise
   *
   * @since  4.0.0
   */
  public function retrieveImage(&$data, $createFilename = true): bool
  {
      $isUploaded = false;

      $user = Factory::getUser();
    $app  = Factory::getApplication();

    // toDo: try {

    $isSaved = $this->saveImgContent2Temp($data);

    if ($isSaved)
    {
        unset($data['content']);

//    // Retrieve request image file data
//    if(\array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image']))
//    {
//      $data['images'] = [];
//      array_push($data['images'], $app->input->files->get('jform')['image']);
//    }
//
//    if(\count($data['images']) > 1)
//    {
//      if($this->filecounter >= 1)
//      {
//        $this->component->addDebug('<hr />');
//      }
//      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_IMAGE_NBR_PROCESSING', $this->filecounter + 1));
//    }
//
//    $image = $data['images'][$this->filecounter - 1];
//
//    // Check for upload error codes
//    if($image['error'] > 0)
//    {
//      if($image['error'] == 4)
//      {
//        $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'));
//        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'), 'error', 'jerror');
//
//        return false;
//      }
//      $this->component->addDebug($this->checkError($image['error']));
//      $this->error = true;
//
//      return false;
//    }
//
//    // Get number of uploaded images of the current user
//    $counter = $this->getImageNumber($user->id);
//
//    // Check if user already exceeds its upload limit
//    if($this->app->isClient('site') && $counter > ($this->component->getConfig()->get('jg_maxuserimage') - 1) && $user->id)
//    {
//      $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');
//      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAY_ADD_MAX_OF', $this->component->getConfig()->get('jg_maxuserimage'), $timespan > 0 ? Text::plural('COM_JOOMGALLERY_UPLOAD_NEW_IMAGE_MAXCOUNT_TIMESPAN', $timespan) : ''));
//
//      return false;
//    }

//    $this->src_tmp  = $image['tmp_name'];
        $src_tmp = $data['tmp_name'];
//    $this->src_name = $image['name'];
//    $this->src_size = $image['size'];

        // Perform the parent method
        // - check tag and size
        // - create filename
        // - trigger onJoomBeforeUpload
        if (!parent::retrieveImage($data, $createFilename))
        {
            return false;
        }

        // Upload file to temp file
        $src_file = JPath::clean(\dirname($this->src_tmp) . \DIRECTORY_SEPARATOR . $this->src_name);
        $return   = JFile::upload($src_tmp, $src_file);

//    if(!$return)
//    {
//      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file));
//      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file), 'error', 'jerror');
//      $this->rollback();
//      $this->error = true;
//
//      return false;
//    }
//
//    // Set permissions of uploaded file
//    $return = JPath::setPermissions($this->src_file, '0644', null);
//    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_UPLOAD_COMPLETE', filesize($this->src_file) / 1000));

        $isUploaded = true;
    }

      return $isUploaded;
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
    // Get upload date
    if(empty($data['date']) || strpos($data['date'], '1900-01-01') !== false)
    {
      $data['date'] = $data['created_time'];
    }

    // Override form data with image metadata
    return parent::overrideData($data);
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
   * Save image bytes content here and tell about it
   * Attention other similar function expect the file is already on the server
   *
   * @param   array    $data      Form data
   *
   * @return  bool     True if file is detected, false otherwise
   *
   * @since   4.0.0
   */
  public function isImgUploaded($data): bool
  {
      $isUploaded = false;

      if ( ! empty ($data['content']) && strlen ($data['content']) > 0) {

          $isUploaded = true;
      }

      // test mime type ... ? png, jpg, svg ?


      return $isUploaded;
  }

  public function saveImgContent2Temp (&$data)
  {
      // JFile::upload($src, $dest);
      $isSaved = false;

      try
      {
          // Define temporary image file to be created
          $tmp_folder = $this->app->get('tmp_path') . 'JG_API_files';
          $tmp_folder = Factory::getApplication()->get('tmp_path') . '/JG_API_files';

          //$tmp_dst_file = $tmp_folder . '/tmp_dst_img_' . $this->rndNumber . '.' . strtolower($this->dst_type);
          $randomName = $this->genFilename('api_', 'tmp');
          $tmp_path_file_name = $tmp_folder . '/' . $randomName;

          $base64Data = base64_decode(urldecode($data['content']));
          file_put_contents($tmp_path_file_name, $base64Data);
//          $test = $data['content'];
//          file_put_contents($tmp_path_file_name, $data['content']);

          $data['src_tmp'] = $tmp_path_file_name;

          $isSaved = true;
      }
      catch (\Exception $e)
      {
          // Debug info
          //$this->component->addDebug(Text::sprintf('Failed to write file %s', $tmp_dst_file));
          //$this->component->addLog(Text::sprintf('Failed to write file %s', $filename), 'error', $logfile);

          throw new Exception("saveImgContent2Temp failed: ", 0, $e);
      }

      return $isSaved;
  }

    /**
     * Generates image filenames
     * e.g. <Name/Title>_<Filecounter (opt.)>_<Date>_<Random Number>.<Extension>
     *
     * ToDo: @ manual: this is a copy of Filmanager code. Better: add static there
     *
     *
     * @param   string    $filename     Original upload name without extension
     * @param   string    $tag          File extension e.g. 'jpg'
     * @param   int       $filecounter  Optionally a filecounter
     *
     * @return  string    The generated filename
     *
     * @since   4.0.0
     */
    public static function genFilename($filename, $tag, $filecounter = null): string
    {
        $filedate = date('Ymd');

        mt_srand();
        $randomnumber = mt_rand(1000000000, 2099999999);

        $maxlen = 255 - 2 - \strlen($filedate) - \strlen($randomnumber) - (\strlen($tag) + 1);

        if(!\is_null($filecounter))
        {
            $maxlen = $maxlen - (\strlen($filecounter) + 1);
        }

        if(\strlen($filename) > $maxlen)
        {
            $filename = substr($filename, 0, $maxlen);
        }

        // New filename
        if(\is_null($filecounter))
        {
            $newfilename = $filename . '_' . $filedate . '_' . $randomnumber . '.' . $tag;
        }
        else
        {
            $newfilename = $filename . '_' . $filecounter . '_' . $filedate . '_' . $randomnumber . '.' . $tag;
        }

        return $newfilename;
    }

}
