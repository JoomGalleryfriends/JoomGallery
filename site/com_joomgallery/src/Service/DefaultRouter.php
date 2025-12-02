<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Service;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Menu\AbstractMenu;
use \Joomla\Database\ParameterType;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Application\SiteApplication;
use \Joomla\CMS\Categories\CategoryFactoryInterface;
use \Joomla\CMS\Component\Router\RouterView;
use \Joomla\CMS\Component\Router\RouterViewConfiguration;
use \Joomla\CMS\Component\Router\Rules\MenuRules;
use \Joomla\CMS\Component\Router\Rules\NomenuRules;
use \Joomla\CMS\Component\Router\Rules\StandardRules;
use \Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;

/**
 * Joomgallery Router class
 *
 */
class DefaultRouter extends RouterView
{
  /**
	 * Name to be displayed
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	public static $displayName = 'COM_JOOMGALLERY_DEFAULT_ROUTER';

  /**
	 * Type of the router
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	public static $type = 'modern';

  /**
	 * ID of the parent of the image view. Empty if none.
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	public static $image_parentID = '';

  /**
	 * Param to use ids in URLs
	 *
	 * @var    bool
	 *
	 * @since  4.0.0
	 */
	private $noIDs;

  /**
	 * Database object
	 *
	 * @var    DatabaseInterface
	 *
	 * @since  4.0.0
	 */
	private $db;

  /**
	 * The category cache
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	private $categoryCache = [];

	public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db , $skipSelf = false)
	{
    parent::__construct($app, $menu);

    // Get router config value
    $this->noIDs = (bool) $app->bootComponent('com_joomgallery')->getConfig()->get('jg_router_ids', '0');
    $this->db    = $db;

    if($skipSelf)
    {
      return;
    }

    $gallery = new RouterViewConfiguration('gallery');
    $this->registerView($gallery);

    $categories = new RouterViewConfiguration('categories');
    $this->registerView($categories);

    $category = new RouterViewConfiguration('category');
    $category->setKey('id')->setNestable()->setParent($gallery);
    $this->registerView($category);

    $categoryform = new RouterViewConfiguration('categoryform');
    $categoryform->setKey('id');
    $this->registerView($categoryform);

		$images = new RouterViewConfiguration('images');
    $images->setParent($gallery);
		$this->registerView($images);

    $image = new RouterViewConfiguration('image');
    $image->setKey('id')->setParent($images);
    $this->registerView($image);

    $imageform = new RouterViewConfiguration('imageform');
    $imageform->setKey('id');
    $this->registerView($imageform);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}

  /**
   * Method to get the segment for a gallery view
   *
   * @param   string  $id     ID of the image to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getGallerySegment($id, $query)
  {
    return array('');
  }
	
  /**
   * Method to get the segment for an image view
   *
   * @param   string  $id     ID of the image to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getImageSegment($id, $query)
  {
    if(!\strpos($id, ':'))
    {
      $dbquery = $this->db->getQuery(true);

      $dbquery->select($this->db->quoteName('alias'))
        ->from($this->db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($this->db->quoteName('id') . ' = :id')
        ->bind(':id', $id, ParameterType::INTEGER);
      $this->db->setQuery($dbquery);

      // To create a segment in the form: id-alias
      $id .= ':' . $this->db->loadResult();
    }

    return array((int) $id => $id);
  }

  /**
   * Method to get the segment(s) for an imageform
   *
   * @param   string  $id     ID of the imageform to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getImageformSegment($id, $query)
  {
    if(!$id)
    {
      // Load empty form view
      return array('');
    }

    return $this->getImageSegment($id, $query);
  }

  /**
   * Method to get the segment(s) for an image
   *
   * @param   string  $id     ID of the image to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getImagesSegment($id, $query)
  {
    if(!$id)
    {
      return array('');
    }

    return $this->getImageSegment($id, $query);
  }

  /**
   * Method to get the segment(s) for an category
   *
   * @param   string  $id     ID of the category to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *                          array(id = id:alias, parentid: parentid:parentalias)
   *
   * @return  array|string  The segments of this item
   */
  public function getCategorySegment($id, $query)
  {
    $category = $this->getCategory((int) $id, 'route_path', true);

    if($category)
    {
      // Replace root with categories
      if($root_key = \key(\preg_grep('/\broot\b/i', $category->route_path)))
      {
        $category->route_path[$root_key] = \str_replace('root', 'categories', $category->route_path[$root_key]);
      }

      if($this->noIDs && \strpos(\reset($category->route_path), ':') !== false)
      {
        foreach($category->route_path as &$segment)
        {
          list($id, $segment) = \explode(':', $segment, 2);
        }
      }

      return $category->route_path;
    }

    return array();
  }

