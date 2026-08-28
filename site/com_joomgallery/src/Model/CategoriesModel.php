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

use Joomgallery\Component\Joomgallery\Administrator\Model\CategoriesModel as AdminCategoriesModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Multilanguage;
// ToDo use ... databsequery  (?MysqliQuery)
/**
 * Model to get a list of category records.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoriesModel extends AdminCategoriesModel
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
        'lft', 'a.lft',
        'rgt', 'a.rgt',
        'level', 'a.level',
        'path', 'a.path',
        'in_hidden', 'a.in_hidden',
        'title', 'a.title',
        'alias', 'a.alias',
        'parent_id', 'a.parent_id',
        'parent_title', 'a.parent_title',
        'published', 'a.published',
        'access', 'a.access',
        'language', 'a.language',
        'description', 'a.description',
        'hidden', 'a.hidden',
        'created_time', 'a.created_time',
        'created_by', 'a.created_by',
        'modified_by', 'a.modified_by',
        'modified_time', 'a.modified_time',
        'id', 'a.id',
        'img_count', 'a.img_count',
        'child_count', 'a.child_count',
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
  protected function populateState($ordering = 'a.lft', $direction = 'ASC')
  {
    // List state information.
    parent::populateState($ordering, $direction);

    if(Multilanguage::isEnabled())
    {
      $currentLanguage  = $this->app->getLanguage()->getTag();
      $fallbackLanguage = ComponentHelper::getParams('com_joomgallery')->get('category_fallback_language', '');

      if($fallbackLanguage === '')
      {
        $fallbackLanguage = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
      }

      $languages = [$currentLanguage, '*'];

      if($fallbackLanguage !== $currentLanguage)
      {
        $languages[] = $fallbackLanguage;
      }

      $this->setState('filter.language', $languages);
    }

    // Set filters based on how the view is used.
    // e.g. user list of categories: $this->setState('filter.created_by', Factory::getApplication()->getIdentity());

    $this->loadComponentParams();
  }
  protected function getListQuery()
  {
    $query = parent::getListQuery();
    $db    = $this->getDatabase();

    if(!Multilanguage::isEnabled())
    {
      return $query;
    }

    $currentLanguage  = $this->app->getLanguage()->getTag();
    $fallbackLanguage = ComponentHelper::getParams('com_joomgallery')->get('category_fallback_language', '');

    if($fallbackLanguage === '')
    {
      $fallbackLanguage = ComponentHelper::getParams('com_languages')->get('site', 'en-GB');
    }

    if($fallbackLanguage === $currentLanguage)
    {
      return $query;
    }

    $subQuery = $db->getQuery(true)
      ->select('1')
      ->from($db->quoteName('#__associations', 'fa'))
      ->join(
        'INNER',
        $db->quoteName('#__associations', 'ca'),
        $db->quoteName('ca.key') . ' = ' . $db->quoteName('fa.key')
        . ' AND ' . $db->quoteName('ca.context') . ' = ' . $db->quoteName('fa.context')
      )
      ->join(
        'INNER',
        $db->quoteName('#__joomgallery_categories', 'cc'),
        $db->quoteName('cc.id') . ' = ' . $db->quoteName('ca.id')
      )
      ->where($db->quoteName('fa.id') . ' = ' . $db->quoteName('a.id'))
      ->where($db->quoteName('fa.context') . ' = ' . $db->quote('com_joomgallery.category'))
      ->where($db->quoteName('cc.language') . ' = ' . $db->quote($currentLanguage));

    $query->where(
      '(' . $db->quoteName('a.language') . ' != ' . $db->quote($fallbackLanguage)
      . ' OR NOT EXISTS (' . $subQuery . '))'
    );

    return $query;
  }
}