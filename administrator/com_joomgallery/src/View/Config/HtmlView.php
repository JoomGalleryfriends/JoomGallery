<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\View\Config;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\FormHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View class for a single Config.
 * @package JoomGallery
 * @since   4.0.0
 */
class HtmlView extends JoomGalleryView
{
	protected $state;

	protected $item;

	protected $form;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		$this->state     = $this->get('State');
		$this->item      = $this->get('Item');
		$this->form      = $this->get('Form');
    $this->fieldsets = array();
    
    // Add options to the replaceinfo field
    $this->addReplaceinfoOptions();

    // Fill fieldset array
    foreach($this->form->getFieldsets() as $key => $fieldset)
    {
      $parts = \explode('-',$key);
      $level = \count($parts);

      $fieldset->level = $level;
      $fieldset->title = \end($parts);

      $this->setFieldset($key, array('this'=>$fieldset));
    }

		// Check for errors.
		if(count($errors = $this->get('Errors')))
		{
			throw new \Exception(implode("\n", $errors));
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

		$user  = Factory::getUser();
		$isNew = ($this->item->id == 0);

		if(isset($this->item->checked_out))
		{
			$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->get('id'));
		}
		else
		{
			$checkedOut = false;
		}

		$canDo = JoomHelper::getActions();

		ToolbarHelper::title(Text::_('COM_JOOMGALLERY_CONFIG_CONFIGURATION_MANAGER').' :: '.Text::_('COM_JOOMGALLERY_COMMON_TOOLBAR_EDIT').' '.Text::_('COM_JOOMGALLERY_COMMON_CONFIG'), "sliders-h");

		// If not checked out, can save the item.
		if(!$checkedOut && ($canDo->get('core.edit') || ($canDo->get('core.create'))))
		{
			ToolbarHelper::apply('config.apply', 'JTOOLBAR_APPLY');
			ToolbarHelper::save('config.save', 'JTOOLBAR_SAVE');
		}

		if(!$checkedOut && ($canDo->get('core.create')))
		{
			ToolbarHelper::custom('config.save2new', 'save-new.png', 'save-new_f2.png', 'JTOOLBAR_SAVE_AND_NEW', false);
		}

		// If an existing item, can save to a copy.
		if(!$isNew && $canDo->get('core.create'))
		{
			ToolbarHelper::custom('config.save2copy', 'save-copy.png', 'save-copy_f2.png', 'JTOOLBAR_SAVE_AS_COPY', false);
		}

		if(empty($this->item->id))
		{
			ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CANCEL');
		}
		else
		{
			ToolbarHelper::cancel('config.cancel', 'JTOOLBAR_CLOSE');
		}
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
    if(false === ($levels = explode('-',$key)))
    {
      return;
    }

    $pointer = &$this->fieldsets;
    for ($i=0; $i < sizeof($levels); $i++)
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
	 * Method to get an array of JFormField objects in a given fieldset by name.
   *
   * @param    string  $name   name of the fieldset
	 *
	 * @return   array   Array with field names
	 *
	 */
  public function getFieldset($name)
	{
    $xml = null;

    // Attempt to load the XML file.
    $filename = JPATH_COMPONENT_ADMINISTRATOR.DIRECTORY_SEPARATOR.'forms'.DIRECTORY_SEPARATOR.'config.xml';
    if(file_exists($filename))
    {
      $xml = simplexml_load_file($filename);
    }

    // Initialise fields array
    $fields = array();

    // Make sure there is a valid Form XML document.
		if(!($xml instanceof \SimpleXMLElement))
		{
			throw new \UnexpectedValueException('XML is not an instance of SimpleXMLElement');
		}

    /*
		 * Get an array of <field /> elements that are underneath a <fieldset /> element
		 * with the appropriate name attribute, and also any <field /> elements with
		 * the appropriate fieldset attribute. To allow repeatable elements only fields
		 * which are not descendants of other fields are selected.
		 */
    $elements = $xml->xpath('(//fieldset[@name="' . $name . '"]/field | //field[@fieldset="' . $name . '"])[not(ancestor::field)]');

    // If no field elements were found return empty.
		if(empty($elements))
		{
			return $fields;
		}

    // Build the result array from the found field elements.
		foreach($elements as $element)
		{
			// Get the field groups for the element.
			$attrs = $element->xpath('ancestor::fields[@name]/@name');
			$groups = array_map('strval', $attrs ? $attrs : array());
			$group = implode('.', $groups);

			// If the field is successfully loaded add it to the result array.
      // Get the field type.
      $type = $element['type'] ? (string) $element['type'] : 'text';

      // Load the FormField object for the field.
      $field = FormHelper::loadFieldType($type);

      // If the object could not be loaded, get a text field object.
      if($field === false)
      {
        $field = FormHelper::loadFieldType('text');
      }

      /*
      * Get the value for the form field if not set.
      * Default to the translated version of the 'default' attribute
      * if 'translate_default' attribute if set to 'true' or '1'
      * else the value of the 'default' attribute for the field.
      */
      $default = (string) ($element['default'] ? $element['default'] : $element->default);

      if(($translate = $element['translate_default']) && ((string) $translate === 'true' || (string) $translate === '1'))
      {
        $lang = Factory::getLanguage();

        if($lang->hasKey($default))
        {
          $debug = $lang->setDebug(false);
          $default = Text::_($default);
          $lang->setDebug($debug);
        }
        else
        {
          $default = Text::_($default);
        }
      }

      $value = $this->form->getValue((string) $element['name'], $group, $default);

      // Setup the FormField object.
      $field->setForm($this->form);
      $field->setup($element, $value, $group);

			if($field)
			{
				$fields[$field->id] = $field;
			}
		}

		return $fields;
  }

  /**
	 * Method to add options to the jg_replaceinfo->source field
   * based on its attributes
	 *
	 * @return   void
	 *
	 */
  protected function addReplaceinfoOptions()
  {
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/iptcarray.php';
    require_once JPATH_ADMINISTRATOR.'/components/'._JOOM_OPTION.'/includes/exifarray.php';
    $lang = Factory::getLanguage();
    $lang->load(_JOOM_OPTION.'.exif', JPATH_ADMINISTRATOR);
    $lang->load(_JOOM_OPTION.'.iptc', JPATH_ADMINISTRATOR);

    // create dropdown list of metadata sources
    $exif_options = $this->form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->getAttribute('EXIF');
    $iptc_options = $this->form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->getAttribute('IPTC');
    $exif_options = \json_decode(\str_replace('\'', '"', $exif_options));
    $iptc_options = \json_decode(\str_replace('\'', '"', $iptc_options));

    foreach ($exif_options as $key => $exif_option)
    {
      // add all defined exif options
      $text  = $exif_config_array[$exif_option[0]][$exif_option[1]]['Name'];
      $value = $exif_option[0] . '-' . $exif_option[1];
      $this->form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->addOption($text, array('value'=>$value));
    }

    foreach ($iptc_options as $key => $iptc_option)
    {
      // add all defined iptc options
      $text  = $iptc_config_array[$iptc_option[0]][$iptc_option[1]]['Name'];
      $value = $iptc_option[0] . '-' . $iptc_option[1];
      $this->form->getField('jg_replaceinfo')->loadSubForm()->getField('source')->addOption($text, array('value'=>$value));
    }
  }
}
