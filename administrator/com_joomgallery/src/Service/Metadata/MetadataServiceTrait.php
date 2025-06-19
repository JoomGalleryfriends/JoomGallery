<?php

/**
 ******************************************************************************************
 **   @package    com_joomgallery                                                        **
 **   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
 **   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
 **   @license    GNU General Public License version 3 or later                          **
 *****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Metadata;

\defined('_JEXEC') or die;

/**
* Trait to implement MetadataServiceInterface
*
* @since  4.1.0
*/
trait MetadataServiceTrait
{
  /**
	 * Storage for the metadata service class.
	 *
	 * @var MetadataInterface
	 *
	 * @since  4.1.0
	 */
	private $metadata = null;

  /**
	 * Returns the metadata service class.
	 *
	 * @return  MetadataInterface
	 *
	 * @since  4.1.0
	 */
	public function getMetadata(): MetadataInterface
	{
		return $this->metadata;
	}

  /**
	 * Creates the metadata service class
   * 
   * @param   string  $processor  Name of the metadata processor to be used
	 *
   * @return  void
   *
	 * @since  4.1.0
	 */
	public function createMetadata(string $processor)
	{
    switch ($processor)
    {
      case 'exiftools':
        $this->metadata = new MetadataExifTool();
        break;

      default:
        $this->metadata = new MetadataPHP();
        break;
    }

    return;
	}
}
