<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomgallery\Component\Joomgallery\Api\View\Version\JsonapiView;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\String\Inflector;
//use Joomla\CMS\MVC\View\JsonApiView;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The version controller
 *
 * @since  4.0.0
 */
class VersionController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $contentType = 'version';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  3.0
     */
    protected $default_view = 'version';

    /**
     * Call version model
     * @param $cachable
     * @param $urlparams
     *
     * @return $this|VersionController
     *
     * @since version
     */
    public function display($cachable = false, $urlparams = [])
    {
        $viewType   = $this->app->getDocument()->getType();
        $viewName   = $this->input->get('view', $this->default_view);
        $viewLayout = $this->input->get('layout', 'default', 'string');

        try {
            /** @var JsonApiView $view */
            $view = $this->getView(
                $viewName,
                $viewType,
                '',
                ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]
            );
        }
        catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        $modelName = $this->input->get('model', Inflector::singularize($this->contentType));

        // Create the model, ignoring request data so we can safely set the state in the request from the controller
        $model = $this->getModel($modelName, '', ['ignore_request' => true, 'state' => $this->modelState]);

        if(!$model)
        {
            throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
        }

        // test if model is valid
        try {
            $modelName = $model->getName();
        }
        catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        // Push the model into the view (as default)
        $view->setModel($model, true);

        $view->setDocument($this->app->getDocument());
        $view->displayItem();

        return $this;
    }
}
