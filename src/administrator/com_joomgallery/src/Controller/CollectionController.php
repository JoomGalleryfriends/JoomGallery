<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Administrator\Controller;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

/**
 * Form controller for collection operations.
 *
 * @package JoomGallery
 * @since   DEPLOY_VERSION
 */
class CollectionController extends JoomFormController
{
  /**
   * List view used after a collection form operation completes.
   *
   * @var    string
   *
   * @since  DEPLOY_VERSION
   */
  protected $view_list = 'collections';

  /** Existing records are managed only through the restricted modal actions. */
  public function execute($task)
  {
    if(!\in_array($task, ['add', 'save', 'cancel', 'manage'], true))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    return parent::execute($task);
  }

  public function add()
  {
    if(!$this->getAcl()->checkACL('core.create', 'collection'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collection&layout=edit', false));

    return true;
  }

  public function save($key = null, $urlVar = null)
  {
    $this->checkToken();

    if(!$this->getAcl()->checkACL('core.create', 'collection'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $data = $this->input->post->get('jform', [], 'array');

    if(!empty($data['id']) || $this->input->getInt('id'))
    {
      throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
    }

    $model = $this->getModel('Collection', 'Administrator');
    $form  = $model->getCreationForm();
    $valid = $form ? $model->validate($form, $data) : false;

    try
    {
      if($valid === false || !$model->createCollection($valid))
      {
        throw new \RuntimeException(implode("\n", array_map(static fn($error) => $error instanceof \Throwable ? $error->getMessage() : (string) $error, $model->getErrors())));
      }
    }
    catch(\Throwable $e)
    {
      $this->app->setUserState('com_joomgallery.collection.create.data', $data);
      $this->app->enqueueMessage($e->getMessage(), 'error');
      $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collection&layout=edit', false));

      return false;
    }

    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_COLLECTION_CREATED'));
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));

    return true;
  }

  public function cancel($key = null)
  {
    $this->checkToken();
    $this->app->setUserState('com_joomgallery.collection.create.data', []);
    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));

    return true;
  }

  public function manage(): void
  {
    $this->checkToken();
    $model = $this->getModel('Collection', 'Administrator');

    try
    {
    $model->manageCollection(
        $this->input->post->getInt('id'),
        $this->input->post->getCmd('operation'),
        $this->input->post->getInt('userid')
    );
      $this->app->enqueueMessage(Text::_('COM_JOOMGALLERY_COLLECTION_UPDATED'));
    }
    catch(\Throwable $e)
    {
      $this->app->enqueueMessage($e->getMessage(), 'error');
    }

    $this->setRedirect(Route::_('index.php?option=com_joomgallery&view=collections', false));
  }
}
