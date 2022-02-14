<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;
// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use \Joomla\CMS\Plugin\PluginHelper;
use \Joomla\CMS\Helper\TagsHelper;
use \Joomla\CMS\Form\Form;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;
use stdClass;

/**
 * Config model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigModel extends JoomAdminModel
{
	/**
	 * @var    string  The prefix to use with controller messages.
	 *
	 * @since  4.0.0
	 */
	protected $text_prefix = 'COM_JOOMGALLERY';

	/**
	 * @var    string  Alias to manage history control
	 *
	 * @since  4.0.0
	 */
	public $typeAlias = 'com_joomgallery.config';

	/**
	 * @var    null  Item data
	 *
	 * @since  4.0.0
	 */
	protected $item = null;	

	/**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Config', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

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
		$form = $this->loadForm('com_joomgallery.config', 'config', array('control' => 'jform', 'load_data' => $loadData));

		if(empty($form))
		{
			return false;
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
		$data = $this->app->getUserState('com_joomgallery.edit.config.data', array());

		if(empty($data))
		{
			if($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;

      // Load imagetypes from database
      $old_staticprocessing = \json_decode($data->jg_staticprocessing);
      $new_staticprocessing = array();
      $imagetypes           = JoomHelper::getRecords('imagetypes');

      // Replace jg_staticprocessing based on imagetypes
      foreach($imagetypes as $key => $imagetype)
      {
        // Search the corresponding entry in the old config values
        $i = \null;
        foreach ($old_staticprocessing as $c => $staticp)
        {
          if($staticp->jg_imgtypename == $imagetype->typename)
          {
            $i = $c;
          }
        }

        if(isset($old_staticprocessing->{$i}))
        {
          // Transfer old staticprocessing data 
          $new_staticprocessing['jg_staticprocessing'.$key] = $old_staticprocessing->{$i};
        }
        else
        {
          // Initialize new staticprocessing data
          $new_staticprocessing['jg_staticprocessing'.$key] = $this->newStaticprocessing();
        }
        
        // Replace values
        $new_staticprocessing['jg_staticprocessing'.$key]->jg_imgtypename = $imagetype->typename;
        $new_staticprocessing['jg_staticprocessing'.$key]->jg_imgtypepath = $imagetype->path;

        foreach($imagetype->params as $k => $param)
        {
          $new_staticprocessing['jg_staticprocessing'.$key]->{$k} = $param;
        }
      }

      // Set replaced jg_staticprocessing data
      $data->jg_staticprocessing = \json_encode((object) $new_staticprocessing);

			// // Support for multiple or not foreign key fields
      // $form   = Form::getInstance('com_joomgallery.config', JPATH_COMPONENT_ADMINISTRATOR . '/forms/config.xml');
      // $fields = $form->getXml()->xpath("//field[@type='list']");

      // foreach ($fields as $key => $field)
      // {
      //   $fieldname = (string) $field->attributes()->{'name'};
        
      //   $array = array();

      //   foreach((array) $data->{$fieldname} as $value)
      //   {
      //     if(!is_array($value))
      //     {
      //       $array[] = $value;
      //     }
      //   }

      //   if(!empty($array))
      //   {
      //     $data->{$fieldname} = $array;
      //   }
      // }
		}

		return $data;
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

    return $item;		
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
		if(!$this->user->authorise('core.create', 'com_joomgallery'))
		{
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
          throw new \Exception($table->getError());
        }        

        // Trigger the before save event.
        $result = $this->app->triggerEvent($this->event_before_save, array($context, &$table, true, $table));

        if(in_array(false, $result, true) || !$table->store())
        {
          throw new \Exception($table->getError());
        }

        // Trigger the after save event.
        $this->app->triggerEvent($this->event_after_save, array($context, &$table, true));
      }
      else
      {
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
	 * @since   1.6
	 */
	public function save($data)
	{  
    $mod_items = $this->component->getMVCFactory()->createModel('imagetypes');
    $model     = $this->component->getMVCFactory()->createModel('imagetype');

    // get all existing imagetypes    
    $imagetypes_arr = $mod_items->getItems();

    // update/add used imagetypes
    foreach ($data['jg_staticprocessing'] as $staticprocessing)
    {      
      // load data
      $imagetype_data = $model->getItem(array('typename' => $staticprocessing['jg_imgtypename']));

      // check if forbidden imagetypes gets unabled
      $forbidden = array('detail', 'thumbnail');
      if(\in_array($staticprocessing['jg_imgtypename'], $forbidden) && $staticprocessing['jg_imgtype'] != '1')
      {
        // not allowed to unset this imagetype
        $this->app->enqueueMessage('It is not allowed to unset the "'.$staticprocessing['jg_imgtypename'].'" imagetype. Imagetype "'.$staticprocessing['jg_imgtypename'].'" re-enabled.');

        $staticprocessing['jg_imgtype'] = 1;
      }
      
      // update data
      $imagetype_data->typename = $staticprocessing['jg_imgtypename'];
      $imagetype_data->path     = $staticprocessing['jg_imgtypepath'];
      $imagetype_data->params   = $this->encodeParams($staticprocessing);

      if(\is_null($imagetype_data->id))
      {
        // prepare data to create new imagetype row
        $imagetype_data->id       = 0;
        $imagetype_data->ordering = '';
      }

      // save data
      $model->save((array) $imagetype_data);

      // unset this used imagetypes from array
      foreach ($imagetypes_arr as $key => $imagetype)
      {
        if ($imagetype->typename == $staticprocessing['jg_imgtypename'])
        {
          unset($imagetypes_arr[$key]);
        }
      }
    }

    // delete unused imagetypes
    $forbidden = array('original', 'detail', 'thumbnail');
    foreach ($imagetypes_arr as $imagetype)
    {
      if(\in_array($imagetype->typename, $forbidden))
      {
        // not allowed to delete this imagetype
        $this->app->enqueueMessage('It is not allowed to delete this imagetype. Imagetype restored.');
      }
      else
      {
        $model->delete($imagetype->id);
      }
    }

    return parent::save($data);
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
		jimport('joomla.filter.output');

		if(empty($table->id))
		{
			// Set ordering to the last item if not set
			if (@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__joomgallery_configs');
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}

  /**
	 * Initialize new stdObject with default config params of jg_staticprocessing.
	 *
   * @param   string     $type    Imagetype (default:original)
   * 
	 * @return  stdClass   Default config params of jg_staticprocessing
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
    $obj['jg_imgtypewidth']       = '';
    $obj['jg_imgtypeheight']      = '';
    $obj['jg_cropposition']       = '2';
    $obj['jg_imgtypeorinet']      = '1';
    $obj['jg_imgtypeanim']        = '0';
    $obj['jg_imgtypesharpen']     = '0';
    $obj['jg_imgtypequality']     = 100;
    $obj['jg_imgtypewatermark']   = '0';
    $obj['jg_imgtypewtmsettings'] = [];

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
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"3","jg_imgtypewidth":"1000","jg_imgtypeheight":"1000","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"0","jg_imgtypequality":"80","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;

      case 'thumbnail':
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"4","jg_imgtypewidth":"250","jg_imgtypeheight":"250","jg_cropposition":"2","jg_imgtypeorinet":"1","jg_imgtypeanim":"0","jg_imgtypesharpen":"1","jg_imgtypequality":"60","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
      
      default:
        $params = '{"jg_imgtype":"1","jg_imgtyperesize":"0","jg_imgtypewidth":"","jg_imgtypeheight":"","jg_cropposition":"2","jg_imgtypeorinet":"0","jg_imgtypeanim":"1","jg_imgtypesharpen":"0","jg_imgtypequality":"100","jg_imgtypewatermark":"0","jg_imgtypewtmsettings":"[]"}';
        break;
    }
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
	 * Encode params string.
	 *
   * @param   array    $data     Form data
   * 
	 * @return  string   Params json string
	 *
	 * @since   4.0.0
	 */
  protected function encodeParams($data)
  {
    if(\array_key_exists('jg_imgtypename', $data))
    {
      unset($data['jg_imgtypename']);
    }

    if(\array_key_exists('jg_imgtypepath', $data))
    {
      unset($data['jg_imgtypepath']);
    }

    return json_encode($data);
  }
}
