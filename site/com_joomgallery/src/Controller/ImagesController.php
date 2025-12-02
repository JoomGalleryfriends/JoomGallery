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
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\MVC\Controller\FormController;

/**
 * Images list controller class.
 * 
 * @package JoomGallery
 * @since   4.0.0
 */
class ImagesController extends FormController
{
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
	public function getModel($name = 'Images', $prefix = 'Site', $config = array())
	{
		return parent::getModel($name, $prefix, array('ignore_request' => true));
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
    $model = $this->getModel('Imageform', 'Site');

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
