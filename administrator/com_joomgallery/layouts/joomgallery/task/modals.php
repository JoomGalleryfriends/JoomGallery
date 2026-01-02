<?php

/**
 * @package     com_joomgallery
 * @author      JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @copyright   2008 - 2025 JoomGallery::ProjectTeam
 * @license     GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

/**
 * @var   array $displayData Layout data
 * @var   string $displayData ['logModalTitle'] The title for the task log modal.
 */

$logModalTitle = $displayData['logModalTitle'] ?? Text::_('COM_JOOMGALLERY_TASK_LOG');

// Task runner log modal
$modalParams = [
  'title' => $logModalTitle,
  'id' => 'joomgallery-task-modal'
];
$modalBody = '<div id="jg-modal-log-output" class="card card-body border overflow-visible text-dark log-area" style="max-height: 400px; overflow-y: auto;"></div>';
echo HTMLHelper::_('bootstrap.renderModal', 'joomgallery-task-modal', $modalParams, $modalBody);

// Failed items modal
$failedModalParams = [
  'title' => Text::_('COM_JOOMGALLERY_TASKS_FAILED_ITEMS_TITLE'),
  'id' => 'joomgallery-failed-items-modal'
];
$failedModalBody = '
  <div class="d-flex justify-content-end mb-4">
      <button type="button" class="btn btn-primary" title="'.Text::_('COM_JOOMGALLERY_TASKS_COPY_FAILED_TITLE').'" data-copy-failed-button><span class="fa fa-copy m-0"></span></button>
  </div>
  <div id="jg-failed-items-list" class="overflow-visible text-muted log-area" style="max-height: 400px; overflow-y: auto;"></div>
';
echo HTMLHelper::_('bootstrap.renderModal', 'joomgallery-failed-items-modal', $failedModalParams, $failedModalBody);
