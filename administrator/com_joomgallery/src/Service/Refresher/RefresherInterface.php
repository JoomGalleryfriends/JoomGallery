<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Refresher;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects


/**
 * Refresher Interface for the Configuration Helper
 *
 * @since  4.0.0
 */
interface RefresherInterface
{
    /**
     * Constructor
     *
     * @param   array $params An array with optional parameters
     *
     * @return  void
     *
     * @since   1.5.5
     */
    public function __construct($params);

    /**
     * Initializes the refresher by storing current time
     *
     * @return  void
     *
     * @since   1.5.5
     */
    public function init();

    /**
     * Resets the progressbar or the name of the current task
     *
     * @param   int     $remaining  Number of remaining steps
     * @param   bool    $start      Determines whether $remaining holds the total number of steps
     * @param   string  $name       Name of the task to display
     *
     * @return  void
     *
     * @since   1.5.6
     */
    public function reset($remaining, $start, $name);

    /**
     * Checks the remaining time
     *
     * @return  bool True: Time remains, false: No more time left
     *
     * @since   1.5.5
     */
    public function check(): bool;

    /**
     * Make a redirect
     *
     * @param   int     $remaining  Number of remaining steps
     * @param   string  $task       The task which will be called after the redirect
     * @param   string  $msg        An optional message which will be enqueued (this is currently disabled)
     * @param   string  $type       Type of the message (one of 'message', 'notice', 'error')
     * @param   string  $controller The controller which will be called after the redirect
     *
     * @return  void
     *
     * @since   1.5.5
     */
    public function refresh($remaining, $task, $msg, $type, $controller);
}
