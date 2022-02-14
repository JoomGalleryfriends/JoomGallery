<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

define('MODIFIED', 1);
define('NOT_MODIFIED', 2);

defined('_JEXEC') or die();

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Installer\Installer;
use \Joomla\CMS\Installer\InstallerScript;
use \Joomla\CMS\Filesystem\File;
use \Joomla\CMS\Filesystem\Folder;

/**
 * Install method
 * is called by the installer of Joomla!
 *
 * @return  void
 * @since   4.0.0
 */
class com_joomgalleryInstallerScript extends InstallerScript
{
	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var string
	 */
	protected $extension = 'JoomGallery';

  /**
	 * Minimum PHP version required to install the extension
	 *
	 * @var    string
	 * @since  3.6
	 */
	protected $minimumPhp = '7.3.0';

	/**
	 * Minimum Joomla! version required to install the extension
	 *
	 * @var    string
	 * @since  3.6
	 */
	protected $minimumJoomla = '4.0.0';

  /**
   * Release code of the currently installed version
   *
   * @var string
   */
  protected $act_code = '';

  /**
   * Release code of the new version to be installed
   *
   * @var string
   */
  protected $new_code = '';


	/**
	 * Method called before install/update the component. Note: This method won't be called during uninstall process.
	 *
	 * @param   string $type   Type of process [install | update]
	 * @param   mixed  $parent Object who called this method
	 *
	 * @return boolean True if the process should continue, false otherwise
   * @throws Exception
	 */
	public function preflight($type, $parent)
	{
		$result = parent::preflight($type, $parent);

		if (!$result)
		{
			return $result;
		}

    if ($type == 'update')
    {
      // save release code information
      //-------------------------------
      if (File::exists(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'joomgallery.xml'))
      {
        $xml = simplexml_load_file(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'joomgallery.xml');
        $this->act_code = $xml->version;
      }
      else
      {
        Factory::getApplication()->enqueueMessage(Text::_('Unable to read JoomGallery manifest XML file.'), 'note');
      }

      $this->new_code    = $parent->getManifest()->version;
    }

		// logic for preflight before install
		return $result;
	}

	/**
	 * Method to install the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 *
	 * @since 0.2b
	 */
	public function install($parent)
	{
    $app = Factory::getApplication();
    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $install_message = $this->getInstallerMSG($act_version, $new_version, 'install');

    // Create default Category
    if(!$this->addDefaultCategory())
    {
      $app->enqueueMessage(Text::_('Unable to create default category', 'error'));
    }

    // Create image types
    $img_types = array('original'  => array('path' => '/images/joomgallery/originals', 'alias' => 'orig'),
                       'detail'    => array('path' => '/images/joomgallery/details', 'alias' => 'det'),
                       'thumbnail' => array('path' => '/images/joomgallery/thumbnails', 'alias' => 'thumb')
                      );
    $this->count = 0;
    foreach ($img_types as $key => $type)
    {
      // Create default Image types records
      if(!$this->addDefaultIMGtype($key, $type['alias'], $type['path']))
      {
        $app->enqueueMessage(Text::_('Unable to create image type: '.$key, 'error'));
      }

      // Create default Image types directories
      if(!Folder::create(JPATH_ROOT.$type['path'].'/uncategorised'))
      {
        $app->enqueueMessage(Text::_('Unable to create image directory for image type: ').$key, 'error');
      }
      $this->count = $this->count + 1;
    }

    // Create default Configuration-Set
    if(!$this->addDefaultConfig())
    {
      $app->enqueueMessage(Text::_('Unable to create default configuration set', 'error'));
    }

		//$this->installDb($parent);
		$this->installPlugins($parent);
		$this->installModules($parent);
    ?>

    <div class="text-center">
      <img src="../media/com_joomgallery/images/joom_logo.png" alt="JoomGallery Logo">
      <p></p>
      <div class="alert alert-light">
        <h3>JoomGallery <?php echo $parent->getManifest()->version;?> was installed successfully.</h3>
        <p>You may now start using JoomGallery or download specific language files afore:</p>
        <p>
          <a title="Start" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery'; return false;" href="#">
            Start now!</a>
          <a title="Languages" class="btn btn-outline-primary" onclick="location.href='index.php?option=com_joomgallery&controller=help'; return false;" href="#">
            Languages</a>
        </p>
        <?php if ($install_message != '') : ?>
          <div><?php echo $install_message;?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php
	}

