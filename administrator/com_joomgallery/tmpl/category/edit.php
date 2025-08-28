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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Layout\LayoutHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useStyle('com_joomgallery.admin');

// Add javascript to deactivate filesystem dropdown field
if(property_exists($this, 'filesystem_js'))
{
  $wa->addInlineScript($this->filesystem_js);
}

HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();
$form      = $this->getForm();
$fieldSets = $form->getFieldsets();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<div class="jg jg-category">
<form
	action="<?php echo Route::_('index.php?option=com_joomgallery&layout='.$layout.$tmpl.'&id=' . (int) $this->item->id); ?>"
	method="post" enctype="multipart/form-data" name="adminForm" id="category-form" class="form-validate"
  aria-label="<?php echo Text::_('COM_JOOMGALLERY_CATEGORY_FORM_TITLE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" >

  <div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('title'); ?>
    </div>
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('alias'); ?>
    </div>
  </div>

  <div class="main-card">
	<?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'category', 'recall' => true, 'breakpoint' => 768)); ?>
	
  <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'category', Text::_('JCATEGORY', true)); ?>
	<div class="row">
    <div class="col-lg-9">
			<fieldset class="adminform">
        <?php echo $this->form->getLabel('description'); ?>
				<?php echo $this->form->getInput('description'); ?>
      </fieldset>
		</div>
    <div class="col-lg-3">
      <fieldset class="form-vertical">
				<legend class="visually-hidden"><?php echo Text::_('JCATEGORY'); ?></legend>
				<?php echo $this->form->renderField('parent_id'); ?>
				<?php echo $this->form->renderField('published'); ?>
				<?php echo $this->form->renderField('access'); ?>
				<?php echo $this->form->renderField('password'); ?>
        <?php echo $this->form->renderField('rm_password'); ?>
				<?php echo $this->form->renderField('language'); ?>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Options', Text::_('JGLOBAL_FIELDSET_BASIC', true)); ?>
	<div class="row">
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-options" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_BASIC'); ?></legend>
        <div>
				  <?php echo $this->form->renderField('hidden'); ?>
				  <?php echo $this->form->renderField('exclude_toplist'); ?>
				  <?php echo $this->form->renderField('exclude_search'); ?>
        </div>
			</fieldset>
    </div>
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-thumbnail" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_PREVIEW'); ?></legend>
        <div>
				  <?php echo $this->form->renderField('thumbnail'); ?>
        </div>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

  <?php foreach ($fieldSets as $name => $fieldSet) :?>
    <?php if (strpos($name,'fields-') !== 0) continue; ?>
    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', $name, Text::_($fieldSet->label)); ?>
      <?php $this->fieldset = $name; ?>
      <?php echo LayoutHelper::render('joomla.edit.fieldset', $this); ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php endforeach; ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
	<div class="row">
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-publishingdata" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
        <div>
          <?php echo $this->form->renderField('created_time'); ?>
				  <?php echo $this->form->renderField('created_by'); ?>
				  <?php echo $this->form->renderField('modified_by'); ?>
				  <?php echo $this->form->renderField('modified_time'); ?>
				  <?php echo $this->form->renderField('id'); ?>
        </div>				
			</fieldset>
    </div>
    <div class="col-12 col-lg-6">
      <fieldset id="fieldset-metadata" class="options-form">
				<legend><?php echo Text::_('JGLOBAL_FIELDSET_METADATA_OPTIONS'); ?></legend>
        <div>
          <?php echo $this->form->renderField('metadesc'); ?>
				  <?php echo $this->form->renderField('metakey'); ?>
				  <?php echo $this->form->renderField('robots'); ?>
        </div>				
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Displayparams', Text::_('COM_JOOMGALLERY_PARAMETERS', true)); ?>
	<div class="row">
    <div class="col-12">
      <fieldset id="fieldset-images-params" class="options-form">
        <legend><?php echo Text::_('COM_JOOMGALLERY_PARAMETERS'); ?></legend>
				<div class="control-group">
          <div class="controls"><?php echo $this->form->getInput('params'); ?></div>
        </div>
			</fieldset>
		</div>
	</div>
	<?php echo HTMLHelper::_('uitab.endTab'); ?>

	<?php if($this->getAcl()->checkACL('core.admin','joomgallery')) : ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('COM_JOOMGALLERY_ACTION_CAT_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules'); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php endif; ?>

	<?php if($this->getAcl()->checkACL('core.admin','joomgallery')) : ?>
		<?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'image-permissions', Text::_('COM_JOOMGALLERY_ACTION_IMAGE_PERMISSIONS_LABEL', true)); ?>
			<?php echo $this->form->getInput('rules-image'); ?>
		<?php echo HTMLHelper::_('uitab.endTab'); ?>
  <?php endif; ?> 
	<?php echo HTMLHelper::_('uitab.endTabSet'); ?>

  <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
	<input type="hidden" name="jform[lft]" value="<?php echo $this->item->lft; ?>" />
	<input type="hidden" name="jform[rgt]" value="<?php echo $this->item->rgt; ?>" />
	<input type="hidden" name="jform[level]" value="<?php echo $this->item->level; ?>" />
	<input type="hidden" name="jform[path]" value="<?php echo $this->item->path; ?>" />
	<input type="hidden" name="jform[in_hidden]" value="<?php echo $this->item->in_hidden; ?>" />
	<input type="hidden" name="task" value=""/>
	<?php echo HTMLHelper::_('form.token'); ?>

</form>
</div>
