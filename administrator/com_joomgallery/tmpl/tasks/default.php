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

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Joomla\Component\Scheduler\Administrator\Task\Status;

// Load scheduler language file
$this->app->getLanguage()->load('com_scheduler', JPATH_ADMINISTRATOR);

// Import CSS & JS
$wa = $this->document->getWebAssetManager();
$wa->getRegistry()->addRegistryFile('media/com_scheduler/joomla.asset.json');
$wa->useStyle('com_joomgallery.admin')
   ->useScript('com_joomgallery.admin')
   ->useScript('table.columns')
   ->useScript('multiselect')
   ->useStyle('com_scheduler.admin-view-tasks-css');

// Add modified test-task script
$jsPathES5 = JPATH_ROOT . '/media/com_scheduler/js/admin-view-run-test-task-es5.min.js';
$jsPath    = JPATH_ROOT . '/media/com_scheduler/js/admin-view-run-test-task.min.js';

// Prefer the ES5 build, fall back to the modern build
if(is_file($jsPathES5) && is_readable($jsPathES5))
{
  $jsContent = file_get_contents($jsPathES5);
}
elseif(is_file($jsPath) && is_readable($jsPath))
{
  $jsContent = file_get_contents($jsPath);
}

if($jsContent !== false && $jsContent !== '')
{
  $jsContent = str_replace(
      '?option=com_scheduler&view=tasks',
      '?option=com_joomgallery&view=tasks',
      $jsContent
  );

  $wa->addInlineScript($jsContent, ['name' => 'com_scheduler.test-task']);
}
else
{
  $wa->useScript('com_scheduler.test-task');
}

// Add language strings to JS
Text::script('COM_SCHEDULER_TEST_RUN_TITLE');
Text::script('COM_SCHEDULER_TEST_RUN_TASK');
Text::script('COM_SCHEDULER_TEST_RUN_DURATION');
Text::script('COM_SCHEDULER_TEST_RUN_OUTPUT');
Text::script('COM_SCHEDULER_TEST_RUN_STATUS_STARTED');
Text::script('COM_SCHEDULER_TEST_RUN_STATUS_COMPLETED');
Text::script('COM_SCHEDULER_TEST_RUN_STATUS_TERMINATED');
Text::script('JLIB_JS_AJAX_ERROR_OTHER');
Text::script('JLIB_JS_AJAX_ERROR_CONNECTION_ABORT');
Text::script('JLIB_JS_AJAX_ERROR_TIMEOUT');
Text::script('JLIB_JS_AJAX_ERROR_NO_CONTENT');
Text::script('JLIB_JS_AJAX_ERROR_PARSE');

$user      = $this->app->getIdentity();
$userId    = $user->id;
$listOrder = $this->state->get('list.ordering');
$listDirn  = $this->state->get('list.direction');
$canOrder  = $this->getAcl()->checkACL('editstate', 'com_joomgallery');
$saveOrder = ($listOrder == 'a.ordering' && strtolower($listDirn) == 'asc');

if($saveOrder && !empty($this->items))
{
	$saveOrderingUrl = 'index.php?option=com_joomgallery&task=tasks.saveOrderAjax&tmpl=component&' . Session::getFormToken() . '=1';
  HTMLHelper::_('draggablelist.draggable');
}
?>

