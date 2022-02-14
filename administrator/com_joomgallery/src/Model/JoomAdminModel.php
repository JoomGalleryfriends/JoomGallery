<?php
/**
******************************************************************************************
**   @version    4.0.0                                                                  **
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2022  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 2 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\Model;

// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\MVC\Model\AdminModel;
use \Joomla\CMS\Factory;

/**
 * Parent Model Class for JoomGallery
 *
 * @package JoomGallery
 * @since   1.5.5
 */
abstract class JoomAdminModel extends AdminModel
{
  /**
   * Joomla\CMS\Application\AdministratorApplication
   *
   * @access  protected
   * @var     object
   */
  var $app;

  /**
   * Joomgallery\Component\Joomgallery\Administrator\Extension\JoomgalleryComponent
   *
   * @access  protected
   * @var     object
   */
  var $component;

  /**
   * JUser object, holds the current user data
   *
   * @access  protected
   * @var     object
   */
  var $user;

  /**
   * Constructor
   *
   * @access  protected
   * @return  void
   * @since   1.5.5
   */
  function __construct($config = array())
  {
    parent::__construct($config);

    $this->app       = Factory::getApplication('administrator');
    $this->component = $this->app->bootComponent(_JOOM_OPTION);
    $this->user      = Factory::getUser();
  }
}
