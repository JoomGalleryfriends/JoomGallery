<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Component\Finder\Administrator\Indexer\Query;
use Joomla\Component\Finder\Site\Model\SearchModel;
use Joomla\Database\QueryInterface;

final class FinderBridgeModel extends SearchModel
{
  /**
   * Custom method to populate the state of SearchModel
   *
   * @param   string  $searchTerm  The search term
   * @param   array   $filters     The applied taxonomy filters
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function customPopulateState(string $searchTerm, array $filters): void
  {
    // Get the configuration options.
    $app        = Factory::getApplication();
    $input      = $app->getInput();
    $params     = ComponentHelper::getParams('com_finder');
    $user       = $app->getIdentity();
    $language   = $app->getLanguage();
    $options    = [];
    $searchTerm = trim($searchTerm);

    // Get the empty query setting.
    $options['empty']      = ($searchTerm === '' && !empty($taxonomyNodeIds)) ? 1 : (int) $params->get('allow_empty_query', 0);
    $options['filter']     = !empty($filters) ? $filters : $params->get('f', '');
    $options['filters']    = $params->get('t', []);
    $options['input']      = !empty($searchTerm) ? $searchTerm : $params->get('q', '');
    $options['language']   = $params->get('l', $language->getTag());
    $options['word_match'] = $params->get('word_match', 'exact');
    $options['date1']      = $params->get('d1', '');
    $options['when1']      = $params->get('w1', '');
    $options['date2']      = $params->get('d2', '');
    $options['when2']      = $params->get('w2', '');

    // Load the query object.
    $this->searchquery = new Query($options, $this->getDatabase());

    // Load the query token data.
    $this->excludedTerms = $this->searchquery->getExcludedTermIds();
    $this->includedTerms = $this->searchquery->getIncludedTermIds();
    $this->requiredTerms = $this->searchquery->getRequiredTermIds();

    $this->setState('filter.language', Multilanguage::isEnabled());
    $this->setState('list.start', $input->get('limitstart', 0, 'uint'));
    $this->setState('list.limit', $input->get('limit', $params->get('list_limit', $app->get('list_limit', 20)), 'uint'));
    $this->setState('list.direction', 'DESC');
    $this->setState('params', $params);
    $this->setState('list.ordering', 'l.link_id');
    $this->setState('list.direction', 'DESC');
    $this->setState('match.limit', 1000);
    $this->setState('params', $params);
    $this->setState('user.id', (int) $user->id);
    $this->setState('user.groups', $user->getAuthorisedViewLevels());

    $order = $params->get('sort_order', 'relevance');
    $this->setState('list.raworder', $order);

    switch($order)
    {
      case 'date':
          $this->setState('list.ordering', 'l.start_date');
          break;

      case 'price':
          $this->setState('list.ordering', 'l.list_price');
          break;

      case 'sale_price':
          $this->setState('list.ordering', 'l.sale_price');
          break;

      case $order === 'relevance' && !empty($this->includedTerms):
          $this->setState('list.ordering', 'm.weight');
          break;

      case 'title':
          $this->setState('list.ordering', 'l.title');
          break;

      default:
          $this->setState('list.ordering', 'l.link_id');
          $this->setState('list.raworder');
          break;
    }
  }

  /**
   * Wrapper function to execute the getListQuery() method
   *
   * @return  QueryInterface  The query created by SearchModel
   *
   * @since   4.4.0
   */
  public function buildListQuery(): QueryInterface
  {
    return $this->getListQuery();
  }
}
