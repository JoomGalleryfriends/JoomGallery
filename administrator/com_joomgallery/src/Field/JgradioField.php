<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Radio field with useglobal option based on config service
 *
 * @since  4.0.0
 */
class JgradioField extends JglistField
{
  use JgMenuitemTrait;

  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'jgradio';

  /**
   * Method to get the field input markup for a generic list.
   * Use the multiple attribute to enable multiselect.
   *
   * @return  string  The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    if($this->element['useglobal'])
    {
      $this->layout = 'joomla.form.field.radio.configbtns';
    }

    $data = $this->getLayoutData();

    $data['options'] = (array) $this->getOptions();

    if($this->element['useglobal'])
    {
      $data['globvalue'] = $this->getGlobalValue(0);
    }

    return $this->getRenderer($this->layout)->render($data);
  }

  /**
   * Method to get the data to be passed to the layout for rendering.
   *
   * @return  array
   *
   * @since   4.0.0
   */
  protected function getLayoutData()
  {
    $data = parent::getLayoutData();

    $extraData = [
      'options' => $this->getOptions(),
      'value'   => (string) $this->value,
    ];

    return array_merge($data, $extraData);
  }
}
