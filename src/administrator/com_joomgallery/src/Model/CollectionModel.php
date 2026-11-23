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
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Asset;
use Joomla\Database\ParameterType;

/**
 * Model for one collection.
 *
 * @package  JoomGallery
 * @since    DEPLOY_VERSION
 */
class CollectionModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'collection';

  /**
   * Return the current Joomla identity's JoomGallery profile ID.
   *
   * @return  int  Profile ID, or zero when no linked profile exists.
   *
   * @since   DEPLOY_VERSION
   */
  protected function getCurrentProfileId(): int
  {
    // Load the identity associated with the current request.
    $identity = Factory::getApplication()->getIdentity();

    // Guests and invalid identities cannot have a linked gallery profile.
    if((int) $identity->id <= 0)
    {
      return 0;
    }

    // Resolve the gallery profile through its Joomla user ID.
    $profile = $this->component->getMVCFactory()->createTable('User', 'Administrator');

    return $profile->load(['cmsuser' => (int) $identity->id]) ? (int) $profile->id : 0;
  }

  /**
   * Check whether the current Joomla identity owns a collection.
   *
   * @param   object  $collection  Collection record containing a profile ID.
   *
   * @return  boolean  True when the current profile owns the collection.
   *
   * @since   DEPLOY_VERSION
   */
  protected function isOwner(object $collection): bool
  {
    // Resolve the current profile before comparing collection ownership.
    $profileId = $this->getCurrentProfileId();

    return $profileId > 0 && $profileId === (int) $collection->userid;
  }

  /**
   * Check only the global ACL result, excluding own-item rule fallbacks.
   *
   * @param   string  $action  JoomGallery action without its core or joom prefix.
   *
   * @return  boolean  True when the global ACL grants the action.
   *
   * @since   DEPLOY_VERSION
   */
  protected function hasGlobalPermission(string $action): bool
  {
    // Ask the JoomGallery access service for the collection-level ACL result.
    $acl = $this->getAcl();
    $acl->checkACL($action, 'collection');

    return (bool) $acl->allowed['default'];
  }

  /**
   * Check whether a collection may be created for the requested owner.
   *
   * @param   int  $requestedOwnerId  Requested JoomGallery profile ID.
   *
   * @return  boolean  True when creation is permitted.
   *
   * @since   DEPLOY_VERSION
   */
  public function canCreateCollection(int $requestedOwnerId = 0): bool
  {
    // Global create permission allows assigning a collection to any valid owner.
    if($this->hasGlobalPermission('add'))
    {
      return true;
    }

    // Without global permission, users may create collections only for themselves.
    $profileId = $this->getCurrentProfileId();

    return $profileId > 0 && ($requestedOwnerId === 0 || $requestedOwnerId === $profileId);
  }

  /**
   * Resolve the submitted owner according to creation and ownership rules.
   *
   * @param   int  $requestedOwnerId  Requested JoomGallery profile ID.
   *
   * @return  int  Authorized owner profile ID.
   *
   * @throws  \RuntimeException  When the owner is invalid or unauthorized.
   *
   * @since   DEPLOY_VERSION
   */
  protected function resolveCreationOwner(int $requestedOwnerId): int
  {
    // Global creators may use the submitted owner after validating the profile.
    if($this->hasGlobalPermission('add'))
    {
      $this->assertOwner($requestedOwnerId);

      return $requestedOwnerId;
    }

    // Other users must have a linked profile and may select only that profile.
    $profileId = $this->getCurrentProfileId();

    if($profileId <= 0 || ($requestedOwnerId > 0 && $requestedOwnerId !== $profileId))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    return $profileId;
  }

  /**
   * Return permissions shared by the list UI and management endpoint.
   *
   * @param   object  $collection  Collection record to authorize.
   *
   * @return  array  Permission flags indexed by operation name.
   *
   * @since   DEPLOY_VERSION
   */
  public function getManagementPermissions(object $collection): array
  {
    // Owners receive own-item permissions before global ACL rules are considered.
    $owner       = $this->isOwner($collection);
    $permissions = ['owner' => $owner];

    // Evaluate the ACL action and default-collection restriction for each operation.
    foreach(
        [
          'reassign' => 'core.edit',
          'state'    => 'core.edit.state',
          'clear'    => 'joom.connect',
          'delete'   => 'core.delete',
        ] as $operation => $action
    )
    {
      $allowed = $owner;

      if(!$allowed)
      {
        $allowed = $this->hasGlobalPermission($action);
      }

      $defaultSafe             = \in_array($operation, ['state', 'clear'], true) || !(int) $collection->is_default;
      $permissions[$operation] = $allowed && $defaultSafe;
    }

    return $permissions;
  }

  /**
   * Load the creation-only form without loading an existing collection.
   *
   * @return  Form|boolean  Form instance on success, false on failure.
   *
   * @since   DEPLOY_VERSION
   */
  public function getCreationForm()
  {
    // Load a form that does not bind an existing collection record.
    $form = $this->loadForm('com_joomgallery.collectioncreate', 'collectioncreate', ['control' => 'jform', 'load_data' => false]);

    // Restore submitted data and restrict the owner field for non-global creators.
    if($form)
    {
      $data = Factory::getApplication()->getUserState('com_joomgallery.collection.create.data', []);

      if(!$this->hasGlobalPermission('add'))
      {
        $data['userid'] = $this->getCurrentProfileId();
        $form->setFieldAttribute('userid', 'readonly', 'true');
      }

      $form->bind($data);
    }

    return $form;
  }

  /**
   * Require a gallery profile linked to an existing Joomla account.
   *
   * @param   int  $id  JoomGallery profile ID.
   *
   * @return  void
   *
   * @throws  \RuntimeException  When the profile or linked Joomla user is missing.
   *
   * @since   DEPLOY_VERSION
   */
  protected function assertOwner(int $id): void
  {
    // Build a query that accepts only profiles linked to existing Joomla users.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('g.id')->from($db->quoteName(_JOOM_TABLE_USERS, 'g'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser')
      ->where('g.id = ' . $id);

    // Reject missing, invalid or orphaned profile records.
    if($id <= 0 || !$db->setQuery($query)->loadResult())
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_OWNER'));
    }
  }

  /**
   * Create a normal collection with server-controlled lifecycle fields.
   *
   * @param   array  $data  Validated collection form data.
   *
   * @return  boolean  True when the collection is stored successfully.
   *
   * @throws  \RuntimeException  When an existing record is submitted or ownership is unauthorized.
   *
   * @since   DEPLOY_VERSION
   */
  public function createCollection(array $data): bool
  {
    // Prevent this creation-only path from updating an existing collection.
    if(!empty($data['id']))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    // Keep permitted form fields and populate server-controlled record values.
    $ownerId               = $this->resolveCreationOwner((int) ($data['userid'] ?? 0));
    $data                  = array_intersect_key($data, array_flip(['userid', 'title', 'alias', 'description', 'access', 'published']));
    $data['id']            = 0;
    $data['userid']        = $ownerId;
    $data['is_default']    = 0;
    $data['created_by']    = (int) Factory::getApplication()->getIdentity()->id;
    $data['modified_by']   = $data['created_by'];
    $data['modified_time'] = Factory::getDate()->toSql();

    // Publish collections for another owner only with global state permission.
    if($ownerId !== $this->getCurrentProfileId() && !$this->hasGlobalPermission('editstate'))
    {
      $data['published'] = 0;
    }

    // Force the parent model to insert a new collection record.
    $this->setState('collection.id', 0);

    return parent::save($data);
  }

  /**
   * Check whether the current user may edit a collection state.
   *
   * @param   object  $record  Collection record to authorize.
   *
   * @return  boolean  True when state changes are permitted.
   *
   * @since   DEPLOY_VERSION
   */
  protected function canEditState($record)
  {
    // Load the stored collection before evaluating own-item permission.
    $table = $this->getTable();

    // Collection owners may always change their collection state.
    if((int) ($record->id ?? 0) > 0 && $table->load((int) $record->id) && $this->isOwner($table))
    {
      return true;
    }

    // Non-owners require the global state permission.
    return $this->hasGlobalPermission('editstate');
  }

  /**
   * Apply one authorized modal operation to the current database record.
   *
   * @param   int     $id         Collection ID.
   * @param   string  $operation  Operation name submitted by the modal.
   * @param   int     $ownerId    New owner profile ID for reassignment.
   *
   * @return  void
   *
   * @throws  \RuntimeException  When the collection, permission or operation is invalid.
   *
   * @since   DEPLOY_VERSION
   */
  public function manageCollection(int $id, string $operation, int $ownerId = 0): void
  {
    // Start a transaction so the selected management operation is atomic.
    $db = $this->getDatabase();
    $db->transactionStart(true);

    try
    {
      // Load the current record from the database.
      $table = $this->getTable();

      if($id <= 0 || !$table->load($id))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'), 404);
      }

      // Resolve the modal operation to its permission flag.
      $permissions  = $this->getManagementPermissions($table);
      $permissionId = \in_array($operation, ['publish', 'unpublish'], true) ? 'state' : $operation;

      // Reject operations that are unavailable for this user or collection.
      if(empty($permissions[$permissionId]))
      {
        throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
      }

      // Execute the requested delete, clear, state or reassignment operation.
      if($operation === 'delete')
      {
        $this->deleteCollection($id);
      }
      elseif($operation === 'clear')
      {
        if(!$this->removeMapping($id))
        {
          throw new \RuntimeException($this->getError());
        }
      }
      elseif(\in_array($operation, ['publish', 'unpublish'], true))
      {
        $table->published     = $operation === 'publish' ? 1 : 0;
        $table->modified_by   = (int) Factory::getApplication()->getIdentity()->id;
        $table->modified_time = Factory::getDate()->toSql();

        if(!$table->store())
        {
          throw new \RuntimeException($table->getError());
        }
      }
      else
      {
        $this->assertOwner($ownerId);
        $table->userid        = $ownerId;
        $table->modified_by   = (int) Factory::getApplication()->getIdentity()->id;
        $table->modified_time = Factory::getDate()->toSql();

        // Recheck alias uniqueness within the new owner's collections.
        if(!$table->check() || !$table->store())
        {
          throw new \RuntimeException($table->getError());
        }
      }

      // Commit the completed management operation.
      $db->transactionCommit(true);
    }
    catch(\Throwable $e)
    {
      // Roll back every change when any step fails.
      $db->transactionRollback(true);
      throw $e;
    }

    // Invalidate cached collection data after a successful operation.
    $this->cleanCache();
  }


  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  mixed  The data for the form.
   *
   * @since   DEPLOY_VERSION
   */
  protected function loadFormData()
  {
    // Supply the currently loaded collection to Joomla's form loader.
    return $this->getItem();
  }

  /**
   * Route controller bulk deletion through collection-specific cleanup.
   *
   * @param   array  &$pks  An array of record primary keys.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   DEPLOY_VERSION
   */
  public function delete(&$pks)
  {
    // Route every ID through permission checks and collection-specific cleanup.
    foreach((array) $pks as $id)
    {
      $this->manageCollection((int) $id, 'delete');
    }

    // Report success after all requested collections have been deleted.
    return true;
  }

  /**
   * Delete a collection together with its image mappings and individual asset.
   *
   * @param   int   $id            Collection ID.
   * @param   bool  $allowDefault  Permit lifecycle deletion of the default collection.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function deleteCollection(int $id, bool $allowDefault = false): void
  {
    // Ignore empty record IDs supplied by lifecycle cleanup.
    if($id <= 0)
    {
      return;
    }

    // Load the collection before deleting dependent records.
    $table = $this->getTable();

    if(!$table->load($id))
    {
      return;
    }

    // Protect default collections outside profile lifecycle deletion.
    if((int) $table->is_default === 1 && !$allowDefault)
    {
      throw new \RuntimeException('The default collection can only be deleted with its JoomGallery user profile.');
    }

    // Delete every image mapping before removing the collection itself.
    if(!$table->removeMapping())
    {
      throw new \RuntimeException($table->getError() ?: 'Could not delete collection mappings.');
    }

    // Preserve the asset ID and tell the table whether default deletion is allowed.
    $assetId                   = (int) $table->asset_id;
    $table->allowDefaultDelete = $allowDefault;

    // Delete the collection record through its table implementation.
    if(!$table->delete($id))
    {
      throw new \RuntimeException($table->getError() ?: 'Could not delete the collection.');
    }

    // Remove the collection-specific Joomla asset when one exists.
    if($assetId > 0)
    {
      $asset = new Asset($this->getDatabase());

      if($asset->load($assetId) && $asset->name === 'com_joomgallery.collection.' . $id && !$asset->delete())
      {
        throw new \RuntimeException('Could not delete the collection asset.');
      }
    }
  }

  /**
   * Ensure a JoomGallery profile owns exactly one designated default collection.
   *
   * A likely migrated Favourites collection is designated before a new collection
   * is created, preventing lifecycle reconciliation from creating duplicates.
   *
   * @param   int  $userId   JoomGallery profile ID.
   * @param   int  $cmsuser  Joomla user ID used as creator and modifier.
   *
   * @return  int  ID of the existing or newly created default collection.
   *
   * @since   DEPLOY_VERSION
   */
  public function ensureDefaultCollection(int $userId, int $cmsuser): int
  {
    // Require valid gallery and Joomla identities before starting reconciliation.
    if($userId <= 0 || $cmsuser <= 0)
    {
      throw new \InvalidArgumentException('Valid JoomGallery and Joomla user IDs are required.');
    }

    // Reconcile the default collection in one transaction.
    $db = $this->getDatabase();
    $db->transactionStart(true);

    try
    {
      // Lock the profile row, which exists even when no collection row can be locked yet.
      // This serializes concurrent default-collection creation for the same profile.
      $query          = $db->getQuery(true)->select('cmsuser')
        ->from($db->quoteName(_JOOM_TABLE_USERS))
        ->where('id = ' . $userId);
      $profileCmsUser = (int) $db->setQuery($query . ' FOR UPDATE')->loadResult();

      if($profileCmsUser <= 0 || $profileCmsUser !== $cmsuser)
      {
        throw new \RuntimeException('The JoomGallery user profile does not match the Joomla user.');
      }

      $query      = $db->getQuery(true)->select('id')
        ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS))
        ->where('userid = ' . $userId)
        ->where('is_default = 1')
        ->order('id ASC');
      $defaultIds = array_map('intval', $db->setQuery($query)->loadColumn());

      // Return the single existing default collection when it is already valid.
      if($defaultIds)
      {
        if(\count($defaultIds) > 1)
        {
          throw new \RuntimeException('A JoomGallery user profile has more than one default collection.');
        }

        $db->transactionCommit(true);

        return $defaultIds[0];
      }

      // Look for a migrated Favourites collection that can become the default.
      $title                      = Text::_('COM_JOOMGALLERY_FAVOURITES');
      $query                      = $db->getQuery(true)->select('id')
        ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS))
        ->where('userid = ' . $userId)
        ->where('title = ' . $db->quote($title))
        ->order('id ASC');
      $collectionId               = (int) $db->setQuery($query, 0, 1)->loadResult();
      $table                      = $this->getTable();
      $table->allowDefaultChanges = true;

      // Designate the migrated collection when one was found.
      if($collectionId > 0)
      {
        if(!$table->load($collectionId))
        {
          throw new \RuntimeException($table->getError() ?: 'Could not load the migrated Favourites collection.');
        }

        $table->is_default = 1;

        if(!$table->store())
        {
          throw new \RuntimeException($table->getError() ?: 'Could not designate the default collection.');
        }

        $db->transactionCommit(true);

        return $collectionId;
      }

      // Build a new default collection when no reusable record exists.
      $date = Factory::getDate()->toSql();
      $data = [
        'id'            => 0,
        'userid'        => $userId,
        'title'         => $title,
        'alias'         => '',
        'description'   => '',
        'is_default'    => 1,
        'access'        => 1,
        'published'     => 1,
        'hidden'        => 0,
        'created_by'    => $cmsuser,
        'modified_by'   => $cmsuser,
        'modified_time' => $date,
      ];

      // Validate and store the new default collection record.
      if(!$table->bind($data) || !$table->check() || !$table->store())
      {
        throw new \RuntimeException($table->getError() ?: 'Could not create the default collection.');
      }

      // Commit the newly created collection and return its ID.
      $collectionId = (int) $table->id;
      $db->transactionCommit(true);

      return $collectionId;
    }
    catch(\Throwable $e)
    {
      // Restore the previous state when reconciliation fails.
      $db->transactionRollback(true);
      throw $e;
    }
  }

  /**
   * Add a mapping between a collection and an image.
   *
   * @param   int        $collection_id  Collection ID.
   * @param   int|array  $img_id         Image ID or list of image IDs.
   * @param   bool       $approve        True to approve the mappings.
   *
   * @return  boolean  True when the mapping exists after the operation.
   *
   * @since   DEPLOY_VERSION
   */
  public function addMapping($collection_id, $img_id, bool $approve = false)
  {
    // Normalize and validate the collection and image identifiers.
    $collection_id = (int) $collection_id;

    if($collection_id <= 0 || (\is_array($img_id) ? empty($img_id) : (int) $img_id <= 0))
    {
      return false;
    }

    // Require ownership or global connection permission.
    if(!$this->canConnectCollection($collection_id))
    {
      $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

      return false;
    }

    // Load the destination collection table.
    $table = $this->getTable();

    if(!$table->load($collection_id))
    {
      $this->setError(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'));

      return false;
    }

    // Store the requested image mappings through the table API.
    if(!$table->addMapping($img_id, $approve))
    {
      $this->setError($table->getError());

      return false;
    }

    // Report that all requested mappings now exist.
    return true;
  }

  /**
   * Remove one image mapping, or every image mapping for a collection.
   *
   * @param   int          $collection_id  Collection ID.
   * @param   int|array    $img_id         Image ID, list of IDs, or zero to remove all mappings.
   *
   * @return  boolean  True on success.
   *
   * @since   DEPLOY_VERSION
   */
  public function removeMapping($collection_id, $img_id = 0)
  {
    // Normalize and validate the collection identifier.
    $collection_id = (int) $collection_id;

    if($collection_id <= 0)
    {
      return false;
    }

    // Require ownership or global connection permission.
    if(!$this->canConnectCollection($collection_id))
    {
      $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

      return false;
    }

    // Load the collection that owns the mappings.
    $table = $this->getTable();

    if(!$table->load($collection_id))
    {
      $this->setError(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'));

      return false;
    }

    // Remove one, several or all mappings through the table API.
    if(!$table->removeMapping($img_id))
    {
      $this->setError($table->getError());

      return false;
    }

    // Report successful removal.
    return true;
  }

  /**
   * Check whether images may be connected to a collection.
   *
   * Owners may always connect images; other users require global joom.connect.
   *
   * @param   int  $collectionId  Collection ID.
   *
   * @return  boolean  True when mappings may be changed.
   *
   * @since   DEPLOY_VERSION
   */
  public function canConnectCollection(int $collectionId): bool
  {
    // Reject empty collection IDs.
    if($collectionId <= 0)
    {
      return false;
    }

    // Load the collection before checking owner and global permissions.
    $table = $this->getTable();

    if(!$table->load($collectionId))
    {
      return false;
    }

    // Grant connection access to the owner or a globally authorized user.
    return $this->isOwner($table) || $this->hasGlobalPermission('connect');
  }

  /**
   * Check whether a collection belongs to the current Joomla user's profile.
   *
   * @param   int  $collectionId  Collection ID.
   *
   * @return  boolean  True when the current user owns the collection.
   *
   * @since   DEPLOY_VERSION
   */
  public function ownsCollection(int $collectionId): bool
  {
    // Reject empty collection IDs.
    if($collectionId <= 0)
    {
      return false;
    }

    // Load the collection and compare it with the current profile.
    $table = $this->getTable();

    return $table->load($collectionId) && $this->isOwner($table);
  }

  /**
   * Load approved images mapped to a collection in stable order.
   *
   * @param   int  $collectionId  Collection ID.
   *
   * @return  array  Mapped image records ordered by image ID.
   *
   * @since   __DEPLOY_VERSION__
   */
  public function getCollectionDownloadImages(int $collectionId): array
  {
    // Select approved mappings and their complete image records.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select('i.*')
      ->from($db->quoteName(_JOOM_TABLE_IMAGES, 'i'))
      ->join('INNER', $db->quoteName(_JOOM_TABLE_COLLECTIONS_REF, 'r') . ' ON r.imgid = i.id')
      ->where('r.collectionid = ' . $collectionId)
      ->where('r.approved = 1')
      ->order('i.id ASC');

    // Execute the query and return the ordered image candidates.
    return $db->setQuery($query)->loadObjectList();
  }

  /**
   * Load image records for an exact stored archive manifest.
   *
   * @param   array  $imageIds  Image IDs stored with the archive.
   *
   * @return  array  Image records ordered by image ID.
   *
   * @since   __DEPLOY_VERSION__
   */
  public function getDownloadImagesByIds(array $imageIds): array
  {
    $imageIds = array_values(array_unique(array_filter(array_map('intval', $imageIds))));

    if(!$imageIds)
    {
      return [];
    }

    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select('i.*')
      ->from($db->quoteName(_JOOM_TABLE_IMAGES, 'i'))
      ->whereIn($db->quoteName('i.id'), $imageIds, ParameterType::INTEGER)
      ->order($db->quoteName('i.id') . ' ASC');

    return $db->setQuery($query)->loadObjectList();
  }

  /**
   * Toggle an image mapping in one of the current user's collections.
   *
   * The collection row is locked so concurrent clicks cannot both decide from
   * the same mapping state.
   *
   * @param   int  $collectionId  Collection ID.
   * @param   int  $imageId       Image ID.
   *
   * @return  boolean  True when the image is mapped after the operation, false when removed.
   *
   * @since   DEPLOY_VERSION
   */
  public function toggleMapping(int $collectionId, int $imageId): bool
  {
    // Reject incomplete collection and image selections.
    if($collectionId <= 0 || $imageId <= 0)
    {
      throw new \InvalidArgumentException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_SELECTION'), 400);
    }

    // Start a transaction that covers the lock and mapping update.
    $db = $this->getDatabase();
    $db->transactionStart(true);

    try
    {
      // Lock the collection row to serialize concurrent toggle requests.
      $query = $db->getQuery(true)
        ->select($db->quoteName('id'))
        ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS))
        ->where($db->quoteName('id') . ' = ' . $collectionId);

      if(!(int) $db->setQuery($query . ' FOR UPDATE')->loadResult())
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'), 404);
      }

      // Verify current ownership after acquiring the database lock.
      $table = $this->getTable();

      if(!$table->load($collectionId) || !$this->isOwner($table))
      {
        throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
      }

      // Read the current mapping state for the selected image.
      $query  = $db->getQuery(true)
        ->select($db->quoteName('id'))
        ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS_REF))
        ->where($db->quoteName('collectionid') . ' = ' . $collectionId)
        ->where($db->quoteName('imgid') . ' = ' . $imageId);
      $mapped = (bool) $db->setQuery($query)->loadResult();

      // Remove an existing mapping or add an approved new mapping.
      if($mapped)
      {
        if(!$table->removeMapping($imageId))
        {
          throw new \RuntimeException($table->getError() ?: Text::_('COM_JOOMGALLERY_COLLECTION_REMOVE_FAILED'));
        }
      }
      elseif(!$table->addMapping($imageId, true))
      {
        throw new \RuntimeException($table->getError() ?: Text::_('COM_JOOMGALLERY_COLLECTION_ADD_FAILED'));
      }

      // Commit the mapping change and return its new state.
      $db->transactionCommit(true);

      return !$mapped;
    }
    catch(\Throwable $e)
    {
      // Roll back the toggle when locking or mapping fails.
      $db->transactionRollback(true);
      throw $e;
    }
  }
}
