<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use Joomla\CMS\Form\Form;
use \Joomla\Component\Scheduler\Administrator\Helper\SchedulerHelper;
use \Joomla\Component\Scheduler\Administrator\Task\TaskOption;

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
		$data = $this->app->getUserState(_JOOM_OPTION.'.edit.task.data', []);

		if(empty($data))
		{
			if($this->item === null)
			{
				$this->item = $this->getItem();
			}

			$data = $this->item;			
		}

		return $data;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   int|string  $pk  The id alias or title of the item
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.2.0
	 */
	public function getItem($pk = null)
	{
    if($item = parent::getItem($pk))
    {
      if(isset($item->params))
      {
        $item->params = json_encode($item->params);
      }
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
    return parent::save($data);
  }

  /**
   * @return TaskOption[]  An array of TaskOption objects
   *
   * @throws \Exception
   * @since  4.2.0
   */
  public function getTasks(): array
  {
    return SchedulerHelper::getTaskOptions()->options;
  }
}
