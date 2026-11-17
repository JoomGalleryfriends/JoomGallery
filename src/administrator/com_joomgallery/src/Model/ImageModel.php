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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;

/**
 * Image model.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImageModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Associations context.
   *
   * @var string
   */
  protected $associationsContext = 'com_joomgallery.image';

  /**
   * The event to trigger after recreation of imagetypes.
   *
   * @var    string
   *
   * @since  4.0.0
   */
  protected $event_after_recreate = null;

  /**
   * The event to trigger before recreation of imagetypes.
   *
   * @var    string
   *
   * @since  4.0.0
   */
  protected $event_before_recreate = null;

  /**
   * Constructor.
   *
   * @param   array                 $config       An array of configuration options (name, state, dbo, table_path, ignore_request).
   * @param   MVCFactoryInterface   $factory      The factory.
   * @param   FormFactoryInterface  $formFactory  The form factory.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?FormFactoryInterface $formFactory = null)
  {
    parent::__construct($config, $factory, $formFactory);

    // Set event after recreate
    if( isset($config['event_after_recreate']) )
    {
      $this->event_after_recreate = $config['event_after_recreate'];
    }
    elseif( empty($this->event_after_recreate) )
    {
      $this->event_after_recreate = 'onJoomAfterRecreate';
    }

    // Set event before recreate
    if( isset($config['event_before_recreate']) )
    {
      $this->event_before_recreate = $config['event_before_recreate'];
    }
    elseif( empty($this->event_before_recreate) )
    {
      $this->event_before_recreate = 'onJoomBeforeRecreate';
    }

    // Update events map
    $this->events_map = array_merge(
        [
          'recreate' => 'joomgallery',
        ],
        $this->events_map
    );
  }

  /**
   * Method to get the record form.
   *
   * @param   array    $data      An optional array of data for the form to interogate.
   * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  Form|boolean  A \JForm object on success, false on failure
   *
   * @since   4.0.0
   */
  public function getForm($data = [], $loadData = true)
  {
    // Get the form.
    $form = $this->loadForm($this->typeAlias, 'image', ['control' => 'jform', 'load_data' => $loadData]);

    if( empty($form) )
    {
      return false;
    }

    $forcedLanguage = $this->app->getInput()->getString('forcedLanguage', '');

    if( $forcedLanguage !== '' )
    {
      $form->setValue('language', null, $forcedLanguage);
      $form->setFieldAttribute('language', 'disabled', 'true');
    }

    $originId = $this->app->getInput()->getInt('origin_id');

    if( $originId )
    {
      $form->setFieldAttribute('image', 'required', 'false');
    }

    if( $originId && $forcedLanguage )
    {
      $sourceImage = JoomHelper::getRecord('image', $originId);

      if( $sourceImage && !empty($sourceImage->catid) )
      {
        $categoryAssociations = Associations::getAssociations(
            'com_joomgallery',
            '#__joomgallery_categories',
            'com_joomgallery.category',
            $sourceImage->catid,
            'id',
            '',
            ''
        );

        if( isset($categoryAssociations[$forcedLanguage]) )
        {
        $form->setValue(
            'catid',
            null,
            $categoryAssociations[$forcedLanguage]->id
        );
        }
      }
    }

    // Set image language according to the selected category.
    if($forcedLanguage === '' && Multilanguage::isEnabled())
    {
      $categoryId = (int) $form->getValue('catid');

      if($categoryId > 0)
      {
        $categoryTable = $this->component->getMVCFactory()->createTable('Category', 'administrator');

        if($categoryTable->load($categoryId))
        {
          $form->setValue('language', null, $categoryTable->language);
          $form->setFieldAttribute('language', 'disabled', 'true');
        }
      }
    }

    if( $originId > 0 && $forcedLanguage !== '' )
    {
      $form->setFieldAttribute('alias', 'required', 'false');
    }

    // On edit, we get ID from state, but on save, we use data from input
    $id = (int)$this->getState('image.id', $this->app->getInput()->getInt('id', null));

    // Object uses for checking edit state permission of image
    $record     = new \stdClass();
    $record->id = $id;

    // Modify the form based on Edit State access controls.
    if( !$this->canEditState($record) )
    {
      // Disable fields for display.
      $form->setFieldAttribute('featured', 'disabled', 'true');
      $form->setFieldAttribute('ordering', 'disabled', 'true');
      $form->setFieldAttribute('published', 'disabled', 'true');

      // Disable fields while saving.
      // The controller has already verified this is an article you can edit.
      $form->setFieldAttribute('featured', 'filter', 'unset');
      $form->setFieldAttribute('ordering', 'filter', 'unset');
      $form->setFieldAttribute('published', 'filter', 'unset');
    }

    // Only component managers/admins and the current owner may transfer ownership.
    if( !$this->canChangeCreatedBy($id) )
    {
      $form->setFieldAttribute('created_by', 'filter', 'unset');
    }

    $uploader = $data['uploader'] ?? $this->app->getInput()->post->get('jform', [], 'array')['uploader'] ?? '';

    if($uploader === 'ftp')
    {
      $form->setFieldAttribute('title', 'required', false);
    }

    return $form;
  }

  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  mixed  The data for the form.
   *
   * @since   4.0.0
   */
  protected function loadFormData()
  {
    // Check the session for previously entered form data.
    $data = Factory::getApplication()->getUserState(_JOOM_OPTION . '.edit.image.data', []);

    if( empty($data) )
    {
      if( $this->item === null || $this->item === false )
      {
        $this->item = $this->getItem();
      }

      $data = $this->item;

      if( $data === false || $data === null )
      {
        return $data;
      }

      // Support for multiple or not foreign key field: robots
      $array = [];

      foreach( (array)$data->robots as $value )
      {
        if( !\is_array($value) )
        {
          $array[] = $value;
        }
      }

      if( !empty($array) )
      {
        $data->robots = $array;
      }
    }

    return $data;
  }

  /**
   * Method to get a single record.
   *
   * @param   integer|array  $pk  The id of the primary key or array(fieldname => value)
   *
   * @return  mixed    Object on success, false on failure.
   *
   * @since   4.0.0
   */
  public function getItem($pk = null)
  {
    if( !\is_null($this->item) && !empty($this->item->id) && isset($this->item->associations) )
    {
      return $this->item;
    }

    $pk    = ( !empty($pk) ) ? $pk : (int)$this->getState('image.id');
    $table = $this->getTable();

    if( $pk > 0 || \is_array($pk) )
    {
      // Attempt to load the row.
      $return = $table->load($pk);

      // Check for a table object error.
      if( $return === false )
      {
        // If there was no underlying error, then the false means there simply was not a row in the db for this $pk.
        if( !$table->getError() )
        {
          $this->setError(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));
        }
        else {
          $this->setError($table->getError());
        }

        return false;
      }
    }

    $this->item = $table->getFieldsValues();

    if( $this->associationsContext && Associations::isEnabled() && !empty($this->item->id) )
    {
    $associations = Associations::getAssociations(
        'com_joomgallery',
        '#__joomgallery',
        'com_joomgallery.image',
        $this->item->id,
        'id',
        '',
        ''
    );

      $this->item->associations = [];

      foreach( $associations as $language => $association )
      {
        $this->item->associations[$language] = $association->id;
      }
    }

    return $this->item;
  }

  /**
   * Method to duplicate an Image
   *
   * @param   array  &$pks  An array of primary key IDs.
   *
   * @return  boolean  True if successful.
   *
   * @throws  Exception
   */
  public function duplicate(&$pks)
  {
    // Access checks.
    if( !$this->user->authorise('core.create', _JOOM_OPTION) )
    {
      throw new\Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
    }

    $context = $this->option . '.' . $this->name;

    // Include the plugins for the save events.
    PluginHelper::importPlugin($this->events_map['save']);

    $table = $this->getTable();

    foreach( $pks as $pk )
    {
      if( $table->load($pk, true) )
      {
        // Reset the id to create a new record.
        $table->id = 0;

        if( !$table->check() )
        {
          throw new\Exception($table->getError());
        }

        if( !empty($table->catid) )
        {
          if( \is_array($table->catid) )
          {
            $table->catid = implode(',', $table->catid);
          }
        }
        else {
          $table->catid = '';
        }

        // Create file manager service
        $manager = JoomHelper::getService('FileManager', [$table->catid]);

        // Regenerate filename
        $newFilename = $manager->regenFilename($table->filename);

        // Copy images
        $manager->copyImages($table, $table->catid, $newFilename);

        // Transfer new filename to table
        $table->filename = $newFilename;

        // Output warning messages
        if( \count($this->component->getWarning()) > 1 )
        {
          $this->component->printWarning();
        }

        // Output debug data
        if( \count($this->component->getDebug()) > 1 )
        {
          $this->component->printDebug();
        }

        // Trigger the before save event.
        $result = $this->app->triggerEvent($this->event_before_save, [$context, & $table, true, $table]);

        if( \in_array(false, $result, true) || !$table->store() )
        {
          throw new\Exception($table->getError());
        }

        // Trigger the after save event.
        $this->app->triggerEvent($this->event_after_save, [$context, & $table, true]);
      }
      else {
        throw new\Exception($table->getError());
      }
    }

    // Clean cache
    $this->cleanCache();

    return true;
  }

  /**
   * Method to save image from form data.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function save($data)
  {
    $table        = $this->getTable();
    $context      = $this->option . '.' . $this->name;
    $app          = Factory::getApplication();
    $imgUploaded  = false;
    $catMoved     = false;
    $isNew        = true;
    $isCopy       = false;
    $isAjax       = false;
    $aliasChanged = false;

    $key = $table->getKeyName();
    $pk  = ( isset($data[$key]) ) ? $data[$key] : (int)$this->getState($this->getName() . '.id');

    // Are we going to copy the image record?
    if( strpos($app->input->get('task'), 'save2copy') !== false )
    {
      $isCopy = true;
    }

    // Are we going to create an image from an existing source image?
    $source_id            = $app->input->getInt('origin_id', 0);
    $forcedLanguage       = $app->input->getString('forcedLanguage', '');
    $isLanguageVariant    = $source_id > 0 && $forcedLanguage !== '';
    $preparedAssociations = [];
    $sourceImageRefPath   = null;

    if( $source_id > 0 )
    {
      $isCopy = true;
    }

    // Are we going to save image in an ajax request?
    if( strpos($app->input->get('task'), 'ajaxsave') !== false )
    {
      $isAjax = true;
    }

    // Change language to 'All' if multilangugae is not enabled
    if( !Multilanguage::isEnabled() )
    {
      $data['language'] = '*';
    }

    if((int) ($data['catid'] ?? 0) <= 0)
    {
      $this->setError(Text::_('COM_JOOMGALLERY_COMMON_ALERT_YOU_MUST_SELECT_CATEGORY'));

      return false;
    }

    // Set image language according to the selected category.
    if(!$isLanguageVariant && Multilanguage::isEnabled())
    {
      $categoryTable = $this->component->getMVCFactory()->createTable('Category', 'administrator');

      if(!$categoryTable->load((int) $data['catid']))
      {
        $this->setError(Text::_('JLIB_APPLICATION_ERROR_NOT_EXIST'));

        return false;
      }

      $data['language'] = $categoryTable->language;
    }

    // Validate a language variant before any upload, file-reference or database changes.
    if($isLanguageVariant && $isNew)
    {
      $contentLanguages = \Joomla\CMS\Language\LanguageHelper::getContentLanguages(false, false, null, 'ordering', 'asc');
      $enabledLanguages = array_map(static fn($language) => $language->lang_code, $contentLanguages);

      if(!\in_array($forcedLanguage, $enabledLanguages, true))
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));

        return false;
      }

      $sourceImage = JoomHelper::getRecord('image', $source_id);

      if(!$sourceImage || empty($sourceImage->id)
        || empty($sourceImage->language)
        || $sourceImage->language === '*'
        || $sourceImage->language === $forcedLanguage)
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));

        return false;
      }

      // The source image must be usable by the current user.
      if(!$this->getAcl()->checkACL('edit', 'image', (int) $sourceImage->id, (int) $sourceImage->catid, true))
      {
        $this->setError(Text::_('JLIB_APPLICATION_ERROR_ACCESS_DENIED'));

        return false;
      }

      // Load and validate the existing association group.
      if($this->associationsContext && Associations::isEnabled())
      {
        $preparedAssociations = $this->getAssociationMap((int) $sourceImage->id);

        if($preparedAssociations === false)
        {
          return false;
        }

        // Do not create a second association for the requested language.
        if(isset($preparedAssociations[$forcedLanguage]))
        {
          $this->setError(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT'));

          return false;
        }
      }

      // Resolve the target category from the source category's association.
    $categoryAssociations = Associations::getAssociations(
        'com_joomgallery',
        '#__joomgallery_categories',
        'com_joomgallery.category',
        (int) $sourceImage->catid,
        'id',
        '',
        ''
    );

      if(!isset($categoryAssociations[$forcedLanguage]))
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_CATEGORY_VARIANT_PARENT'));

        return false;
      }

      $targetCategoryId = (int) $categoryAssociations[$forcedLanguage]->id;
      $categoryTable    = $this->component->getMVCFactory()->createTable('Category', 'administrator');

      if(!$categoryTable->load($targetCategoryId) || $categoryTable->language !== $forcedLanguage)
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_CATEGORY_VARIANT_PARENT'));

        return false;
      }

      // The user must be allowed to add/upload images in the target category.
      if(!$this->getAcl()->checkACL('add', 'image', 0, $targetCategoryId, true))
      {
        $this->setError(Text::_('JLIB_APPLICATION_ERROR_BATCH_CANNOT_CREATE'));

        return false;
      }

      // Verify the source image file using the filesystem stored with the image.
      try
      {
        $filesystem = (string) ($sourceImage->filesystem ?? '');

        // A shared image reference stores the authoritative filesystem and path.
        if(!empty($sourceImage->image_ref))
        {
          $db    = $this->getDatabase();
          $query = $db->getQuery(true)
            ->select($db->quoteName('filesystem'))
            ->from($db->quoteName('#__joomgallery_img_ref'));

          $imageRefId = (int) $sourceImage->image_ref;

          $query->where($db->quoteName('id') . ' = :imageRefId')
            ->bind(':imageRefId', $imageRefId, ParameterType::INTEGER);

          $db->setQuery($query);
          $referenceFilesystem = $db->loadResult();

          if($referenceFilesystem === null || $referenceFilesystem === '')
          {
            throw new \RuntimeException('The filesystem of the shared image reference could not be determined.');
          }

          $filesystem = (string) $referenceFilesystem;
        }

        if($filesystem === '')
        {
          throw new \RuntimeException('The filesystem of the source image could not be determined.');
        }

        $sourceFileManager = JoomHelper::getService('FileManager', [(int) $sourceImage->catid]);

        // FileManager initially uses the category configuration. Override it with
        // the filesystem that actually stores the source image.
        $this->component->createFilesystem($filesystem);
        $sourceImageRefPath = $sourceFileManager->getCatPath($sourceImage->catid);
        $sourceFilePath     = $sourceFileManager->getImgPath($sourceImage, 'original');

        if($sourceFilePath === false)
        {
          throw new \RuntimeException('The source image path could not be resolved.');
        }

        $this->component->getFilesystem()->getFile($sourceFilePath);
      }
      catch(\Throwable $e)
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_EXISTING'));

        return false;
      }

      // Never trust the category id submitted by the browser for a language variant.
      $data['catid'] = $targetCategoryId;
    }

    // Include the plugins for the save events.
    PluginHelper::importPlugin($this->events_map['save']);

    // Track the transaction used for creating a language variant.
    $transactionStarted = false;
    $db                 = $this->getDatabase();

    $rollbackTransaction = function () use (
        $app,
        $db,
        $table,
        &$transactionStarted
    ) {
      if($transactionStarted)
      {
        $db->transactionRollback();
        $transactionStarted = false;

        $app->triggerEvent(
            'onJoomgalleryImageRollbackTransaction',
            ['id' => (int) $table->id]
        );
      }
    };

    // Record editing and image creation
    try {
      // Load the row if saving an existing record.
      if( $pk > 0 )
      {
        $table->load($pk);
        $oldPublished = (int) $table->published;
        $oldHidden    = (int) $table->hidden;
        $oldAccess    = (int) $table->access;
        $oldFeatured  = (int) $table->featured;
        $oldApproved  = (int) $table->approved;
        $isNew        = false;

        // Check if the category was changed
        if( $table->catid != $data['catid'] )
        {
          $catMoved = true;
        }

        // Check if the alias was changed
        if( $table->alias != $data['alias'] )
        {
          $aliasChanged = true;
          $old_alias    = $table->alias;
        }

        // Check if the state was changed
        if( $table->published != $data['published'] )
        {
          if( !$this->getAcl()->checkACL('core.edit.state', _JOOM_OPTION . '.image.' . $table->id, $table->id, $table->catid) )
          {
            // We are not allowed to change the published state
            $this->component->addWarning(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
            $data['published'] = $table->published;
          }
        }

        // Preflight all associated images before storing any synchronized field.
        // This prevents a partial update when one language variant cannot be changed.
        $associatedFields = [
          'published' => $oldPublished,
          'hidden'    => $oldHidden,
          'access'    => $oldAccess,
          'featured'  => $oldFeatured,
          'approved'  => $oldApproved,
        ];

        foreach($associatedFields as $field => $oldValue)
        {
          if(isset($data[$field]) && (int) $data[$field] !== $oldValue
            && !$this->checkAssociatedFieldPermissions($table, $field, 'jg_link_associated_images', false))
          {
            return false;
          }
        }
      }

      // Save form data in session
      $app->setUserState(_JOOM_OPTION . '.image.upload', $data);

      // Detect uploader service
      $upload_service = 'html';

      if( isset($data['uploader']) && !empty($data['uploader']) )
      {
        $upload_service = $data['uploader'];
      }

      // Detect multiple upload service
      $upload_multiple = false;

      if( isset($data['multiple']) && !empty($data['multiple']) )
      {
        $upload_multiple = \boolval($data['multiple']);
      }

      // Create uploader service
      $uploader = JoomHelper::getService('uploader', [$upload_service, $upload_multiple, $isAjax]);

      // Detect uploaded file
      $imgUploaded = $uploader->isImgUploaded($data);

      // Retrieve image from request
      if( $imgUploaded )
      {
        // Determine if we have to create new filename
        $createFilename = false;

        if( $isNew || empty($data['filename']) )
        {
          $createFilename = true;
        }

        // Retrieve image
        // (check upload, check user upload limit, create filename, onJoomBeforeSave)
        if( !$uploader->retrieveImage($data, $createFilename) )
        {
          $this->setError($this->component->getDebug(true));
          $uploader->rollback();

          return false;
        }

        // Override data with image metadata
        if( !$uploader->overrideData($data) )
        {
          $this->setError($this->component->getDebug(true));
          $uploader->rollback();

          return false;
        }
      }

      // Create file manager service
      $manager = JoomHelper::getService('FileManager', [$data['catid']]);

      // Handle images if category was changed
      if( !$isNew && ( $catMoved || $aliasChanged ) )
      {
        // Douplicate old data
        $old_table = clone $table;
      }

      // Prepare data for a new language variant.
      if( $isLanguageVariant && $isNew )
      {
        $sourceImage = JoomHelper::getRecord('image', $source_id);

        if( !$sourceImage )
        {
          $this->setError('Source image not found.');

          if( $imgUploaded )
          {
            $uploader->rollback();
          }

          return false;
        }

        // Start transaction for creating a language variant.
        $db->transactionStart();
        $transactionStarted = true;

        // Create a shared image reference if the source has none.
        $imageRefId = (int)($sourceImage->image_ref ?? 0);

        if($imageRefId === 0)
        {
          $imageRef = (object)[
            'path'      => $sourceImageRefPath,
            'filename'  => $sourceImage->filename,
            'filesystem' => $filesystem,
          ];

          if($imageRef->path === false)
          {
            $this->setError('Could not resolve source image path.');
            $rollbackTransaction();

            return false;
          }

          $db->insertObject('#__joomgallery_img_ref', $imageRef, 'id');
          $imageRefId = (int)$imageRef->id;

          $query = $db->getQuery(true)
            ->update($db->quoteName('#__joomgallery'))
            ->set($db->quoteName('image_ref') . ' = :imageRef')
            ->where($db->quoteName('id') . ' = :sourceId')
            ->bind(':imageRef', $imageRefId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':sourceId', $source_id, \Joomla\Database\ParameterType::INTEGER);

          $db->setQuery($query);
          $db->execute();
        }

        $sourceImage->image_ref = $imageRefId;

        $sourceData = (array)$sourceImage;

        foreach( [
          'filename',
          'image_ref',
          'filesystem',
          'uploader',
          'multiple',
          'published',
          'approved',
          'featured',
          'hidden',
          'access',
          'author',
          'created_by',
          'date',
          'tags',
          'params',
          'imgmetadata',
          'metadesc',
          'metakey',
          'robots',
        ] as $field )
        {
          if( \array_key_exists($field, $sourceData) )
          {
            $data[$field] = $sourceData[$field];
          }
        }

        $data['id']           = 0;
        $data['language']     = $forcedLanguage;
        $data['created_time'] = Factory::getDate()->toSql();

        // Use the associated category when one exists.
        if( !empty($sourceImage->catid) )
        {
        $categoryAssociations = Associations::getAssociations(
            'com_joomgallery',
            '#__joomgallery_categories',
            'com_joomgallery.category',
            $sourceImage->catid,
            'id',
            '',
            ''
        );

          if( isset($categoryAssociations[$forcedLanguage]) )
          {
            $data['catid'] = $categoryAssociations[$forcedLanguage]->id;
          }
        }
      }

      // Bind data to table object
      if( !$table->bind($data) )
      {
        $this->setError($table->getError());

        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }

        $rollbackTransaction();

        return false;
      }

      // Prepare the row for saving
      $this->prepareTable($table);

      // Check the data.
      if( !$table->check() )
      {
        $this->setError($table->getError());

        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }

        $rollbackTransaction();

        return false;
      }

      // Cancel if file needs two filesystems to be saved
      // Can be deleted if filesystem service supports two filesystems
      $two_filesystems = [];

      if( $isCopy || $isLanguageVariant )
      {
        // Get source image
        $src_img = JoomHelper::getRecord('image', $source_id);

        if( $source_id > 0 && !$imgUploaded && $src_img )
        {
          $data['filename'] = $src_img->filename;
        }

        if( $src_img && $src_img->filesystem !== $table->filesystem )
        {
          $two_filesystems = [$src_img->filesystem, $table->filesystem];
        }
      }
      elseif( $catMoved )
      {
        // Get filesystem for new category
        $tmp_config = new \Joomgallery\Component\Joomgallery\Administrator\Service\Config\DefaultConfig('com_joomgallery.category', $table->catid);

        if( $tmp_config->get('jg_filesystem', 'local-images') !== $table->filesystem )
        {
          $two_filesystems = [$table->filesystem, $tmp_config->get('jg_filesystem', 'local-images')];
        }
      }

      if( !empty($two_filesystems) )
      {
        $this->component->addError(Text::sprintf('COM_JOOMGALLERY_ERROR_IMAGE_SAVE_TWO_FILESYSTEMS', $two_filesystems[0], $two_filesystems[1]));

        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }

        $rollbackTransaction();

        return false;
      }

      // Handle images if alias has changed
      if( !$isNew && $aliasChanged && !$imgUploaded && empty($old_table->image_ref) )
      {
        if( !$this->component->getConfig()->get('jg_useorigfilename') )
        {
          // Replace alias in filename if filename is title dependent
          $table->filename = str_replace($old_alias, $table->alias, $table->filename);
        }
      }

      // Trigger the before save event.
      $result = $app->triggerEvent($this->event_before_save, [$context, $table, $isNew, $data]);

      // Stop storing data if one of the plugins returns false
      if( \in_array(false, $result, true) )
      {
        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }
        $this->setError($table->getError());
        $rollbackTransaction();

        return false;
      }

      // Filesystem changes
      $filesystem_success = true;

      // Handle images if category was changed
      if( !$isNew && $catMoved && empty($old_table->image_ref) )
      {
        if( $imgUploaded )
        {
          // Delete old images
          $filesystem_success = $manager->deleteImages($old_table);
        }
        else {
          // Move old images to new location
          $filesystem_success = $manager->moveImages($old_table, $table->catid);
        }
      }

      // Copy image files only for regular copies, not language variants.
      if( $isNew && $isCopy && !$imgUploaded && !$isLanguageVariant )
      {
        // Regenerate filename to prevent copying a file onto itself.
        $newFilename      = $manager->regenFilename($table->filename);
        $table->filename  = $newFilename;
        $data['filename'] = $newFilename;

        // Copy image files using the new filename.
        $filesystem_success = $manager->copyImages($source_id, $table->catid, $newFilename);
      }

      // Handle images if alias has changed
      if( !$isNew && $aliasChanged && !$imgUploaded && empty($old_table->image_ref) )
      {
        if( $catMoved )
        {
          // modify old_table object to fit with new image location
          $old_table->catid = $table->catid;
        }

        // Rename files
        $filesystem_success = $manager->renameImages($old_table, $table->filename);
      }

      // Don't store the table if filesystem changes was not successful
      if( !$filesystem_success )
      {
        $this->component->addError(Text::_('COM_JOOMGALLERY_ERROR_SAVE_FILESYSTEM_ERROR'));

        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }

        $rollbackTransaction();

        return false;
      }

      // Store the data.
      if( !$table->store() )
      {
        $this->setError($table->getError());

        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }

        $rollbackTransaction();

        return false;
      }

      if(!$isNew)
      {
        $fields = [
          'published' => $oldPublished,
          'hidden'    => $oldHidden,
          'access'    => $oldAccess,
          'featured'  => $oldFeatured,
          'approved'  => $oldApproved,
        ];

        foreach($fields as $field => $oldValue)
        {
          if($oldValue !== (int) $table->$field)
          {
            $this->syncAssociatedField($table, $field, $table->$field);
          }
        }
      }

      // Create images
      if( $imgUploaded )
      {
        // Create images
        // (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
        if( !$uploader->createImage($table) )
        {
          $this->setError($this->component->getError(true) ?: $this->component->getDebug(true) ?: Text::_('COM_JOOMGALLERY_SERVICE_SOME_ERRORS_IMAGEFILE'));
          $uploader->rollback($table);

          if( $isNew )
          {
            // Delete the already stored new record if image creation failed
            if( !$table->delete($table->$key) )
            {
              $this->component->setError($table->getError());
            }
          }

          $rollbackTransaction();

          return false;
        }
      }

      // Handle ajax uploads
      if( $isAjax )
      {
        $this->component->cache->set('imgObj', $table->getFieldsValues(['form', 'imgmetadata', 'params', 'created_by', 'modified_by', 'checked_out']));
      }

      // All done. Clean created temp files
      $uploader->deleteTmp();

      // Clean the cache.
      $this->cleanCache();

      // Defer finder indexing for language variants until transaction commit.
      if( $isLanguageVariant && $transactionStarted )
      {
        PluginHelper::importPlugin('finder', 'joomgallery');

        $app->triggerEvent(
            'onJoomgalleryImageDeferFinderIndex',
            ['id' => (int) $table->id]
        );
      }

      // Trigger the after save event.
      $app->triggerEvent($this->event_after_save, [$context, $table, $isNew, $data]);
    }
      catch ( \Throwable $e ) {
        if( $imgUploaded )
        {
          $uploader->rollback($table);
        }

        $rollbackTransaction();
        $this->setError($e->getMessage());

        return false;
      }

    // Output warning messages
    if( \count($this->component->getWarning()) > 0 )
    {
      $this->component->printWarning();
    }

    // Output debug data
    if( \count($this->component->getDebug()) > 0 )
    {
      $this->component->printDebug();
    }

    // Set state
    if( isset($table->$key) )
    {
      $this->setState($this->getName() . '.id', $table->$key);
    }

    $this->setState($this->getName() . '.new', $isNew);

    // Create/update associations and commit the language variant.
    try
    {
      if($this->associationsContext && Associations::isEnabled())
      {
        if($isLanguageVariant && $source_id > 0)
        {
          $data['associations'] = $preparedAssociations;
        }

        if(\array_key_exists('associations', $data)
          && !$this->createAssociations($table, $data['associations'], $transactionStarted))
        {
          $rollbackTransaction();

          return false;
        }
      }

      if($transactionStarted)
      {
        $db->transactionCommit();
        $transactionStarted = false;
      }
    }
    catch(\Throwable $e)
    {
      $rollbackTransaction();
      $this->setError($e->getMessage());

      return false;
    }

    // Reindex the language variant after the transaction has been committed.
    if( $isLanguageVariant && $source_id > 0 )
    {
      try
      {
        $app->triggerEvent(
            'onJoomgalleryImageAfterTransactionCommit',
            ['id' => (int) $table->id]
        );
      }
      catch(\Throwable $e)
      {
        $this->component->addLog(
            'Could not reindex language variant image ' . (int) $table->id
            . ' after transaction commit: ' . $e->getMessage(),
            'warning',
            'jerror'
        );
      }
    }

    // Redirect to associations
    if( $app->input->get('task') == 'editAssociations' )
    {
      return $this->redirectToAssociations($data);
    }

    return true;
  }

  /**
   * Method to delete one or more images.
   *
   * @param   array  &$pks  An array of record primary keys.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.0.0
   */
  public function delete(&$pks)
  {
    $pks   = ArrayHelper::toInteger((array)$pks);
    $table = $this->getTable();

    // Include the plugins for the delete events.
    PluginHelper::importPlugin($this->events_map['delete']);

    // Iterate the items to delete each one.
    foreach( $pks as $i => $pk )
    {
      if( $table->load($pk) )
      {
        if( $this->canDelete($table) )
        {
          $context = $this->option . '.' . $this->name;

          // Trigger the before delete event.
          $result = Factory::getApplication()->triggerEvent($this->event_before_delete, [$context, $table]);

          if( \in_array(false, $result, true) )
          {
            $this->setError($table->getError());

            return false;
          }

          // Start transaction for deleting the image and its associations.
          $db = $this->getDatabase();
          $db->transactionStart();

          try
          {
            // Delete corresponding imagetypes.
            $manager = JoomHelper::getService('FileManager', [$table->catid]);

            if(!$manager->deleteImages($table))
            {
              $this->setError($this->component->getDebug(true));
              $db->transactionRollback();

              return false;
            }

            // Delete corresponding comments
            $query = $db->getQuery(true)
              ->delete($db->quoteName('#__joomgallery_comments'))
              ->where($db->quoteName('imgid') . ' = :pk')
              ->bind(':pk', $pk, ParameterType::INTEGER);

            $db->setQuery($query);
            $db->execute();

            // Delete corresponding votes
            $query = $db->getQuery(true)
              ->delete($db->quoteName('#__joomgallery_votes'))
              ->where($db->quoteName('imgid') . ' = :pk')
              ->bind(':pk', $pk, ParameterType::INTEGER);

            $db->setQuery($query);
            $db->execute();

            // Delete corresponding collection reference
            $query = $db->getQuery(true)
              ->delete($db->quoteName('#__joomgallery_collections_ref'))
              ->where($db->quoteName('imgid') . ' = :pk')
              ->bind(':pk', $pk, ParameterType::INTEGER);

            $db->setQuery($query);
            $db->execute();

            // Remove the image from its language association group.
            if(!$this->removeAssociationMember((int) $pk))
            {
              $db->transactionRollback();

              return false;
            }

            if(!$table->delete($pk))
            {
              $db->transactionRollback();
              $this->setError($table->getError());

              return false;
            }

            $db->transactionCommit();
          }
          catch(\Throwable $exception)
          {
            $db->transactionRollback();
            $this->setError($exception->getMessage());

            return false;
          }

          // Trigger the after event.
          Factory::getApplication()->triggerEvent($this->event_after_delete, [$context, $table]);
        }
        else {
          // Prune items that you can't change.
          unset($pks[$i]);
          $error = $this->getError();

          if( $error )
          {
            $this->component->addLog($error, 'warning', 'jerror');

            return false;
          }


          $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED') . '; Image ID: ' . $pk, 'warning', 'jerror');

          return false;
        }
      }
      else {
        $this->setError($table->getError());

        return false;
      }
    }

    // Output messages
    if( \count($this->component->getWarning()) > 1 )
    {
      $this->component->printWarning();
    }

    // Output debug data
    if( \count($this->component->getDebug()) > 1 )
    {
      $this->component->printDebug();
    }

    // Clear the component's cache
    $this->cleanCache();

    return true;
  }

  /**
   * Method to change the state of one or more records.
   *
   * @param   array    &$pks   A list of the primary keys to change.
   * @param   string   $type   Name of the state to be changed
   * @param   integer  $value  The value of the state.
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function changeState(&$pks, $type = 'publish', $value = 1)
  {
    // The image-list state action may reach changeState() before the component
    // configuration has been initialized.
    $this->component->createConfig();

    $user    = Factory::getContainer()->get(UserFactoryInterface::class);
    $table   = $this->getTable();
    $pks     = (array)$pks;
    $context = $this->option . '.' . $this->name . '.' . $type;

    if(\in_array($type, ['publish', 'feature', 'approve'], true)
      && $this->component->getConfig()->get('jg_link_associated_images', 1)
      && $this->associationsContext
      && Associations::isEnabled())
    {
      $field = 'published';

      if($type === 'feature')
      {
        $field = 'featured';
      }
      elseif($type === 'approve')
      {
        $field = 'approved';
      }

      foreach($pks as $pk)
      {
        $table->reset();

        if(!$table->load($pk) || !$this->checkAssociatedFieldPermissions($table, $field))
        {
          return false;
        }
      }
    }

    // Include the plugins for the change of state event.
    PluginHelper::importPlugin($this->events_map['change_state']);

    // Access checks.
    foreach( $pks as $i => $pk )
    {
      $table->reset();

      if( $table->load($pk) )
      {
        if( !$this->canEditState($table) )
        {
          // Prune items that you can't change.
          unset($pks[$i]);

          $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'), 'warning', 'jerror');

          return false;
        }

        // If the table is checked out by another user, drop it and report to the user trying to change its state.
        if( $table->hasField('checked_out') && $table->checked_out && ( $table->checked_out != $user->id ) )
        {
          $this->component->addLog(Text::_('JLIB_APPLICATION_ERROR_CHECKIN_USER_MISMATCH'), 'warning', 'jerror');

          // Prune items that you can't change.
          unset($pks[$i]);

          return false;
        }

        switch( $type )
        {
          case 'feature':
            $stateColumnName = 'featured';
              break;

          case 'approve':
            $stateColumnName = 'approved';
              break;

          case 'publish':
          default:
            $stateColumnName = 'published';
              break;
        }

        if( property_exists($table, $stateColumnName) && $table->get($stateColumnName, $value) == $value )
        {
          unset($pks[$i]);

          continue;
        }
      }
    }

    // Check if there are items to change
    if( !\count($pks) )
    {
      return true;
    }

    // Trigger the before change state event.
    $result = Factory::getApplication()->triggerEvent($this->event_before_change_state, [$context, $pks, $value]);

    if( \in_array(false, $result, true) )
    {
      $this->setError($table->getError());

      return false;
    }

    // Attempt to change the state of the records.
    if( !$table->changeState($type, $pks, $value, $user->id) )
    {
      $this->setError($table->getError());

      return false;
    }

    // Trigger the change state event.
    $result = Factory::getApplication()->triggerEvent($this->event_change_state, [$context, $pks, $value]);

    if( \in_array(false, $result, true) )
    {
      $this->setError($table->getError());

      return false;
    }

    // Synchronize changed state of associated images.
    if(\in_array($type, ['publish', 'feature', 'approve'], true)
      && $this->component->getConfig()->get('jg_link_associated_images', 1)
      && $this->associationsContext
      && Associations::isEnabled())
    {
      $field = 'published';

      if($type === 'feature')
      {
        $field = 'featured';
      }
      elseif($type === 'approve')
      {
        $field = 'approved';
      }

      foreach($pks as $pk)
      {
        $table->reset();

        if($table->load($pk))
        {
          $this->syncAssociatedField($table, $field, $value);
        }
      }
    }

    // Clear the component's cache
    $this->cleanCache();

    return true;
  }

  /**
   * Method to change the published state of one or more records.
   *
   * @param   array    &$pks   A list of the primary keys to change.
   * @param   integer  $value  The value of the published state.
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function publish(&$pks, $value = 1)
  {
    return $this->changeState($pks, 'publish', $value);
  }

  /**
   * Method to replace an image type.
   *
   * @param   array  $data  The form data.
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function replace($data)
  {
    $table = $this->getTable();
    $app   = Factory::getApplication();
    $key   = $table->getKeyName();
    $pk    = ( isset($data[$key]) ) ? $data[$key] : (int)$this->getState($this->getName() . '.id');

    // Rertrieve request image file data
    if( \array_key_exists('image', $app->input->files->get('jform')) && !empty($app->input->files->get('jform')['image']) &&
      $app->input->files->get('jform')['image']['error'] != 4 && $app->input->files->get('jform')['image']['size'] > 0 )
    {
      $data['images'] = [];
      array_push($data['images'], $app->input->files->get('jform')['image']);
    }

    try {
      // Load image table
      $table->load($pk);

      // Create uploader service
      $uploader = JoomHelper::getService('uploader', ['single', false]);

      // Set replacement settings
      $uploader->type         = $data['replacetype'];
      $uploader->processImage = \boolval($data['replaceprocess']);

      // Set filename in data since it will not be new created during retrieveImage()
      $data['filename'] = $table->filename;

      // Retrieve image
      // (check upload, check user upload limit, create filename, onJoomBeforeSave)
      if( !$uploader->retrieveImage($data, false) )
      {
        $this->setError($this->component->getDebug(true));

        return false;
      }

      // Create images
      // (create imagetypes, upload imagetypes to storage, onJoomAfterUpload)
      if( !$uploader->createImage($table) )
      {
        $uploader->rollback();
        $this->setError($this->component->getDebug(true));

        return false;
      }

      // Clean the cache.
      $this->cleanCache();
    }
    catch ( \Exception $e ) {
      $uploader->rollback();
      $this->setError($e->getMessage());

      return false;
    }

    // Output debug data
    if( \count($this->component->getDebug()) > 1 )
    {
      $this->component->printDebug();
    }

    return true;
  }

  /**
   * Method to recreate the imagetypes for one Image.
   *
   * @param   int     $pk    The record primary key.
   * @param   string  $type  The imagetype to use as source for the recreation
   * @param   array   $skip  List of imagetypes to skip creation (default: [])
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.0
   */
  public function recreate(int $pk, $type = 'original', $skip = []): bool
  {
    $table = $this->getTable();

    // Include the plugins for the recreate events.
    PluginHelper::importPlugin($this->events_map['recreate']);

    if($table->load($pk))
    {
      if($this->canRecreate($table))
      {
        $context = $this->option . '.' . $this->name . '.recreate';

        // Create file manager service.
        $this->component->createFileManager($table->catid);

        // Preserve the filesystem selected by the category configuration.
        $previousFilesystem = $this->component->getFilesystem()->getFilesystem();

        try
        {
          // Use the filesystem stored with the shared image reference.
          if(!empty($table->image_ref))
          {
            $db = $this->getDatabase();

            $query = $db->getQuery(true)
              ->select($db->quoteName('filesystem'))
              ->from($db->quoteName('#__joomgallery_img_ref'))
              ->where($db->quoteName('id') . ' = :imageRefId')
              ->bind(':imageRefId', (int) $table->image_ref, ParameterType::INTEGER);

            $db->setQuery($query);
            $referenceFilesystem = $db->loadResult();

            if(empty($referenceFilesystem))
            {
            throw new \RuntimeException(
                'The filesystem of the shared image reference could not be determined.'
            );
            }

            $this->component->createFilesystem($referenceFilesystem);
          }

          // Get imagetypes.
          $imagetypes      = $this->component->getFileManager()->get('imagetypes');
          $imagetypes_dict = $this->component->getFileManager()->get('imagetypes_dict');

          // Select image source.
          if(($type == 'original' || $type == 'orig')
            && $imagetypes[$imagetypes_dict['original']]->params->get('jg_imgtype', 1, 'int') > 0)
          {
            // Take original as source if available.
            $type = 'original';
          }
          else
          {
            $type = 'detail';
          }

          // Get source file path using the selected filesystem.
          $source = $this->component->getFileManager()->getImgPath($table, $type);

        // Trigger the before recreate event.
        $result = Factory::getApplication()->triggerEvent(
            $this->event_before_recreate,
            [$table, $imagetypes, $source]
        );

          if(\in_array(false, $result, true))
          {
            $this->setError($table->getError());

            return false;
          }

          // Add source type to the list of skipped types.
          if(!\in_array($type, $skip))
          {
            array_push($skip, $type);
          }

          // Perform the recreation using the same filesystem.
        if(!$this->component->getFileManager()->createImages(
            $source,
            $table->filename,
            $table->catid,
            true,
            false,
            $skip,
            'jerror',
            $table
        ))
          {
            $this->setError($table->getError());

            return false;
        }

          // Trigger the after event.
          Factory::getApplication()->triggerEvent($this->event_after_recreate, [$context, $table]);
        }
        finally
        {
          // Restore the filesystem selected by the category configuration.
          $this->component->createFilesystem($previousFilesystem);
        }
      }
      else
      {
        // Prune items that you can't change.
        $error = $this->getError();

        if($error)
        {
          $this->component->addLog($error, 'warning', 'jerror');

          return false;
        }

        $this->component->addLog(
            Text::_('JLIB_APPLICATION_ERROR_DELETE_NOT_PERMITTED'),
            'warning',
            'jerror'
        );

        return false;
      }
    }
    else
    {
      $this->setError($table->getError());
      $this->component->setError('Image ID not found: ' . $pk);

      return false;
    }

    // Clear the component's cache.
    $this->cleanCache();

    return true;
  }

  /**
   * Prepare and sanitise the table prior to saving.
   *
   * @param   Table  $table  Table Object
   *
   * @return  void
   *
   * @since   4.0.0
   */
  protected function prepareTable($table)
  {
    // Apply the ordering
    if( property_exists($table, 'ordering') && $table->id == 0 )
    {
      switch( $this->component->getConfig()->get('jg_uploadorder', 2) )
      {
        case 1:
          $table->ordering = $table->getPreviousOrder('catid = ' . $table->catid);
            break;
        case 2:
        default:
          $table->ordering = $table->getNextOrder('catid = ' . $table->catid);
            break;
      }
    }
  }

  /**
   * Method to save metadata to an image file
   *
   * @param   int     $pk    The record primary key.
   * @param   string  $type  The imagetype to which file the metadata gets stored to.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.0
   */
  public function savemetadata(int $pk, $type = 'original'): bool
  {
    $table = $this->getTable();

    if(!$table->load($pk))
    {
      $this->setError($table->getError());

      return false;
    }

    // Create config and filemanager services.
    $this->component->createConfig();
    $this->component->createFileManager($table->catid);

    // Preserve the filesystem selected by the category configuration.
    $previousFilesystem = $this->component->getFilesystem()->getFilesystem();

    try
    {
      // Use the filesystem stored with the shared image reference.
      if(!empty($table->image_ref))
      {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
          ->select($db->quoteName('filesystem'))
          ->from($db->quoteName('#__joomgallery_img_ref'))
          ->where($db->quoteName('id') . ' = :imageRefId')
          ->bind(':imageRefId', (int) $table->image_ref, ParameterType::INTEGER);

        $db->setQuery($query);
        $referenceFilesystem = $db->loadResult();

        if(empty($referenceFilesystem))
        {
          throw new \RuntimeException('The filesystem of the shared image reference could not be determined.');
        }

        $this->component->createFilesystem($referenceFilesystem);
      }

      // Resolve the image path after selecting the correct filesystem.
      $path = $this->component->getFileManager()->getImgPath($table, $type);

      if($path === false)
      {
        throw new \RuntimeException('The image path could not be resolved.');
      }

      // Get registry to be used in writeMetadata.
      $registry = new Registry($table->imgmetadata);

      // Create the metadata service.
      $this->component->createMetadata($this->component->getConfig()->get('jg_metaprocessor', 'php'));

      // Perform the save using the metadata/filesystem service.
      $filesystem = $this->component->getFilesystem()->getFilesystem();

      if($filesystem != 'local-images')
      {
        $data = $this->component->getMetadata()->writeMetadata($path, $registry, false);
      }
      else
      {
        $data = $this->component->getMetadata()->writeMetadata($path, $registry);
      }

      $this->component->getFilesystem()->createFile(basename($path), \dirname($path), $data);
    }
    catch(\Throwable $e)
    {
      $this->setError($e->getMessage());

      return false;
    }
    finally
    {
      // Restore the filesystem selected by the category configuration.
      $this->component->createFilesystem($previousFilesystem);
    }

    // Clear the component's cache.
    $this->cleanCache();

    return true;
  }

  /**
   * Allows preprocessing of the JForm object.
   *
   * @param   \Joomla\CMS\Form\Form  $form   The form object.
   * @param   array                  $data   The data to be merged into the form object.
   * @param   string                 $group  The plugin group to be executed.
   *
   * @return  void
   *
   * @since   4.5.0
   */
  protected function preprocessForm(\Joomla\CMS\Form\Form $form, $data, $group = 'joomgallery')
  {
    $isLanguageVariantForm =
      $this->app->getInput()->getInt('origin_id') > 0 &&
      $this->app->getInput()->getString('forcedLanguage', '') !== '';
    $languages             = \Joomla\CMS\Language\LanguageHelper::getContentLanguages(false, false, null, 'ordering', 'asc');

    if( \count($languages) > 1 && !$isLanguageVariantForm )
    {
      $addform = new \SimpleXMLElement('<form />');
      $fields  = $addform->addChild('fields');
      $fields->addAttribute('name', 'associations');

      $fieldset = $fields->addChild('fieldset');
      $fieldset->addAttribute('name', 'item_associations');
      $fieldset->addAttribute('addfieldprefix', 'Joomgallery\Component\Joomgallery\Administrator\Field');

      $currentLanguage = \is_object($data) ?
        ( $data->language ?? '' ) :
        ( $data['language'] ?? '' );

      $currentId = \is_object($data) ?
        (int)( $data->id ?? 0 ) :
        (int)( $data['id'] ?? 0 );

      foreach( $languages as $language )
      {
        if( $language->lang_code === $currentLanguage )
        {
          continue;
        }

        $field = $fieldset->addChild('field');
        $field->addAttribute('name', $language->lang_code);
        $field->addAttribute('type', 'jgimage');
        $field->addAttribute('forcedLanguage', $language->lang_code);
        $field->addAttribute('language', $language->lang_code);
        $field->addAttribute('label', $language->title);
        $field->addAttribute('translate_label', 'false');
        $field->addAttribute('extension', 'com_joomgallery');
        $field->addAttribute('select', 'true');
        $field->addAttribute('new', 'true');
        $field->addAttribute('edit', 'true');
        $field->addAttribute('clear', 'true');
        $field->addAttribute(
            'urlNew',
            'index.php?option=com_joomgallery&task=image.add&origin_id=' . (int)$this->app->getInput()->getInt('id') . '&forcedLanguage=' . $language->lang_code
        );
        $field->addAttribute(
            'urlEdit',
            'index.php?option=com_joomgallery&task=image.edit&forcedLanguage=' . $language->lang_code
        );
        $field->addAttribute('propagate', 'true');
      }
      $form->load($addform, false);
    }

    if( $isLanguageVariantForm )
    {
      foreach( [
        'tags',
        'published',
        'author',
        'access',
        'hidden',
        'featured',
        'created_time',
        'created_by',
        'modified_time',
        'modified_by',
        'metadesc',
        'metakey',
        'robots',
        'image',
        'MAX_FILE_SIZE',
        'filename',
        'replacetype',
        'replaceprocess',
        'date',
        'imgmetadata',
        'params',
        'version_note',
        'uploader',
        'multiple',
      ] as $fieldName )
      {
        $form->removeField($fieldName);
      }
    }

    parent::preprocessForm($form, $data, $group);
  }

  /**
   * Method to add tags to an image
   *
   * @param   int     $pk    The record primary key.
   * @param   array   $tags  List of tags to be added.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.4
   */
  public function addTags(int $pk, array $tags): bool
  {
    // Prepare tags array
    $tags = array_unique(array_map('trim', $tags));

    // Get existing tags
    $tags_model    = $this->component->getMVCFactory()->createModel('Tags', 'administrator');
    $existing_tags = [];
    $success       = true;

    foreach($tags_model->getItemsInList($tags) as $tag)
    {
      $existing_tags[$tag->title] = $tag->id;
    }

    // Add #new# prefix to new tags
    $tags = array_map(
        function ($tag) use ($existing_tags) {
        return isset($existing_tags[$tag]) ? $existing_tags[$tag] : '#new#' . $tag;
        },
        $tags
    );

    // Create tags
    $tags = $tags_model->storeTagsList($tags);

    if($tags === false)
    {
      $this->setError('Tags Model reports ' . $tags_model->getError());
      $this->getComponent()->addLog('Tags Model reports ', 'error', 'jerror');
      $success = false;
    }

    // Merge list
    $mapped_tags = array_map(fn($obj) => $obj->id, $tags_model->getMappedItems($pk));
    $tags        = array_merge($mapped_tags, $tags);

    // Update tags mapping
    if(!$tags_model->updateMapping($tags, $pk))
    {
      $this->setError('Tags Model reports ' . $tags_model->getError());
      $this->getComponent()->addLog('Tags Model reports ', 'error', 'jerror');
      $success = false;
    }

    return $success;
  }

  /**
   * Method to remove tags from an image
   *
   * @param   int     $pk    The record primary key.
   * @param   array   $tags  List of tags to be removed.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.4
   */
  public function removeTags(int $pk, array $tags): bool
  {
    // Prepare tags array
    $tags = array_unique(array_map('trim', $tags));

    // Get existing tags
    $tags_model    = $this->component->getMVCFactory()->createModel('Tags', 'administrator');
    $existing_tags = array_column($tags_model->getMappedItems($pk), 'id', 'title');
    $success       = true;

    foreach($tags_model->getItemsInList($tags) as $tag)
    {
      // Remove from existing tags array
      unset($existing_tags[$tag->title]);
    }

    // Update tags mapping
    if(!$tags_model->updateMapping(array_values($existing_tags), $pk))
    {
      $this->setError('Tags Model reports ' . $tags_model->getError());
      $this->getComponent()->addLog('Tags Model reports ', 'error', 'jerror');
      $success = false;
    }

    return $success;
  }

  /**
   * Method to test whether a record can be recreated.
   *
   * @param   object  $record  A record object.
   *
   * @return  boolean  True if allowed to recreate the record. Defaults to the permission for the component.
   *
   * @since   4.0
   */
  protected function canRecreate($record)
  {
    return $this->getAcl()->checkACL('core.edit', $this->typeAlias);
  }
}
