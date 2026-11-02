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
      $this->deleteCollection((int) $id);
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

    $db         = $this->getDatabase();
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

      return $collectionId;
    }

    $date = \Joomla\CMS\Factory::getDate()->toSql();
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

    return (int) $table->id;
  }

  /**
   * Add a mapping between a collection and an image.
   *
   * @param   int  $collection_id  Collection ID.
   * @param   int  $img_id         Image ID.
   *
   * @return  boolean  True when the mapping exists after the operation.
   *
   * @since   DEPLOY_VERSION
   */
  public function addMapping($collection_id, $img_id)
  {
    $collection_id = (int) $collection_id;
    $img_id        = (int) $img_id;

    if($collection_id <= 0 || $img_id <= 0)
    {
      return false;
    }

    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('id')
      ->from($db->quoteName(_JOOM_TABLE_COLLECTIONS_REF))
      ->where('collectionid = ' . $collection_id)
      ->where('imgid = ' . $img_id);

    if($db->setQuery($query)->loadResult())
    {
      return true;
    }

    $mapping               = new \stdClass();
    $mapping->collectionid = $collection_id;
    $mapping->imgid        = $img_id;

    try
    {
      $db->insertObject(_JOOM_TABLE_COLLECTIONS_REF, $mapping);
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }

    return true;
  }

  /**
   * Remove one image mapping, or every image mapping for a collection.
   *
   * @param   int  $collection_id  Collection ID.
   * @param   int  $img_id         Image ID, or zero to remove all collection mappings.
   *
   * @return  boolean  True on success.
   *
   * @since   DEPLOY_VERSION
   */
  public function removeMapping($collection_id, $img_id = 0)
  {
    $collection_id = (int) $collection_id;
    $img_id        = (int) $img_id;

    if($collection_id <= 0)
    {
      return false;
    }

    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->delete($db->quoteName(_JOOM_TABLE_COLLECTIONS_REF))
      ->where('collectionid = ' . $collection_id);

    if($img_id > 0)
    {
      $query->where('imgid = ' . $img_id);
    }

    try
    {
      $db->setQuery($query)->execute();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }

    return true;
  }
}
