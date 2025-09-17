<?php
/**
******************************************************************************************
**   @package    com_joomgallery                                                        **
**   @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>                 **
**   @copyright  2008 - 2025  JoomGallery::ProjectTeam                                  **
**   @license    GNU General Public License version 3 or later                          **
*****************************************************************************************/

namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\CMS\MVC\View\JsonApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\String\Inflector;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The joomgallery controller
 *
 * @since  4.0.0
 */
class JoomgalleryController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $contentType = 'joomgallery';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  3.0
     */
    protected $default_view = 'joomgallery';

  public function version()
  {
    $viewType   = $this->app->getDocument()->getType();
    $viewName   = $this->input->get('view', $this->default_view);
    $viewLayout = $this->input->get('layout', 'default', 'string');

    echo "test" . "\r\n";
    echo "\$viewType $viewType " . "\r\n";


    try {
      /** @var JsonApiView $view */
      $view = $this->getView(
        $viewName,
        $viewType,
        '',
        ['base_path' => $this->basePath, 'layout' => $viewLayout, 'contentType' => $this->contentType]
      );
    } catch (\Exception $e) {
      throw new \RuntimeException($e->getMessage());
    }

    $view->setDocument($this->app->getDocument());

    $view->displayJGVersion();

//    $data['versionText'] = "Version=xxxx"; //     $versionText = "Version=xxxx";
//
//    return $data;

    return $this;
  }


  public function displayItem($id = null)
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
		} catch (\Exception $e) {
			throw new \RuntimeException($e->getMessage());
		}

		$modelName = $this->input->get('model', Inflector::singularize($this->contentType));

		// Create the model, ignoring request data so we can safely set the state in the request from the controller
		$model = $this->getModel($modelName, '', ['ignore_request' => true, 'state' => $this->modelState]);

		if (!$model) {
			throw new \RuntimeException(Text::_('JLIB_APPLICATION_ERROR_MODEL_CREATE'));
		}

		try {
			$modelName = $model->getName();
		} catch (\Exception $e) {
			throw new \RuntimeException($e->getMessage());
		}

		// Push the model into the view (as default)
		$view->setModel($model, true);

		$view->setDocument($this->app->getDocument());
		// works if function in jsonApi is set
		// $view->displayItem();
		// works if function in jsonApi is set
		$view->display();

		return $this;
	}





}