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

use \Joomla\CMS\Form\Field\RadioField;

/**
 * Form Field class for the Joomla Platform.
 * Provides radio button inputs
 *
 * @link   https://html.spec.whatwg.org/multipage/input.html#radio-button-state-(type=radio)
 * @since  4.0.0
 */
class CustomradioField extends RadioField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $type = 'customradio';

    /**
     * Method to get the data to be passed to the layout for rendering.
     *
     * @return  array
     *
     * @since   3.5
     */
    protected function getLayoutData()
    {
        $data = parent::getLayoutData();

        $extraData = array(
          'sensitive'   => $this->getAttribute('sensitive')
        );

        return \array_merge($data, $extraData);
    }
}
