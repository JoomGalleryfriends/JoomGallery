<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useScript('keepalive')
   ->useScript('form.validate')
   ->useStyle('com_joomgallery.admin');
HTMLHelper::_('bootstrap.tooltip');

$app = Factory::getApplication();

// In case of modal
$isModal = $app->input->get('layout') === 'modal';
$layout  = $isModal ? 'modal' : 'edit';
$tmpl    = $isModal || $app->input->get('tmpl', '', 'cmd') === 'component' ? '&tmpl=component' : '';
?>

<form
  action="<?php echo Route::_('index.php?option=com_joomgallery&layout=' . $layout . $tmpl . '&id=' . (int) $this->item->id); ?>"
  method="post" enctype="multipart/form-data" name="adminForm" id="task-form" class="form-validate"
  aria-label="<?php echo Text::_('COM_JOOMGALLERY_TASK_FORM_TITLE_' . ((int) $this->item->id === 0 ? 'NEW' : 'EDIT'), true); ?>" >

  <div class="row title-alias form-vertical mb-3">
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('title'); ?>
    </div>
    <div class="col-12 col-md-6">
      <?php echo $this->form->renderField('type'); ?>
    </div>
  </div>
  
  <div class="main-card">
    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'Details', 'recall' => true, 'breakpoint' => 768]); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Details', Text::_('JDETAILS', true)); ?>
    <div class="row">
      <div class="col-lg-8">
        <fieldset class="adminform">
          <?php echo $this->form->renderField('taskid'); ?>
          <?php echo $this->form->renderField('queue'); ?>
        </fieldset>
      </div>
      <div class="col-lg-4">
        <fieldset class="form-vertical">
          <legend class="visually-hidden"><?php echo Text::_('JGLOBAL_FIELDSET_GLOBAL'); ?></legend>
          <?php echo $this->form->renderField('published'); ?>
          <?php echo $this->form->renderField('times_executed'); ?>
          <?php echo $this->form->renderField('note'); ?>
        </fieldset>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'TaskParams', Text::_('COM_JOOMGALLERY_PARAMETERS', true)); ?>
    <div class="row">
      <div class="col-12">
        <?php echo $this->form->getInput('params'); ?>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'Publishing', Text::_('JGLOBAL_FIELDSET_PUBLISHING', true)); ?>
    <div class="row">
      <div class="col-12">
        <fieldset id="fieldset-publishingdata" class="options-form">
          <legend><?php echo Text::_('JGLOBAL_FIELDSET_PUBLISHING'); ?></legend>
          <div>
            <?php echo $this->form->renderField('created_time'); ?>
            <?php echo $this->form->renderField('last_execution'); ?>
            <?php echo $this->form->renderField('id'); ?>
          </div>          
        </fieldset>
      </div>
    </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>
    
    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>
  </div>

  <input type="hidden" name="task" value=""/>
  <?php echo HTMLHelper::_('form.token'); ?>

</form>
