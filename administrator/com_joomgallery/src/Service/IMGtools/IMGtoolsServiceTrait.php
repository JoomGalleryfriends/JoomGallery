<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools;

\defined('_JEXEC') or die;

use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\IMtools;
use \Joomgallery\Component\Joomgallery\Administrator\Service\IMGtools\GDtools;

/**
* Trait to implement IMGtoolsServiceInterface
*
* @since  4.0.0
*/
trait IMGtoolsServiceTrait
{
  /**
	 * Storage for the IMGtools class.
	 *
	 * @var IMGtoolsInterface
	 *
	 * @since  4.0.0
	 */
	private $IMGtools = null;

  /**
	 * Returns the IMGtools class.
	 *
	 * @return  IMGtoolsInterface
	 *
	 * @since  4.0.0
	 */
	public function getIMGtools(): IMGtoolsInterface
	{
		return $this->IMGtools;
	}

  /**
	 * Creates the IMGtools class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function createIMGtools($processor, $keep_metadata = false, $keep_anim = false): void
	{
    switch ($processor)
    {
      case 'im':
        $this->IMGtools = new IMtools($keep_metadata, $keep_anim);
        break;

      default:
        $this->IMGtools = new GDtools($keep_metadata, $keep_anim);
        break;
    }

    return;
	}

  /**
	 * Destroys the IMGtools class
	 *
   * @return  void
   *
	 * @since  4.0.0
	 */
	public function delIMGtools(): void
	{
    unset($this->IMGtools);

    return;
	}
}
