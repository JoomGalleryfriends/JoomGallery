<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\Utilities\ArrayHelper;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   string   $autocomplete    Autocomplete attribute for the field.
 * @var   boolean  $autofocus       Is autofocus enabled?
 * @var   string   $class           Classes for the input.
 * @var   string   $description     Description of the field.
 * @var   boolean  $disabled        Is this field disabled?
 * @var   string   $group           Group the field belongs to. <fields> section in form XML.
 * @var   boolean  $hidden          Is this field hidden in the form?
 * @var   string   $hint            Placeholder for the field.
 * @var   string   $id              DOM id of the field.
 * @var   string   $label           Label of the field.
 * @var   string   $labelclass      Classes to apply to the label.
 * @var   boolean  $multiple        Does this field support multiple values?
 * @var   string   $name            Name of the input field.
 * @var   string   $onchange        Onchange attribute for the field.
 * @var   string   $onclick         Onclick attribute for the field.
 * @var   string   $pattern         Pattern (Reg Ex) of value of the form field.
 * @var   boolean  $readonly        Is this field read only?
 * @var   boolean  $repeat          Allows extensions to duplicate elements.
 * @var   boolean  $required        Is this field required?
 * @var   integer  $size            Size attribute of the input.
 * @var   boolean  $spellcheck      Spellcheck state for the form field.
 * @var   string   $validate        Validation rules to apply.
 * @var   string   $value           Value attribute of the field.
 * @var   string   $categoryName    The category name
 * @var   mixed    $category        The filtering category (null means no filtering)
 * @var   mixed    $excluded        The categories to exclude from the list of categories
 * @var   string   $dataAttribute   Miscellaneous data attributes preprocessed for HTML output
 * @var   array    $dataAttributes  Miscellaneous data attribute for eg, data-*.
 */
$modalHTML = '';
$uri       = new Uri('index.php?option=com_joomgallery&view=categories&layout=modal&tmpl=component&required=0');
$uri->setVar('field', $this->escape($id));

$forcedLanguage = $forcedLanguage ?? '';
$originId = Factory::getApplication()->getInput()->getInt('id', 0);
$parentTranslationMissing = false;

if(!empty($forcedLanguage))
{
  $uri->setVar('forcedLanguage', $forcedLanguage);

  if($originId > 0)
  {
    $originCategory = JoomHelper::getRecord('category', $originId);

    if($originCategory && (int) $originCategory->parent_id > 1)
    {
    $parentAssociations = Associations::getAssociations(
        'com_joomgallery',
        '#__joomgallery_categories',
        'com_joomgallery.category',
        (int) $originCategory->parent_id,
        'id',
        '',
        ''
    );
    $parentTranslationMissing = !isset($parentAssociations[$forcedLanguage]);
    }
  }
}

if(empty($value))
{
  $value = 0;
}

if($required)
{
  $uri->setVar('required', 1);
}

// Apply filter in categories list
$uri->setVar('list[fullordering]', 'a.lft+ASC');
$uri->setVar('list[limit]', '20');
$uri->setVar('filter[search]', '');
$uri->setVar('filter[published]', '*');
$uri->setVar('filter[level]', '*');
$uri->setVar('filter[created_by]', '');
$uri->setVar('filter[category]', (isset($category)) ? (string) $category : '');
$uri->setVar('filter[exclude]', (isset($excluded)) ? (string) $excluded : '');

// Invalidate the input value if no image selected
if($this->escape($categoryName) === Text::_('COM_JOOMGALLERY_FIELDS_SELECT_CATEGORY'))
{
  $categoryName = '';
}

$inputAttributes = [
  'type' => 'text', 'id' => $id, 'class' => 'form-control field-category-input-name', 'value' => $this->escape($categoryName),
];

if($class)
{
  $inputAttributes['class'] .= ' ' . $class;
}

if($size)
{
  $inputAttributes['size'] = (int) $size;
}

if($required)
{
  $inputAttributes['required'] = 'required';
}

if(!$readonly)
{
  $inputAttributes['placeholder'] = Text::_('COM_JOOMGALLERY_FIELDS_SELECT_CATEGORY');
}

