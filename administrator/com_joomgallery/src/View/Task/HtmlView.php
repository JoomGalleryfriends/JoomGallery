<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Task;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\MVC\View\GenericDataException;
use \Joomla\CMS\Toolbar\Toolbar;
use \Joomla\CMS\Toolbar\ToolbarHelper;
use \Joomla\Component\Scheduler\Administrator\Task\TaskOption;

/**
 * View class for a single Task.
 * 
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
	protected $item;

	protected $form;

  /**
   * An array of items
   *
   * @var  TaskOption[]
   * @since  4.2.0
   */
  protected $tasks;

	/**
	 * Display the view
	 *
	 * @param   string  $tpl  Template name
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
    /** @var TaskModel $model */
    $model = $this->getModel();

    $this->layout = $this->app->input->get('layout', 'edit', 'cmd');
		$this->state    = $model->getState();

    if($this->layout == 'select')
    {
      // Select view
      $this->tasks = $model->getTasks();
    }
    else
    {
      // Form view
      $this->item = $model->getItem();
		  $this->form   = $model->getForm();

      // Apply tasktype to taskid field
      $this->form->setFieldAttribute('taskid', 'tasktype', $this->form->getValue('type'));
    }

		// Check for errors.
		if(\count($errors = $model->getErrors()))
		{
			throw new GenericDataException(implode("\n", $errors), 500);
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function addToolbar()
	{
		Factory::getApplication()->input->set('hidemainmenu', true);

    /** @var Toolbar $model */
    $toolbar = $this->getToolbar();

    if($this->layout == 'select')
    {
      // Select view
      ToolbarHelper::title(Text::_('COM_JOOMGALLERY_TASKS').' :: '.Text::_('COM_JOOMGALLERY_TASK_SELECT'), 'play-circle');
      $toolbar->linkButton('cancel')
            ->url('index.php?option=com_joomgallery&view=tasks')
            ->buttonClass('btn btn-danger')
            ->icon('icon-times')
            ->text('JCANCEL');
    }
    else
    {
      // Form view
      ToolbarHelper::title(Text::_('COM_JOOMGALLERY_TASKS').' :: '.Text::_('COM_JOOMGALLERY_TASK_EDIT'), 'play-circle');

      $isNew = ($this->item->id == 0);
      $user  = Factory::getApplication()->getIdentity();

      if(isset($this->item->checked_out))
      {
        $checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $user->id);
      }
      else
      {
        $checkedOut = false;
      }

      // If not checked out, can save the item.
      if(!$checkedOut && ($this->getAcl()->checkACL('core.edit') || ($this->getAcl()->checkACL('core.create'))))
      {
        // Save
        ToolbarHelper::apply('task.apply', 'JTOOLBAR_APPLY');

        // Save&Close
        ToolbarHelper::apply('task.save', 'JTOOLBAR_SAVE');
      }

      if(empty($this->item->id))
      {
        ToolbarHelper::cancel('task.cancel', 'JTOOLBAR_CANCEL');
      }
      else
      {
        ToolbarHelper::cancel('task.cancel', 'JTOOLBAR_CLOSE');
      }
    }
	}
}
