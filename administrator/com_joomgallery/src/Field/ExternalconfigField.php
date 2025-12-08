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

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Path;

/**
 * Supports a config field whose content is defined in com_config
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ExternalconfigField extends FormField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'externalconfig';

  /**
   * Storage for the external field object.
   *
   * @var    FormField
   * @since  4.0.0
   */
  protected $external = null;

  /**
   * Method to attach a Form object to the field.
   *
   * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
   * @param   mixed              $value    The form field value to validate.
   * @param   string             $group    The field name group control value. This acts as as an array container for the field.
   *                                       For example if the field has name="foo" and the group value is set to "bar" then the
   *                                       full field name would end up being "bar[foo]".
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   */
  public function setup(\SimpleXMLElement $element, $value, $group = null)
  {
    $res = parent::setup($element, $value, $group);

    // Get data
    $data = $this->getLayoutData();

    // // Load external form
    $array       = explode('.', $data['label']);
    $option      = preg_replace('/[^a-z0-9_]/', '', $array[0]);
    $field       = preg_replace('/[^a-z0-9_]/', '', $array[1]);
    $config_xml  = Path::clean(JPATH_ADMINISTRATOR . '/components/' . $option . '/config.xml');
    $config_form = new Form($option . '.config');
    $config_form->loadFile($config_xml, false, '//config//fieldset');

    // Add external field values
    $this->external = $config_form->getField($field);

    // Load external language
    $lang = Factory::getApplication()->getLanguage();
    $lang->load($option, JPATH_ADMINISTRATOR);

    return $res;
  }

  /**
   * Method to get the field input markup.
   *
   * @return  string    The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    $data = $this->getLayoutData();

    // Get externalconfig
    $array  = explode('.', $data['label']);
    $option = preg_replace('/[^a-z0-9_]/', '', $array[0]);
    $field  = preg_replace('/[^a-z0-9_]/', '', $array[1]);

    $this->value       = ComponentHelper::getParams($option)->get($field);
    $this->readonly    = true;
    $this->description = Text::_(\strval($this->external->element->attributes()->description)) . ' (' . Text::_('COM_JOOMGALLERY_SOURCE') . ': ' . $option . ')';

    $html  = '<a class="btn btn-secondary inline" target="_blank" href="index.php?option=com_config&view=component&component=' . $option . '">' . Text::_('JACTION_EDIT') . '</a>';
    $html .= '<input id="' . $this->id . '" disabled class="form-control sensitive-input" type="text" name="' . $this->name . '" value="' . $this->value . '" aria-describedby="' . $this->id . '-desc">';

    return $html;
  }

  /**
   * Method to get the field label markup.
   *
   * @return  string  The field label markup.
   *
   * @since   4.0.0
   */
  protected function getLabel()
  {
    $data = $this->getLayoutData();

    $label = \strval($this->external->element->attributes()->label);

    $extraData = [
      'text'    => Text::_($label),
      'for'     => $this->id,
      'classes' => explode(' ', $data['labelclass']),
    ];

    return $this->getRenderer($this->renderLabelLayout)->render(array_merge($data, $extraData));
  }
}
