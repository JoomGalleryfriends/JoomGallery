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

use Joomgallery\Component\Joomgallery\Administrator\Service\Search\SQLSearch;
use Joomgallery\Component\Joomgallery\Administrator\Service\Search\FinderSearch;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * Trait to implement SearchServiceInterface
 *
 * @since  4.4.0
 */
trait SearchServiceTrait
{
  /**
   * Storage for the search helper class.
   *
   * @var SearchInterface
   *
   * @since  4.4.0
   */
  private $search = null;

  /**
   * Returns the search helper class.
   *
   * @return  SearchInterface
   *
   * @since  4.4.0
   */
  public function getSearch(): SearchInterface
  {
    return $this->search;
  }

  /**
   * Creates the search helper class
   *
   * @param   string      $search  Name of the search to be used
   * @param   Registry    $state   The state object
   *
   * @return  void
   *
   * @since  4.4.0
   */
  public function createSearch($search, DatabaseInterface $db, Registry $state): void
  {
    switch($search)
    {
      case 'finder':
        $this->search = new FinderSearch($db, $state);
        break;

      default:
        $this->search = new SQLSearch($db, $state);
        break;
    }

    return;
  }
}