  /**
	 * Method to update the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 */
	public function update($parent)
	{
    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $update_message = $this->getInstallerMSG($act_version, $new_version, 'update');

		//$this->installDb($parent);
		$this->installPlugins($parent);
		$this->installModules($parent);
    ?>

    <div class="text-center">
      <img src="../media/com_joomgallery/images/joom_logo.png" alt="JoomGallery Logo">
      <p></p>
      <div class="alert alert-light">
        <h3>JoomGallery was updated to version <?php echo $parent->getManifest()->version; ?> successfully.</h3>
        <p>
          <button class="btn btn-small btn-info" data-toggle="modal" data-target="#jg-changelog-popup"><i class="icon-list"></i> Changelog</button>
        </p>
        <p>You may now start using JoomGallery or download specific language files afore:</p>
        <p>
          <a title="Start" class="btn btn-primary" onclick="location.href='index.php?option=com_joomgallery'; return false;" href="#">
            Start now!</a>
          <a title="Languages" class="btn btn-outline-primary" onclick="location.href='index.php?option=com_joomgallery&controller=help'; return false;" href="#">
            Languages</a>
        </p>
        <?php if ($update_message != '') : ?>
          <div><?php echo $update_message;?></div>
        <?php endif; ?>
      </div>
    </div>

    <div id="jg-changelog-popup" class="modal fade" tabindex="-1" aria-labelledby="PopupChangelogModalLabel">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="PopupChangelogModalLabel" class="modal-title">Changelog</h5>
            <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close">&times;</button>
          </div>
          <div class="modal-body">
            <iframe class="iframe" frameborder="0" src="<?php echo Route::_('index.php?option=com_joomgallery&controller=changelog&tmpl=component'); ?>" height="400px" width="100%"></iframe>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" data-dismiss="modal"><?php echo Text::_('JTOOLBAR_CLOSE'); ?></button>
          </div>
        </div>
      </div>
    </div>

    <?php
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   mixed $parent Object who called this method.
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
    $app = Factory::getApplication();
    $act_version = explode('.',$this->act_code);
    $new_version = explode('.',$this->new_code);

    $uninstall_message = $this->getInstallerMSG($act_version, $new_version, 'uninstall');

		$this->uninstallPlugins($parent);
		$this->uninstallModules($parent);

    // Delete directories
    if(!Folder::delete(JPATH_ROOT.'/images/joomgallery'))
    {
      $app->enqueueMessage(Text::_('Unable to delete image directory (/images/joomgallery)'), 'error');
    }
    ?>

    <div class="text-center">
      <div class="alert alert-light">
        <h3>JoomGallery was uninstalled successfully!</h3>
        <p>Please remember to remove your images folders manually if you didn't use JoomGallery's default directories.</p>

        <?php if ($uninstall_message != '') : ?>
          <div><?php echo $uninstall_message;?></div>
        <?php endif; ?>
      </div>
    </div>

    <?php
	}

