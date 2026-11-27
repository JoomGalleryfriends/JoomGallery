<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Form\FormField;

/**
 * Read-only, escaped display of structured JSON data.
 *
 * Filesystem paths are shortened before rendering so the field cannot expose
 * the server's directory structure through administrator profile data.
 */
class JsondisplayField extends FormField
{
  public $type = 'Jsondisplay';

  /**
   * Render the decoded JSON value as an escaped, read-only object dump.
   *
   * @return  string  HTML representation of the JSON value.
   */
  protected function getInput()
  {
    $value = $this->value;

    // Also accommodate older values containing a JSON-encoded JSON string.
    for($depth = 0; $depth < 2 && \is_string($value); $depth++)
    {
      $decoded = json_decode($value);

      if(json_last_error() !== JSON_ERROR_NONE)
      {
        break;
      }

      $value = $decoded;
    }

    // Mask sensitive values only in the displayed copy. The stored JSON remains unchanged.
    $value = $this->maskSensitiveValues($value);

    // Display nested keys and values like an object dump, without editable controls.
    $formatted = \is_array($value) || \is_object($value)
      ? print_r($value, true)
      : (\is_string($value) ? $value : var_export($value, true));

    return '<pre id="' . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8')
      . '" aria-disabled="true" class="border rounded bg-light p-3 mb-0 overflow-auto">'
      . htmlspecialchars($formatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
  }

  /**
   * Recursively mask values whose JSON key identifies a filesystem path.
   *
   * @param   mixed        $value  Value to inspect.
   * @param   string|null  $key    Key belonging to the current value.
   *
   * @return  mixed  Display-safe copy of the supplied value.
   */
  protected function maskSensitiveValues($value, ?string $key = null)
  {
    if($key !== null && strcasecmp($key, 'path') === 0 && \is_string($value))
    {
      return $this->maskPath($value);
    }

    if(\is_array($value))
    {
      foreach($value as $itemKey => $itemValue)
      {
        $value[$itemKey] = $this->maskSensitiveValues($itemValue, (string) $itemKey);
      }

      return $value;
    }

    if(\is_object($value))
    {
      $value = clone $value;

      foreach(get_object_vars($value) as $property => $propertyValue)
      {
        $value->$property = $this->maskSensitiveValues($propertyValue, (string) $property);
      }
    }

    return $value;
  }

  /**
   * Reduce a filesystem path to an ellipsis and its final filename.
   *
   * @param   string  $path  Original filesystem path.
   *
   * @return  string  Masked path, preserving the path's separator style.
   */
  protected function maskPath(string $path): string
  {
    $lastBackslash = strrpos($path, '\\');
    $lastSlash     = strrpos($path, '/');
    $position      = max($lastBackslash === false ? -1 : $lastBackslash, $lastSlash === false ? -1 : $lastSlash);

    // A filename without directory information does not reveal server structure.
    if($position < 0)
    {
      return $path;
    }

    $separator = $lastBackslash !== false && $lastBackslash === $position ? '\\' : '/';
    $filename  = substr($path, $position + 1);

    return '...' . $separator . $filename;
  }
}
