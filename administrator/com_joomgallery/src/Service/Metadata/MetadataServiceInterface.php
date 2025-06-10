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
* The Metadata service
*
* @since  4.1.0
*/
interface MetadataServiceInterface
{
  /**
	 * Creates the metadata service class
   * 
   * @param   string  $processor  Name of the metadata processor to be used
	 *
   * @return  void
   *
	 * @since  4.1.0
	 */
	public function createMetadata(string $processor);

	/**
	 * Returns the metadata service class.
	 *
	 * @return  MetadataInterface
	 *
	 * @since  4.1.0
	 */
	public function getMetadata(): MetadataInterface;
}
