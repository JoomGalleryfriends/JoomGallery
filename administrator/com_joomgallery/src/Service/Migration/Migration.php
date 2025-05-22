<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\Filesystem\Path;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Language\Text;
use \Joomla\Database\DatabaseFactory;
use \Joomla\Database\DatabaseInterface;
use \Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use \Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;
use \Joomgallery\Component\Joomgallery\Administrator\Table\MigrationTable;
use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\Checks;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Migration\MigrationInterface;

/**
 * Migration Base Class
 *
 * @package JoomGallery
 * @since   4.0.0
 */
abstract class Migration implements MigrationInterface
{
  use ServiceTrait;

  /**
	 * Storage for the migration form object.
	 *
	 * @var   Registry
	 *
	 * @since  4.0.0
	 */
	protected $params = null;

  /**
   * Storage for the source database driver object.
   *
   * @var    DatabaseInterface
   *
   * @since  4.0.0
   */
  protected $src_db = null;

  /**
	 * Storage for the migration info object.
	 *
	 * @var   object
	 *
	 * @since  4.0.0
	 */
	protected $info = null;

  /**
	 * Name of the migration script.
	 *
	 * @var   string
	 *
	 * @since  4.0.0
	 */
	protected $name = '';

  /**
   * True to offer the task migration.removesource for this script
   *
   * @var    boolean
   *
   * @since  4.0.0
   */
  protected $sourceDeletion = false;

  /**
   * Is the migration performed from the command line
   *
   * @var    boolean
   *
   * @since  4.0.0
   */
  protected $isCli = false;

  /**
   * List of content types which can be migrated with this script
   * Use the singular form of the content type (e.g image, not images)
   *
   * @var    Types[]
   *
   * @since  4.0.0
   */
  protected $types = array();

  /**
   * List of migrateables processed/migrated with this script
   *
   * @var    MigrationTable[]
   *
   * @since  4.0.0
   */
  protected $migrateables = array();

  /**
   * True, if the migration process of the current content type should be continued
   * False to stop the automatic migration process.
   *
   * @var    boolean
   *
   * @since  4.0.0
   */
  protected $continue = true;

  /**
   * Constructor
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct()
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Try to load language file of the migration script
    $this->app->getLanguage()->load('com_joomgallery.migration.'.$this->name, _JOOM_PATH_ADMIN);

    // Set logger
    $this->component->setLogger('migration');

    // Fill info object
    $this->info               = new \stdClass;
    $this->info->name         = $this->name;
    $this->info->title        = Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_TITLE');
    $this->info->description  = Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($this->name).'_DESC');
  }

  /**
	 * Destructor
	 *
	 * @return  void
   *
	 * @since  4.0.0
	 */
	public function __destruct()
	{
    // Reset logger to default
    $this->component->setLogger();
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
   * @return  array   The source types info, array(tablename, primarykey, isNested, isCategorized, , isFromSourceDB)
   *
   * @since   4.0.0
   */
  public function defineTypes($names_only=false, &$type=null): array
  {
    /* Example:
    $types = array( 'category' => array('#__joomgallery_catg', 'cid', 'name', true, false, true),
                    'image' =>    array('#__joomgallery', 'id', 'imgtitle', false, true, true)
                  );
    */

    return array();
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
    return $data;
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

    // Create the query
    $query->select($db->quoteName($primarykey))
          ->from($db->quoteName($tablename))
          ->order($db->quoteName($primarykey) . ' ASC');

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
      $query->order($db->quoteName('level') . ' ASC');
    }

    $db->setQuery($query);

    // Attempt to load the queue
    try
    {
      return $db->loadColumn();
    }
    catch(\Exception $e)
    {
      $this->component->setError($e->getMessage());

      return array();
    }
  }

  /**
   * Returns an associative array containing the record data from source.
   *
   * @param   string   $type   Name of the content type
   * @param   mixed    $pk     The primary key of the content type
   *
   * @return  array    Associated array of a record data
   *
   * @since   4.0.0
   */
  public function getData(string $type, $pk): array
  {
    $this->loadTypes();
    $pk = (int) $pk;

    if($this->get('types')[$type]->get('insertRecord'))
    {
      // When insertRecord is set to true, we assume that data gets loaded from source table
      list($tablename, $primarykey) = $this->getSourceTableInfo($type);

      // Get db object
      list($db, $prefix) = $this->getDB('source');
    }
    else
    {
      // We assume that this migration is just a data adjustment inside the destination table
      $tablename  = JoomHelper::$content_types[$this->get('types')[$type]->get('recordName')];
      $primarykey = 'id';

      // Get db object
      list($db, $prefix) = $this->getDB('destination');
    }

    // Initialize query object
    $query = $db->getQuery(true);

    // Create the query
    $query->select('*')
          ->from($db->quoteName($tablename))
          ->where($db->quoteName($primarykey) . ' = ' . $db->quote($pk));

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    // Attempt to load the array
    try
    {
      $data = $db->loadAssoc();

      // Load rules
      if($this->get('types')[$type]->get('insertRecord') && $this->params->get('with_rules', 0) > 0)
      {
        // load the rules and add them to the data array
        $this->getRulesData($type, $data);
      }

      return $data;
    }
    catch(\Exception $e)
    {
      $this->component->setError($e->getMessage());

      return array();
    }
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
    if(\array_key_exists('asset_id', $data))
    {
      // Get db object
      list($db, $prefix) = $this->getDB('source');

      // Initialize query object
      $query = $db->getQuery(true);

      // Create the query
      $query->select($db->quoteName('rules'))
            ->from($db->quoteName('#__assets'))
            ->where($db->quoteName('id') . ' = ' . $db->quote($data['asset_id']));

      // Reset the query using our newly populated query object.
      $db->setQuery($query);

      // Load rules
      $data['rules'] = $db->loadResult();
    }
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
    // Default: Recreate images based on source image
    $this->component->createFileManager($img->catid);

    // Get source image
    $img_source = $this->getImageSource($data);

    // Update catid based on migrated categories
    $migrated_cats  = $this->get('migrateables')['category']->successful;
    $migrated_catid = $migrated_cats->get($img->catid);

    // Create imagetypes
    return $this->component->getFileManager()->createImages($img_source, $img->filename, $migrated_catid);
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
    // Default: Create new folders
    $this->component->createFileManager($cat->id);
    return $this->component->getFileManager()->createCategory($cat->alias, $cat->parent_id);
  }

