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

use \Joomla\CMS\Factory;
use \Joomla\Database\DatabaseInterface;
use \Joomla\CMS\Language\Multilanguage;
use \Joomla\CMS\Component\ComponentHelper;
use \Joomla\CMS\Application\ApplicationHelper;
use \Joomla\CMS\Component\Router\RouterInterface;

/**
 * Old Joomgallery v3 Router
 *
 * @see https://github.com/robbiejackson/Joomla-4-MVC-Tutorial/blob/main/joomla4-mvc/site/src/Service/TraditionalRouter.php
 */
class JG3LegacyRouter implements RouterInterface
{
  /**
	 * Name to be displayed
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	public static $displayName = 'JG3 Router';

  /**
	 * Type of the router
	 *
	 * @var    string
	 *
	 * @since  4.0.0
	 */
	public static $type = 'legacy';

	private $application;
    
  private $menu;

  private $db;
  
  private $categories;

  public function __construct($app, $menu)
  {
    $this->application     = $app;
    $this->menu            = $menu;
    $this->db              = Factory::getContainer()->get(DatabaseInterface::class);
  }

  /**
   * Preprocess URLs
   *
   * @param   array  $query  An associative array of query variables
   *
   * @return  array  The segments to use to assemble the subsequent URL.
   */
  public function preprocess($query)
  {
    if(!isset($query['Itemid']))
    {
      // No Itemid set, so try to find a joomgallery menuitem which matches the query params
      // Firstly get all the joomgallery menuitems, matching the language if set.
      // Note that if the lang is set in the query parameters then menuitems with language * are ignored
      // so this might need to be addressed in a genuine joomla extension
      if(Multilanguage::isEnabled() && isset($query['lang']))
      {
        $items = $this->menu->getItems(array('component','language'), array('com_joomgallery',$query['lang']));
      }
      else
      {
        $items = $this->menu->getItems(array('component'), array('com_joomgallery'));
      }

      foreach($items as $menuitem)
      {
        // Look for a match with the view
        if(array_key_exists('view', $query) && array_key_exists('view', $menuitem->query) && ($menuitem->query['view'] == $query['view']))
        {
          $query['Itemid'] = $menuitem->id;

          // If there's an exact match with the id as well, then take that menuitem by preference
          if(array_key_exists('id', $query) && array_key_exists('id', $menuitem->query) && ($menuitem->query['id'] == (int)$query['id']))
          {
            break;
          }
        }
      }
    }

    return $query;
  }

