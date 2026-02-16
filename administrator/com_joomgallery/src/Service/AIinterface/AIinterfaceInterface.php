<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\AIinterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Interface for the AIinterface class
 *
 * @since  4.4.0
 */
interface AIinterfaceInterface
{
  /**
   * Initialize class for specific option
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function __construct(string $option = '');
}
