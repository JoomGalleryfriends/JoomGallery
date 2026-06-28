<?php
/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\View\Categories;

use Joomla\Registry\Registry;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Serializer\JoomlaSerializer;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Router\Exception\RouteNotFoundException;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomgallery\Component\Joomgallery\Api\Helper\JoomgalleryHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The categories view
 *
 * @since  4.0.0
 */
class JsonapiView extends BaseApiView
{
  /**
   * The fields to render item in the documents
   *
   * @var  array
   * @since  4.0.0
   */
  protected $fieldsToRenderItem = [
    'id',
    'asset_id',
    'asset_id_image',
    'parent_id',
    'parent_title',
    'img_count',
    'child_count',

    'lft',
    'rgt',
    'level',

    'path',
    'title',
    'alias',
    'description',

    'published',
    'hidden',
    'in_hidden',
    'password',

    'exclude_toplist',
    'exclude_search',
    'thumbnail',
    'static_path',
    'params',
    'language',

    'created_time',
    'created_by',
    'modified_time',
    'modified_by',
    'checked_out',
    'checked_out_time',

    'metadesc',
    'metakey',
    'robots',
  ];

  /**
   * The fields to render items in the documents
   *
   * @var  array
   * @since  4.0.0
   */
  protected $fieldsToRenderList = [
    'id',
    'asset_id',
    'asset_id_image',
    'parent_id',
    'parent_title',
    'img_count',
    'child_count',

    'lft',
    'rgt',
    'level',

    'path',
    'title',
    'alias',
    'description',

    'published',
    'hidden',
    'in_hidden',
    'password',

    'exclude_toplist',
    'exclude_search',
    'thumbnail',
    'static_path',
    'params',
    'language',

    'created_time',
    'created_by',
    'modified_time',
    'modified_by',
    'checked_out',
    'checked_out_time',

    'metadesc',
    'metakey',
    'robots',
  ];

  /**
   * Constructor.
   *
   * @param   array   $config  A named configuration array for object construction.
   *                           contentType: the name (optional) of the content type to use for the serialization
   *
   * @since   4.0.0
   */
  public function __construct($config = [])
  {
    if(\array_key_exists('contentType', $config))
    {
      $this->serializer = new JoomlaSerializer($config['contentType']);
    }

    parent::__construct($config);
  }

  /**
   * Execute and display a template script.
   *
   * @param   ?array   $items  Array of items
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function displayList(?array $items = null)
  {
    foreach(FieldsHelper::getFields('com_joomgallery.categories') as $field)
    {
      $this->fieldsToRenderList[] = $field->name;
    }

    /** @var \Joomgallery\Component\Joomgallery\Administrator\Model\CategoriesModel $model */
    $model = $this->getModel();

    // show all
    $model->setState('filter.showself', 1);
    $model->setState('filter.showhidden', 1);
    $model->setState('filter.showempty', 1);

    return parent::displayList();
  }

  /**
   * Execute and display a template script.
   *
   * @param   object   $item  Item
   *
   * @return  string
   *
   * @since   4.0.0
   */
  public function displayItem($item = null)
  {
    foreach(FieldsHelper::getFields('com_joomgallery.categories') as $field)
    {
      $this->fieldsToRenderItem[] = $field->name;
    }

    if(Multilanguage::isEnabled())
    {
      $this->fieldsToRenderItem[] = 'languageAssociations';
      $this->relationship[]       = 'languageAssociations';
    }

    return parent::displayItem();
  }

  /**
   * Prepare item before render.
   *
   * @param   object   $item  The model item
   *
   * @return  object
   *
   * @since   4.0.0
   */
  protected function prepareItem($item)
  {
    if(empty($item))
    {
      throw new RouteNotFoundException('Item does not exist');
    }

    $item->text = $item->introtext.' '.$item->fulltext;

    // Process the joomgallery plugins.
    PluginHelper::importPlugin('joomgallery');

    foreach(FieldsHelper::getFields('com_joomgallery.categories', $item, true) as $field)
    {
      $item->{$field->name} = $field->apivalue ?? $field->rawvalue;
    }

    if(isset($item->images))
    {
      $registry     = new Registry($item->images);
      $item->images = $registry->toArray();

      if(!empty($item->images['image_intro']))
      {
        $item->images['image_intro'] = JoomgalleryHelper::resolve($item->images['image_intro']);
      }

      if(!empty($item->images['image_fulltext']))
      {
        $item->images['image_fulltext'] = JoomgalleryHelper::resolve($item->images['image_fulltext']);
      }
    }

    return parent::prepareItem($item);
  }
}
