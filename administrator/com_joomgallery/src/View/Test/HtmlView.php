<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Test;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for the testing view.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class HtmlView extends JoomGalleryView
{
  /**
   * Display the view
   *
   * @param   string  $tpl  Template name
   *
   * @return void
   *
   * @throws Exception
   */
  public function display($tpl = null)
  {
    $user = Factory::getApplication()->getIdentity();

    if(!$user->authorise('core.admin', 'com_joomgallery'))
    {
      throw new Exception('Access to this view only for super users.', 1);
    }

    ToolBarHelper::title('Testing View', 'wrench');

    // Place here yout code to test:

    parent::display($tpl);
  }
}
