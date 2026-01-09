<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\Component\Scheduler\Administrator\Helper\SchedulerHelper;
use Joomla\Component\Scheduler\Administrator\Task\TaskOption;
use Joomla\Registry\Registry;

/*
 * Task model.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class TaskModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'task';

  /**
   * Stock method to auto-populate the model state.
   *
   * @return  void
   *
   * @since   4.2.0
   */
  protected function populateState()
  {
    parent::populateState();

    $taskType   = $this->app->getUserState('com_joomgallery.add.task.task_type');
    $taskOption = $this->app->getUserState('com_joomgallery.add.task.task_option');

    $this->setState($this->getName() . '.type', $taskType);
    $this->setState($this->getName() . '.option', $taskOption);
  }

  /**
   * Method to get the record form.
   *
   * @param   array    $data      An optional array of data for the form to interogate.
   * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
   *
   * @return  Form|boolean  A \JForm object on success, false on failure
   *
   * @since   4.2.0
   */
  public function getForm($data = [], $loadData = true)
  {
    $form = parent::getForm($data, $loadData);

    // If new entry, set task type from state
    if($this->getState('task.id', 0) === 0 && $this->getState('task.type') !== null)
    {
      $form->setValue('type', null, $this->getState('task.type'));
    }
    else
    {
      $form->setFieldAttribute('type', 'readonly', 'true');
    }

    return $form;
  }

  /**
   * Method to get the data that should be injected in the form.
   *
   * @return  mixed  The data for the form.
   *
   * @since   4.2.0
   */
  protected function loadFormData()
  {
    // Check the session for previously entered form data.
    $data = $this->app->getUserState(_JOOM_OPTION . '.edit.task.data', []);

    if(empty($data))
    {
      if($this->item === null)
      {
        $this->item = $this->getItem();
      }

      $data = $this->item;
    }

    $taskId = $data->id ?? $this->getState($this->getName() . '.id');

    if($taskId > 0)
    {
      try
      {
        $db = $this->getDatabase();

        $query = $db->getQuery(true)
          ->select($db->quoteName('item_id'))
          ->from($db->quoteName('#__joomgallery_task_items'))
          ->where($db->quoteName('task_id') . ' = ' . (int) $taskId)
          ->where($db->quoteName('status') . ' = ' . $db->quote('pending'))
          ->order($db->quoteName('id') . ' ASC');

        $db->setQuery($query);
        $pendingItems = $db->loadColumn();

        $data->queue = implode(',', $pendingItems);
      }
      catch (\Exception $e)
      {
        $data->queue = '';
        $this->app->enqueueMessage('Could not load pending items: ' . $e->getMessage(), 'warning');
      }
    }
    else
    {
      $data->queue = '0';
    }

    if(isset($data->params))
    {
      $data->params = (string) $data->params;
    }

    return $data;
  }

  /**
   * Method to get a migrateable record by id.
   *
   * @param   integer  $pk         The id of the primary key.
   * @param   bool     $withQueue  True to load the queue if empty.
   *
   * @return  object|boolean  Object on success, false on failure.
   *
   * @since   4.2.0
   */
  public function getItem($pk = null, $withQueue = true)
  {
    $item = parent::getItem($pk);

    if(!$item)
    {
      $item = parent::getItem(null);
    }

    // Support for params field
    if(isset($item->params))
    {
      $item->params = new Registry($item->params);
    }

    return $item;
  }

  /**
   * @param   array  $data  The form data
   *
   * @return  boolean  True on success, false on failure
   *
   * @since  4.1.0
   * @throws \Exception
   */
  public function save($data): bool
  {
    $queueInput = $data['queue'] ?? null;

    $data['queue'] = '{}';

    if(isset($data['title']))
    {
      $data['successful'] = '{}';
      $data['failed']     = '{}';
      $data['counter']    = '{}';
    }

    $isNew = empty($data['id']);

    if(!parent::save($data))
    {
      return false;
    }

    $taskId = (int) $this->getState($this->getName() . '.id');

    if($taskId === 0)
    {
      $table  = $this->getTable();
      $taskId = isset($table->id) ? (int) $table->id : 0;
    }

    if($isNew && $taskId === 0)
    {
      $taskId = (int) $this->getDatabase()->insertid();
    }

    if($taskId === 0)
    {
      $this->setError(Text::_('COM_JOOMGALLERY_TASK_ERROR_SAVE_FAILED'));

      return false;
    }

    $imageIds = [];

    if($queueInput === '0')
    {
      $imageIds = $this->getAllImageIds();

      if(empty($imageIds))
      {
        $this->app->enqueueMessage(
            Text::_('COM_JOOMGALLERY_TASK_WARN_QUEUE_EMPTY'),
            'warning'
        );
      }
    }
    elseif(!empty($queueInput))
    {
      $imageIds = array_map('trim', explode(',', $queueInput));
      $imageIds = array_filter($imageIds, 'is_numeric');
    }

    return $this->populateTaskItems($taskId, $imageIds);
  }

  /**
   * Befüllt die Job-Queue-Tabelle für einen Task.
   *
   * @param   int    $taskId     Die ID des Haupt-Tasks
   * @param   array  $itemIds    Ein Array von Item-IDs (z.B. Bild-IDs)
   * @return  bool
   */
  private function populateTaskItems(int $taskId, array $itemIds): bool
  {
    $db = $this->getDatabase();

    // Delete old job for task
    $query = $db->getQuery(true)
      ->delete($db->quoteName('#__joomgallery_task_items'))
      ->where($db->quoteName('task_id') . ' = ' . (int) $taskId);
    $db->setQuery($query)->execute();

    if(empty($itemIds))
    {
      return true;
    }

    // Batch insert new jobs for task
    $query = $db->getQuery(true)
      ->insert($db->quoteName('#__joomgallery_task_items'))
      ->columns([$db->quoteName('task_id'), $db->quoteName('item_id'), $db->quoteName('status')]);

    foreach($itemIds as $itemId)
    {
      $query->values((int) $taskId . ', ' . $db->quote((string) $itemId) . ', ' . $db->quote('pending'));
    }

    try
    {
      $db->setQuery($query)->execute();
    }
    catch (\Exception $e)
    {
      $this->setError($e->getMessage());

      return false;
    }

    return true;
  }

  /**
   * @return TaskOption[]  An array of TaskOption objects
   *
   * @throws \Exception
   * @since  4.2.0
   */
  public function getTasks(): array
  {
    $tasks = SchedulerHelper::getTaskOptions()->options;

    // Filter for JoomGallery Tasks
    $jg_tasks = [];

    foreach($tasks as $key => $task)
    {
      if(strpos(strtolower($task->id), 'joomgallery') !== false)
      {
        // Its a JoomGallery task
        array_push($jg_tasks, $task);
      }
    }

    return $jg_tasks;
  }

  /**
   * Holt alle Bild-IDs aus dem ImagesModel.
   *
   * @return  array  Ein Array von Bild-ID-Strings.
   *
   * @since   4.2.0
   */
  private function getAllImageIds(): array
  {
    try
    {
      $db = Factory::getDbo();

      $query = $db->getQuery(true)
        ->select($db->quoteName(['a.id', 'a.title']))
        ->from($db->quoteName(_JOOM_TABLE_IMAGES, 'a'))
        ->where($db->quoteName('a.published') . ' = 1')
        ->where($db->quoteName('a.approved') . ' = 1')
        ->leftJoin(
            $db->quoteName(_JOOM_TABLE_CATEGORIES, 'b') .
            ' ON ' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('b.id')
        )
        ->where($db->quoteName('b.published') . ' = 1')
        ->order($db->quoteName('a.ordering') . ' DESC');

      $db->setQuery($query);
      $allImageObjects = $db->loadObjectList();

      // Nur IDs zurückgeben (als Strings)
      $allImageIds = array_map(fn($item) => (string) $item->id, $allImageObjects);

      return $allImageIds;
    }
    catch (\Exception $e)
    {
    $this->app->enqueueMessage(
        Text::sprintf('COM_JOOMGALLERY_TASK_ERROR_QUEUE_RESOLVE', $e->getMessage()),
        'error'
    );

      return [];
    }
  }

  /**
   * Method to delete one or more records.
   *
   * @param   array  &$pks  An array of record primary keys.
   *
   * @return  boolean  True if successful, false if an error occurs.
   *
   * @since   4.2.0
   */
  public function delete(&$pks)
  {
    // Delete associated task items first (Cascade behavior in PHP)
    if(!empty($pks))
    {
      $db    = $this->getDatabase();
      $query = $db->getQuery(true)
        ->delete($db->quoteName('#__joomgallery_task_items'))
        ->whereIn($db->quoteName('task_id'), $pks); // whereIn handles array sanitization

      try
      {
        $db->setQuery($query)->execute();
      }
      catch (\Exception $e)
      {
        $this->setError($e->getMessage());

        return false;
      }
    }

    return parent::delete($pks);
  }
}
