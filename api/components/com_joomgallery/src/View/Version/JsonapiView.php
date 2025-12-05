<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\View\Version;

use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The version view
 *
 * @since  4.0.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * The fields to render item in the documents
     *
     * @var    array
     * @since  4.1.0
     */
    protected $fieldsToRenderItem = [
      'version',
      'creationDate',
    ];

    /**
     * Prepare item before render.
     *
     * @param   object  $item  The model item
     *
     * @return  object
     *
     * @since   4.1.0
     */
    protected function prepareItem($item)
    {
        // Version item has no id.
        $item->id = '0';

        return $item;
    }
}
