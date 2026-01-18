<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Category controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class CategoryController extends JoomFormController
{
    protected $view_list = 'categories';

    /**
     * Method to save a record.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key (sometimes required to avoid router collisions).
     *
     * @return  boolean  True if successful, false otherwise.
     *
     * @since   4.0.0
     */
    public function save($key = null, $urlVar = null)
    {
        $task = $this->getTask();

        // The save2copy task needs to be handled slightly differently.
        if($task === 'save2copy')
        {
            $this->input->set('origin_id', $this->input->getInt('id'));
        }

        return parent::save($key, $urlVar);
    }
}
