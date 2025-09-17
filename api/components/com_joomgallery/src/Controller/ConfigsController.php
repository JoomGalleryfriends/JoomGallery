<?php
namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The configs controller
 *
 * @since  4.0.0
 */
class ConfigsController extends ApiController
{
    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.0.0
     */
    protected $contentType = 'configs';

    /**
     * The default view for the display method.
     *
     * @var    string
     * @since  3.0
     */
    protected $default_view = 'configs';

//    /**
//     * Article list view amended to add filtering of data
//     *
//     * @return  static  A BaseController object to support chaining.
//     *
//     * @since   4.0.0
//     */
//    public function displayList()
//    {
////        $model = $this->getModel($this->contentType);
////        $items = $model->getItems();
////
////        // Process and return the items
////        return $items;
//
//        $apiFilterInfo = $this->input->get('filter', [], 'array');
//        $filter        = InputFilter::getInstance();
//
//        if (\array_key_exists('created_by', $apiFilterInfo)) {
//            $this->modelState->set('filter.created_by', $filter->clean($apiFilterInfo['created_by'], 'INT'));
//        }
//
//        if (\array_key_exists('id', $apiFilterInfo)) {
//            $this->modelState->set('filter.id', $filter->clean($apiFilterInfo['id'], 'INT'));
//        }
//
//        if (\array_key_exists('title', $apiFilterInfo)) {
//            $this->modelState->set('filter.title', $filter->clean($apiFilterInfo['title'], 'STRING'));
//        }
//
//        if (\array_key_exists('alias', $apiFilterInfo)) {
//            $this->modelState->set('filter.alias', $filter->clean($apiFilterInfo['alias'], 'STRING'));
//        }
//
//        if (\array_key_exists('name', $apiFilterInfo)) {
//            $this->modelState->set('filter.name', $filter->clean($apiFilterInfo['name'], 'INT'));
//        }
//
//        if (\array_key_exists('notes', $apiFilterInfo)) {
//            $this->modelState->set('filter.notes', $filter->clean($apiFilterInfo['notes'], 'STRING'));
//        }
//
//        if (\array_key_exists('root_path', $apiFilterInfo)) {
//            $this->modelState->set('filter.root_path', $filter->clean($apiFilterInfo['root_path'], 'STRING'));
//        }
//
//	    $apiListInfo = $this->input->get('list', [], 'array');
//
//	    if (\array_key_exists('prjType', $apiFilterInfo)) {
//            $this->modelState->set('list.prjType', $filter->clean($apiListInfo['prjType'], 'INT'));
//        }
//
//	    if (\array_key_exists('direction', $apiListInfo)) {
//            $this->modelState->set('list.direction', $filter->clean($apiListInfo['direction'], 'STRING'));
//        }
//
//        return parent::displayList();
//    }

//    public function create()
//    {
//        $data = $this->input->json->get('data', [], 'array');
//
//        $model = $this->getModel($this->contentType);
//        $resourceId = $model->save($data);
//
//        if ($resourceId) {
//            return $this->displayItem($resourceId);
//        }
//
//        throw new \Exception('Failed to create resource');
//    }




    // Implement other methods like read, update, delete as needed
}