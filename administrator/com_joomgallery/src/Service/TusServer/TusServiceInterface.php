<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\TusServer;

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\ServerInterface;

/**
* The TUS server service
*
* @since  4.0.0
*/
interface TusServiceInterface
{
  /**
	 * Creates the tus server class
   * 
   * @param   string   Upload folder path
   * @param   string   TUS server implementation location (URI)
   * @param   bool     True if debug mode should be activated
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createTusServer(string $folder='', string $location = '', bool $debug=false): void;

	/**
	 * Returns the tus server class.
	 *
	 * @return  ServerInterface
	 *
	 * @since  4.0.0
	 */
	public function getTusServer(): ServerInterface;
}
