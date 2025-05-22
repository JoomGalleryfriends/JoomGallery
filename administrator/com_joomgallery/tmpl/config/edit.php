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

use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\LayoutHelper;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
	 ->useScript('form.validate')
   ->useScript('bootstrap.modal')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

// Import modal
$importModal = array(
  'selector' => 'import_modal',
  'params'   => array('title'  => $this->item->title.': '.Text::_($this->form->getField('import_json')->getAttribute('title')),
                      'footer' => '<button class="btn btn-primary" onclick="submitImport(this, event)" aria-label="'.Text::_('COM_JOOMGALLERY_IMPORT').'">'.Text::_('COM_JOOMGALLERY_IMPORT').'</button>'
                     ),
  'body'     => $this->form->renderField('import_json'),
);
$js  = 'var submitImport = function(element, event) {';
$js .=     'event.preventDefault();';
$js .=     'Joomla.submitform("config.import", document.getElementById("config-form"));';
$js .= '};';

// Note modal
$noteModal = array(
  'selector' => 'note_modal',
  'params'   => array('title'  => $this->item->title.': '.Text::_($this->form->getField('note')->getAttribute('title')),
                      'footer' => '<button class="btn btn-primary" data-bs-dismiss="modal" onclick="event.preventDefault()" aria-label="'.Text::_('JCLOSE').'">'.Text::_('JCLOSE').'</button>'
                    ),
  'body'     => $this->form->renderField('note'),
);

$this->document->addScriptDeclaration($js);
?>

<div class="jg jg-config">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="config-form" class="form-validate form-horizontal">

    <div class="row head-row">
      <div class="col-lg-5 form-horizontal">
        <?php echo $this->form->renderField('title'); ?>
      </div>
      <div class="col-lg-5 form-horizontal">
        <?php echo $this->form->renderField('group_id'); ?>
      </div>
      <div class="col-lg-2 form-horizontal">
        <div class="control-group ml">
          <a href="#" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#note_modal" onclick="event.preventDefault()"><?php echo Text::_($this->form->getField('note')->getAttribute('title')); ?></a>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="form-horizontal">
        <p><span class="icon-notification-circle" aria-hidden="true"></span>: <?php echo Text::_('COM_JOOMGALLERY_CONFIG_SENSITIVE_FIELD'); ?></p>
      </div>
    </div>

    <?php echo LayoutHelper::render('libraries.html.bootstrap.modal.main', $noteModal); ?>
    <?php echo LayoutHelper::render('libraries.html.bootstrap.modal.main', $importModal); ?>

    <?php //first level TabSet ?>
    <?php echo HTMLHelper::_('uitab.startTabSet', 'L1-tabset', array('active' => 'general', 'recall' => true)); ?>

    <?php foreach ($this->fieldsets as $key_L1 => $fieldset_L1) : ?>

      <?php if ($key_L1 == 'permissions' && $this->getAcl()->checkACL('admin')) : ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'L1-tabset', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
          <?php echo $this->form->getInput('rules'); ?>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
        <?php continue; ?>
      <?php endif; ?>

      <?php echo HTMLHelper::_('uitab.addTab', 'L1-tabset', $fieldset_L1['this']->name, Text::_($fieldset_L1['this']->label), true); ?>

        <?php //second level TabSet ?>
        <?php if (count($fieldset_L1) > 1 && $key_L1 != 'this') : ?>
          <?php echo HTMLHelper::_('uitab.startTabSet', 'L2-tabset_'.$key_L1, array('active' => 'general', 'recall' => true)); ?>

            <?php foreach ($this->fieldsets[$key_L1] as $key_L2 => $fieldset_L2) : ?>
              <?php if ($key_L2 != 'this') : ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'L2-tabset_'.$key_L1, $fieldset_L2['this']->name, Text::_($fieldset_L2['this']->label), true); ?>

                <?php //third level TabSet ?>
                <?php if (count($fieldset_L2) > 1 && $key_L2 != 'this') : ?>
                  <?php echo HTMLHelper::_('uitab.startTabSet', 'L3-tabset_'.$key_L1.'-'.$key_L2, array('active' => 'general', 'recall' => true)); ?>

                  <?php foreach ($this->fieldsets[$key_L2] as $key_L3 => $fieldset_L3) : ?>
                    <?php if ($key_L3 != 'this') : ?>
                      <?php echo HTMLHelper::_('uitab.addTab', 'L3-tabset_'.$key_L1.'-'.$key_L2, $fieldset_L3['this']->name, Text::_($fieldset_L3['this']->label), true); ?>

                        <?php //third level Fields ?>
                        <div class="row-fluid">
                          <div class="span10 form-horizontal">
                            <fieldset class="adminform">
                              <?php foreach ($this->getFieldset($fieldset_L3['this']->name) as $field) : ?>
                                <?php echo $this->renderField($field); ?>
                              <?php endforeach; ?>
                            </fieldset>
                          </div>
                        </div>

                      <?php echo HTMLHelper::_('uitab.endTab'); ?>
                    <?php endif; ?>
                  <?php endforeach; ?>
                  
                  <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
                <?php endif; ?>

                <?php //second level Fields ?>
                <div class="row-fluid">
                  <div class="span10 form-horizontal">
                    <fieldset class="adminform">
                      <?php foreach ($this->getFieldset($fieldset_L2['this']->name) as $field) : ?>
                        <?php echo $this->renderField($field); ?>
                      <?php endforeach; ?>
                    </fieldset>
                  </div>
                </div>

                <?php echo HTMLHelper::_('uitab.endTab'); ?>
              
              <?php endif; ?>
            <?php endforeach; ?>

          <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
        <?php endif; ?>

          <?php //first level Fields ?>
          <div class="row-fluid">
            <div class="span10 form-horizontal">
              <fieldset class="adminform">
                <?php foreach ($this->getFieldset($fieldset_L1['this']->name) as $field) : ?>
                  <?php echo $this->renderField($field); ?>
                <?php endforeach; ?>
              </fieldset>
            </div>
          </div>

      <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>
    
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="jform[id]" value="<?php echo $this->item->id; ?>" />
    <input type="hidden" name="jform[published]" value="<?php echo $this->item->published; ?>" />
    <input type="hidden" name="jform[ordering]" value="<?php echo $this->item->ordering; ?>" />
    <input type="hidden" name="jform[checked_out]" value="<?php echo $this->item->checked_out; ?>" />
    <?php echo $this->form->renderField('created_by'); ?>
    <?php echo $this->form->renderField('modified_by'); ?>

    <input type="hidden" name="task" value=""/>
    <?php echo HTMLHelper::_('form.token'); ?>

  </form>
</div>
