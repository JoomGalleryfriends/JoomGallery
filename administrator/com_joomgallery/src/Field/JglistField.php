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

use Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Helper\ModuleHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;

/**
 * List field with useglobal option based on config service
 *
 * @since  4.0.0
 */
class JglistField extends ListField
{
  use JgMenuitemTrait;

  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'jglist';

  /**
   * Method to get the field input markup for a generic list.
   * Use the multiple attribute to enable multiselect.
   *
   * @return  string  The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    if($this->element['useglobal'])
    {
      $this->layout = 'joomla.form.field.configlist';
    }

    $data = $this->getLayoutData();

    $data['options'] = (array) $this->getOptions();

    if($this->element['useglobal'])
    {
      $data['globvalue'] = $this->getGlobalValue('...');
    }

    // Make sure the component is correctly set
    $renderer = $this->getRenderer($this->layout);
    $renderer->setComponent('com_joomgallery');

    return $renderer->render($data);
  }

  /**
   * Method to get the field options.
   *
   * @return  object[]  The field option objects.
   *
   * @since   4.0.0
   */
  protected function getOptions()
  {
    $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);
    $options   = [];

    foreach($this->element->xpath('option') as $option)
    {
      // Filter requirements
      $requires = explode(',', (string) $option['requires']);

      // Requires multilanguage
      if(\in_array('multilanguage', $requires) && !Multilanguage::isEnabled())
      {
        continue;
      }

      // Requires associations
      if(\in_array('associations', $requires) && !Associations::isEnabled())
      {
        continue;
      }

      // Requires adminlanguage
      if(\in_array('adminlanguage', $requires) && !ModuleHelper::isAdminMultilang())
      {
        continue;
      }

      // Requires vote plugin
      if(\in_array('vote', $requires) && !PluginHelper::isEnabled('content', 'vote'))
      {
        continue;
      }

      // Requires record hits
      if(\in_array('hits', $requires) && !ComponentHelper::getParams('com_content')->get('record_hits', 1))
      {
        continue;
      }

      $value = (string) $option['value'];
      $text  = trim((string) $option) != '' ? trim((string) $option) : $value;

      $disabled = (string) $option['disabled'];
      $disabled = ($disabled === 'true' || $disabled === 'disabled' || $disabled === '1');
      $disabled = $disabled || ($this->readonly && $value != $this->value);

      $checked = (string) $option['checked'];
      $checked = ($checked === 'true' || $checked === 'checked' || $checked === '1');

      $selected = (string) $option['selected'];
      $selected = ($selected === 'true' || $selected === 'selected' || $selected === '1');

      $tmp = [
        'value'    => $value,
        'text'     => Text::alt($text, $fieldname),
        'disable'  => $disabled,
        'class'    => (string) $option['class'],
        'selected' => ($checked || $selected),
        'checked'  => ($checked || $selected),
      ];

      // Set some event handler attributes. But really, should be using unobtrusive js.
      $tmp['onclick']  = (string) $option['onclick'];
      $tmp['onchange'] = (string) $option['onchange'];

      if((string) $option['showon'])
      {
        $encodedConditions = json_encode(FormHelper::parseShowOnConditions((string) $option['showon'], $this->formControl, $this->group));
        $tmp['optionattr'] = " data-showon='" . $encodedConditions . "'";
      }

      // Add the option object to the result set.
      $options[] = (object) $tmp;
    }

    if($this->element['useglobal'])
    {
      // Add global option if not already available
      if(empty($options) || strpos($options[0]->text, '%s') === false)
      {
        $tmp        = new \stdClass();
        $tmp_def    = (string) $this->element['default'];
        $tmp->value = $tmp_def ? $tmp_def : '';
        $tmp->text  = Text::_('JGLOBAL_USE_GLOBAL_VALUE');

        array_unshift($options, $tmp);
      }
    }

    reset($options);

    return $options;
  }

  protected function getGlobalValue($default = '')
  {
    $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

    // Guess form context
    $context = ConfigHelper::getFormContext($this->form->getData());

    if($context !== false)
    {
      // Load JG config service
      $jg = Factory::getApplication()->bootComponent('com_joomgallery');
      $jg->createConfig($context[0], $context[1], false);

      // Get inherited global config value
      return $jg->getConfig()->get($fieldname, $default);
    }
  }
}
