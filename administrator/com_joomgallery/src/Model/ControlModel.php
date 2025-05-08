<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Feed\FeedFactory;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\MVC\Model\BaseDatabaseModel;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Control model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ControlModel extends BaseDatabaseModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'control';

  /**
   * List of officials extensions
   *
   * @access  protected
   * @var     array
   */
  static protected $extensions = array();

  /**
   * Method to get the statistic data
   *
   * @return   array   Array with statistic data
   *
   * @since 4.0.0
   */
  public function getStatisticData()
  {
    $statisticdata = array();

    $db = $this->getDatabase();

    $query = $db->getQuery(true)
      ->select($db->quoteName('id'))
      ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
      ->where($db->quoteName('published') . ' = ' . $db->quote(1));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['publishedcategories'] = $db->getNumRows() - 1; // Count-1 because Root cat is not counted

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
                ->where($db->quoteName('published') . ' = ' . $db->quote(0));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['unpublishedcategories'] = $db->getNumRows();

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_IMAGES))
                ->where($db->quoteName('published') . ' = ' . $db->quote(1));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['publishedimages'] = $db->getNumRows();

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_IMAGES))
                ->where($db->quoteName('published') . ' = ' . $db->quote(0));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['unpublishedimages'] = $db->getNumRows();

    $query = $db->getQuery(true)
                ->select($db->quoteName('id'))
                ->from($db->quoteName(_JOOM_TABLE_IMAGES))
                ->where($db->quoteName('approved') . ' = ' . $db->quote(0));
    $db->setQuery($query);
    $db->execute();

    $statisticdata['unapprovedimages'] = $db->getNumRows();

    return $statisticdata;
  }

  /**
   * Method to get the Gallery info data
   *
   * @return   array   Array with info data
   *
   * @since 4.0.0
   */
  public function getGalleryInfoData()
  {
    $galleryinfodata = array();

    $db = $this->getDatabase();

    $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                ->where($db->quoteName('element') . ' = ' . $db->quote(_JOOM_OPTION));
    $db->setQuery($query);

    $galleryinfodata = \json_decode($db->loadResult(), true);

    return $galleryinfodata;
  }

  /**
   * Returns all official extensions accepted by JoomGallery::ProjectTeam
   * with some additional information like the current version number or a
   * short description of the extension
   *
   * @return  array  Array with extensions data
   * 
   * @since   4.0.0
   */
  public function getOfficialExtensionsData()
  {
    if(!empty(self::$extensions))
    {
      return self::$extensions;
    }

    $extensions = array();

    // Get url of joomgallery extensions xml    
    $server = (array) JoomHelper::getComponent()->xml->updateservers->server;
    if(!empty($server))
    {
      foreach($server as $key => $value)
      {
        if(!\is_array($value) && \strpos(\basename((string) $value), 'extensions') !== false)
        {
          $extensions_url = (string) $server[$key];
        }
      }
    }
    else
    {
      $extensions_url = _JOOM_WEBSITE_UPDATES_XML . '/extensions4.xml';
    }

    // Get an array of all available extensions
    $extensionsArray = [];
    try
    {
      foreach(JoomHelper::fetchXML($extensions_url)->extension as $extension)
      {
        $extensionsArray[] = $extension;
      }
    }
    catch (\Exception $e)
    {
      JoomHelper::getComponent()->setWarning('Error fetching list of extensions: ' . $e);
    }    

    // Get the list of extensions
    foreach($extensionsArray as $key => $xml_extension)
    {
      // Detect main JoomGallery component
      $element = (string) $xml_extension->attributes()->element;
      $type    = (string) $xml_extension->attributes()->type;
      if( (\strtolower($type) === 'component' || \strtolower($type) === 'package') &&
          \strpos(\strtolower($element), 'joomgallery') !== false
        )
      {
        // Skip main JoomGallery component
        continue;
      }

      // Get extension url
      $url  =  (string) $xml_extension->attributes()->detailsurl;
      $name =  (string) $xml_extension->attributes()->name;      

      try
      {
        $info_extension    = $this->getBestUpdate(JoomHelper::fetchXML($url));
        $extensions[$name] = \json_decode(\json_encode($info_extension), true);
      }
      catch (\Exception $e)
      {
        JoomHelper::getComponent()->setWarning('Error fetching extension info ('.(string) $xml_extension->attributes()->name.'): ' . $e);
      }
    }

    self::$extensions = $extensions;

    return $extensions;
  }

  /**
   * Method to get the installed JoomgGallery extensions
   *
   * @return   array   Array with extensions data
   *
   * @since    4.0.0
   */
  public function getInstalledExtensionsData()
  {
    $InstalledExtensionsData = array();

    $db = $this->getDatabase();

    $query = $db->getQuery(true)
                ->select($db->quoteName(array('extension_id', 'enabled', 'manifest_cache')))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('name')     . ' like ' . $db->quote('%joomgallery%'))
                ->where($db->quoteName('name')     . ' != '   . $db->quote('com_joomgallery'))
                ->orWhere($db->quoteName('folder') . ' like ' . $db->quote('%joomgallery%'));

    $db->setQuery($query);

    $InstalledExtensionsData = $db->loadRowList();

    return $InstalledExtensionsData;
  }

  /**
   * Finds the most suitable update element based on Joomla version, PHP version, and the newest available version.
   *
   * @param  SimpleXMLElement        $xml   The SimpleXMLElement containing the updates.
   *
   * @return SimpleXMLElement|false  The most suitable <update> element or false if none found.
   */
  protected function getBestUpdate($xml)
  {
    if(!isset($xml->update))
    {
      throw new \InvalidArgumentException('No <update> elements found in the provided XML.');
    }

    $bestUpdate  = false;
    $bestVersion = null;

    foreach($xml->update as $update)
    {
      // Parse the target platform and PHP minimum requirements
      $phpMinimum          = (string) $update->php_minimum;
      $targetPlatformRegex = (string) $update->targetplatform->attributes()->version;

      // Extract the major and minor version for comparison
      $majorMinorVersion = \implode('.', \array_slice(\explode('.', JVERSION), 0, 2));

      // Check Joomla version compatibility (regex matching)
      if(!empty($targetPlatformRegex) && !\preg_match('/' . $targetPlatformRegex . '/', $majorMinorVersion))
      {
        continue;
      }

      // Check PHP version compatibility
      if(!empty($phpMinimum) && \version_compare(PHP_VERSION, $phpMinimum, '<='))
      {
        continue;
      }

      // Check and compare versions to find the newest one
      $currentVersion = (string) $update->version;
      if($bestVersion === null || \version_compare($currentVersion, $bestVersion, '>'))
      {
        $bestUpdate = $update;
        $bestVersion = $currentVersion;
      }
    }

    return $bestUpdate;
  }
}
