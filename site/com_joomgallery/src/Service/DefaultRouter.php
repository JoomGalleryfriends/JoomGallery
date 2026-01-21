<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Service;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Joomgallery Router class
 *
 * @package     Joomgallery\Component\Joomgallery\Site\Service
 *
 * @since  4.0.0
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
  public static string $displayName = 'COM_JOOMGALLERY_DEFAULT_ROUTER';

  /**
   * Type of the router
   *
   * @var    string
   *
   * @since  4.0.0
   */
  public static string $type = 'modern';

  /**
   * ID of the parent of the image view. Empty if none.
   *
   * @var    string
   *
   * @since  4.0.0
   */
  public static string $image_parentID = '';

  /**
   * Param to use ids in URLs
   *
   * @var    bool
   *
   * @since  4.0.0
   */
  private bool $noIDs;

  /**
   * Param to use image ids in URLs
   *
   * @var    bool
   *
   * @since  4.3.0
   */
  private bool $noIMG_IDs;

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
  private array $categoryCache = [];

  // ToDo: Fith/Manuel: Get...Id should return 'int|bool' see com_content router. Why do we have array ?
  // ToDo: Fith/Manuel: Get...Segment should return 'array|string' see com_content router. Why do we have array ?

  public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db, $skipSelf = false)
  {
    parent::__construct($app, $menu);

    // Get router config value
    $this->noIDs     = (bool) $app->bootComponent('com_joomgallery')->getConfig()->get('jg_router_ids', '0');
    $this->noIMG_IDs = (bool) $app->bootComponent('com_joomgallery')->getConfig()->get('jg_router_imgids', '0');
    $this->db        = $db;

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

    $images = new RouterViewConfiguration('images');
    $images->setParent($gallery);
    $this->registerView($images);

    $image = new RouterViewConfiguration('image');
    $image->setKey('id')->setParent($images);
    $this->registerView($image);

    $userpanel = new RouterViewConfiguration('userpanel');
    $this->registerView($userpanel);

    $usercategories = new RouterViewConfiguration('usercategories');
    $usercategories->setParent($userpanel);
    $this->registerView($usercategories);

    $usercategory = new RouterViewConfiguration('usercategory');
    $usercategory->setKey('id')->setNestable()->setParent($usercategories);
    $this->registerView($usercategory);

    $userimages = new RouterViewConfiguration('userimages');
    $userimages->setParent($userpanel);
    $this->registerView($userimages);

    $userimage = new RouterViewConfiguration('userimage');
    $userimage->setKey('id')->setParent($userimages);
    $this->registerView($userimage);

    $userupload = new RouterViewConfiguration('userupload');
    $userupload->setParent($userpanel);
    $this->registerView($userupload);

    $this->attachRule(new MenuRules($this));
    $this->attachRule(new StandardRules($this));
    $this->attachRule(new NomenuRules($this));
  }

  /**
   * Preprocess a URL
   *
   * @param   array  $query  An associative array of URL arguments
   * @return  array  The URL arguments to use to assemble the subsequent URL.
   *
   * @since   4.3.0
   */
  public function preprocess($query)
  {
    // Check for a controller.task command.
    if(isset($query['task']) && str_contains($query['task'], '.'))
    {
      [$view, $task] = explode('.', $query['task']);

      if(!isset($query['view']))
      {
        $query['view'] = $view;
      }
    }

    // Check for raw image view
    if( (isset($query['view']) && $query['view'] == 'image') &&
        (isset($query['format']) && \in_array($query['format'], JoomHelper::$image_types))
      )
    {
      // We are processing a raw image. Lets make sure the Itemid is correct
      if(isset($query['Itemid']))
      {
        // Lets check the curretly selected menuitem if any
        $menuitem = Factory::getApplication()->getMenu()->getItem();
      }
      else
      {
        // Lets check the active menuitem otherwise
        $menuitem = Factory::getApplication()->getMenu()->getActive();
      }

      if(isset($menuitem->query['view']) &&
        \in_array($menuitem->query['view'], ['image', 'images'], true)
        )
      {
        // Set the Itemid if it has the correct type
        $query['Itemid'] = $menuitem->id;
      }
      else
      {
        // Fetch a menuitem id of the correct type
        $query['Itemid'] = JoomHelper::getMenuItem('images');
      }
    }

    return parent::preprocess($query);
  }

  /**
   * Method to get the segment for a gallery view
   *
   * @param   string   $id     ID of the image to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   * @since   4.2.0
   */
  public function getGallerySegment(string $id, array $query): array|string
  {
    return [''];
  }

  /**
   * Method to get the segment for an image view
   *
   * @param   string   $id     ID of the image to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.2.0
   */
  public function getImageSegment(string $id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      if(!$id)
      {
        if($query['view'] = 'image' && $query['format'] = 'raw')
        {
          // Load the no-image
          if($this->noIMG_IDs)
          {
            return [0 => 'noimage'];
          }

          return [0 => '0:noimage'];
        }
        elseif($query['view'] = 'userimage')
        {
          // Load empty userimage form view
          return [''];
        }
      }

      $id .= ':' . $this->getImageAliasDb($id);
    }

    if($this->noIMG_IDs)
    {
      list($void, $segment) = explode(':', $id, 2);

      return [$void => $segment];
    }

    return [(int) $id => $id];
  }

  /**
   * Method to get the segment(s) for an userimage
   *
   * @param   string   $id     ID of the category to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.3.0
   */
  public function getUserimageSegment($id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      $id .= ':' . $this->getImageAliasDb($id);
    }

    if($this->noIMG_IDs)
    {
      list($void, $segment) = explode(':', $id, 2);

      return [$void => $segment];
    }

    return [(int) $id => $id];
  }

  /**
   * Method to get the segment(s) for an image
   *
   * @param   string   $id     ID of the image to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.2.0
   */
  public function getImagesSegment($id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      if(!$id)
      {
        return [''];
      }

      return $this->getImageSegment($id, $query);
    }

    if($this->noIMG_IDs)
    {
      list($void, $segment) = explode(':', $id, 2);

      return [$void => $segment];
    }

    return [(int) $id => $id];
  }

  /**
   * Method to get the segment(s) for a userimage
   *
   * @param   string   $id     ID of the image to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.3.0
   */
  public function getUserimagesSegment($id, $query): array|string
  {
    return $this->getImagesSegment($id, $query);
  }

  /**
   * Method to get the segment(s) for a category
   *
   * @param   string   $id     ID of the category to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *                           array(id = id:alias, parentid: parentid:parentalias)
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.2.0
   */
  public function getCategorySegment($id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      $category = $this->getCategory((int) $id, 'route_path', true);

      if($category)
      {
        // Replace root with categories
        if($root_key = key(preg_grep('/\broot\b/i', $category->route_path)))
        {
          $category->route_path[$root_key] = str_replace('root', 'categories', $category->route_path[$root_key]);
        }

        if($this->noIDs && strpos(reset($category->route_path), ':') !== false)
        {
          foreach($category->route_path as &$segment)
          {
            list($id, $segment) = explode(':', $segment, 2);
          }
        }

        return $category->route_path;
      }
    }

    if($this->noIDs)
    {
      list($void, $segment) = explode(':', $id, 2);

      return [$void => $segment];
    }

    return [];
  }

  /**
   * Method to get the segment(s) for an usercategory
   *
   * @param   string   $id     ID of the category to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.2.0
   */
  public function getUsercategorySegment($id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      if(!$id || $id == 'null')
      {
        if($query['view'] = 'usercategory' && $query['layout'] = 'editCat')
        {
          // Load empty form view
          if($this->noIDs)
          {
            return [0 => 'newcat', 1 => 'categories'];
          }

          return [0 => '0:newcat', 1 => '1:categories'];
        }
      }
    }

    return $this->getCategorySegment($id, $query);
  }

  /**
   * Method to get the segment(s) for a category
   *
   * @param   string   $id     ID of the category to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.2.0
   */
  public function getCategoriesSegment($id, $query): array|string
  {
    if(!$id)
    {
      return [''];
    }

    return $this->getCategorySegment($id, $query);
  }

  /**
   * Method to get the segment(s) for a usercategory
   *
   * @param   string   $id     ID of the category to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since   4.3.0
   */
  public function getUsercategoriesSegment($id, $query): array|string
  {
    return $this->getCategoriesSegment($id, $query);
  }

  /**
   * Method to get the segment for a gallery view
   *
   * @param   string   $segment  Segment of the image to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getGalleryId($segment, $query)
  {
    return (int) $segment;
  }

  /**
   * Method to get the segment for an image view
   *
   * @param   string   $segment  Segment of the image to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getImageId($segment, $query)
  {
    if($segment == '0-' || $segment == 'noimage' || $segment == '0-noimage')
    {
      // Special case: No image with id=0
      return 'null';
    }

    $img_id = $this->getImageIdDb($segment, $query);

    return (int) $img_id;
  }

  /**
   * Method to get the segment(s) for an userimage
   *
   * @param   string   $segment  Segment of the userimage to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  int|false   The id of this item or false
   *
   * @since   4.2.0
   */
  public function getUserimageId($segment, $query): int|false
  {
    return $this->getImageId($segment, $query);
  }

  /**
   * Method to get the segment(s) for an image
   *
   * @param   string   $segment  Segment of the image to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getImagesId($segment, $query)
  {
    return $this->getImageId($segment, $query);
  }

  /**
   * Method to get the segment(s) for an image
   *
   * @param   string   $segment  Segment of the image to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getUserimagesId($segment, $query)
  {
    $this->getImagesId($segment, $query);
  }

  /**
   * Method to get the segment(s) for a category
   *
   * @param   string   $segment  Segment of the category to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getCategoryId($segment, $query)
  {
    if($segment == '0-' || $segment == 'newcat' || $segment == '0-newcat')
    {
      // Special case: Empty category form view
      return 'null';
    }

    if(isset($query['id']) && ($query['id'] === 0 || $query['id'] === '0'))
    {
      // Root element of nestable content in core must have the id=0
      // But JoomGallery category root has id=1
      $query['id'] = 1;
    }

    if(strpos($segment, 'categories'))
    {
      // If 'categories' is in the segment, means that we are looking for the root category
      $segment = str_replace('categories', 'root', $segment);
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
   * Method to get the segment(s) for an usercategory
   *
   * @param   string   $segment  Segment of the usercategory to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getUsercategoryId($segment, $query)
  {
    return $this->getCategoryId($segment, $query);
  }

  /**
   * Method to get the segment(s) for a category
   *
   * @param   string   $segment  Segment of the category to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getCategoriesId($segment, $query)
  {
    return $this->getCategoryId($segment, $query);
  }

  /**
   * Method to get the segment(s) for a usercategory
   *
   * @param   string   $segment  Segment of the category to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or false
   *
   * @since   4.2.0
   */
  public function getUsercategoriesId($segment, $query)
  {
    $this->getCategoriesId($segment, $query);
  }

  /**
   * Method to get categories from cache
   *
   * @param   int      $id         It of the category
   * @param   string   $available  The property to make available in the category
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
      switch($available)
      {
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
            throw new \UnexpectedValueException('Requested property (' . $available . ') can to be made available in a category.');
          break;
      }
    }

    return $this->categoryCache[$id];
  }

  /**
   * Fetches alias of image by image ID
   * @param   string   $id image ID
   *
   * @return string alias
   *
   * @since   4.2.0
   */
  public function getImageAliasDb(string $id): string
  {
    $alias   = '';
    $dbquery = $this->db->createQuery();

    $dbquery->select($this->db->quoteName('alias'))
      ->from($this->db->quoteName(_JOOM_TABLE_IMAGES))
      ->where($this->db->quoteName('id') . ' = :id')
      ->bind(':id', $id, ParameterType::INTEGER);
    $this->db->setQuery($dbquery);

    // To create a segment in the form: id-alias
    $alias = (string) $this->db->loadResult();

    return $alias;
  }

  /**
   * if image id from segment 'xx-image-alias' is lower than '1' then
   * the id is taken from the database matching the alias. The query on
   * db regards category id from input or from function argument query
   * variable
   *
   * @param $segment
   * @param $query
   *
   * @return int|false
   *
   * @since  4.2
   */
  public function getImageIdDb($segment, $query): int|false
  {
    $img_id = 0;

    // ToDo: FiTh/Manuel where else do i need to distinguish with '-' ? documentation
    if(is_numeric(explode('-', $segment, 2)[0]))
    {
      // For a segment in the form: id-alias
      $img_id = (int) explode('-', $segment, 2)[0];
    }

    if($img_id < 1)
    {
      $dbquery = $this->db->createQuery();

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

      if(key_exists('view', $query) && $query['view'] == 'category' && key_exists('id', $query))
      {
        // We can identify the image via menu item of type category
        $dbquery->where($this->db->quoteName('catid') . ' = :catid');
        $dbquery->bind(':catid', $query['id'], ParameterType::INTEGER);
      }

      $this->db->setQuery($dbquery);

      $img_id = (int) $this->db->loadResult();
    }

    return $img_id;
  }
}
