<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Helper;

// No direct access
defined('_JEXEC') or die;

/**
 * Upload Helper
 *
 * - Batch (Zip) upload
 * - Single upload
 * - FTP upload
 * - Ajax upload
 *
 * @since   1.0.0
 */
class Upload
{
  /**
   * The ID of the category in which
   * the images shall be uploaded
   *
   * @var int
   */
  public $catid = 0;

  /**
   * Constructor
   *
   * @return  void
   * @since   4.0.0
   */
  public function __construct()
  {
    $this->catid = 99;
  }
}
