# Code Style Guide for JoomGallery 4.x by JoomGalleryfriends  
Last updated: 24/02/2025
[Wechsle zur Deutschen Version](Codestyleguide.md)

## General

- Encoding: UTF-8 without BOM
- Line endings: LF (UNIX style), not CR+LF (Windows) and not CR (macOS classic)
- Indentation: 2 spaces per level (no tabs)
- No empty line at the end of the file

### File Structure
1. File header
2. Namespace definition
3. \defined('_JEXEC') or die;
4. `use` statements (importing required classes)
5. Content

### File Header
1. Component name  
2. Author  
3. Copyright  
4. Licence

- Function names: start with a lowercase letter, capitalise each subsequent word:
```php
buildCategoryQuery()
```

- Variable names: same convention as functions (lowerCamelCase)
- Keywords must be lowercase: `if`, `while`, `for`, `foreach`, `require_once`, `true`, `false`, `null`, `function`, etc.
- HTML output should be handled only in template (`tmpl`) files. If generated elsewhere, buffer it and pass it to the template.
- All identifiers, comments, and code must be in **English only**.

### Namespacing

- PHP files in the `src` directory must use namespaces:
```php
namespace Joomgallery\Component\Joomgallery\<Client>\<Folder>;
```

- Always import classes using `use` or `require_once` for non-namespaced files. **Do not** use `include`, `include_once`, or `require`.

- Classes, functions, and constants from the PHP global namespace (built-in ones like DateTime, strlen, or PHP_VERSION) must not be imported with use statements.
- Built-in PHP functions (e.g. strlen, count, array_merge) must be referenced with a leading backslash when used inside namespaced code.

#### CORRECT
```php
$date = new \DateTime();
```

#### INCORRECT
```php
use DateTime;

$date = new DateTime();
```

- `use` statements (imports) must be grouped and sorted consistently:

1. Order by type:
  - Classes first
  - Then functions
  - Then constants
2. Sort each group by length (shorter names first).

#### CORRECT
```php
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use function strlen;
use const PHP_VERSION;
```

### Language Standards
- Use `image` instead of `picture`
- Use `sub-category` instead of `subcategory`
- Use `current` or `cur` instead of `actual` or `act`

### Whitespace
- Always use a space **before and after** the equals sign
- Align multiple assignments for readability

#### CORRECT
```php
$image->orig_width  = $orig_info[0];
$image->orig_height = $orig_info[1];
$image->img_width   = $img_info[0];
$image->img_height  = $img_info[1];
```

#### INCORRECT
```php
$image->orig_width=$orig_info[0];
$image->orig_height=$orig_info[1];
$image->img_width=$img_info[0];
$image->img_height=$img_info[1];
```

- Always include a space before and after PHP tags, and do not forget semicolons.

#### CORRECT
```php
<?php echo $list['owner']; ?>
```

#### INCORRECT
```php
<?php echo $list['owner']?>
```

## PHP Constructs (`if`, `while`, `for`, `foreach`, `switch`, etc.)

- Always use curly braces, even for single-line statements.
- Curly braces must be on their **own line**.

#### CORRECT
```php
if($integer == 1)
{
  $count = 2;
}
else
{
  if($integer == 2)
  {
    $count = 3;
  }
}
```

#### INCORRECT
```php
if($integer == 1){
  $count = 2;
}
else if($integer == 2)
    $count = 3;
```

## Trailing Commas

Single-line and multi-line lists must not have a trailing comma.


#### CORRECT
```php
$values = ['a', 'b', 'c'];

$values = [
  'a',
  'b',
  'c'
];
```

#### INCORRECT
```php
$values = ['a', 'b', 'c',];

$values = [
  'a',
  'b',
  'c',
];
```

### `switch` Notes

- `default` case is **mandatory**, even if it only contains `break`
- If `break` is intentionally omitted, add a comment

#### Example
```php
switch($count)
{
  case 1:
    $integer = 5;
    break;
  case 2:
    $integer = 3;
    break;
  case 3:
    $count   = 0;
    // 'break' intentionally omitted
  case 4:
    $integer = 4;
    break;
  default:
    $integer = 0;
    break;
}
```

