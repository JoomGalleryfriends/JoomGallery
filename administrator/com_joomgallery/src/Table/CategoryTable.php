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
use \Joomla\CMS\Table\Asset;
use \Joomla\CMS\Access\Rules;
use \Joomla\CMS\User\UserHelper;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Filter\OutputFilter;
use \Joomla\Database\DatabaseDriver;
use \Joomla\CMS\Versioning\VersionableTableInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use \Joomgallery\Component\Joomgallery\Administrator\Table\Asset\MultipleAssetsTableTrait;

/**
 * Category table
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryTable extends MultipleAssetsTable implements VersionableTableInterface
{ 
  use JoomTableTrait;
  use MultipleAssetsTableTrait;
  use MigrationTableTrait;
  use LegacyDatabaseTrait;
  
  /**
   * Object property to hold the path of the new location reference node.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $_new_location_path = null;

  /**
   * Object property to hold the path of the old location reference node.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $_old_location_path = null;

  /**
   * Set here the new password
   *
   * @var    string
   * @since  4.0.0
   */
  public $new_pw = '';

  /**
   * True, if you want to delete current password
   *
   * @var    bool
   * @since  4.0.0
   */
  public $rm_pw = false;

  /**
   * The default itemtype
   *
   * @var    string  The name of the itemtype
   * @since  4.0.0
   */
  public $def_itemtype = 'category';

	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db               A database connector object
	 * @param   bool       $component_exists  True if the component object class exists
	 */
	public function __construct(DatabaseDriver $db, bool $component_exists = true)
	{
		$this->component_exists = $component_exists;
		$this->typeAlias = _JOOM_OPTION.'.category';

		parent::__construct(_JOOM_TABLE_CATEGORIES, 'id', $db);

		$this->setColumnAlias('published', 'published');
		$this->getRootId();
	}

  /**
	 * Resets the root_id property to the default value: 0
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  public static function resetRootId()
  {
    self::$root_id = 0;
  }

  /**
   * Method to load a row from the database by primary key and bind the fields to the Table instance properties.
   *
   * @param   mixed    $keys   An optional primary key value to load the row by, or an array of fields to match.
   *                           If not set the instance property value is used.
   * @param   boolean  $reset  True to reset the default values before loading the new row.
   *
   * @return  boolean  True if successful. False if row not found.
   *
   * @since   4.0.0
   */
  public function load($keys = null, $reset = true)
  {
    $res  = parent::load($keys, $reset);
    $comp = Factory::getApplication('administrator')->bootComponent(_JOOM_OPTION);
    $user = $comp->getMVCFactory()->getIdentity();
    $comp->createAccess();

    // Get all unlocked categories of this user from session
    $unlockedCats = Factory::getApplication()->getUserState(_JOOM_OPTION.'unlockedCategories', array(0));

    // Return password only if user is admin or owner
    $this->pw_protected = false;
    if(isset($this->password) && !empty($this->password) && !\in_array($keys, $unlockedCats))
    {
      if(!$comp->getAccess()->checkACL('admin') || $user->id != $this->created_by)
      {
        $this->password = '';
      }
      
      // Set a property showing that the category is protected
      $this->pw_protected = true;
    }

    if(isset($this->path))
    {
      $this->_old_location_path = $this->path;
    }

    return $res;
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

		if($array['id'] == 0)
		{
			$array['created_time'] = $date->toSql();
		}

		if(!\key_exists('created_by', $array) || empty($array['created_by']))
		{
			$array['created_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($array['id'] == 0 && empty($array['modified_by']))
		{
			$array['modified_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_by'] = Factory::getApplication()->getIdentity()->id;
		}

		if($task == 'apply' || \strpos($task, 'save') !== false)
		{
			$array['modified_time'] = $date->toSql();
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

		if(isset($array['params']) && \is_array($array['params']))
		{
			$registry = new Registry;
			$registry->loadArray($array['params']);
			$array['params'] = (string) $registry;
		}

		if(isset($array['metadata']) && \is_array($array['metadata']))
		{
			$registry = new Registry;
			$registry->loadArray($array['metadata']);
			$array['metadata'] = (string) $registry;
		}

    // Support for multiple rules
    foreach ($array as $key => $value)
    {
      if(\strpos($key, 'rules') !== false)
      {
        // We found a rules entry in the data
        if($key === 'rules')
        {
          $itemtype = 'category';
        }
        else
        {
          $itemtype = \str_replace('rules-', '', $key);
        }

        // Bind the rules for ACL where supported.
        $rules = new Rules($value);
        $this->setRules($rules, $itemtype);
      }
    }
		

		return parent::bind($array, $ignore);
	}

	/**
	 * Method to store a row in the database from the Table instance properties.
	 *
	 * If a primary key value is set the row with that primary key value will be updated with the instance property values.
	 * If no primary key value is set a new row will be inserted into the database with the properties from the Table instance.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   4.0.0
	 */
	public function store($updateNulls = true)
	{
    $this->setPathWithLocation();

    // Support for password field
    if(\property_exists($this, 'password'))
    {
      if(\strlen($this->new_pw) > 0)
      {
        // Set a new password
        $this->password = UserHelper::hashPassword($this->new_pw);
      }

      if($this->rm_pw)
      {
        // Remove current password
        $this->password = '';
      }
    }

    // Support for params field
    if(isset($this->params) && !\is_string($this->params))
		{
			$registry = new Registry($this->params);
			$this->params = (string) $registry;
		}

		return parent::store($updateNulls);
	}

  /**
	 * Method to set path based on the location properties.
	 *
	 * @param   boolean  $old  To use the old location path.
	 *
	 * @return  void
	 *
	 * @since   4.0.0
	 */
  public function setPathWithLocation($old = false)
  {
    // Check with new categories and category data changes!!
    if($old && $this->_old_location_path)
    {
      $this->path = $this->_old_location_path;
    }
    elseif($this->_new_location_path)
    {
      $this->path = \str_replace('{alias}', $this->alias, $this->_new_location_path);
    }
  }

	/**
	 * Overloaded check function
	 *
	 * @return bool
	 */
	public function check()
	{
		// If there is an ordering column and this is a new row then get the next ordering value
		if(\property_exists($this, 'ordering') && $this->id == 0)
		{
			$this->ordering = self::getNextOrder();
		}

		// Check if alias is unique inside this parent category
    if($this->_checkAliasUniqueness)
    {
      if(!$this->isUnique('alias', $this->parent_id, 'parent_id'))
      {
        $count = 2;
        $currentAlias =  $this->alias;

        while(!$this->isUnique('alias', $this->parent_id, 'parent_id'))
        {
          $this->alias = $currentAlias . '-' . $count++;
        }
      }
    }

    // Create new path based on alias and parent category
    $manager    = JoomHelper::getService('FileManager', array($this->id));
    $filesystem = JoomHelper::getService('Filesystem');
    $this->path = $manager->getCatPath(0, false, $this->parent_id, $this->alias, false, false);
    $this->path = $filesystem->cleanPath($this->path, '/');

    // Create static_path if compatibility mode is activated and we are not in migration
    if(!$this->is_migration && $this->getComponent()->getConfig()->get('jg_compatibility_mode', 0))
    {
      $alias = $this->alias;
      $static_name = \basename($this->static_path);
      if(\preg_match('/_([0-9]+)$/', $static_name))
      {
        // We found a numeric value at the end of the folder name: e.g alias_6
        // Therefore we use the static folder name instead
        $alias = $static_name;
      }

      $this->static_path = $manager->getCatPath(0, false, $this->parent_id, $alias, false, true);
      $this->static_path = $filesystem->cleanPath($this->static_path, '/');
    }

    // Support for subform field params
    if(empty($this->params))
    {
      $this->params = $this->loadDefaultField('params');
    }
    if(isset($this->params))
    {
      $this->params = new Registry($this->params);
    }

    // Support for field description
    if(empty($this->description))
    {
      $this->description = $this->loadDefaultField('description');
    }

    // Support for field metadesc
    if(empty($this->metadesc))
    {
      $this->metadesc = $this->loadDefaultField('metadesc');
    }

    // Support for field metakey
    if(empty($this->metakey))
    {
      $this->metakey = $this->loadDefaultField('metakey');
    }

		return parent::check();
	}

  /**
   * Method to set the location of a node in the tree object.  This method does not
   * save the new location to the database, but will set it in the object so
   * that when the node is stored it will be stored in the new location.
   *
   * @param   integer  $referenceId  The primary key of the node to reference new location by.
   * @param   string   $position     Location type string.
   *
   * @return  void
   *
   * @note    Since 3.0.0 this method returns void and throws an \InvalidArgumentException when an invalid position is passed.
   * @see     Nested::$_validLocations
   * @since   4.0.0
   * @throws  \InvalidArgumentException
   */
  public function setLocation($referenceId, $position = 'after')
  {
    if(!empty($position))
    {
      parent::setLocation($referenceId, $position);
    }

    if($referenceId !== 0 && !empty($this->id))
    {
      $referenceObj = JoomHelper::getRecord('category', $referenceId);

      if(empty($referenceObj->path))
      {
        $this->_new_location_path = '{alias}';
      }
      else
      {
        $this->_new_location_path = $referenceObj->path.'/{alias}';
      }      
    }
  }

  /**
   * Delete a record by id
   *
   * @param  mixed   $pk  Primary key value to delete. Optional
   * @param  boolean  $children  True to delete child nodes, false to move them up a level.
   * @return bool
   */
  public function delete($pk = null, $children = true)
  {
    $result = parent::delete($pk, $children);

    return $result;
  }

  /**
   * Add the root node to an empty table.
   *
   * @return    mixed  The id of the new root node or false on error.
   */
  public function addRoot()
  {
    $db = $this->getDatabase();

    $checkQuery = $db->getQuery(true);
    $checkQuery->select('*');
    $checkQuery->from(_JOOM_TABLE_CATEGORIES);
    $checkQuery->where('level = 0');

    $db->setQuery($checkQuery);

    // Add root category
    if(empty($db->loadAssoc()))
    {
      $query = $db->getQuery(true)
      ->insert(_JOOM_TABLE_CATEGORIES)
      ->set('parent_id = 0')
      ->set('lft = 0')
      ->set('rgt = 1')
      ->set('level = 0')
      ->set('path = ' . $db->quote(''))
      ->set('title = ' . $db->quote('Root'))
      ->set('alias = ' . $db->quote('root'))
      ->set('description = ' . $db->quote(''))
      ->set('access = 1')
      ->set('published = 1')
      ->set('params = ' . $db->quote(''))
      ->set('language = ' . $db->quote('*'))
      ->set('metadesc = ' . $db->quote(''))
      ->set('metakey = ' . $db->quote(''));
      
      $db->setQuery($query);

      if(!$db->execute())
      {
        Factory::getApplication()->enqueueMessage(Text::_('Error create root category'), 'error');
        $this->getComponent()->addLog(Text::_('Error create root category'), 'error', 'jerror');

        return false;
      }      
      $root_catid = $db->insertid();

      // Get parent id for asset
      $old_parentID = $this->parent_id;
      $this->parent_id = 0;
      $parentId = $this->_getAssetParentId();
      $this->parent_id = $old_parentID;
      
      // Get asset name
      $name = $this->typeAlias . '.1';

      // Create asset for root category
      $assetTable = new Asset($this->getDatabase());
      $assetTable->loadByName($name);

      if($assetTable->getError())
      {
        Factory::getApplication()->enqueueMessage(Text::_('Error load asset for root category creation'), 'error');
        $this->getComponent()->addLog(Text::_('Error load asset for root category creation'), 'error', 'jerror');

        return false;
      }
      else
      {
        // Specify how a new or moved node asset is inserted into the tree.
        if(empty($assetTable->id) || $assetTable->parent_id != $parentId)
        {
          $assetTable->setLocation($parentId, 'last-child');
        }

        // Prepare the asset to be stored.
        $assetTable->parent_id = $parentId;
        $assetTable->name      = $name;
        $assetTable->title     = 'Root';
        $assetTable->rules     = '{}';

        if(!$assetTable->check() || !$assetTable->store(false))
        {
          Factory::getApplication()->enqueueMessage(Text::_('Error create asset for root category'), 'error');
          $this->getComponent()->addLog(Text::_('Error create asset for root category'), 'error', 'jerror');

          return false;
        }
      }

      // Connect root category with asset table
      $query = $db->getQuery(true);
      $query->update($db->quoteName(_JOOM_TABLE_CATEGORIES))->set($db->quoteName('asset_id') . ' = ' . $assetTable->id)->where($db->quoteName('id') . ' = ' . $root_catid);
      $db->setQuery($query);

      if(!$db->execute())
      {
        Factory::getApplication()->enqueueMessage(Text::_('Error connect root category with asset'), 'error');
        $this->getComponent()->addLog(Text::_('Error connect root category with asset'), 'error', 'jerror');

        return false;
      }

      return $root_catid;
    }

    return true;
  }

  /**
   * Get root node id
   *
   * @return    int  The id of the root node.
   */
  public function getRootId()
  {
    $rootId = parent::getRootId();

    // If root is not set then create it.
    if($rootId === false)
    {
      $rootId = $this->addRoot();
    }

    return $rootId;
  }

  /**
   * Get a node tree based on current category (children, parents, complete)
   *
   * @param    string   $type        Which kind of nde tree (default: cpl)
   * @param    bool     $self        Include current node id (default: false)
   * @param    bool     $root        Include root node (default: false)
   * 
   * @return   array  List tree node node ids ordered by level ascending.
   * @throws  \UnexpectedValueException
   */
  public function getNodeTree($type = 'cpl', $self = false, $root = false)
  {
    // Check if object is loaded
    if(!$this->id)
    {
      $this->getComponent()->addLog(Text::_('Table not loaded. Load table first.'), 'error', 'jerror');
      throw new \UnexpectedValueException('Table not loaded. Load table first.');
    }

    // Convert type
    switch($type)
    {
      case 'parents':
        $type = 'parents';
        break;

      case 'childs':
      case 'children':
        $type = 'children';
        break;
      
      default:
        $type = 'cpl';
        break;
    }

    // Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

    // Select the required fields from the table.
		$query->select(array('id', 'level', 'alias', 'title'));
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));

    if($type === 'children')
    {
      // Select children
      if($self)
      {
        $query->where($db->quoteName('lft') . ' >= ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' <= ' . $this->rgt);
      }
      else
      {
        $query->where($db->quoteName('lft') . ' > ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' < ' . $this->rgt);
      }
    }
    elseif($type === 'parents')
    {
      // Select parents
      if($self)
      {
        $query->where($db->quoteName('lft') . ' <= ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' >= ' . $this->rgt);
      }
      else
      {
        $query->where($db->quoteName('lft') . ' < ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' > ' . $this->rgt);
      }
    }
    else
    {
      // children and itself
      $cWhere = '(' . $db->quoteName('lft') . ' >= ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' <= ' . $this->rgt . ')';
      // parents
      $pWhere = '(' . $db->quoteName('lft') . ' < ' . $this->lft . ' AND ' . $db->quoteName('rgt') . ' > ' . $this->rgt . ')';

      $query->where('(' . implode(' OR ', array($cWhere, $pWhere)) . ')');
    }

    // Exclude root category
    if(!$root)
    {
      $query->where($db->quoteName('level') . ' > 0');
    }
    
    // Apply ordering
    $query->order($db->quoteName('level') . ' ASC');

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    if(!$tree = $db->loadAssocList())
    {
      if($type === 'children')
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_CHILDREN_FOUND'));
        $this->getComponent()->addLog(Text::_('COM_JOOMGALLERY_ERROR_NO_CHILDREN_FOUND'), 'warning', 'jerror');
      }
      elseif($type === 'parents')
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_PARENT_FOUND'));
        $this->getComponent()->addLog(Text::_('COM_JOOMGALLERY_ERROR_NO_PARENT_FOUND'), 'error', 'jerror');
      }
      else
      {
        $this->setError(Text::_('COM_JOOMGALLERY_ERROR_GETNODETREE'));
        $this->getComponent()->addLog(Text::_('COM_JOOMGALLERY_ERROR_GETNODETREE'), 'error', 'jerror');
      }
    }

    return $tree;
  }

  /**
   * Get a list of (direct) siblings (left, right, both)
   *
   * @param    string   $type    Left or right siblings (default: both)
   * @param    bool     $direct  Only direct siblings (default: true)
   * @param    object   $parent  Parent category (only needed if direct=false)
   * 
   * @return   array  List of siblings.
   * @throws  \UnexpectedValueException
   */
  public function getSibling($type = 'both', $direct = true, $parent = null)
  {
    // Check if object is loaded
    if(!$this->id)
    {
      $this->getComponent()->addLog(Text::_('Table not loaded. Load table first.'), 'error', 'jerror');
      throw new \UnexpectedValueException('Table not loaded. Load table first.');
    }

    // Convert type
    switch($type)
    {
      case 'left':
      case 'lft':
      case 'l':
        $type = 'left';
        break;

      case 'right':
      case 'rgt':
      case 'r':
        $type = 'right';
        break;
      
      default:
        $type = 'both';
        break;
    }

    // Check if parent object is loaded
    if(!$direct && !$parent->id)
    {
      $this->getComponent()->addLog(Text::_('Parent table not loaded. Load parent table first.'), 'error', 'jerror');
      throw new \UnexpectedValueException('Parent table not loaded. Load parent table first.');
    }

    // Create a new query object.
		$db    = $this->getDatabase();
		$query = $db->getQuery(true);

    // Select the required fields from the table.
		$query->select(array('id', 'title', 'lft', 'rgt'));
    $query->from($db->quoteName(_JOOM_TABLE_CATEGORIES));

    if($type === 'left')
    {
      // Select left siblings
      if($direct)
      {
        $query->where($db->quoteName('rgt') . ' = ' . strval($this->lft - 1));
      }
      else
      {
        $query->where($db->quoteName('lft') . ' > ' . strval($parent->lft) . ' AND ' . $db->quoteName('rgt') . ' < ' . strval($this->lft));
      }
    }
    elseif($type === 'right')
    {
      // Select right siblings
      if($direct)
      {
        $query->where($db->quoteName('lft') . ' = ' . strval($this->rgt + 1));
      }
      else
      {
        $query->where($db->quoteName('lft') . ' > ' . strval($this->rgt) . ' AND ' . $db->quoteName('rgt') . ' < ' . strval($parent->rgt));
      }
    }
    else
    {
      // Select all siblings
      if($direct)
      {
        $query->where($db->quoteName('rgt') . ' = ' . strval($this->lft - 1). ' OR ' . $db->quoteName('lft') . ' = ' . strval($this->rgt + 1));
      }
      else
      {
        $query->where($db->quoteName('id') . ' != ' . $this->id);
      }
    }

    //Apply level
    $query->where($db->quoteName('level') . ' = ' . $this->level);

    // Apply ordering
    if(!$direct)
    {
      $query->order($db->quoteName('lft') . ' ASC');
    }

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    $siblings = $db->loadAssocList();

    if(!$siblings)
    {
      $this->setError(Text::_('COM_JOOMGALLERY_ERROR_NO_SIBLING_FOUND'));
      $this->getComponent()->addLog(Text::_('COM_JOOMGALLERY_ERROR_NO_SIBLING_FOUND'), 'error', 'jerror');
    }

    // Loop through the sibling and add the position (left or right)
    foreach($siblings as $key => $sibling)
    {
      if($sibling['rgt'] < $this->lft)
      {
        // left sibling
        $siblings[$key]['side'] = 'left';
      }
      elseif($sibling['lft'] > $this->rgt)
      {
        // right sibling
        $siblings[$key]['side'] = 'right';
      }
      else
      {
        $this->getComponent()->addLog(Text::_('Unexpected sibling received.'), 'error', 'jerror');
        throw new \UnexpectedValueException('Unexpected sibling received.');
      }

      unset($siblings[$key]['lft']);
      unset($siblings[$key]['rgt']);
    }

    return $siblings;
  }

  /**
   * Get an array of path segments (needed for routing)
   * 
   * @param   bool     $root        True to include root node (default: false)
   * @param   string   $prop_name   The property name
   * 
   * @return   array  List of path slugs (slug = id:alias).
   * @throws  \UnexpectedValueException
   */
  public function getRoutePath($root = false, $prop_name = 'route_path')
  {
    // Check if object is loaded
    if(!$this->id)
    {
      throw new \UnexpectedValueException('Table not loaded. Load table first.');
    }

    if(!isset($this->{$prop_name}))
    {
      $parents = \array_reverse($this->getNodeTree('parents', true, $root));

      $this->{$prop_name} = array();
      foreach ($parents as $key => $node)
      {
        $this->{$prop_name}[$node['id']] = $node['id'] . ':' . $node['alias'];
      }
    }

    return $this->{$prop_name};
  }
}
