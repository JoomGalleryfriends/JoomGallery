<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Collections list controller class.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class CollectionsController extends JoomAdminController
{
  /**
   * Proxy for getModel.
   *
   * @param   string  $name    Optional. Model name
   * @param   string  $prefix  Optional. Class prefix
   * @param   array   $config  Optional. Configuration array for model
   *
   * @return  object  The Model
   *
   * @since   DEPLOY_VERSION
   */
  public function getModel($name = 'Collection', $prefix = 'Administrator', $config = [])
  {
    return parent::getModel($name, $prefix, ['ignore_request' => true]);
  }
}
