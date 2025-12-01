<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Service\Config;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
* Interface for the configuration classes
*
* @since  4.0.0
*/
interface ConfigInterface
{
	/**
   * Loading the calculated settings for a specific content
   * to class properties
   *
   * @param   string   $context   Context of the content (default: com_joomgallery)
   * @param   int      $id        ID of the contenttype if needed (default: null)
   * @param   bool		 $inclOwn   True, if you want to include settings of current item (default: true)
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function __construct($context = 'com_joomgallery', $id = null, $inclOwn = true);

  /**
   * Writes params from database record to class properties
   *
   * @param   array  $params  Array of config params
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function setParamsToClass($params);

  /**
   * Store the current available caches to the session
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function storeCacheToSession();

  /**
   * Empty all the cache
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function emptyCache();
}
