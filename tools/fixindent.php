<?php
require_once('Indentation.php');
use ColinODell\Indentation\Indentation;

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

$dir = __DIR__;
// Find all .php files (except this file, optional)
$files = \glob($dir . DIRECTORY_SEPARATOR . '*.php');

// Setting
$indentSize = 2;
$doFix = false;

foreach($files as $file)
{
  // Skip this file to avoid reloading itself
  if($file === __FILE__ || \basename($file) === 'Indentation.php')
  {
    continue;
  }

  // Check if file exists
  if(!\is_file($file))
  {
    echo 'File ' . basename($file) . ' not found.'. PHP_EOL;
    continue;
  }

  // Read file
  $content = \file_get_contents($file);
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
  echo 'File (' . basename($file) . '): hasTabs:' . $hasTabs . ', type:' . $currentType . ', #:' . $currentSize. PHP_EOL;

  if(!$doFix || (!$hasTabs && !$needReIndent))
  {
    // Already OK, nothing to fix
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
    $newIndent  = new Indentation(2, Indentation::TYPE_SPACE);
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
    echo 'File ' . basename($file) . ' can not be written.'. PHP_EOL;
  }

  echo PHP_EOL;
}
