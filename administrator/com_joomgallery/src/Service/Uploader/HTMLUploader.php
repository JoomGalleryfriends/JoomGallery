<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Path as JPath;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;

/**
* Uploader helper class (Standard HTML Upload)
*
* @since  4.0.0
*/
class HTMLUploader extends BaseUploader implements UploaderInterface
{
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
	public function retrieveImage(&$data, $filename=True): bool
  {
    $user = Factory::getUser();
    $app  = Factory::getApplication();

    // Retrieve request image file data
    if(\array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image']))
    {
      $data['images'] = array();
      \array_push($data['images'], $app->input->files->get('jform')['image']);
    }

    if(\count($data['images']) > 1)
    {
      if($this->filecounter >= 1)
      {
        $this->component->addDebug('<hr />');
      }
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_IMAGE_NBR_PROCESSING', $this->filecounter + 1));
    }

    $image = $data['images'][$this->filecounter-1];

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

    // Get number of uploaded images of the current user
    $counter = $this->getImageNumber($user->id);

    // Check if user already exceeds its upload limit
    if($this->app->isClient('site') && $counter > ($this->component->getConfig()->get('jg_maxuserimage') - 1) && $user->id)
    {
      $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAY_ADD_MAX_OF', $this->component->getConfig()->get('jg_maxuserimage'), $timespan > 0 ? Text::plural('COM_JOOMGALLERY_UPLOAD_NEW_IMAGE_MAXCOUNT_TIMESPAN', $timespan) : ''));

      return false;
    }

    $this->src_tmp  = $image['tmp_name'];
    $this->src_name = $image['name'];
    $this->src_size = $image['size'];

    // Perform the parent method
    // - check tag and size
    // - create filename
    // - trigger onJoomBeforeUpload
    if(!parent::retrieveImage($data, $filename))
    {
      return false;
    }

    // Upload file to temp file
    $this->src_file = JPath::clean(\dirname($this->src_tmp).\DIRECTORY_SEPARATOR.$this->src_name);
    $return = JFile::upload($this->src_tmp, $this->src_file);
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
    // Get upload date
    if(empty($data['date']) || \strpos($data['date'], '1900-01-01') !== false)
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
    $uploadErrors = array(
      1 => Text::_('COM_JOOMGALLERY_ERROR_PHP_MAXFILESIZE'),
      2 => Text::_('COM_JOOMGALLERY_ERROR_HTML_MAXFILESIZE'),
      3 => Text::_('COM_JOOMGALLERY_ERROR_FILE_PARTLY_UPLOADED'),
      4 => Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED')
    );

    if(in_array($uploaderror, $uploadErrors))
    {
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', $uploadErrors[$uploaderror]), 'error', 'jerror');
      return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', $uploadErrors[$uploaderror]);
    }
    else
    {
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN')), 'error', 'jerror');
      return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN'));
    }
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
    $app   = Factory::getApplication();
    $files = $app->input->files->get('jform');

    if($files && \array_key_exists('image', $files) && !empty($files['image']) && $files['image']['error'] != 4 &&  $files['image']['size'] > 0)
		{
      return true;
    }
    else
    {
      return false;
    }
  }
}