  /**
   * Returns a list of content types which can be migrated.
   *
   * @return  Migrationtable[]  List of content types
   *
   * @since   4.0.0
   */
  public function getMigrateables(): array
  {
    if(empty($this->migrateables))
    {
      // Get MigrationModel
      $model = $this->component->getMVCFactory()->createModel('migration', 'administrator');

      // Load migrateables
      $this->migrateables = $model->getItems();
    }

    return $this->migrateables;
  }

  /**
   * Returns an object of a specific content type which can be migrated.
   *
   * @param   string               $type       Name of the content type
   * @param   string               $withQueue  True to load the queue if not available
   *
   * @return  Migrationtable|bool  Object of the content types on success, false otherwise
   *
   * @since   4.0.0
   */
  public function getMigrateable(string $type, bool $withQueue = true)
  {
    if( !\key_exists($type, $this->migrateables) || empty($this->migrateables[$type]) ||
        ($withQueue && empty($this->migrateables[$type]->queue))
      )
    {
      // Get MigrationModel
      $model = $this->component->getMVCFactory()->createModel('migration', 'administrator');

      // Get list of migration ids
      $mig_ids = $model->getIdList();

      if(!empty($mig_ids))
      {
        // Detect id of the requested type
        $id = 0;
        foreach($mig_ids[$this->name] as $key => $mig)
        {
          if($mig->type == $type)
          {
            $id = $mig->id;
          }
        }

        // Load migrateable
        if($id > 0)
        {
          $this->migrateables[$type] = $model->getItem($id, $withQueue);

          return $this->migrateables[$type];
        }
      }

    }

    return false;
  }

  /**
   * Prepare the migration.
   *
   * @param   string   $type   Name of the content type
   *
   * @return  MigrationTable  The currently processed migrateable
   *
   * @since   4.0.0
   */
  public function prepareMigration(string $type): object
  {
    // Load migrateables to migration service
    $this->getMigrateables();

    // Set the migration parameters
    $migrateableKey = 0;
    foreach($this->migrateables as $key => $migrateable)
    {
      if($migrateable->type == $type)
      {
        $this->setParams($migrateable->params);
        $migrateableKey = $key;

        continue;
      }
    }

    return $this->migrateables[$migrateableKey];
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
    return;
  }

