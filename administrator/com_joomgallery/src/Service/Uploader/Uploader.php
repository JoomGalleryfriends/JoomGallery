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
use \Joomla\CMS\Filesystem\File as JFile;
use \Joomla\CMS\Filesystem\Path as JPath;
use \Joomla\CMS\Filter\InputFilter;
use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Uploader\UploaderInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
* Base class for the Uploader helper classes
*
* @since  4.0.0
*/
abstract class Uploader implements UploaderInterface
{
  /**
   * Set to true if a error occured
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
   * Holds information about the upload procedure
   *
   * @var string
   */
  protected $debugoutput = '';

  /**
   * Holds warnings and informations about the uploaded images
   *
   * @var string
   */
  protected $warningoutput = '';

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
  public $imgtitle = '';

  /**
   * Holds the JoomgalleryComponent object
   *
   * @var JoomgalleryComponent
   */
  protected $jg;

  /**
   * Name of the used filesystem
   *
   * @var string
   */
  protected $filesystem_type = 'localhost';

  /**
   * Holds the paths to the image files for
   * the different image types at local and
   * storage filesystem
   *
   * @var array
   */
  protected $img_paths = array('local' => array('temp' => '', 'original' => ''), 'storage' => array());

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct()
  {
    $this->jg = JoomHelper::getComponent();
    $this->jg->createConfig();

    $app  = Factory::getApplication();

    $this->error         = $app->getUserStateFromRequest($this->userStateKey.'.error', 'error', false, 'bool');
    $this->debugoutput   = $app->getUserStateFromRequest($this->userStateKey.'.debugoutput', 'debugoutput', '', 'string');
    $this->warningoutput = $app->getUserStateFromRequest($this->userStateKey.'.warningoutput', 'warningoutput', '', 'string');
    $this->catid         = $app->getUserStateFromRequest($this->userStateKey.'.catid', 'catid', 0, 'int');
    $this->imgtitle      = $app->getUserStateFromRequest($this->userStateKey.'.imgtitle', 'imgtitle', '', 'string');
  }

  /**
	 * Method to get the debug output string.
	 *
	 * @return  void
	 *
	 * @since  4.0.0
	 */
	public function getDebug(): string
  {
    return $this->debugoutput;
  }

  /**
	 * Method to get the warning output string.
	 *
	 * @return  void
	 *
	 * @since  4.0.0
	 */
	public function getWarning(): string
  {
    return $this->warningoutput;
  }

