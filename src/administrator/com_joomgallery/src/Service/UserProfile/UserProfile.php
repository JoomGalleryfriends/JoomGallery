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

use Joomla\CMS\MVC\Factory\MVCFactoryInterface;

/**
 * UserProfile Class
 *
 * Coordinates the JoomGallery user-profile lifecycle
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class UserProfile implements UserProfileInterface
{
  use ServiceTrait;

  /**
   * The MVCFactory
   *
   * @var MVCFactoryInterface
   */
  private MVCFactoryInterface $mvcFactory;

  /**
   * Constructor
   *
   * @param   MVCFactoryInterface   $mvcFactory   The MVCFactory
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function __construct(MVCFactoryInterface $mvcFactory)
  {
    $this->mvcFactory = $mvcFactory;
  }

  /**
   * Parse a comma-separated list of Joomla user IDs and inclusive ranges.
   *
   * @param   string  $value  Comma-separated Joomla user IDs and inclusive ID ranges.
   *
   * @return  array  List of two-element arrays containing the start and end ID of each range.
   *
   * @since   DEPLOY_VERSION
   */
  public static function parseIds(string $value): array
  {
    if(trim($value) === '')
    {
      return [];
    }

    $ranges = [];

    foreach(explode(',', $value) as $part)
    {
      if(!preg_match('/^\s*([1-9][0-9]*)\s*(?:-\s*([1-9][0-9]*)\s*)?$/D', $part, $match))
      {
        throw new \InvalidArgumentException('Invalid Create User IDs. Use IDs or ranges, for example 3,8,20-100.');
      }

      $start = filter_var($match[1], FILTER_VALIDATE_INT);
      $end   = filter_var($match[2] ?? $match[1], FILTER_VALIDATE_INT);

      if($start === false || $end === false || $end < $start || $end > 4294967295)
      {
        throw new \InvalidArgumentException('Invalid Create User ID range.');
      }

      $ranges[] = [$start, $end];
    }

    return $ranges;
  }

  /**
   * Ensure eligible Joomla users have a JoomGallery profile.
   *
   * @param   int|null  $cmsuser  Optional Joomla user ID. If omitted, all eligible users are reconciled.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function ensure(?int $cmsuser = null): void
  {
    $configModel = $this->mvcFactory->createModel('Config', 'Administrator', ['ignore_request' => true]);
    $config      = $configModel->getItem(1);

    if(!$config)
    {
      throw new \RuntimeException('The global JoomGallery configuration is missing.');
    }

    $policy = $config->jg_create_user ?? 'all';
    $groups = array_filter(array_map('intval', explode(',', (string) ($config->jg_create_user_groups ?? ''))));
    $ranges = self::parseIds((string) ($config->jg_create_user_ids ?? ''));

    $usersModel = $this->mvcFactory->createModel('Users', 'Administrator', ['ignore_request' => true]);
    $userModel  = $this->mvcFactory->createModel('User', 'Administrator', ['ignore_request' => true]);

    foreach($usersModel->getMissingCmsUserIds($policy, $groups, $ranges, $cmsuser) as $id)
    {
      $userModel->createProfile((int) $id);
    }
  }

  /**
   * Delete a Joomla user's gallery profile and its dependent content.
   *
   * @param   int  $cmsuser  Joomla user ID whose JoomGallery profile should be removed.
   *
   * @return  void
   *
   * @since   DEPLOY_VERSION
   */
  public function delete(int $cmsuser): void
  {
    if($cmsuser <= 0)
    {
      return;
    }

    $userModel = $this->mvcFactory->createModel('User', 'Administrator', ['ignore_request' => true]);
    $profile   = $userModel->getItemByCmsUser($cmsuser);

    if(!$profile)
    {
      return;
    }

    $collectionsModel = $this->mvcFactory->createModel('Collections', 'Administrator', ['ignore_request' => true]);
    $collectionModel  = $this->mvcFactory->createModel('Collection', 'Administrator', ['ignore_request' => true]);

    foreach($collectionsModel->getIdsByUser((int) $profile->id) as $collectionId)
    {
      $collectionModel->deleteCollection((int) $collectionId);
    }

    $userModel->deleteProfile((int) $profile->id);
  }
}
