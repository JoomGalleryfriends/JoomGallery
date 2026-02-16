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
 * Trait to implement AIinterfaceServiceInterface
 *
 * @since  4.4.0
 */
trait AIinterfaceServiceTrait
{
  /**
   * Storage for the AIinterface service class.
   *
   * @var AIinterfaceInterface
   *
   * @since  4.4.0
   */
  private $aiinterface = null;

  /**
   * Returns the AIinterface service class.
   *
   * @return  AIinterfaceInterface
   *
   * @since  4.4.0
   */
  public function getAccess(): AIinterfaceInterface
  {
      return $this->aiinterface;
  }

  /**
   * Creates the AIinterface service class
   *
   * @param   string   $option   Component option
   *
   * @return  void
   *
   * @since  4.4.0
   */
  public function createAIinterface($option = '')
  {
      $this->aiinterface = new AIinterface($option);

      return;
  }
}
