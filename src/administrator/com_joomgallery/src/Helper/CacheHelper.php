<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Database\DatabaseInterface;

/**
 * Cache invalidation comparisons and LFU usage bookkeeping
 *
 * Compares configuration parameters and permission rules without treating JSON
 * formatting or object-key order as changes. Tracks usage separately from cached
 * values for LFU eviction with aging and least-recently-used tie-breaking.
 *
 * @package    JoomGallery
 * @since      4.5.0
 */
final class CacheHelper
{
  /**
   * Normalises JSON-compatible values for structural comparison
   *
   * Decodes JSON strings and objects, recursively sorts array keys, and
   * returns an empty array for unsupported or invalid values.
   *
   * @param   mixed  $value  the JSON string, object or array to normalise
   *
   * @return  array
   *
   * @since   4.5.0
   */
  public static function json($value): array
  {
    if(\is_string($value)) $value = json_decode($value, true);

    if(\is_object($value)) $value = json_decode(json_encode($value), true);
    $value                        = \is_array($value) ? $value : [];

    foreach($value as &$child)
    {
      if(\is_array($child) || \is_object($child)) $child = self::json($child);
    }
    unset($child);
    ksort($value);

    return $value;
  }

  /**
   * Loads a persisted row by its primary key
   *
   * Returns an empty array for a zero key or a missing record.
   *
   * @param   DatabaseInterface   $db      the database connection used to read persisted inputs
   * @param   string              $table   the table name, optionally containing the Joomla prefix placeholder
   * @param   int                 $id      the primary key of the record
   * @param   string              $key     the primary-key column name
   *
   * @return  array
   *
   * @since   4.5.0
   */
  public static function row($db, string $table, int $id, string $key = 'id'): array
  {
    if(!$id) return [];

    $query = $db->getQuery(true)->select('*')->from($db->quoteName($table))
      ->where($db->quoteName($key) . ' = ' . $id);

    return $db->setQuery($query)->loadAssoc() ?: [];
  }

  /**
   * Returns the category IDs affected by a deletion
   *
   * Includes descendants when requested and the category exists. Otherwise
   * returns the supplied category ID.
   *
   * @param   DatabaseInterface  $db        the database connection used to read persisted inputs
   * @param   int                $id        the primary key of the record
   * @param   bool               $children  whether to include descendant categories
   *
   * @return  int[]
   *
   * @since   4.5.0
   */
  public static function categoryIds($db, int $id, bool $children): array
  {
    $row = self::row($db, '#__joomgallery_categories', $id);

    if(!$children || !$row) return [$id];

    $query = $db->getQuery(true)->select($db->quoteName('id'))
      ->from($db->quoteName('#__joomgallery_categories'))
      ->where($db->quoteName('lft') . ' >= ' . (int) $row['lft'])
      ->where($db->quoteName('rgt') . ' <= ' . (int) $row['rgt']);

    return array_map('intval', $db->setQuery($query)->loadColumn());
  }

  /**
   * Captures persisted configuration and ACL dependencies
   *
   * Configuration dependencies include category parents and image categories.
   * Category snapshots also include owners, category parents and the parent links
   * of category/image permission assets. Image snapshots omit ACL dependencies
   * because image-specific decisions are request-local. Full records are
   * retained for configuration sets.
   *
   * @param   DatabaseInterface  $db    the database connection used to read persisted inputs
   * @param   string             $type  the gallery record type: config, category or image
   * @param   int[]              $ids   the record IDs to include in the snapshot
   *
   * @return  array
   *
   * @since   4.5.0
   */
  public static function gallery($db, string $type, array $ids): array
  {
    $tables   = ['config' => '#__joomgallery_configs', 'category' => '#__joomgallery_categories', 'image' => '#__joomgallery'];
    $snapshot = ['params' => [], 'rules' => [], 'records' => [], 'owners' => [], 'parents' => [], 'inheritance' => []];
    $names    = [];

    foreach($ids as $id)
    {
      $row = self::row($db, $tables[$type], (int) $id);

      if($type === 'config' && $row) $snapshot['records'][$id] = $row;
      $params                                                  = self::json($row['params'] ?? []);

      if($params) $snapshot['params'][$id] = $params;

      if($type !== 'config' && $row)
      {
        $parent                       = $type === 'category' ? 'parent_id' : 'catid';
        $snapshot['inheritance'][$id] = (int) ($row[$parent] ?? 0);
      }

      if($type === 'category' && $row)
      {
        $snapshot['owners'][$id]                      = (int) ($row['created_by'] ?? 0);
        $snapshot['parents']['category.' . (int) $id] = (int) ($row['parent_id'] ?? 0);
      }

      if($type !== 'image')
      {
        $names[] = 'com_joomgallery.' . $type . '.' . (int) $id;

        if($type === 'category') $names[] = 'com_joomgallery.image.' . (int) $id;
      }
    }

    if($names)
    {
      $query = $db->getQuery(true)->select($db->quoteName(['name', 'rules', 'parent_id']))
        ->from($db->quoteName('#__assets'))
        ->where($db->quoteName('name') . ' IN (' . implode(',', array_map([$db, 'quote'], $names)) . ')');

      foreach($db->setQuery($query)->loadAssocList() as $asset)
      {
        $rules = self::json($asset['rules']);

        if($rules) $snapshot['rules'][$asset['name']] = $rules;
        $snapshot['parents'][$asset['name']]          = (int) $asset['parent_id'];
      }
      ksort($snapshot['rules']);
    }

    foreach(['owners', 'parents', 'inheritance'] as $dependency)
    {
      ksort($snapshot[$dependency]);
    }

    return $snapshot;
  }

