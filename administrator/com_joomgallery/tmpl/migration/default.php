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
   ->useScript('com_joomgallery.admin')
   ->useScript('com_joomgallery.tasklessForm');
?>

<div class="jg jg-migration">
  <h2><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_AVAILABLE_SCRIPTS'); ?>:</h2>
  <br />

  <?php foreach ($this->scripts as $name => $script) : ?>
    <?php
      $openMigrations    = false;
      $openMigrationsIDs = [];

      if(!empty($this->openMigrations) && key_exists($name, $this->openMigrations))
      {
        $openMigrations    = true;
        $openMigrationsIDs = $this->openMigrations[$name];
      }
    ?>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=migration'); ?>" method="post" name="adminForm" id="adminForm">
      <div class="row align-items-start">
        <div class="col-md-12">
          <div class="card<?php echo $openMigrations ? ' border border-2 active' : ''; ?>">
            <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($name).'_TITLE'); ?></h3>
            <div class="card-body row">
              <div class="col-2">
                <img src="<?php echo $script['img']; ?>" alt="<?php echo $name; ?> logo">
              </div>
              <div id="formInputContainer" class="col-10">
                <?php if($openMigrations) : ?>
                  <div>
                    <span class="badge bg-success"><?php echo Text::_('COM_JOOMGALLERY_SERVICE_MIGRATION_ACTIVE'); ?></span>
                  </div><br />
                <?php endif; ?>
                <p><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($name).'_DESC'); ?></p>
                <?php if($openMigrations) : ?>
                  <joomla-button id="migration-resume" task="migration.resume">
                    <button class="btn btn-primary" data-submit-task="migration.resume" type="button"><?php echo Text::_('COM_JOOMGALLERY_RESUME'); ?></button>
                  </joomla-button>
                  <joomla-button id="migration-abort" task="migration.delete">
                    <button class="btn btn-primary" data-submit-task="migration.delete" type="button"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_ABORT_MIGRATION'); ?></button>
                  </joomla-button>
                  <?php foreach ($openMigrationsIDs as $openMigrationID) : ?>
                    <input type="hidden" name="cid[]" value="<?php echo $openMigrationID->id; ?>"/>
                  <?php endforeach; ?>
                <?php else: ?>
                  <joomla-button id="migration-start" task="display">
                    <button class="btn btn-primary <?php echo $openMigrations ? ' disabled' : ''; ?>" <?php echo $openMigrations ? 'disabled' : ''; ?> data-submit-task="display" type="button"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_START_SCRIPT'); ?></button>
                  </joomla-button>
                <?php endif; ?>
                <input type="hidden" name="layout" value="step1"/>
                <input type="hidden" name="script" value="<?php echo $name; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </form>
  <?php endforeach; ?>
</div>
