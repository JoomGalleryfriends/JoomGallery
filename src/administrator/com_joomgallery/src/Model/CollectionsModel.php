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

use Joomla\CMS\Factory;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

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

  /** Include the owner in the list cache identity. */
  protected function getStoreId($id = '')
  {
    return parent::getStoreId($id . ':' . (int) $this->getState('filter.userid'));
  }

  /** Gallery profile IDs and their linked Joomla identities for assignment. */
  public function getOwnerOptions(): array
  {
    $db      = $this->getDatabase();
    $query   = $db->getQuery(true)
      ->select(['g.id AS value', 'u.name', 'u.username'])
      ->from($db->quoteName('#__joomgallery_users', 'g'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser')
      ->order('u.name ASC, g.id ASC');
    $options = [];

    foreach($db->setQuery($query)->loadObjectList() as $user)
    {
      $options[] = \Joomla\CMS\HTML\HTMLHelper::_('select.option', $user->value, $user->name . ' (' . $user->username . ')');
    }

    return $options;
  }


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
      $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'published', 'a.published', 'owner', 'u.name', 'userid', 'a.userid', 'is_default', 'a.is_default', 'created_time', 'a.created_time'];
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
    $this->setState('filter.userid', $this->getUserStateFromRequest($this->context . '.filter.userid', 'filter_userid', '', 'int'));
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

    if($owner = (int) $this->getState('filter.userid'))
    {
      $query->where('a.userid = ' . $owner);
    }

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

  /**
   * Build the query for collections mapped to an image.
   *
   * @param   int  $img_id  Image ID.
   *
   * @return  \Joomla\Database\DatabaseQuery  Mapping query.
   *
   * @since   DEPLOY_VERSION
   */
  protected function getMappedListQuery($img_id)
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select('a.*')
      ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS, 'a'))
    ->join(
        'INNER',
        $db->quoteName(_JOOM_TABLE_COLLECTIONS_REF, 'ref')
        . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('ref.collectionid')
    )
      ->where($db->quoteName('ref.imgid') . ' = ' . (int) $img_id)
      ->order('a.id ASC');

    return $query;
  }

  /**
   * Return collections mapped to an image.
   *
   * @param   int  $img_id  Image ID.
   *
   * @return  array|false  Mapped collection records, or false on failure.
   *
   * @since   DEPLOY_VERSION
   */
  public function getMappedItems($img_id)
  {
    try
    {
      return $this->_getList($this->getMappedListQuery((int) $img_id));
    }
    catch(\RuntimeException $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }
  }

  /**
   * Return mapped collection titles indexed by image ID.
   *
   * @param   array  $imageIds  Image IDs.
   *
   * @return  array  Associative array containing a title list for each image ID.
   *
   * @since   DEPLOY_VERSION
   */
  public function getMappedTitlesByImageIds(array $imageIds): array
  {
    $imageIds = ArrayHelper::toInteger($imageIds);
    $imageIds = array_values(array_unique(array_filter($imageIds)));

    if(!$imageIds)
    {
      return [];
    }

    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select([$db->quoteName('ref.imgid'), $db->quoteName('a.title')])
      ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS, 'a'))
    ->join(
        'INNER',
        $db->quoteName(_JOOM_TABLE_COLLECTIONS_REF, 'ref')
        . ' ON ' . $db->quoteName('ref.collectionid') . ' = ' . $db->quoteName('a.id')
    )
      ->whereIn($db->quoteName('ref.imgid'), $imageIds, ParameterType::INTEGER)
      ->order([$db->quoteName('ref.imgid') . ' ASC', $db->quoteName('a.title') . ' ASC']);
    try
    {
      $rows = $db->setQuery($query)->loadObjectList();
    }
    catch(\RuntimeException $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return [];
    }

    $titles = array_fill_keys($imageIds, []);

    foreach($rows as $row)
    {
      $titles[(int) $row->imgid][] = $row->title;
    }

    return $titles;
  }

  /**
   * Replace all collection mappings for an image with the supplied selection.
   *
   * @param   array  $new_collections  Collection IDs that should be mapped.
   * @param   int    $img_id           Image ID.
   *
   * @return  boolean  True when every mapping operation succeeds.
   *
   * @since   DEPLOY_VERSION
   */
  public function updateMapping($new_collections, $img_id)
  {
    $newCollections = ArrayHelper::toInteger((array) $new_collections);
    $newCollections = array_values(array_unique(array_filter($newCollections)));
    $mappedItems    = $this->getMappedItems((int) $img_id);

    if($mappedItems === false)
    {
      return false;
    }

    $currentCollections = $this->idArray($mappedItems);
    $collectionModel    = Factory::getApplication()->bootComponent('com_joomgallery')
      ->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);
    $success            = true;

    foreach(array_diff($newCollections, $currentCollections) as $collectionId)
    {
      if(!$collectionModel->addMapping($collectionId, $img_id))
      {
        $this->setError($collectionModel->getError());
        $success = false;
      }
    }

    foreach(array_diff($currentCollections, $newCollections) as $collectionId)
    {
      if(!$collectionModel->removeMapping($collectionId, $img_id))
      {
        $this->setError($collectionModel->getError());
        $success = false;
      }
    }

    return $success;
  }

  /**
   * Convert collection records to collection IDs.
   *
   * @param   array  $objectlist  Collection records.
   *
   * @return  array  Collection IDs.
   *
   * @since   DEPLOY_VERSION
   */
  protected function idArray($objectlist): array
  {
    return array_map(static fn($item) => (int) $item->id, (array) $objectlist);
  }
}
