<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomgallery\Component\Joomgallery\Api\Model\VersionModel;
use Joomgallery\Component\Joomgallery\Api\View\Version\JsonapiView;
use Joomla\CMS\Access\Exception\NotAllowed;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\String\Inflector;
use Tobscure\JsonApi\Exception\InvalidParameterException;


// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The version controller
 *
 * @since  4.4.0
 */
class VersionController extends ApiController
{
  /**
   * The content type of the item.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $contentType = 'version';

  /**
   * The default view for the display method.
   *
   * @var    string
   * @since  4.4.0
   */
  protected $default_view = 'version';

  /**
   * Generic method to prepare the view
   *
   * @return JsonapiView  The prepared view
   *
   * @since  4.4.0
   */
  protected function prepareView()
  {
    $viewType   = $this->app->getDocument()->getType();
    $viewName   = $this->input->get('view', $this->default_view);
    $viewLayout = $this->input->get('layout', 'default', 'string');

    try
    {
    /** @var JsonApiView $view */
    $view = $this->getView(
        $viewName,
        $viewType,
        '',
        ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]
    );
    }
    catch(\Exception $e)
    {
      throw new \RuntimeException($e->getMessage());
    }

    $modelName = $this->input->get('model', Inflector::singularize($this->contentType));

    // Create the model, ignoring request data so we can safely set the state in the request from the controller
    /** @var VersionModel $model */
    $model = $this->getModel($modelName, '', ['ignore_request' => true, 'state' => $this->modelState]);

    // test if model is valid
    if(!$model)
    {
      throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
    }

    // Push the model into the view (as default)
    $view->setModel($model, true);

    $view->setDocument($this->app->getDocument());
    $view->displayItem();

    return $view;
  }

  /**
   * @return VersionController
   *
   * @throws InvalidParameterException
   * @since  4.4.0
   */
  public function edit()
  {
    // Access check.
    if(!$this->allowEdit())
    {
      throw new NotAllowed('JLIB_APPLICATION_ERROR_CREATE_RECORD_NOT_PERMITTED', 403);
    }

    // all variables
    $data = $this->input->json->getArray();

    if(empty($data))
    {
      throw new InvalidParameterException(Text::_('No parameter given for patch config'), 403);        //  Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters))
    }

    //--- Create the model -----------------------------------------------------------------

    /** @var VersionModel $model */
    $model = $this->getModel('Version', '', ['ignore_request' => true, 'state' => $this->modelState]);

    $isSaved = $model->save($data);

    return parent::displayItem('0');
  }
}
