<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Form;
use \Joomla\CMS\Event\AbstractEvent;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;

/**
 * List field with dynamic options and useglobal option based on config service 
 *
 * @since  4.0.0
 */
class JgdynamiclistField extends JglistField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'jgdynamiclist';

  /**
   * Method to get the field options.
   *
   * @return  object[]  The field option objects.
   *
   * @since   4.0.0
   */
  protected function getOptions()
  {
    $options = parent::getOptions();

    // Get script
    $script = '';
    if(isset($this->element['script']))
    {
      $script = (string) $this->element['script'];
    }
    else
    {
      return $options;
    }

    // Option 1: Plugin listening to onJoomGetOptions
    $event = AbstractEvent::create(
      'onJoomGetOptions',
      [
        'subject' => $this,
        'context' => 'com_joomgallery.config.form',
        'script'  => $script,
      ]
    );
    Factory::getApplication()->getDispatcher()->dispatch($event->getName(), $event);
    $dyn_options = $event->getArgument('result', array());

    // Option 2: Load script from ConfigHelper
    if(\method_exists('\Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper', $script))
    {
      $tmp_form    = new Form('com_joomgallery.config');
      $dyn_options = \array_merge($dyn_options, ConfigHelper::{$script}($tmp_form, false));
    }

    if(!empty($dyn_options))
    {
      \array_push($options, ...$dyn_options);
    }

    \reset($options);

    return $options;
  }
}
