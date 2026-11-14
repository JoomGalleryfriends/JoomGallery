<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

\defined('_JEXEC') || die;

use Joomgallery\Component\Joomgallery\Administrator\Table\CategoryTable;
use Joomgallery\Component\Joomgallery\Administrator\Table\ImageTable;
use Joomgallery\Component\Joomgallery\Site\Helper\AssociationHelper;
use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;

/**
 * Association helper for JoomGallery content.
 *
 * Provides Joomla core multilingual association support for JoomGallery.
 *
 * @category JoomGallery
 * @package  Com_Joomgallery
 * @author   JoomGallery::ProjectTeam <team@joomgalleryfriends.net>
 * @license  GNU General Public License version 3 or later
 * @link     https://www.joomgalleryfriends.net
 * @since    4.5.0
 */
class AssociationsHelper extends AssociationExtensionHelper
{
  protected $extension = 'com_joomgallery';

  protected $itemTypes = ['jgcategory', 'image'];

  protected $associationsSupport = true;

  /**
   * Get a JoomGallery item by its type and ID.
   *
   * @param string $typeName The item type (jgcategory or image).
   * @param int    $id       The item ID.
   *
   * @return object|null The loaded item, or null if not found.
   *
   * @since 4.5.0
   */
  public function getItem($typeName, $id)
  {
    if(empty($id))
    {
      return null;
    }

    $table = match ($typeName) {
      'jgcategory' => new CategoryTable(Factory::getDbo()),
      'image'      => new ImageTable(Factory::getDbo()),
      default      => null,
    };

    if($table === null || !$table->load($id))
    {
      return null;
    }

    return $table;
  }

  /**
   * Get the associations for a JoomGallery item.
   *
   * @param int         $id   The item ID.
   * @param string|null $view The view name.
   *
   * @return array The associated items.
   *
   * @since 4.5.0
   */
  public function getAssociationsForItem($id = 0, $view = null)
  {
    return AssociationHelper::getAssociations($id, $view);
  }

  /**
   * Get the associations for a category or image.
   *
   * @param string $typeName The association type.
   * @param int    $id       The item ID.
   *
   * @return array The associated items or an empty array.
   *
   * @since 4.5.0
   */
  public function getAssociations($typeName, $id)
  {
    if($typeName === 'jgcategory')
    {
      return Associations::getAssociations(
        $this->extension,
        '#__joomgallery_categories',
        'com_joomgallery.category',
        $id,
        'id',
        '',
        ''
      );
    }

    if($typeName === 'image')
    {
      return Associations::getAssociations(
        $this->extension,
        '#__joomgallery',
        'com_joomgallery.image',
        $id,
        'id',
        '',
        ''
      );
    }

    return [];
  }

  /**
   * Get the association type configuration.
   *
   * @param string $typeName The association type.
   *
   * @return array The association type configuration.
   *
   * @since 4.5.0
   */
  public function getType($typeName = '')
  {
    if($typeName === 'jgcategory')
    {
      $fields             = $this->getFieldsTemplate();
      $fields['ordering'] = '';
      $fields['catid']    = '';
      $fields['title']    = 'a.title';
      $support            = $this->getSupportTemplate();

      $fields['state'] = 'a.published';

      $support['state']    = true;
      $support['acl']      = true;
      $support['checkout'] = true;

      return [
        'fields'  => $fields,
        'support' => $support,
        'tables'  => [
          'a' => '#__joomgallery_categories',
        ],
        'joins' => [],
        'title' => 'category',
      ];
    }

    if($typeName === 'image')
    {
      $fields          = $this->getFieldsTemplate();
      $fields['title'] = 'a.title';
      $support         = $this->getSupportTemplate();

      $fields['state'] = 'a.published';

      $support['state']    = true;
      $support['acl']      = true;
      $support['checkout'] = true;

      return [
        'fields'  => $fields,
        'support' => $support,
        'tables'  => [
          'a' => '#__joomgallery',
        ],
        'joins' => [],
        'title' => 'image',
      ];
    }

    return [
      'fields'  => [],
      'support' => [],
      'tables'  => [],
      'joins'   => [],
      'title'   => '',
    ];
  }
}
