<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\Input\Input;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Application\CMSApplication;
use \Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Controller\JoomAdminController;

/**
 * Images list controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesController extends JoomAdminController
{
  /**
	 * Constructor.
	 *
	 * @param   array                $config   An optional associative array of configuration settings.
	 *                                         Recognized key values include 'name', 'default_task', 'model_path', and
	 *                                         'view_path' (this list is not meant to be comprehensive).
	 * @param   MVCFactoryInterface  $factory  The factory.
	 * @param   CMSApplication       $app      The Application for the dispatcher
	 * @param   Input                $input    The Input object for the request
	 *
	 * @since   4.0.0
	 */
	public function __construct($config = array(), ?MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?Input $input = null)
	{
    parent::__construct($config, $factory, $app, $input);

    // Define standard task mappings.
		$this->registerTask('featured', 'feature');
    $this->registerTask('unfeatured', 'feature');

    $this->registerTask('approved', 'approve');
    $this->registerTask('unapproved', 'approve');
  }

  /**
	 * Method to publish a list of items
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function publish()
	{
    $this->changeState('publish');
  }

  /**
	 * Method to feature a list of items
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function feature()
	{
    $this->changeState('feature');
  }

  /**
	 * Method to approve a list of items
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	public function approve()
	{
    $this->changeState('approve');
  }

	/**
	 * Method to clone existing Images
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
	public function duplicate()
	{
		// Check for request forgeries
		$this->checkToken();

		// Get id(s)
		$pks = $this->input->post->get('cid', array(), 'array');

		try
		{
			if(empty($pks))
			{
				throw new \Exception(Text::_('JERROR_NO_ITEMS_SELECTED'));
			}

			ArrayHelper::toInteger($pks);
			$model = $this->getModel();
			$model->duplicate($pks);

      if(\count($pks) > 1)
      {
        $this->setMessage(Text::_('COM_JOOMGALLERY_ITEMS_SUCCESS_DUPLICATED'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ITEMS_SUCCESS_DUPLICATED'), 'info', 'jerror');
      }
      else
      {
        $this->setMessage(Text::_('COM_JOOMGALLERY_ITEM_SUCCESS_DUPLICATED'));
        $this->component->addLog(Text::_('COM_JOOMGALLERY_ITEM_SUCCESS_DUPLICATED'), 'info', 'jerror');
      }
		}
		catch (\Exception $e)
		{
			$this->component->addLog($e->getMessage(), 'warning', 'jerror');

			Factory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

		$this->setRedirect('index.php?option='._JOOM_OPTION.'&view=images');
	}

  /**
	 * Method to recreate imagetypes of existing Images
	 *
	 * @return  void
	 *
	 * @throws  Exception
	 */
  public function recreate()
  {
    $this->checkToken();

    $pks  = $this->input->post->get('cid', [], 'array');
    $type = $this->input->post->get('type', 'original', 'cmd');

    try
    {
      if (empty($pks)) {
        throw new \Exception(Text::_('JERROR_NO_ITEMS_SELECTED'));
      }

      ArrayHelper::toInteger($pks);

      $taskModel = $this->factory->createModel('Task', 'Administrator');

      $shortRef = bin2hex(random_bytes(3));

      $taskData = [
        'title'     => Text::sprintf('Task (%s)', $shortRef),
        'type'      => 'joomgalleryTask.recreateImage',
        'state'     => 1,
        'access'    => 1,
        'priority'  => 2,
        'note'      => '',
        'queue'     => implode(',', $pks),
        'params'    => json_encode([
          'recreate_type'  => $type,
          'parallel_limit' => 1
        ])
      ];

      if ($taskModel->save($taskData))
      {
        $newTaskId = $taskModel->getState('task.id');

        if (empty($newTaskId)) {
          $newTaskId = $taskModel->getTable()->id;
        }

        $this->setMessage(Text::_('COM_JOOMGALLERY_TASK_CREATED_SUCCESSFULLY'));
        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=images&newTaskId=' . $newTaskId, false));
      }
      else
      {
        $this->setMessage($taskModel->getError(), 'error');
        $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=images', false));
      }
    }
    catch (\Exception $e)
    {
      $this->component->addLog($e->getMessage(), 'warning', 'jerror');
      $this->setMessage($e->getMessage(), 'warning');
      $this->setRedirect(Route::_('index.php?option=' . $this->option . '&view=images', false));
    }
  }

	/**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    Optional. Model name
	 * @param   string  $prefix  Optional. Class prefix
	 * @param   array   $config  Optional. Configuration array for model
	 *
	 * @return  object	The Model
	 *
	 * @since   4.0.0
	 */
	public function getModel($name = 'Image', $prefix = 'Administrator', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
	}	

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 *
	 * @throws  \Exception
	 */
	public function saveOrderAjax()
	{
		// Get the input
		$input = Factory::getApplication()->input;
		$pks   = $input->post->get('cid', array(), 'array');
		$order = $input->post->get('order', array(), 'array');

		// Sanitize the input
		ArrayHelper::toInteger($pks);
		ArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if($return)
		{
			echo "1";
		}

		// Close the application
		Factory::getApplication()->close();
	}

  /**
	 * Method to change the state of a list of items
   * 
   * @param   string   $type   Name of the state to be changed
	 *
	 * @return  void
	 *
	 * @since   4.0
	 */
	protected function changeState($type)
	{
		// Check for request forgeries
		$this->checkToken();

		// Get items to publish from the request.
		$cid   = $this->input->get('cid', array(), 'array');
		$task  = $this->getTask();

    switch($type)
    {
      case 'feature':
        $data  = array('featured' => 1, 'unfeatured' => 0);
        $msgs  = array('FEATURING', 'FEATURED', 'UNFEATURED', '', '');
        break;

      case 'approve':
        $data  = array('approved' => 1, 'unapproved' => 0);
        $msgs  = array('APPROVING', 'APPROVED', 'UNAPPROVED', '', '');
        break;
      
      case 'publish':
      default:
        $data  = array('publish' => 1, 'unpublish' => 0, 'archive' => 2, 'trash' => -2, 'report' => -3);
        $msgs  = array('PUBLISHING', 'PUBLISHED', 'UNPUBLISHED', 'ARCHIVED', 'TRASHED');
        break;
    }

    $value = ArrayHelper::getValue($data, $task, 0, 'int');

		if(empty($cid))
		{
			$this->app->getLogger()->warning(Text::_($this->text_prefix . '_NO_ITEM_SELECTED'), array('image' => 'jerror'));
			$this->component->addLog(Text::_($this->text_prefix . '_NO_ITEM_SELECTED' . array('image' => 'jerror')), 'warning', 'jerror');
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Make sure the item ids are integers
			$cid = ArrayHelper::toInteger($cid);

			// Change the state of the items.
			try
			{
				$model->changeSate($cid, $type,$value);
				$errors = $model->getErrors();
				$ntext = null;

				if ($value === 1)
				{
					if ($errors)
					{
						$this->app->enqueueMessage(Text::plural($this->text_prefix . '_N_ITEMS_FAILED_'.$msgs[0], \count($cid)), 'error');
						$this->component->addLog(Text::plural($this->text_prefix . '_N_ITEMS_FAILED_'.$msgs[0], \count($cid)), 'error', 'jerror');
					}
					else
					{
						$ntext = $this->text_prefix . '_N_ITEMS_'.$msgs[1];
					}
				}
				elseif ($value === 0)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_'.$msgs[2];
				}
				elseif ($value === 2)
				{
					$ntext = $this->text_prefix . '_N_ITEMS_'.$msgs[3];
				}
				else
				{
					$ntext = $this->text_prefix . '_N_ITEMS_'.$msgs[4];
				}

				if (\count($cid))
				{
					$this->setMessage(Text::plural($ntext, \count($cid)));
				}
			}
			catch (\Exception $e)
			{
				$this->component->addLog($e->getMessage(), 'warning', 'jerror');
				$this->setMessage($e->getMessage(), 'error');
			}
		}

		$this->setRedirect(
			Route::_(
				'index.php?option=' . $this->option . '&view=' . $this->view_list
				. $this->getRedirectToListAppend(), false
			)
		);
	}
}
