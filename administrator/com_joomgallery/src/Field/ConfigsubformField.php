<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Form\ConfigForm;
use Joomla\CMS\Form\Field\SubformField;

/**
 * The Field to load the form inside current form
 *
 * @Example with all attributes:
 *  <field name="field-name" type="subform"
 *      formsource="path/to/form.xml" min="1" max="3" multiple="true" buttons="add,remove,move"
 *      layout="joomla.form.field.subform.repeatable-table" groupByFieldset="false" component="com_example" client="site"
 *      label="Field Label" description="Field Description" />
 *
 * @since  4.0.0
 */
class ConfigsubformField extends SubformField
{
  /**
   * The form field type.
   * @var    string
   */
  protected $type = 'configsubform';

  /**
   * Loads the form instance for the subform.
   *
   * @return  ConfigForm  The form instance.
   *
   * @throws  \InvalidArgumentException if no form provided.
   * @throws  \RuntimeException if the form could not be loaded.
   *
   * @since   4.0.0
   */
  public function loadSubForm()
  {
    $control = $this->name;

    if($this->multiple)
    {
      $control .= '[' . $this->fieldname . 'X]';
    }

    // Prepare the form template
    $formname = 'subform.' . str_replace(['jform[', '[', ']'], ['', '.', ''], $this->name);
    $tmpl     = ConfigForm::getInstance($formname, $this->formsource, ['control' => $control]);

    // Get fields with dynamic options
    $dyn_fields = $tmpl->getDynamicFields();

    // Add options to dynamic fields
    foreach($dyn_fields as $key => $field)
    {
      $tmpl->setDynamicOptions($field);
    }

    return $tmpl;
  }

  /**
   * Binds given data to the subform and its elements.
   *
   * @param   ConfigForm   $subForm  Form instance of the subform.
   *
   * @return  ConfigForm[]  Array of Form instances for the rows.
   *
   * @since   4.0.0
   */
  protected function loadSubFormData($subForm)
  {
      $value = $this->value ? (array) $this->value : [];

      // Simple form, just bind the data and return one row.
      if(!$this->multiple)
      {
        // Preprocess form
          // Get fields with dynamic options
          $dyn_fields = $subForm->getDynamicFields();

          // Add options to dynamic fields
          foreach($dyn_fields as $key => $field)
          {
            $subForm->setDynamicOptions($field);
          }

        // Bind form data
        $subForm->bind($value);

        return [$subForm];
      }

      // Multiple rows possible: Construct array and bind values to their respective forms.
      $forms = [];
      $value = array_values($value);

      // Show as many rows as we have values, but at least min and at most max.
      $c = max($this->min, min(\count($value), $this->max));

      for($i = 0; $i < $c; $i++)
      {
        $control  = $this->name . '[' . $this->fieldname . $i . ']';
        $itemForm = ConfigForm::getInstance($subForm->getName() . $i, $this->formsource, ['control' => $control]);

        if(!empty($value[$i]))
        {
          // Preprocess form
            // Get fields with dynamic options
            $dyn_fields = $itemForm->getDynamicFields();

            // Add options to dynamic fields
            foreach($dyn_fields as $key => $field)
            {
              $itemForm->setDynamicOptions($field);
            }

          // Bind form data
          $itemForm->bind($value[$i]);
        }

        $forms[] = $itemForm;
      }

      return $forms;
  }
}
