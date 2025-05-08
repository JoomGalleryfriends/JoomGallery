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

use \Joomla\Registry\Registry;
use \Joomla\Utilities\ArrayHelper;
use \Joomla\CMS\Object\CMSObject;

/**
 * Imagetype model.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagetypeModel extends JoomAdminModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'imagetype';

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

		if($pk > 0 || \is_array($pk))
		{
			// Attempt to load the row.
			$return = $table->load($pk);

			// Check for a table object error.
			if($return === false)
			{
				// If there was no underlying error, then the false means there simply was not a row in the db for this $pk.
				if(!$table->getError())
				{
					// Create new row
          $table->load(0);
				}
				else
				{
					$this->setError($table->getError());
					$this->component->addLog($table->getError(), 'error', 'jerror');

          return false;
				}
			}
		}

		// Convert to \stdClass before adding other data
		$properties = get_object_vars($table);
		$item       = ArrayHelper::toObject($properties);

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
}
