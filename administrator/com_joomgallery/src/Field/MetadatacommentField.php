<?php

/**
 ******************************************************************************************
 **   @version    4.0.0-dev                                                              **
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2023  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\CMS\Form\Field\TextField;
use \Joomla\Registry\Registry;
use \Joomgallery\Component\Joomgallery\Administrator\Helper\ConfigHelper;

/**
 * General comment field in imgmetadata.
 * This field exists to convert the old default value (empty array) of the comment field to a displayable string.
 * 
 * @since  4.1.0
 */
class MetadatacommentField extends TextField
{
    use JgMenuitemTrait;

    /**
     * The form field type.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $type = 'metadatacomment';

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
        if (is_array($data['value'])) {
            $data['value'] = "";
        }
        return $this->getRenderer($this->layout)->render($data);
    }
}
