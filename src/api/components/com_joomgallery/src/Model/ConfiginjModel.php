<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2026  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\MVC\Model\BaseModel;
use Joomla\Component\Media\Administrator\Model\ApiModel;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The config jommla part model
 *
 * @since  4.4.0
 */
class ConfiginjModel extends BaseModel
{
    public function __construct($config = [])
    {
        parent::__construct($config);
    }

    /**
     * Method to get all configuration parameters
     *
     * @return  \stdClass  A file or folder object.
     *
     * @throws  ResourceNotFound
     * @since  4.4.0
     */
    public function getItem()
    {

        $componentName = 'com_joomgallery';

        $oConfig = new \stdClass();

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $query = $db->getQuery(true)
                ->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote($componentName));
            $db->setQuery($query);

            $jsonStr = $db->loadResult();

            if(!empty($jsonStr))
            {
                $params = json_decode($jsonStr, true);
            }

            $oConfig = (object) $params;
        }
        catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $oConfig;
    }
}
