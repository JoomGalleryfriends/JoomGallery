<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

\defined('JPATH_PLATFORM') or die;

/**
* Config Interface for the Configuration Helper
*
* @since  4.0.0
*/
interface ConfigInterface
{
	/**
   * Constructor loads the currently needed configuration set
   * to its class variables
   *
   * @param   int  $id  row id of the config record to be loaded
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($id);
}
