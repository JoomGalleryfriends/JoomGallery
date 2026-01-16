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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Helper\SchedulerHelper;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Joomla\Event\Dispatcher;

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

  /**
   * Executes a single task item via AJAX and returns JSON.
   *
   * @return  void
   *
   * @since   4.2.0
   */
  public function runTask()
  {
    $this->checkToken() || die(Text::_('JINVALID_TOKEN'));

    $app     = Factory::getApplication();
    $db      = Factory::getDbo();
    $taskId  = $app->input->getInt('task_id');
    $itemRow = null;

    $response     = ['success' => true, 'data' => null];
    $responseData = [
      'success'  => false,
      'item_id'  => null,
      'error'    => null,
      'continue' => true,
    ];

    try
    {
      $db->transactionStart();

      $query = $db->getQuery(true)
                  ->select(['id', 'item_id'])
                  ->from($db->quoteName('#__joomgallery_task_items'))
                  ->where($db->quoteName('task_id') . ' = ' . (int)$taskId)
                  ->where($db->quoteName('status') . ' = ' . $db->quote('pending'))
                  ->setLimit(1);

      $db->setQuery($query . ' FOR UPDATE');
      $itemRow = $db->loadObject();


      if($itemRow)
      {
        $updateQuery = $db->getQuery(true)
                          ->update($db->quoteName('#__joomgallery_task_items'))
                          ->set($db->quoteName('status') . ' = ' . $db->quote('processing'))
                          ->set($db->quoteName('processed_at') . ' = NOW()')
                          ->where($db->quoteName('id') . ' = ' . (int)$itemRow->id);
        $db->setQuery($updateQuery)->execute();

        $db->transactionCommit();

        $responseData['item_id'] = $itemRow->item_id;

        /** @var \Joomgallery\Component\Joomgallery\Administrator\Model\TaskModel $taskModel */
        $taskModel = $this->getModel();
        $task      = $taskModel->getItem($taskId);

        if(!$task)
        {
          throw new \RuntimeException('Main Task ' . $taskId . ' not found.');
        }

        $this->processTaskItem($task, $itemRow->item_id);

        $query = $db->getQuery(true)
                    ->update($db->quoteName('#__joomgallery_task_items'))
                    ->set($db->quoteName('status') . ' = ' . $db->quote('success'))
                    ->where($db->quoteName('id') . ' = ' . (int)$itemRow->id);
        $db->setQuery($query)->execute();

        $responseData['success'] = true;
      }
      else
      {
        $db->transactionCommit();
        $responseData['success']  = true;
        $responseData['item_id']  = null;
        $responseData['continue'] = false;
      }
    }
    catch(\Exception $e)
    {
      try
      {
        $db->transactionRollback();
      }
      catch(\Exception $rollbackException)
      {
        Factory::getApplication()->enqueueMessage('Rollback failed: ' . $rollbackException->getMessage(), 'error');
      }

      $responseData['success']  = false;
      $responseData['error']    = $e->getMessage();
      $responseData['continue'] = true;

      if($itemRow)
      {
        $responseData['item_id'] = $itemRow->item_id;
        $query                   = $db->getQuery(true)
                                      ->update($db->quoteName('#__joomgallery_task_items'))
                                      ->set($db->quoteName('status') . ' = ' . $db->quote('failed'))
                                      ->set($db->quoteName('error_message') . ' = ' . $db->quote($e->getMessage()))
                                      ->where($db->quoteName('id') . ' = ' . (int)$itemRow->id);
        $db->setQuery($query)->execute();
      }
    }

    $response['data'] = json_encode($responseData);
    echo json_encode($response);
    $app->close();
  }

  /**
   * Executes the specific action for a task item.
   * Throws an exception on error.
   *
   * @param   \stdClass  $task    The task object
   * @param   string     $itemId  The ID of the item to be processed
   *
   * @return  void
   * @throws  \Exception
   *
   * @since   4.2.0
   */
  private function processTaskItem(\stdClass $task, string $itemId): void
  {
    $app = Factory::getApplication();

    $taskOption = SchedulerHelper::getTaskOptions()->findOption($task->type);

    if(!$taskOption)
    {
      throw new \RuntimeException('Task type "' . $task->type . '" is not registered in SchedulerHelper.');
    }

    $handlerMethod = $this->getTaskParamHandler($task->type);

    if(!method_exists($this, $handlerMethod))
    {
      throw new \RuntimeException('No parameter handler found for task type "' . $task->type . '" (Method: ' . $handlerMethod . ').');
    }

    $params = $this->{$handlerMethod}($task, $itemId);

    $mockRecord                 = new \stdClass();
    $mockRecord->id             = 4;
    $mockRecord->type           = $task->type;
    $mockRecord->title          = $task->title;
    $mockRecord->params         = '{}';
    $mockRecord->taskOption     = $taskOption;
    $mockRecord->last_exit_code = Status::OK;

    $schedulerTask = new Task($mockRecord);

    $event = new ExecuteTaskEvent(
      'onExecuteTask',
      [
        'subject' => $schedulerTask,
        'params'  => $params,
      ]
    );

    $app->getDispatcher()->dispatch($event->getName(), $event);
    $result = $event->getArgument('result') ?? Status::OK;

    if($result !== Status::OK)
    {
      throw new \RuntimeException('Task plugin reported error status: ' . $result);
    }
  }

  /**
   * Converts a task type string into a method name for the handler.
   * e.g. 'joomgalleryTask.recreateImage' -> 'prepareRecreateImageParams'
   *
   * @param   string  $taskType  The task type (e.g. 'joomgalleryTask.recreateImage')
   *
   * @return  string            The name of the handler method
   *
   * @since   4.2.0
   */
  private function getTaskParamHandler(string $taskType): string
  {
    // Removes 'joomgalleryTask.'
    $methodPart = str_replace('joomgalleryTask.', '', $taskType);

    // Converts e.g. 'recreateImage' into 'RecreateImage'
    $methodPart = ucfirst($methodPart);

    return 'prepare' . $methodPart . 'Params';
  }

  /**
   * Prepares the 'params' for the 'recreateImage' task.
   *
   * @param   \stdClass  $task    The JoomGallery task object
   * @param   string     $itemId  The ID of the item to be processed
   *
   * @return  \stdClass         The $params object for the event
   *
   * @since   4.2.0
   */
  private function prepareRecreateImageParams(\stdClass $task, string $itemId): \stdClass
  {
    $app = Factory::getApplication();

    return (object)[
      'cid'     => $itemId,
      'type'    => $task->params->get('type', 'thumbnail'), // 'recreate' specific
      'user'    => $app->getIdentity()->id,
      'instant' => true,
    ];
  }

  /**
   * Retrieves the list of failed items for a task via AJAX.
   *
   * @return void
   */
  public function getFailedItems(): void
  {
    $this->checkToken() || die(Text::_('JINVALID_TOKEN'));
    $app = Factory::getApplication();
    $db  = Factory::getDbo();

    $taskId = $app->input->getInt('task_id', 0);

    $response     = ['success' => false, 'data' => null];
    $responseData = ['items' => null, 'error' => null];

    if(!$taskId)
    {
      $responseData['error'] = Text::_('COM_JOOMGALLERY_TASK_ERROR_ID_MISSING');
      $response['data']      = json_encode($responseData);
    }
    else
    {
      try
      {
        $query = $db->getQuery(true)
                    ->select($db->quoteName(['item_id', 'error_message']))
                    ->from($db->quoteName('#__joomgallery_task_items'))
                    ->where($db->quoteName('task_id') . ' = ' . (int)$taskId)
                    ->andWhere($db->quoteName('status') . ' = ' . $db->quote('failed'));

        $db->setQuery($query);
        $items = $db->loadObjectList();

        $responseData['items'] = $items;
        $response['data']      = json_encode($responseData);
        $response['success']   = true;
      }
      catch(\Exception $e)
      {
        $responseData['error'] = $e->getMessage();
        $response['data']      = json_encode($responseData);
      }
    }

    echo json_encode($response);
    $app->close();
  }

  /**
   * Attempts to clean up the task and its items if successful.
   *
   * @return  void
   * @since   4.2.0
   */
  public function cleanupTask()
  {
    if(!$this->checkToken('post'))
    {
      echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
      $this->app->close();
    }

    $taskId = $this->app->input->getInt('task_id');
    $db     = Factory::getDbo();

    $data = [
      'deleted' => false,
      'reason'  => '',
    ];

    if(!$taskId)
    {
      echo new JsonResponse(null, Text::_('COM_JOOMGALLERY_TASK_ERROR_NO_ID'), true);
      $this->app->close();
    }

    try
    {
      $query = $db->getQuery(true)
                  ->select('COUNT(*)')
                  ->from($db->quoteName('#__joomgallery_task_items'))
                  ->where($db->quoteName('task_id') . ' = ' . (int)$taskId)
                  ->where($db->quoteName('status') . ' != ' . $db->quote('success'));

      $db->setQuery($query);
      $notSuccessCount = (int)$db->loadResult();

      if($notSuccessCount > 0)
      {
        $data['reason'] = Text::_('COM_JOOMGALLERY_TASK_ERROR_ITEMS_FAILED');

        echo new JsonResponse($data, 'Task finished with errors. Cleanup skipped.');
      }
      else
      {
        $pks = [(int)$taskId];

        if($this->getModel()->delete($pks))
        {
          $data['deleted'] = true;
          echo new JsonResponse($data, 'Cleanup successful.');
        }
        else
        {
          $data['reason'] = $this->getModel()->getError();
          echo new JsonResponse($data, 'Cleanup failed: ' . $this->getModel()->getError());
        }
      }
    }
    catch(\Exception $e)
    {
      echo new JsonResponse(null, $e->getMessage(), true);
    }

    $this->app->close();
  }
}
