<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\View\License;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\View\JoomGalleryView;

/**
 * View for native JoomGallery license-term confirmation.
 *
 * @package JoomGallery
 * @since   __DEPLOY_VERSION__
 */
class HtmlView extends JoomGalleryView
{
  /** @var array Normalized protected-resource request. */
  public array $request = [];

  /**
   * Load the protected request and render the confirmation form.
   *
   * @param   string|null  $tpl  Optional template name.
   *
   * @return  void
   */
  public function display($tpl = null): void
  {
    $this->request = (array) $this->app->getUserState('com_joomgallery.license.request', []);

    if(empty($this->request['id']) || empty($this->request['type']) || empty($this->request['terms']))
    {
      throw new \RuntimeException('Invalid license confirmation request.', 400);
    }

    parent::display($tpl);
  }
}
