<?php
/**
 * @package    com_joomgallery
 * @copyright  2008 - 2026 JoomGallery::ProjectTeam
 * @license    GNU General Public License version 3 or later
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Controller for the current user's AI credentials modal.
 *
 * @since  __DEPLOY_VERSION__
 */
class UserkeysController extends BaseController
{
  /**
   * Save the current user's credentials.
   *
   * @return  void
   */
  public function save(): void
  {
    if(!Session::checkToken())
    {
      throw new \RuntimeException(Text::_('JINVALID_TOKEN'), 403);
    }

    $model = $this->getModel('Userkeys', 'Administrator');
    $form  = $model->getForm();
    $data  = $this->input->post->get('jform', [], 'array');
    $valid = $form->filter($data);

    if(!$form->validate($valid))
    {
      foreach($form->getErrors() as $error)
      {
        $this->app->enqueueMessage($error instanceof \Throwable ? $error->getMessage() : (string) $error, 'error');
      }
    }
    else
    {
      $model->save($valid);
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_USERKEYS_SAVE_SUCCESS'), 'success');
    }

    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=userkeys&layout=modal&tmpl=component', false));
  }
}
