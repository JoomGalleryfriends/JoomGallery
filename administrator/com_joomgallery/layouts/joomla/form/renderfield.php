<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;

extract($displayData);

/**
 * Layout variables
 * -----------------
 * @var   array   $options      Optional parameters
 * @var   string  $id           The id of the input this label is for
 * @var   string  $name         The name of the input this label is for
 * @var   string  $label        The html code for the label
 * @var   string  $input        The input field html code
 * @var   string  $description  An optional description to use as in–line help text
 * @var   string  $descClass    The class name to use for the description
 */

if (!empty($options['showonEnabled']))
{
	/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
	$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
	$wa->useScript('showon');
}

if(isset($id) && ($id == 'jform_jg_staticprocessing' || $id == 'jform_jg_dynamicprocessing'))
{
  $addClass = 'mb-5';
}

$unused          = (isset($class) && strpos($class, 'unused') !== false) ? 'unused' : '';
$hidden          = (isset($class) && strpos($class, 'hidden') !== false) ? 'hidden' : '';
$sensitive       = (!empty($options['sensitive']) && $options['sensitive'] == true) ? true : false;
$class           = empty($options['class']) ? '' : ' ' . $options['class'];
$class           = empty($hidden) ? $class : $class . ' ' . $hidden;
$rel             = empty($options['rel']) ? '' : ' ' . $options['rel'];
$id              = ($id ?? $name) . '-desc';
$hideLabel       = !empty($options['hiddenLabel']);
$hideDescription = empty($options['hiddenDescription']) ? false : $options['hiddenDescription'];
$descClass       = ($options['descClass'] ?? '') ?: (!empty($options['inlineHelp']) ? 'hide-aware-inline-help d-none' : '');
$externalConfig  = (isset($field) && $field->type == 'externalconfig') ? true : false;

if((isset($sensitive) && $sensitive == true) || (!empty($options['sensitive']) && $options['sensitive'] == true))
{
  $sensitive = true;
}
else
{
  $sensitive = false;
}

if(!empty($parentclass))
{
	$class .= ' ' . $parentclass;
}

if(!empty($addClass))
{
	$class .= ' ' . $addClass;
}

$tip = null;
if(!empty($description) && strpos($description, '{tip}') !== false)
{
  $desc_arr    = explode('{tip}',$description);
	$description = $desc_arr[0];
	$tip         = $desc_arr[1];
}

$sensitiveBtn = '';
$sensitiveLbl = '';
if($sensitive)
{
  // disabled input field
  $tmp_input = explode(' ', trim($input));
  $tmp_input = array_merge(array($tmp_input[0]), array('disabled'), array_slice($tmp_input, 1));
  $input     = implode(' ', $tmp_input);

  // add sensitive class
  $input = str_replace('class="', 'class="sensitive-input ', $input);

  $sensitiveLbl = ' <span class="icon-notification-circle" aria-hidden="true"></span>';
  $sensitiveBtn = '<a href="#" class="btn btn-secondary inline" onclick="enableEditing(event,this)">'.Text::_('JTOOLBAR_ENABLE').'</a>';

  Text::script('COM_JOOMGALLERY_CONFIG_ALERT_ENABLE_SENSITIVE_FIELD');

  /** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
	$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
	$wa->useScript('com_joomgallery.sensitiveField');
}

?>
<div class="control-group<?php echo $class; ?>"<?php echo $rel; ?>>
	<?php if ($hideLabel) : ?>
		<div class="visually-hidden"><?php echo $label; ?></div>
	<?php else : ?>
		<div class="control-label <?php echo $unused; ?>">
      <?php echo $label.$sensitiveLbl; ?>
    </div>
	<?php endif; ?>
	<div class="controls <?php echo ($sensitive || $externalConfig) ? 'sensitive': ''; ?>">
    <?php echo $sensitiveBtn; ?><?php echo $input; ?>
		<?php if (!$hideDescription && !empty($description)) : ?>
			<div id="<?php echo $id; ?>" class="description <?php echo $descClass ?>">
				<small class="form-text">
					<?php echo $description; ?>
					<?php if(!empty($tip)) : ?>
						<a data-bs-toggle="collapse" href="#collapseTip_<?php echo $id; ?>" role="button" aria-expanded="false" aria-controls="collapseTip_<?php echo $id; ?>">
							<?php echo Text::_('COM_JOOMGALLERY_FIELDS_TIP_MORE'); ?>
						</a>
					<?php endif; ?>
				</small>
				<?php if(!empty($tip)) : ?>
					<br />
					<small id="collapseTip_<?php echo $id; ?>" class="form-text collapse">
						<?php echo $tip; ?>
					</small>
				<?php endif; ?>
        <?php if($name == 'jform[jg_imgprocessor]') : ?>
          <div class="mt">
            <small id="jg_imgprocessor_supplement" class="form-text"></small>
          </div>          
        <?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
