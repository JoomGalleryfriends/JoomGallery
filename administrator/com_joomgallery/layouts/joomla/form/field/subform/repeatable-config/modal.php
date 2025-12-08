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

use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string  $label              The field label
 * @var   Form    $form               The form instance for render the section
 * @var   string  $basegroup          The base group name
 * @var   string  $group              Current group name
 * @var   array   $buttons            Array of the buttons that will be rendered
 * @var   boolean $is_global_config   True if the global configuration is loaded in config.edit view
 */
?>

<div class="p-3">
  <?php
    $i = 0;

    foreach($form->getGroup('') as $k => $field) : ?>
      <?php if($i > 1): ?>
        <div class="row">
          <?php
            if(!$is_global_config && !empty($field->getAttribute('global_only')) && $field->getAttribute('global_only') == true)
            {
              $field_data = [
                'name'        => $field->name,
                'label'       => LayoutHelper::render('joomla.form.renderlabel', ['text' => Text::_($field->getAttribute('label')), 'for' => $field->id, 'required' => false, 'classes' => []]),
                'input'       => LayoutHelper::render('joomla.form.field.value', ['id' => $field->id, 'value' => $field->value, 'class' => '']),
                'description' => Text::_('COM_JOOMGALLERY_CONFIG_EDIT_ONLY_IN_GLOBAL'),
              ];
              echo LayoutHelper::render('joomla.form.renderfield', $field_data);
            }
            else
            {
              echo $field->renderField();
            }
          ?>
        </div>
      <?php endif; ?>
    <?php
    $i++;
    endforeach;
  ?>
</div>
