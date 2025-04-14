<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Access\Base;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Class that handles all access authorisation routines for own elements.
 *
 * @since  4.0.0
 */
class AccessOwn extends Access
{
  /**
   * List of view levels ('id' => 'title')
   *
   * @var    array
   */
  protected static $viewLevelsList = array();

  /**
   * List of content types which do not have their own assets but uses assets
   * of its parent content types.
   * --> Gets filled by the access service class
   *
   * @var array
   */
  public static $parent_dependent_types = array();

  /**
   * List of content types which have only one global asset
   * --> Gets filled by the access service class
   *
   * @var array
   */
  public static $global_types = array();

  /**
   * Method to check against own access.
   *
   * @param   integer         $userId    Id of the user for which to check authorisation.
   * @param   string          $action    The name of the action to authorise.
   * @param   integer|string  $assetKey  The asset key (asset id or asset name). null fallback to root asset.
   * @param   boolean         $preload   Indicates whether preloading should be used.
   * @param   integer         $key       The item key (in order to check ownership)
   *
   * @return  bool   True if permission allowed, false if denied
   *
   * @since   4.0.0
   */
  public static function checkOwn($userId, $action, $assetKey = null, $preload = true, $key = 0)
  {
    // Sanitise inputs.
    $userId  = (int) $userId;
    $action  = strtolower(preg_replace('#[\s\-]+#', '.', trim($action)));

    if (!isset(self::$identities[$userId]))
    {
      // Get all groups against which the user is mapped.
      self::$identities[$userId] = self::getGroupsByUser($userId);
      array_unshift(self::$identities[$userId], $userId * -1);
    }

    // Preload asset rules
    $recursive            = true;
    $recursiveParentAsset = true;
    self::getAssetRules($assetKey, $action, $recursive, $recursiveParentAsset, $preload);

    // Get assets info
    $assetKey      = self::cleanAssetKey($assetKey);
    $assetId       = self::getAssetId($assetKey);
    $assetName     = self::getAssetName($assetKey);
    $assetArray    = \explode('.', $assetName);
    $extensionName = self::getExtensionNameFromAsset($assetName);

    // Collects permissions for each asset
    $collected = array();

    // Get all asset ancestors
    $ancestors = array_reverse(self::getAssetAncestors($extensionName, $assetId));      

    // Get roles for all asset ancestors
    foreach($ancestors as $i => $id)
    {
      // There are no rules for this ancestor
      if(!isset(self::$assetPermissionsParentIdMapping[$extensionName][$id]))
      {
        continue;
      }

      // If full recursive mode, but not recursive parent mode, do not add the extension asset rules.
      if($recursive && !$recursiveParentAsset && self::$assetPermissionsParentIdMapping[$extensionName][$id]->name === $extensionName)
      {
        continue;
      }

      // If not full recursive mode, but recursive parent mode, do not add other recursion rules.
      if (
          !$recursive && $recursiveParentAsset && self::$assetPermissionsParentIdMapping[$extensionName][$id]->name !== $extensionName
          && (int) self::$assetPermissionsParentIdMapping[$extensionName][$id]->id !== $assetId
          )
      {
        continue;
      }

      $collected[$i] = self::$assetPermissionsParentIdMapping[$extensionName][$id];

      // Add owner to collection
      $ancArray = \explode('.', $collected[$i]->name);
      if(\count($ancArray) >= 3)
      {
        // check if it is a parent dependent type
        if(\in_array($ancArray[1], self::$parent_dependent_types))
        {
          // here we do not trust the asset key. We use the provided item key instead.
          $ancArray[2] = $key;
        }

        $collected[$i]->owner = JoomHelper::getCreator($ancArray[1], $ancArray[2]);
      }
      else
      {
        $collected[$i]->owner = false;
      }
    }

    return self::allowOwn($userId, $action, $collected);
  }

  /**
   * Checks that this action can be performed by an identity.
   *
   * @param   integer   $userId      Id of the user for which to check authorisation.
   * @param   string    $action      The name of the action to authorise.
   * @param   array     $ancestors   List of assets (ancestors and current asset)
   *
   * @return  mixed     True if allowed, false for an explicit deny, null for an implicit deny.
   *
   * @since   4.0.0
   */
  public static function allowOwn($userId, $action, $ancestors)
  {
    // Implicit deny by default.
    $result = null;

    $groupsOfUser = self::$identities[$userId];

    $assetOwner = null;
    if (isset(\end($ancestors)->owner))
    {
      $assetOwner = \end($ancestors)->owner;
    }

    foreach($ancestors as $key => $ancestor)
    {
      // Get rules
      $rules = \json_decode($ancestor->rules);
      if(!\in_array($action, \array_keys(\get_object_vars($rules))))
      {
        // This ancestor does not contain any rule for the current action
        continue;
      }
    
      if($assetOwner == $userId)
      {
        // User is owner of this ancestor
        foreach($rules->{$action} as $groupId => $allowed)
        {
          if(\in_array($groupId, $groupsOfUser))
          {
            // Usergroup is allowed to perform the action
            $result = \boolval($allowed);

            // An explicit deny wins.
            if($result === false)
            {
              break;
            }
          }
        }
      }
    }

    return $result;
  }

  /**
   * Returns a list of available view levels.
   *
   * @return  array   List of view levels.
   *
   * @since   4.0.0
   */
  public static function getViewLevels()
  {
    // Only load the view levels once.
    if(empty(self::$viewLevelsList))
    {
      // Get a database object.
      $db = Factory::getDbo();

      // Build the base query.
      $query = $db->getQuery(true)
          ->select($db->quoteName(['id', 'title']))
          ->from($db->quoteName('#__viewlevels'));

      // Set the query for execution.
      $db->setQuery($query);

      // Build the view levels array.
      self::$viewLevelsList = $db->loadAssocList('id');
    }

    return self::$viewLevelsList;
  }
}
