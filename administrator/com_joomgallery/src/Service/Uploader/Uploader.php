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
use \Joomla\CMS\Filter\InputFilter;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Object\CMSObject;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
* Base class for the Uploader helper classes
*
* @since  4.0.0
*/
abstract class Uploader implements UploaderInterface
{
  use ServiceTrait;

  /**
   * Set to true if a error occurred
   *
   * @var bool
   */
  public $error = false;

  /**
   * Holds the key of the user state variable to be used
   *
   * @var string
   */
  protected $userStateKey = 'com_joomgallery.image.upload';

  /**
   * Counter for the number of files already uploaded
   *
   * @var int
   */
  public $filecounter = 0;

  /**
   * The ID of the category in which
   * the images shall be uploaded
   *
   * @var int
   */
  public $catid = 0;

  /**
   * The title of the image if the original
   * file name shouldn't be used
   *
   * @var string
   */
  public $title = '';

  /**
   * Name of the used filesystem
   *
   * @var string
   */
  protected $filesystem_type = 'local-images';

  /**
   * Set to true if it is a multiple upload
   *
   * @var bool
   */
  protected $multiple = false;

  /**
   * Set to true if it is a asynchronous upload
   *
   * @var bool
   */
  protected $async = false;

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
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    $this->component->createConfig();

    $this->multiple    = $multiple;
    $this->async       = $async;

