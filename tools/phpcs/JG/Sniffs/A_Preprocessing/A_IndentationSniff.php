<?php
declare(strict_types=1);

namespace PHP_CodeSniffer\Standards\JG\Sniffs\A_Preprocessing;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use ColinODell\Indentation\Indentation;

class A_IndentationSniff implements Sniff
{
  /**
   * Indent size you want to enforce
   */
  public int $indentSize = 2;

  public function register()
  {
    // Check only once, at the start of the file
    return [T_OPEN_TAG];
  }

  public function process(File $phpcsFile, $stackPtr)
  {
    // Load whole content
    $content = \file_get_contents($phpcsFile->getFilename());

    // Detect tabs
    $hasTabs = \strpos($content, "\t") !== false;

    // Detect indentation
    $indent = Indentation::detect($content);
    $currentSize = $indent->getAmount();
    $currentType = $indent->getType();

    // If already OK, nothing to fix
    if(!$hasTabs && $currentType === Indentation::TYPE_SPACE && $currentSize === $this->indentSize)
    {
      return;
    }

    // Add an error with autofix enabled
    $fix = $phpcsFile->addFixableError(
      sprintf(
        'Indentation probably not correct. Expects %d spaces and no tabs.',
        $this->indentSize
      ),
      $stackPtr,
      'WrongIndentation'
    );

    if($fix === true)
    {
      // Normalize tabs to spaces in the whole fit
      $content = \str_replace("\t", "  ", $content);

      //Normalize mixed indents
      $content = \preg_replace_callback('/^\s+/m', function($m) {
        return \str_replace("\t", "  ", $m[0]);
      }, $content);

      // Fix indentation
      $newIndent = new Indentation($this->indentSize, Indentation::TYPE_SPACE);
      $newContent = Indentation::change($content, $newIndent);

      // Replace entire file content
      \file_put_contents($phpcsFile->getFilename(), $newContent);
    }

    // Only run sniff once per file
    return ($phpcsFile->numTokens + 1);
  }
}
