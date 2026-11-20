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
use Joomla\CMS\HTML\HTMLHelper;
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

  /**
   * Include the owner filter in the list cache identity.
   *
   * @param   string  $id  Existing store ID fragment.
   *
   * @return  string  Cache store ID containing the owner filter.
   *
   * @since   DEPLOY_VERSION
   */
  protected function getStoreId($id = '')
  {
    // Append the selected owner so differently filtered lists cannot share data.
    return parent::getStoreId($id . ':' . (int) $this->getState('filter.userid'));
  }

  /**
   * Return gallery profiles and their linked Joomla identities for assignment.
   *
   * @return  array  Owner options suitable for a Joomla list field.
   *
   * @since   DEPLOY_VERSION
   */
  public function getOwnerOptions(): array
  {
    // Load every gallery profile with its linked Joomla user identity.
    $db      = $this->getDatabase();
    $query   = $db->getQuery(true)
      ->select(['g.id AS value', 'u.name', 'u.username'])
      ->from($db->quoteName(_JOOM_TABLE_USERS, 'g'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser')
      ->order('u.name ASC, g.id ASC');
    $options = [];

    // Convert user records into Joomla select options.
    foreach($db->setQuery($query)->loadObjectList() as $user)
    {
      $options[] = HTMLHelper::_('select.option', $user->value, $user->name . ' (' . $user->username . ')');
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
    // Register collection fields that may be filtered or sorted by the list model.
    if(empty($config['filter_fields']))
    {
      $config['filter_fields'] = ['id', 'a.id', 'title', 'a.title', 'published', 'a.published', 'owner', 'u.name', 'userid', 'a.userid', 'is_default', 'a.is_default', 'created_time', 'a.created_time'];
    }

    // Initialize the base list model with the completed configuration.
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
   *
   * @since   DEPLOY_VERSION
   */
  protected function populateState($ordering = 'a.id', $direction = 'ASC')
  {
    // Populate common pagination and ordering state first.
    parent::populateState($ordering, $direction);

    // Persist the search and owner filters across administrator requests.
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
    // Select collections with owner identity and mapped-image count.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select(['a.*', 'u.name AS owner', 'u.username'])
      ->select('(SELECT COUNT(*) FROM ' . $db->quoteName(_JOOM_TABLE_COLLECTIONS_REF, 'r') . ' WHERE r.collectionid = a.id) AS image_count')
      ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS, 'a'))
      ->join('INNER', $db->quoteName(_JOOM_TABLE_USERS, 'g') . ' ON g.id = a.userid')
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser');

    // Restrict the result to the selected gallery owner.
    if($owner = (int) $this->getState('filter.userid'))
    {
      $query->where('a.userid = ' . $owner);
    }

    // Match free-text searches against collection and owner fields.
    $search = trim((string) $this->getState('filter.search'));

    if($search !== '')
    {
      $search = $db->quote('%' . $db->escape($search, true) . '%');
      $query->where('(a.title LIKE ' . $search . ' OR u.name LIKE ' . $search . ' OR u.username LIKE ' . $search . ')');
    }

    // Apply the validated list ordering and direction.
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
    // Return no collections for an invalid profile ID.
    if($userId <= 0)
    {
      return [];
    }

    // Select all collection IDs owned by the profile.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('id')->from($db->quoteName(_JOOM_TABLE_COLLECTIONS))
      ->where('userid = ' . $userId);

    // Normalize database values to integer IDs.
    return array_map('intval', $db->setQuery($query)->loadColumn());
  }

  /**
   * Return collection titles belonging to one JoomGallery profile.
   *
   * @param   int  $userId  JoomGallery profile ID.
   *
   * @return  array  Collection titles ordered with the default collection first.
   *
   * @since   DEPLOY_VERSION
   */
  public function getTitlesByUser(int $userId): array
  {
    // Return no titles for an invalid profile ID.
    if($userId <= 0)
    {
      return [];
    }

    // Select titles with the profile's default collection first.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select($db->quoteName('title'))
      ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS))
      ->where($db->quoteName('userid') . ' = ' . $userId)
      ->order([$db->quoteName('is_default') . ' DESC', $db->quoteName('id') . ' ASC']);

    // Normalize database values to strings.
    return array_map('strval', $db->setQuery($query)->loadColumn());
  }

  /**
   * Return the collections of a Joomla user and their mapping state.
   *
   * @param   int  $cmsUserId  Joomla user ID owning the JoomGallery profile.
   * @param   int  $imageId    Image ID for which mapping state should be loaded.
   *
   * @return  array  Collection records ordered with the default collection first.
   *
   * @since   DEPLOY_VERSION
   */
  public function getSelectableByCmsUser(int $cmsUserId, int $imageId): array
  {
    // Require both a Joomla user and image before loading selectable collections.
    if($cmsUserId <= 0 || $imageId <= 0)
    {
      return [];
    }

    // Select the user's collections with mapping state and image count.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select([$db->quoteName('a.id'), $db->quoteName('a.title'), $db->quoteName('a.is_default')])
      ->select('CASE WHEN ' . $db->quoteName('ref.id') . ' IS NULL THEN 0 ELSE 1 END AS ' . $db->quoteName('mapped'))
    ->select(
        '(SELECT COUNT(*) FROM ' . $db->quoteName(_JOOM_TABLE_COLLECTIONS_REF, 'total_ref')
        . ' WHERE ' . $db->quoteName('total_ref.collectionid') . ' = ' . $db->quoteName('a.id') . ') AS ' . $db->quoteName('image_count')
    )
      ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS, 'a'))
      ->join('INNER', $db->quoteName(_JOOM_TABLE_USERS, 'g') . ' ON ' . $db->quoteName('g.id') . ' = ' . $db->quoteName('a.userid'))
    ->join(
        'LEFT',
        $db->quoteName(_JOOM_TABLE_COLLECTIONS_REF, 'ref')
          . ' ON ' . $db->quoteName('ref.collectionid') . ' = ' . $db->quoteName('a.id')
          . ' AND ' . $db->quoteName('ref.imgid') . ' = ' . (int) $imageId
    )
      ->where($db->quoteName('g.cmsuser') . ' = ' . (int) $cmsUserId)
      ->order([$db->quoteName('a.is_default') . ' DESC', $db->quoteName('a.ordering') . ' ASC', $db->quoteName('a.id') . ' ASC']);

    // Execute the query in default, ordering and ID order.
    return $db->setQuery($query)->loadObjectList();
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
    // Build the query joining collections to mappings for the selected image.
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
    // Execute the mapping query and convert database failures to model errors.
    try
    {
      return $this->_getList($this->getMappedListQuery((int) $img_id));
    }
    catch(\RuntimeException $e)
    {
      // Record the database error for both the caller and component log.
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
    // Normalize the input to unique, positive integer image IDs.
    $imageIds = ArrayHelper::toInteger($imageIds);
    $imageIds = array_values(array_unique(array_filter($imageIds)));

    // Avoid an invalid empty IN condition.
    if(!$imageIds)
    {
      return [];
    }

    // Select every mapped title for the requested images in stable order.
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
    // Execute the mapping query and convert database failures to model errors.
    try
    {
      $rows = $db->setQuery($query)->loadObjectList();
    }
    catch(\RuntimeException $e)
    {
      // Record the database error for both the caller and component log.
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return [];
    }

    // Initialize every requested image with an empty title list.
    $titles = array_fill_keys($imageIds, []);

    // Group each returned title under its image ID.
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
    // Normalize the submitted selection to unique collection IDs.
    $newCollections = ArrayHelper::toInteger((array) $new_collections);
    $newCollections = array_values(array_unique(array_filter($newCollections)));
    $mappedItems    = $this->getMappedItems((int) $img_id);

    // Stop when the existing mappings could not be loaded.
    if($mappedItems === false)
    {
      return false;
    }

    // Resolve the current selection and load the collection item model.
    $currentCollections = $this->idArray($mappedItems);
    $collectionModel    = Factory::getApplication()->bootComponent('com_joomgallery')
      ->getMVCFactory()->createModel('Collection', 'Administrator', ['ignore_request' => true]);
    $success            = true;

    // Add collection mappings that are present only in the new selection.
    foreach(array_diff($newCollections, $currentCollections) as $collectionId)
    {
      if(!$collectionModel->addMapping($collectionId, $img_id))
      {
        $this->setError($collectionModel->getError());
        $success = false;
      }
    }

    // Remove collection mappings that are absent from the new selection.
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
    // Extract and normalize each collection record's primary key.
    return array_map(static fn($item) => (int) $item->id, (array) $objectlist);
  }
}
