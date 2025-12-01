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
 * Trait for Tables with default assets
 *
 * @since  4.0.0
 */
trait AssetTableTrait
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
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
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
		if(property_exists($this, 'title'))
		{
      return $this->title;
    }


      return $this->_getAssetName();

	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
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

		// The item has the component as asset-parent
		$assetTable->loadByName(_JOOM_OPTION);

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
		// Add the rules for ACL
		$rules = new Rules('{}');
		$this->setRules($rules);
  }
}