  /**
   * Build the route for the component
   *
   * @param   array  &$query  An associative array of query variables
   *
   * @return  array  The segments to use to assemble the subsequent URL.
   */
  public function build(&$query)
  {
    $segments = array();

    if(!defined('_JOOM_OPTION'))
    {
      require_once JPATH_ADMINISTRATOR.'/components/com_joomgallery/includes/defines.php';
    }

    if(isset($query['view']) && $query['view'] == 'toplist')
    {
      if(isset($query['type']))
      {
        switch($query['type'])
        {
          case 'toprated':
            $segment = ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_TOP_RATED'));
            if(trim(str_replace('-', '', $segment)) == '')
            {
              $segments[] = 'top-rated';
            }
            else
            {
              $segments[] = $segment;
            }
            break;
          case 'lastadded':
            $segment = ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_LAST_ADDED'));
            if(trim(str_replace('-', '', $segment)) == '')
            {
              $segments[] = 'last-added';
            }
            else
            {
              $segments[] = $segment;
            }
            break;
          case 'lastcommented':
            $segment = ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_LAST_COMMENTED'));
            if(trim(str_replace('-', '', $segment)) == '')
            {
              $segments[] = 'last-commented';
            }
            else
            {
              $segments[] = $segment;
            }
            break;
          default:
            $segment = ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_MOST_VIEWED'));
            if(trim(str_replace('-', '', $segment)) == '')
            {
              $segments[] = 'most-viewed';
            }
            else
            {
              $segments[] = $segment;
            }
            break;
        }
      }
      else
      {
        $segment = ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_MOST_VIEWED'));
        if(trim(str_replace('-', '', $segment)) == '')
        {
          $segments[] = 'most-viewed';
        }
        else
        {
          $segments[] = $segment;
        }
      }

      unset($query['type']);
      unset($query['view']);
    }

    if(isset($query['view']) && $query['view'] == 'edit')
    {
      $segments[] = 'edit';

      $dbquery = $this->db->getQuery(true)
              ->select('alias')
              ->from(_JOOM_TABLE_IMAGES)
              ->where('id = '.(int) $query['id']);
      $this->db->setQuery($dbquery);
      if(!$segment = $this->db->loadResult())
      {
        // Append ID of image if alias was not found?
        $segment = 'alias-not-found-'.$query['id'];
      }
      $segments[] = $segment;
      unset($query['view']);
      unset($query['id']);
    }

    if(isset($query['view']) && $query['view'] == 'editcategory')
    {
      if(isset($query['catid']))
      {
        $segments[] = 'editcategory';

        $dbquery = $this->db->getQuery(true)
                ->select('alias')
                ->from(_JOOM_TABLE_CATEGORIES)
                ->where('cid = '.(int) $query['catid']);
        $this->db->setQuery($dbquery);
        if(!$segment = $this->db->loadResult())
        {
          // Append ID of category if alias was not found
          $segment = 'alias-not-found-'.$query['catid'];
        }
        $segments[] = $segment;
      }
      else
      {
        $segments[] = 'newcategory';
      }
      unset($query['view']);
      unset($query['catid']);
    }

    if(isset($query['view']) && $query['view'] == 'gallery')
    {
      unset($query['view']);

      if(isset($query['Itemid']) && $Itemid = JoomRouting::checkItemid($query['Itemid']))
      {
        $query['Itemid'] = $Itemid;
      }
    }

    if(isset($query['view']) && $query['view'] == 'image')
    {
      $sef_image = ComponentHelper::getParams(_JOOM_OPTION)->get('sef_image', 0);
      if(!$sef_image)
      {
        $segments[] = 'image';

        return $segments;
      }

      unset($query['view']);
      $query['format'] = 'jpg';
      $segment = 'image-'.$query['id'];
      if(isset($query['type']))
      {
        $segment .= '-'.$query['type'];
        unset($query['type']);
      }
      else
      {
        $segment .= '-thumb';
      }
      if(isset($query['width']) && isset($query['height']))
      {
        $segment .= '-'.$query['width'].'-'.$query['height'];
        unset($query['width']);
        unset($query['height']);

        if(isset($query['pos']))
        {
          $segment .= '-'.$query['pos'];
        }

        if(isset($query['x']))
        {
          if(!isset($query['pos']))
          {
            $segment .= '-0';
          }

          $segment .= '-'.$query['x'];
        }

        if(isset($query['y']))
        {
          if(!isset($query['pos']))
          {
            $segment .= '-0';
          }
          else
          {
            unset($query['pos']);
          }

          if(!isset($query['x']))
          {
            $segment .= '-0';
          }
          else
          {
            unset($query['x']);
          }

          $segment .= '-'.$query['y'];

          unset($query['y']);
        }
        else
        {
          if(isset($query['pos']))
          {
            unset($query['pos']);
          }
          if(isset($query['x']))
          {
            unset($query['x']);
          }
        }
      }

      $segments[] = $segment;

      if($sef_image == 1)
      {
        unset($query['id']);

        return $segments;
      }

      //if($config->get('jg_image_sef') == 2)
      //{
        $dbquery = $this->db->getQuery(true)
                ->select('alias')
                ->from(_JOOM_TABLE_IMAGES)
                ->where('id = '.(int) $query['id']);
        $this->db->setQuery($dbquery);
        if($segment = $this->db->loadResult())
        {
          $segments[] = $segment;
        }
      //}

      unset($query['id']);
    }
    if(isset($query['view']) && $query['view'] == 'mini')
    {
      $segments[] = 'mini';
      unset($query['view']);
    }
    if(isset($query['view']) && $query['view'] == 'search')
    {
      $segments[] = 'search';
      unset($query['view']);
    }
    if(isset($query['view']) && $query['view'] == 'upload')
    {
      $segments[] = 'upload';
      unset($query['view']);
    }
    if(isset($query['view']) && $query['view'] == 'usercategories')
    {
      $segments[] = 'usercategories';
      unset($query['view']);
    }
    if(isset($query['view']) && $query['view'] == 'userpanel')
    {
      $segments[] = 'userpanel';
      unset($query['view']);
    }

    if(isset($query['view']) && $query['view'] == 'favourites')
    {
      $segments[] = 'favourites';

      unset($query['view']);

      if(isset($query['layout']))
      {
        if($query['layout'] == 'default')
        {
          unset($query['layout']);
        }
      }
    }

    if(isset($query['view']) and $query['view'] == 'category')
    {
      $dbquery = $this->db->getQuery(true)
              ->select('alias')
              ->from(_JOOM_TABLE_CATEGORIES)
              ->where('cid = '.(int) $query['catid']);
      $this->db->setQuery($dbquery);
      if(!$segment = $this->db->loadResult())
      {
        // Append ID of category if alias was not found
        $segment = 'alias-not-found-'.$query['catid'];
      }
      $segments[] = $segment;
      unset($query['catid']);
      unset($query['view']);
    }

    if(isset($query['id']) && isset($query['view']) && $query['view'] == 'detail')
    {
      $dbquery = $this->db->getQuery(true)
              ->select('catid, alias')
              ->from(_JOOM_TABLE_IMAGES)
              ->where('id = '.(int) $query['id']);
      $this->db->setQuery($dbquery);
      $result_array = $this->db->loadAssoc();
      $dbquery->clear()
              ->select('alias')
              ->from(_JOOM_TABLE_CATEGORIES)
              ->where('cid = '.$result_array['catid']);
      $this->db->setQuery($dbquery);
      if(!$segment = $this->db->loadResult())
      {
        // Append ID of category if alias was not found
        $segment = 'alias-not-found-'.$result_array['catid'];
      }
      $segments[] = $segment;
      if(!$segment = $result_array['alias'])
      {
        // Append ID of image if alias was not found
        $segment = 'alias-not-found-'.$query['id'];
      }
      $segments[] = $segment;
      unset($query['id']);
      unset($query['view']);
    }

    if(isset($query['task']) && $query['task'] == 'savecategory')
    {
      $segments[] = 'savecategory';
      unset($query['task']);
    }

    if(isset($query['task']) && $query['task'] == 'deletecategory')
    {
      $segments[] = 'deletecategory';
      unset($query['task']);
    }

    return $segments;
  }

