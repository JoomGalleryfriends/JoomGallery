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

use Joomla\CMS\Form\Field\UserField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\Registry\Registry;

/** Select a gallery profile, displaying its linked Joomla identity. */
class JguserField extends UserField
{
  public $type = 'Jguser';

  protected function getInput()
  {
    $db      = $this->getDatabase();
    $query   = $db->getQuery(true)
      ->select(['g.id AS value', 'u.name', 'u.username'])
      ->from($db->quoteName('#__joomgallery_users', 'g'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser')
      ->order('u.name ASC, g.id ASC');
    $options = [HTMLHelper::_('select.option', '', Text::_('JLIB_FORM_SELECT_USER'))];

    foreach($db->setQuery($query)->loadObjectList() as $user)
    {
      $options[] = HTMLHelper::_('select.option', $user->value, $user->name . ' (' . $user->username . ')');
    }

    $attributes  = 'class="form-select"';
    $attributes .= $this->required ? ' required' : '';
    $attributes .= ($this->readonly || $this->disabled) ? ' disabled' : '';
    $html        = HTMLHelper::_('select.genericlist', $options, $this->name, $attributes, 'value', 'text', $this->value, $this->id);

    if($this->readonly && !$this->disabled)
    {
      $html .= '<input type="hidden" name="' . htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8') . '" value="' . (int) $this->value . '">';
    }

    return $html;
  }

  public function validate($value, $group = null, ?Registry $input = null)
  {
    $result = parent::validate($value, $group, $input);

    if($result !== true || (!$this->required && ($value === '' || $value === null)))
    {
      return $result;
    }

    $db    = $this->getDatabase();
    $query = $db->getQuery(true)->select('g.id')
      ->from($db->quoteName('#__joomgallery_users', 'g'))
      ->join('INNER', $db->quoteName('#__users', 'u') . ' ON u.id = g.cmsuser')
      ->where('g.id = ' . (int) $value);

    return ctype_digit((string) $value) && (int) $value > 0 && (bool) $db->setQuery($query)->loadResult();
  }
}
