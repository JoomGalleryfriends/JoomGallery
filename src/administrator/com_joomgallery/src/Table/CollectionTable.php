<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Table;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Table\Asset\AssetTableTrait;
use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseDriver;

/**
 * Collection table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CollectionTable extends Table
{
  use JoomTableTrait;
  use AssetTableTrait;
  use MigrationTableTrait;
  use LegacyDatabaseTrait;

  /**
   * List of images connected to this collection
   *
   * @var    array
   * @since  4.0.0
   */
  public $images = null;

  /**
   * True if new mapped images should be automatically approved
   *
   * @var    bool
   * @since  4.0.0
   */
  public $approveImages = false;

  /**
   * Allow lifecycle code to create or designate a default collection.
   *
   * @var    bool
   * @since  DEPLOY_VERSION
   */
  public bool $allowDefaultChanges = false;

  /**
   * Allow the user-profile lifecycle to delete a default collection.
   *
   * @var    bool
   * @since  DEPLOY_VERSION
   */
  public bool $allowDefaultDelete = false;

  /**
   * Constructor
   *
   * @param   JDatabase  &$db               A database connector object
   * @param   bool       $component_exists  True if the component object class exists
   */
  public function __construct(DatabaseDriver $db, bool $component_exists = true)
  {
    $this->component_exists = $component_exists;
    $this->typeAlias        = _JOOM_OPTION . '.collection';

    parent::__construct(_JOOM_TABLE_COLLECTIONS, 'id', $db);

    $this->setColumnAlias('published', 'published');
  }

  /**
   * Overloaded bind function to pre-process the params.
   *
   * @param   array  $array   Named array
   * @param   mixed  $ignore  Optional array or list of parameters to ignore
   *
   * @return  boolean  True on success.
   *
   * @see     Table:bind
   * @since   4.0.0
   * @throws  \InvalidArgumentException
   */
  public function bind($array, $ignore = '')
  {
    $date = Factory::getDate();
    $task = Factory::getApplication()->input->get('task', '', 'cmd');
    $id   = (int) ($array['id'] ?? $this->id ?? 0);

    // Default status and ownership are lifecycle invariants, not editable fields.
    if(!$this->allowDefaultChanges && !$this->is_migration)
    {
      if($id > 0)
      {
        $original = new self($this->getDatabase());

        if($original->load($id))
        {
          // Only lifecycle or migration code may designate another default.
          $array['is_default'] = (int) $original->is_default;

          if((int) $original->is_default === 1)
          {
            $array['userid'] = (int) $original->userid;
          }
        }
      }
      elseif(!empty($array['is_default']))
      {
        $array['is_default'] = 0;
      }
    }

    // Support for title field: title
    if(\array_key_exists('title', $array))
    {
      $array['title'] = trim($array['title']);

      if(empty($array['title']))
      {
        $array['title'] = 'Unknown';
      }
    }

    // Support for alias field: alias
    if(empty($array['alias']))
    {
      if(empty($array['title']))
      {
        $array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
      }
      else
      {
        if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
        {
          $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
        }
        else
        {
          $array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
        }
      }
    }
    else
    {
      if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
      {
        $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['alias']));
      }
      else
      {
        $array['alias'] = OutputFilter::stringURLSafe(trim($array['alias']));
      }
    }

    if($array['id'] == 0)
    {
      $array['created_time'] = $date->toSql();
    }

    $this->protectCreatedBy($array, true);

    if($task == 'apply' || strpos($task, 'save') !== false)
    {
      $array['modified_time'] = $date->toSql();
    }

    if($array['id'] == 0 && (!key_exists('modified_by', $array) || empty($array['modified_by'])))
    {
      $array['modified_by'] = Factory::getApplication()->getIdentity()->id;
    }

    if($task == 'apply' || strpos($task, 'save') !== false)
    {
      $array['modified_by'] = Factory::getApplication()->getIdentity()->id;
    }

    // Support for list of images to be mapped
    if(isset($array['images']) && !\is_array($array['images']))
    {
      // Try to convert from json string
      $decoded = json_decode($array['images'], true);

      if(json_last_error() === JSON_ERROR_NONE)
      {
        $array['images'] = $decoded;
      }
      else
      {
        $array['images'] = explode(',', $array['images']);
      }
    }

    return parent::bind($array, $ignore);
  }

  /**
   * Overloaded check function
   *
   * @return bool
   */
  public function check()
  {
    // Check if alias is unique inside this user
    if(!$this->isUnique('alias', $this->userid, 'userid'))
    {
      $count        = 2;
      $currentAlias = $this->alias;

      while(!$this->isUnique('alias', $this->userid, 'userid'))
      {
        $this->alias = $currentAlias . '-' . $count++;
      }
    }

    // Support for field description
    if(empty($this->description))
    {
      $this->description = $this->loadDefaultField('description');
    }

    // Support for field metadesc
    if(empty($this->metadesc))
    {
      $this->metadesc = $this->loadDefaultField('metadesc');
    }

    // Support for field metakey
    if(empty($this->metakey))
    {
      $this->metakey = $this->loadDefaultField('metakey');
    }

    return parent::check();
  }

  /**
   * Method to store a row in the database from the Table instance properties.
   *
   * @param   boolean  $updateNulls  True to update fields even if they are null.
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function store($updateNulls = true)
  {
    $images = null;

    if(property_exists($this, 'images') && !empty($this->images))
    {
      $images = $this->images;
    }

    if($success = parent::store($updateNulls))
    {
      if(!\is_null($images) && !empty($images))
      {
        $approved = false;

        if($this->created_by == Factory::getApplication()->getIdentity()->id || $this->approveImages)
        {
          $approved = true;
        }

        // Do the mapping
        $this->addMapping($images, $approved);
      }
    }

    return $success;
  }

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    if(!$this->load($pk))
    {
      return false;
    }

    if((int) $this->is_default === 1 && !$this->allowDefaultDelete)
    {
      $this->setError('The default collection can only be deleted with its JoomGallery user profile.');

      return false;
    }

    $this->_trackAssets = false;

    if($success = parent::delete($pk))
    {
      // Delete mappings if existent
      $this->removeMapping();
    }

    return $success;
  }

  /**
   * Map one or multiple images to the currently loaded collection.
   *
   * @param   int|array  $img_id   IDs of the images to be mapped.
   * @param   bool       $approve  True if new mappings are approved.
   *
   * @return  boolean    True on success, False on error.
   *
   * @since   4.0.0
   */
  public function addMapping($img_id, $approve = false)
  {
    if(empty($this->getId()))
    {
      $this->setError('Load table first.');

      return false;
    }

    // Prepare image ids
    if(!\is_array($img_id))
    {
      $img_id = [$img_id];
    }

    // Load db driver
    $db = $this->getDatabase();

    foreach($img_id as $key => $iid)
    {
      if($iid > 0)
      {
        $mapping               = new \stdClass();
        $mapping->imgid        = (int) $iid;
        $mapping->collectionid = (int) $this->getId();

        if($approve === true)
        {
          $mapping->approved = 1;
        }

        try
        {
          $db->insertObject(_JOOM_TABLE_COLLECTIONS_REF, $mapping);
        }
        catch(\Exception $e)
        {
          $this->setError($e->getMessage());
          $this->getComponent()->addLog($e->getMessage(), 'error', 'jerror');

          return false;
        }
      }
    }

    return true;
  }

  /**
   * Remove specific or all mappings of currently loaded collection
   *
   * @param   int|array  $img_id   IDs of the images to be removed. (0: remove all)
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function removeMapping($img_id = 0)
  {
    if(empty($this->getId()))
    {
      $this->setError('Load table first.');

      return false;
    }

    // Prepare image ids
    if(!\is_array($img_id) && $img_id != 0)
    {
      $img_id = [$img_id];
    }

    // Load db driver
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    // Create where conditions
    $query->where($db->quoteName('collectionid') . ' = ' . $db->quote((int) $this->getId()));

    if(\is_array($img_id))
    {
      // Delete mapping only for a specified images
      $query->where($db->quoteName('imgid') . ' IN (' . implode(',', $img_id) . ')');
    }

    // Create the query
    $query->delete($db->quoteName(_JOOM_TABLE_COLLECTIONS_REF));
    $db->setQuery($query);

    try
    {
      // Execute the query
      $db->execute();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->getComponent()->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }

    return true;
  }
}
