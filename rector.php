<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
  $paths = [
    __DIR__ . '/src/administrator/com_joomgallery/src',
    __DIR__ . '/src/plugins',
    __DIR__ . '/src/site/com_joomgallery/src',
  ];

  // API and CLI applications are optional, but must be analyzed when present.
  foreach(['api', 'cli'] as $application)
  {
    $path = __DIR__ . '/src/' . $application;

    if(is_dir($path))
    {
      $paths[] = $path;
    }
  }

  $rectorConfig->paths($paths);

  // Joomla is analysis-only and is intentionally excluded from Rector's paths.
  $rectorConfig->autoloadPaths([
    __DIR__ . '/joomla',
    __DIR__ . '/src/administrator/com_joomgallery/vendor/autoload.php',
  ]);

  $rectorConfig->sets([
    LevelSetList::UP_TO_PHP_81,
    SetList::CODE_QUALITY,
    __DIR__ . '/vendor/joomla-projects/typehints/rector/joomla_5_0.php',
  ]);
};
