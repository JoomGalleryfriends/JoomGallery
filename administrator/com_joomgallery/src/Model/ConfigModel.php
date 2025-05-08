<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Language\Text;
use \Joomla\Filesystem\File;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Form\FormFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Form\FormFactory;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

/**
 * Config model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'config';

  /**
	 * @var    null  Form object
	 *
	 * @since  4.0.0
	 */
	protected $form = null;

  /**
	 * @var    array  Fieldset array
	 *
	 * @since  4.0.0
	 */
	protected $fieldsets = array();

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$this->form = $this->loadForm($this->typeAlias, 'config', array('control' => 'jform', 'load_data' => $loadData));

		if(empty($this->form))
		{
			return false;
		}

		// On edit, we get ID from state, but on save, we use data from input
		$id = (int) $this->getState('config.id', $this->app->getInput()->getInt('id', null));

    // Special treatment for Global Configuration set
    if($id === 1)
    {
      $this->form->setFieldAttribute('title', 'readonly', 'true');
      $this->form->setFieldAttribute('group_id', 'readonly', 'true');
    }

		// Object uses for checking edit state permission of image
		$record = new \stdClass();
		$record->id = $id;

		// Modify the form based on Edit State access controls.
		if(!$this->canEditState($record))
		{
			// Disable fields for display.
			$this->form->setFieldAttribute('published', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an article you can edit.
			$this->form->setFieldAttribute('published', 'filter', 'unset');
		}

		// Don't allow to change the created_user_id user if not allowed to access com_users.
    if(!$this->user->authorise('core.manage', 'com_users'))
    {
      $this->form->setFieldAttribute('created_by', 'filter', 'unset');
    }

		return $this->form;
	}

  /**
   * Get the FormFactoryInterface.
   *
   * @return  FormFactoryInterface
   *
   * @since   4.0.0
   * @throws  \UnexpectedValueException May be thrown if the FormFactory has not been set.
   */
  public function getFormFactory(): FormFactoryInterface
  {
    $formFactory = new FormFactory;

    return $formFactory;
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
		$data = $this->app->getUserState(_JOOM_OPTION.'.edit.config.data', array());

    if($this->item === null)
    {
      $this->item = $this->getItem();
    }

		if(empty($data))
		{
			$data = $this->item;
		}

		return $data;
	}

  /**
   * Method to allow derived classes to preprocess the form.
   *
   * @param   Form    $form   A Form object.
   * @param   mixed   $data   The data expected for the form.
   * @param   string  $group  The name of the plugin group to import (defaults to "content").
   *
   * @return  void
   *
   * @see     FormField
   * @since   4.0.0
   * @throws  \Exception if there is an error in the form event.
   */
  protected function preprocessForm(Form $form, $data, $group = 'content')
  {
    // Get fields with dynamic options
    $dyn_fields = $form->getDynamicFields();

    // Add options to dynamic fields
    foreach($dyn_fields as $key => $field)
    {
      $form->setDynamicOptions($field);
    }

    // Import the appropriate plugin group.
    PluginHelper::importPlugin($group);

    // Trigger the form preparation event.
    Factory::getApplication()->triggerEvent('onContentPrepareForm', array($form, $data));
  }

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{		
    if($item = parent::getItem($pk))
    {
      if(isset($item->params))
      {
        $item->params = json_encode($item->params);
      }
    }

    // Set jg_staticprocessing data
    $item->jg_staticprocessing = $this->getStaticprocessing();

    return $item;
	}

  /**
	 * Method to get all available fieldsets from form.
	 *
	 * @return  array   Array with available fieldsets
	 *
	 * @since   4.0.0
	 */
  public function getFieldsets()
  {
    // Fill fieldset array
		foreach($this->form->getFieldsets() as $key => $fieldset)
		{
			$parts = \explode('-',$key);
			$level = \count($parts);

			$fieldset->level = $level;
			$fieldset->title = \end($parts);

			$this->setFieldset($key, array('this'=>$fieldset));
		}

		// Add permissions fieldset to level 1 fieldsets
		$permissions = array('name' => 'permissions',
							'label' => 'JGLOBAL_ACTION_PERMISSIONS_LABEL',
							'description' => '',
							'type' => 'tab',
							'level' => 1,
							'title' => 'permissions');
		$this->fieldsets['permissions'] = array('this' => (object) $permissions);

    return $this->fieldsets;
  }

  /**
	 * Add a fieldset to the fieldset array.
   * source: https://stackoverflow.com/questions/13308968/create-infinitely-deep-multidimensional-array-from-string-in-php
   *
   * @param  string  $key    path for the value in the array
   * @param  string  $value  the value to be placed at the defined path
	 *
	 * @return void
	 *
	 */
	protected function setFieldset($key, $value)
	{
    if(false === ($levels = \explode('-',$key)))
    {
      return;
    }

    $pointer = &$this->fieldsets;
    for ($i=0; $i < \sizeof($levels); $i++)
    {
      if(!isset($pointer[$levels[$i]]))
      {
        $pointer[$levels[$i]] = array();
      }

      $pointer = &$pointer[$levels[$i]];
    }

    $pointer = $value;
  }

	/**
	 * Method to duplicate an Config
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
		if(!$this->user->authorise('core.create', _JOOM_OPTION))
		{
			$this->component->addLog(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'), 'error', 'jerror');
			throw new \Exception(Text::_('JERROR_CORE_CREATE_NOT_PERMITTED'));
		}

		$context = $this->option . '.' . $this->name;

		// Include the plugins for the save events.
		PluginHelper::importPlugin($this->events_map['save']);

		$table = $this->getTable();

		foreach($pks as $pk)
		{			
      if($table->load($pk, true))
      {
        // Reset the id to create a new record.
        $table->id = 0;

        if(!$table->check())
        {
          $this->component->addLog($table->getError(), 'error', 'jerror');
          throw new \Exception($table->getError());
        }        

        // Trigger the before save event.
        $result = $this->app->triggerEvent($this->event_before_save, array($context, &$table, true, $table));

        if(in_array(false, $result, true) || !$table->store())
        {
          $this->component->addLog($table->getError(), 'error', 'jerror');
          throw new \Exception($table->getError());
        }

        // Trigger the after save event.
        $this->app->triggerEvent($this->event_after_save, array($context, &$table, true));
      }
      else
      {
        $this->component->addLog($table->getError(), 'error', 'jerror');
        throw new \Exception($table->getError());
      }			
		}

		// Clean cache
		$this->cleanCache();

		return true;
	}

  /**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success, False on error.
	 *
	 * @since   4.0.0
	 */
	public function save($data)
	{
    // id of the data to be saved
    $id = intval($data['id']);

    $mod_items = $this->component->getMVCFactory()->createModel('imagetypes', 'administrator');
    $model     = $this->component->getMVCFactory()->createModel('imagetype', 'administrator');

    // get all existing imagetypes in the database
    $imagetypes_list = $mod_items->getItems();
    $detail_path     = $imagetypes_list[1]->path;

    foreach($data['jg_staticprocessing'] as $staticprocessing)
    {
      // load data
      $imagetype_db = $model->getItem(array('typename' => $staticprocessing['jg_imgtypename']));

      // check if forbidden imagetypes gets disables
      $forbidden = array('detail', 'thumbnail');
      if(\in_array($staticprocessing['jg_imgtypename'], $forbidden) && $staticprocessing['jg_imgtype'] != '1')
      {
        // not allowed to unset this imagetype
        $this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DEACTIVATE_IMAGETYPE', $staticprocessing['jg_imgtypename']));
        //$this->app->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DEACTIVATE_IMAGETYPE', $staticprocessing['jg_imgtypename']));

        $staticprocessing['jg_imgtype'] = 1;
      }
      
      // update data
      $imagetype_db->typename = $staticprocessing['jg_imgtypename'];
      $imagetype_db->path     = $staticprocessing['jg_imgtypepath'];
      $imagetype_db->params   = $this->updateParams($staticprocessing, $imagetype_db->params);

      if(empty($imagetype_db->typename))
      {
        // we are currently handling a deleted imagetype
        // skip he rest of the current loop iteration
        continue;
      }

      if(\is_null($imagetype_db->id))
      {
        // prepare data to create new imagetype row
        $imagetype_db->id       = 0;
        $imagetype_db->ordering = '';
        
        if(empty($imagetype_db->path))
        {
          // create a default path for new imagetype row
          $path_parts = \explode('/',$detail_path);
          \array_pop($path_parts);

          $imagetype_db->path = \implode('/',$path_parts).'/'.$imagetype_db->typename;
        }
      }

      // save data
      $model->save((array) $imagetype_db);

      // unset current imagetype from imagetypes_db list
      foreach($imagetypes_list as $key => $imagetype)
      {
        if ($imagetype->typename == $staticprocessing['jg_imgtypename'])
        {
          unset($imagetypes_list[$key]);
        }
      }
    }

    // delete unused imagetypes from db
    $forbidden = array('original', 'detail', 'thumbnail');
    foreach($imagetypes_list as $imagetype_list)
    {
      if(\in_array($imagetype_list->typename, $forbidden))
      {
        // not allowed to delete this imagetype
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DELETE_IMAGETYPE'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DELETE_IMAGETYPE'), 'error', 'jerror');
        //$this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_DELETE_IMAGETYPE'));
      }
      else
      {
        $model->delete($imagetype_list->id);
      }
    }

    $data['jg_staticprocessing'] = '';

    // Special treatment for Global Configuration set
    if($id === 1)
    {
      $data['title']    = 'Global Configuration';
      $data['group_id'] = '1';
    }

    return parent::save($data);
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
    // remove record with id=1 from the list of primary keys to change
    if(($key = \array_search(1, $pks)) !== false)
    {
      unset($pks[$key]);

      // It is not allowed to unpublish the global configuration set
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_UNPUBLISH_CONFIG_SET'));
      $this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_NOT_ALLOWED_UNPUBLISH_CONFIG_SET'), 'error', 'jerror');
    }

    parent::publish($pks, $value);
  }

  /**
	 * Initialize new stdObject with default config params of jg_staticprocessing.
	 *
   * @param   string     $type    Imagetype (default:original)
   * 
	 * @return  \stdClass   Default config params of jg_staticprocessing
	 *
	 * @since   4.0.0
	 */
  protected function newStaticprocessing($type='original')
  {
    $obj = array();

    $obj['jg_imgtype']            = '1';
    $obj['jg_imgtypename']        = '';
    $obj['jg_imgtypepath']        = '';
    $obj['jg_imgtyperesize']      = '0';
    $obj['jg_imgtypewidth']       = '2000';
    $obj['jg_imgtypeheight']      = '2000';
    $obj['jg_cropposition']       = '2';
    $obj['jg_imgtypeorinet']      = '1';
    $obj['jg_imgtypeanim']        = '0';
    $obj['jg_imgtypesharpen']     = '0';
    $obj['jg_imgtypequality']     = 100;
    $obj['jg_imgtypewatermark']   = '0';
    $obj['jg_imgtypewtmsettings'] = '{}';

    return (object) $obj;
  }

  /**
	 * Initialize new stdObject with default config params of jg_staticprocessing.
	 *
   * @param   string     $type    Imagetype (default:original)
   * 
	 * @return  string   Params json string
	 *
	 * @since   4.0.0
	 */
  protected function newImagetypeParams($type='original')
  {
    switch($type)
    {
      case 'detail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"3","jg_imgtypewidth":"1000","jg_imgtypeheight":"1000","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":"80","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"{}"}';
        break;

      case 'thumbnail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"4","jg_imgtypewidth":"250","jg_imgtypeheight":"250","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"1","jg_imgtypequality":"60","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"{}"}';
        break;
      
      default:
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"0","jg_imgtypewidth":"2000","jg_imgtypeheight":"2000","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":"100","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"{}"}';
        break;
    }
  }

  /**
	 * Loads jg_staticprocessing data from imagetypes.
   * 
	 * @return  object   static processing data
	 *
	 * @since   4.0.0
	 */
  public function getStaticprocessing()
  {
    // Load imagetypes from database
    $new_staticprocessing = array();
    $imagetypes           = JoomHelper::getRecords('imagetypes');

    // Replace jg_staticprocessing based on imagetypes
    foreach($imagetypes as $key => $imagetype)
    {
      // initialize stdClass object
      if(!isset($new_staticprocessing['jg_staticprocessing'.$key]))
      {
        $new_staticprocessing['jg_staticprocessing'.$key] = new \stdClass();
      }

      // create staticprocessing array
      $new_staticprocessing['jg_staticprocessing'.$key]->jg_imgtypename = $imagetype->typename;
      $new_staticprocessing['jg_staticprocessing'.$key]->jg_imgtypepath = $imagetype->path;

      foreach($imagetype->params as $k => $param)
      {
        $new_staticprocessing['jg_staticprocessing'.$key]->{$k} = $param;
      }
    }

    // Return jg_staticprocessing data
    return \json_encode((object) $new_staticprocessing);
  }

  /**
	 * Decode params string.
	 *
   * @param   string      $params     Json string with params
   * 
	 * @return  CMSObject   Params object
	 *
	 * @since   4.0.0
	 */
  protected function decodeParams($params)
  {
    $params = (string) $params;

    if(isset($params))
    {
      $params = json_decode($params);
    }

    return $params;
  }

  /**
	 * Update the staticprocessing params string.
	 *
   * @param   array    $data       New submitted params form data
   * @param   string   $old_data   JSON string of old params
   * 
	 * @return  string   Params JSON string
	 *
	 * @since   4.0.0
	 */
  protected function updateParams(array $data, string $old_data=''): string
  {
    // Decode old params string
    if($old_data === '')
    {
      $old_data = array();
    }
    else
    {
      $old_data = \json_decode($old_data, true);
    }

    // support for jg_imgtypename
    if(\array_key_exists('jg_imgtypename', $data))
    {
      unset($data['jg_imgtypename']);
    }

    // support for jg_imgtypepath
    if(\array_key_exists('jg_imgtypepath', $data))
    {
      unset($data['jg_imgtypepath']);
    }

    // support for jg_imgtype
    if(!\array_key_exists('jg_imgtype', $data))
    {
      if(\array_key_exists('jg_imgtype', $old_data))
      {
        $data['jg_imgtype'] = $old_data['jg_imgtype'];
      }
      else
      {
        $data['jg_imgtype'] = 1;
      }      
    }

    return json_encode($data);
  }

  /**
	 * Method to reset form data to default values.
	 *
	 * @param   array  $data   Form data array
	 * 
	 * @return  array  Form data array with default data
	 *
	 * @since   4.0.0
	 */
	public function resetData($data)
	{
		// Load config form
		$xmlfile = _JOOM_PATH_ADMIN . '/forms/config.xml';
		$cform = new Form('configForm');
		$cform->loadFile($xmlfile);

		foreach($data['jform'] as $key => $value)
		{
			if(strpos($key, 'jg_') !== false)
			{
				if($key == 'jg_replaceinfo')
				{
					$default = '{"jg_replaceinfo0":{"target":"date","source":"EXIF-36867"}}';
				}
				else if($key == 'jg_dynamicprocessing')
				{
					$default = '{"jg_dynamicprocessing0":{"jg_imgtype":"0","jg_imgtypename":"original","jg_imgtyperesize":"0","jg_imgtypewidth":"2000","jg_imgtypeheight":"2000","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":100,"jg_imgtypewatermark":"0","jg_imgtypewtmsettings":{"jg_watermarkpos":"9","jg_watermarkzoom":"0","jg_watermarksize":15,"jg_watermarkopacity":80}},"jg_dynamicprocessing1":{"jg_imgtype":"0","jg_imgtypename":"detail","jg_imgtyperesize":"0","jg_imgtypewidth":"1000","jg_imgtypeheight":"1000","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":80,"jg_imgtypewatermark":"0","jg_imgtypewtmsettings":{"jg_watermarkpos":"9","jg_watermarkzoom":"0","jg_watermarksize":15,"jg_watermarkopacity":80}},"jg_dynamicprocessing2":{"jg_imgtype":"0","jg_imgtypename":"thumbnail","jg_imgtyperesize":"0","jg_imgtypewidth":"360","jg_imgtypeheight":"360","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":60,"jg_imgtypewatermark":"0","jg_imgtypewtmsettings":{"jg_watermarkpos":"9","jg_watermarkzoom":"0","jg_watermarksize":15,"jg_watermarkopacity":80}}}';
				}
				else if($key == 'jg_staticprocessing')
				{
					// Load imageconvert subform
					$xmlfile_subform = _JOOM_PATH_ADMIN . '/forms/subform_imageconvert.xml';
					$subform = new Form('imageconvertSubform');
					$subform->loadFile($xmlfile_subform);

					// load default from imageconvert subform xml
					foreach($value as $nmb => $array)
					{
						if(\in_array($array['jg_imgtypename'],array('original', 'detail', 'thumbnail')))
						{
							foreach($array as $subformkey => $subformvalue)
							{
								if($subformkey == 'jg_imgtypewtmsettings')
								{
									// Load imagewatermark subform
									$xmlfile_wtmsubform = _JOOM_PATH_ADMIN . '/forms/subform_imagewatermark.xml';
									$wtm_subform = new Form('imagewatermarkSubform');
									$wtm_subform->loadFile($xmlfile_wtmsubform);

									// initialize watermark array
									$default = array();

									foreach($subformvalue as $wtm_key => $wtm_value)
									{
										// load default from xml file
										$default_wtm = $wtm_subform->getField($wtm_key)->getAttribute('default', 'not found');

										if($default_wtm === 'not found')
										{
											$this->component->addLog('Watermark subform field with name '.$wtm_key.' does not have any default value!', 'error', 'jerror');
											throw new \Exception('Watermark subform field with name '.$wtm_key.' does not have any default value!', 1);
										}

										// set default to watermark array
										$default[$wtm_key] = $default_wtm;
									}

								}
								else
								{
									$reset_str = $subform->getField($subformkey)->getAttribute('reset', 'not found');
									
									if($default === 'not found')
									{
										$this->component->addLog('Convert subform field with name '.$key.' does not have any reset value!', 'error', 'jerror');
										throw new \Exception('Convert subform field with name '.$key.' does not have any reset value!', 1);
									}

									$reset_arr = $this->getResetArray($reset_str);

									$default = $reset_arr[$array['jg_imgtypename']];
								}

								// set default to data array
								$data['jform']['jg_staticprocessing'][$nmb][$subformkey] = $default;
							}
						}
						else
						{
							unset($data['jform']['jg_staticprocessing'][$nmb]);
						}
					}

					continue; 
				}
				else
				{
					// load default from xml file
					$default = $cform->getField($key)->getAttribute('default', 'not found');
				}

				if($default === 'not found')
				{
					$this->component->addLog('Config field with name '.$key.' does not have any default value!', 'error', 'jerror');
					throw new \Exception('Config field with name '.$key.' does not have any default value!', 1);
				}

				// set default to data array
				$data['jform'][$key] = $default;
			}
		}

		return $data;
	}

	/**
	 * Method to retrieve uploaded json file content
	 *
	 * @param   array    $file        Uploaded file info
	 * @param   string   $fieldname   Name of the form field
	 * 
	 * @return  array    Associative array containing form data of json file
	 *
	 * @since   4.0.0
	 */
	public function getJSONfile($file, $fieldname)
	{
		// Get form field
		$xml = _JOOM_PATH_ADMIN . '/forms/config.xml';
		$form = new Form('configForm');
		$form->loadFile($xml);
		$field = $form->getField($fieldname);

    // Check for upload error codes
    if($file['error'] > 0)
    {
      if($file['error'] == 4)
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_FILE_NOT_UPLOADED'));

        return false;
      }
      $uploader = JoomHelper::getService('Uploader', array('html'));
      $this->setError($uploader->checkError($file['error']));

      return false;
    }	

		// Check file size
		$filesize = intval($field->getAttribute('size', '512000'));
		if($file['size'] > $filesize)
		{
			// Upload failed
			$this->setError(Text::_('COM_JOOMGALLERY_ERROR_HTML_MAXFILESIZE'), 'error');
			$this->component->addLog(Text::_('COM_JOOMGALLERY_ERROR_HTML_MAXFILESIZE'), 'error', 'jerror');

			return false;
		}

		// Check file extension
		if(strtolower(File::getExt($file['name'])) != 'json')
		{
			// Invalid file extension
			$this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_INVALID_FILE_EXTENSION', 'json', $file['name']), 'error');
			$this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_INVALID_FILE_EXTENSION', 'json', $file['name']), 'error', 'jerror');

			return false;
		}

		// Retrieve file content
		$json_string = \file_get_contents($file['tmp_name']);

		// Check file content
		$json = json_decode($json_string, true);
    
   		if(json_last_error() !== JSON_ERROR_NONE)
		{
			// JSON not valid
			$this->setError(Text::sprintf('COM_JOOMGALLERY_ERROR_INVALID_FILE_CONTENT', $file['name']), 'error');
			$this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_INVALID_FILE_CONTENT', $file['name']), 'error', 'jerror');

			return false;
		}

		return $json;
	}

	/**
	 * Method to create the imagetype reset array from string.
	 *
	 * @param   string  $string   String containing the reset values
	 * 
	 * @return  array   Array with reset values for each image type
	 *
	 * @since   4.0.0
	 */
	protected function getResetArray($string)
	{
		$array = array();

		$imgtypes = \explode(';', $string);

		foreach($imgtypes as $imgtype)
		{
			$content = \explode(':', $imgtype);

			switch($content[0])
			{
				case 'orig':
					$name = 'original';
					break;
				case 'det':
					$name = 'detail';
					break;
				case 'thumb':
					$name = 'thumbnail';
					break;
				default:
					$name = false;
					break;
			}

			if($name)
			{
				$array[$name] = $content[1];
			}			
		}

		return $array;
	}
}
