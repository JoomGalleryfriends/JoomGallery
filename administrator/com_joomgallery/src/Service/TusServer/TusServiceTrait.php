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

use \Joomla\CMS\Factory;
use \Joomla\CMS\Uri\Uri;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\ServerInterface;
use \Joomgallery\Component\Joomgallery\Administrator\Service\TusServer\Server;

/**
* Trait to implement TusServerInterface
*
* @since  4.0.0
*/
trait TusServiceTrait
{
  /**
	 * Storage for the Server class.
	 *
	 * @var ServerInterface
	 *
	 * @since  4.0.0
	 */
	private $tus = null;

  /**
	 * Returns the tus server class.
	 *
	 * @return  ServerInterface
	 *
	 * @since  4.0.0
	 */
	public function getTusServer(): ServerInterface
	{
		return $this->tus;
	}

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
	public function createTusServer(string $folder='', string $location = '', bool $debug=false): void
	{
    // Create and configure server
    if(empty($folder))
    {
      $folder = Factory::getApplication()->get('tmp_path');
    }
    if(empty($location))
    {
      $location = Uri::root(true) . '/administrator/index.php?option=com_joomgallery&task=images.tusupload';
    }

    $this->tus = new Server($folder, $location, $debug);

    return;
	}
}
