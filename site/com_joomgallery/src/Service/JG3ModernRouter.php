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

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;

/**
 * Joomgallery Router class (JG3 flavor)
 *
 * @since   4.0.0
 */
class JG3ModernRouter extends DefaultRouter
{
  /**
   * Name to be displayed
   *
   * @var    string
   *
   * @since  4.0.0
   */
  public static string $displayName = 'COM_JOOMGALLERY_JG3_ROUTER';

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
  public static string $image_parentID = 'catid';

  /**
   * Param to use ids in URLs
   *
   * @var    bool
   *
   * @since  4.0.0
   */
  private bool $noIDs;

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

  public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
  {
    parent::__construct($app, $menu, $categoryFactory, $db, true);

    // Get router config value
    $this->noIDs = (bool) $app->bootComponent('com_joomgallery')->getConfig()->get('jg_router_ids', '0');
    $this->db    = $db;

    $gallery = new RouterViewConfiguration('gallery');
    $this->registerView($gallery);

    $categories = new RouterViewConfiguration('categories');
    $categories->setParent($gallery);
    $this->registerView($categories);

    $category = new RouterViewConfiguration('category');
    $category->setKey('id')->setNestable()->setParent($gallery);
    $this->registerView($category);

    $categoryform = new RouterViewConfiguration('categoryform');
    $categoryform->setKey('id')->setParent($gallery);
    $this->registerView($categoryform);

    $images = new RouterViewConfiguration('images');
    $images->setParent($gallery);
    $this->registerView($images);

    $image = new RouterViewConfiguration('image');
    $image->setKey('id')->setParent($category, 'catid');
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

    if( (isset($query['view']) && $query['view'] == 'image') &&
        (isset($query['format']) && \in_array($query['format'], JoomHelper::$image_types)) &&
        (isset($query['id']) && $query['id'] == 0)
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
        \in_array($menuitem->query['view'], ['image', 'categories'], true)
        )
      {
        // Set the Itemid if it has the correct type
        $query['Itemid'] = $menuitem->id;
      }
      else
      {
        // Fetch a menuitem id of the correct type
        $query['Itemid'] = JoomHelper::getMenuItem('categories');
      }
    }

    // Process the parsed variables based on custom defined rules
    foreach($this->rules as $rule)
    {
      $rule->preprocess($query);
    }

    return $query;
  }

  /**
   * Method to get the segment for an image view
   *
   * @param   string   $id     ID of the image to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since  4.0.0
   */
  public function getImageSegment($id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      if(!$id)
      {
        if($query['view'] = 'image' && $query['format'] = 'raw')
        {
          // Load the no-image
          if($this->noIDs)
          {
            return [0 => 'noimage'];
          }

          return [0 => 'noimage:0'];
        }
        elseif($query['view'] = 'userimage')
        {
          // Load empty userimage form view
          return [''];
        }
      }

      $dbquery = $this->db->createQuery();

      $dbquery->select($this->db->quoteName('alias'))
        ->from($this->db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($this->db->quoteName('id') . ' = :id')
        ->bind(':id', $id, ParameterType::INTEGER);
      $this->db->setQuery($dbquery);

      // To create a segment in the form: alias-id
      $id = $this->db->loadResult() . ':' . $id;
    }

    return [(int) $id => $id];
  }

  /**
   * Method to get the segment for an image view
   *
   * @param   string   $id     ID of the image to retrieve the segments for
   * @param   array    $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   *
   * @since  4.3.0
   */
  public function getUserimageSegment($id, $query): array|string
  {
    if(!strpos($id, ':'))
    {
      $dbquery = $this->db->createQuery();

      $dbquery->select($this->db->quoteName('alias'))
        ->from($this->db->quoteName(_JOOM_TABLE_IMAGES))
        ->where($this->db->quoteName('id') . ' = :id')
        ->bind(':id', $id, ParameterType::INTEGER);
      $this->db->setQuery($dbquery);

      // To create a segment in the form: alias-id
      $id = $this->db->loadResult() . ':' . $id;
    }

    return [(int) $id => $id];
  }

  /**
   * Method to get the segment for an image view
   *
   * @param   string   $segment  Segment of the image to retrieve the ID for
   * @param   array    $query    The request that is parsed right now
   *
   * @return  mixed    The id of this item or int 0
   * @since  4.0.0
   */
  public function getImageId($segment, $query)
  {
    if($segment == '-0' || $segment == 'noimage' || $segment == 'noimage-0')
    {
      // Special case: No image with id=0
      return 'null';
    }

    $img_id = 0;

    $parts = explode('-', $segment);

    if(is_numeric(end($parts)))
    {
      // For a segment in the form: alias-id
      $img_id = (int) end($parts);
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

      return (int) $this->db->loadResult();
    }

    return $img_id;
  }
}
