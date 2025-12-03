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
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin');
?>

<div class="jg jg-migration step1">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="#" class="btn btn-outline-primary active" aria-current="page"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 1); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step2'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 2); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step3'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 4); ?></a>
    </div>
  </div>

  <h2><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 1); ?>: <?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP1_TITLE'); ?></h2>
  <br />

  <?php if(!empty($this->error)): ?>
    <div class="alert alert-warning" role="alert">
      <?php foreach($this->error as $error) : ?>      
        <p><?php echo $error; ?></p>
      <?php endforeach; ?>
    </div>
    <?php return; ?>
  <?php endif; ?>

  <div class="alert alert-primary" role="alert">
    <h4><?php echo $this->script->title; ?></h4>
    <?php echo $this->script->description; ?>
  </div>

  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.precheck'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate card" aria-label="COM_JOOMGALLERY_MIGRATION_STEP1_TITLE">

    <div class="card-body">
      <?php if(empty($this->error)) : ?>
        <?php foreach($this->form->getFieldsets() as $key => $fieldset) : ?>
          <div class="row">
            <div class="col-12 col-lg-9">
              <fieldset class="options-form">
                <legend><?php echo Text::_($fieldset->label); ?></legend>
                <div>
                  <?php echo $this->form->renderFieldset($fieldset->name);; ?>
                </div>
              </fieldset>
            </div>
          </div>
        <?php endforeach; ?>

        <input type="submit" class="btn btn-primary" value="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP1_BTN_TXT'); ?>"/>
      <?php endif; ?>

      <input type="hidden" name="task" value="migration.precheck"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>      
    </div>
  </form>
</div>
