<?php
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

use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Language\Associations;

class AssociationsHelper extends AssociationExtensionHelper
{
  protected $extension = 'com_joomgallery';

  protected $itemTypes = ['category'];

  protected $associationsSupport = true;

  public function getAssociationsForItem($id = 0, $view = null)
  {
    return $this->getAssociations('category', $id);
  }

  public function getAssociations($typeName, $id)
  {
    if($typeName !== 'category')
    {
      return [];
    }

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

  public function getType($typeName = '')
  {
    if($typeName !== 'category')
    {
      return [
        'fields'  => [],
        'support' => [],
        'tables'  => [],
        'joins'   => [],
        'title'   => '',
      ];
    }

    $fields  = $this->getFieldsTemplate();
    $support = $this->getSupportTemplate();

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
}