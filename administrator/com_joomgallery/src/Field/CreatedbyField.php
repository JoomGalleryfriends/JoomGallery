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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\User\UserFactoryInterface;

/**
 * Supports an HTML select list of categories
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CreatedbyField extends FormField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'createdby';

  /**
   * Method to get the field input markup.
   *
   * @return  string    The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    // Initialize variables.
    $html = [];

    // Load user
    $user_id = $this->value;

    if($user_id)
    {
      $user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id);
    }
    else
    {
      $user   = Factory::getApplication()->getIdentity();
      $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $user->id . '" />';
    }

    if(!$this->hidden)
    {
      $html[] = '<div>' . $user->name . ' (' . $user->username . ')</div>';
    }

    return implode($html);
  }
}