    $this->error       = $this->app->getUserStateFromRequest($this->userStateKey.'.error', 'error', false, 'bool');
    $this->catid       = $this->app->getUserStateFromRequest($this->userStateKey.'.catid', 'catid', 0, 'int');
    $this->title    = $this->app->getUserStateFromRequest($this->userStateKey.'.title', 'title', '', 'string');
    $this->filecounter = $this->app->getUserStateFromRequest($this->userStateKey.'.filecounter', 'filecounter', 1, 'post', 'int');
    $this->component->addDebug($this->app->getUserStateFromRequest($this->userStateKey.'.debugoutput', 'debugoutput', '', 'string'));
    $this->component->addWarning($this->app->getUserStateFromRequest($this->userStateKey.'.warningoutput', 'warningoutput', '', 'string'));
  }

  /**
	 * Base method to retrieve an uploaded image. Step 1.
   * Method has to be extended! Do not use it in this way!
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
    // Create filesystem service
    $this->component->createFilesystem();

    // Get extension
    $tag = $this->component->getFilesystem()->getExt($this->src_name);

    // Get supported formats of image processor
    $this->component->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));
    $supported_ext = $this->component->getIMGtools()->get('supported_types');
    $allowed_imgtools = \in_array(\strtoupper($tag), $supported_ext);
    $this->component->delIMGtools();

    // Get supported formats of filesystem
    $allowed_filesystem = $this->component->getFilesystem()->isAllowedFile($this->src_name);

    // Check for supported image format
    if(!$allowed_imgtools || !$allowed_filesystem || strlen($this->src_tmp) == 0 || $this->src_tmp == 'none')
    {
      $this->component->addDebug(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_IMAGEFILE_TYPE'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_UNSUPPORTED_IMAGEFILE_TYPE'), 'error', 'jerror');
      $this->error  = true;

      return false;
    }

    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FILENAME', $this->src_name));

    // Image size must not exceed the setting in backend if we are in frontend
    if($this->app->isClient('site') && $this->src_size > $this->component->getConfig()->get('jg_maxfilesize'))
    {
      $this->component->addDebug(Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $this->component->getConfig()->get('jg_maxfilesize')));
      $this->component->addLog(Text::sprintf('JGLOBAL_MAXIMUM_UPLOAD_SIZE_LIMIT', $this->component->getConfig()->get('jg_maxfilesize')), 'error', 'jerror');
      $this->error  = true;

      return false;
    }

    if($filename)
    {
      // Get filecounter
      $filecounter = null;
      if($this->multiple && $this->component->getConfig()->get('jg_filenamenumber'))
      {
        $filecounter = $this->getSerial();
      }

      // Create filename, title and alias
      if($this->component->getConfig()->get('jg_useorigfilename'))
      {
        $data['title'] = \pathinfo($this->src_name, PATHINFO_FILENAME);
      }
      else
      {
        if(!\is_null($filecounter))
        {
          $data['title'] = $data['title'].'-'.$filecounter;
        }
      }
      $newfilename = $this->component->getFilesystem()->cleanFilename($data['title'], 0);

      // Generate image filename
      $this->component->createFileManager($data['catid']);
      $data['filename'] = $this->component->getFileManager()->genFilename($newfilename, $tag, $filecounter);

      // Make an alias proposition if not given
      if(!\key_exists('alias', $data) || empty($data['alias']))
      {
        $data['alias'] = $data['title'];
      }
    }

    // Set filesystem
    $data['filesystem'] = $this->component->getFilesystem()->get('filesystem');

    // Trigger onJoomBeforeUpload
    $plugins  = $this->app->triggerEvent('onJoomBeforeUpload', array($data['filename']));
    if(in_array(false, $plugins, true))
    {
      return false;
    }

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
    // Create filesystem service
    $this->component->createFilesystem();

    // Get image extension
    $tag = $this->component->getFilesystem()->getExt($this->src_file);

    if(!($tag == 'jpg' || $tag == 'jpeg' || $tag == 'jpe' || $tag == 'jfif'))
    {
      // Check for the right file-format, else throw warning
      $this->component->addWarning(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_READ_METADATA'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_READ_METADATA'), 'warning', 'jerror');

      return true;
    }

    // Create the IMGtools service
    $this->component->createIMGtools($this->component->getConfig()->get('jg_imgprocessor'));

    // Get image metadata (source)
    $metadata = $this->component->getIMGtools()->readMetadata($this->src_file);

    // Add image metadata to data
    $data['imgmetadata'] = \json_encode($metadata);

    // Check if there is something to override
    if(!\property_exists($this->component->getConfig()->get('jg_replaceinfo'), 'jg_replaceinfo0'))
    {
      // Destroy the IMGtools service
      $this->component->delIMGtools();

      return true;
    }

    // Load dependencies
    $filter = InputFilter::getInstance();
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';

    $lang = Factory::getLanguage();
    $lang->load(_JOOM_OPTION.'.exif', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);
    $lang->load(_JOOM_OPTION.'.iptc', JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION);

    // Loop through all replacements defined in config
    foreach ($this->component->getConfig()->get('jg_replaceinfo') as $replaceinfo)
    {
      $source_array = \explode('-', $replaceinfo->source);

      // Get metadata value from image
      switch ($source_array[0])
      {
        case 'IFD0':
          // 'break' intentionally omitted
        case 'EXIF':
          // Get exif source attribute
          if(isset($exif_config_array[$source_array[0]]) && isset($exif_config_array[$source_array[0]][$source_array[1]]))
          {
            $source = $exif_config_array[$source_array[0]][$source_array[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['Attribute'];
          $source_name      = $source['Name'];

          // Get metadata value
          if(isset($metadata['exif'][$source_array[0]]) && isset($metadata['exif'][$source_array[0]][$source_attribute])
              && !empty($metadata['exif'][$source_array[0]][$source_attribute]))
          {
            $source_value = $metadata['exif'][$source_array[0]][$source_attribute];
          }
          else
          {
            // Metadata value not available in image
            if($this->component->getConfig()->get('jg_replaceshowwarning') > 0)
            {
              if($source_attribute == 'DateTimeOriginal')
              {
                $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA_DATE', Text::_($source_name)));
                $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA_DATE', Text::_($source_name)), 'warning', 'jerror');
              }
              else
              {
                $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_($source_name)));
                $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_($source_name)), 'warning', 'jerror');
              }
            }

            continue 2;
          }
          break;

        case 'COMMENT':
          // Get metadata value
          if(isset($metadata['comment']) && !empty($metadata['comment']))
          {
            $source_value = $metadata['comment'];
          }
          else
          {
            // Metadata value not available in image
            if($this->component->getConfig()->get('jg_replaceshowwarning') > 0)
            {
              $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_('COM_JOOMGALLERY_COMMENT')));
              $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_('COM_JOOMGALLERY_COMMENT')), 'warning', 'jerror');
            }

            continue 2;
          }
          break;

        case 'IPTC':
          // Get iptc source attribute
          if(isset($iptc_config_array[$source_array[0]]) && isset($iptc_config_array[$source_array[0]][$source_array[1]]))
          {
            $source = $iptc_config_array[$source_array[0]][$source_array[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['IMM'];
          $source_name      = $source['Name'];

          // Adjust iptc source attribute
          $source_attribute = \str_replace(':', '#', $source_attribute);

          // Get metadata value
          if(isset($metadata['iptc'][$source_attribute]) && !empty($metadata['iptc'][$source_attribute]))
          {
            $source_value = $metadata['iptc'][$source_attribute];
          }
          else
          {
            // Metadata value not available in image
            if($this->component->getConfig()->get('jg_replaceshowwarning') > 0)
            {
              $this->component->addWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_($source_name)));
              $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_WARNING_REPLACE_NO_METADATA', Text::_($source_name)), 'warning', 'jerror');
            }

            continue 2;
          }
          break;

        default:
          // Unknown metadata source
          continue 2;
          break;
      }


      if($this->component->getConfig()->get('jg_replaceshowwarning') == 2)
      {
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_REPLACE_METAHINT'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_REPLACE_METAHINT'), 'warning', 'jerror');
      }

      // Replace target with metadata value
      if($replaceinfo->target == 'tags')
      {
        //TODO: Add tags based on metadata
      }
      elseif($replaceinfo->target == 'title')
      {
        // Ttitle influences title, alias and filename
        $data['title'] = $filter->clean($source_value, 'string');

        // Recreate alias
        if(Factory::getConfig()->get('unicodeslugs') == 1)
        {
          $data['alias'] = OutputFilter::stringURLUnicodeSlug(trim($data['title']));
        }
        else
        {
          $data['alias'] = OutputFilter::stringURLSafe(trim($data['title']));
        }

        // Get filecounter
        $filecounter = null;
        if($this->multiple && $this->component->getConfig()->get('jg_filenamenumber'))
        {
          $filecounter = $this->getSerial();
        }

        // Adjust filename
        $tag              = $this->component->getFilesystem()->getExt($this->src_name);
        $newfilename      = $this->component->getFilesystem()->cleanFilename($data['title'], 0);
        $data['filename'] = $this->component->getFileManager()->genFilename($newfilename, $tag, $filecounter);

        // Write debug info
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_' . \strtoupper('title')));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_' . \strtoupper('title')), 'warning', 'jerror');
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_ALIAS_FILENAME'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_ALIAS_FILENAME'), 'warning', 'jerror');

      }
      else
      {
        $data[$replaceinfo->target] = $filter->clean($source_value, 'string');
        $this->component->addWarning(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_' . \strtoupper($replaceinfo->target)));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_DEBUG_REPLACE_' . \strtoupper($replaceinfo->target)), 'warning', 'jerror');
      }
    }

    // Destroy the IMGtools service
    $this->component->delIMGtools();

    return true;
  }


  /**
	 * Method to create uploaded image files. Step 3.
   * (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
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
    $this->component->createFileManager($data_row->catid);

    // Create image types
    if(!$this->component->getFileManager()->createImages($this->src_file, $data_row->filename, $data_row->catid))
    {
      $this->rollback($data_row);
      $this->error = true;

      return false;
    }

    // Message about new image
    $jg_filenamenumber = $this->component->getConfig()->get('jg_filenamenumber');
    if($this->app->isClient('site') && $jg_filenamenumber !== 'none')
    {
      // Create message service
      $this->component->createMessenger($jg_filenamenumber);

      // Get user
      $user = Factory::getUser();

      // Get category
      $cat = JoomHelper::getRecord('category', $data_row->catid, $this->component);

      // Template variables
      $tpl_vars = array( 'user_id' => $user->id,
                         'user_username' => $user->username,
                         'user_name' => $user->name,
                         'img_id' => $data_row->id,
                         'img_title' => $data_row->title,
                         'cat_id' => $cat->id,
                         'cat_title' => $cat->title
                        );

      // Setting up message template
      $this->component->getMessenger()->selectTemplate(_JOOM_OPTION.'.newimage');
      $this->component->getMessenger()->addTemplateData($tpl_vars);

      // Get recipients
      $recipients = $this->component->getConfig()->get('jg_msg_upload_recipients');

      // Send message
      $this->component->getMessenger()->send($recipients);
    }

    $this->component->addDebug(' ');
    $this->component->addDebug(Text::_('COM_JOOMGALLERY_SERVICE_SUCCESS_CREATE_IMAGETYPE_END'));
    $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_FILENAME', $data_row->filename));

    $this->app->triggerEvent('onJoomAfterUpload', array($data_row));

    // Reset user states
    $this->resetUserStates();

    return !$this->error;
  }

  /**
   * Rollback an erroneous upload
   *
   * @param   CMSObject   $data_row     Image object containing at least catid and filename (default: false)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function rollback($data_row=false)
  {
    if($data_row)
    {
      // Create file manager service
      $this->component->createFileManager($data_row->catid);

      // Delete just created images
      $this->component->getFileManager()->deleteImages($data_row);
    }

    // Delete temp images
    $this->deleteTmp();

    $this->resetUserStates();
  }

  /**
   * Delete all temporary created files which were created during upload
   *
   * @return  bool     True if files are deleted, false otherwise
   *
   * @since   4.0.0
   */
  public function deleteTmp(): bool
  {
    $files = array();

    if(isset($this->src_file) && !empty($this->src_file) && \file_exists($this->src_file))
    {
      \array_push($files, $this->src_file);
    }

    if(isset($this->src_tmp) && !empty($this->src_tmp) && \file_exists($this->src_tmp))
    {
      \array_push($files, $this->src_tmp);
    }

    return JFile::delete($files);
  }

  /**
   * Returns the number of images of the current user
   *
   * @param   $userid  Id of the current user
   *
   * @return  int      The number of images of the current user
   *
   * @since   1.5.5
   */
  protected function getImageNumber($userid)
  {
    $db = Factory::getDbo();

    $query = $db->getQuery(true)
          ->select('COUNT(id)')
          ->from(_JOOM_TABLE_IMAGES)
          ->where('created_by = '.\intval($userid));

    $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');
    if($timespan > 0)
    {
      $query->where('date > (UTC_TIMESTAMP() - INTERVAL '. $timespan .' DAY)');
    }

    $db->setQuery($query);

    return $db->loadResult();
  }

  /**
   * Calculates the serial number for images file names and titles
   *
   * @return  int       New serial number
   *
   * @since   4.0.0
   */
  protected function getSerial()
  {
    // Check if the initial value is already calculated
    if(isset($this->filecounter))
    {
      // In asynchronous uploads, the filecounter is upcounted in the frontend
      if(!$this->async)
      {
        // Store the next value in the session
        $this->app->setUserState($this->userStateKey.'.filecounter', $this->filecounter + 1);
      }

      return $this->filecounter;
    }

    // If there is no starting value set, disable numbering
    if(!$this->filecounter)
    {
      return null;
    }

    // No negative starting value
    if($this->filecounter < 0)
    {
      $picserial = 1;
    }
    else
    {
      $picserial = $this->filecounter;
    }

    return $picserial;
  }

  /**
   * Resets user states
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function resetUserStates()
  {
    // Reset file counter, delete original and create special gif selection and debug information
    $this->app->setUserState($this->userStateKey.'.filecounter', 1);
    $this->app->setUserState($this->userStateKey.'.error', false);
    $this->app->setUserState($this->userStateKey.'.debugoutput', null);
    $this->app->setUserState($this->userStateKey.'.warningoutput', null);
  }

  /**
   * Creation of a temporary image object for the rollback
   *
   * @param   array   $data      The form data
   *
   * @return  CMSObject
   *
   * @since   4.0.0
   */
  protected function tempImgObj($data)
  {
    if(!\key_exists('catid', $data) || !empty($data['catid']) || !\key_exists('filename', $data) || !empty($data['filename']))
    {
      $this->component->addLog('Form data must have at least catid and filename', 'error', 'jerror');
      throw new \Exception('Form data must have at least catid and filename');
    }

    $img = new CMSObject;

    $img->set('catid', $data['catid']);
    $img->set('filename', $data['filename']);

    return $img;
  }
}
