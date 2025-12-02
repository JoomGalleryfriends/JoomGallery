<?php
require_once('Indentation.php');
use ColinODell\Indentation\Indentation;

// Setting
//----------------------
$indentSize = 2;
$doFix = false;
//----------------------

// If script is called with "fix" argument → enable fixing
if(isset($argv[1]) && strtolower($argv[1]) === 'fix')
{
  $doFix = true;
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
      return implode("\n", array_slice($lines, $i));
    }
  }

  return '';
}

/**
 * Recursively yield all .php files in a directory.
 */
function getPhpFilesRecursively(string $directory): Generator
{
  $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator(
          $directory,
          FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
      )
  );

  foreach($iterator as $file)
  {
    if($file->isFile() && strtolower($file->getExtension()) === 'php')
    {
      yield $file->getPathname();
    }
  }
}

echo "Mode: " . ($doFix ? "FIXING files" : "ANALYZE only (no changes written)") . PHP_EOL;
echo PHP_EOL;

$baseDir = __DIR__;
foreach(getPhpFilesRecursively($baseDir) as $file)
{
  // Skip this file to avoid reloading itself
  if($file === __FILE__ || \basename($file) === 'Indentation.php')
  {
    continue;
  }

  // Read file
  $content = file_get_contents($file);
  if($content === false)
  {
    echo "Cannot read file: " . basename($file) . PHP_EOL;
    continue;
    echo PHP_EOL;
  }

  // Strip file headers
  $content_det = stripHeader($content);
  if($content_det === '')
  {
    $content_det = $content;
  }

  // Detect tabs
  $hasTabs = \strpos($content_det, "\t") !== false;

  // Detect indentation
  $indent       = Indentation::detect($content_det);
  $currentSize  = $indent->getAmount();
  $currentType  = $indent->getType();
  $needReIndent = ($currentType !== Indentation::TYPE_SPACE) || ($currentSize > 1 && $currentSize !== $indentSize);

  $hasTabsString = $hasTabs ? 'true' : 'false';
  echo "File (" . basename($file) . "): tabs: $hasTabsString, type: $currentType, size: $currentSize" . PHP_EOL;

  if(!$doFix || (!$hasTabs && !$needReIndent))
  {
    // Already OK, nothing to fix
    echo 'nothing to do'. PHP_EOL;
    echo PHP_EOL;
    continue;
  }

  if($doFix && $hasTabs)
  {
    echo 'normalize Tabs...'. PHP_EOL;

    // Normalize tabs to spaces in the whole file
    $content = \str_replace("\t", "  ", $content);

    // Normalize mixed indents
    $content = \preg_replace_callback('/^\s+/m', function($m) {
        return \str_replace("\t", "  ", $m[0]);
    }, $content);
  }


  if($doFix && $needReIndent)
  {
    echo 'fix indentation...'. PHP_EOL;

    // Fix indention
    $newIndent  = new Indentation($indentSize, Indentation::TYPE_SPACE);
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
      \file_put_contents($file, $newContent);
    }
  }
  catch(\Exception $e)
  {
    echo "ERROR: Cannot write file " . basename($file) . PHP_EOL;
  }

  echo PHP_EOL;
}
