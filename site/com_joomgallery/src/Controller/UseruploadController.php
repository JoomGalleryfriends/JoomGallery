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

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomgallery\Component\Joomgallery\Administrator\Controller\JoomAdminController;

/**
 * Upload controller class.
 *
 * @package JoomGallery
 * @since   4.2.0
 */
class UseruploadController extends JoomAdminController
{
  /**
   * Constructor.
   *
   * @param   array    $config   An optional associative array of configuration settings.
   * @param   object   $factory  The factory.
   * @param   object   $app      The Application for the dispatcher
   * @param   object   $input    Input
   *
   * @since   4.2.0
   */
  public function __construct($config = [], $factory = null, $app = null, $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    $this->default_view = 'userupload';
  }

  /**
   * Method to get a model object, loading it if required.
   *
   * @param   string   $name    The model name. Optional.
   * @param   string   $prefix  The class prefix. Optional.
   * @param   array    $config  Configuration array for model. Optional.
   *
   * @return  object  The model
   *
   * @since   4.2.0
   */
  public function getModel($name = 'UserUpload', $prefix = 'Site', $config = ['ignore_request' => true])
  {
    return parent::getModel($name, $prefix, $config);
  }
}
