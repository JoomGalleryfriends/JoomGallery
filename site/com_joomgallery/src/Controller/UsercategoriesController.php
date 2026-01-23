<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Controller\CategoriesController as AdminCategoriesController;
use Joomla\CMS\Router\Route;

/**
 * Category controller class.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UsercategoriesController extends AdminCategoriesController
{
  use RoutingTrait;

  /**
   * Constructor.
   *
   * @param   array    $config   An optional associative array of configuration settings.
   * @param   object   $factory  The factory.
   * @param   object   $app      The Application for the dispatcher
   * @param   object   $input    Input
   *
   * @since   4.2.0
   *
   * @throws  \Exception
   */
  public function __construct($config = [], $factory = null, $app = null, $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    $this->default_view = 'usercategories';
  }
  /**
   * Method to publish a list of items
   *
   * @return  void
   *
   * @since   4.0
   */
  public function publish()
  {
    parent::publish();

    $this->setRedirect(Route::_($this->getReturnPage(), false)); // UserPanel
  }

  /**
   * Removes an item.
   *
   * @return  void
   *
   * @since   1.6
   */
  public function delete()
  {
    parent::delete();

    $this->setRedirect(Route::_($this->getReturnPage(), false)); // UserPanel
  }
}
