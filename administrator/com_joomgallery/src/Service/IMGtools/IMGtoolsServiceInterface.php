<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools;

\defined('JPATH_PLATFORM') or die;

/**
* The IMGtools service
*
* @since  4.0.0
*/
interface IMGtoolsServiceInterface
{
  /**
	 * Storage for the IMGtools class.
	 *
	 * @var IMGtoolsInterface
	 *
	 * @since  4.0.0
	 */
	private $IMGtools;

  /**
	 * Creates the IMGtools class
   *
   * @param   string  $processor  Name of the image processor to be used
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createIMGtools($processor, $debugoutput = '', $keep_metadata = false, $keep_anim = false): void;

  /**
	 * Destroys the IMGtools class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function delIMGtools(): void;

	/**
	 * Returns the IMGtools class.
	 *
	 * @return  IMGtoolsInterface
	 *
	 * @since  4.0.0
	 */
	public function getIMGtools(): IMGtoolsInterface;
}
