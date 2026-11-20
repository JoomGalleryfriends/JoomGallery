<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\UserProfile;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Component contract for the user-profile lifecycle service.
 *
 * @package JoomGallery
 * @since  DEPLOY_VERSION
 */
interface UserProfileServiceInterface
{
  /**
   * Create the user-profile lifecycle service.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function createUserProfile(): void;

  /**
   * Return the user-profile lifecycle service.
   *
   * @return  UserProfileInterface  User-profile lifecycle service instance.
   *
   * @since   DEPLOY_VERSION
   */
  public function getUserProfile(): UserProfileInterface;
}
