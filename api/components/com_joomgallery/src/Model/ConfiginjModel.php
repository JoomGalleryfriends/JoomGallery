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
class ConfiginjModel extends BaseModel
{
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

//        $this->versionApiModel = new ApiModel();
    }

    /**
     * Method to get all configuration parameters
     *
     * @return  \stdClass  A file or folder object.
     *
     * @since   4.1.0
     * @throws  ResourceNotFound
     */
    public function getItem() {

        $componentName = 'com_joomgallery';

        $oConfig = new \stdClass();

        try {

            $db = Factory::getContainer()->get(DatabaseInterface::class);
//            $db = $this->database;

            $query = $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote($componentName));
            $db->setQuery($query);

            $jsonStr = $db->loadResult();
            if (!empty ($jsonStr)) {
                $params = json_decode($jsonStr, true);
            }

            $oConfig = (object) $params;

        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $oConfig;
    }


}