<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Form;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Form\FormField;
use \Joomla\CMS\Event\AbstractEvent;
use \Joomla\Database\DatabaseInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;

/**
 * Form Class for the Joomla Platform.
 *
 * This class implements a robust API for constructing, populating, filtering, and validating forms.
 * It uses XML definitions to construct form fields and a variety of field and rule classes to
 * render and validate the form.
 *
 * @link   https://www.w3.org/TR/html4/interact/forms.html
 * @link   https://html.spec.whatwg.org/multipage/forms.html
 * @since  4.0.0
 */
class ConfigForm extends Form
{
  /**
   * Method to get an array of FormField objects in a given fieldset by name.  If no name is
   * given then all fields are returned.
   *
   * @param   string  $set  Name of the fieldset.
   *
   * @return  FormField[]  The array of FormField objects in the fieldset.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function getFieldset($set = null)
  {
    $fields = [];

    // Get all of the field elements in the fieldset.
    if($set)
    {
      $elements = $this->findFieldsByFieldset($set);
    }

    // If no field elements were found return empty.
    if(empty($elements))
    {
      return $fields;
    }

    // Build the result array from the found field elements.
    foreach ($elements as $element)
    {
      // Get the field groups for the element.
      $attrs = $element->xpath('ancestor::fields[@name]/@name');
      $groups = array_map('strval', $attrs ?: []);
      $group = implode('.', $groups);

      // If the field is successfully loaded add it to the result array.
      if ($field = $this->loadField($element, $group))
      {
        $fields[$field->id] = $field;
      }
    }

    return $fields;
  }

  /**
   * Method to get an array of FormField objects containing an attribute 'dynamic' set to true.
   *
   * @return  FormField[]  The array of FormField objects in the fieldset.
   *
   * @since   4.0.0
   * @throws  \Exception
   */
  public function getDynamicFields()
  {
    $fields = [];

    $elements = $this->findFieldsByAttribute('dynamic', 'true');

    // If no field elements were found return empty.
    if(empty($elements))
    {
      return $fields;
    }

    // Build the result array from the found field elements.
    foreach ($elements as $element)
    {
      // Get the field groups for the element.
      $attrs = $element->xpath('ancestor::fields[@name]/@name');
      $groups = array_map('strval', $attrs ?: []);
      $group = implode('.', $groups);

      // If the field is successfully loaded add it to the result array.
      if ($field = $this->loadField($element, $group))
      {
        $fields[$field->id] = $field;
      }
    }

    return $fields;
  }

  /**
   * Method to add field options provided by a script.
   * Script name provided in the field attribute 'script'.
   * 
   * @param   FormField|string  $name   The name of the field for which to set the value.
   * @param   string            $group  The optional dot-separated form group path on which to find the field.
   *
   * @return  boolean  True on success, false otherwise
   *
   * @since   4.0.0
   */
  public function setDynamicOptions($field, $group = null)
  {
    // Load form field
    if(!($field instanceof FormField))
    {
      $field = $this->getField($element, $group);
    }

    // Get script
    $script = $field->getAttribute('script', '');
    if(empty($script))
    {
      return false;
    }

    // Load options
      // Option 1: Plugin listening to onJoomGetOptions
      $event = AbstractEvent::create(
                    'onJoomGetOptions',
                    [
                      'subject' => $this,
                      'context' => 'com_joomgallery.config.form',
                      'script'  => $script,
                    ]
      );
      Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);
      $options = $event->getArgument('result', array());

      // Option 2: Load script from ConfigHelper
      if(\method_exists('\Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper', $script))
      {
        $options = \array_merge($options, ConfigHelper::{$script}($this));
      }

    // Add options to field
    foreach($options as $key => $option)
    {
      $field->addOption($option['text'], array('value'=>$option['value']));
    }
  }

  /**
   * Method to get an array of `<field>` elements from the form XML document which are in a specified fieldset by name.
   *
   * @param   string  $name  The name of the fieldset.
   *
   * @return  \SimpleXMLElement[]|boolean  Boolean false on error or array of SimpleXMLElement objects.
   *
   * @since   4.0.0
   */
  protected function &findFieldsByFieldset($name)
  {
    // Make sure there is a valid Form XML document.
    if(!($this->xml instanceof \SimpleXMLElement))
    {
      throw new \UnexpectedValueException(sprintf('%s::%s `xml` is not an instance of SimpleXMLElement', \get_class($this), __METHOD__));
    }

    /*
    * Get an array of <field /> elements that are underneath a <fieldset /> element
    * with the appropriate name attribute, and also any <field /> elements with
    * the appropriate fieldset attribute. To allow repeatable elements only fields
    * which are not descendants of other fields are selected.
    */
    $fields = $this->xml->xpath('(//fieldset[@name="' . $name . '"]/field | //field[@fieldset="' . $name . '"])[not(ancestor::field)]');

    return $fields;
  }

  /**
   * Method to get an array of `<field>` elements from the form XML document which have a specific attribute.
   *
   * @param   string  $attribute  The name of the attribute.
   * @param   string  $value      The value of the attribute.
   *
   * @return  \SimpleXMLElement[]|boolean  Boolean false on error or array of SimpleXMLElement objects.
   *
   * @since   4.0.0
   */
  protected function &findFieldsByAttribute($attribute, $value)
  {
    // Make sure there is a valid Form XML document.
    if(!($this->xml instanceof \SimpleXMLElement))
    {
      throw new \UnexpectedValueException(sprintf('%s::%s `xml` is not an instance of SimpleXMLElement', \get_class($this), __METHOD__));
    }

    /*
    * Get an array of <field /> elements that have a specified attribute
    * and a specified value set for this attribute.
    */
    $fields = $this->xml->xpath('//field[@' . $attribute . '="' . $value . '"]');

    return $fields;
  }

  /**
   * Method to get an instance of a form.
   *
   * @param   string          $name     The name of the form.
   * @param   string          $data     The name of an XML file or string to load as the form definition.
   * @param   array           $options  An array of form options.
   * @param   boolean         $replace  Flag to toggle whether form fields should be replaced if a field
   *                                    already exists with the same group/name.
   * @param   string|boolean  $xpath    An optional xpath to search for the fields.
   *
   * @return  ConfigForm  Form instance.
   *
   * @since   4.0.0
   * @throws  \InvalidArgumentException if no data provided.
   * @throws  \RuntimeException if the form could not be loaded.
   */
  public static function getInstance($name, $data = null, $options = [], $replace = true, $xpath = false)
  {
      // Reference to array with form instances
      $forms = &self::$forms;

      // Only instantiate the form if it does not already exist.
      if(!isset($forms[$name]))
      {
        $data = trim($data);

        if(empty($data))
        {
          throw new \InvalidArgumentException(sprintf('%1$s(%2$s, *%3$s*)', __METHOD__, $name, \gettype($data)));
        }

        // Instantiate the form.
        $forms[$name] = new ConfigForm($name, $options);
        $forms[$name]->setDatabase(Factory::getContainer()->get(DatabaseInterface::class));

        // Load the data.
        if(substr($data, 0, 1) === '<')
        {
          if($forms[$name]->load($data, $replace, $xpath) == false)
          {
            throw new \RuntimeException(sprintf('%s() could not load form', __METHOD__));
          }
        }
        else
        {
          if($forms[$name]->loadFile($data, $replace, $xpath) == false)
          {
            throw new \RuntimeException(sprintf('%s() could not load file', __METHOD__));
          }
        }
      }

      return $forms[$name];
  }
}
