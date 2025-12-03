<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Model;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;

/**
 * Model prepares latest categories, latest images and image list of user
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
   * @param   int   $userId
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
      $db = $this->getDatabase();

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(empty($count))
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

  /**
   * Collect from DB by user and assign into one object:
   *  - category count
   *  - image count
   *  - image count by config time span
   *
   * @param   array   $userData object result
   * @param   int     $userId Data only for this user
   *
   *
   * @since 4.3
   */
  public function assignUserData(array &$userData, int $userId): void
  {

    $userData['userCatCount']    = $this->dbUserCategoryCount($userId); // COM_JOOMGALLERY_CONFIG_MAX_USERIMGS_LONG
    $userData['userImgCount']    = $this->dbUserImageCount($userId);
    $userData['userImgTimeSpan'] = $this->dbUserImgTimeSpan($userId);

  }

  /**
   * Number of categories by user from DB
   *
   * @param   int   $userId  Data only for this user
   *
   * @return false|int Number of categories by user
   *
   * @throws \Exception
   * @since 4.3
   */
  private function dbUserCategoryCount(int $userId)
  {
    $categoryCount = 0;

    try
    {
      $db = $this->getDatabase();

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(!empty($count))
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

  /**
   * Number of images by user from DB
   *
   * @param   int   $userId  Data only for this user
   *
   * @return false|int
   *
   * @throws \Exception
   * @since 4.3
   */
  private function dbUserImageCount(int $userId)
  {
    $imageCount = 0;

    try
    {
      $db = $this->getDatabase();

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(!empty($count))
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

  /**
   * Number of images from DB by user and time span in config
   *
   * @param   int   $userId  Data only for this user
   *
   * @return false|int
   *
   * @throws \Exception
   * @since 4.3
   */
  private function dbUserImgTimeSpan(int $userId)
  {
    $imageCount = 0;

    try
    {
      $db = $this->getDatabase();

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('COUNT(id)')
        ->from($db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($db->quoteName('created_by').' = '.(int) $userId);

      $timespan = $this->component->getConfig()->get('jg_maxuserimage_timespan');

      if($timespan > 0)
      {
        $query->where('created_time > (UTC_TIMESTAMP() - INTERVAL '.$timespan.' DAY)');
      }

      $db->setQuery($query);
      $count = $db->loadResult();

      if(!empty($count))
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

  /**
   * Prepare data of latest categories for given user
   *
   * @param   int   $userId  Data only for this user
   * @param   int   $limit
   *
   * @return array|mixed list of category data (selection)
   *
   * @throws \Exception
   * @since 4.3
   */
  public function dbLatestUserCategories(int $userId, int $limit)
  {
    $categories = [];

    try
    {
      $db = $this->getDatabase();

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('*')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES, 'a'))
        ->where($db->quoteName('a.created_by').' = '.(int) $userId)
        ->order($db->quoteName('a.lft').' DESC')
        ->setLimit($limit);

      // assign parent category title
      $parentNameQuery = $db->createQuery()
        ->select('`parent`.title')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES, 'parent'))
        ->where($db->quoteName('a.parent_id') . ' = ' . $db->quoteName('parent.id'));
      $query->select('(' . $parentNameQuery->__toString() . ') AS ' . $db->quoteName('parent_title'));

      // Get image count
      $imgQuery = $db->createQuery()
        ->select('COUNT(`img`.id)')
        ->from($db->quoteName('#__joomgallery', 'img'))
        ->where($db->quoteName('img.catid') . ' = ' . $db->quoteName('a.id'));
      $query->select('(' . $imgQuery->__toString() . ') AS ' . $db->quoteName('img_count'));

      $db->setQuery($query);
      $dbCategories = $db->loadObjectList();

      if(!empty($dbCategories))
      {
        $categories = $dbCategories;
      }
    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('dbUserCategoryCount-Error: '.$e->getMessage(), 'error');

      return [];
    }

    return $categories;
  }

  /**
   * Prepare data of latest images for given user
   *
   * @param   int   $userId  Data only for this user
   *
   * @param   int   $limit
   *
   * @return array list of image data (selection)
   *
   * @throws \Exception
   * @since 4.3
   */
  public function dbLatestUserImages(int $userId, int $limit)
  {
    $images = [];

    try
    {
      $db = $this->getDatabase();

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('*')
        ->from($db->quoteName(_JOOM_TABLE_IMAGES, 'a'))
        ->where($db->quoteName('created_by').' = '.(int) $userId)
        ->order($db->quoteName('ordering').' DESC')
        ->setLimit($limit);

      // assign category title
      $parentNameQuery = $db->createQuery()
        ->select('`cat`.title')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES, 'cat'))
        ->where($db->quoteName('a.catid') . ' = ' . $db->quoteName('cat.id'));
      $query->select('(' . $parentNameQuery->__toString() . ') AS ' . $db->quoteName('cattitle'));

      $db->setQuery($query);
      $dbImages = $db->loadObjectList();

      if(!empty($dbImages))
      {
        $images = $dbImages;
      }

    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('dbUserImageCount-Error: '.$e->getMessage(), 'error');

      return [];
    }

    return $images;
  }
}
