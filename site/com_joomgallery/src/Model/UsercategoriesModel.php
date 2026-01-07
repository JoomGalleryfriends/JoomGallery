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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Model\CategoriesModel as AdminCategoriesModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\Mysqli\MysqliQuery;

/**
 * Model to get a list of category records.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UsercategoriesModel extends AdminCategoriesModel
{
  /**
   * Constructor
   *
   * @param   array   $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.2.0
   */
  function __construct($config = [])
  {
    if(empty($config['filter_fields']))
    {
      $config['filter_fields'] = [
        'lft', 'a.lft',
        'rgt', 'a.rgt',
        'level', 'a.level',
        'path', 'a.path',
        'in_hidden', 'a.in_hidden',
        'title', 'a.title',
        'alias', 'a.alias',
        'parent_id', 'a.parent_id',
        'parent_title', 'a.parent_title',
        'published', 'a.published',
        'access', 'a.access',
        'language', 'a.language',
        'description', 'a.description',
        'hidden', 'a.hidden',
        'created_time', 'a.created_time',
        'created_by', 'a.created_by',
        'modified_by', 'a.modified_by',
        'modified_time', 'a.modified_time',
        'id', 'a.id',
        'img_count', 'a.img_count',
        'child_count', 'a.child_count',
      ];
    }

    parent::__construct($config);
  }

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
  protected function populateState($ordering = 'a.lft', $direction = 'asc'): void
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
      $db = Factory::getContainer()->get(DatabaseInterface::class);

      // Check number of records in tables
      $query = $db->createQuery()
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by') . ' = ' . (int) $userId);

      $db->setQuery($query);
      $count = $db->loadResult();

      if(empty($count))
      {
        $isUserHasACategory = false;
      }
    }
    catch(\RuntimeException $e)
    {
      Factory::getApplication()->enqueueMessage('getUserHasACategory-Error: ' . $e->getMessage(), 'error');

      return false;
    }

    return $isUserHasACategory;
  }
}
