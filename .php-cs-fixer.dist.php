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
  // Ignore template files as PHP CS fixer can't handle them properly
  // https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/issues/3702#issuecomment-396717120
  ->notPath('administrator/com_joomgallery/tmpl')
  ->notPath('administrator/com_joomgallery/layouts')
  ->name('*.php');

$header = <<<'EOF'
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
******************************************************************************************
EOF;

return (new PhpCsFixer\Config())
  ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
  ->setRiskyAllowed(true)
  ->setHideProgress(false)
  ->setUsingCache(false)
  ->setIndent('  ')
  ->setLineEnding("\n")
  ->setRules([
    '@PSR12' => true,
    'encoding' => true,
    'indentation_type' => true,
    'line_ending' => true,

    // Enforce file header
    'header_comment' => ['comment_type' => 'PHPDoc', 'location' => 'after_open', 'header' => $header],

    // Arrays & commas
    'array_syntax' => ['syntax' => 'short'],
    'trim_array_spaces' => true,
    'no_whitespace_before_comma_in_array' => true,
    'no_trailing_comma_in_singleline' => true,
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],

    // Operators, spacing & braces
    'binary_operator_spaces' => ['operators' => ['=>' => 'align_single_space_minimal', '=' => 'align', '??=' => 'align']],
    'blank_line_before_statement' => ['statements' => ['return', 'if', 'for', 'foreach', 'while']],
    'no_break_comment' => ['comment_text' => "'break' intentionally omitted"],
    'braces_position' => ['control_structures_opening_brace' => 'next_line_unless_newline_at_signature_end'],
    'function_typehint_space' => true,
    'method_argument_space' => ['on_multiline' => 'ignore'],
    
    // Imports
    'no_unused_imports' => true,
    'global_namespace_import' => ['import_classes' => false, 'import_constants' => false, 'import_functions' => false],
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'length'],

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
  ])
  ->setFinder($finder);
