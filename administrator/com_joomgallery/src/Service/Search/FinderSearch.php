<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Search;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent;
use Joomgallery\Component\Joomgallery\Site\Model\FinderBridgeModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Component\Finder\Administrator\Helper\LanguageHelper;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * com_finder based search
 *
 * @since  4.4.0
 */
class FinderSearch extends Search implements SearchInterface
{
  /**
   * The search name.
   *
   * @var   string
   * @since  4.4.0
   */
  protected $name = 'finder';

  /**
   * The filters this search service applies.
   *
   * @var   array
   * @since  4.4.0
   */
  protected $filters = ['category', 'tags', 'language'];

  /**
   * True if this search service applies ordering.
   *
   * @var   bool
   * @since  4.4.0
   */
  protected $ordering = true;

  /**
   * Function to add the search to the query.
   *
   * @param   QueryInterface  $query   The list query
   * @param   string          $term    The search term
   * @param   string          $alias   The db table alias
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function applyToQuery(QueryInterface $query, string $term, string $alias = 'a'): void
  {
    $term = trim($term);
    $taxonomyNodeIds = $this->getFinderTaxonomyNodeIdsFromState();

    if($term === '' && empty($taxonomyNodeIds))
    {
      return;
    }

    if(stripos($term, 'id:') === 0)
    {
      $imageId = (int) substr($term, 3);

      $query->where($this->db->quoteName($alias . '.id') . ' = :finder_image_id')
            ->bind(':finder_image_id', $imageId, ParameterType::INTEGER);

      return;
    }

    /** @var JoomgalleryComponent $component */
    $component = Factory::getApplication()->bootComponent('com_joomgallery');

    /** @var FinderBridgeModel $finderModel */
    $finderModel = $component->getMVCFactory()->createModel('FinderBridge', 'Site', ['ignore_request' => true]);

    // Create the state
    $finderModel->customPopulateState($term, $taxonomyNodeIds);

    // /*
    //  * Pass the raw Finder input.
    //  * Finder itself parses AND / OR / NOT, phrases, required terms,
    //  * excluded terms, taxonomy filters, date filters, language, etc.
    //  */
    // $finderModel->setState('input', $term);
    // $finderModel->setState('filter.search', $term);
    // //$finderModel->setState('list.ordering', 'm.weight');
    // //$finderModel->setState('list.direction', 'DESC');

    // /*
    //  * Apply finder taxonomies.
    //  *
    //  */
    // if(!empty($taxonomyNodeIds))
    // {
    //   $finderModel->setState('filter.taxonomy', $taxonomyNodeIds);
    // }

    /*
     * Important:
     * We want Finder's SQL, not Finder's rendered result list.
     */
    $finderQuery = $finderModel->buildListQuery();


    $finderQuery->select($this->db->quoteName('l.url', 'url'));

    /*
     * Restrict Finder result rows to JoomGallery image items.
     *
     * Finder getListQuery() uses #__finder_links AS l.
     * Your plugin sets:
     *
     *   $item->context = 'com_joomgallery.image';
     *
     * Depending on Joomla/Finder version, the context may be stored in
     * l.object, l.url, or be indirectly represented by the indexed URL.
     *
     * The URL restriction is usually the safest fallback.
     */
    $finderQuery->where(
      $this->db->quoteName('l.url') . ' LIKE ' . $this->db->quote('%option=com_joomgallery%')
    );

    foreach($finderQuery->getBounded() as $key => $bound)
    {
      if(\is_array($bound))
      {
        $value = $bound['value'];
        $type  = $bound['dataType'] ?? ParameterType::STRING;
      }
      else
      {
        $value = $bound->value;
        $type  = $bound->dataType ?? ParameterType::STRING;
      }

      $query->bind($key, $value, $type);
    }

