<?php
/**
 * @version    CVS: 4.0.0
 * @package    Com_Joomgallery
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright  2008 - 2022  JoomGallery::ProjectTeam
 * @license    GNU General Public License version 2 or later
 */

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
	method="post" enctype="multipart/form-data" name="adminForm" id="config-form" class="form-validate form-horizontal">

	
	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'General')); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'General', Text::_('COM_JOOMGALLERY_TAB_GENERAL', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_GENERAL'); ?></legend>
				<?php echo $this->form->renderField('jg_pathftpupload'); ?>
				<?php echo $this->form->renderField('jg_pathtemp'); ?>
				<?php echo $this->form->renderField('jg_wmfile'); ?>
				<?php echo $this->form->renderField('jg_use_real_paths'); ?>
				<?php echo $this->form->renderField('jg_checkupdate'); ?>
				<?php echo $this->form->renderField('jg_listbox_max_items'); ?>
				<?php echo $this->form->renderField('title'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_UPLOAD'); ?></legend>
				<?php echo $this->form->renderField('jg_filenamewithjs'); ?>
				<?php echo $this->form->renderField('jg_filenamereplace'); ?>
				<?php echo $this->form->renderField('jg_replaceinfo'); ?>
				<?php echo $this->form->renderField('jg_replaceshowwarning'); ?>
				<?php echo $this->form->renderField('jg_useorigfilename'); ?>
				<?php echo $this->form->renderField('jg_uploadorder'); ?>
				<?php echo $this->form->renderField('jg_filenamenumber'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_IMAGEPROCESSING'); ?></legend>
				<?php echo $this->form->renderField('jg_delete_original'); ?>
				<?php echo $this->form->renderField('jg_imgprocessor'); ?>
				<?php echo $this->form->renderField('jg_fastgd2creation'); ?>
				<?php echo $this->form->renderField('jg_impath'); ?>
				<?php echo $this->form->renderField('jg_staticprocessing'); ?>
				<?php echo $this->form->renderField('jg_dynamicprocessing'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_MESSAGES'); ?></legend>
				<?php echo $this->form->renderField('jg_msg_upload_type'); ?>
				<?php echo $this->form->renderField('jg_msg_upload_recipients'); ?>
				<?php echo $this->form->renderField('jg_msg_download_type'); ?>
				<?php echo $this->form->renderField('jg_msg_download_recipients'); ?>
				<?php echo $this->form->renderField('jg_msg_zipdownload'); ?>
				<?php echo $this->form->renderField('jg_msg_comment_type'); ?>
				<?php echo $this->form->renderField('jg_msg_comment_recipients'); ?>
				<?php echo $this->form->renderField('jg_msg_comment_toowner'); ?>
				<?php echo $this->form->renderField('jg_msg_report_type'); ?>
				<?php echo $this->form->renderField('jg_msg_report_recipients'); ?>
				<?php echo $this->form->renderField('jg_msg_report_toowner'); ?>
				<?php echo $this->form->renderField('jg_msg_rejectimg_type'); ?>
				<?php echo $this->form->renderField('jg_msg_global_from'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Users', Text::_('COM_JOOMGALLERY_TAB_USERS', true)); ?>
	<div class="row-fluid">
		<div class="span10 form-horizontal">
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_GENERAL'); ?></legend>
				<?php echo $this->form->renderField('group_id'); ?>
				<?php echo $this->form->renderField('jg_userspace'); ?>
				<?php echo $this->form->renderField('jg_approve'); ?>
				<?php echo $this->form->renderField('jg_maxusercat'); ?>
				<?php echo $this->form->renderField('jg_maxuserimage'); ?>
				<?php echo $this->form->renderField('jg_maxuserimage_timespan'); ?>
				<?php echo $this->form->renderField('jg_maxfilesize'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_UPLOAD'); ?></legend>
				<?php echo $this->form->renderField('jg_newpiccopyright'); ?>
				<?php echo $this->form->renderField('jg_uploaddefaultcat'); ?>
				<?php echo $this->form->renderField('jg_useruploadsingle'); ?>
				<?php echo $this->form->renderField('jg_maxuploadfields'); ?>
				<?php echo $this->form->renderField('jg_useruploadajax'); ?>
				<?php echo $this->form->renderField('jg_useruploadbatch'); ?>
				<?php echo $this->form->renderField('jg_special_upload'); ?>
				<?php echo $this->form->renderField('jg_newpicnote'); ?>
				<?php echo $this->form->renderField('jg_redirect_after_upload'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_DOWNLOAD'); ?></legend>
				<?php echo $this->form->renderField('jg_download'); ?>
				<?php echo $this->form->renderField('jg_download_hint'); ?>
				<?php echo $this->form->renderField('jg_downloadfile'); ?>
				<?php echo $this->form->renderField('jg_downloadwithwatermark'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_RATINGS'); ?></legend>
				<?php echo $this->form->renderField('jg_showrating'); ?>
				<?php echo $this->form->renderField('jg_maxvoting'); ?>
				<?php echo $this->form->renderField('jg_ratingcalctype'); ?>
				<?php echo $this->form->renderField('jg_votingonlyonce'); ?>
			</fieldset>
			<fieldset class="adminform">
				<legend><?php echo Text::_('COM_JOOMGALLERY_FIELDSET_REPORTS'); ?></legend>
				<?php echo $this->form->renderField('jg_report_images'); ?>
				<?php echo $this->form->renderField('jg_report_hint'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
	<input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
	<input type="hidden" name="jform[published]" value="<?php echo $this->item->published; ?>" />
	<input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
	<input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
	<?php echo $this->form->renderField('created_by'); ?>
	<?php echo $this->form->renderField('modified_by'); ?>

	<?php if (Factory::getUser()->authorise('core.admin','joomgallery')) : ?>
	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
		<?php echo $this->form->getInput('rules'); ?>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>
<?php endif; ?>
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>
