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

use Joomgallery\Component\Joomgallery\Administrator\Helper\JoomHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;

/**
 * Field to select a JoomGallery image ID from a modal list.
 *
 * @since  4.0.0
 */
class JgimageField extends FormField
{
  /**
   * The form field type.
   *
   * @var    string
   * @since  4.0.0
   */
  public $type = 'jgimage';

  /**
   * Filtering categories
   *
   * @var   array
   * @since 4.0.0
   */
  protected $categories = null;

  /**
   * Images to exclude from the list of images
   *
   * @var   array
   * @since 4.0.0
   */
  protected $excluded = null;

  /**
   * Layout to render
   *
   * @var   string
   * @since 4.0.0
   */
  protected $layout = 'joomla.form.field.jgimage';

  /**
   * Method to attach a Form object to the field.
   *
   * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
   * @param   mixed              $value    The form field value to validate.
   * @param   string             $group    The field name group control value. This acts as an array container for the field.
   *                                       For example if the field has name="foo" and the group value is set to "bar" then the
   *                                       full field name would end up being "bar[foo]".
   *
   * @return  boolean  True on success.
   *
   * @since   4.0.0
   *
   * @see     FormField::setup()
   */
  public function setup(\SimpleXMLElement $element, $value, $group = null)
  {
    $return = parent::setup($element, $value, $group);

    // If user can't access com_joomgallery the field should be readonly.
    if($return && !$this->readonly)
    {
      // Get access service
      $comp = Factory::getApplication()->bootComponent('com_joomgallery');
      $comp->createAccess();
      $acl = $comp->getAccess();

      $this->readonly = !$acl->checkACL('core.manage', 'com_joomgallery');
    }

    return $return;
  }

  /**
   * Method to get the user field input markup.
   *
   * @return  string  The field input markup.
   *
   * @since   4.0.0
   */
  protected function getInput()
  {
    if(empty($this->layout))
    {
      $this->component->addLog(\sprintf('%s has no layout assigned.', $this->name), 'error', 'jerror');
      throw new \UnexpectedValueException(\sprintf('%s has no layout assigned.', $this->name));
    }

    // Make sure the component is correctly set
    $renderer = $this->getRenderer($this->layout);
    $renderer->setComponent('com_joomgallery');

    return $renderer->render($this->getLayoutData());
  }

  /**
   * Get the data that is going to be passed to the layout
   *
   * @return  array
   *
   * @since   3.5
   */
  public function getLayoutData()
  {
    // Get the basic field data
    $data = parent::getLayoutData();

    // Initialize value
    $name = Text::_('COM_JOOMGALLERY_FIELDS_SELECT_IMAGE');

    if(is_numeric($this->value))
    {
      if($this->value > 0)
      {
        $img = JoomHelper::getRecord('image', $this->value);
      }

      if($this->value == 0 || !$img)
      {
        $name = '';
      }
      else
      {
        $name = $img->title;
      }
    }

    // User lookup went wrong, we assign the value instead.
    if($name === null && $this->value)
    {
        $name = $this->value;
    }

    $extraData = [
      'imageName'  => $name,
      'categories' => $this->getCats(),
      'excluded'   => $this->getExcluded(),
    ];

    return array_merge($data, $extraData);
  }

  /**
   * Method to get the filtering categories (null means no filtering)
   *
   * @return  mixed  Array of filtering categories or null.
   *
   * @since   4.0.0
   */
  protected function getCats()
  {
    if(isset($this->element['categories']))
    {
      return explode(',', $this->element['categories']);
    }
  }

  /**
   * Method to get the images to exclude from the list of images
   *
   * @return  mixed  Array of images to exclude or null to to not exclude them
   *
   * @since   4.0.0
   */
  protected function getExcluded()
  {
    if(isset($this->element['exclude']))
    {
      return explode(',', $this->element['exclude']);
    }
  }
}
