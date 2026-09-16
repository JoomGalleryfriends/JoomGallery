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

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseInterface;

/**
 * Database-backed revisions shared across user sessions
 *
 * Reads each scope once per request and keeps revisions as decimal strings.
 * Database failures propagate rather than accepting unverified cached
 * permissions.
 *
 * @package    JoomGallery
 * @since      4.4.0
 */
final class CacheRevision
{
  /**
   * Database connection used by this shared revision instance
   *
   * @var     DatabaseInterface
   * @since   4.5.0
   */
  private DatabaseInterface $db;

  /**
   * Initialises the shared revision store
   *
   * @param   DatabaseInterface  $db  the database connection
   *
   * @return  void
   * @since   4.5.0
   */
  public function __construct(DatabaseInterface $db)
  {
    $this->db = $db;
  }

  /**
   * Decimal revision strings memoized by scope during the request
   *
   * @var     array<string, string>
   * @access  private
   *
   * @since   4.4.0
   */
  private array $revisions = [];

  /**
   * Returns the memoized database revision for a scope
   *
   * Reads the database on the first access during a request. Revision strings
   * are never converted to PHP integers.
   *
   * @param   string  $scope  the supported revision scope: config or acl
   *
   * @return  string
   *
   * @throws  \InvalidArgumentException  If a scope is unsupported.
   * @throws  \RuntimeException          If revision storage cannot be read or updated.
   * @since   4.4.0
   */
  public function get(string $scope): string
  {
    $this->validateScope($scope);

    if(!isset($this->revisions[$scope]))
    {
      $db       = $this->db;
      $query    = $db->getQuery(true)
        ->select($db->quoteName('revision'))
        ->from($db->quoteName('#__joomgallery_cache_revisions'))
        ->where($db->quoteName('scope') . ' = ' . $db->quote($scope));
      $revision = $db->setQuery($query)->loadResult();

      if($revision === null || !ctype_digit((string) $revision))
      {
        throw new \RuntimeException('JoomGallery cache revision is missing for scope: ' . $scope);
      }

      $this->revisions[$scope] = (string) $revision;
    }

    return $this->revisions[$scope];
  }

  /**
   * Atomically increments a revision across all sessions
   *
   * Refreshes the request-local revision after the update. Counters must never
   * be reset or reused.
   *
   * @param   string  $scope  the supported revision scope: config or acl
   *
   * @return  void
   *
   * @throws  \InvalidArgumentException  If a scope is unsupported.
   * @throws  \RuntimeException          If revision storage cannot be read or updated.
   * @since   4.4.0
   */
  public function invalidate(string $scope): void
  {
    $this->validateScope($scope);
    $db    = $this->db;
    $query = $db->getQuery(true)
      ->update($db->quoteName('#__joomgallery_cache_revisions'))
      ->set($db->quoteName('revision') . ' = ' . $db->quoteName('revision') . ' + 1')
      ->where($db->quoteName('scope') . ' = ' . $db->quote($scope));

    $db->setQuery($query)->execute();

    if($db->getAffectedRows() !== 1)
    {
      throw new \RuntimeException('JoomGallery cache revision could not be advanced: ' . $scope);
    }

    unset($this->revisions[$scope]);
    $this->get($scope);
  }

  /**
   * Rejects scope names outside the supported revision groups
   *
   * Only config and acl are supported.
   *
   * @param   string  $scope  the supported revision scope: config or acl
   *
   * @return  void
   *
   * @throws  \InvalidArgumentException  If a scope is unsupported.
   * @since   4.4.0
   */
  private function validateScope(string $scope): void
  {
    if(!\in_array($scope, ['config', 'acl'], true))
    {
      throw new \InvalidArgumentException('Unknown JoomGallery cache scope: ' . $scope);
    }
  }
}
