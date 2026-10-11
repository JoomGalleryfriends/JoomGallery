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

use Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;
use Joomgallery\Component\Joomgallery\Administrator\Service\Search\SearchInterface;
use Joomla\CMS\Object\CMSObject;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Joomla\Registry\Registry;

/**
 * Search Base Class
 *
 * @since  4.4.0
 */
class Search implements SearchInterface
{
  use ServiceTrait;

  /**
   * The search name.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $name = '';

  /**
   * The filters this search service applies.
   *
   * @var   array
   * @since  4.4.0
   */
  protected $filters = [];

  /**
   * True if this search service applies ordering.
   *
   * @var   bool
   * @since  4.4.0
   */
  protected $ordering = false;

  /**
   * The database driver object.
   *
   * @var    DatabaseInterface
   * @since  4.4.0
   */
  protected $db = null;

  /**
   * A state object
   *
   * @var    Registry
   * @since  4.4.0
   */
  protected $state = null;

  /**
   * Constructor
   *
   * @param  DatabaseInterface  $db      The databse
   * @param  Registry|CMSObject $state   The state object
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function __construct(DatabaseInterface $db, Registry|CMSObject $state)
  {
    $this->db    = $db;
    $this->state = $state;
  }

  /**
   * Returns the name of the search.
   *
   * @return  string
   *
   * @since   4.4.0
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * Returns the array of filters.
   *
   * @return  array
   *
   * @since   4.4.0
   */
  public function getFilters(): array
  {
    return $this->filters;
  }

  /**
   * Add the state to the service.
   *
   * @param   Registry  $state   The state object
   *
   * @since   4.4.0
   */
  public function setState(Registry $state): void
  {
    $this->state = $state;
  }

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
  public function applyToQuery(QueryInterface $query, string $term, string $alias): void
  {
    return;
  }

  /**
   * Returns true if the search handles this filter within applyToQuery().
   *
   * @param   string    $filter   The name of the filter
   *
   * @return  bool
   *
   * @since   4.4.0
   */
  public function handlesFilter(string $filter): bool
  {
    return \in_array($filter, $this->filters, true);
  }

  /**
   * Returns true if the search handles ordering within applyToQuery().
   *
   * @return  bool
   *
   * @since   4.4.0
   */
  public function handlesOrdering(): bool
  {
    return $this->ordering;
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
    return [];
  }
}
