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
 * Model to get a list of image records.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class UserimagesModel extends ImagesModel
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
//				'ordering', 'a.ordering',
//				'hits', 'a.hits',
//				'downloads', 'a.downloads',
//				'votes', 'a.votes',
//				'votesum', 'a.votesum',
//				'approved', 'a.approved',
//				'title', 'a.title',
//				'alias', 'a.alias',
//				'catid', 'a.catid',
//				'published', 'a.published',
//				'author', 'a.author',
//				'language', 'a.language',
//				'description', 'a.description',
//				'access', 'a.access',
//				'hidden', 'a.hidden',
//				'featured', 'a.featured',
//				'created_time', 'a.created_time',
//				'created_by', 'a.created_by',
//				'modified_time', 'a.modified_time',
//				'modified_by', 'a.modified_by',
//				'id', 'a.id',
//				'date', 'a.date'
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
	protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
	{
    // List state information.
		parent::populateState($ordering, $direction);

    // Set filters based on how the view is used.
    //  e.g. user list of categories:
    $this->setState('filter.created_by', Factory::getApplication()->getIdentity()->id);
    $this->setState('filter.created_by.include', true);

    $this->loadComponentParams();
	}

////	/**
////	 * Build an SQL query to load the list data.
////	 *
////	 * @return  DatabaseQuery
////	 *
////	 * @since   4.0.0
////	 */
////	protected function getListQuery()
////	{
////    $query = parent::getListQuery();
////
////    return $query;
////	}
//
////	/**
////	 * Method to get an array of data items
////	 *
////	 * @return  mixed An array of data on success, false on failure.
////	 */
////	public function getItems()
////	{
////		$items = parent::getItems();
////
////		return $items;
////	}


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