  /**
   * Method to get the segment(s) for an categoryform
   *
   * @param   string  $id     ID of the categoryform to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getCategoryformSegment($id, $query)
  {
    if(!$id)
    {
      // Load empty form view
      return array('');
    }

    return $this->getCategorySegment($id, $query);
  }

  /**
   * Method to get the segment(s) for a category
   *
   * @param   string  $id     ID of the category to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getCategoriesSegment($id, $query)
  {
    if(!$id)
    {
      return array('');
    }

    return $this->getCategorySegment($id, $query);
  }

  /**
   * Method to get the segment for a gallery view
   *
   * @param   string  $segment  Segment of the image to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getGalleryId($segment, $query)
  {
    return 0;
  }
	
  /**
   * Method to get the segment for an image view
   *
   * @param   string  $segment  Segment of the image to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getImageId($segment, $query)
  {
    if($segment == '0-' || $segment == 'noimage' || $segment == '0-noimage')
    {
      // Special case: No image with id=0
      return 'null';
    }

    $img_id = 0;
    if(\is_numeric(\explode('-', $segment, 2)[0]))
    {
      // For a segment in the form: id-alias
      $img_id = (int) \explode('-', $segment, 2)[0];
    }

    if($img_id < 1)
    {
      $dbquery = $this->db->getQuery(true);

      $dbquery->select($this->db->quoteName('id'))
        ->from($this->db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($this->db->quoteName('alias') . ' = :alias')
        ->bind(':alias', $segment);

      if($cat = $this->app->input->get('catid', 0, 'int'))
      {
        // We can identify the image via a request query variable of type catid
        $dbquery->where($this->db->quoteName('catid') . ' = :catid');
        $dbquery->bind(':catid', $cat, ParameterType::INTEGER);
      }

      if(\key_exists('view', $query) && $query['view'] == 'category' && \key_exists('id', $query))
      {
        // We can identify the image via menu item of type category
        $dbquery->where($this->db->quoteName('catid') . ' = :catid');
        $dbquery->bind(':catid', $query['id'], ParameterType::INTEGER);
      }

      $this->db->setQuery($dbquery);

      return (int) $this->db->loadResult();
    }

    return $img_id;
  }

  /**
   * Method to get the segment(s) for an imageform
   *
   * @param   string  $segment  Segment of the imageform to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getImageformId($segment, $query)
  {
    return $this->getImageId($segment, $query);
  }

  /**
   * Method to get the segment(s) for an image
   *
   * @param   string  $segment  Segment of the image to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getImagesId($segment, $query)
  {
    return $this->getImageId($segment, $query);
  }

  /**
   * Method to get the segment(s) for an category
   *
   * @param   string  $segment  Segment of the category to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getCategoryId($segment, $query)
  {
    if(isset($query['id']) && ($query['id'] === 0 || $query['id'] === '0'))
    {
      // Root element of nestable content in core must have the id=0
      // But JoomGallery category root has id=1
      $query['id'] = 1;
    }

    if(\strpos($segment, 'categories'))
    {
      // If 'categories' is in the segment, means that we are looking for the root category
      $segment = \str_replace('categories', 'root', $segment);
    }

    if(isset($query['id']))
    {
      $category = $this->getCategory((int) $query['id'], 'children', true);

      if($category)
      {
        foreach($category->children as $child)
        {
          if($this->noIDs)
          {
            if($child['alias'] == $segment)
            {
              return $child['id'];
            }
          }
          else
          {
            if($child['id'] == (int) $segment)
            {
              return $child['id'];
            }
          }
        }
      }  
    }

    return false;
  }

  /**
   * Method to get the segment(s) for an categoryform
   *
   * @param   string  $segment  Segment of the categoryform to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getCategoryformId($segment, $query)
  {
    return $this->getCategoryId($segment, $query);
  }

  /**
   * Method to get the segment(s) for an category
   *
   * @param   string  $segment  Segment of the category to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getCategoriesId($segment, $query)
  {
    return $this->getCategoryId($segment, $query);
  }

	/**
	 * Method to get categories from cache
	 *
	 * @param   int             $id         It of the category
   * @param   string          $available  The property to make available in the category
	 *
	 * @return  CategoryTable   The category table object
	 *
	 * @since   4.0.0
   * @throws  \UnexpectedValueException
	 */
	private function getCategory($id, $available = null, $root = true): CategoryTable
	{
    // Load the category table
		if(!isset($this->categoryCache[$id]))
		{
      $table = $this->app->bootComponent('com_joomgallery')->getMVCFactory()->createTable('Category', 'administrator');
      $table->load($id);
      $this->categoryCache[$id] = $table;
		}

    // Make node tree available in cache
    if(!\is_null($available) && !isset($this->categoryCache[$id]->{$available}))
    {
      switch ($available) {
        case 'route_path':
          $this->categoryCache[$id]->{$available} = $this->categoryCache[$id]->getRoutePath($root, 'route_path');
          break;
        
        case 'children':
          $this->categoryCache[$id]->{$available} = $this->categoryCache[$id]->getNodeTree('children', true, $root);
          break;

        case 'parents':
          $this->categoryCache[$id]->{$available} = $this->categoryCache[$id]->getNodeTree('children', true, $root);
          break;
        
        default:
          throw new \UnexpectedValueException('Requested property ('.$available.') can to be made available in a category.');
          break;
      }
    }

		return $this->categoryCache[$id];
	}
}
