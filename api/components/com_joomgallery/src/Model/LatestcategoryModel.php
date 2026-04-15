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
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\Component\Media\Administrator\Model\ApiModel;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since  4.4.0
 */
class LatestcategoryModel extends BaseDatabaseModel
{
//    public function __construct($config = [])
//    {
//        parent::__construct($config);
//    }

    /**
     * Method to get latest gallery data
     *
     * @return  \stdClass  A file or folder object.
     *
     * @since   4.1.0
     * @throws  ResourceNotFound
     */
    public function getItem()
    {
        $oCategory = new \stdClass();

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);

            $limit = 1;

            $query = $db->createQuery()
                ->select('*')
                ->from('#__joomgallery_categories')
                ->order($db->quoteName('id') . ' DESC')
                ->setLimit($limit);
            $db->setQuery($query);

            $oCategory = $db->loadObject();

        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $oCategory;
    }

}
