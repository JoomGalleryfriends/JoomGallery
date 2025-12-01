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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Layout\LayoutHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
  ->useScript('form.validate')
  ->useScript('bootstrap.collapse')
  ->useScript('com_joomgallery.form-edit')
  ->useStyle('com_joomgallery.site');

HTMLHelper::_('bootstrap.framework');

// Load admin language file
// Load admin lang may be useful as edit is a copy of backend items
$lang = Factory::getApplication()->getLanguage();
//$lang->load('com_joomgallery', JPATH_SITE);
$testOk = $lang->load('com_joomgallery', JPATH_ADMINISTRATOR);
$lang->load('joomla', JPATH_ADMINISTRATOR);

if($this->item->id)
{
  // ID given -> edit
  $canEdit = $this->getAcl()->checkACL('edit', 'com_joomgallery.category', $this->item->id);
}
else
{
  // ID = null -> add
  $canEdit = $this->getAcl()->checkACL('add', 'com_joomgallery.category', 0, 1, true);
}
$canAdmin = $this->getAcl()->checkACL('admin', 'com_joomgallery');

$config    = $this->params['configs'];
$menuParam = $this->params['menu'];

$isShowTitle = $menuParam->get('showTitle') ?? true;

$app       = Factory::getApplication();
$form      = $this->getForm();
$fieldSets = $form->getFieldsets();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';

?>

<div class="jg category-edit front-end-edit item-page">
  <?php if(!$canEdit) : ?>
    <?php Factory::getApplication()->enqueueMessage(Text::_('COM_JOOMGALLERY_ERROR_ACCESS_VIEW'), 'error'); ?>
  <?php else : ?>
    <form id="adminForm"
          action="<?php echo Route::_('index.php?option=com_joomgallery&controller=usercategory&id='.$this->item->id); ?>"
          method="post" name="adminForm" class="form-validate form-horizontal well" enctype="multipart/form-data">

      <?php if($isShowTitle): ?>
        <h3><?php echo Text::_('COM_JOOMGALLERY_USER_CATEGORY_EDIT'); ?></h3>
        <hr>
      <?php endif; ?>

      <div class="form-group">

        <div class="mb-4">
          <button class="btn btn-primary" type="button" data-submit-task="usercategory.save">
            <span class="fas fa-save" aria-hidden="true"></span> <?php echo Text::_('JAPPLY'); ?>
          </button>
          <button class="btn btn-primary" type="button" data-submit-task="usercategory.saveAndClose">
            <span class="fas fa-save" aria-hidden="true"></span> <?php echo Text::_('JSAVEANDCLOSE'); ?>
          </button>
          <button class="btn btn-primary" type="button" data-submit-task="usercategory.save2copy">
            <span class="fas fa-plus" aria-hidden="true"></span> <?php echo Text::_('JSAVEASCOPY'); ?>
          </button>
          <button class="btn btn-primary" type="button" data-submit-task="usercategory.save2new2">
            <span class="fas fa-copy" aria-hidden="true"></span> <?php echo Text::_('JTOOLBAR_SAVE_AND_NEW'); ?>
          </button>
          <button class="btn btn-danger" type="button" data-submit-task="usercategory.cancel">
            <span class="fas fa-times" aria-hidden="true"></span> <?php echo Text::_('JCANCEL'); ?>
          </button>
        </div>

      </div>

      <fieldset>
        <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'category')); ?>

        <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'category', Text::_('JCATEGORY', true)); ?>

        <?php echo $this->form->renderField('title'); ?>
        <?php echo $this->form->renderField('alias'); ?>
        <?php
//        // for root category avoid selection of category display is root info
//        if($this->isUserRootCategory)
//        {
//          $defaultInfo = Text::_('COM_JOOMGALLERY_PARENT_USER_ROOT_INDICATOR');
//          echo $this->form->renderField('parent_id_root', null, $defaultInfo);
//          ?>
<!--          <input type="hidden" name="jform[parent_id]" value="--><?php //echo $this->item->parent_id; ?><!--"/>-->
<!--          --><?php
//        }
//        else
//        {
          echo $this->form->renderField('parent_id');
//        }
        ?>
        <?php echo $this->form->renderField('published'); ?>
        <?php echo $this->form->renderField('access'); ?>
        <?php echo $this->form->renderField('password'); ?>
        <?php echo $this->form->renderField('rm_password'); ?>
        <?php echo $this->form->renderField('language'); ?>
        <?php echo $this->form->renderField('description'); ?>

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

        <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out ?? ''; ?>"/>
        <input type="hidden" name="jform[lft]"         value="<?php echo $this->item->lft ?? ''; ?>"/>
        <input type="hidden" name="jform[rgt]"         value="<?php echo $this->item->rgt ?? ''; ?>"/>
        <input type="hidden" name="jform[level]"       value="<?php echo $this->item->level ?? ''; ?>"/>
        <input type="hidden" name="jform[path]"        value="<?php echo $this->item->path ?? ''; ?>"/>
        <input type="hidden" name="jform[in_hidden]"   value="<?php echo $this->item->in_hidden ?? ''; ?>"/>

        <input type="hidden" name="type" id="itemType" value="usercategory"/>
      </fieldset>

      <input type="hidden" name="return"             value="<?php echo $this->return_page; ?>"/>
      <input type="hidden" name="task"               value=""/>
      <?php echo HTMLHelper::_('form.token'); ?>
    </form>
  <?php endif; ?>
</div>