## Operators

- Always use `&&` and `||`, never `AND`, `OR`, `and`, or `or`

### Special case – Ternary conditions
```php
$available = $count ? true : false;
```

## Whitespace (detailed)

- No space between keyword and opening parenthesis
- No space **after** the opening parenthesis (unless multi-line or complex condition)
- One space between variables and operators
- No space before the closing parenthesis

#### CORRECT
```php
if($test == true && $integer == 3)
{
  // Statements
}

foreach($array as $key => $integer)
{
  // Statements
}
```

#### INCORRECT
```php
if ( $test == true AND $integer == 3 )
{
  // Statements
}
foreach ($array as $key=>$integer)
{
  // Statements
}
```

#### Correct example for multi-line conditions:
```php
if(  (  (   ($this->_config->get('jg_showdetailfavourite') == 0 && $this->_user->get('aid') < 1)
          || ($this->_config->get('jg_showdetailfavourite') == 1 && $this->_user->get('aid') < 2)
        )
      ^ ($this->_config->get('jg_usefavouritesforpubliczip') == 1 && $this->_user->get('id') < 1)
      )
    || $this->_config->get('jg_favourites') == 0
  )
{
  // Statements
}
```

## Functions

### Whitespace
- No space between function name and opening parenthesis
- No space between opening parenthesis and first parameter
- One space after each comma
- No space before closing parenthesis

#### CORRECT
```php
$data = getData($count, $integer, $string);
```

#### INCORRECT
```php
$data = getData ( $count,$integer, $string );
```

### Function Declarations
- Include spaces around default values in parameters

#### CORRECT
```php
function getData($count, $integer = 0, $string = '')
{
  // Statements
}
```

#### INCORRECT
```php
function getData($count,$integer=0,$string='')
{
  // Statements
}
```

### Blank Lines
- Use blank lines to separate logical blocks
- Add a blank line after control structures like `if`, `while`, etc.  
  *(Exception: if immediately followed by a related statement or another condition)*
- Always add a blank line **before** a `return`, unless it is the only line inside a block

#### CORRECT
```php
function getPlural($count)
{
  if($count > 1)
  {
    return true;
  }

  $this->counter--;

  return false;
}
```

#### INCORRECT
```php
function getPlural($count)
{
  if($count > 1)
  {

    return true;
  }
  $this->counter--;
  return false;
}
```

## Database Queries

- MySQL keywords must be written in **uppercase**
- Use clear, multi-line formatting
- Use constants for all gallery table names
- Always use `quoteName()` and `quote()` methods for building queries

#### Example
```php
$db    = Factory::getDBO();
$query = $db->getQuery(true)
      ->select($db->quoteName(array('a.id', 'a.title')))
      ->from($db->quoteName(_JOOM_TABLE_IMAGES, 'a'))
      ->where($db->quoteName('a.published') . ' = ' . $db->quote(1))
      ->where($db->quoteName('a.approved') . ' = ' . $db->quote(1))
      ->leftJoin($db->quoteName(_JOOM_TABLE_CATEGORIES, 'b') . ' ON ' . $db->quoteName('a.catid') . ' = ' . $db->quoteName('b.cid'))
      ->where($db->quoteName('b.published') . ' = ' . $db->quote(1))
      ->order($db->quoteName('a.ordering') . ' DESC');
$db->setQuery($query);
```

## Template Files

- Use PHP's alternative syntax for control structures

#### CORRECT
```php
<?php if($this->params->get('show_title')): ?>
  <?php echo $this->params->get('title'); ?>
<?php endif; ?>
```

#### INCORRECT
```php
<?php if($this->params->get('show_title'))
  { ?>
  <?php echo $this->params->get('title'); ?>
<?php }
```

- PHP tags should be indented as if they were part of the HTML structure
- Closing PHP tags should always be at the end of the line, with a space

#### CORRECT
```php
<?php if($this->params->get('show_testdata')): ?>
  <div class="jg_test">
    <div class="sectiontableheader">
      <h4>
        <?php echo JText::_('JGS_DATA'); ?>
      </h4>
    </div>

    <?php if(!empty($this->slider)): ?>
      <div class="slider">
    <?php endif; ?>

      <?php echo $this->testdata.'&nbsp;'; ?>

    <?php if(!empty($this->slider)): ?>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>
```

