<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Router\Route;
use Joomla\Component\Scheduler\Administrator\Helper\SchedulerHelper;

/**
 * Task controller class.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class TaskController extends JoomFormController
{
  protected $view_list = 'tasks';

  /**
   * Method to add a new record.
   *
   * @return  boolean  True if the record can be added, false if not.
   *
   * @since   4.2
   */
  public function add(): bool
  {
    $context = "$this->option.edit.task";

    // Access check.
    if(!$this->allowAdd())
    {
      $this->app->enqueueMessage(Text::_('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED'), 'error');
      $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));

      return false;
    }

    $taskType         = $this->app->input->get('type');
    $validTaskOptions = SchedulerHelper::getTaskOptions();
    $taskOption       = $validTaskOptions->findOption($taskType) ?: null;

    if(!$taskOption)
    {
      $this->app->getLanguage()->load('com_scheduler', JPATH_ADMINISTRATOR);
      $this->app->enqueueMessage(Text::_('COM_SCHEDULER_ERROR_INVALID_TASK_TYPE'), 'warning');
      $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_list . $this->getRedirectToListAppend(), false));

      return false;
    }

    // Clear the record edit information from the session.
    $this->app->setUserState($context . '.data', null);

    $this->app->setUserState('com_joomgallery.add.task.task_type', $taskType);
    $this->app->setUserState('com_joomgallery.add.task.task_option', $taskOption);

    // Redirect to the edit screen.
    $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=' . $this->view_item . $this->getRedirectToItemAppend(), false));

    return true;
  }

  /**
   * Override parent cancel method to reset the add task state
   *
   * @param   ?string  $key  Primary key from the URL param
   *
   * @return boolean  True if access level checks pass
   *
   * @since  4.2
   */
  public function cancel($key = null): bool
  {
    $result = parent::cancel($key);

    $this->app->setUserState('com_joomgallery.add.task.task_type', null);
    $this->app->setUserState('com_joomgallery.add.task.task_option', null);

    return $result;
  }
}
