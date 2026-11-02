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

\defined('_JEXEC') || die;

use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormRule;
use Joomla\Registry\Registry;
use Joomgallery\Component\Joomgallery\Administrator\User\ProfileLifecycle;

class UseridsRule extends FormRule
{
  public function test(\SimpleXMLElement $element, $value, $group = null, ?Registry $input = null, ?Form $form = null)
  {
    try
    {
      ProfileLifecycle::parseIds((string) $value);
      return true;
    }
    catch(\InvalidArgumentException $e)
    {
      return false;
    }
  }
}