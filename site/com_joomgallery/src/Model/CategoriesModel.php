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
class CategoriesModel extends ListModel
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
				'lft', 'a.lft',
				'rgt', 'a.rgt',
				'level', 'a.level',
				'path', 'a.path',
				'in_hidden', 'a.in_hidden',
				'title', 'a.title',
				'alias', 'a.alias',
				'parent_id', 'a.parent_id',
				'published', 'a.published',
				'access', 'a.access',
				'password', 'a.password',
				'language', 'a.language',
				'description', 'a.description',
				'hidden', 'a.hidden',
				'exclude_toplist', 'a.exclude_toplist',
				'exclude_search', 'a.exclude_search',
				'thumbnail', 'a.thumbnail',
				'created_time', 'a.created_time',
				'created_by', 'a.created_by',
				'modified_by', 'a.modified_by',
				'modified_time', 'a.modified_time',
				'id', 'a.id',
				'metadesc', 'a.metadesc',
				'metakey', 'a.metakey',
				'robots', 'a.robots',
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
		parent::populateState("a.lft", "ASC");

		$app = Factory::getApplication();
		$list = $app->getUserState($this->context . '.list');

		$value = $app->input->get('limit', $app->get('list_limit', 0), 'uint');
		$this->setState('list.limit', $value);

		$value = $app->input->get('limitstart', 0, 'uint');
		$this->setState('list.start', $value);

		$ordering  = $this->getUserStateFromRequest($this->context .'.filter_order', 'filter_order', "a.lft");
		$direction = strtoupper($this->getUserStateFromRequest($this->context .'.filter_order_Dir', 'filter_order_Dir', "ASC"));

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

    $query->from('`#__joomgallery_categories` AS a');

		// Join over the users for the checked out user.
		$query->select('uc.name AS uEditor');
		$query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
		$query->where("a.level <> 0");

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
      }
    }

    // Add the list ordering clause.
    $orderCol  = $this->state->get('list.ordering', "a.lft");
    $orderDirn = $this->state->get('list.direction', "ASC");

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
      $item->hidden = empty($item->hidden) ? '' : Text::_('COM_JOOMGALLERY_CATEGORIES_HIDDEN_OPTION_' . strtoupper($item->hidden));
      $item->exclude_toplist = empty($item->exclude_toplist) ? '' : Text::_('COM_JOOMGALLERY_CATEGORIES_EXCLUDE_TOPLIST_OPTION_' . strtoupper($item->exclude_toplist));
      $item->exclude_search = empty($item->exclude_search) ? '' : Text::_('COM_JOOMGALLERY_CATEGORIES_EXCLUDE_SEARCH_OPTION_' . strtoupper($item->exclude_search));

      if(!empty($item->robots))
        {
          $item->robots = Text::_('COM_JOOMGALLERY_CATEGORIES_ROBOTS_OPTION_' . strtoupper($item->robots));
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
