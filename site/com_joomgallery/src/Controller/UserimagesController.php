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

use \Joomla\CMS\MVC\Controller\FormController;

/**
 * User images list controller class.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class UserimagesController extends FormController
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

    // parent view
    $this->default_view = 'userimages';

//    // JoomGallery extension class
//    $this->component = $this->app->bootComponent(_JOOM_OPTION);
//
//    // Access service class
//    $this->component->createAccess();
//    $this->acl = $this->component->getAccess();
  }



  /**
	 * Proxy for getModel.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional
	 * @param   array   $config  Configuration array for model. Optional
	 *
	 * @return  object	The model
	 *
	 * @since   4.0.0
	 */
  public function getModel($name = 'Form', $prefix = 'Site', $config = ['ignore_request' => true])
  {
    return parent::getModel($name, $prefix, $config);
  }

  /**
   * Method to save the submitted ordering values for records via AJAX.
   *
   * @return  void
   *
   * @since   4.0.0
   */
  public function saveOrderAjax()
  {
    // Check for request forgeries.
    $this->checkToken();

    // Get the input
    $pks   = (array) $this->input->post->get('cid', [], 'int');
    $order = (array) $this->input->post->get('order', [], 'int');

    // Remove zero PKs and corresponding order values resulting from input filter for PK
    foreach($pks as $i => $pk)
    {
      if($pk === 0)
      {
        unset($pks[$i]);
        unset($order[$i]);
      }
    }

    // Get the model
    $model = $this->getModel('userimage', 'Site');

    // Save the ordering
    $return = $model->saveorder($pks, $order);

    if($return)
    {
      echo '1';
    }

    // Close the application
    $this->app->close();
  }
}
