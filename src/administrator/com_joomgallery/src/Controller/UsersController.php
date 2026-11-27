<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Users list controller class.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class UsersController extends JoomAdminController
{
  /**
   * Proxy for getModel.
   *
   * @param   string  $name    Optional model name.
   * @param   string  $prefix  Optional model namespace prefix.
   * @param   array   $config  Optional model configuration.
   *
   * @return  object  User model instance.
   *
   * @since   DEPLOY_VERSION
   */
  public function getModel($name = 'User', $prefix = 'Administrator', $config = [])
  {
    return parent::getModel($name, $prefix, ['ignore_request' => true]);
  }
}
