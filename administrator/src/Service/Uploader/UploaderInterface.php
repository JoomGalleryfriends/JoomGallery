<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Uploader;

\defined('JPATH_PLATFORM') or die;

/**
* Uploader Interface for the helper classes
*
* @since  4.0.0
*/
interface UploaderInterface
{
  /**
   * Constructor
   *
   * @return  void
   *
   * @since   1.0.0
   */
  public function __construct();

	/**
	 * Method to upload a new image.
	 *
   * @param   array    $data    form data
   *
	 * @return  string   Message
	 *
	 * @since  4.0.0
	 */
	public function upload($data): string;
}
