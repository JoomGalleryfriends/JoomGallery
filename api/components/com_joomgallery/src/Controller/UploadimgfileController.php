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

use Joomgallery\Component\Joomgallery\Administrator\Model\ImageModel;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\ApiController;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
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
    protected $contentType = 'image';

    /**
     * The default view for the display method.
     *
     * @var    string
     *
     * @since  4.1.0
     */
    protected $default_view = 'images';

    /**
     *
     * @return UploadimgfileController
     *
     * @throws InvalidParameterException
     * @since version
     */
    public function image_data_upload()
    {
        //--- DB parameter ------------------------------------------

        $srcFilename  = $this->input->json->getString('filename');
        $srcExtension = $this->input->json->getString('file_extension');

        // from json *.http parameter
        $catId = $this->input->json->getString('catid');

        //--- upload --------------------------------------------

        $content  = $this->input->json->get('content', '', 'RAW');
//        $uploader = $this->input->json->get('uploader', '', 'string');

        //--- Missing parameters --------------------------------------------

        $missingParameters = [];

        if(empty($srcFilename))
        {
            $missingParameters[] = 'filename';
        }

        if(empty($srcExtension))
        {
            $missingParameters[] = 'file_extension';
        }

        if(empty($catId))
        {
            $missingParameters[] = 'catid';
        }

        // Content is required as we expect a file
        if (empty($content))
        {
            $missingParameters[] = 'content';
        }

//        // Content is required as we expect a file
//        if (empty($uploader))
//        {
//            $missingParameters[] = 'uploader';
//        }

//		$missingParameters[] = 'dummy';
//		$text = Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters));

        if (\count($missingParameters))
        {
            // throw new InvalidParameterException(Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)));
            throw new InvalidParameterException(Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters)));
        }

        //--- secure path and image name ----------------------------

        // ToDo:
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

        // check size early
        $this->checkContent();

        //--- Create the backend JG image model ---------------------------------------------------------------

        /** @var ImageModel $model */
        $model = $this->getModel('image', '', ['ignore_request' => true, 'state' => $this->modelState]);

        //--- Fetch data and correct  -----------------------------------------------------------------

        // all variables
        $data = json_decode($this->input->json->getRaw(), true);

        // Uploader may be set from outside in the future
        if (empty ($data['uploader']))
        {
            $data['uploader'] = 'api';
        }

        // ToDo: @Manuel Assign user from API by UserId / name?
        //    $user = Factory::getUser();
        //    $app  = Factory::getApplication();

        //--- Save image data/file  -----------------------------------------------------------------

        $isSaved = $model->save($data);
        if ($isSaved)
        {
            //--- Return json with created DB data -----------------------------------------------------------------

            $imageState = $model->getState('image');
            $image_id      = $imageState->id;

            return parent::displayItem($image_id);
        } else {
            throw new \RuntimeException(Text::_('UploadimgfileController: Could not save the image'), 500);
        }
    }

    public function patch_image_upload_file()
    {
        // $image_name = $this->input->json->get('image_name', '', 'PATH');
        // $image_name    = $this->input->json->get('image_name', '', 'STRING');
        //$image_id    = $this->input->json->get('image_id', '', 'STRING');
        $image_id    = $this->input->getInt('image_id');
        // $category_id = $this->input->json->get('catid', '', 'STRING');
        $category_id = $this->input->getInt('catid');
        //$category_name = $this->input->json->get('category_name', '', 'STRING');
        $content  = $this->input->json->get('content', '', 'RAW');

        // ToDo: API
        $missingParameters = [];

        if (empty($image_id))
        {
            $missingParameters[] = 'image_id';
        }

//        if (empty($category_id))
//        {
//            $missingParameters[] = 'category_id';
//        }
//
        // Content is required as we expect a file
        if (empty($content))
        {
            $missingParameters[] = 'content';
        }

//        // Content is required as we expect a file
//        if (empty($uploader))
//        {
//            $missingParameters[] = 'uploader';
//        }

//		$missingParameters[] = 'dummy';
//		$text = Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters));

        if (\count($missingParameters))
        {
            throw new InvalidParameterException(Text::sprintf('WEBSERVICE_COM_MEDIA_MISSING_REQUIRED_PARAMETERS', implode(' & ', $missingParameters)));
            // throw new InvalidParameterException(Text::sprintf('Missing required parameter(s): %s', implode(' & ', $missingParameters)));
        }

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

        // check size early
        $this->checkContent();

        //--- Create the backend JG image model ---------------------------------------------------------------

        /** @var ImageModel $model */
        $model = $this->getModel('image', '', ['ignore_request' => true, 'state' => $this->modelState]);

        //--- Fetch data and correct  -----------------------------------------------------------------

        // all external variables
        $apiData = json_decode($this->input->json->getRaw(), true);

        $apiData['id'] = $image_id;

//        // Parameters which should be taken from db if not given as parameters
//        [$catId_db, $alias_db, $published_db] = $this->catId_byDB($image_id);

        // not given as API parameter
        if (empty ($apiData['catid']))
        {
            // given by api route
            if (!empty($category_id)) {
                // given from API route
                $apiData['catid'] = $category_id;
            }
        }

//        // not given as API parameter
//        if (empty ($data['alias']))
//        {
//            // fetch from database
//            $data['alias'] = $alias_db;
//        }
//
//        // not given as API parameter
//        if (empty ($data['published']))
//        {
//            // fetch from database
//            $data['published'] = $published_db;
//        }

        // Uploader may be set from outside in the future
        if (empty ($apiData['uploader']))
        {
            $apiData['uploader'] = 'api';
        }

        // Merge api data into image db data
        $data = $this->mergeDbData($image_id, $apiData);

        //--- Save image data/file  -----------------------------------------------------------------

        $isSaved = $model->save($data);
        if ($isSaved)
        {
            //--- Return json with created DB data -----------------------------------------------------------------

            return parent::displayItem($image_id);

        } else {
            throw new \RuntimeException(Text::_('UploadimgfileController: Could not save the image'), 500);
        }
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

    /**
     * Method to allow extended classes to manipulate the data to be saved for an extension.
     *
     * @param   array  $data  An array of input data.
     *
     * @return  array
     *
     * @since   4.0.0
     */
    protected function preprocessSaveData(array $data): array
    {

//        // If we are updating an item the template is a readonly property based on the ID
//        if ($this->input->getMethod() === 'PATCH')
//        {
//            if (\array_key_exists('template', $data))
//            {
//                unset($data['template']);
//            }
//
//        }

        return $data;
    }

//    private function catId_byDB(int $image_id): array
//    {
//        $catId = 0;
//        $alias = 0;
//        $published = 0;
//
//        try {
//            $db = Factory::getContainer()->get(DatabaseInterface::class);
//
//            $query = $db->createQuery()
//                ->select('catId, alias, published')
//                ->from('#__joomgallery')
//                ->where($db->quoteName('id') . ' = :id')
//                ->bind(':id', $image_id, ParameterType::INTEGER);
//            $db->setQuery($query);
//
//            $oImage = $db->loadObject();
//
//            $catId = $oImage->catId;
//            $alias = $oImage->alias;
//            $published = $oImage->published;
//
//        }
//        catch (\Exception $e) {
//            throw new \RuntimeException($e->getMessage());
//        }
//
//        return [$catId, $alias, $published];
//    }

    /**
     * Merge api data into image db data
     * @param   int    $image_id
     * @param   array  $apiData
     *
     * @return array
     *
     * @since version
     */
    private function mergeDbData(int $image_id, array $apiData): array
    {
        $data = $apiData;

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->createQuery()
                ->select('*')
                ->from('#__joomgallery')
                ->where($db->quoteName('id') . ' = :id')
                ->bind(':id', $image_id, ParameterType::INTEGER);
            $db->setQuery($query);

            $dbData = $db->loadAssoc();

            $data = array_merge($dbData, $apiData);
        }
        catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $data;
    }
}
