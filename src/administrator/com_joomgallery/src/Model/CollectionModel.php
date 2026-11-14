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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Table\Asset;

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

  /** Return the current Joomla identity's JoomGallery profile ID. */
  protected function getCurrentProfileId(): int
  {
    $identity = Factory::getApplication()->getIdentity();

    if((int) $identity->id <= 0)
    {
      return 0;
    }

    $profile = $this->component->getMVCFactory()->createTable('User', 'Administrator');

    return $profile->load(['cmsuser' => (int) $identity->id]) ? (int) $profile->id : 0;
  }

  /** Check whether the current Joomla identity owns a collection or profile. */
  protected function isOwner(object $collection): bool
  {
    $profileId = $this->getCurrentProfileId();

    return $profileId > 0 && $profileId === (int) $collection->userid;
  }

  /** Check only the global ACL result, excluding JoomGallery own-rule fallbacks. */
  protected function hasGlobalPermission(string $action): bool
  {
    $acl = $this->getAcl();
    $acl->checkACL($action, 'collection');

    return (bool) $acl->allowed['default'];
  }

  /** Check whether a collection may be created for the requested owner. */
  public function canCreateCollection(int $requestedOwnerId = 0): bool
  {
    if($this->hasGlobalPermission('add'))
    {
      return true;
    }

    $profileId = $this->getCurrentProfileId();

    return $profileId > 0 && ($requestedOwnerId === 0 || $requestedOwnerId === $profileId);
  }

  /** Resolve the submitted owner according to global-create and ownership rules. */
  protected function resolveCreationOwner(int $requestedOwnerId): int
  {
    if($this->hasGlobalPermission('add'))
    {
      $this->assertOwner($requestedOwnerId);

      return $requestedOwnerId;
    }

    $profileId = $this->getCurrentProfileId();

    if($profileId <= 0 || ($requestedOwnerId > 0 && $requestedOwnerId !== $profileId))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    return $profileId;
  }

  /** Permissions shared by the list UI and the submitted management operation. */
  public function getManagementPermissions(object $collection): array
  {
    $owner       = $this->isOwner($collection);
    $permissions = [];

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

  /** Load the creation-only form without loading an existing collection. */
  public function getCreationForm()
  {
    $form = $this->loadForm('com_joomgallery.collectioncreate', 'collectioncreate', ['control' => 'jform', 'load_data' => false]);

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

  /** Require a gallery profile linked to an existing Joomla account. */
  protected function assertOwner(int $id): void
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('g.id')->from($db->quoteName('#__joomgallery_users', 'g'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser')
      ->where('g.id = ' . $id);

    if($id <= 0 || !$db->setQuery($query)->loadResult())
    {
      throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_INVALID_OWNER'));
    }
  }

  /** Create a normal collection; record identity and lifecycle fields are server-controlled. */
  public function createCollection(array $data): bool
  {
    if(!empty($data['id']))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $ownerId               = $this->resolveCreationOwner((int) ($data['userid'] ?? 0));
    $data                  = array_intersect_key($data, array_flip(['userid', 'title', 'alias', 'description', 'access', 'published']));
    $data['id']            = 0;
    $data['userid']        = $ownerId;
    $data['is_default']    = 0;
    $data['created_by']    = (int) Factory::getApplication()->getIdentity()->id;
    $data['modified_by']   = $data['created_by'];
    $data['modified_time'] = Factory::getDate()->toSql();

    if($ownerId !== $this->getCurrentProfileId() && !$this->hasGlobalPermission('editstate'))
    {
      $data['published'] = 0;
    }

    $this->setState('collection.id', 0);

    return parent::save($data);
  }

  /** Owners may always edit state; other users require global core.edit.state. */
  protected function canEditState($record)
  {
    $table = $this->getTable();

    if((int) ($record->id ?? 0) > 0 && $table->load((int) $record->id) && $this->isOwner($table))
    {
      return true;
    }

    return $this->hasGlobalPermission('editstate');
  }

  /** Apply exactly one authorised modal operation against the current database record. */
  public function manageCollection(int $id, string $operation, int $ownerId = 0): void
  {
    $db = $this->getDatabase();
    $db->transactionStart(true);

    try
    {
      $table = $this->getTable();

      if($id <= 0 || !$table->load($id))
      {
        throw new \RuntimeException(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'), 404);
      }

      $permissions  = $this->getManagementPermissions($table);
      $permissionId = \in_array($operation, ['publish', 'unpublish'], true) ? 'state' : $operation;

      if(empty($permissions[$permissionId]))
      {
        throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
      }

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

      $db->transactionCommit(true);
    }
    catch(\Throwable $e)
    {
      $db->transactionRollback(true);
      throw $e;
    }

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
    foreach((array) $pks as $id)
    {
      $this->manageCollection((int) $id, 'delete');
    }

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
    if($id <= 0)
    {
      return;
    }

    $table = $this->getTable();

    if(!$table->load($id))
    {
      return;
    }

    if((int) $table->is_default === 1 && !$allowDefault)
    {
      throw new \RuntimeException('The default collection can only be deleted with its JoomGallery user profile.');
    }

    if(!$table->removeMapping())
    {
      throw new \RuntimeException($table->getError() ?: 'Could not delete collection mappings.');
    }

    $assetId                   = (int) $table->asset_id;
    $table->allowDefaultDelete = $allowDefault;

    if(!$table->delete($id))
    {
      throw new \RuntimeException($table->getError() ?: 'Could not delete the collection.');
    }

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
    if($userId <= 0 || $cmsuser <= 0)
    {
      throw new \InvalidArgumentException('Valid JoomGallery and Joomla user IDs are required.');
    }

    $db = $this->getDatabase();
    $db->transactionStart(true);

    try
    {
      // Lock the profile row, which exists even when no collection row can be locked yet.
      // This serializes concurrent default-collection creation for the same profile.
      $query          = $db->getQuery(true)->select('cmsuser')
        ->from($db->quoteName('#__joomgallery_users'))
        ->where('id = ' . $userId);
      $profileCmsUser = (int) $db->setQuery($query . ' FOR UPDATE')->loadResult();

      if($profileCmsUser <= 0 || $profileCmsUser !== $cmsuser)
      {
        throw new \RuntimeException('The JoomGallery user profile does not match the Joomla user.');
      }

      $query      = $db->getQuery(true)->select('id')
        ->from($db->quoteName('#__joomgallery_collections'))
        ->where('userid = ' . $userId)
        ->where('is_default = 1')
        ->order('id ASC');
      $defaultIds = array_map('intval', $db->setQuery($query)->loadColumn());

      if($defaultIds)
      {
        if(\count($defaultIds) > 1)
        {
          throw new \RuntimeException('A JoomGallery user profile has more than one default collection.');
        }

        $db->transactionCommit(true);

        return $defaultIds[0];
      }

      $title                      = Text::_('COM_JOOMGALLERY_FAVOURITES');
      $query                      = $db->getQuery(true)->select('id')
        ->from($db->quoteName('#__joomgallery_collections'))
        ->where('userid = ' . $userId)
        ->where('title = ' . $db->quote($title))
        ->order('id ASC');
      $collectionId               = (int) $db->setQuery($query, 0, 1)->loadResult();
      $table                      = $this->getTable();
      $table->allowDefaultChanges = true;

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

      if(!$table->bind($data) || !$table->check() || !$table->store())
      {
        throw new \RuntimeException($table->getError() ?: 'Could not create the default collection.');
      }

      $collectionId = (int) $table->id;
      $db->transactionCommit(true);

      return $collectionId;
    }
    catch(\Throwable $e)
    {
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
    $collection_id = (int) $collection_id;

    if($collection_id <= 0 || (\is_array($img_id) ? empty($img_id) : (int) $img_id <= 0))
    {
      return false;
    }

    if(!$this->canConnectCollection($collection_id))
    {
      $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

      return false;
    }

    $table = $this->getTable();

    if(!$table->load($collection_id))
    {
      $this->setError(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'));

      return false;
    }

    if(!$table->addMapping($img_id, $approve))
    {
      $this->setError($table->getError());

      return false;
    }

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
    $collection_id = (int) $collection_id;

    if($collection_id <= 0)
    {
      return false;
    }

    if(!$this->canConnectCollection($collection_id))
    {
      $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

      return false;
    }

    $table = $this->getTable();

    if(!$table->load($collection_id))
    {
      $this->setError(Text::_('COM_JOOMGALLERY_COLLECTION_NOT_FOUND'));

      return false;
    }

    if(!$table->removeMapping($img_id))
    {
      $this->setError($table->getError());

      return false;
    }

    return true;
  }

  /** Owners may always connect images; other users require global joom.connect. */
  public function canConnectCollection(int $collectionId): bool
  {
    if($collectionId <= 0)
    {
      return false;
    }

    $table = $this->getTable();

    if(!$table->load($collectionId))
    {
      return false;
    }

    return $this->isOwner($table) || $this->hasGlobalPermission('connect');
  }
}
