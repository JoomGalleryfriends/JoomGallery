<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Table;
 
// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Table\Table;
use \Joomla\CMS\Access\Rules;
use \Joomla\Registry\Registry;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\GlobalAssetTableTrait;

/**
 * Tag table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class TagTable extends Table
{
  use JoomTableTrait;
  use GlobalAssetTableTrait;
  use LegacyDatabaseTrait;

  /**
   * List of images connected to this tag
   *
   * @var    array
   * @since  4.0.0
   */
  public $images = null;

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db               A database connector object
	 * @param   bool       $component_exists  True if the component object class exists
	 */
	public function __construct(DatabaseDriver $db, bool $component_exists = true)
	{
		$this->component_exists = $component_exists;
		$this->typeAlias = _JOOM_OPTION.'.tag';

		parent::__construct(_JOOM_TABLE_TAGS, 'id', $db);

		$this->setColumnAlias('published', 'published');

	}

	/**
	 * Overloaded bind function to pre-process the params.
	 *
	 * @param   array  $array   Named array
	 * @param   mixed  $ignore  Optional array or list of parameters to ignore
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     Table:bind
	 * @since   4.0.0
	 * @throws  \InvalidArgumentException
	 */
	public function bind($array, $ignore = '')
	{
		$date = Factory::getDate();
		$task = Factory::getApplication()->input->get('task', '', 'cmd');

    // Support for title field: title
    if(\array_key_exists('title', $array))
    {
      $array['title'] = \trim($array['title']);
      if(empty($array['title']))
      {
        $array['title'] = 'Unknown';
      }
    }

    // Support for alias field: alias
		if(empty($array['alias']))
		{
			if(empty($array['title']))
			{
				$array['alias'] = OutputFilter::stringURLSafe(date('Y-m-d H:i:s'));
			}
			else
			{
				if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
				{
					$array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['title']));
				}
				else
				{
					$array['alias'] = OutputFilter::stringURLSafe(trim($array['title']));
				}
			}
		}
    else
    {
      if(Factory::getApplication()->getConfig()->get('unicodeslugs') == 1)
      {
        $array['alias'] = OutputFilter::stringURLUnicodeSlug(trim($array['alias']));
      }
      else
      {
        $array['alias'] = OutputFilter::stringURLSafe(trim($array['alias']));
      }
    }

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if(!\key_exists('created_by', $array) || empty($array['created_by']))
		{
			$array['created_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if(isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry($array['params']);
			$array['params'] = (string) $registry;
		}

		if(isset($array['metadata']) && \is_array($array['metadata']))
		{
			$registry = new Registry($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

    // Support for list of images to be mapped
    if(isset($array['images']) && !\is_array($array['images']))
		{
			// Try to convert from json string
      $decoded = json_decode($array['images'], true);

      if(\json_last_error() === JSON_ERROR_NONE)
      {
        $array['images'] = $decoded;
      }
      else
      {
        $array['images'] = \explode(',', $array['images']);
      }
		}

		// Bind the rules for ACL where supported.
		if(isset($array['rules']))
		{
      $rules = new Rules($array['rules']);
			$this->setRules($rules);
		}

		return parent::bind($array, $ignore);
	}

  /**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0.0
	 */
	public function store($updateNulls = true)
	{
    $images = null;
    if(\property_exists($this, 'images') && !empty($this->images))
    {
      $images = $this->images;
    }

    if($success = parent::store($updateNulls))
    {
      if(!\is_null($images) && !empty($images))
      {
        // Do the mapping
        $this->addMapping($images);
      }
    }

    return $success;
  }

  /**
   * Delete a record by id
   *
   * @param   mixed  $pk  Primary key value to delete. Optional
   *
   * @return bool
   */
  public function delete($pk = null)
  {
    $this->_trackAssets = false;
    
    if($success = parent::delete($pk))
    {
      // Delete mappings if existent
      $this->removeMapping();
    }

    return $success;
  }

  /**
   * Map one or multiple images to the currently loaded tag.
   *
   * @param   int|array  $img_id  IDs of the images to be mapped.
   *
   * @return  boolean    True on success, False on error.
   *
   * @since   4.0.0
   */
  public function addMapping($img_id)
  {
    if(empty($this->getId()))
    {
      $this->setError('Load table first.');

      return false;
    }

    // Prepare image ids
    if(!\is_array($img_id))
    {
      $img_id = array($img_id);
    }

    // Load db driver
    $db = $this->getDatabase();

    foreach($img_id as $key => $iid)
    {
      $mapping = new \stdClass();
      $mapping->imgid = (int) $iid;
      $mapping->tagid = (int) $this->getId();

      try
      {
        $db->insertObject(_JOOM_TABLE_TAGS_REF, $mapping);
      }
      catch(\Exception $e)
      {
        $this->setError($e->getMessage());
        $this->getComponent()->addLog($e->getMessage(), 'error', 'jerror');

        return false;
      }
    }

    return true;
  }

  /**
   * Remove specific or all mappings of currently loaded tag
   *
   * @param   int|array  $img_id   IDs of the images to be removed. (0: remove all)
   *
   * @return  boolean  True on success, False on error.
   *
   * @since   4.0.0
   */
  public function removeMapping($img_id = 0)
  {
    if(empty($this->getId()))
    {
      $this->setError('Load table first.');

      return false;
    }

    // Prepare image ids
    if(!\is_array($img_id) && $img_id != 0)
    {
      $img_id = array($img_id);
    }

    // Load db driver
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    // Create where conditions
    $query->where($db->quoteName('tagid') . ' = ' . $db->quote((int) $this->getId()));
    if(\is_array($img_id))
    {
      // Delete mapping only for a specified images
      $query->where($db->quoteName('imgid') . ' IN (' . \implode(',', $img_id) . ')');
    }

    // Create the query
    $query->delete($db->quoteName(_JOOM_TABLE_TAGS_REF));
    $db->setQuery($query);

    try
    {
      // Execute the query
      $db->execute();
    }
    catch(\Exception $e)
    {
      $this->setError($e->getMessage());
      $this->getComponent()->addLog($e->getMessage(), 'error', 'jerror');

      return false;
    }

    return true;
  }
}
