<?php
declare(strict_types=1);

namespace PHP_CodeSniffer\Standards\JG\Sniffs\ControlStructures;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

/**
 * Enforce:
 *  - NO space between control-structure keyword and "("  ->  if(condition)
 *  - Opening brace "{" on the NEXT line after the closing ")" (Allman)
 *
 * Affects: if, elseif, for, foreach, while, switch
 */
final class ControlSignatureSniff implements Sniff
{
  public function register(): array
  {
    return [T_IF, T_ELSEIF, T_FOR, T_FOREACH, T_WHILE, T_SWITCH];
  }

  public function process(File $phpcsFile, $stackPtr): void
  {
    $tokens = $phpcsFile->getTokens();

    // Find the "(" immediately after the keyword (ignoring whitespace)
    $nextPtr = $stackPtr + 1;
    while(isset($tokens[$nextPtr]) && $tokens[$nextPtr]['code'] === T_WHITESPACE)
    {
      $nextPtr++;
    }

    // If the next non-whitespace token isn't "(", we ignore (e.g., alternative syntax, invalid code)
    if(!isset($tokens[$nextPtr]) || $tokens[$nextPtr]['content'] !== '(')
    {
      return;
    }

    // -------- 1) NO SPACE after keyword --------
    $immediate = $stackPtr + 1;
    if($tokens[$immediate]['code'] === T_WHITESPACE)
    {
      $found = $tokens[$immediate]['content'];
      $fix = $phpcsFile->addFixableError(
        'No space allowed after control-structure keyword; found "%s".',
        $immediate,
        'SpaceAfterKeyword',
        [$this->visualizeWhitespace($found)]
      );

      if($fix)
      {
        $phpcsFile->fixer->beginChangeset();

        // Remove ALL whitespace between keyword and "("
        $ptr = $immediate;

        while($ptr < $nextPtr && $tokens[$ptr]['code'] === T_WHITESPACE)
        {
          $phpcsFile->fixer->replaceToken($ptr, '');
          $ptr++;
        }

        $phpcsFile->fixer->endChangeset();
      }
    }

    // Get the closing ")" matched by PHPCS so we can locate the "{"
    $openParen  = $nextPtr;
    $closeParen = $tokens[$openParen]['parenthesis_closer'] ?? null;

    if($closeParen === null)
    {
      return; // unbalanced; let other sniffs handle
    }

    // From closing ")", find next non-whitespace token
    $afterClose = $closeParen + 1;
    $firstNonWs = $this->findNextNonWhitespace($tokens, $afterClose);

    // If we hit colon syntax (T_COLON), skip (alternative syntax like "if(...):")
    if($firstNonWs !== null && $tokens[$firstNonWs]['code'] === T_COLON)
    {
      return;
    }

    // We expect a "{"
    if($firstNonWs === null || $tokens[$firstNonWs]['code'] !== T_OPEN_CURLY_BRACKET)
    {
      return; // different style (or single-line), leave it to other sniffs
    }

    // -------- 2) "{" must be on the NEXT line after ")" --------
    $parenLine = (int)$tokens[$closeParen]['line'];
    $braceLine = (int)$tokens[$firstNonWs]['line'];

    if($braceLine === $parenLine)
    {
      // Compute indentation for the new line by mirroring keyword line indent
      $indent = $this->fetchLineIndentBefore($tokens, $stackPtr);

      $fix = $phpcsFile->addFixableError(
        'Opening brace for control structure must be on the next line.',
        $firstNonWs,
        'BraceNotOnNewLine'
      );

      if($fix)
      {
        $phpcsFile->fixer->beginChangeset();

        // Replace whitespace between ")" and "{" with a newline + indent
        // (Remove everything from afterClose up to the brace, then insert newline before brace)
        for($i = $afterClose; $i < $firstNonWs; $i++)
        {
          $phpcsFile->fixer->replaceToken($i, '');
        }
        // Insert newline + indent before the "{"
        $phpcsFile->fixer->addContentBefore($firstNonWs, $phpcsFile->eolChar . $indent);

        $phpcsFile->fixer->endChangeset();
      }
    }
  }

  private function findNextNonWhitespace(array $tokens, int $start): ?int
  {
    $ptr = $start;

    while(isset($tokens[$ptr]))
    {
      if($tokens[$ptr]['code'] !== T_WHITESPACE)
      {
        return $ptr;
      }

      $ptr++;
    }

    return null;
  }

  private function fetchLineIndentBefore(array $tokens, int $ptr): string
  {
    // Walk backwards to find the first token on the line, then capture its leading whitespace
    $line = (int)$tokens[$ptr]['line'];
    $firstOnLine = $ptr;

    while($firstOnLine > 0 && (int)$tokens[$firstOnLine - 1]['line'] === $line)
    {
      $firstOnLine--;
    }

    $indent = '';

    if($tokens[$firstOnLine]['code'] === T_WHITESPACE)
    {
      $indent = $tokens[$firstOnLine]['content'];
    }

    return $indent;
  }

  private function visualizeWhitespace(string $s): string
  {
    return preg_replace(
      ["/\n/", "/\r/", "/\t/", "/ /"],
      ['\\n', '\\r', '\\t', '·'],
      $s
    );
  }
}
