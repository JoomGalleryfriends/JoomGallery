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

// Import CSS
$wa->useStyle('com_joomgallery.admin');
?>

<div class="jg jg-config">
  <form
    action="<?php echo Route::_('index.php?option=com_joomgallery&layout=edit&id=' . (int) $this->item->id); ?>"
    method="post" enctype="multipart/form-data" name="adminForm" id="config-form" class="form-validate form-horizontal">

    <div class="row-fluid">
      <div class="span6 form-horizontal">
        <?php echo $this->form->renderField('title'); ?>
      </div>
      <div class="span6 form-horizontal">
        <?php echo $this->form->renderField('group_id'); ?>
      </div>
    </div>

    <?php //first level TabSet ?>
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

    <?php foreach ($this->fieldsets as $key_L1 => $fieldset_L1) : ?>
      <?php echo HTMLHelper::_('uitab.addTab', 'myTab', $fieldset_L1['this']->title, Text::_($fieldset_L1['this']->label), true); ?>

        <?php //second level TabSet ?>
        <?php if (count($fieldset_L1) > 1 && $key_L1 != 'this') : ?>
          <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

            <?php foreach ($this->fieldsets[$key_L1] as $key_L2 => $fieldset_L2) : ?>
              <?php if ($key_L2 != 'this') : ?>
                <?php echo HTMLHelper::_('uitab.addTab', 'myTab', $fieldset_L2['this']->title, Text::_($fieldset_L2['this']->label), true); ?>

                <?php //third level TabSet ?>
                <?php if (count($fieldset_L2) > 1 && $key_L2 != 'this') : ?>
                  <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', array('active' => 'general')); ?>

                  <?php foreach ($this->fieldsets[$key_L2] as $key_L3 => $fieldset_L3) : ?>
                    <?php if ($key_L3 != 'this') : ?>
                      <?php echo HTMLHelper::_('uitab.addTab', 'myTab', $fieldset_L3['this']->title, Text::_($fieldset_L3['this']->label), true); ?>

                        <?php //third level Fields ?>
                        <div class="row-fluid">
                          <div class="span10 form-horizontal">
                            <fieldset class="adminform">
                              <?php foreach ($this->getFieldset($fieldset_L3['this']->name) as $field) : ?>
                                <?php echo $field->renderField(); ?>
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
                        <?php echo $field->renderField(); ?>
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
                  <?php echo $field->renderField(); ?>
                <?php endforeach; ?>
              </fieldset>
            </div>
          </div>

      <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>

    <?php if (Factory::getUser()->authorise('core.admin','joomgallery')) : ?>
      <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'permissions', Text::_('JGLOBAL_ACTION_PERMISSIONS_LABEL', true)); ?>
        <?php echo $this->form->getInput('rules'); ?>
      <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endif; ?>

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
