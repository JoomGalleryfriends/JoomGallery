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

use Joomla\CMS\Form\Field\ListField;

/**
 * Form Field class for the Joomla Platform.
 * Supports a generic list of options.
 *
 * @since  4.0.0
 */
class CustomlistField extends ListField
{
    /**
     * The form field type.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $type = 'customlist';

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
          'sensitive' => $this->getAttribute('sensitive'),
        ];

        return array_merge($data, $extraData);
    }
}