  /**
   * Parse the segments of a URL.
   *
   * @param   array  &$segments  The segments of the URL to parse. (Has to be empty after parsing)
   *
   * @return  array  The variables to be used by the application. (like 'view', 'id',...)
   */
  public function parse(&$segments)
  {
    if(!defined('_JOOM_OPTION'))
    {
      require_once JPATH_ADMINISTRATOR.'/components/com_joomgallery/includes/defines.php';
    }
  
    $vars = array();
  
    $language = Factory::getApplication()->getLanguage();
    $language->load('com_joomgallery');
  
    $segment = str_replace(':', '-', $segments[0]);
  
    if(   $segment == ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_TOP_RATED'))
      ||  $segment == ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_LAST_ADDED'))
      ||  $segment == ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_LAST_COMMENTED'))
      ||  $segment == ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_MOST_VIEWED'))
      ||  $segment == 'top-rated'
      ||  $segment == 'last-added'
      ||  $segment == 'last-commented'
      ||  $segment == 'most-viewed'
      )
    {
      $vars['view'] = 'toplist';
  
      switch($segment)
      {
        case 'top-rated':
        case ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_TOP_RATED')):
          $vars['type'] = 'toprated';
          break;
        case 'last-added':
        case ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_LAST_ADDED')):
          $vars['type'] = 'lastadded';
          break;
        case 'last-commented':
        case ApplicationHelper::stringURLSafe(Text::_('COM_JOOMGALLERY_COMMON_TOPLIST_LAST_COMMENTED')):
          $vars['type'] = 'lastcommented';
          break;
        default:
          break;
      }
  
      return $vars;
    }
  
    if($segments[0] == 'newcategory')
    {
      $vars['view'] = 'editcategory';
      return $vars;
    }
  
    if($segments[0] == 'editcategory')
    {
      array_shift($segments);
      if($result_array = JoomRouting::getId($segments))
      {
        $vars['catid'] = $result_array['id'];
      }
      $vars['view'] = 'editcategory';
  
      return $vars;
    }
  
    if($segments[0] == 'edit')
    {
      array_shift($segments);
      if(count($segments) && $result_array = JoomRouting::getId($segments))
      {
        $vars['id']   = $result_array['id'];
        $vars['view'] = 'edit';
      }
      else
      {
        $vars['view'] = 'upload';
      }
  
      return $vars;
    }
  
    if($segments[0] == 'savecategory')
    {
      $vars['task'] = 'savecategory';
  
      return $vars;
    }
  
    if($segments[0] == 'deletecategory')
    {
      $vars['task'] = 'deletecategory';
  
      return $vars;
    }
  
    if(strpos($segments[0], 'image') === 0)
    {
      $vars['view'] = 'image';
      $vars['format'] = 'raw';
      $exploded = explode('-', str_replace(':', '-', $segments[0]));
      if(isset($exploded[1]))
      {
        $vars['id'] = $exploded[1];
      }
      if(isset($exploded[2]))
      {
        $vars['type'] = $exploded[2];
      }
      if(isset($exploded[3]))
      {
        $vars['width'] = $exploded[3];
      }
      if(isset($exploded[4]))
      {
        $vars['height'] = $exploded[4];
      }
      if(isset($exploded[5]))
      {
        $vars['pos'] = $exploded[5];
      }
      if(isset($exploded[6]))
      {
        $vars['x'] = $exploded[6];
      }
      if(isset($exploded[7]))
      {
        $vars['y'] = $exploded[7];
      }
  
      return $vars;
    }
  
    if($result_array = JoomRouting::getId($segments))
    {
      if($result_array['view'] == 'category')
      {
        $vars['view']   = 'category';
        $vars['catid']  = $result_array['id'];
      }
      else
      {
        $vars['view']   = 'detail';
        $vars['id']  = $result_array['id'];
      }
  
      return $vars;
    }
  
    $valid_views = array( 'downloadzip',
                          'favourites',
                          'mini',
                          'search',
                          'upload',
                          'usercategories',
                          'userpanel'
                        );
    if(in_array($segments[0], $valid_views))
    {
      $vars['view'] = $segments[0];
      return $vars;
    }
  
    $vars['view'] = 'gallery';
  
    return $vars;
  }
}
