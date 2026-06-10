<?php
/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;

// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\Uploader as BaseUploader;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
     * @param   array  $data            Form data (as reference)
     * @param   bool   $createFilename  True, if the filename has to be created (default: True)
     *
     * @return  bool     True on success, false otherwise
     *
     * @since  4.0.0
     */
    public function retrieveImage(&$data, $createFilename = true): bool
    {
        $isSaved = false;

        // ToDo: @Manuel Assign user from API cal UserId ?
//    $user = Factory::getUser();
//    $app  = Factory::getApplication();

        $this->src_size = strlen($data['content']);

//        $data ['uuid'] = $this->getUserUuid();

        //old: $this->src_tmp  = $image['tmp_name'];
        // J! tempfolder with folder from uuid
        // where the file does come from
        //$this->src_tmp = $data['src_tmp'];
        $this->src_tmp = JPath::clean(Factory::getApplication()->get('tmp_path')); // @Manuel: found in  . '/' . $data['uuid'];
        //old: $this->src_name = $image['name'];
        $this->src_name = $data['filename'] . '.' . $data['file_extension'];
        //old: $this->src_size = $image['size'];

        //$this->src_size = filesize($this->src_tmp);

        // Perform the parent method
        // - check tag and size
        // - create filename
        // - trigger onJoomBeforeUpload
        $isFileNameValid = parent::retrieveImage($data, $createFilename);

        if ($isFileNameValid)
        {
            // Save file to intermediate temp file
            //$this->src_file = JPath::clean(\dirname($this->src_tmp) . '/' . $this->src_name);
            $this->src_file = JPath::clean($this->src_tmp . '/' . $this->src_name);

            $isSaved = $this->saveImgContent2Temp($data['content'], $this->src_file);
            unset($data['content']);
        }

        // not saved or filename invalid
        if (!$isSaved)
        {
            // Set permissions of uploaded file
            $isSaved = JPath::setPermissions($this->src_file, '0644', null);
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_UPLOAD_COMPLETE', filesize($this->src_file) / 1000));
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_UPLOAD_COMPLETE', $this->src_size / 1000));
        }
        else
        {
            $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file));
            $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVING_FILE', $this->src_file), 'error', 'jerror');
        }

        $this->error = !$isSaved;

        return $isSaved;
    }

    /**
     * Override form data with image metadata
     * according to configuration. Step 2.
     *
     * @param   array  $data  The form data (as a reference)
     *
     * @return  bool    True on success, false otherwise
     *
     * @since   1.5.7
     */
    public function overrideData(&$data): bool
    {
        // Get upload date
        if (empty($data['date']) || strpos($data['date'], '1900-01-01') !== false)
        {
            $data['date'] = $data['created_time'];
            // ToDo: Use actual data if $data['created_time']; not set ?
        }

        // Override form data with image metadata
        return parent::overrideData($data);
    }

    /**
     * Analyses an error code and returns its text
     *
     * @param   int  $uploaderror  The errorcode
     *
     * @return  string  The error message
     *
     * @since   4.0.0
     */
    public function checkError($uploaderror): string
    {
        // Common PHP errors
        $uploadErrors = [1 => Text::_('COM_JOOMGALLERY_ERROR_PHP_MAXFILESIZE'), 2 => Text::_('COM_JOOMGALLERY_ERROR_HTML_MAXFILESIZE'), 3 => Text::_('COM_JOOMGALLERY_ERROR_FILE_PARTLY_UPLOADED'), 4 => Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'),];

        if (\in_array($uploaderror, $uploadErrors))
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
     * @param   array  $data  Form data
     *
     * @return  bool     True if file is detected, false otherwise
     *
     * @since   4.0.0
     */
    public function isImgUploaded($data): bool
    {
        $isUploaded = false;

        if (!empty ($data['content']) && strlen($data['content']) > 0)
        {

            $isUploaded = true;
        }

        // test mime type ... ? png, jpg, svg ?


        return $isUploaded;
    }

//    public function saveImgContent2Temp(&$data)
    public function saveImgContent2Temp($content, $tmp_path_file_name)
    {
        $isSaved = false;

        try
        {
            $base64Data = base64_decode($content);
            $count = file_put_contents($tmp_path_file_name, $base64Data);

            $isSaved = ! empty ($count);
        }
        catch (\Exception $e)
        {
            // Debug info
            //$this->component->addDebug(Text::sprintf('Failed to write file %s', $tmp_dst_file));
            //$this->component->addLog(Text::sprintf('Failed to write file %s', $filename), 'error', $logfile);

            throw new \Exception("saveImgContent2Temp failed: ", 0, $e);
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
     * @param   string  $filename     Original upload name without extension
     * @param   string  $tag          File extension e.g. 'jpg'
     * @param   int     $filecounter  Optionally a filecounter
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

        if (!\is_null($filecounter))
        {
            $maxlen = $maxlen - (\strlen($filecounter) + 1);
        }

        if (\strlen($filename) > $maxlen)
        {
            $filename = substr($filename, 0, $maxlen);
        }

        // New filename
        if (\is_null($filecounter))
        {
            $newfilename = $filename . '_' . $filedate . '_' . $randomnumber . '.' . $tag;
        }
        else
        {
            $newfilename = $filename . '_' . $filecounter . '_' . $filedate . '_' . $randomnumber . '.' . $tag;
        }

        return $newfilename;
    }

//    /**
//     * Get the UUID of the request (use for HEAD and PATCH request)
//     *
//     * @return  string  The UUID of the request
//     *
//     * @throws \InvalidArgumentException If the UUID is empty
//     */
//    private function getUserUuid(): string
//    {
//        $uuid = 0;
//        // ? CLI ....
//        $uuidFound = $this->app->input->get('uuid', '', 'string');
//
//        if (\strlen($uuidFound) === 32 && preg_match('/[a-z0-9]/', $uuidFound))
//        {
//            $uuid = $uuidFound;
//        }
//        else
//        {
//            $this->component->addLog('The uuid cannot be empty.', 'error', 'jerror');
//            throw new \InvalidArgumentException('The uuid cannot be empty.');
//        }
//
//        return $uuid;
//    }

}