  /**
   * Determines which scopes changed between gallery snapshots
   *
   * Configuration-set stores always invalidate config. Category and image
   * parameters and configuration inheritance links invalidate config, including
   * moves saved individually or in batches. Permission rules, category ownership
   * and ACL inheritance relationships invalidate ACL across all visitors.
   *
   * @param   string  $type    the gallery record type: config, category or image
   * @param   array   $before  the persisted values before the operation
   * @param   array   $after   the persisted values after the operation
   * @param   bool    $stored  whether the configuration-set store succeeded
   *
   * @return  string[]
   *
   * @since   4.5.0
   */
  public static function galleryScopes(string $type, array $before, array $after, bool $stored = false): array
  {
    $scopes = [];

    if( ($type === 'config' && ($stored || $before['records'] !== $after['records'])) ||
        ($type !== 'config' && ($before['params'] !== $after['params'] || $before['inheritance'] !== $after['inheritance']))
      )
    {
      $scopes[] = 'config';
    }

    if( $type !== 'image' &&
        ($before['rules'] !== $after['rules'] || $before['owners'] !== $after['owners'] || $before['parents'] !== $after['parents'])
      )
    {
      $scopes[] = 'acl';
    }

    return $scopes;
  }

  /**
   * Checks whether a component menu item points to JoomGallery
   *
   * @param   array  $row  the persisted menu record
   *
   * @return  bool
   *
   * @since   4.5.0
   */
  public static function isGalleryMenu(array $row): bool
  {
    if(($row['type'] ?? '') !== 'component') return false;
    parse_str((string) parse_url($row['link'] ?? '', PHP_URL_QUERY), $query);

    return ($query['option'] ?? '') === 'com_joomgallery';
  }

  /**
   * Determines the scopes affected by a core table change
   *
   * The caller must restrict asset rows to root or JoomGallery permissions and
   * extension rows to the JoomGallery component. User rows do not invalidate
   * either scope.
   *
   * @param   string  $kind    the core table kind: assets, extensions, menu, usergroups or viewlevels
   * @param   array   $before  the persisted values before the operation
   * @param   array   $after   the persisted values after the operation
   *
   * @return  string[]
   *
   * @since   4.5.0
   */
  public static function coreScopes(string $kind, array $before, array $after): array
  {
    if($kind === 'assets')
    {
      $changed = self::json($before['rules'] ?? []) !== self::json($after['rules'] ?? [])
        || (int) ($before['parent_id'] ?? 0) !== (int) ($after['parent_id'] ?? 0)
        || (string) ($before['name'] ?? '') !== (string) ($after['name'] ?? '');

      return $changed ? ['acl'] : [];
    }

    if($kind === 'extensions')
    {
      $old = self::json($before['params'] ?? []);
      $new = self::json($after['params'] ?? []);

      foreach(['inheritance_config' => 'default', 'save_history' => '0'] as $field => $default)
      {
        if((string) ($old[$field] ?? $default) !== (string) ($new[$field] ?? $default)) return ['config'];
      }

      return [];
    }

    if($kind === 'menu')
    {
      return $before !== $after && (self::isGalleryMenu($before) || self::isGalleryMenu($after)) ? ['config'] : [];
    }

    return \in_array($kind, ['usergroups', 'viewlevels'], true) && $before !== $after ? ['acl'] : [];
  }

  /**
   * Retains usage metadata for existing values and initialises older entries.
   * Array order records least to most recent use for equal-hit eviction ties.
   * Values stay separate from metadata; entries without metadata start at one hit.
   */
  public static function reconcileUsage(array $items, array $usage): array
  {
    $usage = array_intersect_key($usage, $items);

    foreach($usage as &$entry)
    {
      $entry         = \is_array($entry) ? $entry : [];
      $entry['hits'] = max(1, (int) ($entry['hits'] ?? 1));
    }
    unset($entry);

    foreach($items as $key => $value)
    {
      $usage[$key] ??= ['hits' => 1];
    }

    return $usage;
  }

  /** Records one retrieval, preserving metadata such as absolute expiration. */
  public static function recordHit(array &$usage, string|int $key): void
  {
    $entry = $usage[$key] ?? ['hits' => 1];

    if($entry['hits'] < PHP_INT_MAX) $entry['hits']++;

    unset($usage[$key]);
    $usage[$key] = $entry;
  }

  /** Ages surviving counters once when a new insertion encounters a full cache. */
  public static function ageUsage(array &$usage): void
  {
    foreach($usage as &$entry)
    {
      $entry['hits'] = max(1, (int) floor($entry['hits'] / 1.5));
    }
    unset($entry);
  }

  /** Chooses the lowest count, retaining the oldest access on equal counts. */
  public static function evictionKey(array $usage): string|int|null
  {
    $victim = null;
    $hits   = PHP_INT_MAX;

    foreach($usage as $key => $entry)
    {
      if($victim === null || $entry['hits'] < $hits)
      {
        $victim = $key;
        $hits   = $entry['hits'];
      }
    }

    return $victim;
  }

  /** Values without an expiration field remain valid until explicitly removed. */
  public static function isExpired(mixed $value): bool
  {
    return \is_array($value) && isset($value['expires']) && (int) $value['expires'] < time();
  }
}
