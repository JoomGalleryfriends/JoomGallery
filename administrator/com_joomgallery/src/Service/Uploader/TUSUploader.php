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
* Uploader helper class (TUS Upload)
*
* @since  4.0.0
*/
class TUSUploader extends BaseUploader implements UploaderInterface
{
  protected $src_name;
  protected $src_size;
  protected $src_tmp;
  protected $src_file;

	/**
   * Constructor
   * 
   * @param   bool   $multiple     True, if it is a multiple upload  (default: false)
   * @param   bool   $async        True, if it is a asynchronous upload  (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($multiple=false, $async=false)
  {
		parent::__construct($multiple, $async);

		$this->component->createTusServer();
	}

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

    // Load tus upload
    $uuid = $data['uuid'];
    $this->component->getTusServer()->loadUpload($uuid);

    // Check for upload errors
    $isfinal = $this->component->getTusServer()->getMetaDataValue('isfinal');
    $offset  = $this->component->getTusServer()->getMetaDataValue('ofset');

    if(!$isfinal || $offset)
    {
      $data['error'] = 2;
    }

    // Check for upload error codes
    if(\array_key_exists('error', $data) && $data['error'] > 0)
    {
      $this->component->addDebug($this->checkError($data['error']));
      $this->component->addLog($this->checkError($data['error']), 'error', 'jerror');
      $this->error = true;

      return false;
    }

    // Get number of uploaded images of the current user
    $counter = $this->getImageNumber($user->get('id'));
    $is_site = $this->app->isClient('site');

    // Check if user already exceeds its upload limit
    if($is_site && $counter > ($this->component->getConfig()->get('jg_maxuserimage') - 1) && $user->get('id'))
    {
      $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAY_ADD_MAX_OF', $this->component->getConfig()->get('jg_maxuserimage'), $timespan > 0 ? Text::plural('COM_JOOMGALLERY_UPLOAD_NEW_IMAGE_MAXCOUNT_TIMESPAN', $timespan) : ''));

      return false;
    }

    $this->src_name = $this->component->getTusServer()->getMetaDataValue('name');
    $this->src_size = $this->component->getTusServer()->getMetaDataValue('size');
    $this->src_tmp  = JPath::clean($this->component->getTusServer()->getDirectory()).$uuid;

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
    if(!JFile::move($this->src_tmp, $this->src_file))
    {
      $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file), 'error', 'jerror');
      $this->rollback();
      $this->error = true;

      return false;
    }

    // Set permissions of uploaded file
    JPath::setPermissions($this->src_file, '0644', null);
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_UPLOAD_COMPLETE', filesize($this->src_file) / 1000));

    return true;
	}

  /**
   * Override form data with image metadata
   * according to configuration. Step 2.
   *
   * @param   array   $data     The form data (as a reference)
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
      $data['date'] = date('Y-m-d');
    }

    // Get tus metadata
    if(isset($data['uuid']) && !empty($data['uuid']))
    {
      // Load tus upload
      $uuid = $data['uuid'];
      $this->component->getTusServer()->loadUpload($uuid);

      // Override title with tus metadata
      if($title = $this->component->getTusServer()->getMetaDataValue('jtitle'))
      {
        $data['title'] = $title;
        $data['alias'] = $data['title'];
      }

      // Override description with tus metadata
      if($desc = $this->component->getTusServer()->getMetaDataValue('jdescription'))
      {
        $data['description'] = $desc;
      }

      // Override author with tus metadata
      if($author = $this->component->getTusServer()->getMetaDataValue('jauthor'))
      {
        $data['author'] = $author;
      }
    }

    // Delete info file
    JFile::delete($this->src_tmp.'.info');

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
      1 => Text::_('COM_JOOMGALLERY_ERROR_TUS_MAXFILESIZE'),
      2 => Text::_('COM_JOOMGALLERY_ERROR_FILE_PARTLY_UPLOADED'),
      3 => Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED')
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
    if(isset($data['uuid']) && !empty($data['uuid']))
    {
      // Load tus upload
      $uuid = $data['uuid'];
      $this->component->getTusServer()->loadUpload($uuid);

      return $this->component->getTusServer()->getMetaDataValue('isfinal');
    }
    else
    {
      return false;
    }
  }
}
