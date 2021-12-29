<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomgallery\Component\Joomgallery\Site\Helper\JoomgalleryHelper;

$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');

// Load admin language file
$lang = Factory::getLanguage();
$lang->load('com_joomgallery', JPATH_SITE);

$user    = Factory::getUser();
$canEdit = JoomgalleryHelper::canUserEdit($this->item, $user);


?>

<div class="image-edit front-end-edit">
	<?php if (!$canEdit) : ?>
		<h3>
			<?php throw new \Exception(Text::_('COM_JOOMGALLERY_ERROR_MESSAGE_NOT_AUTHORISED'), 403); ?>
		</h3>
	<?php else : ?>
		<?php if (!empty($this->item->id)): ?>
			<h1><?php echo Text::sprintf('COM_JOOMGALLERY_EDIT_ITEM_TITLE', $this->item->id); ?></h1>
		<?php else: ?>
			<h1><?php echo Text::_('COM_JOOMGALLERY_ADD_ITEM_TITLE'); ?></h1>
		<?php endif; ?>

		<form id="form-image"
			  action="<?php echo Route::_('index.php?option=com_joomgallery&task=imageform.save'); ?>"
			  method="post" class="form-validate form-horizontal" enctype="multipart/form-data">
			
	<input type="hidden" name="jform[ordering]" value="<?php echo isset($this->item->ordering) ? $this->item->ordering : ''; ?>" />

	<input type="hidden" name="jform[checked_out]" value="<?php echo isset($this->item->checked_out) ? $this->item->checked_out : ''; ?>" />

	<input type="hidden" name="jform[hits]" value="<?php echo isset($this->item->hits) ? $this->item->hits : ''; ?>" />

	<input type="hidden" name="jform[downloads]" value="<?php echo isset($this->item->downloads) ? $this->item->downloads : ''; ?>" />

	<input type="hidden" name="jform[imgvotes]" value="<?php echo isset($this->item->imgvotes) ? $this->item->imgvotes : ''; ?>" />

	<input type="hidden" name="jform[imgvotesum]" value="<?php echo isset($this->item->imgvotesum) ? $this->item->imgvotesum : ''; ?>" />

	<input type="hidden" name="jform[approved]" value="<?php echo isset($this->item->approved) ? $this->item->approved : ''; ?>" />

	<input type="hidden" name="jform[useruploaded]" value="<?php echo isset($this->item->useruploaded) ? $this->item->useruploaded : ''; ?>" />

	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'Details')); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('COM_JOOMGALLERY_TAB_DETAILS', true)); ?>
	<?php echo $this->form->renderField('imgtitle'); ?>

	<?php echo $this->form->renderField('alias'); ?>

	<?php echo $this->form->renderField('catid'); ?>

	<?php echo $this->form->renderField('published'); ?>

	<?php echo $this->form->renderField('imgauthor'); ?>

	<?php echo $this->form->renderField('language'); ?>

	<?php echo $this->form->renderField('imgtext'); ?>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('COM_JOOMGALLERY_TAB_PUBLISHING', true)); ?>
	<?php echo $this->form->renderField('access'); ?>

	<?php echo $this->form->renderField('hidden'); ?>

	<?php echo $this->form->renderField('featured'); ?>

	<?php echo $this->form->renderField('created_time'); ?>

	<?php echo $this->form->renderField('created_by'); ?>

	<?php echo $this->form->renderField('modified_time'); ?>

	<?php echo $this->form->renderField('modified_by'); ?>

	<?php echo $this->form->renderField('id'); ?>

	<?php echo $this->form->renderField('metadesc'); ?>

	<?php echo $this->form->renderField('metakey'); ?>

	<?php echo $this->form->renderField('robots'); ?>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Images', Text::_('COM_JOOMGALLERY_TAB_IMAGES', true)); ?>
	<?php echo $this->form->renderField('filename'); ?>

	<?php echo $this->form->renderField('imgdate'); ?>

	<?php echo $this->form->renderField('imgmetadata'); ?>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'DisplayParams', Text::_('COM_JOOMGALLERY_TAB_DISPLAYPARAMS', true)); ?>
	<?php echo $this->form->renderField('params'); ?>

	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('COM_JOOMGALLERY_ACTION_PERMISSIONS_LABEL', true)); ?>				<div class="fltlft" <?php if (!Factory::getUser()->authorise('core.admin','joomgallery')): ?> style="display:none;" <?php endif; ?> >
                
                <fieldset class="panelform">
                    <?php echo $this->form->getLabel('rules'); ?>
                    <?php echo $this->form->getInput('rules'); ?>
                </fieldset>
            </div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
				<?php if (!Factory::getUser()->authorise('core.admin','joomgallery')) {
                    $wa->addInlineScript("
                        jQuery.noConflict();
                        jQuery('.tab-pane select').each(function(){
                        var option_selected = jQuery(this).find(':selected');
                        var input = document.createElement('input');
                        input.setAttribute('type', 'hidden');
                        input.setAttribute('name', jQuery(this).attr('name'));
                        input.setAttribute('value', option_selected.val());
                        document.getElementById('form-image').appendChild(input);
                        });
                    ", [], [], ["jquery"]);
                } ?>
			<div class="control-group">
				<div class="controls">

					<?php if ($this->canSave): ?>
						<button type="submit" class="validate btn btn-primary">
							<span class="fas fa-check" aria-hidden="true"></span>
							<?php echo Text::_('JSUBMIT'); ?>
						</button>
					<?php endif; ?>
					<a class="btn btn-danger"
					   href="<?php echo Route::_('index.php?option=com_joomgallery&task=imageform.cancel'); ?>"
					   title="<?php echo Text::_('JCANCEL'); ?>">
					   <span class="fas fa-times" aria-hidden="true"></span>
						<?php echo Text::_('JCANCEL'); ?>
					</a>
				</div>
			</div>

			<input type="hidden" name="option" value="com_joomgallery"/>
			<input type="hidden" name="task"
				   value="imageform.save"/>
			<?php echo HTMLHelper::_('form.token'); ?>
		</form>
	<?php endif; ?>
</div>
