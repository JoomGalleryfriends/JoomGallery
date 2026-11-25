<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\View\Userkeys;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;
use Joomla\CMS\Factory;

/**
 * AI credential modal view.
 *
 * @since  __DEPLOY_VERSION__
 */
class HtmlView extends JoomGalleryView
{
  protected $form;

  /** Display the modal view. */
  public function display($tpl = null)
  {
    if((int) Factory::getApplication()->getIdentity()->id < 1)
    {
      throw new \RuntimeException('JERROR_ALERTNOAUTHOR', 403);
    }

    $this->form = $this->getModel()->getForm();

    parent::display($tpl);
  }
}
