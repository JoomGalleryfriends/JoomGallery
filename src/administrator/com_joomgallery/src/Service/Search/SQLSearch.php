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

use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;

/**
 * SQL text based search
 *
 * @since  4.4.0
 */
class SQLSearch extends Search implements SearchInterface
{
  /**
   * The search name.
   *
   * @var   string
   * @since  4.4.0
   */
  protected $name = 'sql';

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
      $id = (int) substr($term, 3);

      $query->where($this->db->quoteName($alias . '.id') . ' = :search_id')
            ->bind(':search_id', $id, ParameterType::INTEGER);

      return;
    }

    $search = '%' . str_replace(' ', '%', trim($term)) . '%';

    $query->where(
        '('
        . $this->db->quoteName($alias . '.title') . ' LIKE :search_title'
        . ' OR ' . $this->db->quoteName($alias . '.alias') . ' LIKE :search_alias'
        . ' OR ' . $this->db->quoteName($alias . '.description') . ' LIKE :search_description'
        . ')'
    );

    $query->bind(
        [':search_title', ':search_alias', ':search_description'],
        $search,
        [ParameterType::STRING, ParameterType::STRING, ParameterType::STRING]
    );
  }
}
