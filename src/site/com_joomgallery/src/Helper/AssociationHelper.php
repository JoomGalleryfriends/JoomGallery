<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Helper;

\defined('_JEXEC') || die;

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Multilanguage;
use Joomla\Database\ParameterType;

/**
 * Frontend association helper for JoomGallery.
 */
class AssociationHelper
{
  /**
   * Get the URLs for associated categories or images.
   *
   * @param int         $id   Item ID.
   * @param string|null $view View name.
   *
   * @return array URLs indexed by language tag.
   */
  public static function getAssociations($id = 0, $view = null): array
  {
    if(!Multilanguage::isEnabled() || !Associations::isEnabled())
    {
      return [];
    }

    $input = Factory::getApplication()->getInput();

    if($view === null)
    {
      $view = $input->getCmd('view');
    }

    if(!(int) $id)
    {
      $id = $input->getInt('id');
    }

    $id = (int) $id;

    if($id < 1)
    {
      return [];
    }

    if($view === 'category')
    {
    $associations = Associations::getAssociations(
        'com_joomgallery',
        '#__joomgallery_categories',
        'com_joomgallery.category',
        $id,
        'id',
        'alias',
        ''
    );
    }
    elseif($view === 'image')
    {
    $associations = Associations::getAssociations(
        'com_joomgallery',
        '#__joomgallery',
        'com_joomgallery.image',
        $id,
        'id',
        'alias',
        'catid'
    );
    }
    else
    {
      return [];
    }

    $return = [];

    foreach($associations as $language => $item)
    {
      if(empty($item->id) || $language === '*')
      {
        continue;
      }

      if($view === 'category')
      {
        $link = JoomHelper::getViewRoute(
            'category',
            (int) $item->id,
            0,
            null,
            null,
            null
        );
      }
      else
      {
        $targetId = (int) $item->id;

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
          ->select($db->quoteName('catid'))
          ->from($db->quoteName('#__joomgallery'))
          ->where($db->quoteName('id') . ' = :id')
          ->bind(':id', $targetId, ParameterType::INTEGER);

        $db->setQuery($query);
        $catid = (int) $db->loadResult();

        $link = JoomHelper::getViewRoute(
            'image',
            $targetId,
            $catid,
            null,
            null,
            null
        );
      }

      $return[$language] = $link;
    }

    return $return;
  }
}