if(!$readonly)
{
$modalHTML = HTMLHelper::_(
    'bootstrap.renderModal',
    'categoryModal_' . $id,
    [
      'url'         => $uri,
      'title'       => Text::_('COM_JOOMGALLERY_FIELDS_SELECT_CATEGORY'),
      'closeButton' => true,
      'height'      => '100%',
      'width'       => '100%',
      'modalWidth'  => 80,
      'bodyHeight'  => 60,
      'footer'      => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>',
    ]
);

  try
  {
    Factory::getApplication()->getDocument()->getWebAssetManager()
      ->useScript('com_joomgallery.field-category');
  }
  catch(\Exception)
  {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->getRegistry()->addRegistryFile('media/com_joomgallery/joomla.asset.json');
    $wa->useScript('com_joomgallery.field-category');
    $wa->useStyle('com_joomgallery.admin');
  }
}
?>
<?php // Create a dummy text field with the category name. ?>
<joomla-field-category class="field-category-wrapper"
    url="<?php echo (string) $uri; ?>"
    modal=".modal"
    modal-width="100%"
    modal-height="400px"
    input=".field-category-input"
    input-name=".field-category-input-name"
    button-select=".button-select">
  <div class="input-group">
    <input <?php echo ArrayHelper::toString($inputAttributes), $dataAttribute; ?> readonly>
    <?php if(!$readonly) : ?>
      <button type="button" class="btn btn-primary button-select" title="<?php echo Text::_('COM_JOOMGALLERY_FIELDS_SELECT_CATEGORY'); ?>">
        <span class="icon-folder icon-white" aria-hidden="true"></span>
        <span class="visually-hidden"><?php echo Text::_('COM_JOOMGALLERY_FIELDS_SELECT_CATEGORY'); ?></span>
      </button>
    <?php endif; ?>
    <?php if(!$readonly && !empty($forcedLanguage)) : ?>
      <button type="button" class="btn btn-success button-create"
        data-url="index.php?option=com_joomgallery&amp;view=category&amp;layout=edit&amp;origin_id=<?php echo (int) $originId; ?>&amp;forcedLanguage=<?php echo rawurlencode($forcedLanguage); ?>"
        data-parent-missing="<?php echo $parentTranslationMissing ? '1' : '0'; ?>"
        data-parent-error="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_ERROR_CATEGORY_VARIANT_PARENT')); ?>">
        <span class="icon-plus" aria-hidden="true"></span>
        <span class="visually-hidden"><?php echo Text::_('JACTION_CREATE'); ?></span>
      </button>
      <button type="button" class="btn btn-danger button-delete-variant"
        data-url="index.php?option=com_joomgallery&amp;task=category.deleteVariant"
        data-language="<?php echo $this->escape($forcedLanguage); ?>"
        data-confirm="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_CONFIRM_DELETE_CATEGORY_VARIANT')); ?>"
        data-success="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_SUCCESS_DELETE_CATEGORY_VARIANT')); ?>"
        data-error="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_ERROR_DELETE_CATEGORY_VARIANT')); ?>"
        title="<?php echo $this->escape(Text::_('COM_JOOMGALLERY_DELETE_CATEGORY_VARIANT')); ?>"
        <?php echo empty($value) ? 'disabled' : ''; ?>>
        <span class="icon-minus" aria-hidden="true"></span>
        <span class="visually-hidden"><?php echo Text::_('JACTION_DELETE'); ?></span>
      </button>
    <?php endif; ?>
  </div>
  <?php // Create the real field, hidden, that stored the category id. ?>
  <?php if(!$readonly) : ?>
    <input type="hidden" id="<?php echo $id; ?>_id" name="<?php echo $name; ?>" value="<?php echo $this->escape($value); ?>"
      class="field-category-input <?php echo $class ? (string) $class : ''?>"
      data-onchange="<?php echo $this->escape($onchange); ?>">
    <?php echo $modalHTML; ?>
  <?php endif; ?>
</joomla-field-category>
