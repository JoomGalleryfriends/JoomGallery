<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */declare(strict_types=1);

use Joomla\Jorobo\Tasks\Tasks as JoroboTasks;
use Robo\Tasks;

require_once __DIR__ . '/vendor/autoload.php';

if(!defined('JPATH_BASE'))
{
  define('JPATH_BASE', __DIR__);
}

final class RoboFile extends Tasks
{
  use JoroboTasks;

  public function __construct()
  {
    date_default_timezone_set('UTC');
  }

  public function build(array $params = ['dev' => false]): void
  {
    $this->ensureLocalConfig();
    $this->task(\Joomla\Jorobo\Tasks\Build::class, $params)->run();
  }

  public function headers(): void
  {
    $this->ensureLocalConfig();
    $this->task(\Joomla\Jorobo\Tasks\CopyrightHeader::class)->run();
  }

  public function bump(): void
  {
    $this->ensureLocalConfig();
    $this->task(\Joomla\Jorobo\Tasks\BumpVersion::class)->run();
  }

  public function map(string $target = 'joomla'): void
  {
    $this->ensureLocalConfig();
    $this->task(\Joomla\Jorobo\Tasks\Map::class, $target)->run();
  }

  private function ensureLocalConfig(): void
  {
    if(!file_exists(__DIR__ . '/jorobo.ini'))
    {
      $this->_copy(__DIR__ . '/jorobo.dist.ini', __DIR__ . '/jorobo.ini');
    }
  }
}
