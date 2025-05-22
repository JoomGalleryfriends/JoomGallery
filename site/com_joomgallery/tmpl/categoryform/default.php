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
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useScript('bootstrap.collapse')
   ->useScript('com_joomgallery.form-edit')
   ->useStyle('com_joomgallery.site');

// Load admin language file
$lang = Factory::getApplication()->getLanguage();
$lang->load('com_joomgallery', JPATH_SITE);
$lang->load('com_joomgallery', JPATH_ADMINISTRATOR);
$lang->load('joomla', JPATH_ADMINISTRATOR);

if($this->item->id)
{
  // ID given -> edit
  $canEdit  = $this->getAcl()->checkACL('edit', 'com_joomgallery.category', $this->item->id);
}
else
{
  // ID = null -> add
  $canEdit  = $this->getAcl()->checkACL('add', 'com_joomgallery.category', 0, 1, true);
}
$canAdmin = $this->getAcl()->checkACL('admin', 'com_joomgallery');
?>

<div class="jg category-edit front-end-edit item-page">
	<?php if (!$canEdit) : ?>
		<?php Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error'); ?>
	<?php else : ?>
		<form id="adminForm" action="<?php echo Route::_('index.php?option=com_joomgallery&controller=categoryform&id='.$this->item->id); ?>"
			    method="post" name="adminForm" class="form-validate form-horizontal" enctype="multipart/form-data">
      <fieldset>
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'category')); ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'category', Text::_('JCATEGORY', true)); ?>
          <?php echo $this->form->renderField('title'); ?>
          <?php echo $this->form->renderField('alias'); ?>
          <?php echo $this->form->renderField('parent_id'); ?>
          <?php echo $this->form->renderField('published'); ?>
          <?php echo $this->form->renderField('access'); ?>
          <?php echo $this->form->renderField('password'); ?>
          <?php echo $this->form->renderField('language'); ?>
          <?php echo $this->form->renderField('description'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Options', Text::_('JGLOBAL_FIELDSET_BASIC', true)); ?>
          <?php echo $this->form->renderField('hidden'); ?>
          <?php echo $this->form->renderField('exclude_toplist'); ?>
          <?php echo $this->form->renderField('exclude_search'); ?>
          <?php echo $this->form->renderField('thumbnail'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
          <?php echo $this->form->renderField('created_time'); ?>
          <?php echo $this->form->renderField('created_by'); ?>
          <?php echo $this->form->renderField('modified_by'); ?>
          <?php echo $this->form->renderField('modified_time'); ?>
          <?php echo $this->form->renderField('id'); ?>
          <?php echo $this->form->renderField('metadesc'); ?>
          <?php echo $this->form->renderField('metakey'); ?>
          <?php echo $this->form->renderField('robots'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Displayparams', Text::_('COM_JOOMGALLERY_PARAMETERS', true)); ?>
          <div class="control-group">
            <div class="controls"><?php echo $this->form->getInput('params'); ?></div>
          </div>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>

        <?php if($canAdmin) : ?>
          <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
            <div class="fltlft">
              <fieldset class="panelform">
                  <?php echo $this->form->getLabel('rules'); ?>
                  <?php echo $this->form->getInput('rules'); ?>
              </fieldset>
            </div>
          <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php endif; ?>

        <input type="hidden" name="jform[checked_out]" value="<?php echo isset($this->item->checked_out) ? $this->item->checked_out : ''; ?>" />
        <input type="hidden" name="jform[lft]" value="<?php echo isset($this->item->lft) ? $this->item->lft : ''; ?>" />
        <input type="hidden" name="jform[rgt]" value="<?php echo isset($this->item->rgt) ? $this->item->rgt : ''; ?>" />
        <input type="hidden" name="jform[level]" value="<?php echo isset($this->item->level) ? $this->item->level : ''; ?>" />
        <input type="hidden" name="jform[path]" value="<?php echo isset($this->item->path) ? $this->item->path : ''; ?>" />
        <input type="hidden" name="jform[in_hidden]" value="<?php echo isset($this->item->in_hidden) ? $this->item->in_hidden : ''; ?>" />

        <input type="hidden" name="type" id ="itemType" value="categoryform"/>
        <input type="hidden" name="return" value="<?php echo $this->return_page; ?>"/>
        <input type="hidden" name="task" value=""/>
        <?php echo HTMLHelper::_('form.token'); ?>
      </fieldset>
        
      <div class="mb-2">
        <button class="btn btn-primary" type="button" data-submit-task="categoryform.save">
          <span class="fas fa-check" aria-hidden="true"></span> <?php echo Text::_('JAPPLY'); ?>
        </button>
        <button class="btn btn-danger" type="button" data-submit-task="categoryform.cancel">
          <span class="fas fa-times" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
        </button>
      </div>
		</form>
	<?php endif; ?>
</div>
