<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @subpackage plg_privacyjoomgalleryimages                                           **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/
namespace Joomgallery\Plugin\Task\Joomgallery\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Joomla\Component\Scheduler\Administrator\Task\Task;
use Joomla\Component\Scheduler\Administrator\Task\Status;
use Joomla\Component\Scheduler\Administrator\Event\ExecuteTaskEvent;
use Joomla\Component\Scheduler\Administrator\Traits\TaskPluginTrait;

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
   * @var string[]
   * @since 4.2.0
   */
  private const TASKS_MAP = [
    'joomgalleryTask.recreateImage' => [
      'langConstPrefix' => 'PLG_TASK_JOOMGALLERY_TASK_RECREATEIMAGE',
      'method'          => 'recreate',
      'form'            => 'recreateForm'
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
    $task         = $event->getArgument('subject');
    $params       = $event->getArgument('params');
    $lastStatus   = $task->get('last_exit_code', Status::OK);
    $max_time     = (int) \ini_get('max_execution_time');
    $willResume   = true;
    $webcron      = false;
    $app          = Factrory::getApplication();

    // Retreiving param values
    $ids  = \array_map('trim', \explode(',', $params->ids)) ?? [];
    $type = \strval($params->type) ?? 'thumbnail';

    // Only when using WebCron requests
    if($ids_val = (array) $app->input->get('cid', [], 'int'))
    {
      // There are ids submitted to the task with a request
      // We use this instead
      $ids     = $ids_val;
      $webcron = true;
    }
    if($type_val = $app->input->get('type', null, 'string'))
    {
      // There is a catid submitted to the task with a request
      // We use this instead
      $type    = $type_val;
      $webcron = true;
    }

    // If we retrieve just a zero (0), all images have to be recreated
    // Attention: This will cause long script execution time
    if(\count($ids) == 1 && $ids[0] == 0)
    {
      $this->logTask('Attempt to recreate all available images...');

      //ToDo: get list of all possible image ids
    }

    // Load the model to perform the task
    $model = $app->bootComponent('com_joomgallery')->getMVCFactory()->createModel('image', 'administrator');

    if(\is_null($model))
    {
      $this->logTask('JoomGallery image model could not be loaded');
      throw new \Exception('JoomGallery image model could not be loaded');
    }

    // Logging
    if($lastStatus === Status::WILL_RESUME)
    {
      $this->logTask(\sprintf('Resuming recreation of images as task %d', $task->get('id')));
    }
    else
    {
      $this->logTask(\sprintf('Starting recreation of %s images as task %d', \count($ids), $task->get('id')));
    }

    $assumed_duration = 1;
    $executed_ids = \array_map('trim', \explode(',', $params->successful));
    foreach($ids as $id)
    {
      // Skip the already executed ids
      if(in_array($id, $executed_ids))
      {
        continue;
      }

      // Check if we can still continue executing the task
      $execute_task = true;
      if($max_time !== 0)
      {
        $remaining = $max_time - (\microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']);
        if($assumed_duration > $remaining)
        {
          $execute_task = false;
        }
      }

      if($execute_task)
      {
        // Continue execution
        $start   = \microtime(true);
        $success = $model->recreate($id, $type);
        $assumed_duration = \microtime(true) - $start;

        if(!$success)
        {
          // We log failed recreations.
          $this->logTask(\sprintf('Recreation of images failed. Failed image: %s', $id));
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

    // Check if we are finished
    if(\count($ids) == \count($executed_ids))
    {
      // We finished the job
      $willResume = false;
      $params->successful = [];
    }

    // Log our intention to resume or not and return the appropriate exit code.
    if($willResume && !$webcron)
    {
      // Write params with successful executed ids to database
      $params->successful = \implode(',', $executed_ids);
      $this->setParams($task->get('id'), $params);

      $this->logTask(\sprintf('Recreation of images (Task %d) will resume', $task->get('id')));
    }
    else
    {
      $this->logTask(\sprintf('Recreation of images (Task %d) is now complete', $task->get('id')));
      $willResume = false;
    }

    return $willResume ? Status::WILL_RESUME : Status::OK;
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
