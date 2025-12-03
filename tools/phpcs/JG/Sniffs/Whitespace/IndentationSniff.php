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

namespace PHP_CodeSniffer\Standards\JG\Sniffs\Whitespace;

use ColinODell\Indentation\Indentation;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class IndentationSniff implements Sniff
{
  /**
   * Indent size you want to enforce
   */
  public int $indentSize = 2;

  public function register()
  {
    return [T_OPEN_TAG];
  }

  public function process(File $phpcsFile, $stackPtr)
  {
    // Run only once
    if($stackPtr !== 0)
    {
      return;
    }

    // Load whole content
    $content     = \file_get_contents($phpcsFile->getFilename());
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

    if($hasTabs)
    {
      $phpcsFile->addError(
        'No tabs `\t` allowed.',
        $stackPtr,
        'TabsDetected'
      );
    }

    if($needReIndent)
    {
      $phpcsFile->addWarning(
        sprintf(
            'Indentation probably not correct. Expects %d spaces.',
            $this->indentSize
        ),
        $stackPtr,
        'WrongIndentation'
      );
    }

    return;
  }

  /**
   * Remove everything before the first class/interface/trait/enum
   * including docblocks, comments, attributes, and blank lines.
   */
  protected function stripHeader(string $content): string
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
}
