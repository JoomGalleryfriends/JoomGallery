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

use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/**
 * The Search service
 *
 * @since  4.4.0
 */
interface SearchServiceInterface
{
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
  public function createSearch($search, DatabaseInterface $db, Registry $state): void;

  /**
   * Returns the search helper class.
   *
   * @return  SearchInterface
   *
   * @since  4.4.0
   */
  public function getSearch(): SearchInterface;

  /**
   * Returns a list of available search providers.
   *
   * @return  array
   *
   * @since  4.4.0
   */
  public function getSearchProviders(): array;
}
