<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Rule;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Service\UserProfile\UserProfile;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;

/**
 * Form rule validating comma-separated Joomla user IDs and inclusive ranges.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class UseridsRule extends FormRule
{
  /**
   * Validate the configured Joomla user-ID selection.
   *
   * @param   \SimpleXMLElement  $element  Form field XML definition.
   * @param   mixed              $value    Submitted field value.
   * @param   string|null        $group    Optional field group.
   * @param   Registry|null      $input    Complete submitted form data.
   * @param   Form|null          $form     Form being validated.
   *
   * @return  boolean  True when the ID list is valid.
   *
   * @since   DEPLOY_VERSION
   */
  public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
  {
    try
    {
      UserProfile::parseIds((string) $value);

      return true;
    }
    catch(\InvalidArgumentException $e)
    {
      return false;
    }
  }
}
