<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Model to get a list of category records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
//class UserpanelModel extends AdminCategoriesModel
// class UserpanelModel extends ImagesModel  orientieren an category model hat
// zwei listen die geladen werden
//class UserpanelModel extends JoomItemModel oder besser gallery model hat getimages
//
// soll !!! wenn imagelist und categories list
class UserpanelModel extends ImagesModel
{
//	/**
//   * Constructor
//   *
//   * @param   array  $config  An optional associative array of configuration settings.
//   *
//   * @return  void
//   * @since   4.0.0
//   */
//  function __construct($config = array())
//	{
//		if(empty($config['filter_fields']))
//		{
//			$config['filter_fields'] = array(
//				'lft', 'a.lft',
//				'rgt', 'a.rgt',
//				'level', 'a.level',
//				'path', 'a.path',
//				'in_hidden', 'a.in_hidden',
//				'title', 'a.title',
//				'alias', 'a.alias',
//				'parent_id', 'a.parent_id',
//				'parent_title', 'a.parent_title',
//				'published', 'a.published',
//				'access', 'a.access',
//				'language', 'a.language',
//				'description', 'a.description',
//				'hidden', 'a.hidden',
//				'created_time', 'a.created_time',
//				'created_by', 'a.created_by',
//				'modified_by', 'a.modified_by',
//				'modified_time', 'a.modified_time',
//				'id', 'a.id',
//				'img_count', 'a.img_count',
//				'child_count', 'a.child_count'
//			);
//		}
//
//		parent::__construct($config);
//	}
//
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
	 * @throws  \Exception
	 *
	 * @since   4.0.0
	 */
// see
//	protected function populateState($ordering = 'a.lft', $direction = 'DESC')
//	protected function populateState($ordering = 'a.id', $direction = 'desc')
	protected function populateState($ordering = 'a.ordering', $direction = 'asc')
	{
		// List state information.
		parent::populateState($ordering, $direction);

//    // tried to use list_limit in menu for first limit but failed
//    $params = $this->app->getParams();
//    $limit = $this->app->getParams()->get('list_limit');
//    $activeMenu = $this->app->getMenu()->getActive();
//    $limit = $activeMenu->getParams()->get('list_limit');
//    if( ! empty ($limit)) {
//      $this->setState('list.limit', $limit);
//    }

    // Set filters based on how the view is used.
		//  e.g. user list of categories:
    $this->setState('filter.created_by', Factory::getApplication()->getIdentity()->id);
    $this->setState('filter.created_by.include', true);

    $this->loadComponentParams();
	}

//
//	/**
//	 * Method to get an array of data items
//	 *
//	 * @return  mixed An array of data on success, false on failure.
//	 */
//	public function getItems()
//	{
//		$items = parent::getItems();
//
//		return $items;
//	}



  /**
   * Method to check if user owns at least one category. Without
   * only a matching request message will be displayed
   *
   * @param   \Joomla\CMS\User\User $user ToDO: Id would suffice
   *
   * @return  bool true wnhen user owns a
   *
   * @throws  \Exception
   *
   * @since   4.0.1
   */
  public function getUserHasACategory(\Joomla\CMS\User\User $user)
  {
    $isUserHasACategory = true;

    // try {

    $db = Factory::getContainer()->get(DatabaseInterface::class);		// ToDo: Count categories of user

    // Check number of records in tables
    $query = $db->getQuery(true)
      ->select('COUNT(*)')
      ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
      ->where($db->quoteName('created_by') . ' = ' . (int) $user->id);

    $db->setQuery($query);
    $count = $db->loadResult();

    if(empty ($count)) {
      $isUserHasACategory = false;
    }

    return $isUserHasACategory;
  }





}
