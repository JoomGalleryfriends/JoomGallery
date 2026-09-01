<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Field;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\LanguageField;

/**
 * Language selection field for JoomGallery
 *
 * @since  4.4.0
 */
class JglanguageField extends LanguageField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  4.4.0
   */
  public $type = 'jglanguage';


  /**
   * Method to get a list of languages
   *
   * @return  array  The field option objects.
   *
   * @since   4.4.0
   */
  protected function getOptions()
  {
    // Initialize needed classes
    $comp = Factory::getApplication()->bootComponent('com_joomgallery');

    if( isset($this->element['search_service']) && (string) $this->element['search_service'] == 'true' &&
        $comp->getSearch()->handlesFilter('language')
      )
    {
      // Load options from search provider
      $options = $comp->getSearch()->getFilterOptions('language');

      // Merge any additional options in the XML definition.
      return $options;
    }

    return parent::getOptions();
  }
}
