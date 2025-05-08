<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Administrator\MVC;

// No direct access
\defined('_JEXEC') or die;

use \Joomla\CMS\Factory;
use \Joomla\CMS\MVC\Factory\MVCFactory as MVCFactoryBase;
use \Joomgallery\Component\Joomgallery\Administrator\User\User;

/**
 * Factory to create MVC objects based on a namespace.
 *
 * @since  4.0.0
 */
class MVCFactory extends MVCFactoryBase
{
  /**
   * The extensions identity object.
   *
   * @var    User
   * @since  4.0.0
   */
  protected $identity = null;

  /**
   * Get the extension identity.
   *
   * @return  User
   *
   * @since   4.0.0
   */
  public function getIdentity()
  {
    if(\is_null($this->identity))
    {
      $this->loadIdentity();
    }

    return $this->identity;
  }

  /**
   * Allows the extension to load a custom or default identity.
   *
   * @param   User  $identity  An optional identity object. If omitted, a null user object is created.
   *
   * @return  $this
   *
   * @since   4.0.0
   */
  public function loadIdentity(?User $identity = null)
  {
    if(\is_null($identity))
    {
      $appUser = Factory::getApplication()->getIdentity();
      $id = $appUser->id ?: 0;

      $this->identity = $this->getUserFactory()->loadUserById($id);
    }
    else
    {
      $this->identity = $identity;
    }

    return $this;
  }
}
