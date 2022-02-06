<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\Model\ListModel;
use \Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Layout\FileLayout;
use \Joomla\Database\ParameterType;
use \Joomla\Utilities\ArrayHelper;
use \Joomgallery\Component\Joomgallery\Site\Helper\JoomHelper;

/**
 * Methods supporting a list of Joomgallery records.
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
	 * @see    JController
	 * @since  4.0.0
	 */
	public function __construct($config = array())
	{
		if(empty($config['filter_fields']))
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
	 * @return  void
	 *
	 * @throws  Exception
	 *
	 * @since   4.0.0
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		// List state information.
		parent::populateState('a.id', 'ASC');

		$app = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$value = $app->input->get('limit', $app->get('list_limit', 0), 'uint');
		$this->setState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		$ordering  = $this->getUserStateFromRequest($this->context .'.filter_order', 'filter_order', 'a.id');
		$direction = strtoupper($this->getUserStateFromRequest($this->context .'.filter_order_Dir', 'filter_order_Dir', 'ASC'));

		if(!empty($ordering) || !empty($direction))
		{
			$list['fullordering'] = $ordering . ' ' . $direction;
		}

		$app->setUserState($this->context . '.list', $list);

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

		// Join over the users for the checked out user.
		$query->select('uc.name AS uEditor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
		// Join over the foreign key 'catid'
		$query->select('`#__joomgallery_categories_3681153`.`title` AS categories_fk_value_3681153');
		$query->join('LEFT', '#__joomgallery_categories AS #__joomgallery_categories_3681153 ON #__joomgallery_categories_3681153.`id` = a.`catid`');

		// Join over the created by field 'created_by'
		$query->join('LEFT', '#__users AS created_by ON created_by.id = a.created_by');

		// Join over the created by field 'modified_by'
		$query->join('LEFT', '#__users AS modified_by ON modified_by.id = a.modified_by');

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

		if($filter_access != '')
    {
			$query->where("a.access = '".$db->escape($filter_access)."'");
		}

    // Add the list ordering clause.
    $orderCol  = $this->state->get('list.ordering', 'a.id');
    $orderDirn = $this->state->get('list.direction', 'ASC');

    if($orderCol && $orderDirn)
    {
      $query->order($db->escape($orderCol . ' ' . $orderDirn));
    }

    return $query;
	}

	/**
	 * Method to get an array of data items
	 *
	 * @return  mixed An array of data on success, false on failure.
	 */
	public function getItems()
	{
		$items = parent::getItems();

		foreach($items as $item)
		{
			if(isset($item->catid))
			{
				$values    = explode(',', $item->catid);
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

				$item->catid = !empty($textValue) ? implode(', ', $textValue) : $item->catid;
			}

      if(!empty($item->robots))
      {
        $item->robots = Text::_('COM_JOOMGALLERY_IMAGES_ROBOTS_OPTION_' . strtoupper($item->robots));
      }
		}

		return $items;
	}

	/**
	 * Overrides the default function to check Date fields format, identified by
	 * "_dateformat" suffix, and erases the field if it's not correct.
	 *
	 * @return void
	 */
	protected function loadFormData()
	{
		$app              = Factory::getApplication();
		$filters          = $app->getUserState($this->context . '.filter', array());
		$error_dateformat = false;

		foreach($filters as $key => $value)
		{
			if(strpos($key, '_dateformat') && !empty($value) && $this->isValidDate($value) == null)
			{
				$filters[$key]    = '';
				$error_dateformat = true;
			}
		}

		if($error_dateformat)
		{
			$app->enqueueMessage(Text::_("COM_JOOMGALLERY_SEARCH_FILTER_DATE_FORMAT"), "warning");
			$app->setUserState($this->context . '.filter', $filters);
		}

		return parent::loadFormData();
	}

	/**
	 * Checks if a given date is valid and in a specified format (YYYY-MM-DD)
	 *
	 * @param   string  $date  Date to be checked
	 *
	 * @return bool
	 */
	private function isValidDate($date)
	{
		$date = str_replace('/', '-', $date);
    
		return (date_create($date)) ? Factory::getDate($date)->format("Y-m-d") : null;
	}
}
