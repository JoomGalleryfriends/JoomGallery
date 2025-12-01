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

namespace PHP_CodeSniffer\Standards\JG\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

final class FileHeaderSniff implements Sniff
{
  /** @var string Filename of header template */
  public $headerFile = '';

  /** @var string The loaded header text */
  private $header = '';

  public function register()
  {
    return [T_OPEN_TAG];
  }

  public function process(File $phpcsFile, $stackPtr)
  {
    // ONLY run on the FIRST opening PHP tag in the file
    $firstOpenTag = $phpcsFile->findNext(T_OPEN_TAG, 0);
    if($stackPtr !== $firstOpenTag)
    {
      return;
    }

    // Lazy load header file only once
    if($this->header === '')
    {
      $this->loadHeader($phpcsFile);

      if($this->header === '')
      {
        return;
      }
    }

    $tokens = $phpcsFile->getTokens();

    // Find first non-whitespace token after <?php
    $nextPtr = $phpcsFile->findNext(T_WHITESPACE, $firstOpenTag + 1, null, true);

    // Expected formatted header
    $expected = $this->getHeaderBlock();

    // CASE 1 — No header present → insert it
    if($tokens[$nextPtr]['code'] !== T_DOC_COMMENT_OPEN_TAG)
    {
    $phpcsFile->addFixableError(
        'Missing required JoomGallery file header.',
        $stackPtr,
        'Missing'
    );

      if($phpcsFile->fixer->enabled)
      {
        $this->insertHeader($phpcsFile, $firstOpenTag);
      }

      return;
    }

    // CASE 2 — A header exists: validate
    $closer         = $tokens[$nextPtr]['comment_closer'];
    $existingHeader = trim($phpcsFile->getTokensAsString($nextPtr, $closer - $nextPtr + 1));

    if($this->normalize($existingHeader) !== $this->normalize($expected))
    {
    $phpcsFile->addFixableError(
        'Invalid or outdated JoomGallery file header.',
        $stackPtr,
        'Mismatch'
    );

      if($phpcsFile->fixer->enabled)
      {
        $this->replaceHeader($phpcsFile, $firstOpenTag, $nextPtr, $closer);
      }
    }
  }

  private function normalize(string $txt): string
  {
    return trim(preg_replace('/\s+/', ' ', $txt));
  }

  private function loadHeader(File $phpcsFile)
  {
    if(!is_readable($this->headerFile))
    {
    $phpcsFile->addWarning(
        'Could not read header file: ' . $this->headerFile,
        0,
        'HeaderFileMissing'
    );
      $this->header = '';

      return;
    }

    $this->header = trim(file_get_contents($this->headerFile));
  }

  private function insertHeader(File $phpcsFile, int $openTagPtr)
  {
    $phpcsFile->fixer->beginChangeset();

    // Remove ALL whitespace/newlines after <?php
    $this->removeWhitespaces($phpcsFile, $openTagPtr);

    $phpcsFile->fixer->addContent($openTagPtr, $this->getHeaderBlock());
    $phpcsFile->fixer->endChangeset();
  }

  private function replaceHeader(File $phpcsFile, int $openTagPtr, int $headerOpenPtr, int $headerClosePtr)
  {
    $phpcsFile->fixer->beginChangeset();

    // Remove the old header
    for($i = $headerOpenPtr; $i <= $headerClosePtr; $i++)
    {
      $phpcsFile->fixer->replaceToken($i, '');
    }

    // Remove ALL whitespace/newlines after <?php
    $this->removeWhitespaces($phpcsFile, $openTagPtr);

    // Insert corrected header
    $phpcsFile->fixer->addContent($openTagPtr, $this->getHeaderBlock());
    $phpcsFile->fixer->endChangeset();
  }

  private function removeWhitespaces(File $phpcsFile, int $openTagPtr)
  {
    $next   = $openTagPtr + 1;
    $tokens = $phpcsFile->getTokens();

    while(isset($tokens[$next]) && $tokens[$next]['code'] === T_WHITESPACE)
    {
      $phpcsFile->fixer->replaceToken($next, '');
      $next++;
    }
  }

  private function getHeaderBlock()
  {
    $lines   = explode("\n", $this->header);
    $wrapped = "/**\n";

    foreach($lines as $line)
    {
      $wrapped .= " * " . rtrim($line) . "\n";
    }
    $wrapped .= " */";

    return $wrapped;
  }
}
