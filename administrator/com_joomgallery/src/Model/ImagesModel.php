<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Methods supporting a list of Images records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesModel extends ListModel
{
	/**
	* Constructor.
	*
	* @param   array  $config  An optional associative array of configuration settings.
	*
	* @see        JController
	* @since      1.6
	*/
	public function __construct($config = array())
	{
		if (empty($config['filter_fields']))
		{
			$config['filter_fields'] = array(
				'ordering', 'a.ordering',
				'hits', 'a.hits',
				'downloads', 'a.downloads',
				'imgvotes', 'a.imgvotes',
				'imgvotesum', 'a.imgvotesum',
				'approved', 'a.approved',
				'useruploaded', 'a.useruploaded',
				'imgtitle', 'a.imgtitle',
				'alias', 'a.alias',
				'catid', 'a.catid',
				'published', 'a.published',
				'imgauthor', 'a.imgauthor',
				'language', 'a.language',
				'imgtext', 'a.imgtext',
				'access', 'a.access',
				'hidden', 'a.hidden',
				'featured', 'a.featured',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_time', 'a.modified_time',
				'modified_by', 'a.modified_by',
				'id', 'a.id',
				'metadesc', 'a.metadesc',
				'metakey', 'a.metakey',
				'robots', 'a.robots',
				'filename', 'a.filename',
				'imgdate', 'a.imgdate',
				'imgmetadata', 'a.imgmetadata',
				'params', 'a.params',
			);
		}

		parent::__construct($config);
	}

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   Elements order
	 * @param   string  $direction  Order direction
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('id', 'ASC');

		$context = $this->getUserStateFromRequest($this->context.'.filter.search', 'filter_search');
		$this->setState('filter.search', $context);

		// Split context into component and optional section
		$parts = FieldsHelper::extract($context);

		if($parts)
		{
			$this->setState('filter.component', $parts[0]);
			$this->setState('filter.section', $parts[1]);
		}
	}

	/**
	 * Method to get a store id based on model configuration state.
	 *
	 * This is necessary because the model is used by the component and
	 * different modules that might need different sets of data or different
	 * ordering requirements.
	 *
	 * @param   string  $id  A prefix for the store id.
	 *
	 * @return  string A store id.
	 *
	 * @since   4.0.0
	 */
	protected function getStoreId($id = '')
	{
		// Compile the store id.
		$id .= ':' . $this->getState('filter.search');
		$id .= ':' . $this->getState('filter.state');

		return parent::getStoreId($id);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @return  DatabaseQuery
	 *
	 * @since   4.0.0
	 */
	protected function getListQuery()
	{
		// Create a new query object.
		$db    = $this->getDbo();
		$query = $db->getQuery(true);

		// Select the required fields from the table.
		$query->select($this->getState('list.select', 'DISTINCT a.*'));
		$query->from('`#__joomgallery` AS a');

		// Join over the users for the checked out user
		$query->select("uc.name AS uEditor");
		$query->join("LEFT", "#__users AS uc ON uc.id=a.checked_out");
		// Join over the foreign key 'catid'
		$query->select('`#__joomgallery_categories_3681153`.`title` AS categories_fk_value_3681153');
		$query->join('LEFT', '#__joomgallery_categories AS #__joomgallery_categories_3681153 ON #__joomgallery_categories_3681153.`id` = a.`catid`');

		// Join over the access level field 'access'
		$query->select('`access`.title AS `access`');
		$query->join('LEFT', '#__viewlevels AS access ON `access`.id = a.`access`');

		// Join over the user field 'created_by'
		$query->select('`created_by`.name AS `created_by`');
		$query->join('LEFT', '#__users AS `created_by` ON `created_by`.id = a.`created_by`');

		// Join over the user field 'modified_by'
		$query->select('`modified_by`.name AS `modified_by`');
		$query->join('LEFT', '#__users AS `modified_by` ON `modified_by`.id = a.`modified_by`');


		// Filter by search in title
		$search = $this->getState('filter.search');

		if(!empty($search))
		{
			if(stripos($search, 'id:') === 0)
			{
				$query->where('a.id = ' . (int) substr($search, 3));
			}
			else
			{
				$search = $db->Quote('%' . $db->escape($search, true) . '%');
				$query->where('( a.imgtitle LIKE ' . $search . ' )');
			}
		}

		// Filtering access
		$filter_access = $this->state->get("filter.access");
		if($filter_access !== null && !empty($filter_access))
		{
			$query->where("a.`access` = '".$db->escape($filter_access)."'");
		}

		// Add the list ordering clause.
		$orderCol  = $this->state->get('list.ordering', 'id');
		$orderDirn = $this->state->get('list.direction', 'ASC');

		if($orderCol && $orderDirn)
		{
			$query->order($db->escape($orderCol . ' ' . $orderDirn));
		}

		return $query;
	}

	/**
	 * Get an array of data items
	 *
	 * @return mixed Array of data items on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach($items as $oneItem)
		{
			if(isset($oneItem->catid))
			{
				$values    = explode(',', $oneItem->catid);
				$textValue = array();

				foreach($values as $value)
				{
					$db    = Factory::getDbo();
					$query = $db->getQuery(true);
					$query
						->select('`#__joomgallery_categories_3681153`.`title`')
						->from($db->quoteName('#__joomgallery_categories', '#__joomgallery_categories_3681153'))
						->where($db->quoteName('#__joomgallery_categories_3681153.id') . ' = '. $db->quote($db->escape($value)));

					$db->setQuery($query);
					$results = $db->loadObject();

					if($results)
					{
						$textValue[] = $results->title;
					}
				}

				$oneItem->catid = !empty($textValue) ? implode(', ', $textValue) : $oneItem->catid;
			}
		}

		return $items;
	}
}
