<?php
/**
 * @package     Joomgallery\Component\Joomgallery\Api\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Joomgallery\Component\Joomgallery\Api\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\Component\Media\Administrator\Model\ApiModel;
use Joomla\Database\DatabaseInterface;

/**
 *
 *
 * @since  4.2.0
 */
class VersionModel extends BaseModel
{
    /**
     * Instance of com_media's ApiModel
     *
     * @var ApiModel
     * @since  4.1.0
     */
    private $versionApiModel;

    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->versionApiModel = new ApiModel();
    }

    /**
     * Method to get a single files or folder.
     *
     * @return  \stdClass  A file or folder object.
     *
     * @since   4.1.0
     * @throws  ResourceNotFound
     */
    public function getItem() {

        $componentName = 'com_joomgallery';

        $oVersion = new \stdClass();
        $oVersion->version = "xx.xx.xx";
        $oVersion->creationDate = "2025.xx.xx";

        try {

            //$db = Factory::getDbo();
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->select($db->quoteName('manifest_cache'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote($componentName));
            $db->setQuery($query);

            $manifest = json_decode($db->loadResult(), true);

            $oVersion->version = $manifest['version'];
            $oVersion->creationDate = $manifest['creationDate'];

        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $oVersion;
    }
}