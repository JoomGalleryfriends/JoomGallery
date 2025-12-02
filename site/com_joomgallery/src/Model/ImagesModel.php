<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Model;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Model\ImagesModel as AdminImagesModel;

/**
 * Model to get a list of image records.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesModel extends AdminImagesModel
{
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
        'title', 'a.title',
        'alias', 'a.alias',
        'catid', 'a.catid',
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
        'date', 'a.date',
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
   * @return  void
   *
   * @throws  \Exception
   *
   * @since   4.0.0
   */
  protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
  {
    // List state information.
    parent::populateState($ordering, $direction);

    // Set filters based on how the view is used.
    // e.g. user list of images: $this->setState('filter.created_by', Factory::getApplication()->getIdentity());

    $this->loadComponentParams();
  }

  /**
   * Build an SQL query to load the list data.
   *
   * @return  DatabaseQuery
   *
   * @since   4.0.0
   */
  protected function getListQuery()
  {
    $query = parent::getListQuery();

    return $query;
  }

  /**
   * Method to get an array of data items
   *
   * @return  mixed An array of data on success, false on failure.
   */
  public function getItems()
  {
    $items = parent::getItems();
    $start = $this->getState('list.start');

    if($start > 0)
    {
      $pages = \boolval($this->getState('list.pages', 1));

      if(!$pages)
      {
        // Make sure $start=1 starts at the first image
        $items = \array_slice($items, $start - 1);
      }
    }

    return $items;
  }

  /**
   * Method to get the starting number of items for the data set.
   *
   * @return  integer  The starting number of items available in the data set.
   *
   * @since   4.2.0
   */
  public function getStart()
  {
    $store = $this->getStoreId('getstart');

    // Try to load the data from internal storage.
    if(isset($this->cache[$store]))
    {
      return $this->cache[$store];
    }

    $start = $this->getState('list.start');

    if($start > 0)
    {
      $pages = \boolval($this->getState('list.pages', 1));
      $limit = $this->getState('list.limit');
      $total = $this->getTotal();

      if($pages && ($start > $total - $limit))
      {
        // Get a start value that makes sense for pagination
        $start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
      }
    }

    // Add the total to the internal cache.
    $this->cache[$store] = $start;

    return $this->cache[$store];
  }
}
