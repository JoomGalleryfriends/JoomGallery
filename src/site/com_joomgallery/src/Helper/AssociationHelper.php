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
use Joomla\CMS\Language\LanguageHelper;
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

    $imageLanguage = null;

    if($view === 'image')
    {
      $db    = Factory::getDbo();
      $query = $db->getQuery(true)
        ->select($db->quoteName('language'))
        ->from($db->quoteName('#__joomgallery'))
        ->where($db->quoteName('id') . ' = :id')
        ->bind(':id', $id, ParameterType::INTEGER);

      $db->setQuery($query);
      $imageLanguage = $db->loadResult();
    }

    $categoryLanguage = null;

    if($view === 'category')
    {
      $db    = Factory::getDbo();
      $query = $db->getQuery(true)
        ->select($db->quoteName('language'))
        ->from($db->quoteName('#__joomgallery_categories'))
        ->where($db->quoteName('id') . ' = :id')
        ->bind(':id', $id, ParameterType::INTEGER);

      $db->setQuery($query);
      $categoryLanguage = $db->loadResult();
    }

    // Handle language-neutral categories.
    if($view === 'category' && $categoryLanguage === '*')
    {
      $db = Factory::getDbo();

      $query = $db->getQuery(true)
        ->select($db->quoteName('id'))
        ->from($db->quoteName('#__joomgallery_categories'))
        ->where($db->quoteName('id') . ' = :id')
        ->where($db->quoteName('language') . ' = ' . $db->quote('*'))
        ->where($db->quoteName('published') . ' = 1')
        ->where($db->quoteName('hidden') . ' = 0')
        ->where($db->quoteName('in_hidden') . ' = 0')
        ->bind(':id', $id, ParameterType::INTEGER);

      $db->setQuery($query);
      $categoryId = (int) $db->loadResult();

      if($categoryId !== $id)
      {
        return [];
      }

      $return = [];

      foreach(LanguageHelper::getContentLanguages() as $contentLanguage)
      {
        $language = $contentLanguage->lang_code;

        $return[$language] = JoomHelper::getViewRoute(
            'category',
            $id,
            0,
            null,
            null,
            $language
        );
      }

      return $return;
    }

    // Handle language-neutral images.
    if($view === 'image' && $imageLanguage === '*')
    {
      $db = Factory::getDbo();

      $query = $db->getQuery(true)
        ->select(
            [
              $db->quoteName('image.id'),
              $db->quoteName('image.catid'),
            ]
        )
        ->from($db->quoteName('#__joomgallery', 'image'))
        ->innerJoin(
            $db->quoteName('#__joomgallery_categories', 'category') .
            ' ON ' . $db->quoteName('category.id') . ' = ' . $db->quoteName('image.catid')
        )
        ->where($db->quoteName('image.id') . ' = :id')
        ->where($db->quoteName('image.language') . ' = ' . $db->quote('*'))
        ->where($db->quoteName('image.published') . ' = 1')
        ->where($db->quoteName('image.hidden') . ' = 0')
        ->where($db->quoteName('category.published') . ' = 1')
        ->where($db->quoteName('category.hidden') . ' = 0')
        ->where($db->quoteName('category.in_hidden') . ' = 0')
        ->bind(':id', $id, ParameterType::INTEGER);

      $db->setQuery($query);
      $image = $db->loadObject();

      if(!$image || (int) $image->id !== $id)
      {
        return [];
      }

      $catid = (int) $image->catid;

      $return = [];

      foreach(LanguageHelper::getContentLanguages() as $contentLanguage)
      {
        $language = $contentLanguage->lang_code;

        $return[$language] = JoomHelper::getViewRoute(
            'image',
            $id,
            $catid,
            null,
            null,
            $language
        );
      }

      return $return;
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
            $language
        );
      }
      else
      {
        $targetId = (int) $item->id;

        $db    = Factory::getDbo();
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
            $language
        );
      }

      $return[$language] = $link;
    }

    return $return;
  }
}
