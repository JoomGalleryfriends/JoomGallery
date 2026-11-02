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
 * Component implementation of UserProfileServiceInterface.
 *
 * @package JoomGallery
 * @since  DEPLOY_VERSION
 */
trait UserProfileServiceTrait
{
  /**
   * Storage for the user profile helper class.
   *
   * @var    UserProfileInterface|null
   *
   * @since  DEPLOY_VERSION
   */
  private $userProfile = null;

  /**
   * Create the user-profile lifecycle service.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function createUserProfile(): void
  {
    $this->userProfile = new UserProfile($this->getMVCFactory());
  }

  /**
   * Return the user-profile lifecycle service.
   *
   * @return  UserProfileInterface  User-profile lifecycle service instance.
   *
   * @since   DEPLOY_VERSION
   */
  public function getUserProfile(): UserProfileInterface
  {
    if($this->userProfile === null)
    {
      $this->createUserProfile();
    }

    return $this->userProfile;
  }
}