<div class="row">
  <div class="col-md-12">
    <h2><?php echo Text::_('COM_JOOMGALLERY_TASKS_INSTANT_TASKS'); ?></h2>
    <form action="<?php echo Route::_('index.php?option=com_joomgallery&view=tasks'); ?>" method="post" name="adminForm" id="adminForm">
      <div id="ajax-tasks-container" class="j-main-container">
        <?php echo LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>
        <div class="clearfix"></div>

        <?php if (empty($this->items)) : ?>
          <div class="alert alert-info">
            <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
            <?php echo Text::_('COM_JOOMGALLERY_TASKS_EMPTYSTATE_TITLE'); ?>
          </div>
        <?php else : ?>
          <div class="ms-4 mb-2">
            <span><?php echo HTMLHelper::_('grid.checkall'); ?></span> <span><?php echo Text::_('JGLOBAL_SELECTION_ALL'); ?></span>
          </div>
          <?php foreach ($this->items as $i => $item): ?>
            <div class="row align-items-start">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header flex-column">
                    <h3><?php echo  $this->escape($item->title); ?></h3>
                    <h4 class="small"><?php echo  '(' . $item->type . '.' . $item->taskid . ')'; ?></h4>
                  </div>
                  <div class="card-body d-flex flex-row">
                    <div class="p-2 align-self-center">
                      <?php echo HTMLHelper::_('grid.id', $i, $item->id, false, 'cid', 'cb', $item->title); ?>
                    </div>
                    <div class="p-2 flex-grow-1">
                      <a class="btn btn-sm btn-primary mb-2" href="<?php echo Route::_('index.php?option=com_joomgallery&view=task&layout=edit&id='.$item->id); ?>"><span class="fa fa-edit fa-sm me-2"></span> <?php echo Text::_('JACTION_EDIT'); ?></a>
                      <button type="button" class="btn btn-sm btn-warning mb-2" <?php echo $item->published < 0 ? 'disabled' : ''; ?> data-id="<?php echo (int) $item->id; ?>" data-title="<?php echo htmlspecialchars($item->title); ?>">
                        <span class="fa fa-play fa-sm me-2"></span><?php echo Text::_('COM_JOOMGALLERY_TASK_START'); ?>
                      </button>
                      <div class="badge-group mb-3">
                        <span class="badge bg-secondary"><?php echo Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="badgeQueue-<?php echo $item->id; ?>"><?php echo \count($item->queue); ?></span></span>
                        <span class="badge bg-success"><?php echo Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="badgeSuccessful-<?php echo $item->id; ?>"><?php echo \count($item->successful); ?></span></span>
                        <span class="badge bg-danger"><?php echo Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="badgeFailed-<?php echo $item->id; ?>"><?php echo \count($item->failed); ?></span></span>
                      </div>
                      <div class="progress mb-2">
                        <div id="progress-<?php echo $item->id; ?>" class="progress-bar" style="width: <?php echo $item->progress; ?>%" role="progressbar" aria-valuenow="<?php echo $item->progress; ?>" aria-valuemin="0" aria-valuemax="100"><?php if($item->progress > 0)
                        {
                        echo $item->progress.'%';
                        }; ?></div>
                      </div>
                      <a class="collapse-arrow mb-2" data-bs-toggle="collapse" href="#collapseLog-<?php echo $item->id; ?>" role="button" aria-expanded="false" aria-controls="collapseLog">
                        <i class="icon-angle-down"></i><span> <?php echo Text::_('COM_JOOMGALLERY_SHOWLOG'); ?></span>
                      </a>
                      <div class="collapse mt-2" id="collapseLog-<?php echo $item->id; ?>">
                        <div id="logOutput-<?php echo $item->id; ?>" class="card card-body border bg-light log-area">
                        </div>
                      </div>
                    </div>                      
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="boxchecked" value="0"/>
        <input type="hidden" name="form_submited" value="1"/>
        <?php echo HTMLHelper::_('form.token'); ?>
      </div>
    </form>

    <br><hr><br>

    <h2><?php echo Text::_('COM_SCHEDULER'); ?></h2>
    <div id="scheduler-tasks-container" class="j-main-container">
      <?php if (empty($this->scheduledTasks)) : ?>
        <div class="alert alert-info">
          <span class="icon-info-circle" aria-hidden="true"></span><span class="visually-hidden"><?php echo Text::_('INFO'); ?></span>
          <?php echo Text::_('COM_SCHEDULER_EMPTYSTATE_TITLE'); ?>
        </div>
      <?php else : ?>
        <!-- Tasks table starts here -->
        <table class="table" id="categoryList">
          <caption class="visually-hidden">
            <?php echo Text::_('Scheduled Tasks'); ?>,
            <span id="scheduler-orderedBy"><?php echo Text::_('JGLOBAL_SORTED_BY'); ?> </span>,
            <span id="scheduler-filteredBy"><?php echo Text::_('JGLOBAL_FILTERED_BY'); ?></span>
          </caption>
          <thead>
          <tr>
            <!-- Task State -->
            <th scope="col" class="w-1 text-center">
              <?php echo Text::_('JSTATUS'); ?>
            </th>
            <!-- Task title header -->
            <th scope="col">
              <?php echo Text::_('JGLOBAL_TITLE'); ?>
            </th>
            <!-- Task type header -->
            <th scope="col" class="d-none d-md-table-cell">
              <?php echo Text::_('COM_JOOMGALLERY_TASK_TYPE'); ?>
            </th>
            <!-- Last runs -->
            <th scope="col" class="d-none d-lg-table-cell">
              <?php echo Text::_('COM_JOOMGALLERY_TASK_LAST_RUN_DATE'); ?>
            </th>
            <!-- Run task -->
            <th scope="col" class="d-none d-md-table-cell">
              <?php echo Text::_('COM_JOOMGALLERY_TASK_START_MANUALLY'); ?>
            </th>
            <!-- Nmbr of executions -->
            <th scope="col" class="d-none d-lg-table-cell">
              <?php echo Text::_('COM_JOOMGALLERY_TASK_EXECUTIONS'); ?>
            </th>
            <!-- Is executing -->
            <th scope="col" class="w-5 d-none d-md-table-cell">
              <?php echo Text::_('COM_JOOMGALLERY_EXECUTING'); ?>
            </th>
          </tr>
          </thead>
          <tbody>
          <?php foreach ($this->scheduledTasks as $i => $item) :?>
            <?php
              $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || \is_null($item->checked_out);
              $canChange  = $user->authorise('core.edit.state', 'com_scheduler') && $canCheckin;
            ?>
            <tr class="row<?php echo $i % 2; ?>">
              <!-- Item State -->
              <td class="text-center">
                  <?php echo HTMLHelper::_('jgrid.published', $item->state, $i, 'tasks.', false); ?>
              </td>

              <!-- Item title -->
              <th scope="row">
                <?php if ($item->checked_out) : ?>
                  <?php echo HTMLHelper::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'tasks.', false); ?>
                <?php endif; ?>
                <?php if ($item->locked) : ?>
                  <?php echo HTMLHelper::_('jgrid.action', $i, 'unlock', ['enabled' => $canChange, 'prefix' => 'tasks.',
                    'active_class'                                                  => 'none fa fa-running border-dark text-body',
                    'inactive_class'                                                => 'none fa fa-running', 'tip' => true, 'translate' => false,
                    'active_title'                                                  => Text::sprintf('COM_JOOMGALLERY_TASK_RUNNING_SINCE', HTMLHelper::_('date', $item->last_execution, 'DATE_FORMAT_LC5')),
                    'inactive_title'                                                => Text::sprintf('COM_JOOMGALLERY_TASK_RUNNING_SINCE', HTMLHelper::_('date', $item->last_execution, 'DATE_FORMAT_LC5')),
                  ]); ?>
                <?php endif; ?>
                <span class="task-title">
                  <a href="<?php echo Route::_('index.php?option=com_scheduler&task=task.edit&id=' . $item->id); ?>"
                    title="<?php echo Text::_('JACTION_EDIT'); ?> <?php echo $this->escape($item->title); ?>"> <?php echo $this->escape($item->title); ?>
                  </a>
                  <?php if(!\in_array($item->last_exit_code, [Status::OK, Status::WILL_RESUME])) : ?>
                    <span class="failure-indicator icon-exclamation-triangle" aria-hidden="true"></span>
                    <div role="tooltip">
                      <?php echo Text::sprintf('COM_JOOMGALLERY_TASK_TOOLTIP_TASK_FAILING', $item->last_exit_code); ?>
                    </div>
                  <?php endif; ?>
                </span>
                <?php if ($item->note) : ?>
                  <span class="small">
                    <?php echo Text::sprintf('JGLOBAL_LIST_NOTE', $this->escape($item->note)); ?>
                  </span>
                <?php endif; ?>
              </th>

              <!-- Item type -->
              <td class="small d-none d-md-table-cell">
                  <?php echo $this->escape($item->safeTypeTitle); ?>
              </td>

              <!-- Last run date -->
              <td class="small d-none d-lg-table-cell">
                <?php echo $item->last_execution ? HTMLHelper::_('date', $item->last_execution, 'DATE_FORMAT_LC5') : '-'; ?>
              </td>

              <!-- Run task -->
              <td class="small d-none d-md-table-cell">
                <button type="button" class="btn btn-sm btn-warning" <?php echo $item->state < 0 ? 'disabled' : ''; ?> data-id="<?php echo (int) $item->id; ?>" data-title="<?php echo htmlspecialchars($item->title); ?>" data-bs-toggle="modal" data-bs-backdrop="static" data-bs-target="#scheduler-test-modal">
                  <span class="fa fa-play fa-sm me-2"></span>
                  <?php echo Text::_('COM_JOOMGALLERY_TASK_START_SCHEDULER_TASK'); ?>
                </button>
              </td>

              <!-- Nmbr of executions -->
              <td class="small d-none d-lg-table-cell">
                <?php echo (int) $item->times_executed; ?>
              </td>

              <!-- Is executing -->
              <td class="small d-none d-md-table-cell">
                <?php if(!$item->locked): ?>
                  <?php echo '-'; ?>
                <?php elseif($item->locked < 0): ?>
                  <?php echo 'running'; ?>
                <?php else: ?>
                  <?php echo 'error'; ?>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>

        <?php
          // Modal for scheduler test runs
          $modalparams = ['title' => ''];
          $modalbody   = '<div class="p-3"></div>';
          echo HTMLHelper::_('bootstrap.renderModal', 'scheduler-test-modal', $modalparams, $modalbody);
        ?>
      <?php endif; ?>
      
      <br>
      <a class="btn btn-secondary" href="<?php echo Route::_('index.php?option=com_scheduler&view=tasks'); ?>">Go to Scheduled Tasks view</a>
    </div>
  </div>
</div>
