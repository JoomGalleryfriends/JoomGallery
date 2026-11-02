<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\User;

\defined('_JEXEC') || die;

use Joomla\CMS\Factory;
use Joomla\CMS\Table\Asset;
use Joomla\Database\DatabaseInterface;
use Joomla\Registry\Registry;

/** Shared, idempotent gallery profile provisioning and account cleanup. */
class ProfileLifecycle
{
  private DatabaseInterface $db;
  private Registry $params;

  public function __construct(DatabaseInterface $db, Registry $params)
  {
    $this->db = $db;
    $this->params = $params;
  }

  /** Read the global gallery configuration, independent of the acting user's groups. */
  public static function fromConfiguration(DatabaseInterface $db): self
  {
    $query = $db->getQuery(true)
      ->select($db->quoteName(['jg_create_user', 'jg_create_user_groups', 'jg_create_user_ids']))
      ->from($db->quoteName('#__joomgallery_configs'))->where('id = 1');
    $config = $db->setQuery($query)->loadObject();
    if(!$config)
    {
      throw new \RuntimeException('The global JoomGallery configuration is missing.');
    }

    return new self($db, new Registry([
      'create_user' => $config->jg_create_user,
      'create_user_groups' => explode(',', (string) $config->jg_create_user_groups),
      'create_user_ids' => $config->jg_create_user_ids,
    ]));
  }
  /** Parse ranges without expanding them into potentially enormous ID lists. */
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
      $end = filter_var($match[2] ?? $match[1], FILTER_VALIDATE_INT);
      if($start === false || $end === false || $end < $start || $end > 4294967295)
      {
        throw new \InvalidArgumentException('Invalid Create User ID range.');
      }
      $ranges[] = [$start, $end];
    }

    return $ranges;
  }

  /** Ensure eligible profiles exist. A null ID backfills all existing accounts. */
  public function ensure(?int $cmsuser = null): void
  {
    $db = $this->db;
    $query = $db->getQuery(true)->select('u.id')->from($db->quoteName('#__users', 'u'))
      ->where('u.id > 0');
    if($cmsuser !== null)
    {
      $query->where('u.id = ' . (int) $cmsuser);
    }

    switch($this->params->get('create_user', 'all'))
    {
      case 'all':
        break;
      case 'groups':
        $groups = array_filter(array_map('intval', (array) $this->params->get('create_user_groups', [])), static fn($id) => $id > 0);
        if(!$groups)
        {
          return;
        }
        $query->where('EXISTS (SELECT 1 FROM ' . $db->quoteName('#__user_usergroup_map')
          . ' AS m WHERE m.user_id = u.id AND m.group_id IN (' . implode(',', $groups) . '))');
        break;
      case 'users':
        $ranges = self::parseIds((string) $this->params->get('create_user_ids', ''));
        if(!$ranges)
        {
          return;
        }
        $conditions = [];
        foreach($ranges as [$start, $end])
        {
          $conditions[] = 'u.id BETWEEN ' . $start . ' AND ' . $end;
        }
        $query->where('(' . implode(' OR ', $conditions) . ')');
        break;
      default:
        throw new \InvalidArgumentException('Invalid Create User policy.');
    }

    // Serialize provisioning on installations which have not yet added the unique index.
    $lock = 'jg_profiles_' . md5($db->replacePrefix('#__joomgallery_users') . (string) $db->setQuery('SELECT DATABASE()')->loadResult());
    if(!(int) $db->setQuery('SELECT GET_LOCK(' . $db->quote($lock) . ', 10)')->loadResult())
    {
      throw new \RuntimeException('Could not lock JoomGallery profile provisioning.');
    }

    try
    {
      $query->where('NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__joomgallery_users') . ' AS g WHERE g.cmsuser = u.id)');
      $query->clear('select')->select('u.id, ' . $db->quote('') . ', ' . $db->quote('{}') . ', '
        . $db->quote('{}') . ', ' . $db->quote(Factory::getDate()->format('Y-m-d H:i:s')));
      $db->setQuery('INSERT INTO ' . $db->quoteName('#__joomgallery_users')
        . ' (' . implode(',', $db->quoteName(['cmsuser', 'description', 'files', 'params', 'created_time'])) . ') ' . $query)->execute();
    }
    finally
    {
      $db->setQuery('SELECT RELEASE_LOCK(' . $db->quote($lock) . ')')->loadResult();
    }
  }

  /** Account deletion always cleans existing data, regardless of current eligibility. */
  public function delete(int $cmsuser): void
  {
    if($cmsuser <= 0)
    {
      return;
    }
    $db = $this->db;
    $db->transactionStart();
    try
    {
      $query = $db->getQuery(true)->select('c.id, c.asset_id')
        ->from($db->quoteName('#__joomgallery_collections', 'c'))
        ->join('INNER', $db->quoteName('#__joomgallery_users', 'g') . ' ON g.id = c.userid')
        ->where('g.cmsuser = ' . $cmsuser);
      foreach($db->setQuery($query)->loadObjectList() as $collection)
      {
        $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomgallery_collections_ref'))
          ->where('collectionid = ' . (int) $collection->id))->execute();
        if($collection->asset_id)
        {
          $asset = new Asset($db);
          // Never delete a shared/component asset, even if legacy data refers to it.
          if($asset->load((int) $collection->asset_id) && $asset->name === 'com_joomgallery.collection.' . $collection->id)
          {
            if(!$asset->delete())
            {
              throw new \RuntimeException('Could not delete the collection asset.');
            }
          }
        }
        $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomgallery_collections'))
          ->where('id = ' . (int) $collection->id))->execute();
      }
      $db->setQuery($db->getQuery(true)->delete($db->quoteName('#__joomgallery_users'))
        ->where('cmsuser = ' . $cmsuser))->execute();
      $db->transactionCommit();
    }
    catch(\Throwable $e)
    {
      $db->transactionRollback();
      throw $e;
    }
  }
}