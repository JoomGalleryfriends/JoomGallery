<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

/**
 * This is the configuration file for php-cs-fixer
 *
 * @link https://github.com/FriendsOfPHP/PHP-CS-Fixer
 * @link https://mlocati.github.io/php-cs-fixer-configurator/#version:3.0
 *
 *
 * If you would like to run the automated clean up, then open a command line and type one of the commands below
 *
 * To run a quick dry run to see the files that would be modified:
 *
 *        ./administrator/com_joomgallery/vendor/bin/php-cs-fixer fix --dry-run
 *
 * To run a full check, with automated fixing of each problem :
 *
 *        ./administrator/com_joomgallery/vendor/bin/php-cs-fixer fix
 *
 * You can run the clean up on a single file if you need to, this is faster
 *
 *        ./administrator/com_joomgallery/vendor/bin/php-cs-fixer fix --dry-run administrator/index.php
 *        ./administrator/com_joomgallery/vendor/bin/php-cs-fixer fix administrator/index.php
 */

$finder = PhpCsFixer\Finder::create()
  ->in(
    [
      __DIR__ . '/administrator',
      __DIR__ . '/plugins',
      __DIR__ . '/site',
    ]
  )
  ->notPath('administrator/com_joomgallery/vendor')
  ->notPath('administrator/com_joomgallery/includes')
  ->notPath('tools/phpcs')
  ->exclude('vendor')
  ->exclude('includes')
  ->exclude('tools')
  ->name('*.php')
  ->ignoreDotFiles(true)
  ->ignoreVCS(true);

// Load hesder from header.txt
$headerFile = __DIR__ . '/header.txt';
if(!file_exists($headerFile))
{
  throw new RuntimeException("header.txt not found at: " . $headerFile);
}
$header = trim(file_get_contents($headerFile));

return (new PhpCsFixer\Config())
  ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
  ->setRiskyAllowed(true)
  ->setHideProgress(false)
  ->setUsingCache(false)
  ->setIndent('  ')
  ->setLineEnding("\n")
  ->setRules([
    '@PSR1' => true,
    'encoding' => true,
    'indentation_type' => true,
    'line_ending' => true,

    // Enforce file header
    'header_comment' => ['comment_type' => 'PHPDoc', 'location' => 'after_open', 'separate' => 'bottom', 'header' => $header],
    'blank_line_after_opening_tag' => false,

    // Arrays & commas
    'array_syntax' => ['syntax' => 'short'],
    'trim_array_spaces' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_trailing_comma_in_singleline' => true,
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    'array_indentation' => true,

    // Operators, spacing & braces
    'binary_operator_spaces' => false,
    'blank_line_before_statement' => ['statements' => ['return', 'if', 'for', 'foreach', 'while']],
    'no_break_comment' => ['comment_text' => "'break' intentionally omitted"],
    'braces_position' => false,
    'control_structure_continuation_position' => ['position' => 'next_line'],
    'type_declaration_spaces' => ['elements' => ['function', 'property']],
    'method_argument_space' => ['on_multiline' => 'ignore'],

    // Imports
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],

    // Misc quality/cleanup
    'no_useless_else' => true,
    'native_function_invocation' => ['include' => ['@compiler_optimized']],
    'nullable_type_declaration_for_default_null_value' => true,
    'no_unneeded_control_parentheses' => true,
    'combine_consecutive_issets' => true,
    'combine_consecutive_unsets' => true,
    'no_useless_sprintf' => true,
    'lowercase_keywords' => true,
    'logical_operators' => true,

    // Whitespace hygiene
    'single_quote' => true,
    'no_trailing_whitespace' => true,
    'no_whitespace_in_blank_line' => true,
    'no_spaces_after_function_name' => true,
    'phpdoc_indent' => true,
    'phpdoc_trim' => true,
  ])
  ->setFinder($finder);
