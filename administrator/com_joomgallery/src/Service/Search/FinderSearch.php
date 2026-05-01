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

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\Finder\Site\Model\SearchModel;
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
    if($term === '')
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

    /** @var MVCFactoryInterface $mvcFactory */
    $mvcFactory = Factory::getApplication()->bootComponent('com_finder')->getMVCFactory();

    /** @var SearchModel $finderModel */
    $finderModel = $mvcFactory->createModel('Search', 'Site', ['ignore_request' => true]);

    /*
     * Pass the raw Finder input.
     * Finder itself parses AND / OR / NOT, phrases, required terms,
     * excluded terms, taxonomy filters, date filters, language, etc.
     */
    $finderModel->setState('input', $term);
    $finderModel->setState('filter.search', $term);

    /*
     * Optional: restrict Finder to your JoomGallery taxonomy.
     *
     * You can either:
     * - pass a configured Finder filter id
     * - or pass taxonomy node filters if your UI exposes them
     *
     * Exact state names depend on how you build your Finder filter UI.
     */
    $finderFilterId = (int) ($this->state->get('filter.finder_filter_id') ?: 0);

    if($finderFilterId > 0)
    {
      $finderModel->setState('filter', $finderFilterId);
    }

    $finderModel->setState('list.ordering', 'm.weight');
    $finderModel->setState('list.direction', 'DESC');

    /*
     * Important:
     * We want Finder's SQL, not Finder's rendered result list.
     */
    $finderQuery = $finderModel->getListQuery();

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
        . $this->db->quoteName('fr.object')
        . ', ' . $this->db->quote('&id=') . ', -1), ' . $this->db->quote('&') . ', 1) AS UNSIGNED)'
        . ' = ' . $this->db->quoteName($alias . '.id')
    );

    $query->select($this->db->quoteName('fr.ordering', 'finder_score'));
    $query->order($this->db->quoteName('fr.ordering') . ' DESC');
  }
}
