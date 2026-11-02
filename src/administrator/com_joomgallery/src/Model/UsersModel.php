<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Model supporting lists and lifecycle selection of gallery profiles.
 *
 * @package  JoomGallery
 * @since    DEPLOY_VERSION
 */
class UsersModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'user';

  /**
   * Constructor
   *
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   DEPLOY_VERSION
   */
  public function __construct($config = [])
  {
    if(empty($config['filter_fields']))
    {
      $config['filter_fields'] = ['id', 'a.id', 'cmsuser', 'a.cmsuser', 'name', 'u.name', 'username', 'u.username', 'created_time', 'a.created_time'];
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
  protected function populateState($ordering = 'u.name', $direction = 'ASC')
  {
    parent::populateState($ordering, $direction);
    $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
  }

  /**
   * Return Joomla user IDs that satisfy the creation policy and have no gallery profile.
   *
   * @return  DatabaseQuery
   *
   * @since   DEPLOY_VERSION
   */
  protected function getListQuery()
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select(['a.*', 'u.name', 'u.username', 'u.email'])
      ->select('(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomgallery_collections', 'c') . ' WHERE c.userid = a.id) AS collection_count')
      ->from($db->quoteName('#__joomgallery_users', 'a'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = a.cmsuser');

    $search = trim((string) $this->getState('filter.search'));

    if($search !== '')
    {
      if(str_starts_with($search, 'id:'))
      {
        $query->where('a.id = ' . (int) substr($search, 3));
      }
      else
      {
        $search = $db->quote('%' . $db->escape($search, true) . '%');
        $query->where('(u.name LIKE ' . $search . ' OR u.username LIKE ' . $search . ' OR u.email LIKE ' . $search . ')');
      }
    }

    $orderCol  = $this->state->get('list.ordering', 'u.name');
    $orderDirn = $this->state->get('list.direction', 'ASC');
    $query->order($db->escape($orderCol . ' ' . $orderDirn));

    return $query;
  }

  /**
   * Build an SQL query to load the list data.
   *
   * @param   string    $policy   Profile creation policy: all, groups or users.
   * @param   array     $groups   Joomla user-group IDs accepted by the groups policy.
   * @param   array     $ranges   Inclusive Joomla user-ID ranges accepted by the users policy.
   * @param   int|null  $cmsuser  Optional Joomla user ID used to limit reconciliation to one account.
   *
   * @return  array  Joomla user IDs requiring a JoomGallery profile.
   *
   * @since   DEPLOY_VERSION
   */
  public function getMissingCmsUserIds(string $policy, array $groups, array $ranges, ?int $cmsuser = null): array
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('u.id')->from($db->quoteName('#__users', 'u'))
      ->where('u.id > 0')
      ->where('NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__joomgallery_users') . ' AS g WHERE g.cmsuser = u.id)');

    if($cmsuser !== null)
    {
      $query->where('u.id = ' . (int) $cmsuser);
    }

    if($policy === 'groups')
    {
      $groups = array_values(array_filter(array_map('intval', $groups), static fn($id) => $id > 0));

      if(!$groups)
      {
        return [];
      }

    $query->where(
        'EXISTS (SELECT 1 FROM ' . $db->quoteName('#__user_usergroup_map')
        . ' AS m WHERE m.user_id = u.id AND m.group_id IN (' . implode(',', $groups) . '))'
    );
    }
    elseif($policy === 'users')
    {
      if(!$ranges)
      {
        return [];
      }

      $conditions = [];

      foreach($ranges as [$start, $end])
      {
        $conditions[] = 'u.id BETWEEN ' . (int) $start . ' AND ' . (int) $end;
      }

      $query->where('(' . implode(' OR ', $conditions) . ')');
    }
    elseif($policy !== 'all')
    {
      throw new \InvalidArgumentException('Invalid Create User policy.');
    }

    return array_map('intval', $db->setQuery($query)->loadColumn());
  }
}
