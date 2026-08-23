<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Plugin\Finder\Joomgallery\Extension;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\Finder as FinderEvent;
use Joomla\Component\Finder\Administrator\Indexer\Adapter;
use Joomla\Component\Finder\Administrator\Indexer\Helper;
use Joomla\Component\Finder\Administrator\Indexer\Indexer;
use Joomla\Component\Finder\Administrator\Indexer\Result;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\QueryInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;

/**
 * Smart Search adapter for JoomGallery Images.
 *
 * @package JoomGallery
 * @since   4.4.0
 */
final class JoomImage extends Adapter implements SubscriberInterface
{
  use DatabaseAwareTrait;

  /**
   * The plugin identifier.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $context = 'JoomGallery';

  /**
   * The extension name.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $extension = 'com_joomgallery';

  /**
   * The sublayout to use when rendering the results.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $layout = 'joomimage';

  /**
   * The type of content that the adapter indexes.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $type_title = 'Image (JoomGallery)';

  /**
   * The table name.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $table = '#__joomgallery';

  /**
   * Load the language file on instantiation.
   *
   * @var    boolean
   * @since  4.4.0
   */
  protected $autoloadLanguage = true;

  /**
   * Item type that is currently performed
   *
   * @var    string
   * @since  4.4.0
   */
  protected $item_type = 'com_joomgallery.image';

  /**
   * Temporary item state.
   *
   * @var    array
   * @since  4.4.0
   */
  protected $tmp_state = ['state' => null,'access' => null];

  /**
   * Temporary storage.
   *
   * @var    mixed
   * @since  4.4.0
   */
  protected $tmp = null;

  /**
   * Returns an array of events this subscriber will listen to.
   *
   * @return  array
   *
   * @since   4.4.0
   */
  public static function getSubscribedEvents(): array
  {
    try
    {
      $events = parent::getSubscribedEvents();
    }
    catch (\Throwable $th)
    {
      $events = [];
    }

    return array_merge(
        $events,
        [
          'onFinderCategoryChangeState' => 'onFinderCategoryChangeState',
          'onFinderChangeState'         => 'onFinderChangeState',
          'onFinderAfterDelete'         => 'onFinderAfterDelete',
          'onFinderBeforeSave'          => 'onFinderBeforeSave',
          'onFinderAfterSave'           => 'onFinderAfterSave',
        ]
    );
  }

  /**
   * Method to setup the indexer to be run.
   *
   * @return  boolean  True on success.
   *
   * @since   2.5
   */
  protected function setup()
  {
    if(!ComponentHelper::isEnabled($this->extension))
    {
      return false;
    }

    // Define JoomGallery constants
    require_once(JPATH_ADMINISTRATOR . '/components/com_joomgallery/includes/defines.php');

    return true;
  }

