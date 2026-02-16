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
 * AIinterface Class
 *
 * Provides methods to integrate the AIinterface
 * https://github.com/JoomGalleryfriends/AI-Interface
 *
 * @package JoomGallery
 * @since   4.4.0
 */
class AIinterface implements AIinterfaceInterface
{
  use ServiceTrait;

  /**
   * Initialize class for specific option
   *
   * @return  void
   *
   * @since   4.4.0
   */
  public function __construct(string $option = '')
  {
    // Load application
    $this->getApp();

    // Load component
    $this->getComponent();

    // Set option
    if($option)
    {
      $this->option = $option;
    }
  }
}
