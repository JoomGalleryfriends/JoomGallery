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

use \Joomla\CMS\Form\FormField;

/**
  * Supports an HTML select list of categories
  * 
  * @package JoomGallery
  * @since   4.0.0
  */
class FileMultipleField extends FormField
{
    /**
      * The form field type.
      *
      * @var    string
      * @since  4.0.0
      */
    protected $type = 'filemultiple';

    /**
      * Method to get the field input markup.
      *
      * @return  string    The field input markup.
      *
      * @since   4.0.0
      */
    protected function getInput()
    {
        // Initialize variables.
        $html = '<input type="file" name="' . $this->name . '[]" multiple>';

        return $html;
    }
}