    /*
     * Join the Finder result query into the JoomGallery list query.
     *
     * The derived table contains:
     *   link_id
     *   object
     *   ordering
     *
     * We map Finder link URL back to image id.
     */
    $query->join(
        'INNER',
        '(' . $finderQuery->__toString() . ') AS ' . $this->db->quoteName('fr')
        . ' ON CAST(SUBSTRING_INDEX(SUBSTRING_INDEX('
        . $this->db->quoteName('fr.url')
        . ', ' . $this->db->quote('&id=') . ', -1), ' . $this->db->quote('&') . ', 1) AS UNSIGNED)'
        . ' = ' . $this->db->quoteName($alias . '.id')
    );
  }

  /**
   * Method to get a list of possible options
   *
   * @param   string  $filter  The name of the filter
   *
   * @return  array   The field option objects.
   *
   * @since   4.4.0
   */
  public function getFilterOptions(string $filter): array
  {
    if (!$this->handlesFilter($filter))
    {
      return [];
    }

    $branches = $this->getFinderTaxonomyNodeIds();

    foreach($branches as $branch)
    {
      if($branch->alias == $filter || $branch->path == $filter)
      {
        // We found the possible options for this filter field
        return $branch->nodes;
      }
    }

    // No finder taxonomies available
    return [];
  }

  /**
   * Method to get a list of taxonomy node ids
   *
   * @return  array  List of all taxonomy nodes
   *
   * @since   4.4.0
   */
  private function getFinderTaxonomyNodeIds(): array
  {
    $user   = Factory::getApplication()->getIdentity();
    $groups = implode(',', $user->getAuthorisedViewLevels());

    // Try to load the results from cache.
    $cache   = Factory::getCache('com_joomgallery', '');
    $cacheId = 'finder_filter_select_' . serialize([$groups, Factory::getLanguage()->getTag()]);

    // Check the cached results.
    if ($cache->contains($cacheId)) {
      $branches = $cache->get($cacheId);
    } else {
      $query = $this->db->getQuery(true);

      // Build the query to get the branch data and the number of child nodes.
      $joomgalleryUrl = '%option=com_joomgallery%';
      $query->clear()
        ->select('t.*, count(c.id) AS children')
        ->from($this->db->quoteName('#__finder_taxonomy', 't'))
        ->join('INNER', $this->db->quoteName('#__finder_taxonomy', 'c') . ' ON ' . $this->db->quoteName('c.parent_id') . ' = ' . $this->db->quoteName('t.id'))
        ->join('INNER', $this->db->quoteName('#__finder_taxonomy_map', 'tm') . ' ON ' . $this->db->quoteName('tm.node_id') . ' = ' . $this->db->quoteName('c.id'))
        ->join('INNER', $this->db->quoteName('#__finder_links', 'fl') . ' ON ' . $this->db->quoteName('fl.link_id') . ' = ' . $this->db->quoteName('tm.link_id'))
        ->where($this->db->quoteName('t.parent_id') . ' = 1')
        ->where($this->db->quoteName('t.state') . ' = 1')
        ->where($this->db->quoteName('c.state') . ' = 1')
        ->where($this->db->quoteName('fl.state') . ' = 1')
        ->where($this->db->quoteName('fl.published') . ' = 1')
        ->where($this->db->quoteName('fl.url') . ' LIKE :joomgallery_url')
        ->whereIn($this->db->quoteName('t.access'), $user->getAuthorisedViewLevels())
        ->whereIn($this->db->quoteName('c.access'), $user->getAuthorisedViewLevels())
        ->group($this->db->quoteName('t.id'))
        ->order($this->db->quoteName('t.lft') . ', ' . $this->db->quoteName('t.title'))
        ->bind(':joomgallery_url', $joomgalleryUrl, ParameterType::STRING);

      // Load the branches.
      $this->db->setQuery($query);

      try
      {
        $branches = $this->db->loadObjectList('id');
      }
      catch (\RuntimeException)
      {
        return [];
      }

      // Check that we have at least one branch.
      if(\count($branches) === 0)
      {
        return [];
      }

      // Iterate through the branches and build the branch groups.
      foreach($branches as $bk => $bv)
      {
        // If the multi-lang plugin is enabled then drop the language branch.
        if($bv->title === 'Language' && Multilanguage::isEnabled())
        {
          continue;
        }

        // Build the query to get the child nodes for this branch.
        $query->clear()
          ->select('t.*')
          ->from($this->db->quoteName('#__finder_taxonomy') . ' AS t')
          ->where('t.lft > :lft')
          ->where('t.rgt < :rgt')
          ->where('t.state = 1')
          ->whereIn('t.access', $user->getAuthorisedViewLevels())
          ->order('t.level, t.parent_id, t.title')
          ->bind(':lft', $bv->lft, ParameterType::INTEGER)
          ->bind(':rgt', $bv->rgt, ParameterType::INTEGER);

        // Apply multilanguage filter
        if(Multilanguage::isEnabled())
        {
          $language = [Factory::getLanguage()->getTag(), '*'];
          $query->whereIn($this->db->quoteName('t.language'), $language, ParameterType::STRING);
        }

        // Self-join to get the parent title.
        $query->select('e.title AS parent_title')
          ->join('LEFT', $this->db->quoteName('#__finder_taxonomy', 'e') . ' ON ' . $this->db->quoteName('e.id') . ' = ' . $this->db->quoteName('t.parent_id'));

        // Load the branches.
        $this->db->setQuery($query);

        try
        {
          $bv->nodes = $this->db->loadObjectList('id');
        }
        catch (\RuntimeException)
        {
          return [];
        }

        // Translate branch nodes if possible.
        $language = Factory::getLanguage();
        $root     = [];

        foreach($bv->nodes as $node_id => $node)
        {
          if(trim($node->parent_title, '*') === 'Language')
          {
            $title = LanguageHelper::branchLanguageTitle($node->title);
          }
          else
          {
            $key   = LanguageHelper::branchPlural($node->title);
            $title = $language->hasKey($key) ? Text::_($key) : $node->title;
          }

          if($node->level > 2)
          {
            $node->title = str_repeat('-', $node->level - 2) . $title;
          }
          else
          {
            $node->title = $title;
            $root[]      = $branches[$bk]->nodes[$node_id];
          }

          // Make sure text and value property exists
          $node->text  = $node->title;
          $node->value = $node->id;

          if($node->parent_id && isset($branches[$bk]->nodes[$node->parent_id]))
          {
            if(!isset($branches[$bk]->nodes[$node->parent_id]->children))
            {
              $branches[$bk]->nodes[$node->parent_id]->children = [];
            }

            $branches[$bk]->nodes[$node->parent_id]->children[] = $node;
          }
        }
      }

      // Store the data in cache.
      $cache->store($branches, $cacheId);
    }

    return $branches;
  }

  /**
   * Method to flatten a tree to a sorted array
   *
   * @param   \stdClass[]  $array
   *
   * @return  \stdClass[]  Flat array of all nodes of a tree with the children after each parent
   *
   * @since   4.4.0
   */
  private function reduce(array $array)
  {
    $return = [];

    foreach($array as $item)
    {
      $return[] = $item;
      if(isset($item->children))
      {
        $return = array_merge($return, $this->reduce($item->children));
      }
    }

    return $return;
  }

  private function getFinderTaxonomyNodeIdsFromState(): array
  {
    $categoryIds = $this->normaliseTaxonomyIds($this->state->get('filter.category'));
    $tagIds      = $this->normaliseTaxonomyIds($this->state->get('filter.tag'));
    $languageIds = $this->normaliseTaxonomyIds($this->state->get('filter.language'));

    $logicAnd = (bool) ((int) $this->state->get('filter.and') > 0);

    $filters = [];

    // Multiple categories should usually be OR.
    if(!empty($categoryIds))
    {
      $filters['category'] = $categoryIds;
    }

    // Multiple tags can be OR or AND.
    if(!empty($tagIds))
    {
      if($logicAnd && \count($tagIds) > 1)
      {
        foreach($tagIds as $tagId)
        {
          // Separate groups force AND logic in Finder.
          $filters['tag_' . $tagId] = [$tagId];
        }
      }
      else
      {
        // One group means OR logic inside the Tags branch.
        $filters['tag'] = $tagIds;
      }
    }

    // Usually only one language, but support array anyway.
    if(!empty($languageIds))
    {
      $filters['language'] = $languageIds;
    }

    return $filters;
  }

  private function normaliseTaxonomyIds(mixed $value): array
  {
    if(empty($value))
    {
      return [];
    }

    if(\is_string($value))
    {
      $value = preg_replace('/[^0-9,]/', '', $value);
      $value = $value !== '' ? explode(',', $value) : [];
    }

    if(!\is_array($value))
    {
      $value = [$value];
    }

    $value = array_map('intval', $value);
    $value = array_filter($value, static fn($id) => $id > 0);

    return array_values(array_unique($value));
  }
}
