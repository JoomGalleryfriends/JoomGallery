<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

declare(strict_types=1);

namespace PHP_CodeSniffer\Standards\JG\Sniffs\A_Preprocessing;

use ColinODell\Indentation\Indentation;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class A_IndentationSniff implements Sniff
{
  /**
   * Indent size you want to enforce
   */
  public int $indentSize = 2;

  /**
   * Toggle fixing
   */
  public int $activateFix = 0;

  public function register()
  {
    return [T_OPEN_TAG];
  }

  public function process(File $phpcsFile, $stackPtr)
  {
    if ($stackPtr !== 0) return;

    // Load whole content
    $content     = \file_get_contents($phpcsFile->getFilename());
    //$content     = $phpcsFile->getTokensAsString(0, $phpcsFile->numTokens - 1);
    $content_det = $this->stripHeader($content);

    // Fallback if no class/interface/trait/enum found
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
    $needReIndent = ($currentType !== Indentation::TYPE_SPACE) || ($currentSize > 1 && $currentSize !== $this->indentSize);

    if(!$hasTabs && !$needReIndent)
    {
      // Already OK, nothing to fix
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

    if(!(bool)$this->activateFix || !$fix)
    {
      return;
    }

    if($hasTabs)
    {
      // Normalize tabs to spaces in the whole fit
      $content = \str_replace("\t", "  ", $content);

      //Normalize mixed indents
      $content = \preg_replace_callback(
        '/^\s+/m',
        function ($m) {
          return \str_replace("\t", "  ", $m[0]);
        },
        $content
      );
    }

    if($needReIndent)
    {
      // Fix indentation
      $newIndent  = new Indentation($this->indentSize, Indentation::TYPE_SPACE);
      $newContent = Indentation::change($content, $newIndent);
    }
    else
    {
      $newContent = $content;
    }

    // Replace entire file content
    $phpcsFile->fixer->beginChangeset();

    // Put the entire new content into the first token
    $phpcsFile->fixer->replaceToken(0, $newContent);

    // Clear all remaining tokens so old code doesn’t hang around
    for($i = 1; $i < $phpcsFile->numTokens; $i++)
    {
      $phpcsFile->fixer->replaceToken($i, '');
    }

    $phpcsFile->fixer->endChangeset();

    // Only run sniff once per file
    return ($phpcsFile->numTokens + 1);
  }

  /**
   * Removes all lines before the class/interface/trait/enum definition.
   *
   * @param   string   $content  The full contents of a PHP file.
   * @return  string   The cleaned file content.
   */
  protected function stripHeader(string $content): string
  {
    $lines = \preg_split('/\R/', $content);

    for($i = 0; $i < count($lines); $i++)
    {
      $line = $lines[$i];

      foreach($lines as $i => $line)
      {
        if(preg_match('/^\s*(final\s+|abstract\s+)?(class|interface|trait|enum)\s+\w+/i', $line))
        {
          return implode("\n", array_slice($lines, $i));
        }
      }
    }

    return '';
  }
}
