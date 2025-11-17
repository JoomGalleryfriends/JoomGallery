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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Form\FormFactoryInterface;

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin')
   ->useScript('com_joomgallery.migrator');

// Add language strings to JavaScript
Text::script('COM_JOOMGALLERY_MIGRATION_ALREADY_RUNNING');
Text::script('COM_JOOMGALLERY_ERROR_NETWORK_PROBLEM');
Text::script('ERROR');
Text::script('WARNING');
Text::script('INFO');
Text::script('SUCCESS');
?>

<div class="jg jg-migration step3">
  
  <div class="flex-center">
    <div class="btn-group navigation" aria-label="Migration navigation">
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step1'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 1); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step2'); ?>" class="btn btn-outline-primary"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 2); ?></a>
      <a href="#" class="btn btn-outline-primary active" aria-current="page"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?></a>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=migration&layout=step4'); ?>" disabled class="btn btn-outline-primary disabled"><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 4); ?></a>
    </div>
  </div>

  <h2><?php echo Text::sprintf('COM_JOOMGALLERY_STEP_X', 3); ?>: <?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP3_TITLE'); ?></h2>
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
    <h3><?php echo $this->script->title; ?></h3>
    <?php echo $this->script->description; ?>
  </div>

  <br />

  <?php if(empty($this->error) && !empty($this->migrateables)) : ?>
    <?php foreach($this->migrateables as $key => $migrateable) : ?>
      <?php
        $type = $migrateable->get('type');
        $total = count($migrateable->get('queue')) + $migrateable->get('failed')->count() + $migrateable->get('successful')->count();
        
        $dependentCompleted = true;
        foreach($this->dependencies['from'][$key] as $dependency)
        {
          if(!in_array($dependency, $this->completed))
          {
            $dependentCompleted = false;
          }
        }
      ?>
      <form  name="migrationForm-<?php echo $type; ?>" id="migrationForm-<?php echo $type; ?>" action="<?php echo Route::_('index.php?option='._JOOM_OPTION); ?>" method="post">
        <div class="row align-items-start">
          <div class="col-md-12">
            <div class="card">
              <h3 class="card-header"><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($type).'_TITLE'); ?></h3>
              <div class="card-body">
                <div class="badge-group mb-3">
                  <span class="badge bg-secondary"><?php echo Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="badgeQueue-<?php echo $type; ?>"><?php echo count($migrateable->queue); ?></span></span>
                  <span class="badge bg-success"><?php echo Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="badgeSuccessful-<?php echo $type; ?>"><?php echo count($migrateable->successful); ?></span></span>
                  <span class="badge bg-danger"><?php echo Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="badgeFailed-<?php echo $type; ?>"><?php echo count($migrateable->failed); ?></span></span>
                </div>
                <div id="startCond-<?php echo $type; ?>" class="small">
                  <?php echo Text::_('COM_JOOMGALLERY_MIGRATION_START_CONDITION'); ?>:
                  <?php if(empty($this->dependencies['from'][$key])) : ?>
                    <span><?php echo Text::_('JFIELD_OPTION_NONE'); ?></span>
                  <?php else : ?>
                    <?php foreach ($this->dependencies['from'][$key] as $i => $dependency) : ?>
                      <?php
                        if($i > 0) echo ',';

                        $dep_class = 'pending';
                        if($this->migrateables[$dependency]->completed)
                        {
                          $dep_class = 'fulfilled';
                        }
                      ?>
                      <span data-type="<?php echo $dependency; ?>" class="dependency <?php echo $dep_class; ?>"><?php echo Text::_('FILES_JOOMGALLERY_MIGRATION_'.strtoupper($dependency).'_NAME'); ?></span>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
                <button id="migrationBtn-<?php echo $type; ?>" class="btn btn-primary mb-3 btn-migration<?php if($dependentCompleted && !$migrateable->completed){echo '';}else{echo ' disabled';}; ?>" onclick="Migrator.submitTask(event, this)" <?php if($dependentCompleted && !$migrateable->completed){echo '';}else{echo ' disabled';}; ?> data-type="<?php echo $type; ?>"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_START'); ?></button>
                <button id="stopBtn-<?php echo $type; ?>" class="btn mb-3 btn-outline-secondary btn-stop disabled" onclick="Migrator.stopTask(event, this)" disabled="true" data-type="<?php echo $type; ?>"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STOP'); ?></button>
                <button id="repairBtn-<?php echo $type; ?>" class="btn mb-3 btn-outline-secondary<?php echo ($total > 0) ? '' : ' disabled'; ?>" onclick="Migrator.repairTask(event, this)" <?php echo ($total > 0) ? '' : 'disabled'; ?> data-type="<?php echo $type; ?>"><?php echo Text::_('COM_JOOMGALLERY_MIGRATION_MANUAL'); ?></button>
                <input type="hidden" name="type" value="<?php echo $type; ?>"/>
                <input type="hidden" name="task" value="migration.start"/>
                <input type="hidden" name="migrateable" value="<?php echo base64_encode(json_encode($migrateable, JSON_UNESCAPED_UNICODE)); ?>"/>
                <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
                <?php echo HTMLHelper::_('form.token'); ?>
                <span id="is_dependent-<?php echo $type; ?>" style="display: none;"><?php echo json_encode($this->dependencies['from'][$key]); ?></span>
                <span id="dependent_of-<?php echo $type; ?>" style="display: none;"><?php echo json_encode($this->dependencies['of'][$key]); ?></span>
                <div class="progress mb-2">
                  <div id="progress-<?php echo $type; ?>" class="progress-bar" style="width: <?php echo $migrateable->progress; ?>%" role="progressbar" aria-valuenow="<?php echo $migrateable->progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php if($migrateable->progress > 0){echo $migrateable->progress.'%';}; ?></div>
                </div>
                <a class="collapse-arrow mb-2" data-bs-toggle="collapse" href="#collapseLog-<?php echo $type; ?>" role="button" aria-expanded="false" aria-controls="collapseLog">
                  <i class="icon-angle-down"></i><span> <?php echo Text::_('COM_JOOMGALLERY_SHOWLOG'); ?></span>
                </a>
                <div class="collapse mt-2" id="collapseLog-<?php echo $type; ?>">
                  <div id="logOutput-<?php echo $type; ?>" class="card card-body border bg-light log-area">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
      <br />
    <?php endforeach; ?>
  <?php endif; ?>

  <?php
    $total_complete = false;
    if(empty(array_diff_key($this->completed, array_keys($this->migrateables))) && empty(array_diff_key(array_keys($this->migrateables), $this->completed)))
    {
      $total_complete = true;
    }
  ?>
  <form action="<?php echo Route::_('index.php?option='._JOOM_OPTION.'&task=migration.postcheck'); ?>" method="post" enctype="multipart/form-data" 
        name="adminForm" id="migration-form" class="form-validate">

      <input id="step4Btn" type="submit" class="btn btn-primary<?php echo $total_complete ? '' : ' disabled'; ?>" value="<?php echo Text::_('COM_JOOMGALLERY_MIGRATION_STEP3_BTN_TXT'); ?>" <?php echo $total_complete ? '' : 'disabled'; ?>/>
      <input type="hidden" name="task" value="migration.postcheck"/>
      <input type="hidden" name="script" value="<?php echo $this->script->name; ?>"/>
      <?php echo HTMLHelper::_('form.token'); ?>
  </form>

  <?php
  // Load migrepair form
  $formFactory   = Factory::getContainer()->get(FormFactoryInterface::class);
  $migrepairForm = $formFactory->createForm('migrepairForm', array());
  $source        = _JOOM_PATH_ADMIN . '/forms/migrationrepair.xml';

  if ($migrepairForm->loadFile($source) == false)
  {
    throw new \RuntimeException('Form::loadForm could not load file');
  }

  // Migration repair modal box
  $options = array('modal-dialog-scrollable' => true,
                    'title'  => Text::_('COM_JOOMGALLERY_MIGRATION_MANUAL'),
                    'footer' => '<input type="submit" form="migrepairForm" class="btn btn-primary" value="'.Text::_('COM_JOOMGALLERY_MIGRATION_MANUAL_BTN').'"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('JCLOSE').'</button>',
                  );
  $data    = array('script' => $this->script->name, 'form' => $migrepairForm);
  $layout  = new FileLayout('joomgallery.migrepair', null, array('component' => 'com_joomgallery', 'client' => 1));
  $body  = $layout->render($data);

  echo HTMLHelper::_('bootstrap.renderModal', 'repair-modal-box', $options, $body);
  ?>

  <?php
  // Add sleeping mode info modal box
  $options = array('modal-dialog-scrollable' => true,
                    'title'  => Text::_('JFIELD_NOTE_LABEL'),
                    'footer' => '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">'.Text::_('COM_JOOMGALLERY_CONFIRM').'</button>',
                  );
  $body  = Text::_('COM_JOOMGALLERY_MIGRATION_INFO_MODAL_TEXT');

  echo HTMLHelper::_('bootstrap.renderModal', 'info-modal-box', $options, $body);
  ?>

  <script>
    var callback = function(){
      // document ready function;
      Migrator.updateMigrateablesList();

      // Show info modal
      let bsmodal = new bootstrap.Modal(document.getElementById('info-modal-box'), {keyboard: false});
      bsmodal.show();
    }; //end callback
    
    if(document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll))
    {
      callback();
    } else {
      document.addEventListener("DOMContentLoaded", callback);
    }
  </script>
</div>
