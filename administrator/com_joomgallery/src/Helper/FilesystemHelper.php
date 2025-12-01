<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;

/**
 * Helper for the filesystem adapters
 *
 * @static
 * @package JoomGallery
 * @since   4.0.0
 */
class FilesystemHelper
{
  use ProviderManagerHelperTrait;
}
