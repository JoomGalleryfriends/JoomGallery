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
   * Führt ein einzelnes Task-Item per AJAX aus und gibt JSON zurück.
   *
   * @return  void
   *
   * @since   4.2.0
   */
  public function runTask()
  {
    $this->checkToken() or die(Text::_('JINVALID_TOKEN'));

    $app     = Factory::getApplication();
    $db      = Factory::getDbo();
    $taskId  = $app->input->getInt('task_id');
    $itemRow = null;

    $response = ['success' => true, 'data' => null];
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
        ->where($db->quoteName('task_id') . ' = ' . (int) $taskId)
        ->where($db->quoteName('status') . ' = ' . $db->quote('pending'))
        ->setLimit(1);

      $db->setQuery($query . ' FOR UPDATE');
      $itemRow = $db->loadObject();


      if ($itemRow)
      {
        $updateQuery = $db->getQuery(true)
          ->update($db->quoteName('#__joomgallery_task_items'))
          ->set($db->quoteName('status') . ' = ' . $db->quote('processing'))
          ->set($db->quoteName('processed_at') . ' = NOW()')
          ->where($db->quoteName('id') . ' = ' . (int) $itemRow->id);
        $db->setQuery($updateQuery)->execute();

        $db->transactionCommit();

        $responseData['item_id'] = $itemRow->item_id;

        /** @var \Joomgallery\Component\Joomgallery\Administrator\Model\TaskModel $taskModel */
        $taskModel = $this->getModel();
        $task = $taskModel->getItem($taskId);

//        if (str_starts_with($itemRow->item_id, '9')) {
//          throw new \RuntimeException("Manueller Fehler wenn ItemId mit 8 beginnt.");
//        }

        if (!$task) {
          throw new \RuntimeException('Haupt-Task ' . $taskId . ' nicht gefunden.');
        }

        $this->processTaskItem($task, $itemRow->item_id);

        $query = $db->getQuery(true)
          ->update($db->quoteName('#__joomgallery_task_items'))
          ->set($db->quoteName('status') . ' = ' . $db->quote('success'))
          ->where($db->quoteName('id') . ' = ' . (int) $itemRow->id);
        $db->setQuery($query)->execute();

        $responseData['success'] = true;
      }
      else
      {
        $db->transactionCommit();
        $responseData['success'] = true;
        $responseData['item_id'] = null;
        $responseData['continue'] = false;
      }
    }
    catch (\Exception $e)
    {
      try {
        $db->transactionRollback();
      } catch (\Exception $rollbackException) {
        Factory::getApplication()->enqueueMessage('Rollback failed: ' . $rollbackException->getMessage(), 'error');
      }

      $responseData['success']   = false;
      $responseData['error']     = $e->getMessage();
      $responseData['continue']  = true;

      if ($itemRow) {
        $responseData['item_id'] = $itemRow->item_id;
        $query = $db->getQuery(true)
          ->update($db->quoteName('#__joomgallery_task_items'))
          ->set($db->quoteName('status') . ' = ' . $db->quote('failed'))
          ->set($db->quoteName('error_message') . ' = ' . $db->quote($e->getMessage()))
          ->where($db->quoteName('id') . ' = ' . (int) $itemRow->id);
        $db->setQuery($query)->execute();
      }
    }

    $response['data'] = json_encode($responseData);
    echo json_encode($response);
    $app->close();
  }

  /**
   * Führt die spezifische Aktion für ein Task-Item aus.
   * Löst bei Fehlern eine Exception aus.
   *
   * @param   \stdClass $task    Das Task-Objekt
   * @param   string    $itemId  Die ID des zu verarbeitenden Items
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
    if (!$taskOption)
    {
      throw new \RuntimeException('Task-Typ "' . $task->type . '" ist im SchedulerHelper nicht registriert.');
    }

    $handlerMethod = $this->getTaskParamHandler($task->type);

    if (!method_exists($this, $handlerMethod))
    {
      throw new \RuntimeException('Kein Parameter-Handler für Task-Typ "' . $task->type . '" gefunden (Methode: ' . $handlerMethod . ').');
    }

    $params = $this->{$handlerMethod}($task, $itemId);

    $mockRecord = new \stdClass();
    $mockRecord->id             = 4;
    $mockRecord->type           = $task->type;
    $mockRecord->title          = $task->title;
    $mockRecord->params         = '{}';
    $mockRecord->taskOption     = $taskOption;
    $mockRecord->last_exit_code = Status::OK;

    $schedulerTask = new Task($mockRecord);

    $event = new ExecuteTaskEvent('onExecuteTask', [
      'subject' => $schedulerTask,
      'params'  => $params,
    ]);

    $app->getDispatcher()->dispatch($event->getName(), $event);
    $result = $event->getArgument('result') ?? Status::OK;

    if ($result !== Status::OK)
    {
      throw new \RuntimeException('Task-Plugin meldete Fehlerstatus: ' . $result);
    }
  }

  /**
   * Wandelt einen Task-Typ-String in einen Methoden-Namen für den Handler um.
   * z.B. 'joomgalleryTask.recreateImage' -> 'prepareRecreateImageParams'
   *
   * @param   string $taskType  Der Task-Typ (z.B. 'joomgalleryTask.recreateImage')
   *
   * @return  string            Der Name der Handler-Methode
   *
   * @since   4.2.0
   */
  private function getTaskParamHandler(string $taskType): string
  {
    // Entfernt 'joomgalleryTask.'
    $methodPart = str_replace('joomgalleryTask.', '', $taskType);

    // Wandelt z.B. 'recreateImage' in 'RecreateImage' um
    $methodPart = ucfirst($methodPart);

    return 'prepare' . $methodPart . 'Params';
  }

  /**
   * Bereitet die 'params' für den 'recreateImage' Task vor.
   *
   * @param   \stdClass $task    Das JoomGallery Task-Objekt
   * @param   string    $itemId  Die ID des zu verarbeitenden Items
   *
   * @return  \stdClass         Das $params-Objekt für das Event
   *
   * @since   4.2.0
   */
  private function prepareRecreateImageParams(\stdClass $task, string $itemId): \stdClass
  {
    $app = Factory::getApplication();

    return (object) [
      'cid'     => $itemId,
      'type'    => $task->params->get('type', 'thumbnail'), // 'recreate' specific
      'user'    => $app->getIdentity()->id,
      'instant' => true,
    ];
  }

  /**
   * Holt die Liste der fehlgeschlagenen Items für einen Task via AJAX.
   *
   * @return void
   */
  public function getFailedItems(): void
  {
    $this->checkToken() or die(Text::_('JINVALID_TOKEN'));
    $app = Factory::getApplication();
    $db  = Factory::getDbo();

    $taskId = $app->input->getInt('task_id', 0);

    $response = ['success' => false, 'data' => null];
    $responseData = ['items' => null, 'error' => null];

    if (!$taskId)
    {
      $responseData['error'] = Text::_('COM_JOOMGALLERY_TASK_ERROR_ID_MISSING');
      $response['data'] = json_encode($responseData);
    }
    else
    {
      try
      {
        $query = $db->getQuery(true)
          ->select($db->quoteName(['item_id', 'error_message']))
          ->from($db->quoteName('#__joomgallery_task_items'))
          ->where($db->quoteName('task_id') . ' = ' . (int) $taskId)
          ->andWhere($db->quoteName('status') . ' = ' . $db->quote('failed'));

        $db->setQuery($query);
        $items = $db->loadObjectList();

        $responseData['items'] = $items;
        $response['data'] = json_encode($responseData);
        $response['success'] = true;
      }
      catch (\Exception $e)
      {
        $responseData['error'] = $e->getMessage();
        $response['data'] = json_encode($responseData);
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
    if (!$this->checkToken('post'))
    {
      echo new JsonResponse(null, Text::_('JINVALID_TOKEN'), true);
      $this->app->close();
    }

    $taskId = $this->app->input->getInt('task_id');
    $db     = Factory::getDbo();

    $data = [
      'deleted' => false,
      'reason'  => ''
    ];

    if (!$taskId)
    {
      echo new JsonResponse(null, Text::_('COM_JOOMGALLERY_TASK_ERROR_NO_ID'), true);
      $this->app->close();
    }

    try
    {
      $query = $db->getQuery(true)
        ->select('COUNT(*)')
        ->from($db->quoteName('#__joomgallery_task_items'))
        ->where($db->quoteName('task_id') . ' = ' . (int) $taskId)
        ->where($db->quoteName('status') . ' != ' . $db->quote('success'));

      $db->setQuery($query);
      $notSuccessCount = (int) $db->loadResult();

      if ($notSuccessCount > 0)
      {
        $data['reason'] = Text::_('COM_JOOMGALLERY_TASK_ERROR_ITEMS_FAILED');

        echo new JsonResponse($data, 'Task finished with errors. Cleanup skipped.');
      }
      else
      {
        $pks = [(int)$taskId];
        if ($this->getModel()->delete($pks))
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
    catch (\Exception $e)
    {
      echo new JsonResponse(null, $e->getMessage(), true);
    }

    $this->app->close();
  }
}