<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Migration;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomgallery\Component\Joomgallery\Administrator\Extension\ServiceTrait;

/**
 * Targetinfo Class
 * Providing compatibility information about source or destination extension
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class Targetinfo
{
  use ServiceTrait;

  /**
   * Does this object provide info about source or destination extension?
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $target = 'source';

  /**
   * Extension name
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $extension = 'com_joomgallery';

  /**
   * Type of the extension
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $type = 'component';

  /**
   * Minimum compatible version
   * - Version string must be compatible with \version_compare()
   * - If there is no limit, add '-' as version string
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $min = '1.0.0';

  /**
   * Maximum compatible version
   * - Version string must be compatible with \version_compare()
   * - If there is no limit, add '-' as version string
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $max = '-';

  /**
   * Minimum compatible PHP version
   * - Version string must be compatible with \version_compare()
   *
   * @var  string
   *
   * @since  4.0.0
   */
  private $php_min = '7.4.0';
}