  /**
	 * Add a category to the ´#__joomgallery_categories´ table
   *
	 * @return  bool  true on success
	 */
	public function addDefaultCategory()
	{
    $db = Factory::getDbo();

    Table::addIncludePath(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR);
    JLoader::register('\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\CategoryTable', JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR);

    $table = Table::getInstance('CategoryTable', '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\');

    $data = array();
    $data["id"] = NULL;
    $data["asset_id"] = NULL;
    $data["parent_id"] = 1;
    $data["level"] = 1;
    $data["path"] = "uncategorised";
    $data["title"] = "Uncategorised";
    $data["alias"] = "uncategorised";
    $data["description"] = "";
    $data["access"] = 1;
    $data["published"] = 1;
    $data["params"] = '{"allow_download":"-1","allow_comment":"-1","allow_rating":"-1","allow_watermark":"-1","allow_watermark_download":"-1"}';
    $data["language"] = "*";
    $data["metadesc"] = "";
    $data["metakey"] = "";

    if (!$table->bind($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error bind category'), 'error');

      return false;
    }
    if (!$table->store($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error store category'), 'error');

      return false;
    }

    // Set level and parent_id
    $fields = array(
      $db->quoteName('parent_id') . ' = ' . $db->quote($data['parent_id']),
      $db->quoteName('level') . ' = ' . $db->quote($data['level'])
    );
    $conditions = array (
      $db->quoteName('alias') . ' = ' . $db->quote($data['alias'])
    );
    // insert to database
    $query = $db->getQuery(true);
    $query->update($db->quoteName('#__joomgallery_categories'))->set($fields)->where($conditions);
    $db->setQuery($query);
    $db->execute();

    return true;
  }

  /**
	 * Add a category to the ´#__joomgallery_configs´ table
   *
	 * @return  bool  true on success
	 */
	public function addDefaultConfig()
	{
    $db = Factory::getDbo();

    Table::addIncludePath(JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR);
    JLoader::register('\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\ConfigTable', JPATH_ADMINISTRATOR.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'com_joomgallery'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Table'.DIRECTORY_SEPARATOR);

    $table = Table::getInstance('ConfigTable', '\\Joomgallery\\Component\\Joomgallery\\Administrator\\Table\\');

    $data = array();
    $data["id"] = NULL;
    $data["asset_id"] = NULL;
    $data["group_id"] = 1;
    $data["title"] = 'Global Configuration';
    $data["published"] = 1;
    $data["ordering"] = 0;
    $data["checked_out"] = 0;
    $data["created_by"] = 0;
    $data["modified_by"] = 0;
    $data["jg_filenamereplace"] = 'Š|S, Œ|O, Ž|Z, š|s, œ|oe, ž|z, Ÿ|Y, ¥|Y, µ|u, À|A, Á|A, Â|A, Ã|A, Ä|AE, Å|A, Æ|A, Ç|C, È|E, É|E, Ê|E, Ë|E, Ì|I, Í|I, Î|I, Ï|I, Ð|D, Ñ|N, Ò|O, Ó|O, Ô|O, Õ|O, Ö|OE, Ø|O, Ù|U, Ú|U, Û|U, Ü|UE, Ý|Y, à|a, á|a, â|a, ã|a, ä|ae, å|a, æ|a, ç|c, è|e, é|e, ê|e, ë|e';
    $data["jg_replaceinfo"] = '[]';
    $data["jg_staticprocessing"] = '{"jg_staticprocessing0":{"jg_imgtype":"1","jg_imgtypename":"original","jg_imgtypepath":"/images/joomgallery/originals","jg_imgtyperesize":"0","jg_imgtypewidth":"","jg_imgtypeheight":"","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":100,"jg_imgtypewatermark":"0","jg_imgtypewtmsettings":[]},"jg_staticprocessing1":{"jg_imgtype":"1","jg_imgtypename":"detail","jg_imgtypepath":"/images/joomgallery/details","jg_imgtyperesize":"3","jg_imgtypewidth":1000,"jg_imgtypeheight":1000,"jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":80,"jg_imgtypewatermark":"0","jg_imgtypewtmsettings":[]},"jg_staticprocessing2":{"jg_imgtype":"1","jg_imgtypename":"thumbnail","jg_imgtypepath":"/images/joomgallery/thumbnails","jg_imgtyperesize":"4","jg_imgtypewidth":250,"jg_imgtypeheight":250,"jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"1","jg_imgtypequality":60,"jg_imgtypewatermark":"0","jg_imgtypewtmsettings":[]}}';
    $data["jg_dynamicprocessing"] = '[]';
    $data["jg_imgprocessor"] = 'gd';
    $data["jg_maxusercat"] = 10;
    $data["jg_maxuserimage"] = 500;
    $data["jg_maxuserimage_timespan"] = 0;
    $data["jg_maxfilesize"] = 2000000;
    $data["jg_maxuploadfields"] = 3;
    $data["jg_maxvoting"] = 5;

    if (!$table->bind($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error bind category'), 'error');

      return false;
    }
    if (!$table->store($data))
    {
      Factory::getApplication()->enqueueMessage(Text::_('Error store category'), 'error');

      return false;
    }

    return true;
  }

  /**
	 * Add a category to the ´#__joomgallery_img_types´ table
   *
   * @param   string $type Image type name
   * @param   string $type Image type alias
   * @param   string $path Path for the image type
   *
	 * @return  bool  true on success
	 */
	public function addDefaultIMGtype($type, $alias, $path)
	{
    $db = Factory::getDbo();

    switch($type)
    {
      case 'detail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"3","jg_imgtypewidth":"1000","jg_imgtypeheight":"1000","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":"80","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;

      case 'thumbnail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"4","jg_imgtypewidth":"250","jg_imgtypeheight":"250","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"1","jg_imgtypequality":"60","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
      
      default:
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"0","jg_imgtypewidth":"","jg_imgtypeheight":"","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":"100","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
    }

    $record = new stdClass();
    $record->typename = $type;
    $record->type_alias = $alias;
    $record->path = $path;
    $record->params = $params;
    $record->ordering = $this->count;

    // Insert the object into the user profile table.
    if(!$db->insertObject('#__joomgallery_img_types', $record))
    {
      return false;
    }

    return true;
  }

	/**
	 * Method to update the DB of the component
	 *
	 * @param   mixed $parent Object who started the upgrading process
	 *
	 * @return void
	 *
	 * @since 0.2b
     * @throws Exception
	 */
	private function installDb($parent)
	{
		$installation_folder = $parent->getParent()->getPath('source');

		$app = Factory::getApplication();

		if (function_exists('simplexml_load_file') && file_exists($installation_folder . '/installer/structure.xml'))
		{
			$component_data = simplexml_load_file($installation_folder . '/installer/structure.xml');

			// Check if there are tables to import.
			foreach ($component_data->children() as $table)
			{
				$this->processTable($app, $table);
			}
		}
		else
		{
			if (!function_exists('simplexml_load_file'))
			{
				$app->enqueueMessage(Text::_('This script needs \'simplexml_load_file\' to update the component'));
			}
			else
			{
				$app->enqueueMessage(Text::_('Structure file was not found.'));
			}
		}
	}

	/**
	 * Process a table
	 *
	 * @param   CMSApplication  $app   Application object
	 * @param   SimpleXMLElement $table Table to process
	 *
	 * @return void
	 *
	 * @since 0.2b
	 */
	private function processTable($app, $table)
	{
		$db = Factory::getDbo();

		$table_added = false;

		if (isset($table['action']))
		{
			switch ($table['action'])
			{
				case 'add':

					// Check if the table exists before create the statement
					if (!$this->existsTable($table['table_name']))
					{
						$create_statement = $this->generateCreateTableStatement($table);
						$db->setQuery($create_statement);

						try
						{
							$db->execute();
							$app->enqueueMessage(
								Text::sprintf(
									'Table `%s` has been successfully created',
									(string) $table['table_name']
								)
							);
							$table_added = true;
						} catch (Exception $ex)
						{
							$app->enqueueMessage(
								Text::sprintf(
									'There was an error creating the table `%s`. Error: %s',
									(string) $table['table_name'],
									$ex->getMessage()
								), 'error'
							);
						}
					}
					break;
				case 'change':

					// Check if the table exists first to avoid errors.
					if ($this->existsTable($table['old_name']) && !$this->existsTable($table['new_name']))
					{
						try
						{
							$db->renameTable($table['old_name'], $table['new_name']);
							$app->enqueueMessage(
								Text::sprintf(
									'Table `%s` was successfully renamed to `%s`',
									$table['old_name'],
									$table['new_name']
								)
							);
						} catch (Exception $ex)
						{
							$app->enqueueMessage(
								Text::sprintf(
									'There was an error renaming the table `%s`. Error: %s',
									$table['old_name'],
									$ex->getMessage()
								), 'error'
							);
						}
					}
					else
					{
						if (!$this->existsTable($table['table_name']))
						{
							// If the table does not exists, let's create it.
							$create_statement = $this->generateCreateTableStatement($table);
							$db->setQuery($create_statement);

							try
							{
								$db->execute();
								$app->enqueueMessage(
									Text::sprintf('Table `%s` has been successfully created', $table['table_name'])
								);
								$table_added = true;
							} catch (Exception $ex)
							{
								$app->enqueueMessage(
									Text::sprintf(
										'There was an error creating the table `%s`. Error: %s',
										$table['table_name'],
										$ex->getMessage()
									), 'error'
								);
							}
						}
					}
					break;
				case 'remove':

					try
					{
						// We make sure that the table will be removed only if it exists specifying ifExists argument as true.
						$db->dropTable((string) $table['table_name'], true);
						$app->enqueueMessage(
							Text::sprintf('Table `%s` was successfully deleted', $table['table_name'])
						);
					} catch (Exception $ex)
					{
						$app->enqueueMessage(
							Text::sprintf(
								'There was an error deleting Table `%s`. Error: %s',
								$table['table_name'], $ex->getMessage()
							), 'error'
						);
					}

					break;
			}
		}

		// If the table wasn't added before, let's process the fields of the table
		if (!$table_added)
		{
			if ($this->existsTable($table['table_name']))
			{
				$this->executeFieldsUpdating($app, $table);
			}
		}
	}

	/**
	 * Checks if a certain exists on the current database
	 *
	 * @param   string $table_name Name of the table
	 *
	 * @return boolean True if it exists, false if it does not.
	 */
	private function existsTable($table_name)
	{
		$db = Factory::getDbo();

		$table_name = str_replace('#__', $db->getPrefix(), (string) $table_name);

		return in_array($table_name, $db->getTableList());
	}

	/**
	 * Generates a 'CREATE TABLE' statement for the tables passed by argument.
	 *
	 * @param   SimpleXMLElement $table Table of the database
	 *
	 * @return string 'CREATE TABLE' statement
	 */
	private function generateCreateTableStatement($table)
	{
		$create_table_statement = '';

		if (isset($table->field))
		{
			$fields = $table->children();

			$fields_definitions = array();
			$indexes            = array();

			$db = Factory::getDbo();

			foreach ($fields as $field)
			{
				$field_definition = $this->generateColumnDeclaration($field);

				if ($field_definition !== false)
				{
					$fields_definitions[] = $field_definition;
				}

				if ($field['index'] == 'index')
				{
					$indexes[] = $field['field_name'];
				}
			}

			foreach ($indexes as $index)
			{
				$fields_definitions[] = Text::sprintf(
					'INDEX %s (%s ASC)',
					$db->quoteName((string) $index), $index
				);
			}

			// Avoid duplicate PK definition
            if (strpos(implode(',', $fields_definitions), 'PRIMARY KEY') === false)
            {
                $fields_definitions[] = 'PRIMARY KEY (`id`)';
            }

			$create_table_statement = Text::sprintf(
				'CREATE TABLE IF NOT EXISTS %s (%s)',
				$table['table_name'],
				implode(',', $fields_definitions)
			);

			if(isset($table['storage_engine']) && !empty($table['storage_engine']))
			{
				$create_table_statement .= " ENGINE=" . $table['storage_engine'];
			}
			if(isset($table['collation']))
			{
				$create_table_statement .= " DEFAULT COLLATE=" . $table['collation'];
			}
		}
		return $create_table_statement;
	}

	/**
	 * Generate a column declaration
	 *
	 * @param   SimpleXMLElement $field Field data
	 *
	 * @return string Column declaration
	 */
	private function generateColumnDeclaration($field)
	{
		$db        = Factory::getDbo();
		$col_name  = $db->quoteName((string) $field['field_name']);
		$data_type = $this->getFieldType($field);

		if ($data_type !== false)
		{
			$default_value = (isset($field['default'])) ? 'DEFAULT ' . $field['default'] : '';

			$other_data = '';

			if (isset($field['is_autoincrement']) && $field['is_autoincrement'] == 1)
			{
				$other_data .= ' AUTO_INCREMENT PRIMARY KEY';
			}

			$comment_value = (isset($field['description'])) ? 'COMMENT ' . $db->quote((string) $field['description']) : '';

			if(strtolower($field['field_type']) == 'datetime' || strtolower($field['field_type']) == 'text')
			{
				return Text::sprintf(
					'%s %s %s %s %s', $col_name, $data_type,
					$default_value, $other_data, $comment_value
				);
			}

			if((isset($field['required']) && $field['required'] == 1)  || $field['field_name'] == 'id')
			{
				return Text::sprintf(
					'%s %s NOT NULL %s %s %s', $col_name, $data_type,
					$default_value, $other_data, $comment_value
				);
			}

			return Text::sprintf(
				'%s %s NULL %s %s %s', $col_name, $data_type,
				$default_value, $other_data, $comment_value
			);

		}

		return false;
	}

	/**
	 * Generates SQL field type of a field.
	 *
	 * @param   SimpleXMLElement $field Field information
	 *
	 * @return  mixed SQL string data type, false on failure.
	 */
	private function getFieldType($field)
	{
		$data_type = (string) $field['field_type'];

		if (isset($field['field_length']) && ($this->allowsLengthField($data_type) || $data_type == 'ENUM'))
		{
			$data_type .= '(' . (string) $field['field_length'] . ')';
		}

		return (!empty($data_type)) ? $data_type : false;
	}

	/**
	 * Check if a SQL type allows length values.
	 *
	 * @param   string $field_type SQL type
	 *
	 * @return boolean True if it allows length values, false if it does not.
	 */
	private function allowsLengthField($field_type)
	{
		$allow_length = array(
			'INT',
			'VARCHAR',
			'CHAR',
			'TINYINT',
			'SMALLINT',
			'MEDIUMINT',
			'INTEGER',
			'BIGINT',
			'FLOAT',
			'DOUBLE',
			'DECIMAL',
			'NUMERIC'
		);

		return (in_array((string) $field_type, $allow_length));
	}

	/**
	 * Updates all the fields related to a table.
	 *
	 * @param   CMSApplication  $app   Application Object
	 * @param   SimpleXMLElement $table Table information.
	 *
	 * @return void
	 */
	private function executeFieldsUpdating($app, $table)
	{
		if (isset($table->field))
		{
			foreach ($table->children() as $field)
			{
				$table_name = (string) $table['table_name'];

				$this->processField($app, $table_name, $field);
			}
		}
	}

	/**
	 * Process a certain field.
	 *
	 * @param   CMSApplication  $app        Application object
	 * @param   string           $table_name The name of the table that contains the field.
	 * @param   SimpleXMLElement $field      Field Information.
	 *
	 * @return void
	 */
	private function processField($app, $table_name, $field)
	{
		$db = Factory::getDbo();

		if (isset($field['action']))
		{
			switch ($field['action'])
			{
				case 'add':
					$result = $this->addField($table_name, $field);

					if ($result === MODIFIED)
					{
						$app->enqueueMessage(
							Text::sprintf('Field `%s` has been successfully added', $field['field_name'])
						);
					}
					else
					{
						if ($result !== NOT_MODIFIED)
						{
							$app->enqueueMessage(
								Text::sprintf(
									'There was an error adding the field `%s`. Error: %s',
									$field['field_name'], $result
								), 'error'
							);
						}
					}
					break;
				case 'change':

					if (isset($field['old_name']) && isset($field['new_name']))
					{
						if ($this->existsField($table_name, $field['old_name']) && !$this->existsField($table_name, $field['new_name']))
						{
							$renaming_statement = Text::sprintf(
								'ALTER TABLE %s CHANGE %s %s %s',
								$table_name, $db->quoteName($field['old_name']->__toString()),
								$db->quoteName($field['new_name']->__toString()),
								$this->getFieldType($field)
							);
							$db->setQuery($renaming_statement);

							try
							{
								$db->execute();
								$app->enqueueMessage(
									Text::sprintf('Field `%s` has been successfully modified', $field['old_name'])
								);
							} catch (Exception $ex)
							{
								$app->enqueueMessage(
									Text::sprintf(
										'There was an error modifying the field `%s`. Error: %s',
										$field['field_name'],
										$ex->getMessage()
									), 'error'
								);
							}
						}
						else
						{
							$result = $this->addField($table_name, $field);

							if ($result === MODIFIED)
							{
								$app->enqueueMessage(
									Text::sprintf('Field `%s` has been successfully modified', $field['field_name'])
								);
							}
							else
							{
								if ($result !== NOT_MODIFIED)
								{
									$app->enqueueMessage(
										Text::sprintf(
											'There was an error modifying the field `%s`. Error: %s',
											$field['field_name'], $result
										), 'error'
									);
								}
							}
						}
					}
					else
					{
						$result = $this->addField($table_name, $field);

						if ($result === MODIFIED)
						{
							$app->enqueueMessage(
								Text::sprintf('Field `%s` has been successfully modified', $field['field_name'])
							);
						}
						else
						{
							if ($result !== NOT_MODIFIED)
							{
								$app->enqueueMessage(
									Text::sprintf(
										'There was an error modifying the field `%s`. Error: %s',
										$field['field_name'], $result
									), 'error'
								);
							}
						}
					}

					break;
				case 'remove':

					// Check if the field exists first to prevent issue removing the field
					if ($this->existsField($table_name, $field['field_name']))
					{
						$drop_statement = Text::sprintf(
							'ALTER TABLE %s DROP COLUMN %s',
							$table_name, $field['field_name']
						);
						$db->setQuery($drop_statement);

						try
						{
							$db->execute();
							$app->enqueueMessage(
								Text::sprintf('Field `%s` has been successfully deleted', $field['field_name'])
							);
						} catch (Exception $ex)
						{
							$app->enqueueMessage(
								Text::sprintf(
									'There was an error deleting the field `%s`. Error: %s',
									$field['field_name'],
									$ex->getMessage()
								), 'error'
							);
						}
					}

					break;
			}
		}
		else
		{
			$result = $this->addField($table_name, $field);

			if ($result === MODIFIED)
			{
				$app->enqueueMessage(
					Text::sprintf('Field `%s` has been successfully added', $field['field_name'])
				);
			}
			else
			{
				if ($result !== NOT_MODIFIED)
				{
					$app->enqueueMessage(
						Text::sprintf(
							'There was an error adding the field `%s`. Error: %s',
							$field['field_name'], $result
						), 'error'
					);
				}
			}
		}
	}

	/**
	 * Add a field if it does not exists or modify it if it does.
	 *
	 * @param   string           $table_name Table name
	 * @param   SimpleXMLElement $field      Field Information
	 *
	 * @return mixed Constant on success(self::$MODIFIED | self::$NOT_MODIFIED), error message if an error occurred
	 */
	private function addField($table_name, $field)
	{
		$db = Factory::getDbo();

		$query_generated = false;

		// Check if the field exists first to prevent issues adding the field
		if ($this->existsField($table_name, $field['field_name']))
		{
			if ($this->needsToUpdate($table_name, $field))
			{
				$change_statement = $this->generateChangeFieldStatement($table_name, $field);
				$db->setQuery($change_statement);
				$query_generated = true;
			}
		}
		else
		{
			$add_statement = $this->generateAddFieldStatement($table_name, $field);
			$db->setQuery($add_statement);
			$query_generated = true;
		}

		if ($query_generated)
		{
			try
			{
				$db->execute();

				return MODIFIED;
			} catch (Exception $ex)
			{
				return $ex->getMessage();
			}
		}

		return NOT_MODIFIED;
	}

	/**
	 * Checks if a field exists on a table
	 *
	 * @param   string $table_name Table name
	 * @param   string $field_name Field name
	 *
	 * @return boolean True if exists, false if it do
	 */
	private function existsField($table_name, $field_name)
	{
		$db = Factory::getDbo();

		return in_array((string) $field_name, array_keys($db->getTableColumns($table_name)));
	}

	/**
	 * Check if a field needs to be updated.
	 *
	 * @param   string           $table_name Table name
	 * @param   SimpleXMLElement $field      Field information
	 *
	 * @return boolean True if the field has to be updated, false otherwise
	 */
	private function needsToUpdate($table_name, $field)
	{

		if(!isset($field['action']) || $field['field_name'] == 'id')
		{
			return false;
		}

		$db = Factory::getDbo();

		$query = Text::sprintf(
			'SHOW FULL COLUMNS FROM `%s` WHERE Field LIKE %s', $table_name, $db->quote((string) $field['field_name'])
		);
		$db->setQuery($query);

		$field_info = $db->loadObject();

		if (strripos($field_info->Type, $this->getFieldType($field)) === false)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Generates an change column statement
	 *
	 * @param   string           $table_name Table name
	 * @param   SimpleXMLElement $field      Field Information
	 *
	 * @return string Change column statement
	 */
	private function generateChangeFieldStatement($table_name, $field)
	{
		$column_declaration = $this->generateColumnDeclaration($field);

		return Text::sprintf('ALTER TABLE %s MODIFY %s', $table_name, $column_declaration);
	}

	/**
	 * Generates an add column statement
	 *
	 * @param   string           $table_name Table name
	 * @param   SimpleXMLElement $field      Field Information
	 *
	 * @return string Add column statement
	 */
	private function generateAddFieldStatement($table_name, $field)
	{
		$column_declaration = $this->generateColumnDeclaration($field);

		return Text::sprintf('ALTER TABLE %s ADD %s', $table_name, $column_declaration);
	}

	/**
	 * Installs plugins for this component
	 *
	 * @param   mixed $parent Object who called the install/update method
	 *
	 * @return void
	 */
	private function installPlugins($parent)
	{
		$installation_folder = $parent->getParent()->getPath('source');
		$app                 = Factory::getApplication();

		/* @var $plugins SimpleXMLElement */
		if (method_exists($parent, 'getManifest'))
		{
			$plugins = $parent->getManifest()->plugins;
		}
		else
		{
			$plugins = $parent->get('manifest')->plugins;
		}

		if (count($plugins->children()))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			foreach ($plugins->children() as $plugin)
			{
				$pluginName  = (string) $plugin['plugin'];
				$pluginGroup = (string) $plugin['group'];
				$path        = $installation_folder . '/plugins/' . $pluginGroup . '/' . $pluginName;
				$installer   = new Installer;

				if (!$this->isAlreadyInstalled('plugin', $pluginName, $pluginGroup))
				{
					$result = $installer->install($path);
				}
				else
				{
					$result = $installer->update($path);
				}

				if ($result)
				{
					$app->enqueueMessage('Plugin ' . $pluginName . ' was installed successfully');
				}
				else
				{
					$app->enqueueMessage('There was an issue installing the plugin ' . $pluginName,
						'error');
				}

				$query
					->clear()
					->update('#__extensions')
					->set('enabled = 1')
					->where(
						array(
							'type LIKE ' . $db->quote('plugin'),
							'element LIKE ' . $db->quote($pluginName),
							'folder LIKE ' . $db->quote($pluginGroup)
						)
					);
				$db->setQuery($query);
				$db->execute();
			}
		}
	}

	/**
	 * Check if an extension is already installed in the system
	 *
	 * @param   string $type   Extension type
	 * @param   string $name   Extension name
	 * @param   mixed  $folder Extension folder(for plugins)
	 *
	 * @return boolean
	 */
	private function isAlreadyInstalled($type, $name, $folder = null)
	{
		$result = false;

		switch ($type)
		{
			case 'plugin':
				$result = file_exists(JPATH_PLUGINS . '/' . $folder . '/' . $name);
				break;
			case 'module':
				$result = file_exists(JPATH_SITE . '/modules/' . $name);
				break;
		}

		return $result;
	}

	/**
	 * Installs plugins for this component
	 *
	 * @param   mixed $parent Object who called the install/update method
	 *
	 * @return void
	 */
	private function installModules($parent)
	{
		$installation_folder = $parent->getParent()->getPath('source');
		$app                 = Factory::getApplication();

		if (method_exists($parent, 'getManifest'))
		{
			$modules = $parent->getManifest()->modules;
		}
		else
		{
			$modules = $parent->get('manifest')->modules;
		}

		if (!empty($modules))
		{

			if (count($modules->children()))
			{
				foreach ($modules->children() as $module)
				{
					$moduleName = (string) $module['module'];
					$path       = $installation_folder . '/modules/' . $moduleName;
					$installer  = new Installer;

					if (!$this->isAlreadyInstalled('module', $moduleName))
					{
						$result = $installer->install($path);
					}
					else
					{
						$result = $installer->update($path);
					}

					if ($result)
					{
						$app->enqueueMessage('Module ' . $moduleName . ' was installed successfully');
					}
					else
					{
						$app->enqueueMessage('There was an issue installing the module ' . $moduleName,
							'error');
					}
				}
			}
		}
	}

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed $parent Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallPlugins($parent)
	{
		$app     = Factory::getApplication();

		if (method_exists($parent, 'getManifest'))
		{
			$plugins = $parent->getManifest()->plugins;
		}
		else
		{
			$plugins = $parent->get('manifest')->plugins;
		}

		if (count($plugins->children()))
		{
			$db    = Factory::getDbo();
			$query = $db->getQuery(true);

			foreach ($plugins->children() as $plugin)
			{
				$pluginName  = (string) $plugin['plugin'];
				$pluginGroup = (string) $plugin['group'];
				$query
					->clear()
					->select('extension_id')
					->from('#__extensions')
					->where(
						array(
							'type LIKE ' . $db->quote('plugin'),
							'element LIKE ' . $db->quote($pluginName),
							'folder LIKE ' . $db->quote($pluginGroup)
						)
					);
				$db->setQuery($query);
				$extension = $db->loadResult();

				if (!empty($extension))
				{
					$installer = new Installer;
					$result    = $installer->uninstall('plugin', $extension);

					if ($result)
					{
						$app->enqueueMessage('Plugin ' . $pluginName . ' was uninstalled successfully');
					}
					else
					{
						$app->enqueueMessage('There was an issue uninstalling the plugin ' . $pluginName,
							'error');
					}
				}
			}
		}
	}

	/**
	 * Uninstalls plugins
	 *
	 * @param   mixed $parent Object who called the uninstall method
	 *
	 * @return void
	 */
	private function uninstallModules($parent)
	{
		$app = Factory::getApplication();

		if (method_exists($parent, 'getManifest'))
		{
			$modules = $parent->getManifest()->modules;
		}
		else
		{
			$modules = $parent->get('manifest')->modules;
		}

		if (!empty($modules))
		{

			if (count($modules->children()))
			{
				$db    = Factory::getDbo();
				$query = $db->getQuery(true);

				foreach ($modules->children() as $plugin)
				{
					$moduleName = (string) $plugin['module'];
					$query
						->clear()
						->select('extension_id')
						->from('#__extensions')
						->where(
							array(
								'type LIKE ' . $db->quote('module'),
								'element LIKE ' . $db->quote($moduleName)
							)
						);
					$db->setQuery($query);
					$extension = $db->loadResult();

					if (!empty($extension))
					{
						$installer = new Installer;
						$result    = $installer->uninstall('module', $extension);

						if ($result)
						{
							$app->enqueueMessage('Module ' . $moduleName . ' was uninstalled successfully');
						}
						else
						{
							$app->enqueueMessage('There was an issue uninstalling the module ' . $moduleName,
								'error');
						}
					}
				}
			}
		}
	}

  /**
   * Generates post installer messages.
   *
   * @param  array   $act_version     Array with the currently installled version code
   * @param  array   $new_version     Array with the version code the package will be updated to
   * @param  string  $methode         install, uninstall, update
   *
   * @return string html string of the message
   */
  private function getInstallerMSG($act_version, $new_version, $methode)
  {
    $msg = '';

    return $msg;
  }
}
