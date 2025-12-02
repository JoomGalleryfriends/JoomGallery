<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Field\ListField;
use \Joomla\CMS\HTML\HTMLHelper;
use \Joomla\CMS\Language\Text;
use \Joomla\Database\DatabaseInterface;

class UserdropdownField extends ListField
{
  /**
   * A flexible category list that respects access controls
   *
   * @var    string
   * @since  4.0.0
   */
  public $type = 'userdropdown';

  /**
   * Method to get a list of categories that respects access controls and can be used for
   * either category assignment or parent category assignment in edit screens.
   * Use the parent element to indicate that the field will be used for assigning parent categories.
   *
   * @return  array  The field option objects.
   *
   * @since   4.0.0
   */
  protected function getOptions()
  {
    // Get selected parameters.
    $usergroup    = $this->getAttribute('usergroup', '');
    $ordering     = $this->getAttribute('ordering', 'name');
    $dropdownname = $this->getAttribute('dropdownname', 'both');
    $multiple     = $this->getAttribute('multiple', 'false');

    // Get a db connection.
    $db = Factory::getContainer()->get(DatabaseInterface::class);

    // Create a new query object.
    $query = $db->getQuery(true);

    // Select all records from the user profile table where usergroup is the selected usergroup.
    $query->select($db->quoteName(['u.id', 'u.name', 'u.username']));
    $query->from($db->quoteName('#__users', 'u'));

    // Don't compare usergroup when "all"-option is selected.
    if ($usergroup != '')
    {
      $query->join('INNER', $db->quoteName('#__user_usergroup_map', 'm') . ' ON (' . $db->quoteName('u.id') . ' = ' . $db->quoteName('m.user_id') . ')');
      $query->where(($db->quoteName('m.group_id')) .'='. $usergroup);
    }
    // Group by id to show user once in dropdown.
    $query->group($db->quoteName(['u.id']));

    switch ($ordering)
    {
      case 'id':
        $query->order('u.id ASC');
        break;
      case 'username':
        $query->order('u.username ASC');
        break;
      case 'name':
      default:
        $query->order('u.name ASC');
        break;
    }

    // Reset the query using our newly populated query object.
    $db->setQuery($query);

    // Load the results as a list of stdClass objects.
    $results = $db->loadObjectList();

    // Prepare the empty array
    $options = [];

    // "Please select" option when parameter multiple is false.
    if($multiple == 'false')
    {
      $options[] = HTMLHelper::_('select.option', '', Text::_('COM_JOOMGALLERY_FIELDS_SELECT_OWNER'));
    }

    foreach($results as $result)
    {
      switch($dropdownname)
      {
          case 'name':
            $options[] = HTMLHelper::_('select.option', $result->id, $result->name);
            break;
          case 'username':
            $options[] = HTMLHelper::_('select.option', $result->id, $result->username);
            break;
          case 'both':
          default:
            $options[] = HTMLHelper::_('select.option', $result->id, $result->name.' ('. $result->username .')');
            break;
        }
    }

    return $options;
  }

}
