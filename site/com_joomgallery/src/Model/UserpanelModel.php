<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Database\DatabaseInterface;

/**
 * Model to get a list of category records.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UserpanelModel extends ImagesModel
{
  /**
   * Method to autopopulate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @param   string   $ordering   Elements order
   * @param   string   $direction  Order direction
   *
   * @return  void
   *
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  protected function populateState($ordering = 'a.ordering', $direction = 'asc'): void
  {
    // List state information.
    parent::populateState($ordering, $direction);

    // Set filters based on how the view is used.
    //  e.g. user list of categories:
    $this->setState('filter.created_by', Factory::getApplication()->getIdentity()->id);
    $this->setState('filter.created_by.include', true);

    $this->loadComponentParams();
  }

  /**
   * Method to check if user owns at least one category. Without
   * only a matching request message will be displayed
   *
   * @param   int   $userId  ToDO: Id would suffice
   *
   * @return  bool true when user owns at least one category
   *
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  public function getUserHasACategory(int $userId): bool
  {
    $isUserHasACategory = true;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);    // ToDo: Count categories of user

      // Check number of records in tables
      $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(empty ($count))
      {
        $isUserHasACategory = false;
      }

    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('getUserHasACategory-Error: '.$e->getMessage(), 'error');

      return false;
    }

    return $isUserHasACategory;
  }

  public function assignUserData(array &$userData, int $userId): void
  {

    $userData['userCatCount'] = $this->dbUserCategoryCount ($userId); // COM_JOOMGALLERY_CONFIG_MAX_USERIMGS_LONG
    $userData['userImgCount'] = $this->dbUserImageCount ($userId);
    $userData['userImgTimeSpan'] = $this->dbUserImgTimeSpan ($userId);

  }

  private function dbUserCategoryCount(int $userId)
  {
    $categoryCount = 0;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);    // ToDo: Count categories of user

      // Check number of records in tables
      $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(!empty ($count))
      {
        $categoryCount = $count;
      }

    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('dbUserCategoryCount-Error: '.$e->getMessage(), 'error');

      return false;
    }

    return $categoryCount;
  }

  private function dbUserImageCount(int $userId)
  {
    $imageCount = 0;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);    // ToDo: Count categories of user

      // Check number of records in tables
      $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(!empty ($count))
      {
        $imageCount = $count;
      }

    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('dbUserImageCount-Error: '.$e->getMessage(), 'error');

      return false;
    }

    return $imageCount;
  }

  private function dbUserImgTimeSpan(int $userId)
  {
    $imageCount = 0;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);    // ToDo: Count categories of user

      // Check number of records in tables
      $query = $db->getQuery(true)
        ->select('COUNT(id)')
        ->from($db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');
      if($timespan > 0)
      {
        $query->where('created_time > (UTC_TIMESTAMP() - INTERVAL '. $timespan .' DAY)');
      }

      $db->setQuery($query);
      $count = $db->loadResult();

      if(!empty ($count))
      {
        $imageCount = $count;
      }

    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('dbUserImageCount-Error: '.$e->getMessage(), 'error');

      return false;
    }

    return $imageCount;
  }

  private function dbUserImgTimeMin(int $userId)
  {
    return 88;




  }

  private function dbUserImgTimeMax(int $userId)
  {
    return 88;

    // select * from users where order_date > now() - INTERVAL 15 day


  }


}
