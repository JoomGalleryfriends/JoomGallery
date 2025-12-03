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

use \Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;
use \Joomla\CMS\Factory;
use \Joomla\CMS\Form\Field\NumberField;
use \Joomla\CMS\Language\Text;

/**
 * Number field with useglobal option based on config service 
 * 
 * @since  4.0.0
 */
class JgnumberField extends NumberField
{
  use JgMenuitemTrait;

  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  protected $type = 'jgnumber';

  /**
   * Name of the layout being used to render the field
   *
   * @var    string
   * @since  4.0.0
   */
  protected $layout = 'joomla.form.field.number';

  /**
   * Method to get the field input markup.
   *
   * @return  string  The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    $fieldname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

    if($this->element['useglobal'])
    {
      // Guess form context
      $context = ConfigHelper::getFormContext($this->form->getData());

      if($context !== false)
      {
        // Load JG config service
        $jg = Factory::getApplication()->bootComponent('com_joomgallery');
        $jg->createConfig($context[0], $context[1], false);

        // Get inherited global config value
        $value = $jg->getConfig()->get($fieldname, '0');

        if(!\is_null($value))
        {
          $value = (string) $value;

          $this->hint = Text::sprintf('JGLOBAL_USE_GLOBAL_VALUE', $value);
        }
      }
    }

    // Trim the trailing line in the layout file
    return rtrim($this->getRenderer($this->layout)->render($this->getLayoutData()), PHP_EOL);
  }
}
