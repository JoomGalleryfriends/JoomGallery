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

use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * Search service interface
 *
 * @since  4.4.0
 */
interface SearchInterface
{
  /**
   * Returns the name of the search.
   *
   * @return  string
   *
   * @since   4.4.0
   */
  public function getName(): string;

  /**
   * Add the state to the service.
   *
   * @param   Registry  $state   The state object
   *
   * @since   4.4.0
   */
  public function setState(Registry $state): void;

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
  public function applyToQuery(QueryInterface $query, string $term, string $alias): void;

  /**
   * Returns true if the search handles this filter within applyToQuery().
   *
   * @param   string    $filter   The name of the filter
   *
   * @return  bool
   *
   * @since   4.4.0
   */
  public function handlesFilter(string $filter): bool;

  /**
   * Returns true if the search handles ordering within applyToQuery().
   *
   * @return  bool
   *
   * @since   4.4.0
   */
  public function handlesOrdering(): bool;
}
