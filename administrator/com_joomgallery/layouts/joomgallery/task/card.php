<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

$context = $displayData[0];
$item    = $displayData[1];

$overrideable_params = explode(',', $item->task->params['overrideable_params'])

?>
<div class="card form-layout">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <div>
        <?php // Checkbox wird nur gerendert, wenn eine ID für das Grid übergeben wird, sonst nur ID anzeigen oder weglassen ?>
        <?php if(isset($item->grid_index)) : ?>
          <?php echo HTMLHelper::_('grid.id', $item->grid_index, $item->id, false, 'cid', 'cb', $item->title); ?>
        <?php else : ?>
          <span class="badge bg-secondary">ID: <?php echo $item->id; ?></span>
        <?php endif; ?>
      </div>
      <div>
        <strong><?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?></strong><br>
      </div>
    </div>

    <div class="d-flex gap-1">
      <button type="button"
              title="<?php echo Text::_('Run Log'); ?>"
              class="btn btn-sm"
              data-bs-toggle="modal"
              data-bs-target="#joomgallery-task-modal">
        <span class="fa fa-file-lines m-0"></span>
      </button>
      <a href="<?php echo Route::_('index.php?option=com_joomgallery&view=task&layout=edit&id=' . $item->id); ?>"
         class="btn btn-sm btn-primary" title="<?php echo Text::_('Edit Task'); ?>">
        <span class="fa fa-edit m-0"></span>
      </a>
      <button type="button"
              class="btn btn-sm btn-warning jg-run-instant-task"
              title="<?php echo Text::_('Run/Pause Task'); ?>"
        <?php echo (isset($item->published) && $item->published < 0) ? 'disabled' : ''; ?>
              data-id="<?php echo (int) $item->id; ?>"
              data-title="<?php echo htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8'); ?>"
              data-limit="<?php echo isset($item->params) ? $item->params->get('parallel_limit', 1) : 1 ?>">
        <span class="fa fa-play m-0"></span>
      </button>
    </div>

  </div>
  <div class="card-body">
    <?php if($context == 'com_joomgallery.images'): ?>
      <?php
        /** @var FormFactoryInterface $formFactory */
        $formFactory = Factory::getContainer()->get(FormFactoryInterface::class);
        $form = $formFactory->createForm('com_jommgallery.subform_taskparams', ['control' => 'jform']);
        $form->loadFile(JPATH_COMPONENT_ADMINISTRATOR . '/forms/subform_taskparams.xml');
        $form->bind($item->params->toArray());
      ?>

      <div class="test">
        <?php foreach($item->params as $param => $value): ?>
          <?php if(in_array($param, $overrideable_params)): ?>
            <?php echo $form->renderField($param); ?>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <div class="progress mb-2" style="height: 6px;">
      <div id="progress-<?php echo $item->id; ?>"
           class="progress-bar"
           style="width: <?php echo $item->progress ?? 0; ?>%"
           aria-valuenow="<?php echo $item->progress ?? 0; ?>"
           aria-valuemin="0" aria-valuemax="100">
      </div>
    </div>

    <div class="d-flex justify-content-between small mb-2">
      <span><?php echo Text::_('COM_JOOMGALLERY_PENDING'); ?>: <span id="count-pending-<?php echo $item->id; ?>"><?php echo $item->count_pending ?? 0; ?></span></span>
      <span><?php echo Text::_('COM_JOOMGALLERY_SUCCESSFUL'); ?>: <span id="count-success-<?php echo $item->id; ?>"><?php echo $item->count_success ?? 0; ?></span></span>
      <a href="#"
         class="jg-show-failed-items"
         data-task-id="<?php echo $item->id; ?>"
         data-bs-toggle="modal"
         data-bs-target="#joomgallery-failed-items-modal">
        <?php echo Text::_('COM_JOOMGALLERY_FAILED'); ?>: <span id="count-failed-<?php echo $item->id; ?>"><?php echo $item->count_failed ?? 0; ?></span>
      </a>
    </div>
  </div>
</div>