  /**
   * Step 2
   * Perform pre migration checks.
   *
   * @param   bool      $resumed  True, if the precheck is called during resuming the migration
   *
   * @return  object[]  An array containing the precheck results.
   *
   * @since   4.0.0
   */
  public function precheck($resumed = false): array
  {
    // Instantiate a new checks class
    $checks = new Checks();

    // Check general requirements
    $checks->addCategory('general', Text::_('COM_JOOMGALLERY_GENERAL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_GENERAL_PRECHECK_DESC'));
    $this->checkLogFile($checks, 'general');
    $this->checkSiteState($checks, 'general');

    // Check source extension (version, compatibility)
    $checks->addCategory('source', Text::_('COM_JOOMGALLERY_SOURCE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_PRECHECK_DESC'));
    $this->checkSourceExtension($checks, 'source');

    // Check existence and writeability of source directories
    $this->checkSourceDir($checks, 'source');

    // Check existence and integrity of source database tables
    $this->checkSourceTable($checks, 'source');

    // Check destination extension (version, compatibility)
    $checks->addCategory('destination', Text::_('COM_JOOMGALLERY_DESTINATION'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DESTINATION_PRECHECK_DESC'));
    $this->checkDestExtension($checks, 'destination');

    // Check existence and writeability of destination directories
    $this->checkDestDir($checks, 'destination');

    // Check existence and integrity of destination database tables
    $this->checkDestTable($checks, 'destination', $resumed);

    // Check image mapping
    if($this->params->get('image_usage', 0) > 1)
    {
      $this->checkImageMapping($checks, 'destination');
    }

    // Perform some script specific checks
    $this->scriptSpecificChecks('pre', $checks, 'general');

    return $checks->getAll();
  }

  /**
   * Step 4
   * Perform post migration checks.
   *
   * @return  object[]  An array containing the postcheck results.
   *
   * @since   4.0.0
   */
  public function postcheck()
  {
    // Instantiate a new checks class
    $checks = new Checks();

    // Check general migration
    $checks->addCategory('general', Text::_('COM_JOOMGALLERY_GENERAL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_GENERAL_POSTCHECK_DESC'));

    // Check if all queues have been addressed and migrated
    $this->checkMigrationQueues($checks, 'general');
    // Check if there are still errors in the migration
    $this->checkMigrationErrors($checks, 'general');

    // Check database
    // $checks->addCategory('database', Text::_('JLIB_FORM_VALUE_SESSION_DATABASE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DB_POSTCHECK_DESC'));
    // $this->checkCategories($checks, 'database');
    // $this->checkImages($checks, 'database');

    // Check filesystem
    // $checks->addCategory('directories', Text::_('COM_JOOMGALLERY_DIRECTORIES'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRS_POSTCHECK_DESC'));
    // $this->checkFolder($checks, 'directories');

    // Perform some script specific checks
    $this->scriptSpecificChecks('post', $checks, 'general');

    return $checks->getAll();
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
    return true;
  }

  /**
   * Get a database object
   *
   * @param   string   $target   The target (source or destination)
   *
   * @return  array    list($db, $dbPrefix)
   *
   * @since   4.0.0
   * @throws  \Exception
  */
  public function getDB(string $target): array
  {
    if(!in_array($target, array('source', 'destination')))
    {
      $this->component->addLog('Target has to be either "source" or "destination". Given: ' . $target, 'error', 'jerror');
      throw new \Exception('Target has to be either "source" or "destination". Given: ' . $target, 1);
    }

    if($target === 'destination' || $this->params->get('same_db', 1))
    {
      // Get database driver of the current joomla application
      $db        = Factory::getContainer()->get(DatabaseInterface::class);
      $dbPrefix  = $this->app->get('dbprefix');
    }
    else
    {
      // Get database driver for the source joomla database
      if(\is_null($this->src_db))
      {
        $options      = array ('driver' => $this->params->get('dbtype'), 'host' => $this->params->get('dbhost'), 'user' => $this->params->get('dbuser'), 'password' => $this->params->get('dbpass'), 'database' => $this->params->get('dbname'), 'prefix' => $this->params->get('dbprefix'));
        $dbFactory    = new DatabaseFactory();
        $this->src_db = $dbFactory->getDriver($this->params->get('dbtype'), $options);
      }

      $dbPrefix = $this->params->get('dbprefix');
      $db       = $this->src_db;
    }

    return array($db, $dbPrefix);
  }

  /**
   * Set params to object
   *
   * @param   mixed   $params   Array or object of params
   *
   * @since   4.0.0
  */
  public function setParams($params)
  {
    $this->params = new Registry($params);
  }

  /**
   * Returns the Joomla root path of the source.
   *
   * @return  string    Source Joomla root path
   *
   * @since   4.0.0
   */
  protected function getSourceRootPath(): string
  {
    if($this->params->get('same_joomla', 1))
    {
      $root = Path::clean(JPATH_ROOT . '/');
    }
    else
    {
      $root = Path::clean($this->params->get('joomla_path'));

      if(\substr($root, -1) != '/')
      {
        $root = Path::clean($root . '/');
      }
    }

    return $root;
  }

  /**
   * Loads all available content types to Migration object.
   * Gets available with the function defineTypes() from migration script.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function loadTypes()
  {
    if(empty($this->types))
    {
      if(\is_null($this->params))
      {
        $this->component->addLog('Migration parameters need to be set in order to load types.', 'error', 'jerror');
        throw new \Exception('Migration parameters need to be set in order to load types.', 1);
      }

      // First call of defineTypes(): Retrieve the array of source types info
      $types = $this->defineTypes();

      // Create Types objects
      foreach($types as $key => $list)
      {
        $type = new Type($key, $list);

        // Pass $type by reference
        // Next calls of defineTypes(): Define optional type infos
        $this->defineTypes(false, $type);

        $this->types[$key] = $type;
      }

      // Fill the dependent_of based on $types->dependent_on
      foreach($this->types as $key => $type)
      {
        $type->setDependentOf($this->types);
      }
    }
  }

  /**
   * Returns a list of involved source tables.
   *
   * @return  array    List of table names (Joomla style, e.g #__joomgallery)
   *                   array('image' => '#__joomgallery', ...)
   *
   * @since   4.0.0
   */
  public function getSourceTables(): array
  {
    $this->loadTypes();

    $tables = array();
    foreach($this->types as $key => $type)
    {
      $tables[$key] = $this->types[$key]->get('queueTablename');
    }

    if($this->params->get('with_rules', 0) > 0)
    {
      $tables['assets'] = '#__assets';
    }

    return $tables;
  }

  /**
   * Returns tablename and primarykey name of the source table
   *
   * @param   string   $type    The content type name
   *
   * @return  array   The corresponding source table info
   *                  list(tablename, primarykey)
   *
   * @since   4.0.0
   */
  public function getSourceTableInfo(string $type): array
  {
    $this->loadTypes();

    return array($this->types[$type]->get('tablename'), $this->types[$type]->get('pk'));
  }

  /**
   * Returns a list of content type names available in this migration script.
   *
   * @return  Type[]   List of type names
   *                   array('image', 'category', ...)
   *
   * @since   4.0.0
   */
  public function getTypeNames(): array
  {
    if(\is_null($this->params))
    {
      $this->component->addLog('Migration parameters need to be set in order to load types.', 'error', 'jerror');
      throw new \Exception('Migration parameters need to be set in order to load types.', 1);
    }

    $types = $this->defineTypes(true);

    return $types;
  }

  /**
   * Returns a type object based on type name.
   *
   * @param   string   $type   The content type name
   *
   * @return  Type     Type object
   *
   * @since   4.0.0
   */
  public function getType(string $name): Type
  {
    $this->loadTypes();

    return $this->types[$name];
  }

  /**
   * True if the given record has to be migrated
   * False to skip the migration for this record
   *
   * @param   string   $type   Name of the content type
   * @param   mixed    $pk     The primary key of the content type
   *
   * @return  bool     True to continue migration, false to skip it
   *
   * @since   4.0.0
   */
  public function needsMigration(string $type, $pk): bool
  {
    $this->loadTypes();

    // Content types that require another type being migrated completely
    if(!empty($this->types[$type]))
    {
      foreach($this->types[$type]->get('dependent_on') as $key => $req)
      {
        if(!$this->migrateables[$req] || !$this->migrateables[$req]->completed || $this->migrateables[$req]->failed->count() > 0)
        {
          $this->continue = false;
          $this->component->setError(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PREREQUIREMENT_ERROR', $req));
          $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PREREQUIREMENT_ERROR', $req), 'error', 'migration');
          $this->component->addLog('Fix the error and try the migration again.', 'error', 'migration');

          return false;
        }
      }
    }

    // Specific record primary keys which can be skipped
    foreach($this->types[$type]->get('pkstoskip') as $skip)
    {
      if($pk == $skip)
      {
        return false;
      }
    }

    return true;
  }

  /**
   * True if the given type needs the database counter
   * (counter row in #__joomgallery_migration)
   *
   * @param   string   $type   Name of the content type
   *
   * @return  bool     True if counter needed, false otherwise
   *
   * @since   4.0.0
   */
  public function needsCounter(string $type): bool
  {
    $this->loadTypes();

    if(!empty($this->types[$type]))
    {
      return $this->types[$type]->get('counterNeeded');
    }

    return false;
  }

  /**
   * Precheck: Check logfile and add check to checks array.
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkLogFile(Checks &$checks, string $category)
  {
    $log_dir  = Path::clean($this->app->get('log_path'));

    if(\is_dir($log_dir))
    {
      $log_file = Path::clean($log_dir . '/' . 'com_joomgallery.log.php');

      if(\is_file($log_file))
      {
        if(\is_writable($log_dir))
        {
          $checks->addCheck($category, 'log_file', true, false, Text::_('COM_JOOMGALLERY_LOGFILE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_SUCCESS', $log_file));
        }
        else
        {
          $checks->addCheck($category, 'log_file', false, false, Text::_('COM_JOOMGALLERY_LOGFILE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_ERROR', $log_file));
          $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGFILE_ERROR', $log_file), 'error', 'jerror');
        }
      }
      else
      {
        if(\is_writable($log_dir))
        {
          $checks->addCheck($category, 'log_dir', true, false, Text::_('COM_JOOMGALLERY_LOGDIRECTORY'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_SUCCESS', $log_dir));
        }
        else
        {
          $checks->addCheck($category, 'log_dir', false, false, Text::_('COM_JOOMGALLERY_LOGDIRECTORY'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_ERROR', $log_dir));
          $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LOGDIR_ERROR', $log_dir), 'error', 'jerror');
        }
      }
    }
    else
    {
      $checks->addCheck($category, 'log_dir', false, false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_LOG_DIR_LABEL'), Text::_('Logging directory not existent.'));
      $this->component->addLog(Text::_('Logging directory not existent.'), 'error', 'jerror');
    }

  }

  /**
   * Precheck: Check the source extension to be the correct one for this migration script
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceExtension(Checks &$checks, string $category)
  {
    $src_info = $this->getTargetinfo('source');

    if(!($src_xml = $this->getSourceXML()))
    {
      // Source XML not found
      $checks->addCheck($category, 'src_xml', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_XML', $src_xml));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_SOURCE_XML', $src_xml), 'error', 'jerror');
      return;
    }

    if(\version_compare(PHP_VERSION, $src_info->get('php_min'), '<'))
    {
      // PHP version not supported
      $checks->addCheck($category, 'src_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $src_info->get('php_min')));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $src_info->get('php_min')), 'error', 'jerror');
    }
    elseif(\strval($src_xml->name) !== $src_info->get('extension'))
    {
      // Wrong source extension
      $checks->addCheck($category, 'src_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', \strval($src_xml->name)));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', \strval($src_xml->name)), 'error', 'jerror');
    }
    elseif(\version_compare($src_xml->version, $src_info->get('min'), '<') || \version_compare($src_xml->version, $src_info->get('max'), '>'))
    {
      // Version not correct
      $checks->addCheck($category, 'src_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $src_xml->version, $src_info->get('min') . ' - ' . $src_info->get('max')));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $src_xml->version, $src_info->get('min') . ' - ' . $src_info->get('max')), 'error', 'jerror');
    }
    else
    {
      // Check successful
      $checks->addCheck($category, 'src_extension', true, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_SUCCESS', \strval($src_xml->name), $src_xml->version));
    }
  }

  /**
   * Precheck: Check the destination extension to be the correct one for this migration script
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestExtension(Checks &$checks, string $category)
  {
    $dest_info = $this->getTargetinfo('destination');
    $version   = \str_replace('-dev', '', $this->component->version);

    if(\version_compare(PHP_VERSION, $dest_info->get('php_min'), '<'))
    {
      // PHP version not supported
      $checks->addCheck($category, 'dest_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_SRC_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $dest_info->get('php_min')));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_PHP_WRONG_VERSION', PHP_VERSION, $dest_info->get('php_min')), 'error', 'jerror');
    }
    elseif(\strval($this->component->xml->name) !== $dest_info->get('extension'))
    {
      // Wrong destination extension
      $checks->addCheck($category, 'dest_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', \strval($this->component->xml->name)));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_NOT_SUPPORTED', \strval($this->component->xml->name)), 'error', 'jerror');
    }
    elseif(\version_compare($version, $dest_info->get('min'), '<') || \version_compare($version, $dest_info->get('max'), '>'))
    {
      // Version not correct
      $checks->addCheck($category, 'dest_extension', false, false, Text::_('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $this->component->version, $dest_info->get('min') . ' - ' . $dest_info->get('max')));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_WRONG_VERSION', $this->component->version, $dest_info->get('min') . ' - ' . $dest_info->get('max')), 'error', 'jerror');
    }
    else
    {
      // Check successful
      $checks->addCheck($category, 'dest_extension', true, false, Text::_('COM_JOOMGALLERY_FIELDS_DEST_EXTENSION_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_EXTENSION_SUCCESS', \strval($this->component->xml->name), $this->component->version));
    }
  }

  /**
   * Precheck: Check site state and add check to checks array.
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSiteState(Checks &$checks, string $category)
  {
    // Check that site is offline
    if($this->app->get('offline'))
    {
      $checks->addCheck($category, 'offline', true, false, Text::_('COM_JOOMGALLERY_SITE_OFFLINE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_OFFLINE_SUCCESS'));
    }
    else
    {
      $checks->addCheck($category, 'offline', false, false, Text::_('COM_JOOMGALLERY_SITE_OFFLINE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_OFFLINE_ERROR'), 'prechecks/offline');
    }

    // Put check about session lifetime
    if($this->app->get('lifetime') < 30)
    {
      $time_min = $this->app->get('lifetime');
      $time_hr  = \round($time_min / 60, 1);
      $checks->addCheck($category, 'lifetime', true, true, Text::_('COM_JOOMGALLERY_SITE_LIFETIME'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_LIFETIME_ERROR', $time_min, $time_hr));
    }
  }

  /**
   * Precheck: Check directories of the source to be existent
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceDir(Checks &$checks, string $category)
  {
    // Retrieve a list of source directories involved in migration
    $directories = $this->getSourceDirs();
    $root        = $this->getSourceRootPath();

    $dirs_checked = array();
    foreach($directories as $dir)
    {
      // Make sure, we check each directory only once
      if(!\in_array($dir, $dirs_checked))
      {
        \array_push($dirs_checked, $dir);
      }
      else
      {
        // Table already checked. Skip check.
        continue;
      }

      $check_name = 'src_dir_' . \basename($dir);

      if(!\is_dir($root . $dir))
      {
        // Path is not a directory
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $dir, Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_A_DIRECTORY'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_A_DIRECTORY'), 'error', 'jerror');
      }
      else
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $dir, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRECTORY_SUCCESS'));
      }
    }
  }

  /**
   * Precheck: Check directories of the destination to be existent and writeable
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestDir(Checks &$checks, string $category)
  {
    // Instantiate filesystem service
    $this->component->createFilesystem($this->component->getConfig()->get('jg_filesystem','local-images'));

    // Get all imagetypes
    $imagetypes = JoomHelper::getRecords('imagetypes', $this->component);

    $dirs_checked = array();
    foreach($imagetypes as $imagetype)
    {
      // Make sure, we check each directory only once
      if(!\in_array($imagetype, $dirs_checked))
      {
        \array_push($dirs_checked, $imagetype);
      }
      else
      {
        // Table already checked. Skip check.
        continue;
      }

      $check_name = 'dest_dir_' . $imagetype->typename;
      $error      = false;

      try
      {
        $dir_info = $this->component->getFilesystem()->getFile($imagetype->path);
      }
      catch(FileNotFoundException $msg)
      {
        // Path doesn't exist
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::_('COM_JOOMGALLERY_ERROR_PATH_NOT_EXISTING'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_PATH_NOT_EXISTING'), 'error', 'jerror');
        $error = true;
      }
      catch(\Exception $msg)
      {
        // Error in filesystem
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_ERROR', $msg));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_ERROR', $msg), 'error', 'jerror');
        $error = true;
      }

      if(!$error)
      {
        if($dir_info->type !== 'dir')
        {
          // Path is not a directory
          $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_A_DIRECTORY'));
          $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_ERROR_FILESYSTEM_NOT_A_DIRECTORY'), 'error', 'jerror');
        }
        else
        {
          $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_DIRECTORY') . ': ' . $imagetype->path, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_DIRECTORY_SUCCESS'));
        }
      }
    }
  }

  /**
   * Precheck: Check db and tables of the source
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkSourceTable(Checks &$checks, string $category)
  {
    list($db, $dbPrefix) = $this->getDB('source');

    // Check connection to database
    try
    {
      $tableList = $db->getTableList();
    }
    catch (\Exception $msg)
    {
      $checks->addCheck($category, 'src_table_connect', true, Text::_('JLIB_FORM_VALUE_SESSION_DATABASE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_TABLE_CONN_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_TABLE_CONN_ERROR'), 'error', 'jerror');
      return;
    }

    // Check required tables
    $tables = $this->getSourceTables();
    $tables_checked = array();
    foreach($tables as $tablename)
    {
      // Make sure, we check each table only once
      if(!\in_array($tablename, $tables_checked))
      {
        \array_push($tables_checked, $tablename);
      }
      else
      {
        // Table already checked. Skip check.
        continue;
      }

      $check_name = 'src_table_' . $tablename;

      // Check if required tables exists
      if(!\in_array(\str_replace('#__', $dbPrefix, $tablename), $tableList))
      {
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_ERROR_TABLE_NOT_EXISTING'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_TABLE_NOT_EXISTING'), 'error', 'jerror');
        continue;
      }

      $query = $db->getQuery(true)
              ->select('COUNT(*)')
              ->from($tablename);
      $db->setQuery($query);

      $count = $db->loadResult();

      // Check number of records in tables
      $check_name = 'dest_table_' . $tablename . '_count';
      if($count == 0)
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_EMPTY'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_EMPTY'), 'info', 'jerror');
      }
      else
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES', $count));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES'), 'info', 'jerror');
      }
    }
  }

  /**
   * Precheck: Check db and tables of the destination
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   * @param  bool     $resumed    True, if the precheck is called during resuming the migration
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestTable(Checks &$checks, string $category, bool $resumed = false)
  {
    // Get table info
    list($db, $dbPrefix) = $this->getDB('destination');
    $tables              = JoomHelper::$content_types;
    $tableList           = $db->getTableList();

    // Check whether root category exists
    $rootCat = false;
    $query   = $db->getQuery(true)
          ->select('COUNT(*)')
          ->from($db->quoteName(_JOOM_TABLE_CATEGORIES))
          ->where($db->quoteName('id') . ' = 1')
          ->where($db->quoteName('title') . ' = ' . $db->quote('Root'))
          ->where($db->quoteName('parent_id') . ' = 0');
    $db->setQuery($query);

    if($db->loadResult())
    {
      $checks->addCheck($category, 'dest_root_cat', true, false, Text::_('COM_JOOMGALLERY_ROOT_CATEGORY'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_SUCCESS'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_SUCCESS'), 'info', 'jerror');
      $rootCat = true;
    }
    else
    {
      $checks->addCheck($category, 'dest_root_cat', false, false, Text::_('COM_JOOMGALLERY_ROOT_CATEGORY'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ERROR'), 'error', 'jerror');
    }

    // Check whether root asset exists
    $query = $db->getQuery(true)
          ->select('id')
          ->from($db->quoteName('#__assets'))
          ->where($db->quoteName('name') . ' = ' . $db->quote(_JOOM_OPTION))
          ->where($db->quoteName('parent_id') . ' = 1');
    $db->setQuery($query);

    if($rootAssetID = $db->loadResult())
    {
      $checks->addCheck($category, 'dest_root_asset', true, false, Text::_('COM_JOOMGALLERY_ROOT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_ASSET_SUCCESS'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_ASSET_SUCCESS'), 'info', 'jerror');
    }
    else
    {
      $checks->addCheck($category, 'dest_root_asset', false, false, Text::_('COM_JOOMGALLERY_ROOT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_ASSET_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_ASSET_ERROR'), 'warning', 'jerror');
    }

    // Check whether root category asset exists
    $query = $db->getQuery(true)
          ->select('id')
          ->from($db->quoteName('#__assets'))
          ->where($db->quoteName('name') . ' = ' . $db->quote('com_joomgallery.category.1'))
          ->where($db->quoteName('parent_id') . ' = ' . $db->quote($rootAssetID));
    $db->setQuery($query);

    if($db->loadResult())
    {
      $checks->addCheck($category, 'dest_root_cat_asset', true, false, Text::_('COM_JOOMGALLERY_ROOT_CAT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ASSET_SUCCESS'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ASSET_SUCCESS'), 'info', 'jerror');
    }
    else
    {
      $checks->addCheck($category, 'dest_root_cat_asset', false, false, Text::_('COM_JOOMGALLERY_ROOT_CAT_ASSET'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ASSET_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ROOT_CAT_ASSET_ERROR'), 'warning', 'jerror');
    }

    // Check required tables
    $tables_checked = array();
    foreach($tables as $tablename)
    {
      // Make sure, we check each table only once
      if(!\in_array($tablename, $tables_checked))
      {
        \array_push($tables_checked, $tablename);
      }
      else
      {
        // Table already checked. Skip check.
        continue;
      }

      $check_name = 'dest_table_' . $tablename;

      // Check if required tables exists
      if(!\in_array( \str_replace('#__', $dbPrefix, $tablename), $tableList))
      {
        $checks->addCheck($category, $check_name, false, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_ERROR_TABLE_NOT_EXISTING'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_TABLE_NOT_EXISTING'), 'error', 'jerror');
        continue;
      }

      // Check number of records in tables
      $query = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($tablename);
      $db->setQuery($query);

      $count = $db->loadResult();

      if($tablename == _JOOM_TABLE_CATEGORIES && $rootCat)
      {
        $count = $count - 1;
      }

      $check_name = 'dest_table_' . $tablename . '_count';
      if($count == 0)
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_EMPTY'));
      }
      elseif($this->params->get('source_ids', 0) > 0 && $count > 0 && !$resumed)
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES', $count));
        $this->checkDestTableIdAvailability($checks, $category, $tablename);
      }
      else
      {
        $checks->addCheck($category, $check_name, true, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES', $count));
      }
    }
  }

  /**
   * Precheck: Check destination tables for already existing ids
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   * @param  string   $tablename  The table to be checked
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkDestTableIdAvailability(Checks &$checks, string $category, string $tablename)
  {
    // Get content type to check
    $type = '';
    foreach(JoomHelper::$content_types as $type => $table)
    {
      if($table === $tablename)
      {
        break;
      }

      $type = '';
    }

    // Get migrateable to check
    $this->getMigrateables();
    $migrateable = null;
    foreach($this->migrateables as $key => $migrateable)
    {
      if($migrateable->get('type', false) === $type)
      {
        break;
      }

      $migrateable = null;
    }

    if(!$migrateable)
    {
      // Table does not correspond to a migrateable. Exit method.
      return;
    }

    // Get destination database
    list($db, $dbPrefix) = $this->getDB('destination');

    // Get a list of used ids from destination database
    $destQuery = $db->getQuery(true);
    $destQuery->select($db->quoteName('id'))
              ->from($db->quoteName($tablename));
    $destQuery_string = \trim($destQuery->__toString());

    if($this->params->get('same_db', 1))
    {
      // Get list of used ids from source database
      $srcQuery = $db->getQuery(true);
      $srcQuery->select($db->quoteName($migrateable->get('src_pk'), 'id'))
               ->from($db->quoteName($migrateable->get('src_table')));
      $srcQuery_string = \trim($srcQuery->__toString());

      // Get a list of ids used in both source and destination
      $query = $db->getQuery(true);
      $query->select($db->quoteName('ids.id'))
          ->from('(' . $srcQuery_string . ') ids')
          ->where($db->quoteName('ids.id') . ' IN (' . $destQuery_string . ')');
      $db->setQuery($query);
    }
    else
    {
      // Get source database
      list($src_db, $src_dbPrefix) = $this->getDB('source');

      // Get list of used ids from the source database
      $query = $src_db->getQuery(true);
      $query->select($db->quoteName($migrateable->get('src_pk'), 'id'))
            ->from($db->quoteName($migrateable->get('src_table')));
      $src_db->setQuery($query);

      // Load list from source database
      $src_list = $src_db->loadColumn();

      if(\count($src_list) < 1)
      {
        // There are no records in the source tabele. Exit method.
        return;
      }

      // Create UNION query string
      foreach($src_list as $i => $id)
      {
        ${'query' . $i} = $db->getQuery(true);
        ${'query' . $i}->select($db->quote($id) . ' AS ' . $db->quoteName('id'));
        if($i > 0)
        {
          $query0->unionAll(${'query' . $i});
        }
      }
      $srcQuery_string = \trim($query0->__toString());

      // Get a list of ids used in both source and destination
      $query = $db->getQuery(true);
      $query->select($db->quoteName('ids.id'))
          ->from('(' . $srcQuery_string . ') ids')
          ->where($db->quoteName('ids.id') . ' IN (' . $destQuery_string . ')');
      $db->setQuery($query);
    }

    // Load list of Id's used in both tables (source and destination)
    $list = $db->loadColumn();

    // Exception for root category
    if($tablename == _JOOM_TABLE_CATEGORIES)
    {
      $list = \array_diff($list, array(1, '1'));
    }

    if(!empty($list))
    {
      $checks->addCheck($category, 'dest_table_' . $tablename . '_ids', false, false, Text::_('COM_JOOMGALLERY_TABLE') . ': ' . $tablename, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_TABLES_USE_IDS_HINT', \implode(',', $list)));
    }
  }

  /**
   * Precheck: Check the configured image mapping
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkImageMapping(Checks &$checks, string $category)
  {
    $mapping         = $this->params->get('image_mapping');
    $dest_imagetypes = JoomHelper::getRecords('imagetypes', $this->component);
    $src_imagetypes  = array();

    // Check if mapping contains enough elements
    if(\count((array)$mapping) != \count($dest_imagetypes))
    {
      $checks->addCheck($category, 'mapping_count', false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MAPPING_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MAPPING_ERROR'), 'error', 'jerror');
      return;
    }

    // Load source imagetypes from xml file
    $xml     = \simplexml_load_file(JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/src/Service/Migration/Scripts/'. $this->name . '.xml');
    $element = $xml->xpath('/form/fieldset/field[@name="image_mapping"]/form/field[@name="source"]');

    foreach($element[0]->option as $option)
    {
      \array_push($src_imagetypes, (string) $option['value']);
    }

    // Prepare destination imagetypes
    $tmp_dest_imagetypes = array();
    foreach($dest_imagetypes as $key => $type)
    {
      \array_push($tmp_dest_imagetypes, (string) $type->typename);
    }

    // Check if all imagetypes are correctly set in the mapping
    foreach($mapping as $key => $mapVal)
    {
      if(\in_array($mapVal->destination, $tmp_dest_imagetypes))
      {
        // Remove imagetype from tmp_dest_imagetypes array
        $tmp_dest_imagetypes = \array_diff($tmp_dest_imagetypes, array($mapVal->destination));
      }
      else
      {
        // Destination imagetype in mapping does not exist
        $checks->addCheck($category, 'mapping_dest_types_'.$mapVal->destination, false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_DEST_IMAGETYPE_NOT_EXIST', Text::_('COM_JOOMGALLERY_' . \strtoupper($mapVal->destination))));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_DEST_IMAGETYPE_NOT_EXIST', Text::_('COM_JOOMGALLERY_' . \strtoupper($mapVal->destination))), 'error', 'jerror');
        return;
      }

      if(!\in_array($mapVal->source, $src_imagetypes))
      {
        // Source imagetype in mapping does not exist
        $checks->addCheck($category, 'mapping_src_types_'.$mapVal->source, false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_IMAGETYPE_NOT_EXIST', Text::_('COM_JOOMGALLERY_' . \strtoupper($mapVal->source))));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_IMAGETYPE_NOT_EXIST', Text::_('COM_JOOMGALLERY_' . \strtoupper($mapVal->source))), 'error', 'jerror');
        return;
      }
    }

    if(!empty($tmp_dest_imagetypes))
    {
      // Destination imagetype not used in the mapping
      $checks->addCheck($category, 'mapping_dest_types', false, false, Text::_('COM_JOOMGALLERY_FIELDS_IMAGEMAPPING_LABEL'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_IMAGETYPE_NOT_USED', \implode(', ', $tmp_dest_imagetypes)));
      $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_MAPPING_IMAGETYPE_NOT_USED', \implode(', ', $tmp_dest_imagetypes)), 'warning', 'jerror');
    }
  }

  /**
   * Postcheck: Check if all queues are completed
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkMigrationQueues(Checks &$checks, string $category)
  {
    $types        = $this->getTypeNames();
    $migrateables = $this->getMigrateables();

    // Check if all types are existent in migrateables
    if(\count(\array_diff($types, \array_keys($migrateables))) > 0)
    {
      $checks->addCheck($category, 'types_migrated', false, false, Text::_('COM_JOOMGALLERY_MIGRATIONS'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MIGRATIONS_ERROR'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MIGRATIONS_ERROR'), 'error', 'jerror');
      return;
    }

    // Check if all queues in migrateables are empty
    $empty = true;
    foreach($migrateables as $key => $mig)
    {
      if(\count($mig->queue) > 0)
      {
        $checks->addCheck($category, 'queue_' . $mig->type, false, false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_QUEUE'), Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MIGRATIONS_ERROR', $mig->type));
        $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MIGRATIONS_ERROR', $mig->type), 'error', 'jerror');
        $empty = false;
      }
    }

    if($empty)
    {
      $checks->addCheck($category, 'queues', true, false, Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_QUEUE'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_COUNT_MIGRATIONS_SUCCESS'));
    }
  }

  /**
   * Postcheck: Check if all migrateables are error free
   *
   * @param  Checks   $checks     The checks object
   * @param  string   $category   The checks-category into which to add the new check
   *
   * @return  void
   *
   * @since   4.0.0
  */
  protected function checkMigrationErrors(Checks &$checks, string $category)
  {
    $migrateables = $this->getMigrateables();

    // Check if all migrateables are error free
    $errors = false;
    foreach($migrateables as $key => $mig)
    {
      if($mig->failed->count() > 0)
      {
        foreach($mig->failed->toArray()as $id => $error)
        {
          $checks->addCheck($category, 'error_' . $mig->type . '_' . $id, false, false, Text::_('ERROR') . ': ' . $mig->type, Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERRORS_ERROR', $mig->type, $id, $error));
          $this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SERVICE_MIGRATION_ERRORS_ERROR', $mig->type, $id, $error), 'error', 'jerror');
          $errors = true;
        }
      }
    }

    if(!$errors)
    {
      $checks->addCheck($category, 'errors', true, false, Text::_('SUCCESS'), Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ERRORS_SUCCESS'));
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
    return;
  }

  /**
   * Converts a data array based on a mapping.
   *
   * @param   array   $data     Data received from getData() method.
   * @param   array   $mapping  Mapping array telling how to convert data.
   * @param   string  $pk_name  Name of the destination primary key. (default: 'id')
   *
   * @return  array   Converted data array
   *
   * @since   4.0.0
   */
  protected function applyConvertData(array $data, array $mapping, string $pk_name = 'id'): array
  {
    // Convert data only if a mapping is available
    if(empty($mapping))
    {
      return $data;
    }

    // Loop through the data provided
    foreach($data as $key => $value)
    {
      // Key not in the mapping array --> Nothing to do.
      if(!\key_exists($key, $mapping))
      {
        continue;
      }

      // Mapping from an old to a new key ('old key' => 'new key')
      if(\is_string($mapping[$key]) && !empty($mapping[$key]))
      {
        $data[$mapping[$key]] = $value;

        if($key !== $mapping[$key])
        {
          unset($data[$key]);
        }

        continue;
      }

      // Remove content from data array element ('old key' => false)
      if($mapping[$key] === false)
      {
        $data[$key] = null;

        continue;
      }

      // Content gets merged into anothter data array element ('old key' => array(string, string, bool))
      // array('destination field name', 'new field name', 'create child')
      if(\is_array($mapping[$key]))
      {
        $destFieldName = $mapping[$key][0];

        // Prepare destField
        if(!\key_exists($destFieldName, $data) || empty($data[$destFieldName]))
        {
          // Field does not exist or is empty
          $data[$destFieldName] = new Registry();
        }
        elseif(!($data[$destFieldName] instanceof Registry))
        {
          // Field exists and is already of type Registry
          $data[$destFieldName] = new Registry($data[$destFieldName]);
        }

        // Create new field name
        $newKey = $key;
        if(\count($mapping[$key]) > 1 && !empty($mapping[$key][1]))
        {
          $newKey = $mapping[$key][1];
        }

        // Prepare srcField
        if(\count($mapping[$key]) > 2 && !empty($mapping[$key][2]))
        {
          // Add as a child node
          $child = new Registry($value);
          $value = new Registry(array($newKey=> $child));
        }
        else
        {
          // Add directly
          $srclength = 1;
          $isJson    = false;

          // Detect we have a json string
          if(\is_string($value))
          {
            \json_decode($value);
            if(json_last_error() === JSON_ERROR_NONE)
            {
              $isJson = true;
            }
          }

          // Get source length
          elseif(\is_array($value))
          {
            $srclength = \count($value);
          }
          elseif(\is_object($value))
          {
            if($value instanceof \Countable)
            {
              $srclength = $value->count();
            }
            else
            {
              $srclength = \count(\get_object_vars($value));
            }
          }

          if($srclength > 1 || $isJson)
          {
            // We are trying to add a json or an object directly without adding a child
            // Here 'new field name' has no effect
            $value = new Registry($value);
          }
          else
          {
            if(\is_array($value))
            {
              $value = $value[0];
            }
            elseif(\is_object($value))
            {
              $keys = \array_keys(\get_object_vars($value));
              $value = $value[$keys[0]];
            }

            // Create registry with only one key value pair
            $value = new Registry(array($newKey=> $value));
          }
        }

        // Apply merge
        $data[$destFieldName]->merge($value);

        if($key != $destFieldName)
        {
          unset($data[$key]);
        }

        continue;
      }

      // Add a static content
      if(\is_string($mapping[$key]) || \is_numeric($mapping[$key]))
      {
        $data[$key] = $mapping[$key];

        continue;
      }
    }

    // Make sure the primary key field is available in the data array
    if(!\array_key_exists($pk_name, $data))
    {
      $data[$pk_name] = null;
    }

    return $data;
  }
}
