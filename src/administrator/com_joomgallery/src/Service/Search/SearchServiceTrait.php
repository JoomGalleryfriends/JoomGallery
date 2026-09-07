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

use Joomgallery\Component\Joomgallery\Administrator\Service\Search\FinderSearch;
use Joomgallery\Component\Joomgallery\Administrator\Service\Search\SQLSearch;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Object\CMSObject;
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
   * Returns a list of available search providers.
   *
   * @return  array
   *
   * @since  4.4.0
   */
  public function getSearchProviders(): array
  {
    $providers = [
      ['value' => 'sql', 'text' => Text::_('COM_JOOMGALLERY_SERVICE_SEARCH_PROVIDER_SQL_TITLE'), 'desc' => Text::_('COM_JOOMGALLERY_SERVICE_SEARCH_PROVIDER_SQL_DESC')],
      ['value' => 'finder', 'text' => Text::_('COM_JOOMGALLERY_SERVICE_SEARCH_PROVIDER_FINDER_TITLE'), 'desc' => Text::_('COM_JOOMGALLERY_SERVICE_SEARCH_PROVIDER_FINDER_DESC')],
    ];

    return $providers;
  }

  /**
   * Creates the search helper class
   *
   * @param   string               $search  Name of the search to be used
   * @param   Registry|CMSObject   $state   The state object
   *
   * @return  void
   *
   * @since  4.4.0
   */
  public function createSearch($search, DatabaseInterface $db, Registry|CMSObject $state): void
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
