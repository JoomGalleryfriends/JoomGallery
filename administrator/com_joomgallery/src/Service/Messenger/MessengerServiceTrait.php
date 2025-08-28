<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Messenger;

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\PmMessenger;
use \Joomgallery\Component\Joomgallery\Administrator\Service\Messenger\MailMessenger;

/**
* Trait to implement MessengerServiceInterface
*
* @since  4.0.0
*/
trait MessengerServiceTrait
{
  /**
	 * Storage for the messenger service class.
	 *
	 * @var MessengerInterface
	 *
	 * @since  4.0.0
	 */
	private $messenger = null;

  /**
	 * Creates the messenger service class
   * 
   * @param   string  $msgMethod   Name of the messager to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createMessenger($msgMethod)
  {
    switch ($msgMethod)
    {
      case 'pm':
        $this->messenger = new PmMessenger();
        break;

      default:
        $this->messenger = new MailMessenger();
        break;
    }

    return;
  }

  /**
	 * Returns the messenger service class.
	 *
	 * @return  MessengerInterface
	 *
	 * @since  4.0.0
	 */
	public function getMessenger()
  {
    return $this->messenger;
  }
}
