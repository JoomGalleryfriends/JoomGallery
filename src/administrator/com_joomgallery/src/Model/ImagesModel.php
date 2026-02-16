<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Model\JoomListModel;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseQuery;
use Joomla\Database\ParameterType;
use Joomla\Utilities\ArrayHelper;

/**
 * Methods supporting a list of Images records.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesModel extends JoomListModel
{
  /**
   * Item type
   *
   * @access  protected
   * @var     string
   */
  protected $type = 'image';

  /**
   * Configuration param for search provider
   *
   * @access  public
   * @var     string
   */
  public $search = 'jg_backend_searchprovider';

  /**
   * Constructor
   *
   * @param   array  $config  An optional associative array of configuration settings.
   *
   * @return  void
   * @since   4.0.0
   */
  function __construct($config = [])
  {
    if(empty($config['filter_fields']))
    {
      $config['filter_fields'] = [
        'ordering', 'a.ordering',
        'hits', 'a.hits',
        'downloads', 'a.downloads',
        'votes', 'a.votes',
        'votesum', 'a.votesum',
        'approved', 'a.approved',
        'useruploaded', 'a.useruploaded',
        'title', 'a.title',
        'alias', 'a.alias',
        'cattitle', 'cattitle',
        'published', 'a.published',
        'author', 'a.author',
        'language', 'a.language',
        'description', 'a.description',
        'access', 'a.access',
        'hidden', 'a.hidden',
        'featured', 'a.featured',
        'created_time', 'a.created_time',
        'created_by', 'a.created_by',
        'modified_time', 'a.modified_time',
        'modified_by', 'a.modified_by',
        'id', 'a.id',
        'metadesc', 'a.metadesc',
        'metakey', 'a.metakey',
        'robots', 'a.robots',
        'filename', 'a.filename',
        'date', 'a.date',
        'imgmetadata', 'a.imgmetadata',
        'params', 'a.params',
      ];
    }

    parent::__construct($config);
  }

  /**
   * Method to auto-populate the model state.
   *
   * Note. Calling getState in this method will result in recursion.
   *
   * @param   string  $ordering   Elements order
   * @param   string  $direction  Order direction
   *
   * @return void
   *
   * @throws \Exception
   */
  protected function populateState($ordering = 'a.id', $direction = 'DESC')
  {
    $app = Factory::getApplication();

    $forcedLanguage = $app->input->get('forcedLanguage', '', 'cmd');

    // Adjust the context to support modal layouts.
    if($layout = $app->input->get('layout'))
    {
      $this->context .= '.' . $layout;
    }

    // Adjust the context to support forced languages.
    if($forcedLanguage)
    {
      $this->context .= '.' . $forcedLanguage;
    }

    // List state information.
    parent::populateState($ordering, $direction);

    // Load the filter state.
    $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '');
    $this->setState('filter.search', $search);
    $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '*');
    $this->setState('filter.published', $published);
    $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
    $this->setState('filter.language', $language);
    $showunapproved = $this->getUserStateFromRequest($this->context . '.filter.showunapproved', 'filter_showunapproved', '1');
    $this->setState('filter.showunapproved', $showunapproved);
    $showhidden = $this->getUserStateFromRequest($this->context . '.filter.showhidden', 'filter_showhidden', '1');
    $this->setState('filter.showhidden', $showhidden);
    $access = $this->getUserStateFromRequest($this->context . '.filter.access', 'filter_access', []);
    $this->setState('filter.access', $access);
    $createdBy = $this->getUserStateFromRequest($this->context . '.filter.created_by', 'filter_created_by', '');
    $this->setState('filter.created_by', $createdBy);
    $category = $this->getUserStateFromRequest($this->context . '.filter.category', 'filter_category', []);
    $this->setState('filter.category', $category);
    $tag = $this->getUserStateFromRequest($this->context . '.filter.tag', 'filter_tag', []);
    $this->setState('filter.tag', $tag);
    $and = $this->getUserStateFromRequest($this->context . '.filter.and', 'filter_and', false);
    $this->setState('filter.and', $and);
    $ids = $this->getUserStateFromRequest($this->context . '.filter.ids', 'filter_ids', '');
    $this->setState('filter.ids', $ids);
    $dateField = $this->getUserStateFromRequest($this->context . '.filter.datefiled', 'filter_datefield', 'date');
    $this->setState('filter.datefiled', $dateField);
    $startDate = $this->getUserStateFromRequest($this->context . '.filter.startdate', 'filter_startdate', '');
    $this->setState('filter.startdate', $startDate);
    $endDate = $this->getUserStateFromRequest($this->context . '.filter.enddate', 'filter_enddate', '');
    $this->setState('filter.enddate', $endDate);

    // Force a language
    if(!empty($forcedLanguage))
    {
      $this->setState('filter.language', $forcedLanguage);
      $this->setState('filter.forcedLanguage', $forcedLanguage);
    }
  }

  /**
   * Method to get a store id based on model configuration state.
   *
   * This is necessary because the model is used by the component and
   * different modules that might need different sets of data or different
   * ordering requirements.
   *
   * @param   string  $id  A prefix for the store id.
   *
   * @return  string A store id.
   *
   * @since   4.0.0
   */
  protected function getStoreId($id = '')
  {
    // Compile the store id.
    $id .= ':' . $this->getState('filter.search');
    $id .= ':' . $this->getState('filter.published');
    $id .= ':' . $this->getState('filter.language');
    $id .= ':' . $this->getState('filter.showunapproved');
    $id .= ':' . $this->getState('filter.showhidden');
    $id .= ':' . $this->getState('filter.datefield');
    $id .= ':' . $this->getState('filter.startdate');
    $id .= ':' . $this->getState('filter.enddate');
    $id .= ':' . serialize($this->getState('filter.ids'));
    $id .= ':' . serialize($this->getState('filter.access'));
    $id .= ':' . serialize($this->getState('filter.created_by'));
    $id .= ':' . serialize($this->getState('filter.category'));
    $id .= ':' . serialize($this->getState('filter.ids'));
    $id .= ':' . serialize($this->getState('filter.tag'));
    $id .= ':' . $this->getState('filter.and');

    return parent::getStoreId($id);
  }

  /**
   * Build an SQL query to load the list data.
   *
   * ToDo: Manuel
   * @return  \Joomla\Database\QueryInterface
   *
   * @since   4.0.0
   */
  protected function getListQuery()
  {
    // Create a new query object.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    // Initialize search service
    $this->component->createConfig();
    try
    {
      $searchProvider = $this->component->getSearch();
    }
    catch (\TypeError $e)
    {
      $searchProviderName = $this->component->getConfig()->get($this->search, 'sql');
      $this->component->createSearch($searchProviderName, $db, $this->state);
      $searchProvider = $this->component->getSearch();
    }

    // Check if logic and is active
    $logicAnd = (bool) ($this->getState('filter.and') > 0);

    // Check if filtering by tags
    $tag = $this->getState('filter.tag');

    // Check if filtering by ids
    $ids = $this->getState('filter.ids');

    // Sanitise tags array
    $tag = $this->sanitiseIDlist($tag);

    // Sanitise ids array
    $ids = $this->sanitiseIDlist($ids);

    // With less than two tags, we do not need the AND logic
    if(empty($tag) || \count($tag) < 2)
    {
      $logicAnd = false;
    }

    // Select the required fields from the table.
    // Add DISTINCT when filtering with multiple tags
    $useDistinct = !$searchProvider->handlesFilter('tags') && !empty($tag) && \count($tag) > 1 && !$logicAnd;
    $query->select($this->getListSelectFields($useDistinct));

    // Select table
    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && $logicAnd)
    {
      // With tags applied (AND logic)
      $subquery = $db->getQuery(true);
      $subquery->select($db->quoteName('tr.imgid'))
               ->from($db->quoteName('#__joomgallery_tags_ref', 'tr'))
               ->where($db->quoteName('tr.tagid') . ' IN (' . implode(',', array_map('intval', $tag)) . ')')
               ->group($db->quoteName('tr.imgid'))
               ->having('COUNT(DISTINCT tr.tagid) = ' . (int) \count($tag));

      // Join the image table to the subquery
      $query->from('(' . trim($subquery->__toString()) . ') AS imgs');
      $query->join('INNER', $db->quoteName('#__joomgallery', 'a') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('imgs.imgid'));
    }
    else
    {
      $query->from($db->quoteName('#__joomgallery', 'a'));
    }

    // Join over the users for the checked out user
    $query->select($db->quoteName('uc.name', 'uEditor'));
    $query->join('LEFT', $db->quoteName('#__users', 'uc'), $db->quoteName('uc.id') . ' = ' . $db->quoteName('a.checked_out'));

    // Join over the foreign key 'catid'
    $query->select([$db->quoteName('category.title', 'cattitle'), $db->quoteName('category.created_by', 'cat_uid')]);
    $query->join('LEFT', $db->quoteName('#__joomgallery_categories', 'category'), $db->quoteName('category.id') . ' = ' . $db->quoteName('a.catid'));

    // Join over the access level field 'access'
    $query->select($db->quoteName('access.title', 'access'));
    $query->join('LEFT', $db->quoteName('#__viewlevels', 'access'), $db->quoteName('access.id') . ' = ' . $db->quoteName('a.access'));

    // Join over the user field 'created_by'
    $query->select([$db->quoteName('ua.name', 'created_by'), $db->quoteName('ua.id', 'created_by_id')]);
    $query->join('LEFT', $db->quoteName('#__users', 'ua'), $db->quoteName('ua.id') . ' = ' . $db->quoteName('a.created_by'));

    // Join over the user field 'modified_by'
    $query->select([$db->quoteName('um.name', 'modified_by'), $db->quoteName('um.id', 'modified_by_id')]);
    $query->join('LEFT', $db->quoteName('#__users', 'um'), $db->quoteName('um.id') . ' = ' . $db->quoteName('a.modified_by'));

    // Join over the language fields 'language_title' and 'language_image'
    $query->select([$db->quoteName('l.title', 'language_title'), $db->quoteName('l.image', 'language_image')]);
    $query->join('LEFT', $db->quoteName('#__languages', 'l'), $db->quoteName('l.lang_code') . ' = ' . $db->quoteName('a.language'));

    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && !$logicAnd)
    {
      // Tags aggregation subquery
      $tagsSub = $db->getQuery(true)
          ->select($db->quoteName('tr_all.imgid', 'imgid'))
          ->select('GROUP_CONCAT(DISTINCT ' . $db->quoteName('t_all.id') . ' ORDER BY ' . $db->quoteName('t_all.title') . ' SEPARATOR ",") AS ' . $db->quoteName('tag_ids'))
          ->select('GROUP_CONCAT(DISTINCT ' . $db->quoteName('t_all.title') . ' ORDER BY ' . $db->quoteName('t_all.title') . ' SEPARATOR ",") AS ' . $db->quoteName('tag_titles'))
          ->from($db->quoteName('#__joomgallery_tags_ref', 'tr_all'))
          ->join('INNER', $db->quoteName('#__joomgallery_tags', 't_all') . ' ON ' . $db->quoteName('t_all.id') . ' = ' . $db->quoteName('tr_all.tagid'))
          ->group($db->quoteName('tr_all.imgid'));

      // Join aggregated tags into main query
      $query->join('LEFT', '(' . $tagsSub->__toString() . ') AS ' . $db->quoteName('tags') . ' ON ' . $db->quoteName('tags.imgid') . ' = ' . $db->quoteName('a.id'));
      $query->select([
        $db->quoteName('tags.tag_ids', 'tag_ids'),
        $db->quoteName('tags.tag_titles', 'tag_titles'),
      ]);
    }

    // Filter by access level.
    $filter_access = $this->getState('filter.access');

    if(!empty($filter_access))
    {
      if(is_numeric($filter_access))
      {
        $filter_access = (int) $filter_access;
        $query->where($db->quoteName('a.access') . ' = :access')
              ->bind(':access', $filter_access, ParameterType::INTEGER);
      }
      elseif(\is_array($filter_access))
      {
        $filter_access = ArrayHelper::toInteger($filter_access);
        $query->whereIn($db->quoteName('a.access'), $filter_access);
      }
    }

    // Filter by owner
    $userId = $this->getState('filter.created_by');

    if(!empty($userId))
    {
      if(is_numeric($userId))
      {
        $userId = (int) $userId;
        $type   = $this->getState('filter.created_by.include', true) ? ' = ' : ' <> ';
        $query->where($db->quoteName('a.created_by') . $type . ':userId')
          ->bind(':userId', $userId, ParameterType::INTEGER);
      }
      elseif(\is_array($userId))
      {
        $userId = ArrayHelper::toInteger($userId);
        $query->whereIn($db->quoteName('a.created_by'), $userId);
      }
    }

    // Filter by search
    $search = trim((string) $this->getState('filter.search'));

    $hasActiveSearchProviderFilter =
      !empty($this->getState('filter.category'))
      || !empty($this->getState('filter.tag'))
      || !empty($this->getState('filter.language'));

    if(!empty($search) || $hasActiveSearchProviderFilter)
    {
      $this->component->getSearch()->applyToQuery($query, $search, 'a');
    }

    // Filter by published state
    $published = (string) $this->getState('filter.published');

    if($published !== '*')
    {
      if(is_numeric($published))
      {
        $state = (int) $published;

        if($state == 1 || $state == 2)
        { // published/unpublished
          // translate state
          $state = ($state == 1) ? 1 : 0;

          // row name
          $row = 'a.published';
        }
        elseif($state == 3 || $state == 4)
        {// approved/not approved
          // translate state
          $state = ($state == 3) ? 1 : 0;

          // row name
          $row = 'a.approved';
        }
        elseif($state == 5)
        {// rejected
          Factory::getApplication()->enqueueMessage('Unknown state: Rejected', 'error');
          $state = false;
        }
        elseif($state == 6 || $state == 7)
        {// featured/not featured
          // translate state
          $state = ($state == 6) ? 1 : 0;

          // row name
          $row = 'a.featured';
        }

        if($state || $state === 0)
        {
          $query->where($db->quoteName($row) . ' = :state')
          ->bind(':state', $state, ParameterType::INTEGER);
        }
      }
    }

    // Filter by hidden images
    $showhidden = (bool) $this->getState('filter.showhidden');

    if(!$showhidden)
    {
    $query->where(
        [
          $db->quoteName('a.hidden') . ' = 0',
          $db->quoteName('category.hidden') . ' = 0',
          $db->quoteName('category.in_hidden') . ' = 0',
        ]
    );
    }

    // Filter by unapproved images
    $showunapproved = (bool) $this->getState('filter.showunapproved');

    if(!$showunapproved)
    {
      $query->where($db->quoteName('a.approved') . ' = 1');
    }

    // Filter by categories
    $catId = $this->getState('filter.category');

    // Convert to array
    if(!$searchProvider->handlesFilter('category') && isset($catId) && !\is_array($catId))
    {
      $catId = (string) preg_replace('/[^0-9\,]/i', '', $catId);

      if(strpos($catId, ',') !== false)
      {
        $catId = explode(',', $catId);
      }
    }

    if(!$searchProvider->handlesFilter('category') && !empty($catId))
    {
      if(is_numeric($catId))
      {
        $catId = (int) $catId;
        $query->where($db->quoteName('a.catid') . ' = :catId')
          ->bind(':catId', $catId, ParameterType::INTEGER);
      }
      elseif(\is_array($catId))
      {
        $catId = array_values(array_filter(array_map('intval', (array) $catId)));
        $query->whereIn($db->quoteName('a.catid'), $catId);
      }
    }

    // Filter by tags (OR logic)
    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && !$logicAnd)
    {
      $exists = $db->getQuery(true)
          ->select('1')
          ->from($db->quoteName('#__joomgallery_tags_ref', 'trx'))
          ->where($db->quoteName('trx.imgid') . ' = ' . $db->quoteName('a.id'));

      if (\count($tag) === 1)
      {
        $exists->where($db->quoteName('trx.tagid') . ' = ' . (int) $tag[0]);
      }
      else
      {
        $exists->where($db->quoteName('trx.tagid') . ' IN (' . implode(',', array_map('intval', $tag)) . ')');
      }

      $query->where('EXISTS (' . $exists->__toString() . ')');
    }

    // Filter by ids
    if(!empty($ids))
    {
      if (\count($ids) === 1)
      {
        $query->where($db->quoteName('a.id') . ' = :imgId')
          ->bind(':imgId', $ids[0], ParameterType::INTEGER);
      }
      else
      {
        $query->where($db->quoteName('a.id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')');
      }
    }

    // Filter by IDs
    $ids = $this->getState('filter.ids');

    if(!empty($ids))
    {
      if(!\is_array($ids))
      {
        $ids = (string) preg_replace('/[^0-9,]/', '', $ids);
        $ids = strpos($ids, ',') !== false ? explode(',', $ids) : [$ids];
      }

      $ids = ArrayHelper::toInteger((array) $ids);
      $ids = array_filter($ids);

      if(!empty($ids))
      {
        $query->whereIn($db->quoteName('a.id'), $ids);
      }
    }

    // Filter: Exclude images
    $excludedId = Factory::getApplication()->input->get('exclude', '', 'string');
    $excludedId = (string) preg_replace('/[^0-9\,]/i', '', $excludedId);

    if(strpos($excludedId, ',') !== false)
    {
      $excludedId = explode(',', $excludedId);
    }

    if(is_numeric($excludedId))
    {
      $excludedId = (int) $excludedId;
      $query->where($db->quoteName('a.id') . ' != :imgId')
        ->bind(':imgId', $excludedId, ParameterType::INTEGER);
    }
    elseif(\is_array($excludedId))
    {
      $excludedId = ArrayHelper::toInteger($excludedId);
      $query->whereNotIn($db->quoteName('a.id'), $excludedId);
    }

    // Filter on the language.
    if(!$searchProvider->handlesFilter('language') && $language = $this->getState('filter.language'))
    {
      $query->where($db->quoteName('a.language') . ' = :language')
        ->bind(':language', $language);
    }

    // Filter by date range
    $dateField = trim((string) $this->getState('filter.datefield'));
    $startDate = trim((string) $this->getState('filter.startdate'));
    $endDate   = trim((string) $this->getState('filter.enddate'));

    if($startDate !== '')
    {
      // Adjust date format
      if(!preg_match('/\d{2}:\d{2}:\d{2}$/', $startDate))
      {
        $startDate = Factory::getDate($startDate)->setTime(0, 0, 0);
      }
      else
      {
        $startDate = Factory::getDate($startDate);
      }

      $startDate = $startDate->toSql();

      $query->where($db->quoteName('a.' . $dateField) . ' >= :startDate')
        ->bind(':startDate', $startDate);
    }

    if($endDate !== '')
    {
      // Adjust date format
      if(!preg_match('/\d{2}:\d{2}:\d{2}$/', $endDate))
      {
        $endDate = Factory::getDate($endDate)->setTime(23, 59, 59);
      }
      else
      {
        $endDate = Factory::getDate($endDate);
      }

      $endDate = $endDate->toSql();

      $query->where($db->quoteName('a.' . $dateField) . ' <= :endDate')
        ->bind(':endDate', $endDate);
    }

    // Add the list ordering clause.
    if(!$searchProvider->handlesOrdering())
    {
      $orderCol  = $this->getState('list.ordering', 'a.id');
      $orderDirn = $this->getState('list.direction', 'ASC');

      if($orderCol && $orderDirn)
      {
        $query->order($db->escape($orderCol . ' ' . $orderDirn));
      }
      else
      {
        $query->order($db->escape($this->getState('list.fullordering', 'a.lft ASC')));
      }
    }

    return $query;
  }

  /**
   * Build an SQL query to load the list data for counting.
   *
   * @return  \Joomla\Database\QueryInterface
   *
   * @since   4.1.0
   */
  protected function getCountListQuery()
  {
    // Create a new query object.
    $db    = $this->getDatabase();
    $query = $db->getQuery(true);

    // Initialize search service
    $this->component->createConfig();
    try
    {
      $searchProvider = $this->component->getSearch();
    }
    catch (\TypeError $e)
    {
      $searchProviderName = $this->component->getConfig()->get($this->search);
      $this->component->createSearch($searchProviderName, $db, $this->state);
      $searchProvider = $this->component->getSearch();
    }

    // Check if logic and is active
    $logicAnd = (bool) ($this->getState('filter.and') > 0);

    // Check if filtering by tags
    $tag = $this->getState('filter.tag');

    // Sanitise tags array
    if(isset($tag))
    {
      if(!\is_array($tag))
      {
        $tag = (string) preg_replace('/[^0-9,]/', '', $tag);
        $tag = strpos($tag, ',') !== false ? explode(',', $tag) : [$tag];
      }

      $tag = ArrayHelper::toInteger((array) $tag);
      $tag = array_filter($tag);
    }

    // With less than two tags, we do not need the AND logic
    if(empty($tag) || \count($tag) < 2)
    {
      $logicAnd = false;
    }

    // Select the required fields from the table.
    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && \count($tag) > 1 && !$logicAnd)
    {
      // Add DISTINCT when filtering with multiple tags
      $query->select('COUNT(DISTINCT a.id)');
    }
    else
    {
      $query->select('COUNT(*)');
    }

    // Select table
    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && $logicAnd)
    {
      // With tags applied (AND logic)
      $subquery = $db->getQuery(true);
      $subquery->select($db->quoteName('tr.imgid'))
               ->from($db->quoteName('#__joomgallery_tags_ref', 'tr'))
               ->where($db->quoteName('tr.tagid') . ' IN (' . implode(',', array_map('intval', $tag)) . ')')
               ->group($db->quoteName('tr.imgid'))
               ->having('COUNT(DISTINCT tr.tagid) = ' . (int) \count($tag));

      // Join the image table to the subquery
      $query->from('(' . trim($subquery->__toString()) . ') AS imgs');
      $query->join('INNER', $db->quoteName('#__joomgallery', 'a') . ' ON ' . $db->quoteName('a.id') . ' = ' . $db->quoteName('imgs.imgid'));
    }
    else
    {
      $query->from($db->quoteName('#__joomgallery', 'a'));
    }

    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && !$logicAnd)
    {
      // Join with the tags and reference table to get tag IDs
      $query->join('INNER', $db->quoteName('#__joomgallery_tags_ref', 'tr') . ' ON ' . $db->quoteName('tr.imgid') . ' = ' . $db->quoteName('a.id'));
      $query->join('INNER', $db->quoteName('#__joomgallery_tags', 't') . ' ON ' . $db->quoteName('t.id') . ' = ' . $db->quoteName('tr.tagid'));
    }

    // Filter by access level.
    $filter_access = $this->state->get('filter.access');

    if(!empty($filter_access))
    {
      if(is_numeric($filter_access))
      {
        $filter_access = (int) $filter_access;
        $query->where($db->quoteName('a.access') . ' = :access')
              ->bind(':access', $filter_access, ParameterType::INTEGER);
      }
      elseif(\is_array($filter_access))
      {
        $filter_access = ArrayHelper::toInteger($filter_access);
        $query->whereIn($db->quoteName('a.access'), $filter_access);
      }
    }

    // Filter by owner
    $userId = $this->getState('filter.created_by');

    if(!empty($userId))
    {
      if(is_numeric($userId))
      {
        $userId = (int) $userId;
        $type   = $this->getState('filter.created_by.include', true) ? ' = ' : ' <> ';
        $query->where($db->quoteName('a.created_by') . $type . ':userId')
          ->bind(':userId', $userId, ParameterType::INTEGER);
      }
      elseif(\is_array($userId))
      {
        $userId = ArrayHelper::toInteger($userId);
        $query->whereIn($db->quoteName('a.created_by'), $userId);
      }
    }

    // Filter by search
    $search = $this->getState('filter.search');

    if(!empty($search))
    {
      if(stripos($search, 'id:') === 0)
      {
        $search = (int) substr($search, 3);
        $query->where($db->quoteName('a.id') . ' = :search')
          ->bind(':search', $search, ParameterType::INTEGER);
      }
      else
      {
        $search = '%' . str_replace(' ', '%', trim($search)) . '%';
        $query->where(
            '(' . $db->quoteName('a.title') . ' LIKE :search1 OR ' . $db->quoteName('a.alias') . ' LIKE :search2'
            . ' OR ' . $db->quoteName('a.description') . ' LIKE :search3)'
        )
          ->bind([':search1', ':search2', ':search3'], $search);
      }
    }

    // Filter by published state
    $published = (string) $this->getState('filter.published');

    if($published !== '*')
    {
      if(is_numeric($published))
      {
        $state = (int) $published;

        if($state == 1 || $state == 2)
        { // published/unpublished
          // translate state
          $state = ($state == 1) ? 1 : 0;

          // row name
          $row = 'a.published';
        }
        elseif($state == 3 || $state == 4)
        {// approved/not approved
          // translate state
          $state = ($state == 3) ? 1 : 0;

          // row name
          $row = 'a.approved';
        }
        elseif($state == 5)
        {// rejected
          Factory::getApplication()->enqueueMessage('Unknown state: Rejected', 'error');
          $state = false;
        }
        elseif($state == 6 || $state == 7)
        {// featured/not featured
          // translate state
          $state = ($state == 6) ? 1 : 0;

          // row name
          $row = 'a.featured';
        }

        if($state || $state === 0)
        {
          $query->where($db->quoteName($row) . ' = :state')
          ->bind(':state', $state, ParameterType::INTEGER);
        }
      }
    }

    // Filter by hidden images
    $showhidden = (bool) $this->getState('filter.showhidden');

    if(!$showhidden)
    {
    $query->where(
        [
          $db->quoteName('a.hidden') . ' = 0',
          $db->quoteName('category.hidden') . ' = 0',
          $db->quoteName('category.in_hidden') . ' = 0',
        ]
    );
    }

    // Filter by unapproved images
    $showunapproved = (bool) $this->getState('filter.showunapproved');

    if(!$showunapproved)
    {
      $query->where($db->quoteName('a.approved') . ' = 1');
    }

    // Filter by categories
    $catId = $this->getState('filter.category');

    // Convert to array
    if(!$searchProvider->handlesFilter('category') && isset($catId) && !\is_array($catId))
    {
      $catId = (string) preg_replace('/[^0-9\,]/i', '', $catId);

      if(strpos($catId, ',') !== false)
      {
        $catId = explode(',', $catId);
      }
    }

    if(!$searchProvider->handlesFilter('category') && !empty($catId))
    {
      if(is_numeric($catId))
      {
        $catId = (int) $catId;
        $query->where($db->quoteName('a.catid') . ' = :catId')
          ->bind(':catId', $catId, ParameterType::INTEGER);
      }
      elseif(\is_array($catId))
      {
        $catId = ArrayHelper::toInteger($catId);
        $query->whereIn($db->quoteName('a.catid'), $catId);
      }
    }

    // Filter by tags (OR logic)
    if(!$searchProvider->handlesFilter('tags') && !empty($tag) && !$logicAnd)
    {
      if(\count($tag) === 1)
      {
        $query->where($db->quoteName('t.id') . ' = :tag')
          ->bind(':tag', $tag[0], ParameterType::INTEGER);
      }
      else
      {
        $query->whereIn($db->quoteName('t.id'), $tag);
      }
    }

    // Filter by IDs
    $ids = $this->getState('filter.ids');

    if(!empty($ids))
    {
      if(!\is_array($ids))
      {
        $ids = (string) preg_replace('/[^0-9,]/', '', $ids);
        $ids = strpos($ids, ',') !== false ? explode(',', $ids) : [$ids];
      }

      $ids = ArrayHelper::toInteger((array) $ids);
      $ids = array_filter($ids);

      if(!empty($ids))
      {
        $query->whereIn($db->quoteName('a.id'), $ids);
      }
    }

    // Filter: Exclude images
    $excludedId = Factory::getApplication()->input->get('exclude', '', 'string');
    $excludedId = (string) preg_replace('/[^0-9\,]/i', '', $excludedId);

    if(strpos($excludedId, ',') !== false)
    {
      $excludedId = explode(',', $excludedId);
    }

    if(is_numeric($excludedId))
    {
      $excludedId = (int) $excludedId;
      $query->where($db->quoteName('a.id') . ' != :imgId')
        ->bind(':imgId', $excludedId, ParameterType::INTEGER);
    }
    elseif(\is_array($excludedId))
    {
      $excludedId = ArrayHelper::toInteger($excludedId);
      $query->whereNotIn($db->quoteName('a.id'), $excludedId);
    }

    // Filter on the language.
    if($language = $this->getState('filter.language'))
    {
      $query->where($db->quoteName('a.language') . ' = :language')
        ->bind(':language', $language);
    }

    // Filter by date range
    $dateField = trim((string) $this->getState('filter.datefield'));
    $startDate = trim((string) $this->getState('filter.startdate'));
    $endDate   = trim((string) $this->getState('filter.enddate'));

    if($startDate !== '')
    {
      // Adjust date format
      if(!preg_match('/\d{2}:\d{2}:\d{2}$/', $startDate))
      {
        $startDate = Factory::getDate($startDate)->setTime(0, 0, 0);
      }
      else
      {
        $startDate = Factory::getDate($startDate);
      }

      $query->where($db->quoteName('a.' . $dateField) . ' >= :startDate')
        ->bind(':startDate', $db->toSql($startDate));
    }

    if($endDate !== '')
    {
      // Adjust date format
      if(!preg_match('/\d{2}:\d{2}:\d{2}$/', $endDate))
      {
        $endDate = Factory::getDate($endDate)->setTime(23, 59, 59);
      }
      else
      {
        $endDate = Factory::getDate($endDate);
      }

      $query->where($db->quoteName('a.' . $dateField) . ' <= :endDate')
        ->bind(':endDate', $db->toSql($endDate));
    }

    return $query;
  }
}
