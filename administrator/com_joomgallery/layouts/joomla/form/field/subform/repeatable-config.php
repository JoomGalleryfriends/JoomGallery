<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   Form    $tmpl             The Empty form for template
 * @var   array   $forms            Array of JForm instances for render the rows
 * @var   bool    $multiple         The multiple state for the form field
 * @var   int     $min              Count of minimum repeating in multiple mode
 * @var   int     $max              Count of maximum repeating in multiple mode
 * @var   string  $name             Name of the input field.
 * @var   string  $fieldname        The field name
 * @var   string  $fieldId          The field ID
 * @var   string  $control          The forms control
 * @var   string  $label            The field label
 * @var   string  $description      The field description
 * @var   string  $class            Classes for the container
 * @var   array   $buttons          Array of the buttons that will be rendered
 * @var   bool    $groupByFieldset  Whether group the subform fields by it`s fieldset
 */

if($multiple)
{
  // Add script
  Factory::getApplication()
    ->getDocument()
    ->getWebAssetManager()
    ->useScript('webcomponent.field-subform');
}

// Get input variables
$input = Factory::getApplication()->input;

$option = $input->get('option');
$view   = $input->get('view');
$id     = $input->get('id', 0, 'integer');

// Guess config id
$is_global_config = false;

if($option == 'com_joomgallery' && $view == 'config' && $id === 1)
{
  $is_global_config = true;
}

$class = $class ? ' ' . $class : '';

// Build heading
$table_head          = '';
$subforms_with_popup = ['jg_staticprocessing', 'jg_dynamicprocessing'];

$i = 0;

foreach($tmpl->getGroup('') as $field)
{
  if($i < 2)
  {
    $table_head .= '<th scope="col">' . strip_tags($field->label);

    if($field->description)
    {
      $table_head .= '<span class="icon-info-circle" aria-hidden="true" tabindex="0"></span><div role="tooltip" id="tip-' . $field->id . '">' . Text::_($field->description) . '</div>';
    }

    $table_head .= '</th>';
  }

  $i++;
}

$section = 'section';

if(\in_array($fieldname, $subforms_with_popup))
{
  $table_head .= '<th scope="col">' . Text::_('COM_JOOMGALLERY_SETTINGS') . '</th>';
  $section     = 'sectionWithPopup';
}

$sublayout = 'section-byfieldsets';

// Label will not be shown for sections layout, so reset the margin left
Factory::getApplication()
  ->getDocument()
  ->addStyleDeclaration('.subform-table-sublayout-section .controls { margin-left: 0px }');
?>

<div class="subform-repeatable-wrapper subform-table-layout subform-table-sublayout-<?php echo $sublayout; ?>">
  <joomla-field-subform class="subform-repeatable<?php echo $class; ?>" name="<?php echo $name; ?>"
    button-add=".group-add" button-remove=".group-remove" button-move="<?php echo empty($buttons['move']) ? '' : '.group-move' ?>"
    repeatable-element=".subform-repeatable-group"
    rows-container="tbody.subform-repeatable-container" minimum="<?php echo $min; ?>" maximum="<?php echo $max; ?>">
    <div class="table-responsive">
      <table class="table" id="subfieldList_<?php echo $fieldId; ?>">
        <caption class="visually-hidden">
          <?php echo Text::_('JGLOBAL_REPEATABLE_FIELDS_TABLE_CAPTION'); ?>
        </caption>
        <thead>
          <tr>
            <?php echo $table_head; ?>
            <?php if(!empty($buttons)) : ?>
            <td style="width:8%;">
              <?php if(!empty($buttons['add'])) : ?>
                <div class="btn-group">
                  <button type="button" class="group-add btn btn-sm btn-success" aria-label="<?php echo Text::_('JGLOBAL_FIELD_ADD'); ?>">
                    <span class="icon-plus" aria-hidden="true"></span>
                  </button>
                </div>
              <?php endif; ?>
            </td>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody class="subform-repeatable-container">
        <?php
        foreach($forms as $k => $form) :
            echo $this->sublayout($section, ['label' => $label, 'form' => $form, 'basegroup' => $fieldname, 'group' => $fieldname . $k, 'buttons' => $buttons, 'is_global_config' => $is_global_config]);
        endforeach;
        ?>
        </tbody>
      </table>
    </div>
    <?php if($multiple) : ?>
    <template class="subform-repeatable-template-section hidden">
      <?php echo trim($this->sublayout($section, ['label' => $label, 'form' => $tmpl, 'basegroup' => $fieldname, 'group' => $fieldname . 'X', 'buttons' => $buttons, 'is_global_config' => $is_global_config])); ?>
    </template>
    <?php endif; ?>
  </joomla-field-subform>
</div>