  /**
   * Method to remove the link information for items that have been deleted.
   *
   * @param   FinderEvent\AfterDeleteEvent   $event  The event instance.
   *
   * @return  void
   *
   * @since   2.5
   * @throws  \Exception on database error.
   */
  public function onFinderAfterDelete($event): void
  {
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$context, $table] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      $context = $event->getContext();
      $table   = $event->getItem();
    }

    if($context === 'com_joomgallery.image')
    {
      //get image id
      $ids = [$table->id];
    }
    elseif($context === 'com_joomgallery.category')
    {
      $db = $this->getDatabase();

      // get image ids from category
      $query = clone $this->getStateQuery();
      $query->where('c.id = ' . (int) $table->id);
      $db->setQuery($query);
      $items = $db->loadObjectList();

      $ids = [];

      foreach($items as $item)
      {
        array_push($ids, $item->id);
      }
    }
    elseif($context === 'com_finder.index')
    {
      // get item id
      $ids = [$table->link_id];
    }
    else
    {
      return;
    }

    foreach($ids as $id)
    {
      // Remove item from the index.
      $this->remove($id);
    }
  }

  /**
   * Smart Search after save content method.
   * Reindexes the link information for an article that has been saved.
   * It also makes adjustments if the access level of an item or the
   * category to which it belongs has changed.
   *
   * @param   FinderEvent\AfterSaveEvent   $event  The event instance.
   *
   * @return  void
   *
   * @since   2.5
   * @throws  \Exception on database error.
   */
  public function onFinderAfterSave($event): void
  {
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$context, $row, $isNew] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      $context = $event->getContext();
      $row     = $event->getItem();
      $isNew   = $event->getIsNew();
    }

    // We only want to handle joomgallery images here.
    if($context === 'com_joomgallery.image' || $context === 'com_joomgallery.image.quick' || $context === 'com_joomgallery.image.batch')
    {
      // Save the item type
      $this->item_type = 'com_joomgallery.image';

      // Check if the access levels are different.
      if(!$isNew && $this->old_access != $row->access)
      {
        // Process the change.
        $this->itemAccessChange($row);
      }

      // Check if published or hidden or approved changed
      if(!$isNew && ($this->old_published != $row->published || $this->old_hidden != $row->hidden || $this->old_approved != $row->approved))
      {
        if($this->old_approved != $row->approved)
        {
          // approved has changed
          if($row->approved != 1)
          {
            // not approved anymore
            $value = 3;
          }
          else
          {
            // approved now
            $value = 4;
          }
        }
        else
        {
          if($row->published == 0 || $row->hidden != 0)
          {
            // threat the image as if its state has changed to unpublished
            $value = 0;
          }
          else
          {
            // threat the image as if its state has changed to published
            $value = 1;
          }
        }

        // Process the change.
        $this->itemStateChange([$row->id], $value, false);
      }

      // Reindex the item.
      $this->reindex($row->id);
    }

    // We only want to handle joomgallery categories here.
    if($context === 'com_joomgallery.category')
    {
      // Save the item type
      $this->item_type = 'com_joomgallery.category';

      // Check if the access levels are different.
      if(!$isNew && $this->old_cataccess != $row->access)
      {
        $this->categoryAccessChange($row);
      }

      // Check if published, hidden, in_hidden or hidden from search changed
      if(!$isNew && ($this->old_catpublished != $row->published || $this->old_cathidden != $row->hidden || $this->old_catinhidden != $row->in_hidden || $this->old_catexclude != $row->exclude_search))
      {
        if($row->published == 0 || $row->hidden != 0 || $row->in_hidden != 0 || $row->exclude_search != 0)
        {
          // threat the category as if its state has changed to unpublished
          $value = 0;
        }
        else
        {
          // threat the category as if its state has changed to published
          $value = 1;
        }

        // Process the change.
        $this->itemStateChange([$row->id], $value, false);
      }
    }
  }

  /**
   * Smart Search before content save method.
   * This event is fired before the data is actually saved.
   *
   * @param   FinderEvent\BeforeSaveEvent   $event  The event instance.
   *
   * @return  void
   *
   * @since   2.5
   * @throws  \Exception on database error.
   */
  public function onFinderBeforeSave($event): void
  {
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$context, $row, $isNew] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      $context = $event->getContext();
      $row     = $event->getItem();
      $isNew   = $event->getIsNew();
    }

    // We only want to handle joomgallery images here.
    if($context === 'com_joomgallery.image' || $context === 'com_joomgallery.image.quick' || $context === 'com_joomgallery.image.batch')
    {
      // Save the item type
      $this->item_type = 'com_joomgallery.image';

      // Query the database for the old access level if the item isn't new.
      if(!$isNew)
      {
        $this->checkItemState($row);
      }
    }

    // Check for access levels from the category.
    if(\in_array($context, ['com_joomgallery.category']))
    {
      // Save the item type
      $this->item_type = 'com_joomgallery.category';

      // Query the database for the old access level if the item isn't new.
      if(!$isNew)
      {
        $this->checkCategoryState($row);
      }
    }
  }

  /**
   * Method to update the link information for items that have been changed
   * from outside the edit screen. This is fired when the item is published,
   * unpublished, archived, or unarchived from the list view.
   *
   * @param   FinderEvent\AfterChangeStateEvent   $event  The event instance.
   *
   * @return  void
   *
   * @since   2.5
   */
  public function onFinderChangeState($event): void
  {
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$context, $pks, $value] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      $context = $event->getContext();
      $pks     = $event->getPks();
      $value   = $event->getValue();
    }

    $value = \intval($value);

    // We only want to handle the reindexing of joomgallery images.
    if((str_contains($context, 'com_joomgallery.image') || str_contains($context, 'com_joomgallery.category')) && $value >= 0)
    {
      // Save the item type
      if(str_contains($context, 'com_joomgallery.image'))
      {
        $this->item_type = 'com_joomgallery.image';
      }
      else
      {
        $this->item_type = 'com_joomgallery.category';
      }

      $this->itemStateChange($pks, $value);
    }

    // We only want to handle the reindexing of joomgallery categories.
    if(str_contains($context, 'com_joomgallery.category') && $value >= 0)
    {
      // Save the item type
      $this->item_type = 'com_joomgallery.category';

      $this->categoryStateChange($pks, $value);
    }

    // Handle when the plugin is disabled.
    if($context === 'com_plugins.plugin' && $value === 0)
    {
      $this->pluginDisable($pks);
    }
  }

  /**
   * Method to update the item link information when the item category is
   * changed. This is fired when the item category is published or unpublished
   * from the list view.
   *
   * @param   FinderEvent\AfterCategoryChangeStateEvent   $event  The event instance.
   *
   * @return  void
   *
   * @since   2.5
   */
  public function onFinderCategoryChangeState($event): void
  {
    if(version_compare(JVERSION, '5.0.0', '<'))
    {
      // Joomla 4
      [$context, $pks, $value] = $event->getArguments();
    }
    else
    {
      // Joomla 5 or newer
      $context = $event->getContext();
      $pks     = $event->getPks();
      $value   = $event->getValue();
    }

    $value = \intval($value);

    // We only want to handle joomgallery categories that get changed in the publishing state.
    if(str_contains($context, 'com_joomgallery.category') && $value >= 0)
    {
      // Save the item type
      $this->item_type = 'com_joomgallery.category';

      $this->categoryStateChange($event->getPks(), $value);
    }
  }

  /**
   * Method to index an item. The item must be a Result object.
   *
   * @param   Result  $item  The item to index as a Result object.
   *
   * @return  void
   *
   * @since   2.5
   * @throws  \Exception on database error.
   */
  protected function index(Result $item)
  {
    $item->setLanguage();

    // Check if the extension is enabled.
    if(ComponentHelper::isEnabled($this->extension) === false)
    {
      return;
    }

    $item->context = 'com_joomgallery.image';

    // Check tmp state
    if(!\is_null($this->tmp_state['state']))
    {
      $item->state = $this->tmp_state['state'];
    }

    // Change category access due to parent categories
    $item->cat_access = $this->getParentCatAccess($item->catid);

    // Check tmp access
    if(!\is_null($this->tmp_state['access']))
    {
      $item->access = $this->tmp_state['access'];
    }

    // Translate access
    $item->access = max($item->access, $item->cat_access);

    // Get the dates
    $item->publish_start_date = $item->created_time;
    $item->start_date         = $item->created_time;
    $item->publish_end_date   = null;
    unset($item->date);

    // Initialize the item parameters.
    $item->params = new Registry($item->params);

    // Trigger the onContentPrepare event.
    $item->summary = Helper::prepareContent($item->summary, $item->params, $item);

    // Build the necessary route and path information.
    $item->url   = $this->getUrl($item->id, $this->extension, $this->layout);
    $item->route = JoomHelper::getViewRoute('image', $item->id, $item->catid, null, null, $item->language);

    // Get the menu title if it exists.
    $title = $this->getItemMenuTitle($item->url);

    // Adjust the title if necessary.
    if(!empty($title) && $this->params->get('use_menu_title', true))
    {
      $item->title = $title;
    }

    // Add the image.
    $item->imageUrl = JoomHelper::getImg($item->id, 'thumbnail', true);
    $item->imageAlt = $item->alias;

    // Translate the state.
    $this->tmp   = $item;
    $this->tmp   = $this->getParentCatStates($this->tmp);
    $item->state = $this->translateState($item->state, $item->cat_state);
    $this->tmp   = null;

    // Get taxonomies to display
    $taxonomies = $this->params->get('taxonomies', ['type', 'author', 'category', 'tags', 'language']);

    if(!\is_array($taxonomies))
    {
      $taxonomies = explode(',', $taxonomies);
    }

      // Add the type taxonomy data.
    if(\in_array('type', $taxonomies))
    {
      $item->addTaxonomy('Type', 'Image (JoomGallery)');
    }

    // Add the author taxonomy data.
    if(\in_array('author', $taxonomies) && (!empty($item->author)))
    {
      $item->addTaxonomy('Author', $item->author);
    }

    // Add the category taxonomy data.
    if(\in_array('category', $taxonomies))
    {
      $item->addTaxonomy('Category', $item->category, $item->cat_state, $item->cat_access);
    }

      // Add the language taxonomy data.
    if(\in_array('language', $taxonomies))
    {
      $item->addTaxonomy('Language', $item->language);
    }

    // Fetch tags
    $tags = $this->getTagsForImage((int) $item->id);

    foreach($tags as $tag)
    {
      // Add the tags taxonomy data. (multi-value taxonomy)
      if(\in_array('tags', $taxonomies))
      {
        $item->addTaxonomy('Tags', $tag->title, $tag->published, $tag->access, $tag->language);
      }
    }

    // Put tags into a single text field for indexing
    $item->tags = implode(' ', array_map(fn($tag) => $tag->title, $tags));

    // Title = strongest relevance
    $item->addInstruction(Indexer::TITLE_CONTEXT, 'title');

    // Main searchable text
    $item->addInstruction(Indexer::TEXT_CONTEXT, 'summary');
    $item->addInstruction(Indexer::TEXT_CONTEXT, 'tags');
    $item->addInstruction(Indexer::TEXT_CONTEXT, 'author');

    // Metadata relevance (weak)
    $item->addInstruction(Indexer::META_CONTEXT, 'metakey');
    $item->addInstruction(Indexer::META_CONTEXT, 'metadesc');

    // Get content extras.
    Helper::getContentExtras($item);

    if(version_compare(JVERSION, '5.0.0', '>='))
    {
      Helper::addCustomFields($item, 'com_joomgallery.image');
    }

    // Index the item.
    $this->indexer->index($item);
  }

  /**
   * Method to get the SQL query used to retrieve a list of all items to index.
   *
   * @param   mixed  $query  A QueryInterface object or null.
   *
   * @return  QueryInterface  A database object.
   *
   * @since   2.5
   */
  protected function getListQuery($query = null)
  {
    $db = $this->getDatabase();

    // Check if we can use the supplied SQL query.
    $query = $query instanceof QueryInterface ? $query : $db->getQuery(true)
      ->select('a.id, a.title AS title, a.alias, a.author AS author, a.description AS summary')
      ->select('a.published AS state, a.catid, a.date')
      ->select('a.hidden, a.featured, a.checked_out, a.approved, a.params, a.language')
      ->select('a.metakey, a.metadesc, a.access, a.ordering, a.created_time')
      ->select('c.title AS category, c.published AS cat_state')
      ->select('u.name AS owner')
      ->from($this->table . ' AS a')
      ->join('LEFT', '#__joomgallery_categories AS c ON c.id = a.catid')
      ->join('LEFT', '#__users AS u ON u.id = a.created_by');

    return $query;
  }

  /**
   * Method to get the URL for the item. The URL is how we look up the link
   * in the Finder index.
   *
   * @param   integer  $id         The id of the item.
   * @param   string   $extension  The extension the category is in.
   * @param   string   $view       The view for the URL.
   *
   * @return  string  The URL of the item.
   *
   * @since   2.5
   */
  protected function getUrl($id, $extension, $view)
  {
    return 'index.php?option=' . $extension . '&view=' . $view . '&id=' . $id;
  }

  /**
   * Method to get a SQL query to load the published and access states for
   * an item and category.
   *
   * @return  QueryInterface  A database object.
   *
   * @since   2.5
   */
  protected function getStateQuery()
  {
    $query = $this->getDatabase()->getQuery(true);

    // Item ID
    $query->select('a.id');

    // Item and category published state
    $query->select('a.published AS state, c.published AS cat_state');

    // Additional item states
    $query->select('a.hidden AS hidden, a.approved AS approved');

    // Additional parent category states
    $query->select('c.hidden AS cat_hidden, c.in_hidden AS cat_inhidden, c.exclude_search AS cat_exclude');

    // Item and category access levels
    $query->select('a.access, c.access AS cat_access')
      ->from($this->table . ' AS a')
      ->join('LEFT', '#__joomgallery_categories AS c ON c.id = a.catid');

    return $query;
  }

  /**
   * Method to check the existing states for categories
   *
   * @param   JTable  $row  A JTable object
   *
   * @return  void
   *
   * @since   2.5
   */
  protected function checkCategoryState($row)
  {
    $db = $this->getDatabase();

    $query = $db->getQuery(true)
      ->select($db->quoteName('published'))
      ->select($db->quoteName('hidden'))
      ->select($db->quoteName('in_hidden'))
      ->select($db->quoteName('exclude_search'))
      ->select($db->quoteName('access'))
      ->from($db->quoteName('#__joomgallery_categories'))
      ->where($db->quoteName('id') . ' = ' . (int) $row->id);
    $db->setQuery($query);
    $states = $db->loadObject();

    // Store the states to determine if it changes
    $this->old_cataccess    = $states->access;
    $this->old_catpublished = $states->published;
    $this->old_cathidden    = $states->hidden;
    $this->old_catinhidden  = $states->in_hidden;
    $this->old_catexclude   = $states->exclude_search;
  }

  /**
   * Method to check the existing states for an item
   *
   * @param   JTable  $row  A JTable object
   *
   * @return  void
   *
   * @since   2.5
   */
  protected function checkItemState($row)
  {
    $db = $this->getDatabase();

    $query = $db->getQuery(true)
      ->select($db->quoteName('published'))
      ->select($db->quoteName('hidden'))
      ->select($db->quoteName('approved'))
      ->select($db->quoteName('access'))
      ->from($db->quoteName($this->table))
      ->where($db->quoteName('id') . ' = ' . (int) $row->id);
    $db->setQuery($query);
    $states = $db->loadObject();

    // Store the states to determine if it changes
    $this->old_access    = $states->access;
    $this->old_published = $states->published;
    $this->old_approved  = $states->approved;
    $this->old_hidden    = $states->hidden;
  }

  /**
   * Method to translate the native content states into states that the
   * indexer can use.
   *
   * @param   array    $value     The new item state. (0:umpublished,1:published,2:archived,3:not_approved,4:approved,5:not_featured,6:featured)
   * @param   integer  $category  The category state. [not used in this plugin]
   *
   * @return  integer  The translated indexer state.
   *
   * @since   2.5
   */
  protected function translateState($value, $category = null)
  {
    // states before change
    $published      = $this->tmp->state;
    $approved       = $this->tmp->approved;
    $hidden         = $this->tmp->hidden;
    $cat_state      = (isset($this->tmp->cat_state)) ? $this->tmp->cat_state : 1;
    $cat_hidden     = (isset($this->tmp->cat_hidden)) ? $this->tmp->cat_hidden : 0;
    $cat_inhidden   = (isset($this->tmp->cat_inhidden)) ? $this->tmp->cat_inhidden : 0;
    $cat_exclude    = (isset($this->tmp->cat_exclude)) ? $this->tmp->cat_exclude : 0;
    $cat_inexcluded = (isset($this->tmp->cat_inexcluded)) ? $this->tmp->cat_inexcluded : 0;

    if($this->item_type == 'com_joomgallery.image')
    {
      switch($value)
      {
        case 0:
          $published = 0;
            break;
        case 1:
          // break intensionally omitted
        case 2:
          $published = 1;
            break;
        case 3:
          $approved = 0;
            break;
        case 4:
          $approved = 1;
            break;
        default:
            break;
      }
    }

    if($this->item_type == 'com_joomgallery.category')
    {
      switch($value)
      {
        case 0:
          $cat_state = 0;
            break;
        case 1:
          // break intensionally omitted
        case 2:
          $cat_state = 1;
            break;
        case 3:
            break;
        case 4:
            break;
        default:
            break;
      }
    }

    if($published != 1 || $approved != 1 || $hidden != 0 || $cat_state != 1 || $cat_hidden != 0 || $cat_inhidden != 0 || $cat_exclude != 0 || $cat_inexcluded != 0)
    {
      // if one of these states are not set the item to be visible in frontend return 0
      return 0;
    }


    return 1;
  }

  /**
   * Method to update index data on published state changes
   *
   * @param   array    $pks       A list of primary key ids of the content that has changed state.
   * @param   integer  $value     The new item state. (0:umpublished,1:published,2:archived,3:not_approved,4:approved,5:not_featured,6:featured)
   * @param   bool     $reindex   ture, if item should be reindexed [optional]
   *
   * @return  void
   *
   * @since   2.5
   */
  protected function itemStateChange($pks, $value, $reindex = true)
  {
    $db = $this->getDatabase();

    if($this->item_type == 'com_joomgallery.category')
    {
      $this->categoryStateChange($pks, $value, $reindex = true);

      $img_ids = [];

      // Change also the state of all images in each subcategories
      foreach($pks as $cat_id)
      {
        $img_ids = array_unique(array_merge($img_ids, JoomHelper::getImages($cat_id, true, [], true)));
      }

      // Override category pks with image pks
      $pks = $img_ids;
    }

    /*
     * The item's published state is tied to the category
     * published state so we need to look up all published states
     * before we change anything.
     */
    foreach($pks as $pk)
    {
      $query = clone $this->getStateQuery();
      $query->where('a.id = ' . (int) $pk);

      // Get the published states.
      $db->setQuery($query);
      $item = $db->loadObject();

      // Translate the state.
      $this->tmp     = $item;
      $indexer_state = $this->translateState($value, $item->cat_state);
      $this->tmp     = null;

      // Update the item.
      $this->change($pk, 'state', $indexer_state);
      $this->tmp_state['state'] = $indexer_state;

      if($reindex)
      {
        // Reindex the item
        $this->reindex($pk);
      }

      // reset the tmp values
      $this->tmp_state['state'] = null;
    }
  }

  /**
   * Method to update index data on category access level changes
   *
   * @param   array    $pks       A list of primary key ids of the content that has changed state.
   * @param   integer  $value     The new item state. (0:umpublished,1:published,2:archived,3:not_approved,4:approved,5:not_featured,6:featured)
   * @param   bool     $reindex   ture, if item should be reindexed [optional]
   *
   * @return  void
   *
   * @since   2.5
   */
  protected function categoryStateChange($pks, $value, $reindex = true)
  {
    $db = $this->getDatabase();

    /*
     * The item's published state is tied to the category
     * published state so we need to look up all published states
     * before we change anything.
     */
    foreach($pks as $pk)
    {
      // create where array out of all subcategories
      $subcats     = JoomHelper::getCategories($pk, 'children', true, false);
      $where_array = [];

      foreach($subcats as $cat)
      {
        array_push($where_array, 'c.id = ' . (int) $cat->id);
      }

      $query = clone $this->getStateQuery();
      $query->where($where_array, 'OR');

      // Get the published states.
      $db->setQuery($query);
      $items = $db->loadObjectList();

      // Adjust the state for each item within the category.
      foreach($items as $item)
      {
        // Translate the state.
        $this->tmp     = $item;
        $indexer_state = $this->translateState($value, $item->cat_state);
        $this->tmp     = null;

        // Update the item.
        $this->change($item->id, 'state', $indexer_state);
        $this->tmp_state['state'] = $indexer_state;

        if($reindex)
        {
          // Reindex the item
          $this->reindex($item->id);
        }

        // reset the tmp values
        $this->tmp_state['state'] = null;
      }
    }
  }

  /**
   * Method to update index data on access level changes
   *
   * @param   JTable  $row  A JTable object
   * @param   bool    $reindex   ture, if item should be reindexed [optional]
   *
   * @return  void
   *
   * @since   2.5
   */
  protected function itemAccessChange($row, $reindex = true)
  {
    $db = $this->getDatabase();

    $query = clone $this->getStateQuery();
    $query->where('a.id = ' . (int) $row->id);

    // Get the access level.
    $db->setQuery($query);
    $item = $db->loadObject();

    // Set the access level.
    $temp = max($row->access, $item->cat_access);

    // Update the item.
    $this->change((int) $row->id, 'access', $temp);
    $this->tmp_state['access'] = $temp;

    if($reindex)
    {
      // Reindex the item
      $this->reindex($row->id);
    }

    // reset the tmp values
    $this->tmp_state['access'] = null;
  }

  /**
   * Method to update index data on category access level changes
   *
   * @param   JTable  $row  A JTable object
   * @param   bool    $reindex   ture, if item should be reindexed [optional]
   *
   * @return  void
   *
   * @since   2.5
   */
  protected function categoryAccessChange($row, $reindex = true)
  {
    // create where array out of all subcategories
    $subcats     = JoomHelper::getCategories($row, 'children', true, false);
    $where_array = [];

    foreach($subcats as $catid)
    {
      array_push($where_array, 'c.id = ' . (int) $catid);
    }

    $db = $this->getDatabase();

    $query = clone $this->getStateQuery();
    $query->where($where_array, 'OR');

    // Get the access level.
    $db->setQuery($query);
    $items = $db->loadObjectList();

    // Adjust the access level for each item within the category.
    foreach($items as $item)
    {
      // Set the access level.
      $temp = max($item->access, $row->access);

      // Update the item.
      $this->change((int) $item->id, 'access', $temp);
      $this->tmp_state['access'] = $temp;

      if($reindex)
      {
        // Reindex the item
        $this->reindex($item->id);
      }

      // reset the tmp values
      $this->tmp_state['access'] = null;
    }
  }

  /**
   * Method to update item object with states from parent categories
   *
   * @param   JTable  $item  A JTable object
   *
   * @return  JTable  extended item object
   *
   * @since   1.0
   */
  protected function getParentCatStates($item)
  {
    // get parent cats
    $parent_cats = JoomHelper::getCategories($item->catid, 'parents', true, false);

    $where_array = [];

    foreach($parent_cats as $cat)
    {
      array_push($where_array, 'id = ' . $cat['id']);
    }

    $db = $this->getDatabase();

    // get all states of the parent cats
    $query = $db->getQuery(true);
    $query->select($db->quoteName(['published', 'hidden','exclude_search']));
    $query->from($db->quoteName('#__joomgallery_categories'));
    $query->where($where_array, 'OR');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    // add parent states to item object
    foreach($results as $res)
    {
      if($res->hidden != 0)
      {
        // one of the parent categories is hidden
        $item->cat_inhidden = 1;
      }

      if($res->exclude_search != 0)
      {
        // one of the parent categories is excluded from search
        $item->cat_inexcluded = 1;
      }

      if($res->published < 1)
      {
        // one of the parent categories has a publish state which is not 1 or 2
        $item->cat_state = 0;
      }
    }

    return $item;
  }

  /**
   * Method to update item object with access from parent categories
   *
   * @param   integer  $catid  ID of the child category
   *
   * @return  integer  max access value of any parent category
   *
   * @since   1.0
   */
  protected function getParentCatAccess($catid)
  {
    // get parent cats
    $parent_cats = JoomHelper::getCategories($catid, 'parents', true, false);

    $where_array = [];

    foreach($parent_cats as $cat)
    {
      array_push($where_array, 'id = ' . $cat['id']);
    }

    $db = $this->getDatabase();

    // get all states of the parent cats
    $query = $db->getQuery(true);
    $query->select($db->quoteName(['access']));
    $query->from($db->quoteName('#__joomgallery_categories'));
    $query->where($where_array, 'OR');
    $db->setQuery($query);
    $results = $db->loadObjectList();

    // add parent states to item object
    $cat_access = 1;

    foreach($results as $res)
    {
      $cat_access = max($cat_access, $res->access);
    }

    return $cat_access;
  }

  /**
   * Method to get associated tags for a given image
   *
   * @param   integer  $imgId  ID of the image
   *
   * @return  array    List of tags
   *
   * @since   4.4
   */
  protected function getTagsForImage(int $imgId): array
  {
    $db    = $this->getDatabase();
    $query = $db->getQuery(true)
      ->select('t.id, t.title, t.access, t.published, t.language')
      ->from($db->quoteName('#__joomgallery_tags', 't'))
      ->join('INNER', $db->quoteName('#__joomgallery_tags_ref', 'ref') . ' ON ' . $db->quoteName('ref.tagid') . ' = ' . $db->quoteName('t.id'))
      ->where($db->quoteName('ref.imgid') . ' = ' . (int) $imgId)
      ->order($db->quoteName('t.title') . ' ASC');

    $db->setQuery($query);

    return $db->loadObjectList() ?: [];
  }
}
