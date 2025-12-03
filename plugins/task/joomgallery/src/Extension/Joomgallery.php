<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Plugin\Task\Joomgallery\Extension;

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

/**
 * A task plugin. Offers task routines for JoomGallery {@see TaskPluginTrait},
 * {@see ExecuteTaskEvent}.
 *
 * @since 4.2.0
 */
final class Joomgallery extends CMSPlugin implements SubscriberInterface
{
  use TaskPluginTrait;

  /**
   * Global database object
   *
   * @var    \JDatabaseDriver
   *
   * @since  4.2.0
   */
  protected $db = null;

  /**
   * @var string[]
   * @since 4.2.0
   */
  private const TASKS_MAP = [
    'joomgalleryTask.recreateImage' => [
      'langConstPrefix' => 'PLG_TASK_JOOMGALLERY_TASK_RECREATEIMAGE',
      'method'          => 'recreate',
      'form'            => 'recreateForm',
    ],
  ];

  /**
   * @var boolean
   * @since 4.2.0
   */
  protected $autoloadLanguage = true;

  /**
   * @inheritDoc
   *
   * @return string[]
   *
   * @since 4.2.0
   */
  public static function getSubscribedEvents(): array
  {
    return [
      'onTaskOptionsList'    => 'advertiseRoutines',
      'onExecuteTask'        => 'standardRoutineHandler',
      'onContentPrepareForm' => 'enhanceTaskItemForm',
    ];
  }

  /**
   * Task to recreate an imagetype of one image
   * @param   ExecuteTaskEvent  $event  The `onExecuteTask` event.
   *
   * @return  integer  The routine exit code.
   *
   * @since  4.2.0
   * @throws \Exception
   */
  private function recreate(ExecuteTaskEvent $event): int
  {
    /** @var Task $task */
    $task       = $event->getArgument('subject');
    $params     = $event->getArgument('params');
    $lastStatus = $task->get('last_exit_code', Status::OK);
    $willResume = (bool) $params->resume;
    $webcron    = false;
    $app        = Factory::getApplication();
    $user       = $app->getIdentity();

    // Some applications like CLI or WebCron do not support users
    // We might have to inject a default user to the application
    if(!$user->id)
    {
      $user_id = (int) $params->user;
      $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
      $app->loadIdentity($user);
    }

    // Retreiving param values
    $ids  = array_map('trim', explode(',', $params->cid)) ?? [];
    $type = \strval($params->type) ?? 'thumbnail';

    // Only when using WebCron requests
    $ids_str = $app->input->get('cid', null, 'string');
    $ids_arr = (array) $app->input->get('cid', [], 'int');

    if($ids_str || $ids_arr)
    {
      // There are ids submitted to the task with a request
      // We use this instead
      if($ids_str)
      {
        // ids were submitted as a comma separated string
        $ids = array_map('trim', explode(',', $ids_str));
      }
      else
      {
        // ids were submitted as form array
        $ids = $ids_arr;
      }

      $webcron    = true;
      $willResume = false;
    }

    if($type_val = $app->input->get('type', null, 'string'))
    {
      // There is a catid submitted to the task with a request
      // We use this instead
      $type       = $type_val;
      $webcron    = true;
      $willResume = false;
    }

    // If we retrieve just a zero (0), all images have to be recreated
    // Attention: This will cause long script execution time
    if(\count($ids) == 1 && $ids[0] == 0)
    {
      $this->logTask('Attempt to recreate all available images...');

      $listModel = $app->bootComponent('com_joomgallery')->getMVCFactory()->createModel('images', 'administrator');
      $ids       = array_map(function($item) { return $item->id;}, $listModel->getIDs());
    }

    // Remove zero ids from list
    $ids = array_filter($ids);

    // Load the model to perform the task
    $component = $app->bootComponent('com_joomgallery');
    $model     = $component->getMVCFactory()->createModel('image', 'administrator');

    if(\is_null($model))
    {
      $this->logTask('JoomGallery image model could not be loaded');
      throw new \Exception('JoomGallery image model could not be loaded');
    }

    // Logging
    if($lastStatus === Status::WILL_RESUME)
    {
      $this->logTask(\sprintf('Resuming recreation of images as task %d', $task->get('id')));
      $willResume = true;
    }
    else
    {
      $this->logTask(\sprintf('Starting recreation of %s images as task %d', \count($ids), $task->get('id')));
    }

    // Create list of imagetypes to be skipped
    $skip = array_map(fn($obj) => $obj->typename, JoomHelper::getRecords('imagetypes', $component));
    $skip = array_filter($skip, fn($typename) => $typename !== $type);

    // Actually performing the task using the model and a specific method
    $task_def     = ['model' => $model, 'method' => 'recreate', 'options' => ['original', $skip]];
    $error_msg    = 'Recreation of images failed. Failed image: %s';
    $executed_ids = $this->performTask($ids, $task_def, $params, $error_msg);

    // Check if we are finished
    if(\count($ids) == \count($executed_ids))
    {
      // We finished the job
      $willResume = false;
      $params->successful = '';
    }

    // Log our intention to resume or not and return the appropriate exit code.
    if($willResume && !$webcron)
    {
      // Write params with successful executed ids to database
      $params->successful = implode(',', $executed_ids);
      $this->logTask(\sprintf('Recreation of images (Task %d) will resume', $task->get('id')));
    }
    else
    {
      $this->logTask(\sprintf('Recreation of images (Task %d) is now complete', $task->get('id')));
      $willResume = false;
    }

    // Update params
    $this->setParams($task->get('id'), $params);

    return $willResume ? Status::WILL_RESUME : Status::OK;
  }

