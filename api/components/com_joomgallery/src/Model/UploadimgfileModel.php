<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\MVC\Controller\Exception\Save;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\Component\Media\Administrator\Exception\FileExistsException;
use Joomla\Component\Media\Administrator\Exception\FileNotFoundException;
use Joomla\Component\Media\Administrator\Exception\InvalidPathException;
use Joomla\Component\Media\Administrator\Model\ApiModel;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since  4.2.0
 */
class UploadimgfileModel extends BaseModel
{
    use ProviderManagerHelperTrait;

    /**
     * Instance of com_media's ApiModel
     *
     * @var ApiModel
     * @since  4.1.0
     */
//    private $versionApiModel;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->uploadApiModel = new ApiModel();
    }

//    /**
//     * Method to get a single files or folder.
//     *
//     * @return  \stdClass  A file or folder object.
//     *
//     * @throws  ResourceNotFound
//     * @since   4.1.0
//     */
//    public function getItem()
//    {
//        // ToDo; fill out later
//        $componentName = 'com_joomgallery';
//
//        $oVersion = new \stdClass();
//
//        return $oVersion;
//    }
//

    /**
     * Method to save a file or folder.
     *
     * @param   string  $path  The primary key of the item (if exists)
     *
     * @return  string   The path
     *
     * @throws  Save
     * @since   4.1.0
     *
     */
    public function save($path = null): string
    {
        $image_name = $this->getState('image_name', null);
        $category_id = $this->getState('category_id', false);
        $content    = $this->getState('content', null);
        $override   = $this->getState('override', false);

        $resultPath = '';

        //--- create path ----------------------------------

        // ToDo: use db to retrieve $category_path from image id
        // $path = 'local-image' . ':/' . implode('/', $paths);
//        $path = 'local-image:/' . 'joomgallery/' . $category_path . '/' . $image_name;
        $category_path = $this->getState('category_path', false);
        $category_path = "api-05";
        $path        = '/joomgallery/originals/' . $category_id . '/' . $image_name;
        $adapterName = 'local-images';

        //--- ToDo: validate path ------------------------------

        try {
            //--- write file ------------------------------

            if ($path && $content) {
                // com_media expects separate directory and file name.
                $basename = basename($path);
                $dirname  = \dirname($path);

                $name = $this->uploadApiModel->createFile(
                    $adapterName,
                    $basename,
                    $dirname,
                    $content,
                    $override,
                );

                $resultPath = $dirname . '/' . $name;
            }
        } catch (FileNotFoundException) {
            throw new Save(
                Text::sprintf(
                    'WEBSERVICE_COM_MEDIA_FILE_NOT_FOUND',
                    $dirname . '/' . $basename,
                ),
                404,
            );
        } catch
        (FileExistsException) {
            throw new Save(
                Text::sprintf(
                    'WEBSERVICE_COM_MEDIA_FILE_EXISTS',
                    $dirname . '/' . $basename,
                ),
                400,
            );
        } catch
        (InvalidPathException) {
            throw new Save(
                Text::sprintf(
                    'WEBSERVICE_COM_MEDIA_BAD_FILE_TYPE',
                    $dirname . '/' . $basename,
                ),
                400,
            );
        }

        // If we still have no result path, something fishy is going on.
        if (empty($resultPath)) {
            throw new Save(
                Text::_(
                    'WEBSERVICE_COM_MEDIA_UNSUPPORTED_PARAMETER_COMBINATION'
                ),
                400
            );
        }

        // Return resulting path with the requested adapter in it
        return $adapterName . ':/' . $resultPath;
    }

}
