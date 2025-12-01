<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Form\Field\ListField;
use \Joomla\Database\DatabaseInterface;

/**
 * List of JoomGallery Tasks field.
 *
 * @since  4.2.0
 */
class JgtaskField extends ListField
{
  /**
	 * A dropdown field with all created joomgallery tasks of a certain type
	 *
	 * @var    string
	 * @since  4.2.0
	 */
	public $type = 'jgtask';

  /**
   * Method to get the field input markup for a generic list.
   * Use the multiple attribute to enable multiselect.
   *
   * @return  string  The field input markup.
   *
   * @since   4.2.0
   */
  protected function getInput()
  {
    $data = $this->getLayoutData();

    if(\is_object($data['value']))
    {
      $data['value'] = (array) $data['value'];
    }

    $data['options'] = (array) $this->getOptions();

    return $this->getRenderer($this->layout)->render($data);
  }

  /**
	 * Method to get a list of scheduled tasks that correspond to
   * a certain task type.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   4.2.0
	 */
	protected function getOptions()
	{
    // Get all tasks
		$tasks = $this->getScheduledTasks($this->getAttribute('tasktype'));

		// Prepare the empty array
		$options = array();

		foreach($tasks as $task)
		{
      if($task->state > 0)
      {
        $options[] = HTMLHelper::_('select.option', $task->id, $task->title);
      }
		}

		return $options;
	}

  /**
	 * Method to fetch all scheduled tasks of one type from db
   * 
   * @param   string  $type  Task type
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   4.2.0
	 */
	protected function getScheduledTasks(string $type = '')
	{
    // Get a db connection.
		$db = Factory::getContainer()->get(DatabaseInterface::class);

		// Create a new query object.
		$query = $db->getQuery(true);

		// Select all records from the scheduler tasks table where type is matching.
		$query->select($db->quoteName(array('a.id', 'a.title', 'a.type', 'a.state')));
		$query->from($db->quoteName('#__scheduler_tasks', 'a'));

    if(!empty($type))
    {
      $query->where(($db->quoteName('a.type')) .'='. $db->quote($type));
    }

		// Group by id to show user once in dropdown.
    $query->group($db->quoteName(array('a.id')));

		// Reset the query using our newly populated query object.
		$db->setQuery($query);

		// Load the results as a list of stdClass objects.
		return $db->loadObjectList();
  }
}
