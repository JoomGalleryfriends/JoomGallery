<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Cache;

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Shared cache revisions, read once per scope per request.
 *
 * Database errors deliberately propagate: accepting an unverified session cache
 * or silently losing an invalidation could retain revoked permissions.
 *
 * @since 4.4.0
 */
final class CacheRevision
{
  /** @var array<string, string> */
  private static array $revisions = [];

  public static function get(string $scope): string
  {
    self::validateScope($scope);

    if(!isset(self::$revisions[$scope]))
    {
      $db       = Factory::getContainer()->get(DatabaseInterface::class);
      $query    = $db->getQuery(true)
        ->select($db->quoteName('revision'))
        ->from($db->quoteName('#__joomgallery_cache_revisions'))
        ->where($db->quoteName('scope') . ' = ' . $db->quote($scope));
      $revision = $db->setQuery($query)->loadResult();

      if($revision === null || !ctype_digit((string) $revision))
      {
        throw new \RuntimeException('JoomGallery cache revision is missing for scope: ' . $scope);
      }

      self::$revisions[$scope] = (string) $revision;
    }

    return self::$revisions[$scope];
  }

  /**
   * Atomically retire a scope across all sessions. Never reset a revision.
   * Call after the underlying change has been stored.
   */
  public static function invalidate(string $scope): void
  {
    self::validateScope($scope);
    $db    = Factory::getContainer()->get(DatabaseInterface::class);
    $query = $db->getQuery(true)
      ->update($db->quoteName('#__joomgallery_cache_revisions'))
      ->set($db->quoteName('revision') . ' = ' . $db->quoteName('revision') . ' + 1')
      ->where($db->quoteName('scope') . ' = ' . $db->quote($scope));

    $db->setQuery($query)->execute();

    if($db->getAffectedRows() !== 1)
    {
      throw new \RuntimeException('JoomGallery cache revision could not be advanced: ' . $scope);
    }

    unset(self::$revisions[$scope]);
    self::get($scope);
  }

  private static function validateScope(string $scope): void
  {
    if(!\in_array($scope, ['config', 'acl'], true))
    {
      throw new \InvalidArgumentException('Unknown JoomGallery cache scope: ' . $scope);
    }
  }
}
