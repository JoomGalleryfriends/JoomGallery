<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Button\ActionButton;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;

/**
 * The ApprovedButton class.
 *
 * @since  4.0.0
 */
class ApprovedButton extends ActionButton
{
  /**
   * Configure this object.
   *
   * @return  void
   *
   * @since  4.0.0
   */
  protected function preprocess()
  {
    $this->addState(1, 'unapproved', 'approve', Text::_('COM_JOOMGALLERY_UNAPPROVE_ITEM'), ['tip_title' => Text::_('COM_JOOMGALLERY_APPROVED')]);
    $this->addState(0, 'approved', 'unapprove', Text::_('COM_JOOMGALLERY_APPROVE_ITEM'), ['tip_title' => Text::_('COM_JOOMGALLERY_UNAPPROVED')]);
  }

  /**
   * Render action button by item value.
   *
   * @param   integer|null  $value      Current value of this item.
   * @param   integer|null  $row        The row number of this item.
   * @param   array         $options    The options to override group options.
   *
   * @return  string  Rendered HTML.
   *
   * @since  4.0.0
   */
  public function render(?int $value = null, ?int $row = null, array $options = []): string
  {
    return parent::render($value, $row, $options);
  }

  /**
   * Method to get the CSS class name for an icon identifier.
   *
   * Can be redefined in the final class.
   *
   * @param   string  $identifier  Icon identification string.
   *
   * @return  string  CSS class name.
   *
   * @since   4.0.0
   */
  public function fetchIconClass(string $identifier): string
  {
    // It's an ugly hack, but this allows templates to define the icon classes for the toolbar
    $layout = new FileLayout('joomla.button.iconclass');

    switch($identifier)
    {
      case 'approve':
        $identifier = 'publish';
        break;

      case 'unapprove':
        $identifier = 'unpublish';
        break;

      default:
        break;
    }

    return $layout->render(['icon' => $identifier]);
  }
}
