<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\User;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\User\UserFactory as BaseUserFactory;

/**
 * JoomGallery factory for creating User objects
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class UserFactory extends BaseUserFactory
{
  /**
   * Method to get an instance of a user for the given id.
   *
   * @param   int  $id  The id
   *
   * @return  User
   *
   * @since   4.0.0
   */
  public function loadUserById(int $id): User
  {
    return new User($id);
  }
}
