<?php

/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\Registry\Registry;
use \Joomla\CMS\Form\Field\TextField;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;

/**
 * UserComment Field for EXIF in imgmetadata.
 * This field exists to keep the same format UserComment was saved in before PEL was integrated.
 * 
 * @since  4.0.0
 */
class ExifusercommentField extends TextField
{
    use JgMenuitemTrait;

    /**
     * The form field type.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $type = 'exifusercomment';

    /**
     * Name of the layout being used to render the field
     *
     * @var    string
     * @since  4.0.0
     */
    protected $layout = 'joomla.form.field.text';

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     *
     * @since   4.0.0
     */
    protected function getInput()
    {
        $fieldname = \preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

        if ($this->element['useglobal']) {
            // Guess form context
            $context = ConfigHelper::getFormContext($this->form->getData());

            if ($context !== false) {
                // Load JG config service
                $jg = Factory::getApplication()->bootComponent('com_joomgallery');
                $jg->createConfig($context[0], $context[1], false);

                // Get inherited global config value
                $value = $jg->getConfig()->get($fieldname, '...');

                if (!\is_null($value)) {
                    $value = (string) $value;

                    $this->hint = Text::sprintf('JGLOBAL_USE_GLOBAL_VALUE', $value);
                }
            }
        }
        
        $data = $this->getLayoutData();
        $data['value'] = substr($data['value'], 8);

        return $this->getRenderer($this->layout)->render($data);
    }
}
