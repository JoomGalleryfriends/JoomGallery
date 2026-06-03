<?php
/**
 * *********************************************************************************
 * @package    com_joomgallery                                                 **
 * @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 * @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 * @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Controller;

use Joomgallery\Component\Joomgallery\Api\Model\VersionModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Tobscure\JsonApi\Exception\InvalidParameterException;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;

// phpcs:enable PSR1.Files.SideEffects


class UploadimgfileController extends ApiController
{
    use ProviderManagerHelperTrait;

    /**
     * The content type of the item.
     *
     * @var    string
     * @since  4.1.0
     */
    // protected $contentType = 'uploadimgfile';
    protected $contentType = 'image';

    /**
     * The default view for the display method.
     *
     * @var    string
     *
     * @since  4.1.0
     */
    protected $default_view = 'uploadimgfile';


    public function image_upload_file()
    {
        // $image_name = $this->input->json->get('image_name', '', 'PATH');
        // $image_name    = $this->input->json->get('image_name', '', 'STRING');
        //$image_id    = $this->input->json->get('image_id', '', 'STRING');
        $category_id = $this->input->json->get('category_id', '', 'STRING');
        //$category_name = $this->input->json->get('category_name', '', 'STRING');
        $content  = $this->input->json->get('content', '', 'RAW');
        $uploader = $this->input->json->get('uploader', '', 'string');



        // ToDo: API
        $missingParameters = [];

//        if(empty($image_name))
//        {
//            $missingParameters[] = 'image_name';
//        }
//
//        if(empty($category_name))
//        {
//            $missingParameters[] = 'category_name';
//        }
//
        if (empty($image_id))
        {
            $missingParameters[] = 'image_id';
        }

        if (empty($category_id))
        {
            $missingParameters[] = 'category_id';
        }

        // Content is required as we expect a file
        if (empty($content))
        {
            $missingParameters[] = 'content';
        }

        // Content is required as we expect a file
        if (empty($uploader))
        {
            $missingParameters[] = 'uploader';
        }

//		$missingParameters[] = 'dummy';
//		$text = Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters));

        if (\count($missingParameters))
        {
            throw new InvalidParameterException(Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)));
            // throw new InvalidParameterException(Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters)));
        }

        $this->checkContent();


        //--- secure path and image name ----------------------------

//        // secure image name
//        $safeFileName = File::makeSafe($image_name);
//        $this->modelState->set('image_name', $safeFileName);
//
//        // secure category name
//        $safeCategoryName = File::makeSafe($category_name);
//        $this->modelState->set('category_name', $safeCategoryName);

//        $this->modelState->set('id', $image_id);
//        $this->modelState->set('catId', $category_id);
//        $this->modelState->set('content', $content);

        // Check if an existing file may be overwritten. Defaults to false.
        //$this->modelState->set('override', $this->input->json->get('override', false));
//        $this->modelState->set('override', $this->input->json->get('override', true)); // false

        // calls $this->save
        // parent::add();

        // $this->save();

        //--- Create the model -----------------------------------------------------------------

        /** @var VersionModel $model */
        $model = $this->getModel('image', '', ['ignore_request' => true, 'state' => $this->modelState]);

        // all variables
        $data = $this->input->json->getArray();
//
//        $data['id'] = $image_id;
//        $data['catId'] = $category_id;
//
//        unset($data['image_id']);
//        unset($data['category_id']);

        $data = [];
        $data['id'] = $this->input->json->get('image_id', '', 'STRING');
        $data['catId'] = $this->input->json->get('category_id', '', 'STRING');
        $data['content'] = $this->input->json->get('content', '', 'RAW');
        $data['content'] = base64_decode($this->input->json->get('content', '', 'RAW'));
        $data['uploader'] = $this->input->json->get('uploader', '', 'STRING');

        // $data['content'] = $content;

        // ToDo: load data from table

        // ToDo: check cat id ?

        $isSaved = $model->save($data);
        return parent::displayItem($image_id);

    public function patch_image_upload_file()
    {
        // $image_name = $this->input->json->get('image_name', '', 'PATH');
        // $image_name    = $this->input->json->get('image_name', '', 'STRING');
        $image_id    = $this->input->json->get('image_id', '', 'STRING');
        $category_id = $this->input->json->get('category_id', '', 'STRING');
        //$category_name = $this->input->json->get('category_name', '', 'STRING');
        $content  = $this->input->json->get('content', '', 'RAW');
        $uploader = $this->input->json->get('uploader', '', 'string');

        // ToDo: API
        $missingParameters = [];

//        if(empty($image_name))
//        {
//            $missingParameters[] = 'image_name';
//        }
//
//        if(empty($category_name))
//        {
//            $missingParameters[] = 'category_name';
//        }
//
        if (empty($image_id))
        {
            $missingParameters[] = 'image_id';
        }

        if (empty($category_id))
        {
            $missingParameters[] = 'category_id';
        }

        // Content is required as we expect a file
        if (empty($content))
        {
            $missingParameters[] = 'content';
        }

        // Content is required as we expect a file
        if (empty($uploader))
        {
            $missingParameters[] = 'uploader';
        }

//		$missingParameters[] = 'dummy';
//		$text = Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters));

        if (\count($missingParameters))
        {
            throw new InvalidParameterException(Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)));
            // throw new InvalidParameterException(Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters)));
        }

        $this->checkContent();

        //--- secure path and image name ----------------------------