  /**
   * Performs the actual task with the model defined in the 
   * 
   * @param   array   $ids         The id of the task
   * @param   array   $task_def    Task definition array in the form
   *                               ['model' => (object) Model, 'method' => (string) method-name, 'options' => (array) method-arguments]
   * @param   object  $params      The params object
   * @param   string  $error_msg   The message to be logged on error
   * 
   * @return  array   List of ecexuted ids
   * 
   * @since   4.2.0
   */
  private function performTask(array $ids, array $task_def, object $params, string $error_msg = ''): array
  {
    $max_time = (int) \ini_get('max_execution_time');

    // Check if model exists and is an instance of BaseModel
    if(!isset($task_def['model']) || !$task_def['model'] instanceof \Joomla\CMS\MVC\Model\BaseModel)
    {
      throw new \InvalidArgumentException('Invalid model given. Must be an instance of Joomla\CMS\MVC\Model\BaseModel');
    }

    // Check if method exists in the model
    if(!isset($task_def['method']) || !method_exists($task_def['model'], $task_def['method']))
    {
      throw new \InvalidArgumentException('Invalid method given. Method does not exist on the provided model');
    }

    // Check if options is an array
    if(!isset($task_def['options']) || !\is_array($task_def['options']))
    {
      throw new \InvalidArgumentException('Invalid options given: Options must be an array');
    }

    // Check that $task_def is correctly given
    $model    = $task_def['model'];
    $method   = $task_def['method'];
    $options  = $task_def['options'];

    $assumed_duration = 1;
    $successful   = \is_string($params->successful) ? $params->successful : '';
    $executed_ids = $successful !== '' ? array_map('trim', explode(',', $successful)) : [];

    foreach($ids as $id)
    {
      // Skip the already executed ids
      if(\in_array($id, $executed_ids))
      {
        continue;
      }

      // Check if we can still continue executing the task
      $execute_task = true;

      if($max_time !== 0)
      {
        $remaining = $max_time - (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);

        if($assumed_duration > $remaining)
        {
          $execute_task = false;
        }
      }

      if($execute_task)
      {
        // Continue execution
        $start   = microtime(true);
        $success = $model->{$method}($id, ...$options);
        $assumed_duration = microtime(true) - $start;

        if(!$success && $error_msg)
        {
          // We log failed recreations.
          $this->logTask(\sprintf($error_msg, $id));
        }

        // Add id to executed ids array
        array_push($executed_ids, $id);
      }
      else
      {
        // Stop execution
        break;
      }
    }

    return $executed_ids;
  }

  /**
   * Writes the params to the database
   * 
   * @param   int     $task_id  The id of the task
   * @param   object  $params   The params object
   * 
   * @return  void
   * 
   * @since   4.2.0
   */
  private function setParams($task_id, $params)
  {
    $params = new Registry($params);

    $query = $this->db->getQuery(true);

    $query->update($this->db->quoteName('#__scheduler_tasks'))
          ->set($this->db->quoteName('params') . ' = ' . $this->db->quote($params->toString('json')))
          ->where($this->db->quoteName('id') . ' = :extension_id')
          ->bind(':extension_id', $task_id, ParameterType::INTEGER);

    $this->db->setQuery($query);

    try
    {
      $this->db->execute();
    }
    catch(\Exception $e)
    {
      $this->logTask(\sprintf('[Task ID %d] Error storing task params: ' . $e->getMessage(), $task_id));
    }
  }
}
