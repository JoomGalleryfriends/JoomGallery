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

use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use \Joomla\CMS\Factory;
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
   * @var boolean
   */
  public $error = false;

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
   * Constructor
   *
   * @return  void
   * @since   1.0.0
   */
  public function __construct()
  {
    $this->jg = JoomHelper::getComponent();
    $this->jg->createConfig();

    $app  = Factory::getApplication();

    $this->error         = $app->getUserStateFromRequest('joom.upload.debug', 'debug', false, 'post', 'bool');
    $this->debugoutput   = $app->getUserStateFromRequest('joom.upload.debugoutput', 'debugoutput', '', 'post', 'string');
    $this->warningoutput = $app->getUserStateFromRequest('joom.upload.warningoutput', 'warningoutput', '', 'post', 'string');
    $this->catid         = $app->getUserStateFromRequest('joom.upload.catid', 'catid', 0, 'int');
    $this->imgtitle      = $app->getUserStateFromRequest('joom.upload.title', 'imgtitle', '', 'string');
  }

  /**
	 * Method to get the direction for a given item.
	 *
	 * @return  void
	 *
	 * @since  4.0.0
	 */
	public function getDebugOutput(): string
  {
    return $this->debugoutput;
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
          ->where('owner = '.$userid);

    $timespan = $this->jg->config->get('jg_maxuserimage_timespan');
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

    do
    {
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
    }
    while(    JFile::exists($this->_ambit->getImg('orig_path', $newfilename, null, $this->catid))
           || JFile::exists($this->_ambit->getImg('img_path', $newfilename, null, $this->catid))
           || JFile::exists($this->_ambit->getImg('thumb_path', $newfilename, null, $this->catid))
         );

    return $newfilename;
  }
}
