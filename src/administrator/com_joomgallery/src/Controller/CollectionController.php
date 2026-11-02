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
 * Form controller for collection operations.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class CollectionController extends JoomFormController
{
  /**
   * List view used after a collection form operation completes.
   *
   * @var    string
   *
   * @since  DEPLOY_VERSION
   */
  protected $view_list = 'collections';
}
