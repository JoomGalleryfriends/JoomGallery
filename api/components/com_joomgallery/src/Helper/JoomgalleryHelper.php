<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Helper;

use Joomla\CMS\Uri\Uri;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Content api helper.
 *
 * @since  4.0.0
 */
class JoomgalleryHelper
{
    /**
     * Fully Qualified Domain name for the image url
     *
     * @param   string  $uri      The uri to resolve
     *
     * @return  string
     */
    public static function resolve(string $uri): string
    {
        // Check if external URL.
        if(stripos($uri, 'http') !== 0)
        {
            return Uri::root() . $uri;
        }

        return $uri;
    }
}
