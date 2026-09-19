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
 * Global guest cache policy read independently of calculated configurations
 *
 * @package JoomGallery
 * @since   4.5.0
 */
final class GuestCachePolicy
{
  /**
   * Global policy memoized for the current request
   *
   * @var     array|null
   * @since   4.5.0
   */
  private static ?array $policy = null;

  /**
   * Returns the shared entry limit and lifetime in seconds
   *
   * Reading the global row avoids recursive configuration service creation.
   *
   * @return  array{entries: int, lifetime: int}
   * @since   4.5.0
   */
  public static function get(): array
  {
    if(self::$policy !== null) return self::$policy;
    self::$policy = ['entries' => 4096, 'lifetime' => 3600];

    try
    {
      $db    = Factory::getContainer()->get(DatabaseInterface::class);
      $query = $db->getQuery(true)
        ->select($db->quoteName(['jg_guest_cache_entries', 'jg_guest_cache_lifetime']))
        ->from($db->quoteName('#__joomgallery_configs'))
        ->where($db->quoteName('id') . ' = 1');
      $row   = $db->setQuery($query)->loadAssoc();

      if(\is_array($row))
      {
        self::$policy = [
          'entries' => max(0, min(100000, (int) ($row['jg_guest_cache_entries'] ?? 4096))),
          'lifetime' => max(0, min(10080, (int) ($row['jg_guest_cache_lifetime'] ?? 60))) * 60,
        ];
      }
    }
    catch(\Throwable $e)
    {
      // Defaults permit installation and upgrades before new columns exist.
    }

    return self::$policy;
  }
}
