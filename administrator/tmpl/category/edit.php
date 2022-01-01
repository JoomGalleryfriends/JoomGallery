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


HTMLHelper::addIncludePath(JPATH_COMPONENT . '/helpers/html');
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	->useScript('form.validate');
HTMLHelper::_('bootstrap.tooltip');
?>

<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout=edit&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="category-form" class="form-validate form-horizontal">

	
	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'category')); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'category', Text::_('COM_JOOMGALLERY_FIELDSET_CATEGORY', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_CATEGORY'); ?></legend>
				<?php echo $this->form->renderField('title'); ?>
				<?php echo $this->form->renderField('alias'); ?>
				<?php echo $this->form->renderField('parent_id'); ?>
				<?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('access'); ?>
				<?php echo $this->form->renderField('password'); ?>
				<?php echo $this->form->renderField('language'); ?>
				<?php echo $this->form->renderField('description'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Options', Text::_('JGLOBAL_FIELDSET_BASIC', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('JDETAILS'); ?></legend>
				<?php echo $this->form->renderField('hidden'); ?>
				<?php echo $this->form->renderField('exclude_toplist'); ?>
				<?php echo $this->form->renderField('exclude_search'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_IMAGES'); ?></legend>
				<?php echo $this->form->renderField('thumbnail'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
				<?php echo $this->form->renderField('created_time'); ?>
				<?php echo $this->form->renderField('created_by'); ?>
				<?php echo $this->form->renderField('modified_by'); ?>
				<?php echo $this->form->renderField('modified_time'); ?>
				<?php echo $this->form->renderField('id'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA'); ?></legend>
				<?php echo $this->form->renderField('metadesc'); ?>
				<?php echo $this->form->renderField('metakey'); ?>
				<?php echo $this->form->renderField('robots'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Displayparams', Text::_('COM_JOOMGALLERY_COMMON_PARAMETERS', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_COMMON_PARAMETERS'); ?></legend>
				<?php echo $this->form->renderField('params'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
	<input type="hidden" name="jform[lft]" value="<?php echo $this->item->lft; ?>" />
	<input type="hidden" name="jform[rgt]" value="<?php echo $this->item->rgt; ?>" />
	<input type="hidden" name="jform[level]" value="<?php echo $this->item->level; ?>" />
	<input type="hidden" name="jform[path]" value="<?php echo $this->item->path; ?>" />
	<input type="hidden" name="jform[in_hidden]" value="<?php echo $this->item->in_hidden; ?>" />

	<?php if (Factory::getUser()->authorise('core.admin','joomgallery')) : ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
		<?php echo $this->form->getInput('rules'); ?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>
