<?php

/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Service;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Factory;
use Joomla\CMS\Categories\Categories;
use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Categories\CategoryFactoryInterface;
use Joomla\CMS\Categories\CategoryInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Class JoomgalleryRouter
 *
 */
class Router extends RouterView
{
	private $noIDs;

	/**
	 * The category factory
	 *
	 * @var    CategoryFactoryInterface
	 *
	 * @since  4.0.0
	 */
	private $categoryFactory;

	/**
	 * The category cache
	 *
	 * @var    array
	 *
	 * @since  4.0.0
	 */
	private $categoryCache = [];

	public function __construct(SiteApplication $app, AbstractMenu $menu, CategoryFactoryInterface $categoryFactory, DatabaseInterface $db)
	{
		$params = Factory::getApplication()->getParams('com_joomgallery');

		$this->noIDs = (bool) $params->get('sef_ids');
		$this->categoryFactory = $categoryFactory;
		
		$images = new RouterViewConfiguration('images');
		$this->registerView($images);

    $image = new RouterViewConfiguration('image');
    $image->setKey('id')->setParent($images);
    $this->registerView($image);

    $imageform = new RouterViewConfiguration('imageform');
    $imageform->setKey('id');

    $this->registerView($imageform);$categories = new RouterViewConfiguration('categories');
		$this->registerView($categories);

    $category = new RouterViewConfiguration('category');
    $category->setKey('id')->setParent($categories);
    $this->registerView($category);

    $categoryform = new RouterViewConfiguration('categoryform');
    $categoryform->setKey('id');
    $this->registerView($categoryform);

		parent::__construct($app, $menu);

		$this->attachRule(new MenuRules($this));
		$this->attachRule(new StandardRules($this));
		$this->attachRule(new NomenuRules($this));
	}
	
  /**
   * Method to get the segment(s) for an image
   *
   * @param   string  $id     ID of the image to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getImageSegment($id, $query)
  {
    if(!strpos($id, ':'))
    {
      $db = Factory::getDbo();
      $dbquery = $db->getQuery(true);
      $dbquery->select($dbquery->qn('alias'))
        ->from($dbquery->qn('#__joomgallery'))
        ->where('id = ' . $dbquery->q($id));
      $db->setQuery($dbquery);

      $id .= ':' . $db->loadResult();
    }

    if($this->noIDs)
    {
      list($void, $segment) = explode(':', $id, 2);

      return array($void => $segment);
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
    return $this->getImageSegment($id, $query);
  }

  /**
   * Method to get the segment(s) for an category
   *
   * @param   string  $id     ID of the category to retrieve the segments for
   * @param   array   $query  The request that is built right now
   *
   * @return  array|string  The segments of this item
   */
  public function getCategorySegment($id, $query)
  {
    if(!strpos($id, ':'))
    {
      $db = Factory::getDbo();
      $dbquery = $db->getQuery(true);
      $dbquery->select($dbquery->qn('alias'))
        ->from($dbquery->qn('#__joomgallery_categories'))
        ->where('id = ' . $dbquery->q($id));
      $db->setQuery($dbquery);

      $id .= ':' . $db->loadResult();
    }

    if($this->noIDs)
    {
      list($void, $segment) = explode(':', $id, 2);

      return array($void => $segment);
    }

    return array((int) $id => $id);
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
    return $this->getCategorySegment($id, $query);
  }

	
  /**
   * Method to get the segment(s) for an image
   *
   * @param   string  $segment  Segment of the image to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getImageId($segment, $query)
  {
    if($this->noIDs)
    {
      $db = Factory::getDbo();
      $dbquery = $db->getQuery(true);
      $dbquery->select($dbquery->qn('id'))
        ->from($dbquery->qn('#__joomgallery'))
        ->where('alias = ' . $dbquery->q($segment));
      $db->setQuery($dbquery);

      return (int) $db->loadResult();
    }

    return (int) $segment;
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
   * Method to get the segment(s) for an category
   *
   * @param   string  $segment  Segment of the category to retrieve the ID for
   * @param   array   $query    The request that is parsed right now
   *
   * @return  mixed   The id of this item or false
   */
  public function getCategoryId($segment, $query)
  {
    if($this->noIDs)
    {
      $db = Factory::getDbo();
      $dbquery = $db->getQuery(true);
      $dbquery->select($dbquery->qn('id'))
        ->from($dbquery->qn('#__joomgallery_categories'))
        ->where('alias = ' . $dbquery->q($segment));
      $db->setQuery($dbquery);

      return (int) $db->loadResult();
    }
    
    return (int) $segment;
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
	 * Method to get categories from cache
	 *
	 * @param   array  $options   The options for retrieving categories
	 *
	 * @return  CategoryInterface  The object containing categories
	 *
	 * @since   4.0.0
	 */
	private function getCategories(array $options = []): CategoryInterface
	{
		$key = serialize($options);

		if(!isset($this->categoryCache[$key]))
		{
			$this->categoryCache[$key] = $this->categoryFactory->createCategory($options);
		}

		return $this->categoryCache[$key];
	}
}
