<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
$rectorConfig->paths(
    [
    __DIR__ . '/src/administrator/com_joomgallery/src',
    __DIR__ . '/src/plugins',
    __DIR__ . '/src/site/com_joomgallery/src',
    ]
);

  // Joomla is analysis-only and is intentionally excluded from Rector's paths.
$rectorConfig->autoloadPaths(
    [
    __DIR__ . '/joomla',
    __DIR__ . '/src/administrator/com_joomgallery/vendor/autoload.php',
    ]
);

$rectorConfig->sets(
    [
    LevelSetList::UP_TO_PHP_81,
    SetList::CODE_QUALITY,
    __DIR__ . '/vendor/joomla-projects/typehints/rector/joomla_5_0.php',
    ]
);
};
