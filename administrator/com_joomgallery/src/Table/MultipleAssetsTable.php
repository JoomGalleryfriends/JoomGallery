<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Table\Nested as Table;
use \Joomla\CMS\Access\Rules;
use \Joomla\String\StringHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\MultipleAssetsTableTrait;

/**
 * Category table for records with multiple assets
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class MultipleAssetsTable extends Table
{
  use MultipleAssetsTableTrait;
  use LegacyDatabaseTrait;

  /**
   * The rules associated with this record.
   *
   * @var    array  A listof Rules objects.
   * @since  4.0.0
   */
  protected $_rules;

  /**
   * The default itemtype
   *
   * @var    string  The name of the itemtype
   * @since  4.0.0
   */
  public $def_itemtype = '';

  /**
   * Method to set rules for the record.
   *
   * @param   mixed   $input     A Rules object, JSON string, or array.
   * @param   string  $itemtype  The name to identify the rule.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setRules($input, $itemtype=null)
  {
    if(\is_null($itemtype))
    {
      $itemtype = $this->def_itemtype;
    }

    if ($input instanceof Rules)
    {
      $this->_rules[$itemtype] = $input;
    }
    else
    {
      $this->_rules[$itemtype] = new Rules($input);
    }
  }

  /**
   * Method to get the rules for the record.
   * 
   * @param   string  $itemtype  The name to identify the rule.
   *
   * @return  mixed   One or multiple Rule objects
   *
   * @since   4.0.0
   */
  public function getRules($itemtype=null)
  {
    if($itemtype = 'all')
    {
      return $this->_rules;
    }

    if(\is_null($itemtype))
    {
      $itemtype = $this->def_itemtype;
    }

    return $this->_rules[$itemtype];
  }

  /**
   * Method to store a row in the database from the Table instance properties.
   * 
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function store($updateNulls = false)
  {
    // Temporary disable the property _trackAssets
    $trackAssets = $this->_trackAssets;
    $this->_trackAssets = false;

    $result = parent::store($updateNulls);

    if ($trackAssets) {
      if ($this->_locked)
      {
        $this->_unlock();
      }

      /*
       * Asset Tracking
       */
      foreach ($this->_rules as $key => $rule)
      {
        $parentId = $this->_getAssetParentId($this->_tbl, $this->id, $key);
        $name     = $this->_getAssetName($key);
        $title    = $this->_getAssetTitle($key);

        $asset = new Asset($this->getDatabase(), $this->getDispatcher());
        $asset->loadByName($name);

        // Get asset id property name
        $assetIdName = 'asset_id';
        if ($key != $this->def_itemtype)
        {
          $assetIdName = 'asset_id_' . $key;
        }

        // Re-inject the asset id.
        $this->{$assetIdName} = $asset->id;

        // Check for an error.
        $error = $asset->getError();

        if ($error) {
            $this->setError($error);

            return false;
        } else {
            // Specify how a new or moved node asset is inserted into the tree.
            if (empty($this->{$assetIdName}) || $asset->parent_id != $parentId) {
                $asset->setLocation($parentId, 'last-child');
            }

            // Prepare the asset to be stored.
            $asset->parent_id = $parentId;
            $asset->name      = $name;

            // Respect the table field limits
            $asset->title = StringHelper::substr($title, 0, 100);

            if ($rule instanceof Rules) {
                $asset->rules = (string) $rule;
            }

            if (!$asset->check() || !$asset->store()) {
                $this->setError($asset->getError());

                return false;
            } else {
                // Create an asset_id or heal one that is corrupted.
                if (empty($this->{$assetIdName}) || ($currentAssetId != $this->{$assetIdName} && !empty($this->{$assetIdName}))) {
                    // Update the asset_id field in this table.
                    $this->{$assetIdName} = (int) $asset->id;

                    $query = $this->getDatabase()->getQuery(true)
                        ->update($this->getDatabase()->quoteName($this->_tbl))
                        ->set($assetIdName . ' = ' . (int) $this->{$assetIdName});
                    $this->appendPrimaryKeys($query);
                    $this->getDatabase()->setQuery($query)->execute();
                }
            }
        }
      }
    }

    // Write back the original value of the property _trackAssets
    $this->_trackAssets = $trackAssets;

    return $result;
  }

  /**
   * Method to delete a node and, optionally, its child nodes from the table.
   *
   * @param   integer  $pk        The primary key of the node to delete.
   * @param   boolean  $children  True to delete child nodes, false to move them up a level.
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function delete($pk = null, $children = true)
  {
    // Temporary disable the property _trackAssets
    $trackAssets = $this->_trackAssets;
    $this->_trackAssets = false;

    $result = parent::delete($pk, $children);

    if($trackAssets)
    {
      // Look for assets in object properties
      foreach (\get_object_vars($this) as $key => $value)
      {
        if(strpos($key, 'asset_id') !== false)
        {
          // We found an asset, get itemtype of asset
          $itemtype = \str_replace('asset_id', '', $key);
          if($itemtype == '')
          {
            $itemtype = $this->def_itemtype;
          }
          else
          {
            $itemtype = \ltrim($itemtype, '_');
          }
          
          //Get the asset name
          $name  = $this->_getAssetName($itemtype);

          $asset = new Asset($this->getDatabase(), $this->getDispatcher());

          if ($asset->loadByName($name)) {
            // Delete the node in assets table.
            if (!$asset->delete(null, $children)) {
                $this->setError($asset->getError());

                return false;
            }
          } else {
              $this->setError($asset->getError());

              return false;
          }
        }
      }
    }

    // Write back the original value of the property _trackAssets
    $this->_trackAssets = $trackAssets;

    return $result;
  }  
}
