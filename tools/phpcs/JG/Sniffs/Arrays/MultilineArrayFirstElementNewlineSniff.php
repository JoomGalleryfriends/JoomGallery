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

namespace PHP_CodeSniffer\Standards\JG\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class MultilineArrayFirstElementNewlineSniff implements Sniff
{
  public function register(): array
  {
    return [T_ARRAY, T_OPEN_SHORT_ARRAY];
  }

  public function process(File $phpcsFile, $stackPtr): void
  {
    $tokens = $phpcsFile->getTokens();
    $token  = $tokens[$stackPtr];

    // Determine array boundaries
    if($token['code'] === T_ARRAY)
    {
      $openPtr     = $tokens[$token['parenthesis_opener']]['code'] === T_OPEN_PARENTHESIS
        ? $token['parenthesis_opener']
        : null;
      $openPtr     = $openPtr === null ? $token['parenthesis_opener'] : $token['parenthesis_opener'];
      $openBracket = $tokens[$openPtr]['parenthesis_opener'];
    }
    else
    {
      // Short syntax "[ ]"
      $openBracket = $stackPtr;
    }

    $open = $token['code'] === T_ARRAY
      ? $tokens[$token['parenthesis_opener']]['parenthesis_opener']
      : $token['bracket_opener'];

    $close = $token['code'] === T_ARRAY
      ? $tokens[$token['parenthesis_opener']]['parenthesis_closer']
      : $token['bracket_closer'];

    // Single-line array → do not modify
    if($tokens[$open]['line'] === $tokens[$close]['line'])
    {
      return;
    }

    // Find first non-whitespace after '['
    $firstContent = $phpcsFile->findNext(
        [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT],
        $open + 1,
        $close,
        true
    );

    if($firstContent === false)
    {
      return; // empty array
    }

    // If first element is on same line as the "[", enforce newline
    if($tokens[$firstContent]['line'] === $tokens[$open]['line'])
    {
      $error = 'Multiline array must place first element on a new line.';
      $fix   = $phpcsFile->addFixableError($error, $firstContent, 'FirstElementNewline');

      if($fix === true)
      {
        $phpcsFile->fixer->beginChangeset();

        // Detect indentation of the following line
        $indentation = '';
        $lineAfter   = $tokens[$open]['line'] + 1;

        // Scan tokens until we find the first token on that line
        for($i = $open + 1; $i < $close; $i++)
        {
          if($tokens[$i]['line'] === $lineAfter)
          {
            // Capture all leading whitespace on that line
            if($tokens[$i]['code'] === T_WHITESPACE)
            {
              $indentation = $tokens[$i]['content'];
            }

            break;
          }
        }

        // Fallback: if indentation was not found, use 2 spaces
        if($indentation === '')
        {
          $indentation = str_repeat(' ', 2);
        }

        // Insert newline + dynamic indentation
        $phpcsFile->fixer->addContent($open, "\n" . $indentation);

        // Also ensure closing bracket is on its own line
        if($tokens[$close]['line'] === $tokens[$firstContent]['line'])
        {
          $phpcsFile->fixer->addContentBefore($close, "\n");
        }

        $phpcsFile->fixer->endChangeset();
      }
    }
  }
}
