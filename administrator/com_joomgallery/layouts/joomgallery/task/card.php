<?php
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\HTML\HTMLHelper;

/** @var \stdClass $item Das Task-Objekt */
$item = $displayData;
?>
<div class="card form-layout">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <div>
        <?php // Checkbox wird nur gerendert, wenn eine ID für das Grid übergeben wird, sonst nur ID anzeigen oder weglassen ?>
        <?php if (isset($item->grid_index)) : ?>
          <?php echo HTMLHelper::_('grid.id', $item->grid_index, $item->id, false, 'cid', 'cb', $item->title); ?>
        <?php else : ?>
          <span class="badge bg-secondary">ID: <?= $item->id; ?></span>
        <?php endif; ?>
      </div>
      <div>
        <strong><?= htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
      </div>
    </div>

    <div class="d-flex gap-1">
      <button type="button"
              title="<?= Text::_('Run Log'); ?>"
              class="btn btn-sm"
              data-bs-toggle="modal"
              data-bs-target="#joomgallery-task-modal">
        <span class="fa fa-file-lines m-0"></span>
      </button>
      <a href="<?= Route::_('index.php?option=com_joomgallery&view=task&layout=edit&id=' . $item->id); ?>"
         class="btn btn-sm btn-primary" title="<?= Text::_('Edit Task'); ?>">
        <span class="fa fa-edit m-0"></span>
      </a>
      <button type="button"
              class="btn btn-sm btn-warning jg-run-instant-task"
              title="<?= Text::_('Run/Pause Task'); ?>"
        <?= (isset($item->published) && $item->published < 0) ? 'disabled' : ''; ?>
              data-id="<?= (int)$item->id; ?>"
              data-title="<?= htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>"
              data-limit="<?= isset($item->params) ? $item->params->get('parallel_limit', 1) : 1 ?>">
        <span class="fa fa-play m-0"></span>
      </button>
    </div>

  </div>
  <div class="card-body">
    <div class="progress mb-2" style="height: 6px;">
      <div id="progress-<?= $item->id; ?>"
           class="progress-bar"
           style="width: <?= $item->progress ?? 0; ?>%"
           aria-valuenow="<?= $item->progress ?? 0; ?>"
           aria-valuemin="0" aria-valuemax="100">
      </div>
    </div>

    <div class="d-flex justify-content-between small mb-2">
      <span><?= Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="count-pending-<?= $item->id; ?>"><?= $item->count_pending ?? 0; ?></span></span>
      <span><?= Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="count-success-<?= $item->id; ?>"><?= $item->count_success ?? 0; ?></span></span>
      <a href="#"
         class="jg-show-failed-items"
         data-task-id="<?= $item->id; ?>"
         data-bs-toggle="modal"
         data-bs-target="#joomgallery-failed-items-modal">
        <?= Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="count-failed-<?= $item->id; ?>"><?= $item->count_failed ?? 0; ?></span>
      </a>
    </div>
  </div>
</div>