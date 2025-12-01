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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Router\Route;

/**
 * Config controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ConfigController extends JoomFormController
{
	protected $view_list = 'configs';

	/**
	 * Method to restore a record to its default values.
	 *
	 * @param   string  $key     The name of the primary key of the URL variable.
	 * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   4.0.0
	 */
	public function reset($key = null, $urlVar = null)
	{
		// Check for request forgeries.
		$this->checkToken();

		$model = $this->getModel();
		$table = $model->getTable();

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		$recordId = $this->input->getInt($urlVar);

		// Check if it is a new config set
		if(!isset($recordId) || $recordId == 0)
		{
			// Unable to import into a new record
			$this->setMessage(Text::_('COM_JOOMGALLERY_INFO_IMPORT_EXPORT'), 'error');

			$this->component->addLog(Text::_('COM_JOOMGALLERY_INFO_IMPORT_EXPORT'), 'error', 'jerror');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return false;
		}

		// Populate the row id from the session.
		$data[$key] = $recordId;

		// Load data array from JInput
		$data = $this->input->getArray([]);

		// Exchange data array with default data
		$data = $model->resetData($data);

		// Put data array back to JInput
		$this->input->post->set('jform', $data['jform']);

		// Set task
		$this->task = 'apply';

		// Perform save task
    	parent::save($key, $urlVar);
  	}

	/**
	 * Method to export a record as json object.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   4.0.0
	 */
	public function export()
	{
		// Check for request forgeries.
		$this->checkToken();

		$model = $this->getModel();
		$table = $model->getTable();
		$task  = $this->getTask();

		// Determine the name of the primary key for the data.
		if (empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if (empty($urlVar))
		{
			$urlVar = $key;
		}

		// Populate the row id from the session.
		$recordId = $this->input->getInt($urlVar);

		// Check if it is a new config set
		if(!isset($recordId) || $recordId == 0)
		{
			// Unable to import into a new record
			$this->setMessage(Text::_('COM_JOOMGALLERY_INFO_IMPORT_EXPORT'), 'error');

			$this->component->addLog(Text::_('COM_JOOMGALLERY_INFO_IMPORT_EXPORT'), 'error', 'jerror');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return false;
		}

		// Load table object
		$data                      = $model->getItem($recordId);
		$title                     = $data->title;
		$data->jg_staticprocessing = json_decode($model->getStaticprocessing());

		// json decode subform fields
		$data->jg_replaceinfo       = json_decode($data->jg_replaceinfo);
		$data->jg_dynamicprocessing = json_decode($data->jg_dynamicprocessing);

		foreach ($data as $key => $value)
		{
			if(strpos($key, 'jg_') === false)
			{
				unset($data->{$key});
			}
		}

		// Preparing document
		$this->app->mimeType = 'application/json';
		$this->app->setHeader('Content-Disposition', 'attachment; filename="' . $title . '.json"', true);
		$this->app->sendHeaders();

		// Output json data
		echo json_encode($data);

		$this->app->close();
	}

	/**
	 * Method to import a record as json object.
	 *
	 * @return  boolean  True if successful, false otherwise.
	 *
	 * @since   4.0.0
	 */
	public function import()
	{
		// Check for request forgeries.
		$this->checkToken();

		$model = $this->getModel();
		$table = $model->getTable();
		$task  = $this->getTask();

		// Determine the name of the primary key for the data.
		if(empty($key))
		{
			$key = $table->getKeyName();
		}

		// To avoid data collisions the urlVar may be different from the primary key.
		if(empty($urlVar))
		{
			$urlVar = $key;
		}

		// Populate the row id from the session.
		$recordId = $this->input->getInt($urlVar);

		// Check if it is a new config set
		if(!isset($recordId) || $recordId == 0)
		{
			// Unable to import into a new record
			$this->setMessage(Text::_('COM_JOOMGALLERY_INFO_IMPORT_EXPORT'), 'error');

			$this->component->addLog(Text::_('COM_JOOMGALLERY_INFO_IMPORT_EXPORT'), 'warning', 'jerror');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return false;
		}

		// Get uploaded json file info
		$file = $this->input->files->get('jform', [], 'array')['import_json'];

		// Load form data
		$data = $this->input->post->get('jform', [], 'array');

		// Retrieve json file content
		$file_data = $model->getJSONfile($file, 'import_json');

		if(!$file_data)
		{
			$this->setMessage(Text::sprintf('COM_JOOMGALLERY_ERROR_IMPORT_FAILED', $model->getError()), 'error');

			$this->component->addLog(Text::sprintf('COM_JOOMGALLERY_ERROR_IMPORT_FAILED', $model->getError()), 'error', 'jerror');

			$this->setRedirect(
				Route::_(
					'index.php?option=' . $this->option . '&view=' . $this->view_item
					. $this->getRedirectToItemAppend($recordId, $urlVar), false
				)
			);

			return false;
		}

		// Transfer file content to data array
		foreach($data as $key => $value)
		{
			if(strpos($key, 'jg_') !== false && key_exists($key, $file_data))
			{
				if(\is_array($file_data[$key]) && \count($file_data[$key]) <= 0)
				{
					$data[$key] = '';
				}
				else
				{
					$data[$key] = $file_data[$key];
				}
			}
		}

		// Put data array to JInput
		$this->input->post->set('jform', $data);

		// Set task
		$this->task = 'apply';

		Factory::getApplication()->enqueueMessage(Text::sprintf('COM_JOOMGALLERY_SUCCESS_IMPORT', $file['name']), 'success');

		$this->component->addLog(Text::sprintf('COM_JOOMGALLERY_SUCCESS_IMPORT', $file['name']), 'info', 'jerror');

		// Perform save task
    	parent::save($key, $urlVar);
	}

  /**
   * Analyses an error code and returns its text
   *
   * @param   int      $uploaderror  The errorcode
   * 
   * @return  string   Error message
   * 
   * @since   4.0.0
   */
  protected function checkError($uploaderror)
  {
    // Common PHP errors
    $uploadErrors = [
      1 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_PHP_MAXFILESIZE'),
      2 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_HTML_MAXFILESIZE'),
      3 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_PARTLY_UPLOADED'),
      4 => Text::_('COM_JOOMGALLERY_UPLOAD_ERROR_FILE_NOT_UPLOADED'),
    ];

    if(\in_array($uploaderror, $uploadErrors))
    {
      return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', $uploadErrors[$uploaderror]);
    }


      return Text::sprintf('COM_JOOMGALLERY_ERROR_CODE', Text::_('COM_JOOMGALLERY_ERROR_UNKNOWN'));

  }
}
