<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Table\Asset;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Access\Rules;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Table\Table;

/**
 * Trait for Table methods with multiple assets
 *
 * @since  4.0.0
 */
trait MultipleAssetsTableTrait
{
  /**
   * Define a namespaced asset name for inclusion in the #__assets table
   *
   * @return string The asset name
   *
   * @since 4.0.0
   * @see Joomla\CMS\Table\Table::_getAssetName
   */
  protected function _getAssetName($itemtype = null)
  {
    $keys = [];

    if(\is_null($itemtype))
    {
      $itemtype = $this->def_itemtype;
    }

    foreach ($this->_tbl_keys as $k)
    {
        $keys[] = (int) $this->$k;
    }

    return _JOOM_OPTION . '.' . $itemtype . '.' . implode('.', $keys);
  }

  /**
   * Returns the parent asset's id.
   *
   * @param   Table    $table  Table name
   * @param   integer  $id     Id
   *
   * @return mixed The id on success, false on failure.
   * 
   * @since 4.0.0
   * @see Joomla\CMS\Table\Table::_getAssetParentId
   */
  protected function _getAssetParentId($table = null, $id = null, $itemtype = null)
  {
    // We will retrieve the parent-asset from the Asset-table
    $assetTable = new Asset($this->getDbo());

    if(!\is_null($itemtype) && $itemtype != $this->def_itemtype)
    {
      // The item is a child of the current item
      $parent_id = \intval($this->id);
      $assetTable->loadByName(_JOOM_OPTION.'.'.$this->def_itemtype.'.'.$parent_id);
    }
    elseif($this->parent_id && \intval($this->parent_id) >= 1)
    {
      // The item has a category as asset-parent
      $parent_id = \intval($this->parent_id);
      $assetTable->loadByName(_JOOM_OPTION.'.'.$this->def_itemtype.'.'.$parent_id);
    }
    else
    {
      // The item has the component as asset-parent
      $assetTable->loadByName(_JOOM_OPTION);
    }

    // Return the found asset-parent-id
    if($assetTable->id)
    {
      $assetParentId = $assetTable->id;
    }
    else
    {
      // If no asset-parent can be found we take the global asset
      $assetParentId = $assetTable->getRootId();
    }

    return $assetParentId;
  }

  /**
   * Method to return the title to use for the asset table.
   *
   * @return  string
   *
   * @since 4.0.0
   * @see Joomla\CMS\Table\Table::_getAssetTitle
   */
  protected function _getAssetTitle($itemtype = null)
  {
    if(!\is_null($itemtype) && $itemtype != $this->def_itemtype)
    {
      return $this->title.' ('.$itemtype.')';
    }


      return $this->title;

  }

  /**
   * Method to set empty rules for the record based on a form.
   * 
   * @param   Form  $form  The form object where the rules gets extracted
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setEmptyRules(Form $form)
  {
    $fieldsets = $form->getFieldsets();

    // Default itemtype
    $def_itemtype = explode('.', $form->getName(), 2)[1];

    foreach ($fieldsets as $key => $fieldset)
    {
      if(strpos($key, 'accesscontrol') !== false)
      {
        $formItems = $form->getFieldset($key);

        foreach ($formItems as $itemkey => $formItem)
        {
          if(strpos($itemkey, 'rules') !== false)
          {
            // We found a rules entry in the data
            $rulename = strstr($itemkey, 'rules');

            if($rulename === 'rules')
            {
              $itemtype = $def_itemtype;
            }
            else
            {
              $itemtype = str_replace('rules_', '', $rulename);
            }

            // Add the rules for ACL
            $rules = new Rules('{}');
            $this->setRules($rules, $itemtype);
          }
        }
      }
    }
  }
}
