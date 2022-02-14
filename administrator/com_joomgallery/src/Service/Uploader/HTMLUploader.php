<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Path as JPath;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* Uploader helper class (Single Upload)
*
* @since  4.0.0
*/
class HTMLUploader extends BaseUploader implements UploaderInterface
{
	/**
	 * Method to upload a new image.
	 *
   * @param   array    $data    form data (as reference)
   * @param   array    $output  output data in the form array('debug'=>'','msg'=>'')
   *
	 * @return  bool     True on success, false otherwise
	 *
	 * @since  4.0.0
	 */
	public function upload(&$data, $output=array('debug'=>'','msg'=>'')): string
  {
    $app  = Factory::getApplication();
    $user = Factory::getUser();

    // Initialise outputs
    if(\key_exists('debug', $output))
    {
      $this->debugoutput   = $output['debug'];
    }
    if(\key_exists('msg', $output))
    {
      $this->warningoutput   = $output['msg'];
    }

    $this->addDebug('<strong>___'.Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_DEBUG_HEADING').'___</strong>');

    foreach ($data['images'] as $i => $image)
    {
      $this->addDebug('<hr />', false);
      $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_POSITION', $i + 1));

      // Check for upload error codes
      if($image['error'] > 0)
      {
        if($image['error'] == 4)
        {
          $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_NOT_UPLOADED'));

          continue;
        }
        $this->addDebug($this->checkError($image['error']));
        $this->error = true;

        continue;
      }

      $counter = $this->getImageNumber($user->get('id'));
      $is_site = $app->isClient('site');

      // Check if user already exceeds its upload limit
      if($is_site && $counter > ($this->jg->getConfig()->get('jg_maxuserimage') - 1) && $user->get('id'))
      {
        $timespan = $this->jg->getConfig()->get('jg_maxuserimage_timespan');
        $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAY_ADD_MAX_OF', $this->jg->getConfig()->get('jg_maxuserimage'), $timespan > 0 ? Text::plural('COM_JOOMGALLERY_UPLOAD_NEW_IMAGE_MAXCOUNT_TIMESPAN', $timespan) : ''));

        break;
      }

      // Trigger onJoomBeforeUpload
      $plugins  = $app->triggerEvent('onJoomBeforeUpload');
      if(in_array(false, $plugins, true))
      {
        continue;
      }

      $this->img_paths['local']['temp'] = $image['tmp_name'];
      $img_name                         = $image['name'];
      $img_size                         = $image['size'];

      $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_FILENAME', $img_name));

      // Image size must not exceed the setting in backend if we are in frontend
      if($is_site && $img_size > $this->jg->getConfig()->get('jg_maxfilesize'))
      {
        $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAX_ALLOWED_FILESIZE', $this->jg->getConfig()->get('jg_maxfilesize')));
        $this->error  = true;

        continue;
      }

      // Get extension
      $tag = strtolower(JFile::getExt($img_name));

      if( !\in_array(strtoupper($tag), $this->jg->supported_types) || strlen($this->img_paths['local']['temp']) == 0 || $this->img_paths['local']['temp'] == 'none' )
      {
        $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_TYPE'));
        $this->error  = true;

        continue;
      }

      $filecounter = null;
      if($this->jg->getConfig()->get('jg_filenamenumber'))
      {
        $filecounter = $this->getSerial();
      }

      // Create filesystem service
      $this->jg->createFilesystem('localhost');

      // Create new filename
      if($this->jg->getConfig()->get('jg_useorigfilename'))
      {
        $oldfilename = $img_name;
        $newfilename = $this->jg->getFilesystem()->cleanFilename($img_name);
      }
      else
      {
        $oldfilename = $this->imgtitle;
        $newfilename = $this->jg->getFilesystem()->cleanFilename($this->imgtitle);
      }

      // Check the new filename
      if($this->jg->getFilesystem()->checkFilename($oldfilename, $newfilename) == false)
      {
        if($is_site)
        {
          $this->addDebug(Text::_('COM_JOOMGALLERY_COMMON_ERROR_INVALID_FILENAME'));
        }
        else
        {
          $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_INVALID_FILENAME', $newfilename, $oldfilename));
        }
        $this->error = true;

        continue;
      }

      // Generate image filename
      $data['filename'] = $this->genFilename($newfilename, $tag, $filecounter);

      // Get upload date
      if(!empty($data['imgdate']))
      {
        $data['imgdate'] = $data['created_time'];
      }
      
      // Generate local file path of original image
      $this->img_paths['local']['original'] = JPath::clean($this->jg->getFilesystem()->get('local_root') . $this->getImgPath('original', $data['catid'], $data['filename']));

      // Move the image from temp folder to originals folder of local filesystem
      $return = JFile::upload($this->img_paths['local']['temp'], $this->img_paths['local']['original']);
      if(!$return)
      {
        $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_MOVING', $this->img_paths['local']['original'] .' '. Text::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS')));
        $this->rollback($data['filename']);
        $this->error = true;

        continue;
      }

      // Set permissions of uploaded file
      $return = JPath::setPermissions($this->img_paths['local']['original'], '0644', null);

      $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_COMPLETE', filesize($this->img_paths['local']['original']) / 1000));      

      // Override form data with image metadata
      $this->overrideData($data, $this->img_paths['local']['original']);

      // Create image types
      if(!$this->createImages($this->img_paths['local']['original'], $data['filename'], true, false))
      {
        $this->rollback($data['filename']);
        $this->error = true;

        continue;
      }

      // Move image types to storage filesystem
      foreach ($this->img_paths['local'] as $local_path)
      {
        if(!$this->jg->getFilesystem()->uploadFile($local_path))
        {
          $this->rollback($data['filename']);
          $this->error = true;
  
          continue;
        }
      }

      // Message about new image
      // if($is_site)
      // {
      //   // Create message service
      //   $this->jg->createMessenger();

      //   $message    = array(
      //                         'from'      => $user->get('id'),
      //                         'subject'   => Text::_('COM_JOOMGALLERY_UPLOAD_MESSAGE_NEW_IMAGE_UPLOADED'),
      //                         'body'      => Text::sprintf('COM_JOOMGALLERY_MESSAGE_NEW_IMAGE_SUBMITTED_BODY', $this->jg->getConfig()->get('jg_realname') ? $user->get('name') : $user->get('username'), $row->imgtitle),
      //                         'mode'      => 'upload'
      //                       );
      //   $this->jg->getMessenger()->send($message);
      // }

      $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_IMAGE_SUCCESSFULLY_ADDED'));
      $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_NEW_FILENAME', $newfilename));

      $app->triggerEvent('onJoomAfterUpload', array($data));
      $counter++;
    }

    $this->addDebug('<hr />');

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
   * @since   1.0.0
   */
  protected function checkError($uploaderror)
  {
    // Common PHP errors
    $uploadErrors = array(
      1 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_PHP_MAXFILESIZE'),
      2 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_HTML_MAXFILESIZE'),
      3 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_PARTLY_UPLOADED'),
      4 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_NOT_UPLOADED')
    );

    if(in_array($uploaderror, $uploadErrors))
    {
      return Text::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_CODE', $uploadErrors[$uploaderror]);
    }
    else
    {
      return Text::sprintf('COM_JOOMGALLERY_UPLOAD_ERROR_CODE', Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_UNKNOWN'));
    }
  }
}
