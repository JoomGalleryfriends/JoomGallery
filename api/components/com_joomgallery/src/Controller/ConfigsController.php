<?php
/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomla\CMS\MVC\Controller\ApiController;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The configs controller
 *
 * @since  4.4.0
 */
class ConfigsController extends ApiController
{
  /**
   * The content type of the item.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $contentType = 'configs';

  /**
   * The default view for the display method.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $default_view = 'configs';

  // Implement other methods like read, update, delete as needed
}