//        // secure image name
//        $safeFileName = File::makeSafe($image_name);
//        $this->modelState->set('image_name', $safeFileName);
//
//        // secure category name
//        $safeCategoryName = File::makeSafe($category_name);
//        $this->modelState->set('category_name', $safeCategoryName);

//        $this->modelState->set('id', $image_id);
//        $this->modelState->set('catId', $category_id);
//        $this->modelState->set('content', $content);

        // Check if an existing file may be overwritten. Defaults to false.
        //$this->modelState->set('override', $this->input->json->get('override', false));
        $this->modelState->set('override', $this->input->json->get('override', true)); // false

        // calls $this->save
        // parent::add();

        // $this->save();

        //--- Create the model -----------------------------------------------------------------

        /** @var VersionModel $model */
        $model = $this->getModel('image', '', ['ignore_request' => true, 'state' => $this->modelState]);

        // all variables
//        $data = $this->input->json->getArray();
//
//        $data['id'] = $image_id;
//        $data['catId'] = $category_id;
//
//        unset($data['image_id']);
//        unset($data['category_id']);

        $data = [];
        $data['id'] = $this->input->json->get('image_id', '', 'STRING');
        $data['catId'] = $this->input->json->get('category_id', '', 'STRING');
        $data['content'] = $this->input->json->get('content', '', 'RAW');
        $data['content'] = base64_decode($this->input->json->get('content', '', 'RAW'));
        $data['uploader'] = $this->input->json->get('uploader', '', 'STRING');

        // $data['content'] = $content;

        // ToDo: load data from table

        // ToDo: check cat id ?

        $isSaved = $model->save($data);
        return parent::displayItem($image_id);
    }

//    /**
//     * Method to create or modify a file or folder.
//     *
//     * @param   integer  $recordKey  The primary key of the item (if exists)
//     *
//     * @return  string   The path
//     *
//     * @since   4.1.0
//     */
//    protected function save($recordKey = null)
//    {
//        // Explicitly get the single item model name.
//        $modelName = $this->input->get('model', Inflector::singularize($this->contentType));
//
//        /** @var MediumModel $model */
//        $model = $this->getModel($modelName, '', ['ignore_request' => true, 'state' => $this->modelState]);
//
//        $json = $this->input->json;
//
//        // Decode content, if any
//        if($content = base64_decode($json->get('content', '', 'raw')))
//        {
//            $this->checkContent();
//        }
//
//        // If there is no content, com_media assumes the path refers to a folder.
//        $this->modelState->set('content', $content);
//
//        $model->save();
//
//        return parent::displayItem($recordKey);
//    }
//


//    protected function save($recordKey = null)
//    {
//        //$tmpPath = $this->app->get('tmp_path') . '/joomgallery_api';
//
//
//        parent::save($recordKey);
//
//    }
//

    /**
     * Performs various checks to see if it is allowed to save the content.
     *
     * @return  void
     *
     * @throws  \RuntimeException
     * @since   4.1.0
     */
    private function checkContent(): void
    {
        $params       = ComponentHelper::getParams('com_media');
        $helper       = new \Joomla\CMS\Helper\MediaHelper();
        $serverlength = $this->input->server->getInt('CONTENT_LENGTH');

        // Check if the size of the request body does not exceed various server imposed limits.
        if (($params->get('upload_maxsize', 0) > 0 && $serverlength > ($params->get('upload_maxsize', 0) * 1024 * 1024)) || $serverlength > $helper->toBytes(\ini_get('upload_max_filesize')) || $serverlength > $helper->toBytes(\ini_get('post_max_size')) || $serverlength > $helper->toBytes(\ini_get('memory_limit')))
        {
            throw new \RuntimeException(Text::_('COM_MEDIA_ERROR_WARNFILETOOLARGE'), 400);
        }

        // ToDo: Image extension

    }
}
