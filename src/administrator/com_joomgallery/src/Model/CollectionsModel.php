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
 * Model supporting lists of collections.
 *
 * @package  JoomGallery
 * @since    DEPLOY_VERSION
 */
class CollectionsModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'collection';

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
      $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'published', 'a.published', 'owner', 'u.name', 'created_time', 'a.created_time'];
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
  protected function populateState($ordering = 'a.id', $direction = 'ASC')
  {
    parent::populateState($ordering, $direction);
    $this->setState('filter.search', $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search'));
  }

  /**
   * Build an SQL query to load the list data.
   *
   * @return  DatabaseQuery
   *
   * @since   DEPLOY_VERSION
   */
  protected function getListQuery()
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select(['a.*', 'u.name AS owner', 'u.username'])
      ->select('(SELECT COUNT(*) FROM ' . $db->quoteName('#__joomgallery_collections_ref', 'r') . ' WHERE r.collectionid = a.id) AS image_count')
      ->from($db->quoteName('#__joomgallery_collections', 'a'))
      ->join('INNER', $db->quoteName('#__joomgallery_users', 'g') . ' ON g.id = a.userid')
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser');

    $search = trim((string) $this->getState('filter.search'));

    if($search !== '')
    {
      $search = $db->quote('%' . $db->escape($search, true) . '%');
      $query->where('(a.title LIKE ' . $search . ' OR u.name LIKE ' . $search . ' OR u.username LIKE ' . $search . ')');
    }

    $query->order($db->escape($this->state->get('list.ordering', 'a.id') . ' ' . $this->state->get('list.direction', 'ASC')));

    return $query;
  }

  /**
   * Return the IDs of all collections owned by a JoomGallery profile.
   *
   * @param   int  $userId  JoomGallery profile ID stored in the collection userid field.
   *
   * @return  array  Collection IDs owned by the profile.
   *
   * @since   DEPLOY_VERSION
   */
  public function getIdsByUser(int $userId): array
  {
    if($userId <= 0)
    {
      return [];
    }

    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('id')->from($db->quoteName('#__joomgallery_collections'))
      ->where('userid = ' . $userId);

    return array_map('intval', $db->setQuery($query)->loadColumn());
  }
}
