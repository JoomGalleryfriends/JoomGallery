<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Utilities\ArrayHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;

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
 * @var   string   $imageName       The image name
 * @var   mixed    $categories      The filtering for categories (null means no filtering)
 * @var   mixed    $excluded        The images to exclude from the list of images
 * @var   string   $dataAttribute   Miscellaneous data attributes preprocessed for HTML output
 * @var   array    $dataAttributes  Miscellaneous data attribute for eg, data-*.
 */
$modalHTML = '';
$uri = new Uri('index.php?option=com_joomgallery&view=images&layout=modal&tmpl=component&required=0');
$uri->setVar('field', $this->escape($id));

if(empty($value))
{
  $value = 0;
}

if($required)
{
	$uri->setVar('required', 1);
}

// Apply filter in images list
$uri->setVar('list[fullordering]', 'a.id+DESC');
$uri->setVar('list[limit]', '20');
$uri->setVar('filter[search]', '');
$uri->setVar('filter[published]', '*');
$uri->setVar('filter[created_by]', '');

if(!empty($categories))
{
	$uri->setVar('filter[category]', '['.implode(',', $categories).']');
}

if(!empty($excluded))
{
	$uri->setVar('exclude', '['.implode(',', $excluded).']');
}

// Invalidate the input value if no image selected
if($this->escape($imageName) === Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE'))
{
	$imageName = '';
}

$inputAttributes = array(
	'type' => 'text', 'id' => $id, 'class' => 'form-control field-image-input-name', 'value' => $this->escape($imageName)
);
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
	$inputAttributes['placeholder'] = Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE');
}

if(!$readonly)
{
	$modalHTML = HTMLHelper::_(
		'bootstrap.renderModal',
		'imageModal_' . $id,
		array(
			'url'         => $uri,
			'title'       => Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE'),
			'closeButton' => true,
			'height'      => '100%',
			'width'       => '100%',
			'modalWidth'  => 80,
			'bodyHeight'  => 60,
			'footer'      => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">' . Text::_('JCANCEL') . '</button>',
		)
	);

	Factory::getApplication()->getDocument()->getWebAssetManager()
		->useScript('com_joomgallery.field-image');
}
?>
<?php // Create a dummy text field with the image name. ?>
<joomla-field-image class="field-image-wrapper"
		url="<?php echo (string) $uri; ?>"
		modal=".modal"
		modal-width="100%"
		modal-height="400px"
		input=".field-image-input"
		input-name=".field-image-input-name"
    	input-img="#jform_thumbnail_img"
		button-select=".button-select">
	<div class="input-group">
    <img id="jform_thumbnail_img" class="jg_minithumb" src="<?php echo JoomHelper::getImg($value, 'thumbnail'); ?>" alt="<?php echo Text::_('COM_JOOMGALLERY_THUMBNAIL'); ?>">
		<input <?php echo ArrayHelper::toString($inputAttributes), $dataAttribute; ?> readonly>
		<?php if (!$readonly) : ?>
			<button type="button" class="btn btn-primary button-select" title="<?php echo Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE'); ?>">
				<span class="icon-image icon-white" aria-hidden="true"></span>
				<span class="visually-hidden"><?php echo Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE'); ?></span>
			</button>
		<?php endif; ?>
	</div>
	<?php // Create the real field, hidden, that stored the image id. ?>
	<?php if (!$readonly) : ?>
		<input type="hidden" id="<?php echo $id; ?>_id" name="<?php echo $name; ?>" value="<?php echo $this->escape($value); ?>"
			class="field-image-input <?php echo $class ? (string) $class : ''?>"
			data-onchange="<?php echo $this->escape($onchange); ?>">
		<?php echo $modalHTML; ?>
	<?php endif; ?>
</joomla-field-image>
