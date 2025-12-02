<?php
/**
  * *********************************************************************************
  *    @package    com_joomgallery                                                 **
  *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
  *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
  *    @license    GNU General Public License version 3 or later                   **
  * *********************************************************************************
  */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// No direct access
// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use \Joomla\CMS\Factory;
use \Joomla\CMS\Language\Text;
use \Joomla\Utilities\ArrayHelper;

/**
  * Tasks list controller class.
  *
  * @package JoomGallery
  * @since   4.2.0
  */
class TasksController extends JoomAdminController
{
    /**
      * Method to save the submitted ordering values for records via AJAX.
      *
      * @return  void
      *
      * @since   4.2.0
      *
      * @throws  \Exception
      */
    public function saveOrderAjax()
    {
        // Get the input
        $input = Factory::getApplication()->input;
        $pks   = $input->post->get('cid', [], 'array');
        $order = $input->post->get('order', [], 'array');

        // Sanitize the input
        ArrayHelper::toInteger($pks);
        ArrayHelper::toInteger($order);

        // Get the model
        $model = $this->getModel();

        // Save the ordering
        $return = $model->saveorder($pks, $order);

        if($return)
        {
            echo '1';
        }

        // Close the application
        Factory::getApplication()->close();
    }

    /**
      * Proxy for getModel.
      *
      * @param   string  $name    Optional. Model name
      * @param   string  $prefix  Optional. Class prefix
      * @param   array   $config  Optional. Configuration array for model
      *
      * @return  object  The Model
      *
      * @since   4.2.0
      */
    public function getModel($name = 'Task', $prefix = 'Administrator', $config = [])
    {
        return parent::getModel($name, $prefix, ['ignore_request' => true]);
    }
}
