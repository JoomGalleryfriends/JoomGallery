<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Scripts;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\Filesystem\Path;
use \Joomla\Filesystem\File;
use \Joomla\Filesystem\Folder;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Component\Media\Administrator\Exception\FileExistsException;
use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Checks;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Migration;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Targetinfo;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationInterface;

/**
 * Migration script class
 * JoomGallery 3.x to JoomGallery 4.x
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Jg3ToJg4 extends Migration implements MigrationInterface
{
  /**
	 * Name of the migration script to be used.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
	protected $name = 'Jg3ToJg4';

  /**
   * True to offer the task migration.removesource for this script
   *
   * @var    boolean
   *
   * @since  4.0.0
   */
  protected $sourceDeletion = true;

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Returns an object with compatibility info for this migration script.
   *
   * @param   string       $type    Select if you get source or destination info
   *
   * @return  Targetinfo   Compatibility info object
   *
   * @since   4.0.0
   */
  public function getTargetinfo(string $type = 'source'): Targetinfo
  {
    $info = new Targetinfo();

    $info->set('target', $type);
    $info->set('type','component');

    if($type === 'source')
    {
      $info->set('extension','JoomGallery');
      $info->set('min', '3.6.0');
      $info->set('max', '3.7.99');
      $info->set('php_min', '5.6.0');
    }
    elseif($type === 'destination')
    {
      $info->set('extension','com_joomgallery');
      $info->set('min', '4.0.0-beta1'); // Todo: change back to 4.0.0
      $info->set('max', '5.99.99');
      $info->set('php_min', '7.4.0');
    }
    else
    {
      $this->component->addLog('Type must be either "source" or "destination", but "'.$type.'" given.', 'error', 'migration');
      throw new \Exception('Type must be either "source" or "destination", but "'.$type.'" given.', 1);
    }

    return $info;
  }

  /**
   * Returns the XML object of the source extension
   *
   * @return  \SimpleXMLElement|string   Extension XML object or XML path on failure
   *
   * @since   4.0.0
   */
  public function getSourceXML()
  {
    if($this->params->get('same_joomla', 1))
    {
      $path = Path::clean(JPATH_ADMINISTRATOR . '/components/com_joomgallery/joomgallery_old.xml');
      $xml  = \simplexml_load_file($path);

      if($xml){return $xml;}else{return $path;}
    }
    else
    {
      $joomla_root = $this->params->get('joomla_path');

      // Remove directory separator at the end
      if(\substr($joomla_root, -1) == '/' || \substr($joomla_root, -1) == \DIRECTORY_SEPARATOR)
      {
        $joomla_root = \substr($joomla_root, 0, -1);
      }

      if(\file_exists(Path::clean($joomla_root . '/administrator/components/com_joomgallery/joomgallery.xml')))
      {
        $path = Path::clean($joomla_root . '/administrator/components/com_joomgallery/joomgallery.xml');
        $xml  = \simplexml_load_file($path);

        if($xml){return $xml;}else{return $path;}
      }
      else
      {
        $path = Path::clean($joomla_root . '/administrator/components/com_joomgallery/joomgallery_old.xml');
        $xml  = \simplexml_load_file($path);

        if($xml){return $xml;}else{return $path;}
      }
    }
  }

  /**
   * Returns a list of involved source directories.
   *
   * @return  array    List of paths
   *
   * @since   4.0.0
   */
  public function getSourceDirs(): array
  {
    $dirs = array( $this->params->get('orig_path'),
                   $this->params->get('detail_path'),
                   $this->params->get('thumb_path')
                  );

    return $dirs;
  }

  /**
   * A list of content type definitions depending on migration source
   * (Required in migration scripts. The order of the content types must correspond to its migration order)
   *
   * ------
   * This method is multiple times, when the migration types are loaded. The first time it is called without
   * the $type param, just to retrieve the array of source types info. The next times it is called with a
   * $type param to load the optional type infos like ownerFieldname.
   *
   * Needed: tablename, primarykey, isNested, isCategorized
   * Optional: ownerFieldname, dependent_on, pkstoskip, insertRecord, queueTablename, recordName
   *
   * Assumption for insertrecord:
   * If insertrecord == true assumes, that type is a migration; Means reading data from source db and write it to destination db (default)
   * If insertrecord == false assumes, that type is an adjustment; Means reading data from destination db adjust it and write it back to destination db
   *
   * Attention:
   * Order of the content types must correspond to the migration order
   * Pay attention to the dependent_on when ordering here !!!
   *
   * @param   bool   $names_only  True to load type names only. No migration parameters required.
   * @param   Type   $type        Type object to set optional definitions
   *
   * @return  array   The source types info, array(tablename, primarykey, titlename, isNested, isCategorized, isFromSourceDB)
   *
   * @since   4.0.0
   */
  public function defineTypes($names_only=false, &$type=null): array
  {
    $types = array( 'category'  => array('#__joomgallery_catg', 'cid', 'name', true, false, true),
                    'image'     => array('#__joomgallery', 'id', 'imgtitle', false, true, true),
                    'catimage'  => array(_JOOM_TABLE_CATEGORIES, 'cid', 'name', false, false, false),
                    'user'      => array('#__joomgallery_users', 'uid', '', false, false, true),
                    'collection'=> array('#__joomgallery_users', 'uid', '', false, false, true),
                    'vote'      => array('#__joomgallery_votes', 'voteid', '', false, false, true),
                    'comment'   => array('#__joomgallery_comments', 'cmtid', 'cmtname', false, false, true)
                  );

    if($this->params->get('source_ids', 0) == 1)
    {
      // Special case: When using ids from source, category images don't have to be adjusted.
      unset($types['catimage']);
    }

    if($names_only)
    {
      return \array_keys($types);
    }
    //------- First point of return: Return names only

    // add suffix, if source tables are in the same db with *_old at the end
    $source_db_suffix = '';
    if($this->params->get('same_db', 1))
    {
      $source_db_suffix = '_old';
    }

    foreach($types as $key => $value)
    {
      if(\count($value) < 6 || (\count($value) > 5 && $value[5]))
      {
        // tablename is from source db and has to be checked
        $types[$key][0] = $value[0] . $source_db_suffix;
      }
    }

    // Return here if type is not given
    if(\is_null($type))
    {
      return $types;
    }
    //------- Second point of return: Don't load optional type infos

    // Load the optional type infos:
    // ownerFieldname, dependent_on, pkstoskip, insertRecord, queueTablename, recordName
    switch($type->name)
    {
      case 'category':
        $type->set('pkstoskip', array(1));
        break;

      case 'image':
        $type->set('dependent_on', array('category'));
        break;

      case 'catimage':
        $type->set('dependent_on', array('category', 'image'));
        $type->set('pkstoskip', array(1));
        $type->set('insertRecord', false);
        $type->set('queueTablename', '#__joomgallery_catg' . $source_db_suffix);
        $type->set('recordName', 'category');
        break;

      case 'user':
        $type->set('ownerFieldname', 'cmsuser');
        break;

      case 'vote':
        $type->set('dependent_on', array('image'));
        break;

      case 'comment':
        $type->set('dependent_on', array('image'));
        break;

      case 'collection':
        $type->set('dependent_on', array('user', 'image'));
        break;

      default:
        // No optional type infos needed
        break;
    }

    return $types;
  }

  /**
   * Converts data from source into the structure needed for JoomGallery.
   * (Optional in migration scripts, but highly recommended.)
   *
   * ------
   * How mappings work:
   * - Key not in the mapping array:              Nothing changes. Field value can be magrated as it is.
   * - 'old key' => 'new key':                    Field name has changed. Old values will be inserted in field with the provided new key.
   * - 'old key' => false:                        Field does not exist anymore or value has to be emptied to create new record in the new table.
   * - 'old key' => array(string, string, bool):  Field will be merged into another field of type json.
   *                                              1. ('destination field name'): Name of the field to be merged into.
   *                                              2. ('new field name'): New name of the field created in the destination field. (default: false / retain field name)
   *                                              3. ('create child'): True, if a child node shall be created in the destination field containing the field values. (default: false / no child)
   *
   *
   * @param   string  $type   Name of the content type
   * @param   array   $data   Source data received from getData()
   * @param   mixed   $pk     The primary key of the content type
   *
   * @return  array   Converted data to save into JoomGallery
   *
   * @since   4.0.0
   */
  public function convertData(string $type, array $data, $pk): array
  {
    // Parameter dependent mapping fields
    $id    = \boolval($this->params->get('source_ids', 0)) ? 'id' : false;
    $owner = \boolval($this->params->get('check_owner', 1)) ? $this->types[$type]->get('ownerFieldname') : false;

    // Configure mapping for each content type
    switch($type)
    {
      case 'category':
        // Apply mapping for category table
        $mapping  = array( 'cid' => $id, 'asset_id' => 0, 'name' => 'title', 'alias' => false, 'lft' => false, 'rgt' => false, 'level' => false,
                           'owner' => $owner, 'img_position' => false, 'catpath' => 'static_path', 'params' => array('params', false, false),
                           'allow_download' => array('params', 'jg_download', false), 'allow_comment' => array('params', 'jg_showcomment', false),
                           'allow_rating' => array('params', 'jg_showrating', false), 'allow_watermark' => array('params', 'jg_dynamic_watermark', false),
                           'allow_watermark_download' => array('params', 'jg_downloadwithwatermark', false)
                          );

        // Adjust parent_id based on already created categories
        if(!\boolval($this->params->get('source_ids', 0)) && $data['parent_id'] > 0)
        {
          $data['parent_id'] = $this->migrateables['category']->successful->get($data['parent_id']);
        }

        break;

      case 'image':
        // Apply mapping for image table
        $mapping  = array( 'id' => $id, 'asset_id' => 0, 'alias' => false, 'imgtitle' => 'title', 'imgtext' => 'description', 'imgauthor' => 'author',
                           'imgdate' => 'date', 'imgfilename' => 'filename', 'imgvotes' => 'votes', 'imgvotesum' => 'votesum', 'imgthumbname' => false,
                           'owner' => $owner, 'params' => array('params', false, false)
                          );

        // Check difference between imgfilename and imgthumbname
        if($data['imgfilename'] !== $data['imgthumbname'])
        {
          $this->component->setError(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_FILENAME_DIFF', $data['id'], $data['alias']));
          $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_FILENAME_DIFF', $data['id'], $data['alias']), 'error', 'migration');

          return array();
        }

        // Adjust catid with new created categories
        if(!\boolval($this->params->get('source_ids', 0)))
        {
          $data['catid'] = $this->migrateables['category']->successful->get($data['catid']);
        }

        break;

      case 'catimage':
        // Dont change the record data
        $mapping = array();

        // Adjust category thumbnail
        if(!empty($data['thumbnail']))
        {
          if($this->migrateables['image']->successful->get($data['thumbnail'], false))
          {
            // Change category thumbnail id based on migrated image id
            $data['thumbnail'] = $this->migrateables['image']->successful->get($data['thumbnail']);
          }
          else
          {
            // Migrated image id not available, set id to 0
            $data['thumbnail'] = 0;
          }
        }

        break;

      case 'user':
        // Apply mapping for users table
        $mapping  = array( 'uid' => $id, 'uuserid' => $owner, 'piclist' => false, 'time' => 'created_time',
                           'zipname' => array('params', false, false), 'layout' => array('params', false, false)
                          );

        break;

      case 'vote':
        // Apply mapping for votes table
        $mapping  = array( 'voteid' => $id, 'picid' => 'imgid', 'userid' => $owner,
                           'userip' => 'identication', 'datevoted' => 'created_time', 'vote' => 'score',
                          );

        // Adjust imgid with new created images
        if(!\boolval($this->params->get('source_ids', 0)))
        {
          $data['picid'] = $this->migrateables['image']->successful->get($data['picid']);
        }

        break;

      case 'comment':
        // Apply mapping for comments table
        $mapping  = array( 'cmtid' => $id, 'cmtpic' => 'imgid', 'cmtip' => false, 'userid' => $owner,
                           'cmtname' => 'title', 'cmttext' => 'description', 'cmtdate' => 'created_time',
                          );

        // Adjust imgid with new created images
        if(!\boolval($this->params->get('source_ids', 0)))
        {
          $data['cmtpic'] = $this->migrateables['image']->successful->get($data['cmtpic']);
        }

        break;

      case 'collection':
        // Apply mapping for collections table
        $mapping  = array( 'uid' => false, 'uuserid' => 'userid', 'piclist' => 'images', 'layout' => false,
                           'time' => false, 'zipname' => false
                          );

        // Convert piclist to array
        $data['piclist'] = \explode(',', $data['piclist']);

        // Add title to array
        $data['title'] = Text::_('COM_JOOMGALLERY_FAVOURITES');

        // Adjust piclist with image id
        if(!\boolval($this->params->get('source_ids', 0)))
        {
          // Get new created destination image id
          foreach($data['piclist'] as $key => $img_id)
          {
            $data['piclist'][$key] = $this->migrateables['image']->successful->get($img_id);
          }
        }

        // Adjust uuserid with new created users
        if(!\boolval($this->params->get('source_ids', 0)))
        {
          $data['uuserid'] = $this->migrateables['user']->successful->get($data['uuserid']);
        }

        break;

      default:
        // The table structure is the same
        $mapping = array('id' => $id, 'owner' => $owner);

        break;
    }

    // Strip zero values for owners (owner=0)
    if(isset($data[$this->types[$type]->get('ownerFieldname')]) && !$data[$this->types[$type]->get('ownerFieldname')])
    {
      // Owner is currently set to zero or empty. Set it to be null
      $data[$this->types[$type]->get('ownerFieldname')] = null;
    }

    // Apply mapping
    return $this->applyConvertData($data, $mapping);
  }

  /**
   * - Load a queue of ids from a specific migrateable object
   * - Reload/Reorder the queue if migrateable object already has queue
   *
   * @param   string     $type         Content type
   * @param   object     $migrateable  Mibrateable object
   *
   * @return  array
   *
   * @since   4.0.0
   */
  public function getQueue(string $type, ?object $migrateable = null): array
  {
    if(\is_null($migrateable))
    {
      if(!$migrateable  = $this->getMigrateable($type))
      {
        return array();
      }
    }

    $this->loadTypes();

    // Queue gets always loaded from source db
    $tablename  = $this->types[$type]->get('queueTablename');
    $primarykey = $this->types[$type]->get('pk');

    // Get db object
    list($db, $prefix) = $this->getDB('source');

    // Initialize query object
		$query = $db->getQuery(true);

    // Create selection
    $selection = array($db->quoteName($primarykey));

    // Create the query
    $query->select($selection);
    $query->from($db->quoteName($tablename));

    // Apply additional where clauses for specific content types
    if($type == 'catimage')
    {
      $query->where($db->quoteName($primarykey) . ' > 1');
      $query->where($db->quoteName('thumbnail') . ' > 0');
    }

    // Apply id filter
    // Reorder the queue if queue is not empty
    if(\property_exists($migrateable, 'queue') && !empty($migrateable->queue))
    {
      $queue = (array) $migrateable->get('queue', array());
      $query->where($db->quoteName($primarykey) . ' IN (' . implode(',', $queue) .')');
    }

    // Gather migration types info
    if(empty($this->get('types')))
    {
      $this->getSourceTableInfo($type);
    }

    // Apply ordering based on level if it is a nested type
    if($this->get('types')[$type]->get('nested'))
    {
      //$query->order($db->quoteName('level') . ' ASC');
      $query->order($db->quoteName('lft') . ' ASC');
    }
    else
    {
      $query->order($db->quoteName($primarykey) . ' ASC');
    }

    $db->setQuery($query);

    // Attempt to load the queue
    $queue = array();
    try
    {
      $queue = $db->loadColumn();
    }
    catch(\Exception $e)
    {
      $this->component->setError($e->getMessage());
      $this->component->addLog($e->getMessage(), 'error', 'migration');
    }

    // Postprocessing the queue
    $needs_postprocessing = array('catimage');
    if(!empty($queue) && \in_array($type, $needs_postprocessing))
    {
      if($type == 'catimage' && !\boolval($this->params->get('source_ids', 0)))
      {
        $mig_cat = $this->getMigrateable('category', false);

        if($mig_cat && $mig_cat->id > 0)
        {
          // Adjust catid with new created/migrates categories
          foreach($queue as $key => $old_id)
          {
            $queue[$key] = $mig_cat->successful->get($old_id);
          }
        }
      }
    }

    return $queue;
  }

  /**
   * Load the rules from the assets table and add them to data
   *
   * @param   string   $type   Name of the content type
   * @param   array    $data   Source data received from getData()
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function getRulesData(string $type, array &$data)
  {
    switch($type)
    {
      case 'category':
        parent::getRulesData($type, $data);
        $data['rules-image'] = '{}';
        break;
      
      default:
        // Dont migrate any rules
        break;
    }
  }

  /**
   * onBeforeSave event.
   * Last possibility to change anything on the data before storing into destination database.
   *
   * @param   string   $type    Name of the content type
   * @param   Table    $table   Table object to be inserted into destination
   * 
   * @return  void
   * 
   * @since   4.0.0
   */
  public function onBeforeSave(string $type, Table &$table): void
  {
    if($type == 'collection' && \property_exists($table, 'approveImages'))
    {
      $table->approveImages = true;
    }

    return;
  }

  /**
   * Performs the neccessary steps to migrate an image in the filesystem
   *
   * @param   ImageTable   $img    ImageTable object, already stored
   * @param   array        $data   Source data received from getData()
   *
   * @return  bool         True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function migrateFiles(ImageTable $img, array $data): bool
  {
    $img_source = $this->getImageSource($data);

    // Set old catid for image creation
    $img->catid = $data['catid'];

    if($this->params->get('image_usage', 0) == 1)
    {
      // Recreate imagetypes based on given image
      $res = $this->createImages($img, $img_source[0]);
    }
    elseif($this->params->get('image_usage', 0) == 2 || $this->params->get('image_usage', 0) == 3)
    {
      $copy = false;
      if($this->params->get('image_usage', 0) == 2)
      {
        $copy = true;
      }

      // Copy/Move images from source based on mapping
      $res = $this->reuseImages($img, $img_source, $copy);
    }
    else
    {
      // Direct usage of images
      // Nothing to do
      $res = true;
    }

    return $res;
  }

  /**
   * Performs the necessary steps to migrate a category in the filesystem
   *
   * @param   CategoryTable   $cat    CategoryTable object, already stored
   * @param   array           $data   Source data received from getData()
   *
   * @return  bool            True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function migrateFolder(CategoryTable $cat, array $data): bool
  {
    // Create file manager service
    $this->component->createFileManager($cat->id);

    if($this->params->get('image_usage', 0) == 0)
    {
      // Direct usage
      if($this->params->get('same_joomla', 1) == 0)
      {
        // Direct usage from other source is impossible
        $this->component->setError('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_ERROR');
        $this->component->addLog('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_ERROR', 'error', 'migration');

        return false;
      }

      // Rename folders if needed
      if($this->params->get('new_dirs', 1) == 1)
      {
        // Create new/JG4 folder structure
        // Get new foldername out of path
        $newName = \basename($cat->path);

        // Get old foldername out of static_path
        $oldName = \basename($cat->static_path);

        // Create dummy object for category Renaming
        $tmp_cat       = new \stdClass();
        $tmp_cat->path = \substr($cat->path, 0, strrpos($cat->path, \basename($cat->path))) . $oldName;

        // Rename existing folders
        return $this->component->getFileManager()->renameCategory($tmp_cat, $newName, 'migration');
      }
    }
    else
    {
      // Recreate, copy or move
      if($this->params->get('new_dirs', 1) == 1)
      {
        // Create new/JG4 folder structure (based on alias)
        return $this->component->getFileManager()->createCategory($cat->alias, $cat->parent_id, 'migration');
      }
      else
      {
        // Create old/JG3 folder structure (based on static_path)
        if( $this->params->get('same_joomla', 1) == 1 &&
            $this->component->getConfig()->get('jg_filesystem', 'local-images') == 'local-images'
          )
        {
          // Recreate, copy or move within the same filesystem by keeping the old folder structure is impossible
          $this->component->setError('FILES_JOOMGALLERY_SERVICE_MIGRATION_MCR_ERROR');
          $this->component->addLog('FILES_JOOMGALLERY_SERVICE_MIGRATION_MCR_ERROR', 'error', 'migration');

          return false;
        }

        return $this->component->getFileManager()->createCategory(\basename($cat->static_path), $cat->parent_id, 'migration');
      }
    }

    return true;
  }

  /**
   * Fetches an array of images from source to be used for creating the imagetypes
   * for the current image.
   *
   * @param   array   $data   Source record data received from getData() - before convertData()
   *
   * @return  array   List of images from sources used to create the new imagetypes
   *                  1. If imagetypes get recreated:    array('image/source/path')
   *                  2. If imagetypes get copied/moved: array('original' => 'image/source/path1', 'detail' => 'image/source/path2', ...)
   *
   * @since   4.0.0
   */
  public function getImageSource(array $data): array
  {
    $directories = $this->getSourceDirs();
    $cat         = $this->getData('category', $data['catid']);

    switch($this->params->get('image_usage', 0))
    {
      // Recreate images
      case 1:
        if(!empty($directories[0]))
        {
          // use original image if not empty
          $dir = $directories[0];
        }
        else
        {
          // use detail image
          $dir = $directories[1];
        }

        // Assemble path to source image with complete system root
        return array(Path::clean($this->getSourceRootPath() . '/' . $dir . '/' . $cat['catpath'] . '/' . $data['imgfilename']));
        break;

      // Copy/Move images
      case 2:
      case 3:
        $imagetypes = JoomHelper::getRecords('imagetypes', $this->component);
        $dirs_map   = array('original' => 0, 'detail' => 1, 'thumbnail' => 2);

        $paths = array();
        foreach($imagetypes as $key => $type)
        {
          // Choose source type based on params
          $source_type = 'detail';
          foreach($this->params->get('image_mapping') as $key => $map)
          {
            if($map['destination'] == $type->typename)
            {
              $source_type = $map['source'];
              break;
            }
          }

          // Assemble path to source image
          $paths[$type->typename] = Path::clean($this->getSourceRootPath() . '/' . $directories[$dirs_map[$source_type]]. '/' . $cat['catpath'] . '/' . $data['imgfilename']);
        }

        return $paths;
        break;

      // Direct usage
      default:
        return array();
        break;
    }
  }

  /**
   * Delete migration source data.
   * It's recommended to use delete source data by uninstalling source extension if possible.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.0.0
   */
  public function deleteSource() 
  {
    // Retrieve a list of source directories involved in migration
    $directories = $this->getSourceDirs();
    $root        = $this->getSourceRootPath();
    $dir_array   = array( array(Text::_('COM_JOOMGALLERY_ORIGINAL'), 'images/joomgallery/originals/'),
                          array(Text::_('COM_JOOMGALLERY_DETAIL'), 'images/joomgallery/details/'),
                          array(Text::_('COM_JOOMGALLERY_THUMBNAIL'), 'images/joomgallery/thumbnails/')
                        );

    // Delete source directories
    $successful = true;
    $undeleted  = array();
    foreach($directories as $key => $dir)
    {
      if($this->params->get('same_joomla', 1) && $dir == $dir_array[$key][1] && $this->params->get('image_usage', 0) === 0)
      {
        // Source directory corresponds to destination directory and images are directly used.
        // Do not do anything
      }
      elseif($this->params->get('same_joomla', 1) && $dir == $dir_array[$key][1] && $this->params->get('image_usage', 0) !== 0)
      {
        // Source directory corresponds to destination directory, but images are not directly used.
        // Do not delete anything, but display message to delete old folders manually
        \array_push($undeleted, $dir_array[$key][0]);
      }
      else
      {
        // Source and destination directory are different.
        try
        {
          $successful = Folder::delete($root . $dir);
        }
        catch (\Exception $e)
        {
          $successful = false;
        }

        if(!$successful)
        {
          if($key == 0)
          {
            $this->component->addLog('The following directories could not be deleted. Try to delete them manually:', 'error', 'migration');
          }

          $this->component->addLog($dir, 'error', 'migration');
        }
      }
    }

    if(!empty($undeleted))
    {
      $this->component->setWarning(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MANUALLY_DELETE_SOURCE_FOLDERS', implode(', ', $undeleted)));
    }

    if(!$successful)
    {
      $this->component->setError('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_DELETE_SOURCE_FOLDERS');
    }

    // Retrieve a list of source tables
    list($db, $dbPrefix) = $this->getDB('source');
    $tables = array( '#__joomgallery',
                     '#__joomgallery_catg',
                     '#__joomgallery_category_details',
                     '#__joomgallery_comments',
                     '#__joomgallery_config',
                     '#__joomgallery_countstop',
                     '#__joomgallery_image_details',
                     '#__joomgallery_maintenance',
                     '#__joomgallery_nameshields',
                     '#__joomgallery_orphans',
                     '#__joomgallery_users',
                     '#__joomgallery_users_ref',
                     '#__joomgallery_votes'
                    );

    // add suffix, if source tables are in the same db with *_old at the end
    $source_db_suffix = '';
    if($this->params->get('same_db', 1))
    {
      $source_db_suffix = '_old';
    }

    // Delete source tables
    $successful = true;
    foreach($tables as $key => $tablename)
    {
      // add suffix to tablename
      $tablename = $tablename . $source_db_suffix;

      $query     = $db->getQuery(true);
      $query->setQuery('DROP TABLE IF EXISTS ' . $tablename);

      $db->setQuery($query);

      try
      {
        $db->execute();
      }
      catch (\Exception $e)
      {
        $successful = false;
      }

      if(!$successful)
      {
        if($key == 0)
        {
          $this->component->addLog('The following DB tables could not be deleted. Try to delete them manually:', 'error', 'migration');
        }

        $this->component->addLog($tablename, 'error', 'migration');
      }
    }

    if(!$successful)
    {
      $this->component->setError('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_DELETE_SOURCE_TABLES');
    }

    return true;
  }

  /**
   * Creation of imagetypes based on one source file.
   * Source file has to be given with a full system path.
   *
   * @param   ImageTable     $img        ImageTable object, already stored
   * @param   string         $source     Source file with which the imagetypes shall be created
   *
   * @return  bool           True on success, false otherwise
   *
   * @since   4.0.0
   */
  protected function createImages(ImageTable $img, string $source): bool
  {
    // Create file manager service
    $this->component->createFileManager($img->catid);

    // Update catid based on migrated categories
    $migrated_cats  = $this->get('migrateables')['category']->successful;
    $migrated_catid = $migrated_cats->get($img->catid);

    // Create imagetypes
    return $this->component->getFileManager()->createImages($source, $img->filename, $migrated_catid, true, true, [], 'migration');
  }

  /**
   * Creation of imagetypes based on images already available on the server.
   * Source files has to be given for each imagetype with a full system path.
   *
   * @param   ImageTable   $img        ImageTable object, already stored
   * @param   array        $sources    List of source images for each imagetype available in JG4
   * @param   bool         $copy       True: copy, False: move
   *
   * @return  bool         True on success, false otherwise
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  protected function reuseImages(ImageTable $img, array $sources, bool $copy = false): bool
  {
    // Create services
    $this->component->createFileManager($img->catid);
    $this->component->createFilesystem($this->component->getConfig()->get('jg_filesystem','local-images'));

    // Fetch available imagetypes from destination
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->component, 'typename');

    // Check the source mapping
    if(\count(\array_diff_key($imagetypes, $sources)) !== 0 || \count(\array_diff_key($sources, $imagetypes)) !== 0)
    {
      $this->component->addLog('Imagetype mapping from migration script does not match component configuration!', 'error', 'migration');
      throw new \Exception('Imagetype mapping from migration script does not match component configuration!', 1);
    }

    // Update catid based on migrated categories
    $migrated_cats  = $this->get('migrateables')['category']->successful;
    $migrated_catid = $migrated_cats->get($img->catid);

    // Loop through all sources
    $error = false;
    foreach($imagetypes as $type => $tmp)
    {
      // Get image source path (with system root)
      $img_src = $sources[$type];

      // Get category destination path
      $cat_dst = $this->component->getFileManager()->getCatPath($migrated_catid, $type);

      // Create image destination path
      $img_dst = $cat_dst . '/' . $img->filename;

      // Create destination folder if not existent
      $folder_dst = \dirname($img_dst);
      try
      {
        $this->component->getFilesystem()->createFolder(\basename($folder_dst), \dirname($folder_dst));
      }
      catch(FileExistsException $e)
      {
        // Do nothing
      }
      catch(\Exception $e)
      {
        // Debug info
        $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($folder_dst)));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_CREATE_CATEGORY', \ucfirst($folder_dst)), 'error', 'migration');
        $error = true;

        continue;
      }

      // Move / Copy image
      try
      {
        if($this->component->getFilesystem()->get('filesystem') == 'local-images')
        {
          // Suorce and destination on the local filesystem
          if($img_src == Path::clean(JPATH_ROOT . '/' . $img_dst))
          {
            // Source and destination are identical. Do nothing.
            continue;
          }

          if($copy)
          {
            if(!File::copy($img_src, Path::clean(JPATH_ROOT . '/' . $img_dst)))
            {
              $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', \basename($img_src), $type));
              $this->component->addLog('Jg3ToJg4 - ' . 'Action File::copy: ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', \basename($img_src), $type), 'error', 'migration');
              $this->component->addLog('Jg3ToJg4 - ' . Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_TXT'), 'error', 'migration');
              $this->component->addLog('Jg3ToJg4 - ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_PATH', \dirname($img_src)), 'error', 'migration');
              $error = true;
              continue;
            }
          }
          else
          {
            if(!File::move($img_src, Path::clean(JPATH_ROOT . '/' . $img_dst)))
            {
              $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type));
              $this->component->addLog('Jg3ToJg4 - ' . 'Action File::move: ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type), 'error', 'migration');
              $this->component->addLog('Jg3ToJg4 - ' . Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_TXT'), 'error', 'migration');
              $this->component->addLog('Jg3ToJg4 - ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_PATH', \dirname($img_src)), 'error', 'migration');
              $error = true;
              continue;
            }
          }
        }
        else
        {
          // Destination not on the local filesystem. Upload required
          $this->component->getFilesystem()->createFile($img->filename, $cat_dst, \file_get_contents($img_src));

          if(!$copy)
          {
            // When image shall be moved, source have to be deleted
            File::delete($img_src);
          }
        }
      }
      catch(\Exception $e)
      {
        // Operation failed
        if($copy)
        {
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', \basename($img_src), $type));
          $this->component->addLog('Jg3ToJg4 - ' . 'Action copy: ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_COPY_IMAGETYPE', \basename($img_src), $type), 'error', 'migration');
          $this->component->addLog('Jg3ToJg4 - ' . Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_TXT'), 'error', 'migration');
          $this->component->addLog('Jg3ToJg4 - ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_PATH', \dirname($img_src)), 'error', 'migration');
        }
        else
        {
          $this->component->addDebug(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type));
          $this->component->addLog('Jg3ToJg4 - ' . 'Action move: ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_MOVE_IMAGETYPE', \basename($img_src), $type), 'error', 'migration');
          $this->component->addLog('Jg3ToJg4 - ' . Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_TXT'), 'error', 'migration');
          $this->component->addLog('Jg3ToJg4 - ' . Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERROR_FILE_NOT_FOUND_PATH', \dirname($img_src)), 'error', 'migration');
        }

        $error = true;

        continue;
      }
    }

    if($error)
    {
      return false;
    }
    else
    {
      return true;
    }
  }

  /**
   * Perform script specific checks at the end of pre and postcheck.
   *
   * @param  string   $type       Type of checks (pre or post)
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  public function scriptSpecificChecks(string $type, Checks &$checks, string $category)
  {
    $this->component->createConfig();

    if($type == 'pre' && $checks->getSuccess())
    {
      // Get source db info
      list($db, $dbPrefix)            = $this->getDB('source');
      list($tablename, $pkname)       = $this->getSourceTableInfo('image');
      list($cattablename, $catpkname) = $this->getSourceTableInfo('category');

      //------------------------

      // Check if imgfilename and imgthumbname are the same
      $query = $db->getQuery(true)
              ->select($db->quoteName(array('id')))
              ->from($db->quoteName($tablename))
              ->where($db->quoteName('imgfilename') . ' != ' . $db->quoteName('imgthumbname'));
      $db->setQuery($query);

      // Load a list of ids that have different values for imgfilename and imgthumbname
      $res = $db->loadColumn();

      if(!empty(\count($res)))
      {
        $checks->addCheck($category, 'src_table_image_filename', true, true, Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_IMAGE_FILENAMES_TITLE'), Text::sprintf('FILES_JOOMGALLERY_MIGRATION_CHECK_IMAGE_FILENAMES_DESC', \count($res)), Text::sprintf('FILES_JOOMGALLERY_MIGRATION_CHECK_IMAGE_FILENAMES_HELP', \implode(', ', $res)));
      }

      //------------------------

      if($this->params->get('new_dirs', 1) == 1)
      {
        // We want to use the new folder structure style
        // Check catpath of JG3 category table if they are consistent and convertible
        $query = $db->getQuery(true)
                ->select($db->quoteName(array('cid', 'alias', 'parent_id', 'catpath')))
                ->from($db->quoteName($cattablename))
                ->where($db->quoteName('level') . ' > 0 ');
        $db->setQuery($query);

        // Load a list of category objects
        $cats = $db->loadObjectList();

        // Check them for inconsistency
        $inconsistent = array();
        foreach($cats as $key => $cat)
        {
          if(!$this->checkCatpath($cat))
          {
            \array_push($inconsistent, $cat->cid);
          }
        }

        if(\count($inconsistent) > 0)
        {
          $checks->addCheck($category, 'src_table_cat_path', false, false, Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_CATEGORY_CATPATH'), Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_CATEGORY_CATPATH_DESC'), Text::sprintf('FILES_JOOMGALLERY_MIGRATION_CHECK_CATEGORY_CATPATH_HELP', \implode(', ', $inconsistent)));
        }

        // Check if compatibility mode is deactivated
        if($this->component->getConfig()->get('jg_compatibility_mode', 0) == 1)
        {
          $checks->addCheck($category, 'compatibility_mode', false, false, Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_COMPATIBILITY_MODE'), Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_COMPATIBILITY_MODE_OFF_DESC'));
        }
      }
      else
      {
        // We want to use the old folder structure style
        // Check if compatibility mode is activated
        if($this->component->getConfig()->get('jg_compatibility_mode', 0) == 0)
        {
          $checks->addCheck($category, 'compatibility_mode', false, false, Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_COMPATIBILITY_MODE'), Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_COMPATIBILITY_MODE_ON_DESC'));
          $this->component->addLog(Text::_('FILES_JOOMGALLERY_MIGRATION_CHECK_COMPATIBILITY_MODE_ON_DESC'), 'error', 'migration');
        }
      }

      //------------------------

      // Check use case: Direct usage
      if($this->params->get('image_usage', 0) == 0)
      {
        if($this->params->get('same_joomla', 1) == 0)
        {
          // Direct usage is not possible when source is outside this joomla installation
          $checks->addCheck($category, 'direct_usage_joomla', false, false, Text::_('COM_JOOMGALLERY_DIRECT_USAGE'), Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_ERROR'));
          $this->component->addLog(Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_ERROR'), 'error', 'migration');
        }
        else
        {
          $dest_imagetypes = JoomHelper::getRecords('imagetypes', $this->component);

          if(\count($dest_imagetypes) !== 3)
          {
            // Direct usage only possible with the three standard imagetypes
            $checks->addCheck($category, 'direct_usage_imgtypes', false, false, Text::_('COM_JOOMGALLERY_DIRECT_USAGE'), Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_IMGTYPES_ERROR'));
            $this->component->addLog(Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_IMGTYPES_ERROR'), 'error', 'migration');
          }
          else
          {
            // Make sure that original is deactivated is it was the case in JG3
            $checks->addCheck($category, 'direct_usage_orig', true, true, Text::_('COM_JOOMGALLERY_DIRECT_USAGE'), Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_ORIGINAL_WARNING'));
            $this->component->addLog(Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_ORIGINAL_WARNING'), 'error', 'migration');
          }

          $this->component->createConfig();
          if($this->component->getConfig()->get('jg_filesystem') !== 'local-images')
          {
            // Direct usage is only possible with local filesystem
            $checks->addCheck($category, 'direct_usage_local', false, false, Text::_('COM_JOOMGALLERY_DIRECT_USAGE'), Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_LOCAL_ERROR'));
            $this->component->addLog(Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_DIRECT_USAGE_LOCAL_ERROR'), 'error', 'migration');
          }
        }
      }

      //------------------------

      // Check use case: Move/Copy/Recreate at same joomla, local filesystem at old folder structure
      if($this->params->get('image_usage', 0) > 0)
      {
        if( $this->params->get('same_joomla', 1) == 1 &&
            $this->params->get('new_dirs', 1) == 0 &&
            $this->component->getConfig()->get('jg_filesystem', 'local-images') == 'local-images'
          )
        {
          // Move/Copy/Recreate is not possible since source and destination folders are identical
          $checks->addCheck($category, 'copy_identical_folders', false, false, Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_MCR'), Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_MCR_ERROR'));
          $this->component->addLog(Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_MCR_ERROR'), 'error', 'migration');
        }
      }

      //------------------------

      // Check use case: Deactivate alias uniqueness check during insertion
      if($this->params->get('unique_alias', 1) == 0)
      {
        $selection = $this->params->get('unique_alias_select', 'all');

        foreach($this->types as $name => $type)
        {
          if($type->get('insertRecord') && ($selection == 'all' || strpos($selection, $name) !== false))
          {
            // Alias uniqueness check is disabled for this content type
            // Alias will be created out of the title. Therefore title has to be unique such that aliases will become unique
            $query = $db->getQuery(true)
              ->select($db->quoteName(array($type->get('pk'), $type->get('title'))))
              ->from($db->quoteName($type->get('tablename')));
            $db->setQuery($query);

            // Load list of titles
            $titles = $db->loadAssocList($type->get('pk'), $type->get('title'));

            // Transform titles to possible aliases & look for doubles
            $unique = array();
            foreach($titles as $id => $title)
            {

              if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
              {
                $titles[$id] = OutputFilter::stringURLUnicodeSlug(trim($title));
              }
              else
              {
                $titles[$id] = OutputFilter::stringURLSafe(trim($title));
              }

              $uniqueKey = \array_search($titles[$id], $unique);
              if($uniqueKey === false)
              {
                // Looks like this is a unique alias. Move to unique array
                $unique[$id] = $titles[$id];
                unset($titles[$id]);
              }
              else
              {
                // This alias is already in unique array, but it shouldnt. Move it back
                $titles[$uniqueKey] = $unique[$uniqueKey];
                unset($unique[$uniqueKey]);
              }
            }

            // Add check result
            if(!empty($titles))
            {
              $checks->addCheck($category, 'alias_uniqueness_' . $name, true, true, Text::_('FILES_JOOMGALLERY_SERVICE_MIGRATION_ALIAS_UNIQUE'), Text::sprintf('FILES_JOOMGALLERY_SERVICE_MIGRATION_ALIAS_UNIQUE_ERROR', $type->get('recordName'), $type->get('tablename'), \implode(', ', \array_keys($titles))));
            }
          }
        }
      }
    }

    if($type == 'post' && $checks->getSuccess())
    {

    }

    return;
  }

  /**
   * Check if catpath is correct due to scheme 'parent-path/alias_cid'.
   *
   * @param   \stdClass   $cat   Category object
   *
   * @return  bool        True if catpath is correct, false otherwise
   *
   * @since   4.0.0
  */
  protected function checkCatpath(\stdClass $cat): bool
  {
    // Prepare catpath
    $catpath    = \basename($cat->catpath);
    $parentpath = \substr($cat->catpath, 0, -1 * \strlen('/'.$catpath));

    // Prepare alias
    $alias      = \basename($cat->alias);

    // Check for alias_cid
    if($catpath !== $alias.'_'.$cat->cid)
    {
      return false;
    }

    // Get path of parent category
    list($db, $dbPrefix)      = $this->getDB('source');
    list($tablename, $pkname) = $this->getSourceTableInfo('category');

    $query = $db->getQuery(true)
                ->select($db->quoteName('catpath'))
                ->from($db->quoteName($tablename))
                ->where($db->quoteName('cid') . ' = '. $db->quote($cat->parent_id));
    $db->setQuery($query);

    $path = $db->loadResult();

    // Check for parent-path
    if($parentpath !== $path)
    {
      return false;
    }

    return true;
  }
}
