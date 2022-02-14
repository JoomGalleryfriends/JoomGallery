<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// No direct access
defined('_JEXEC') or die;

use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Access\Access;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Table\Table as Table;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\CMS\Filesystem\File;
use \Joomla\Registry\Registry;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomla\CMS\Helper\ContentHelper;

/**
 * Config table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigTable extends Table implements VersionableTableInterface
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  A database connector object
	 */
	public function __construct(DatabaseDriver $db)
	{
		$this->typeAlias = 'com_joomgallery.config';
		parent::__construct('#__joomgallery_configs', 'id', $db);
		$this->setColumnAlias('published', 'state');
	}

	/**
	 * Get the type alias for the history table
	 *
	 * @return  string  The alias as described above
	 *
	 * @since   4.0.0
	 */
	public function getTypeAlias()
	{
		return $this->typeAlias;
	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   4.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task');

		if($array['id'] == 0 && empty($array['created_by']))
		{
			$array['created_by'] = Factory::getUser()->id;
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		if($task == 'apply' || $task == 'save')
		{
			$array['modified_by'] = Factory::getUser()->id;
		}

		// Support for multiple field: jg_uploadorder
		if(isset($array['jg_uploadorder']))
		{
			if(is_array($array['jg_uploadorder']))
			{
				$array['jg_uploadorder'] = implode(',',$array['jg_uploadorder']);
			}
			elseif(strpos($array['jg_uploadorder'], ',') != false)
			{
				$array['jg_uploadorder'] = explode(',',$array['jg_uploadorder']);
			}
			elseif(strlen($array['jg_uploadorder']) == 0)
			{
				$array['jg_uploadorder'] = '';
			}
		}
		else
		{
			$array['jg_uploadorder'] = '';
		}

		// Support for multiple field: jg_delete_original
		if(isset($array['jg_delete_original']))
		{
			if(is_array($array['jg_delete_original']))
			{
				$array['jg_delete_original'] = implode(',',$array['jg_delete_original']);
			}
			elseif(strpos($array['jg_delete_original'], ',') != false)
			{
				$array['jg_delete_original'] = explode(',',$array['jg_delete_original']);
			}
			elseif(strlen($array['jg_delete_original']) == 0)
			{
				$array['jg_delete_original'] = '';
			}
		}
		else
		{
			$array['jg_delete_original'] = '';
		}

		// Support for multiple field: jg_imgprocessor
		if(isset($array['jg_imgprocessor']))
		{
			if(is_array($array['jg_imgprocessor']))
			{
				$array['jg_imgprocessor'] = implode(',',$array['jg_imgprocessor']);
			}
			elseif(strpos($array['jg_imgprocessor'], ',') != false)
			{
				$array['jg_imgprocessor'] = explode(',',$array['jg_imgprocessor']);
			}
			elseif(strlen($array['jg_imgprocessor']) == 0)
			{
				$array['jg_imgprocessor'] = '';
			}
		}
		else
		{
			$array['jg_imgprocessor'] = '';
		}

		// Support for multiple field: jg_msg_upload_type
		if(isset($array['jg_msg_upload_type']))
		{
			if(is_array($array['jg_msg_upload_type']))
			{
				$array['jg_msg_upload_type'] = implode(',',$array['jg_msg_upload_type']);
			}
			elseif(strpos($array['jg_msg_upload_type'], ',') != false)
			{
				$array['jg_msg_upload_type'] = explode(',',$array['jg_msg_upload_type']);
			}
			elseif(strlen($array['jg_msg_upload_type']) == 0)
			{
				$array['jg_msg_upload_type'] = '';
			}
		}
		else
		{
			$array['jg_msg_upload_type'] = '';
		}

		// Support for multiple field: jg_msg_upload_recipients
		if(isset($array['jg_msg_upload_recipients']))
		{
			if(is_array($array['jg_msg_upload_recipients']))
			{
				$array['jg_msg_upload_recipients'] = implode(',',$array['jg_msg_upload_recipients']);
			}
			elseif(strpos($array['jg_msg_upload_recipients'], ',') != false)
			{
				$array['jg_msg_upload_recipients'] = explode(',',$array['jg_msg_upload_recipients']);
			}
			elseif(strlen($array['jg_msg_upload_recipients']) == 0)
			{
				$array['jg_msg_upload_recipients'] = '';
			}
		}
		else
		{
			$array['jg_msg_upload_recipients'] = '';
		}

		// Support for multiple field: jg_msg_download_type
		if(isset($array['jg_msg_download_type']))
		{
			if(is_array($array['jg_msg_download_type']))
			{
				$array['jg_msg_download_type'] = implode(',',$array['jg_msg_download_type']);
			}
			elseif(strpos($array['jg_msg_download_type'], ',') != false)
			{
				$array['jg_msg_download_type'] = explode(',',$array['jg_msg_download_type']);
			}
			elseif(strlen($array['jg_msg_download_type']) == 0)
			{
				$array['jg_msg_download_type'] = '';
			}
		}
		else
		{
			$array['jg_msg_download_type'] = '';
		}

		// Support for multiple field: jg_msg_download_recipients
		if(isset($array['jg_msg_download_recipients']))
		{
			if(is_array($array['jg_msg_download_recipients']))
			{
				$array['jg_msg_download_recipients'] = implode(',',$array['jg_msg_download_recipients']);
			}
			elseif(strpos($array['jg_msg_download_recipients'], ',') != false)
			{
				$array['jg_msg_download_recipients'] = explode(',',$array['jg_msg_download_recipients']);
			}
			elseif(strlen($array['jg_msg_download_recipients']) == 0)
			{
				$array['jg_msg_download_recipients'] = '';
			}
		}
		else
		{
			$array['jg_msg_download_recipients'] = '';
		}

		// Support for multiple field: jg_msg_comment_type
		if(isset($array['jg_msg_comment_type']))
		{
			if(is_array($array['jg_msg_comment_type']))
			{
				$array['jg_msg_comment_type'] = implode(',',$array['jg_msg_comment_type']);
			}
			elseif(strpos($array['jg_msg_comment_type'], ',') != false)
			{
				$array['jg_msg_comment_type'] = explode(',',$array['jg_msg_comment_type']);
			}
			elseif(strlen($array['jg_msg_comment_type']) == 0)
			{
				$array['jg_msg_comment_type'] = '';
			}
		}
		else
		{
			$array['jg_msg_comment_type'] = '';
		}

		// Support for multiple field: jg_msg_comment_recipients
		if(isset($array['jg_msg_comment_recipients']))
		{
			if(is_array($array['jg_msg_comment_recipients']))
			{
				$array['jg_msg_comment_recipients'] = implode(',',$array['jg_msg_comment_recipients']);
			}
			elseif(strpos($array['jg_msg_comment_recipients'], ',') != false)
			{
				$array['jg_msg_comment_recipients'] = explode(',',$array['jg_msg_comment_recipients']);
			}
			elseif(strlen($array['jg_msg_comment_recipients']) == 0)
			{
				$array['jg_msg_comment_recipients'] = '';
			}
		}
		else
		{
			$array['jg_msg_comment_recipients'] = '';
		}

		// Support for multiple field: jg_msg_report_type
		if(isset($array['jg_msg_report_type']))
		{
			if(is_array($array['jg_msg_report_type']))
			{
				$array['jg_msg_report_type'] = implode(',',$array['jg_msg_report_type']);
			}
			elseif(strpos($array['jg_msg_report_type'], ',') != false)
			{
				$array['jg_msg_report_type'] = explode(',',$array['jg_msg_report_type']);
			}
			elseif(strlen($array['jg_msg_report_type']) == 0)
			{
				$array['jg_msg_report_type'] = '';
			}
		}
		else
		{
			$array['jg_msg_report_type'] = '';
		}

		// Support for multiple field: jg_msg_report_recipients
		if(isset($array['jg_msg_report_recipients']))
		{
			if(is_array($array['jg_msg_report_recipients']))
			{
				$array['jg_msg_report_recipients'] = implode(',',$array['jg_msg_report_recipients']);
			}
			elseif(strpos($array['jg_msg_report_recipients'], ',') != false)
			{
				$array['jg_msg_report_recipients'] = explode(',',$array['jg_msg_report_recipients']);
			}
			elseif(strlen($array['jg_msg_report_recipients']) == 0)
			{
				$array['jg_msg_report_recipients'] = '';
			}
		}
		else
		{
			$array['jg_msg_report_recipients'] = '';
		}

		// Support for multiple field: jg_msg_rejectimg_type
		if(isset($array['jg_msg_rejectimg_type']))
		{
			if(is_array($array['jg_msg_rejectimg_type']))
			{
				$array['jg_msg_rejectimg_type'] = implode(',',$array['jg_msg_rejectimg_type']);
			}
			elseif(strpos($array['jg_msg_rejectimg_type'], ',') != false)
			{
				$array['jg_msg_rejectimg_type'] = explode(',',$array['jg_msg_rejectimg_type']);
			}
			elseif(strlen($array['jg_msg_rejectimg_type']) == 0)
			{
				$array['jg_msg_rejectimg_type'] = '';
			}
		}
		else
		{
			$array['jg_msg_rejectimg_type'] = '';
		}

		// Support for multiple field: group_id
		if(isset($array['group_id']))
		{
			if(is_array($array['group_id']))
			{
				$array['group_id'] = implode(',',$array['group_id']);
			}
			elseif(strpos($array['group_id'], ',') != false)
			{
				$array['group_id'] = explode(',',$array['group_id']);
			}
			elseif(strlen($array['group_id']) == 0)
			{
				$array['group_id'] = '';
			}
		}
		else
		{
			$array['group_id'] = '';
		}

		if($array['jg_maxusercat'] === '')
		{
			$array['jg_maxusercat'] = NULL;
			$this->jg_maxusercat = NULL;
		}

		if($array['jg_maxuserimage'] === '')
		{
			$array['jg_maxuserimage'] = NULL;
			$this->jg_maxuserimage = NULL;
		}

		if($array['jg_maxuserimage_timespan'] === '')
		{
			$array['jg_maxuserimage_timespan'] = NULL;
			$this->jg_maxuserimage_timespan = NULL;
		}

		if($array['jg_maxfilesize'] === '')
		{
			$array['jg_maxfilesize'] = NULL;
			$this->jg_maxfilesize = NULL;
		}

		// Support for multiple field: jg_uploaddefaultcat
		if(isset($array['jg_uploaddefaultcat']))
		{
			if(is_array($array['jg_uploaddefaultcat']))
			{
				$array['jg_uploaddefaultcat'] = implode(',',$array['jg_uploaddefaultcat']);
			}
			elseif(strpos($array['jg_uploaddefaultcat'], ',') != false)
			{
				$array['jg_uploaddefaultcat'] = explode(',',$array['jg_uploaddefaultcat']);
			}
			elseif(strlen($array['jg_uploaddefaultcat']) == 0)
			{
				$array['jg_uploaddefaultcat'] = '';
			}
		}
		else
		{
			$array['jg_uploaddefaultcat'] = '';
		}

		if($array['jg_maxuploadfields'] === '')
		{
			$array['jg_maxuploadfields'] = NULL;
			$this->jg_maxuploadfields = NULL;
		}

		// Support for multiple field: jg_redirect_after_upload
		if(isset($array['jg_redirect_after_upload']))
		{
			if(is_array($array['jg_redirect_after_upload']))
			{
				$array['jg_redirect_after_upload'] = implode(',',$array['jg_redirect_after_upload']);
			}
			elseif(strpos($array['jg_redirect_after_upload'], ',') != false)
			{
				$array['jg_redirect_after_upload'] = explode(',',$array['jg_redirect_after_upload']);
			}
			elseif(strlen($array['jg_redirect_after_upload']) == 0)
			{
				$array['jg_redirect_after_upload'] = '';
			}
		}
		else
		{
			$array['jg_redirect_after_upload'] = '';
		}

		// Support for multiple field: jg_downloadfile
		if(isset($array['jg_downloadfile']))
		{
			if(is_array($array['jg_downloadfile']))
			{
				$array['jg_downloadfile'] = implode(',',$array['jg_downloadfile']);
			}
			elseif(strpos($array['jg_downloadfile'], ',') != false)
			{
				$array['jg_downloadfile'] = explode(',',$array['jg_downloadfile']);
			}
			elseif(strlen($array['jg_downloadfile']) == 0)
			{
				$array['jg_downloadfile'] = '';
			}
		}
		else
		{
			$array['jg_downloadfile'] = '';
		}

		if($array['jg_maxvoting'] === '')
		{
			$array['jg_maxvoting'] = NULL;
			$this->jg_maxvoting = NULL;
		}

		// Support for multiple field: jg_ratingcalctype
		if(isset($array['jg_ratingcalctype']))
		{
			if(is_array($array['jg_ratingcalctype']))
			{
				$array['jg_ratingcalctype'] = implode(',',$array['jg_ratingcalctype']);
			}
			elseif(strpos($array['jg_ratingcalctype'], ',') != false)
			{
				$array['jg_ratingcalctype'] = explode(',',$array['jg_ratingcalctype']);
			}
			elseif(strlen($array['jg_ratingcalctype']) == 0)
			{
				$array['jg_ratingcalctype'] = '';
			}
		}
		else
		{
			$array['jg_ratingcalctype'] = '';
		}

		if(isset($array['params']) && is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if(isset($array['metadata']) && is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

		if(!Factory::getUser()->authorise('core.admin', 'com_joomgallery.config.' . $array['id']))
		{
			$actions         = Access::getActionsFromFile(
				JPATH_ADMINISTRATOR . '/components/com_joomgallery/access.xml',
				"/access/section[@name='config']/"
			);
			$default_actions = Access::getAssetRules('com_joomgallery.config.' . $array['id'])->getData();
			$array_jaccess   = array();

			foreach($actions as $action)
			{
				if(key_exists($action->name, $default_actions))
				{
					$array_jaccess[$action->name] = $default_actions[$action->name];
				}
			}

			$array['rules'] = $this->JAccessRulestoArray($array_jaccess);
		}

		// Bind the rules for ACL where supported.
		if(isset($array['rules']) && is_array($array['rules']))
		{
			$this->setRules($array['rules']);
		}

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0.0
	 */
	public function store($updateNulls = true)
	{
		return parent::store($updateNulls);
	}

	/**
	 * This function convert an array of Access objects into an rules array.
	 *
	 * @param   array  $jaccessrules  An array of Access objects.
	 *
	 * @return  array
	 */
	private function JAccessRulestoArray($jaccessrules)
	{
		$rules = array();

		foreach($jaccessrules as $action => $jaccess)
		{
			$actions = array();

			if($jaccess)
			{
				foreach($jaccess->getData() as $group => $allow)
				{
					$actions[$group] = ((bool)$allow);
				}
			}

			$rules[$action] = $actions;
		}

		return $rules;
	}

	/**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if(property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		// Support for subform field jg_replaceinfo
		if(is_array($this->jg_replaceinfo))
		{
			$this->jg_replaceinfo = json_encode($this->jg_replaceinfo, JSON_UNESCAPED_UNICODE);
		}

		// Support for subform field jg_staticprocessing
		if(is_array($this->jg_staticprocessing))
		{
			$this->jg_staticprocessing = json_encode($this->jg_staticprocessing, JSON_UNESCAPED_UNICODE);
		}

		// Support for subform field jg_dynamicprocessing
		if(is_array($this->jg_dynamicprocessing))
		{
			$this->jg_dynamicprocessing = json_encode($this->jg_dynamicprocessing, JSON_UNESCAPED_UNICODE);
		}

		return parent::check();
	}

	/**
	 * Define a namespaced asset name for inclusion in the #__assets table
	 *
	 * @return string The asset name
	 *
	 * @see Table::_getAssetName
	 */
	protected function _getAssetName()
	{
		$k = $this->_tbl_key;

		return $this->typeAlias . '.' . (int) $this->$k;
	}

	/**
	 * Returns the parent asset's id. If you have a tree structure, retrieve the parent's id using the external key field
	 *
	 * @param   Table   $table  Table name
	 * @param   integer  $id     Id
	 *
	 * @see Table::_getAssetParentId
	 *
	 * @return mixed The id on success, false on failure.
	 */
	protected function _getAssetParentId($table = null, $id = null)
	{
		// We will retrieve the parent-asset from the Asset-table
		$assetParent = Table::getInstance('Asset');

		// Default: if no asset-parent can be found we take the global asset
		$assetParentId = $assetParent->getRootId();

		// The item has the component as asset-parent
		$assetParent->loadByName('com_joomgallery');

		// Return the found asset-parent-id
		if($assetParent->id)
		{
			$assetParentId = $assetParent->id;
		}

		return $assetParentId;
	}

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->load($pk);
    $result = parent::delete($pk);

    return $result;
  }
}
