<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access.
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;
use Joomla\Utilities\ArrayHelper;
use Joomla\CMS\Object\CMSObject;
use \Joomgallery\Component\Joomgallery\Administrator\Model\JoomAdminModel;

/**
 * Imagetype model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagetypeModel extends JoomAdminModel
{
  /**
	 * @var    string  The prefix to use with controller messages.
	 *
	 * @since  4.0.0
	 */
	protected $text_prefix = 'COM_JOOMGALLERY';

	/**
	 * @var    string  Alias to manage history control
	 *
	 * @since  4.0.0
	 */
	public $typeAlias = 'com_joomgallery.imagetype';

	/**
	 * @var    null  Item data
	 *
	 * @since  4.0.0
	 */
	protected $item = null;

  /**
	 * Returns a reference to the a Table object, always creating it.
	 *
	 * @param   string  $type    The table type to instantiate
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  Table    A database object
	 *
	 * @since   4.0.0
	 */
	public function getTable($type = 'Imagetype', $prefix = 'Administrator', $config = array())
	{
		return parent::getTable($type, $prefix, $config);
	}

  /**
	 * Method to get the record form.
	 *
	 * @param   array    $data      An optional array of data for the form to interogate.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  \JForm|boolean  A \JForm object on success, false on failure
	 *
	 * @since   4.0.0
	 */
	public function getForm($data = array(), $loadData = true)
	{
    // Get the form.
		$form = $this->loadForm('com_joomgallery.imagetype', 'imagetype', array('control' => 'jform', 'load_data' => $loadData));

		if(empty($form))
		{
			return false;
		}

		return $form;
  }

  /**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   4.0.0
	 */
	protected function loadFormData()
	{
    if($this->item === null)
    {
      $this->item = $this->getItem();
    }

		return $this->item;
	}

  /**
	 * Method to get a single record.
	 *
	 * @param   integer|array  $pk  The id of the primary key or array(fieldname => value)
	 *
	 * @return  mixed    Object on success, false on failure.
	 *
	 * @since   4.0.0
	 */
	public function getItem($pk = null)
	{
    $pk = (!empty($pk)) ? $pk : (int) $this->getState($this->getName() . '.id');
		$table = $this->getTable();

		if ($pk > 0 || \is_array($pk))
		{
			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if ($return === false)
			{
				// If there was no underlying error, then the false means there simply was not a row in the db for this $pk.
				if (!$table->getError())
				{
					// Create new row
          $table->load(0);
				}
				else
				{
					$this->setError($table->getError());

          return false;
				}
			}
		}

		// Convert to the CMSObject before adding other data.
		$properties = $table->getProperties(1);
		$item = ArrayHelper::toObject($properties, CMSObject::class);

    if(property_exists($item, 'params')) 
		{
			$registry = new Registry($item->params);
			$item->params = $registry->toArray();
		}

    if(isset($item->params))
    {
      $item->params = json_encode($item->params);
    }

		return $item;	
	}

  /**
	 * Prepare and sanitise the table prior to saving.
	 *
	 * @param   Table  $table  Table Object
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
	protected function prepareTable($table)
	{
		jimport('joomla.filter.output');

		if(empty($table->id))
		{
			// Set ordering to the last item if not set
			if(@$table->ordering === '')
			{
				$db = Factory::getDbo();
				$db->setQuery('SELECT MAX(ordering) FROM #__joomgallery_img_types');
        
				$max             = $db->loadResult();
				$table->ordering = $max + 1;
			}
		}
	}
}
