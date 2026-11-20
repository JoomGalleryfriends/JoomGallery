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

\defined('_JEXEC') || die;

use Joomla\CMS\Form\FormField;

/** Read-only, escaped display of structured JSON data. */
class JsondisplayField extends FormField
{
  public $type = 'Jsondisplay';

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

    // Display nested keys and values like an object dump, without editable controls.
    $formatted = \is_array($value) || \is_object($value)
      ? print_r($value, true)
      : (\is_string($value) ? $value : var_export($value, true));

    return '<pre id="' . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8')
      . '" aria-disabled="true" class="border rounded bg-light p-3 mb-0 overflow-auto">'
      . htmlspecialchars($formatted, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre>';
  }
}
