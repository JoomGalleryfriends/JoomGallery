<?php
/****************************************************************************************\
**   JoomGallery 3                                                                      **
**   By: JoomGallery::ProjectTeam                                                       **
**   Copyright (C) 2008 - 2021  JoomGallery::ProjectTeam                                **
**   Based on: JoomGallery 1.0.0 by JoomGallery::ProjectTeam                            **
**   Released under GNU GPL Public License                                              **
**   License: http://www.gnu.org/copyleft/gpl.html or have a look                       **
**   at administrator/components/com_joomgallery/LICENSE.TXT                            **
\****************************************************************************************/

defined('_JEXEC') or die('Direct Access to this location is not allowed.');

/**
 * JoomGallery search model
 *
 * @package JoomGallery
 * @since   1.5.5
 */
class JoomGalleryModelSearch extends JoomGalleryModel
{
  /**
   * Images data array with the search results
   *
   * @var   array
   */
  protected $_searchResults;

  /**
   * Constructor
   *
   * @return  void
   * @since   1.5.5
   */
  public function __construct()
  {
    parent::__construct();
  }

  /**
   * Method to get the images searched for
   *
   * @return  object  An object containing the search results
   * @since   1.5.5
   */
  public function getSearchResults()
  {
    if($this->_loadSearchResults())
    {
      return $this->_searchResults;
    }

    return array();
  }

  /**
   * Method to get the images searched for from the database
   *
   * @return  boolean True on success, false otherwise
   * @since   1.5.5
   */
  protected function _loadSearchResults()
  {
    if(empty($this->_searchResults))
    {
      $sstring        = JRequest::getString('sstring');
      $searchstring   = $this->_db->escape(trim($sstring));

      $categories           = $this->_ambit->getCategoryStructure();
      $authorisedViewLevels = implode(',', $this->_user->getAuthorisedViewLevels());

      // Create query object
      $query = $this->_db->getQuery(true);

      if($this->_config->get('jg_searchengine') == 'joomgallery')
      {
        // Create search part of the query
        $where   = '(u.username       LIKE '.$this->_db->q('%'.$searchstring.'%').'
                  OR a.imgtitle       LIKE '.$this->_db->q('%'.$searchstring.'%').'
                  OR LOWER(a.imgtext) LIKE '.$this->_db->q('%'.$searchstring.'%');
        $context = _JOOM_OPTION.'.search';
      }
      else
      {
        $where   = '(';
        $context = _JOOM_OPTION.'.'.$this->_config->get('jg_searchengine');
      }

      // Add query parts from plugins
      $aliases = array('images' => 'a', 'categories' => 'ca', 'users' => 'u');
      $plugins = $this->_mainframe->triggerEvent('onJoomSearch', array($searchstring, $aliases, $context));

      foreach($plugins as $plugin)
      {
        $plugin_searchengines = array('joomgallery');
        if(isset($plugin['searchengines']))
        {
          $plugin_searchengines = $plugin['searchengines'];
        }

        if(in_array($this->_config->get('jg_searchengine'), $plugin_searchengines))
		    {
          if(isset($plugin['images.select']))
          {
            $query->select($plugin['images.select']);
          }
          if(isset($plugin['images.leftjoin']))
          {
            $query->leftJoin($plugin['images.leftjoin']);
          }
          if(isset($plugin['images.where']))
          {
            $query->where($plugin['images.where']);
          }
          if(isset($plugin['images.where.or']))
          {
            $where .= '
                OR '.$plugin['images.where.or'];
          }
        }
      }

      // Only now the search part can be finalized
      $where .= ')';


      // General select clause of the query
      $query->select('a.*, '.JoomHelper::getSQLRatingClause('a').' AS rating, u.username, ca.cid, ca.name AS name, ca.allow_download AS allow_download');

      // Count comments of each image if required
      if($this->_config->get('jg_showcatcom'))
      {
        $subquery = $this->_db->getQuery(true)
                 ->select('COUNT(*)')
                 ->from(_JOOM_TABLE_COMMENTS)
                 ->where('cmtpic = a.id')
                 ->where('published = 1')
                 ->where('approved  = 1');
        $query->select('('.$subquery.') As comments');
      }

      // Main part of the query
      $query->from(_JOOM_TABLE_IMAGES.' AS a')
            ->innerJoin(_JOOM_TABLE_CATEGORIES.' AS ca ON a.catid = ca.cid')
            ->leftJoin('#__users AS u ON a.owner = u.id');
      if($this->_config->get('jg_searchengine') == 'joomgallery')
      {
        $query->where($where);
      }
      $query->where('a.published = 1')
            ->where('a.approved = 1')
            ->where('a.access IN ('.$authorisedViewLevels.')')
            ->where('ca.published = 1')
            ->where('ca.access IN ('.$authorisedViewLevels.')')
            ->where('ca.cid IN ('.implode(',', array_keys($categories)).')')
            ->where('a.hidden = 0')
            ->where('ca.hidden = 0')
            ->where('ca.in_hidden = 0')
            ->where('ca.exclude_search = 0')
            ->group('a.id')
            ->order('a.id DESC');

      $this->_db->setQuery($query);

      if(!$rows = $this->_db->loadObjectList())
      {
        return false;
      }

      $this->_searchResults = $rows;
    }

    return true;
  }
}