#### INCORRECT
```php
<?php if($this->params->get('show_testdata')): ?>
  <div class="jg_test">
    <div class="sectiontableheader">
      <h4>
<?php   echo JText::_('JGS_DATA'); ?>
      </h4>
    </div>
<?php   if(!empty($this->slider)): ?>
    <div class="slider">
<?php   endif;
        echo $this->testdata.'&nbsp;';
        if(!empty($this->slider)): ?>
    </div>
<?php   endif; ?>
  </div>
<?php endif; ?>
```

## Classes

- Always add a JavaDoc-style comment block above each class
- Begin the class description with a capital letter
- Required annotations:
  - `@package` (e.g. `JoomGallery`)
  - `@since` (version in which the class was introduced)
- Maintain correct formatting of asterisks and `@` tags
- For service classes: always define and implement an interface

#### Example
```php
/**
* JoomGallery Refresher Helper
*
* Provides handling with the filesystem where the image files are stored
*
* @package JoomGallery
* @since   4.0.0
*/
class Refresher implements RefresherInterface
{
  // Content
}
```

- For static classes, add `@static` as the first annotation

#### Example
```php
/**
* JoomGallery Helper for the Backend
*
* @static
* @package JoomGallery
* @since  4.0.0
*/
class JoomHelper
{
  // Content
}
```

## Functions

- Always add a JavaDoc-style comment block above each function
- Start the description with a capital letter
- Required annotations:
  - `@param` – one for each parameter (can be omitted if none)
  - `@return` – use `@return void` if nothing is returned
  - `@since` – version when the method was added
- Optional:
  - `@deprecated` – e.g. `@deprecated as of version 1.5.0`
- `@param` format: Type, variable name, short description (capital letter)
- `@return` format: Type, short description (capital letter)
- Align descriptions for readability

#### CORRECT
```php
/**
 * Collect information for the watermarking
 * (information: dimensions, type, position)
 *
 * @param   array   $imginfo        Array with image information of the background image
 * @param   int     $position       Positioning of the watermark
 * @param   int     $resize         Resize watermark (0: no, 1: by height, 2: by width)
 * @param   float   $new_size       New size of the resized watermark in percent (1–100)
 *
 * @return  array   Array with watermark positions; array(x, y)
 *
 * @since   3.6.0
 */
protected function getWatermarkingInfo($imginfo, $position, $resize, $new_size): array
{
  // Statements
}
```

#### INCORRECT
```php
/**
 * Moves an image to another folder
 *
 * @param $src string Absolute path to source file
 * @param $dest string Absolute path to destination file
 * @return result boolean True on success, false otherwise
 * @since 1.0.0
 * @deprecated as of version 1.5.0
 */
function copyImage($src, $dest)
{
  // Statements
}
```

### In-function Comments
- Use `//` (not `#`)
- Add a space after the slashes
- Begin the comment with a capital letter (often imperative)

#### CORRECT
```php
// Perform the request task
$controller->execute(Factory::getApplication()->input->get('task', 'display', 'cmd'));
```

#### INCORRECT
```php
//perform the request task
$controller->execute(Factory::getApplication()->input->get('task', 'display', 'cmd'));
```

- For unclear or questionable code, mark it as:
```php
// TODO Name: Description of the issue
```
"Name" refers to the person who noticed the issue, not necessarily the one who must fix it.

## Miscellaneous

- `require_once` is not a function – **do not use parentheses**
- No spaces before and after concatenation dots in expressions

#### CORRECT
```php
require_once JPATH_COMPONENT.DS.'helpers'.DS.'messenger.php';
```

#### INCORRECT
```php
require_once(JPATH_COMPONENT . DS . 'helpers'.DS.'messenger.php');
```

- Prefer single quotes for strings wherever possible (for performance)

#### CORRECT
```php
$string = '('.$count.')';
```

#### INCORRECT
```php
$string = "($count)";
```
