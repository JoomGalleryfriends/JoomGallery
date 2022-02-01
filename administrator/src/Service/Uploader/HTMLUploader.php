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

use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filesystem\File as JFile;
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
   * @param   array    $data    form data
   *
	 * @return  string   Message
	 *
	 * @since  4.0.0
	 */
	public function upload($data): string
  {
    $app  = Factory::getApplication();
    $user = Factory::getUser();

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
      if($is_site && $counter > ($this->jg->config->get('jg_maxuserimage') - 1) && $user->get('id'))
      {
        $timespan = $this->jg->config->get('jg_maxuserimage_timespan');
        $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAY_ADD_MAX_OF', $this->jg->config->get('jg_maxuserimage'), $timespan > 0 ? Text::plural('COM_JOOMGALLERY_UPLOAD_NEW_IMAGE_MAXCOUNT_TIMESPAN', $timespan) : ''));

        break;
      }

      // Trigger onJoomBeforeUpload
      $plugins  = $app->triggerEvent('onJoomBeforeUpload');
      if(in_array(false, $plugins, true))
      {
        continue;
      }

      $img_file = $image['tmp_name'];
      $img_name = $image['name'];
      $img_size = $image['size'];

      $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_FILENAME', $img_name));

      // Image size must not exceed the setting in backend if we are in frontend
      if($is_site && $img_size > $this->jg->config->get('jg_maxfilesize'))
      {
        $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_MAX_ALLOWED_FILESIZE', $this->jg->config->get('jg_maxfilesize')));
        $this->error  = true;

        continue;
      }

      // Get extension
      $tag = strtolower(JFile::getExt($img_name));

      if( !\in_array(strtoupper($tag), $this->jg->supported_types) || strlen($img_file) == 0 || $img_file == 'none' )
      {
        $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_INVALID_IMAGE_TYPE'));
        $this->error  = true;

        continue;
      }

      $filecounter = null;
      if($this->jg->config->get('jg_filenamenumber'))
      {
        $filecounter = $this->getSerial();
      }

      // Create filesystem service
      $this->jg->createFilesystem('localhost');

      // Create new filename
      if($this->jg->config->get('jg_useorigfilename'))
      {
        $oldfilename = $img_name;
        $newfilename = $this->jg->filesystem->cleanFilename($img_name);
      }
      else
      {
        $oldfilename = $this->imgtitle;
        $newfilename = $this->jg->filesystem->cleanFilename($this->imgtitle);
      }

      // Check the new filename
      if($this->jg->filesystem->checkValidFilename($oldfilename, $newfilename) == false)
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

      $newfilename = $this->genFilename($newfilename, $tag, $filecounter);
    }

    $upfilesize = filesize($img_size) / 1000;
    $orig_path  = $this->jg->filesystem->root . JoomHelper::getImg($newfilename, 'original', false);

    // Move the image from temp folder to originals folder
    $return = $this->jg->filesystem->upload($img_file, $orig_path);
    if(!$return)
    {
      $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_PROBLEM_MOVING', $orig_path.' '.Text::_('COM_JOOMGALLERY_COMMON_CHECK_PERMISSIONS'));
      $this->error = true;

      continue;
    }

    $this->addDebug(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_COMPLETE', $upfilesize));

    // Set permissions of uploaded file
    $return = $this->jg->filesystem->chmod($orig_path, '0644');


    // Check for overriding with meta data
    $overridevalues = $this->getOverrideValues($orig_path, $origfilename);


    return $this->error;
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
