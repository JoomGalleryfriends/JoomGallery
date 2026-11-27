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
 * Lifecycle operations for JoomGallery user profiles.
 *
 * @package JoomGallery
 * @since  DEPLOY_VERSION
 */
interface UserProfileInterface
{
  /**
   * Ensure eligible Joomla users have a JoomGallery profile.
   *
   * @param   int|null  $cmsuser  Optional Joomla user ID. If omitted, all eligible users are reconciled.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function ensure(?int $cmsuser = null): void;

  /**
   * Delete a Joomla user's gallery profile and its dependent content.
   *
   * @param   int  $cmsuser  Joomla user ID whose JoomGallery profile should be removed.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function delete(int $cmsuser): void;

  /**
   * Parse a comma-separated list of Joomla user IDs and inclusive ranges.
   *
   * @param   string  $value  Comma-separated Joomla user IDs and inclusive ID ranges.
   *
   * @return  array  List of two-element arrays containing the start and end ID of each range.
   *
   * @since   DEPLOY_VERSION
   */
  public static function parseIds(string $value): array;
}
