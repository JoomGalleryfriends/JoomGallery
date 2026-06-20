<?php

/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Helper;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Content api helper.
 *
 * @since  4.0.0
 */
class ManifestHelper
{

  /**
   * Method to get the manifest from the Databas as json
   *
   * @return  null|string json representation
   *
   * @throws  ResourceNotFound
   * @since   4.1.0
   */
  public static function getDbManifestJson($extension = 'com_joomgallery'): null|string
  {
    $manifest = null;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);

      $query = $db->createQuery()
        ->select($db->quoteName('manifest_cache'))
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('element').' = '.$db->quote($extension));
      $db->setQuery($query);

      $manifest = $db->loadResult();
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
    }

    return $manifest;
  }

  /**
   * Method to get the manifest from the Databas as assoc array
   *
   * @return  null|array
   *
   * @throws  ResourceNotFound
   * @since   4.1.0
   */
  public static function getDbManifest($extension = 'com_joomgallery'): null|array
  {
    $manifest = null;

    try
    {
      $manifestJson = self::getDbManifestJson($extension);

      if(!empty($manifestJson))
      {
        $manifest = json_decode($manifestJson, true);
      }
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
      // throw new \Exception("Could not perform copy operation.", 0, $e);
    }

    return $manifest;
  }

  /**
   * Method to save the manifest from an assoc array
   *
   * @return
   *
   * @throws  ResourceNotFound
   * @since   4.1.0
   */
  public static function saveDbManifestJson(string $manifestJson, $extension = 'com_joomgallery'): null|string
  {
    $isSaved = false;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);

      $query = $db->createQuery()
        ->update($db->quoteName('#__extensions'))
        ->set($db->quoteName('manifest_cache').' = '.$db->quote($manifestJson))
        ->where($db->quoteName('element').' = '.$db->quote($extension));
      $db->setQuery($query)->execute();

      $isSaved = true;
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
    }

    return $isSaved;
  }

  /**
   * Method to save the manifest from a assoc object
   *
   * @param $oManifest
   * @param $extension
   *
   * @return mixed
   *
   * @since version
   */
  public static function saveDbManifest($oManifest, $extension = 'com_joomgallery')
  {
    $isSaved = false;

    try
    {
      if(!empty($oManifest))
      {
        $manifestJson = json_encode($oManifest); // flags

        // ToDo: Secure input $db->escape
        $isSaved = self::saveDbManifestJson($manifestJson, $extension);
      }
      else
      {
        throw new \Exception ("Can not json_encode given manifest data ");
      }
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
      // throw new \Exception("Could not perform copy operation.", 0, $e);
    }

    return $isSaved;
  }

}
