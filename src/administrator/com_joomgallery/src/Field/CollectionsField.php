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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Read-only collection list field whose label links to the filtered collection manager.
 *
 * @since  __DEPLOY_VERSION__
 */
class CollectionsField extends FormField
{
  /**
   * Field type.
   *
   * @var    string
   * @since  __DEPLOY_VERSION__
   */
  public $type = 'Collections';

  /**
   * Render the label as a link to this profile's collections.
   *
   * @return  string  HTML label.
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function getLabel()
  {
    $profileId = (int) ($this->element['profile_id'] ?? 0);
    $url       = 'index.php?option=com_joomgallery&view=collections&filter_userid=' . $profileId . '&filter_search=';
    $label     = Text::_((string) $this->element['label']);

    return '<label id="' . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') . '-lbl" for="'
      . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8') . '"><a href="'
      . htmlspecialchars(Route::_($url, false), ENT_QUOTES, 'UTF-8') . '">'
      . htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</a></label>';
  }

  /**
   * Render the comma-separated titles in a disabled text input.
   *
   * @return  string  HTML input.
   *
   * @since   __DEPLOY_VERSION__
   */
  protected function getInput()
  {
    return '<input type="text" id="' . htmlspecialchars($this->id, ENT_QUOTES, 'UTF-8')
      . '" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8')
      . '" value="' . htmlspecialchars((string) $this->value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
      . '" class="form-control" disabled readonly aria-disabled="true">';
  }
}
