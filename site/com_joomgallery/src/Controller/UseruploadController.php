<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Site\Controller;

// No direct access
\defined('_JEXEC') or die;

use Joomgallery\Component\Joomgallery\Administrator\Controller\JoomFormController;
use Joomla\CMS\Response\JsonResponse;
use \Joomla\CMS\Router\Route;
use \Joomla\CMS\Language\Text;

/**
 * Upload controller class.
 *
 * @package JoomGallery
 * @since   4.0.0
 */
class UseruploadController extends JoomFormController
{

  /**
   * Constructor.
   *
   * @param   array    $config   An optional associative array of configuration settings.
   * @param   object   $factory  The factory.
   * @param   object   $app      The Application for the dispatcher
   * @param   object   $input    Input
   *
   * @since   4.0.0
   */
  public function __construct($config = [], $factory = null, $app = null, $input = null)
  {
    parent::__construct($config, $factory, $app, $input);

    //
    $this->default_view = 'userupload';

//    // JoomGallery extension class
//    $this->component = $this->app->bootComponent(_JOOM_OPTION);
//
//    // Access service class
//    $this->component->createAccess();
//    $this->acl = $this->component->getAccess();
  }

  /**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  object  The model
	 *
	 * @since   1.6.4
	 */
	public function getModel($name = 'UserUpload', $prefix = 'Site', $config = ['ignore_request' => true])
  {
    return parent::getModel($name, $prefix, $config);
  }



} // class
