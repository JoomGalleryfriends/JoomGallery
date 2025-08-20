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

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

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
   * @param   \Joomla\CMS\User\User   $user  ToDO: Id would suffice
   *
   * @return  bool true when user owns at least one category
   *
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  public function getUserHasACategory(\Joomla\CMS\User\User $user): bool
  {
    $isUserHasACategory = true;

    try
    {
      $db = Factory::getContainer()->get(DatabaseInterface::class);    // ToDo: Count categories of user

      // Check number of records in tables
      $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
        ->where($db->quoteName('created_by').' = '.(int) $user->id);

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

}
