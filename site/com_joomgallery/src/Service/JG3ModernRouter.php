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
defined('_JEXEC') or die;

//use Joomgallery\Component\Joomgallery\Site\Service\DefaultRouter;
use \Joomla\CMS\Menu\AbstractMenu;
use \Joomla\Database\ParameterType;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Application\SiteApplication;
use \Joomla\CMS\Categories\CategoryFactoryInterface;
use \Joomla\CMS\Component\Router\Rules\MenuRules;
use \Joomla\CMS\Component\Router\Rules\NomenuRules;
use \Joomla\CMS\Component\Router\Rules\StandardRules;
use \Joomla\CMS\Component\Router\RouterViewConfiguration;

/**
 * Joomgallery Router class (JG3 flavor)
 *
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
	public static $displayName = 'COM_JOOMGALLERY_JG3_ROUTER';

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
	public static $image_parentID = 'catid';

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

	public function __construct(SiteApplication $app, AbstractMenu $menu, ?CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
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

    $imageform = new RouterViewConfiguration('imageform');
    $imageform->setKey('id')->setParent($gallery);
    $this->registerView($imageform);

    $userpanel = new RouterViewConfiguration('userpanel');
    $this->registerView($userpanel);

    $userupload = new RouterViewConfiguration('userupload');
    //$userupload->setKey('id');
    $this->registerView($userupload);

    $usercategories = new RouterViewConfiguration('usercategories');
    $this->registerView($usercategories);

    $usercategory = new RouterViewConfiguration('usercategory');
    $usercategory->setKey('id');
    $this->registerView($usercategory);

    $userimages = new RouterViewConfiguration('userimages');
//    $userimages->setParent($usercategory);
    $this->registerView($userimages);

    $userimage = new RouterViewConfiguration('userimage');
    $userimage->setKey('id');
    $this->registerView($userimage);

    parent::__construct($app, $menu, $categoryFactory, $db, true);

    $this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
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

      // To create a segment in the form: alias-id
      $id = $this->db->loadResult() . ':' . $id;
    }

    return array((int) $id => $id);
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
    $img_id = 0;
    $parts = explode('-', $segment);
    if(\is_numeric(\end($parts)))
    {
      // For a segment in the form: alias-id
      $img_id = (int) \end($parts);
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
}
