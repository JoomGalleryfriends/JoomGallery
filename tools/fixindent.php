<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

require_once __DIR__ . '/../administrator/com_joomgallery/vendor/autoload.php';
use ColinODell\Indentation\Indentation;

$rootDir = realpath(__DIR__ . '/..');

// Setting
//----------------------
$indentSize = 2;
$indentType = Indentation::TYPE_SPACE;
$doFix = false;
$details = false;
$folders = ['administrator', 'site', 'plugins'];
$exclude = ['.git', 'vendor', 'includes', 'node_modules', 'cache'];
//----------------------

// If script is called with "fix" argument → enable fixing
if(isset($argv[1]) && strtolower($argv[1]) === 'fix')
{
  $doFix = true;
}

// If script is called with "details" argument → enable printing
if(isset($argv[2]) && strtolower($argv[2]) === 'details')
{
  $details = true;
}

/**
 * Remove everything before the first class/interface/trait/enum
 * including docblocks, comments, attributes, and blank lines.
 */
function stripHeader(string $content): string
{
  $lines = preg_split('/\R/', $content);

  foreach($lines as $i => $line)
  {
    // skip docblocks
    if(preg_match('/^\s*\/\*\*/', $line)) {
      continue;
    }

    if(preg_match('/^\s*\*/', $line)) {
      continue;
    }

    if(preg_match('/^\s*\*\/\s*$/', $line)) {
      continue;
    }

    // skip attribute blocks #[...]
    if(preg_match('/^\s*#\[.*\]/', $line)) {
      continue;
    }

    // first class-like definition found
    if(preg_match('/^\s*(final\s+|abstract\s+)?(class|interface|trait|enum)\s+\w+/i', $line)) {
      return implode("\n", \array_slice($lines, $i));
    }
  }

  return '';
}

/**
 * Recursively yield all .php files in a directory.
 */
function getPhpFilesRecursively(string $directory, array $exclude = []): Generator
{
  $iterator = new RecursiveDirectoryIterator(
    $directory,
    FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
  );

  $recursive = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator($iterator, function ($current, $key, $iterator) use ($exclude)
    {
      // Exclude folder names (case-insensitive)
      if($current->isDir())
      {
        $name = strtolower(basename($current));

        if(\in_array($name, $exclude))
        {
          return false; // do not recurse into this directory
        }
      }

      return true; // keep everything else
    }),

    RecursiveIteratorIterator::SELF_FIRST
  );

  foreach($recursive as $file)
  {
    if($file->isFile() && strtolower($file->getExtension()) === 'php')
    {
      yield $file->getPathname();
    }
  }
}

echo PHP_EOL;
echo 'Mode: ' . ($doFix ? 'FIXING files' : 'ANALYZE only (no changes written)') . PHP_EOL;
echo PHP_EOL;

$stats = ['total' => 0, 'tobeFixed' => 0, 'good' => 0, 'successful' => 0, 'error' => 0];

foreach($folders as $folder)
{
  $baseDir = $rootDir . DIRECTORY_SEPARATOR . $folder;

  foreach(getPhpFilesRecursively($baseDir, $exclude) as $file)
  {
    // Skip this file to avoid reloading itself
    if($file === __FILE__ || basename($file) === 'Indentation.php')
    {
      continue;
    }

    $stats['total'] = $stats['total'] + 1;

    // Read file
    $content = file_get_contents($file);

    if($content === false)
    {
      echo 'Cannot read file: ' . basename($file) . PHP_EOL . PHP_EOL;
      continue;
    }

    // Strip file headers
    $content_det = stripHeader($content);

    if($content_det === '')
    {
      $content_det = $content;
    }

    // Detect tabs
    $hasTabs = strpos($content_det, "\t") !== false;

    // Detect indentation
    $indent       = Indentation::detect($content_det);
    $currentSize  = $indent->getAmount();
    $currentType  = $indent->getType();
    $needReIndent = ($currentType !== Indentation::TYPE_UNKNOWN && $currentType !== $indentType) || ($currentSize > 1 && $currentSize !== $indentSize);

    if($details || $hasTabs || $needReIndent)
    {
      $hasTabsString = $hasTabs ? 'true' : 'false';
      echo 'File (' . basename($file) . "): tabs: $hasTabsString, type: $currentType, size: $currentSize" . PHP_EOL;
    }

    if($hasTabs || $needReIndent)
    {
      $stats['tobeFixed'] = $stats['tobeFixed'] + 1;
    }
    else
    {
      $stats['good'] = $stats['good'] + 1;
    }

    if(!$doFix || (!$hasTabs && !$needReIndent))
    {
      // Already OK, nothing to fix
      if($details)
      {
        echo 'nothing to do'. PHP_EOL . PHP_EOL;
      }
      continue;
    }

    if($doFix && $hasTabs)
    {
      if($details)
      {
        echo 'normalize Tabs...'. PHP_EOL;
      }

      // Normalize tabs to spaces in the whole file
      $content = str_replace("\t", '  ', $content);

      // Normalize mixed indents
      $content = preg_replace_callback('/^\s+/m', function($m) {
          return str_replace("\t", '  ', $m[0]);
      }, $content);
    }


    if($doFix && $needReIndent)
    {
      if($details)
      {
        echo 'fix indentation...'. PHP_EOL;
      }

      // Fix indention
      $newIndent  = new Indentation($indentSize, $indentType);
      $newContent = Indentation::change($content, $newIndent);
    }
    else
    {
      $newContent = $content;
    }

    // Write directly
    try
    {
      if($doFix)
      {
        file_put_contents($file, $newContent);
        $stats['successful'] = $stats['successful'] + 1;
      }
    }
    catch(\Exception $e)
    {
      echo 'ERROR: Cannot write file ' . basename($file) . PHP_EOL;
      $stats['error'] = $stats['error'] + 1;
    }

    echo PHP_EOL;
  }
}

echo PHP_EOL;
echo 'STATISTICS' . PHP_EOL;
echo '-----------------------' . PHP_EOL;
echo 'Total files: ' . $stats['total'] . PHP_EOL;
echo 'Nothing to fix: ' . $stats['good'] . PHP_EOL;
echo 'Needs fixing: ' . $stats['tobeFixed'] . PHP_EOL;

if($stats['tobeFixed'] > 0)
{
  echo 'Successfully fixed: ' . $stats['successful'] . PHP_EOL;

  if($stats['error'] > 0)
  {
    echo 'Error during fixing: ' . $stats['error'] . PHP_EOL;
  }
}
echo '-----------------------' . PHP_EOL;
echo PHP_EOL;

// EXIT CODE HANDLING
$exitCode = 0;

if($stats['error'] > 0)
{
  // Error during fixing
  $exitCode = 1;
}
elseif(!$doFix && $stats['tobeFixed'] > 0)
{
  // Found something that needs to be fixed
  $exitCode = 1;
}

exit($exitCode);