  /**
   * Add text to the debugoutput
   *
   * @param   string   $txt        Text to add to the debugoutput
   * @param   bool     $new_line   True to add text to a new line (default: true)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function addDebug($txt, $new_line=true)
  {
    if($this->debugoutput == '' || !$new_line)
    {
      $this->debugoutput .= strval($txt);
    }
    else
    {
      $this->debugoutput .= '<br />'. strval($txt);
    }
  }

  /**
   * Add text to the warningoutput
   *
   * @param   string   $txt        Text to add to the warningoutput
   * @param   bool     $new_line   True to add text to a new line (default: true)
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function addWarning($txt, $new_line=true)
  {
    if($this->warningoutput == '' || !$new_line)
    {
      $this->warningoutput .= strval($txt);
    }
    else
    {
      $this->warningoutput .= '<br />'. strval($txt);
    }
  }

  /**
   * Rollback an erroneous upload
   *
   * @param   string  $filename    Filename of the image
   * 
   * @return  void
   * 
   * @since   1.0.0
   */
  public function rollback($filename)
  {
    // Create filesystem service
    $this->jg->createFilesystem($this->filesystem_type);

    // Get imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes');

    // Delete files in local image folders
    foreach($this->img_paths['local'] as $key => $path)
    {
      if(!is_null($path) && JFile::exists($path))
      {
        $return = JFile::delete($path);
        if($return)
        {
          $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_RB_LOCAL_'.\strtoupper($key).'DEL_OK'));
        }
        else
        {
          $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_RB_LOCAL_'.\strtoupper($key).'DEL_NOK'));
        }
      }
    }

    // Delete files in storage
    if($this->filesystem_type !== 'localhost')
    {
      foreach($this->img_paths['storage'] as $key => $path)
      {
        if(!is_null($path) && $this->jg->getFilesystem()->checkFile($path))
        {
          $return = $this->jg->getFilesystem()->deleteFile($path);
          if($return)
          {
            $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_RB_STORAGE_'.\strtoupper($key).'DEL_OK'));
          }
          else
          {
            $this->addDebug(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_RB_STORAGE_'.\strtoupper($key).'DEL_NOK'));
          }
        }
      }
    }
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
          ->where('created_by = '.$userid);

    $timespan = $this->jg->getConfig()->get('jg_maxuserimage_timespan');
    if($timespan > 0)
    {
      $query->where('imgdate > (UTC_TIMESTAMP() - INTERVAL '. $timespan .' DAY)');
    }

    $db->setQuery($query);

    return $db->loadResult();
  }

  /**
   * Calculates the serial number for images file names and titles
   *
   * @return  int       New serial number
   *
   * @since   1.0.0
   */
  protected function getSerial()
  {
    static $picserial;

    $app  = Factory::getApplication();

    // Check if the initial value is already calculated
    if(isset($picserial))
    {
      $picserial++;

      // Store the next value in the session
      $app->setUserState('joom.upload.filecounter', $picserial + 1);

      return $picserial;
    }

    // Start value set in backend
    $filecounter = $app->getUserStateFromRequest('joom.upload.filecounter', 'filecounter', 0, 'post', 'int');

    // If there is no starting value set, disable numbering
    if(!$filecounter)
    {
      return null;
    }

    // No negative starting value
    if($filecounter < 0)
    {
      $picserial = 1;
    }
    else
    {
      $picserial = $filecounter;
    }

    return $picserial;
  }

  /**
   * Generates filenames
   * e.g. <Name/gen. Title>_<opt. Filecounter>_<Date>_<Random Number>.<Extension>
   *
   * @param   string    $filename     Original upload name e.g. 'malta.jpg'
   * @param   string    $tag          File extension e.g. 'jpg'
   * @param   int       $filecounter  Optinally a filecounter
   *
   * @return  string    The generated filename
   *
   * @since   1.0.0
   */
  protected function genFilename($filename, $tag, $filecounter = null)
  {
    $filedate = date('Ymd');

    // Remove filetag = $tag incl '.'
    // Only if exists in filename
    if(stristr($filename, $tag))
    {
      $filename = substr($filename, 0, strlen($filename)-strlen($tag)-1);
    }

    // do
    // {
      mt_srand();
      $randomnumber = mt_rand(1000000000, 2099999999);

      $maxlen = 255 - 2 - strlen($filedate) - strlen($randomnumber) - (strlen($tag) + 1);
      if(!is_null($filecounter))
      {
        $maxlen = $maxlen - (strlen($filecounter) + 1);
      }
      if(strlen($filename) > $maxlen)
      {
        $filename = substr($filename, 0, $maxlen);
      }

      // New filename
      if(is_null($filecounter))
      {
        $newfilename = $filename.'_'.$filedate.'_'.$randomnumber.'.'.$tag;
      }
      else
      {
        $newfilename = $filename.'_'.$filecounter.'_'.$filedate.'_'.$randomnumber.'.'.$tag;
      }
    // }
    // while(    JFile::exists($this->_ambit->getImg('orig_path', $newfilename, null, $this->catid))
    //        || JFile::exists($this->_ambit->getImg('img_path', $newfilename, null, $this->catid))
    //        || JFile::exists($this->_ambit->getImg('thumb_path', $newfilename, null, $this->catid))
    //      );

    return $newfilename;
  }

  /**
   * Override form data with image metadata
   * according to configuration
   *
   * @param   array   $data     The form data (as a reference)
   * @param   string  $image    The file name of the original image
   * 
   * @return  bool    True on success, false otherwise
   * 
   * @since   1.5.7
   */
  protected function overrideData($data, $image)
  {
    // Get image extension
    $tag = strtolower(JFile::getExt($image));

    if(!($tag == 'jpg' || $tag == 'jpeg' || $tag == 'jpe' || $tag == 'jfif'))
    {
      // Check for the right file-format, else throw warning
      $this->addWarning(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_WRONGFILEFORMAT'));

      return true;
    }

    // Create the IMGtools service
    $this->jg->createIMGtools($this->jg->getConfig()->get('jg_imgprocessor'));

    // Get image metadata (source)
    $metadata = $this->jg->getIMGtools()->readMetadata($image);

    // Add image metadata to data
    $data['imgmetadata'] = \json_encode($metadata);

    // Check if there is something to override
    if(empty($this->jg->getConfig()->get('jg_replaceinfo')))
    {
      // Destroy the IMGtools service
      $this->jg->delIMGtools();
      
      return true;
    }

    // Load dependencies
    $filter = InputFilter::getInstance();
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';

    // Loop through all replacements defined in config
    foreach ($this->jg->getConfig()->get('jg_replaceinfo') as $replaceinfo)
    {
      $source_array = \explode('-', $replaceinfo->source);

      // Get matadata value from image
      switch ($source_array[0])
      {
        case 'IFD0':
          // 'break' intentionally omitted
        case 'EXIF':
          // Get exif source attribute
          if(isset($exif_config_array[$source_arr[0]]) && isset($exif_config_array[$source_arr[0]][$source_arr[1]]))
          {
            $source = $exif_config_array[$source_arr[0]][$source_arr[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['Attribute'];
          $source_name      = $source['Name'];

          // Get matadata value
          if(isset($metadata['exif'][$source_arr[0]]) && isset($metadata['exif'][$source_arr[0]][$source_attribute])
              && !empty($metadata['exif'][$source_arr[0]][$source_attribute]))
          {
            $source_value = $metadata['exif'][$source_arr[0]][$source_attribute];
          }
          else
          {
            // Matadata value not available in image
            $this->addWarning(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_REPLACE', $source_name));
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
            // Matadata value not available in image
            $this->addWarning(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_REPLACE', Text::_('COM_JOOMGALLERY_META_COMMENT')));
            continue 2;
          }
          break;

        case 'IPTC':
          // Get iptc source attribute
          if(isset($iptc_config_array[$source_arr[0]]) && isset($iptc_config_array[$source_arr[0]][$source_arr[1]]))
          {
            $source = $config_array[$source_arr[0]][$source_arr[1]];
          }
          else
          {
            // Unknown source
            continue 2;
          }

          $source_attribute = $source['IMM'];
          $source_name      = $source['Name'];

          // Adjust iptc source attribute
          \str_replace(':', '#', $source_attribute);

          // Get matadata value 
          if(isset($metadata['iptc'][$source_attribute]) && !empty($metadata['iptc'][$source_attribute]))
          {
            $source_value = $metadata['iptc'][$source_attribute];
          }
          else
          {
            // Matadata value not available in image
            $this->addWarning(Text::sprintf('COM_JOOMGALLERY_UPLOAD_OUTPUT_WARNING_REPLACE', $source_name));
            continue 2;
          }
          break;
        
        default:
          // Unknown metadata source
          continue 2;
          break;
      }

      // Replace target with metadata value
      if($replaceinfo->target == 'tags')
      {
        //TODO: Add tags based on metadata
      }
      else
      {
        $data[$replaceinfo->target] = $filter->clean($source_value, 'string');
        $this->addWarning(Text::_('COM_JOOMGALLERY_UPLOAD_OUTPUT_UPLOAD_REPLACE_' . \strtoupper($replaceinfo->target)));
      }
    }

    // Destroy the IMGtools service
    $this->jg->delIMGtools();

    return true;
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
    $app  = Factory::getApplication();

    // Reset file counter, delete original and create special gif selection and debug information
    $app->setUserState($this->userStateKey.'.filecounter', 0);
    $app->setUserState($this->userStateKey.'.error', false);
    $app->setUserState($this->userStateKey.'.debugoutput', null);
    $app->setUserState($this->userStateKey.'.warningoutput', null);
  }

  /**
   * Returns the path to an image without root path.
   *
   * @param   string  $type        The imagetype
   * @param   string  $catid       The id of the corresponding category
   * @param   string  $filename    The filename
   * 
   * @return  mixed   Path to the image on success, false otherwise
   * 
   * @since   4.0.0
   */
  protected function getImgPath($type, $catid, $filename)
  {
    // get imagetype object
    $imagetype = JoomHelper::getRecord('imagetype', array('typename' => $type));

    if($imagetype === false)
    {
      Factory::getApplication()->enqueueMessage('Imagetype not found!', 'error');

      return false;
    }

    // get corresponding category
    $cat = JoomHelper::getRecord('category', $catid);

    if($cat === false)
    {
      Factory::getApplication()->enqueueMessage('Category not found. Please create the category before uploading into this category.', 'error');

      return false;
    }

    // Create the complete path
    $path = $imagetype->path.\DIRECTORY_SEPARATOR.$cat->path.\DIRECTORY_SEPARATOR.$filename;

    return JPath::clean($path);
  }

  /**
   * Creates image types
   *
   * @param   string  $source         The source file for which the thumbnail and the detail image shall be created
   * @param   string  $filename       The file name for the created files
   * 
   * @return  boolean True on success, false otherwise
   * 
   * @since   1.5.7
   */
  protected function createImages($source, $filename)
  {
    // Get all imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->jg);

    // Sort imagetypes by id descending
    $imagetypes = \array_reverse($imagetypes);

    // Loop through all imagetypes
    foreach($imagetypes as $key => $config)
    {
      // Create the IMGtools service
      $this->jg->createIMGtools($this->jg->getConfig()->get('jg_imgprocessor', array($this->debugoutput)));

      // Only proceed if imagetype is active
      if($config->params->jg_imgtype != 1)
      {
        continue;
      }

      // Read source image
      if(!$this->jg->getIMGtools()->read($source))
      {
        // Read out debugoutput from IMGtools service
        $this->addDebug($this->jg->getIMGtools()->debugoutput);

        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        return false;
      }

      // Keep metadata only for original images
      if($config->typename == 'original')
      {
        $this->jg->getIMGtools()->keep_metadata = true;
      }
      else
      {
        $this->jg->getIMGtools()->keep_metadata = false;
      }

      // Do we need to keep animation?
      if($config->params->jg_imgtypeanim == 1)
      {
        // Yes
        $this->jg->getIMGtools()->keep_anim = true;
      }
      else
      {
        // No
        $this->jg->getIMGtools()->keep_anim = false;
      }

      // Do we need to auto orient?
      if($config->params->jg_imgtypeorinet == 1)
      {
        // Yes
        if(!$this->jg->getIMGtools()->orient())
        {
          // Read out debugoutput from IMGtools service
          $this->addDebug($this->jg->getIMGtools()->debugoutput);
  
          // Destroy the IMGtools service
          $this->jg->delIMGtools();
  
          return false;
        }
      }

      // Need for resize?
      if($config->params->jg_imgtyperesize > 0)
      {
        // Yes
        if(!$this->jg->getIMGtools()->resize($config->params->jg_imgtyperesize,
                                             $config->params->jg_imgtypewidth,
                                             $config->params->jg_imgtypeheight,
                                             $config->params->jg_cropposition,
                                             $config->params->jg_imgtypesharpen)
          )
        {
          // Read out debugoutput from IMGtools service
          $this->addDebug($this->jg->getIMGtools()->debugoutput);

          // Destroy the IMGtools service
          $this->jg->delIMGtools();

          return false;
        }
      }

      // Need for watermarking?
      if($config->params->jg_imgtypewatermark == 1 && property_exists($config->params->jg_imgtypewtmsettings, 'jg_imgtypewtmsettings0'))
      {
        // Yes
        $config->params->jg_imgtypewtmsettings = $config->params->jg_imgtypewtmsettings->jg_imgtypewtmsettings0;
        
        if(!$this->jg->getIMGtools()->watermark(JPATH_ROOT.\DIRECTORY_SEPARATOR.$this->jg->getConfig()->get('jg_wmfile'),
                                                $config->params->jg_imgtypewtmsettings->jg_watermarkpos,
                                                $config->params->jg_imgtypewtmsettings->jg_watermarkzoom,
                                                $config->params->jg_imgtypewtmsettings->jg_watermarksize,
                                                $config->params->jg_imgtypewtmsettings->jg_watermarkopacity)
          )
        {
          // Read out debugoutput from IMGtools service
          $this->addDebug($this->jg->getIMGtools()->debugoutput);

          // Destroy the IMGtools service
          $this->jg->delIMGtools();

          return false;
        }
      }

      // Write image to file
      $file = $this->getImgPath($config->typename, $this->catid, $filename);

      if(!$this->jg->getIMGtools()->write($file, $config->params->jg_imgtypequality))
      {
        // Read out debugoutput from IMGtools service
        $this->addDebug($this->jg->getIMGtools()->debugoutput);

        // Destroy the IMGtools service
        $this->jg->delIMGtools();

        return false;
      }

      // Read out debugoutput from IMGtools service
      $this->addDebug($this->jg->getIMGtools()->debugoutput);

      // Destroy the IMGtools service
      $this->jg->delIMGtools();
    }

    return true;
  }
}
