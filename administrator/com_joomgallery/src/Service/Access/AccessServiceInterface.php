<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Access;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Access service
 *
 * @since  4.0.0
 */
interface AccessServiceInterface
{
    /**
     * Creates the access service class
     *
     * @param   string   $option   Component option
     *
     * @return  void
     *
     * @since  4.0.0
     */
    public function createAccess($option = '');

    /**
     * Returns the access service class.
     *
     * @return  AccessInterface
     *
     * @since  4.0.0
     */
    public function getAccess(): AccessInterface;
}
