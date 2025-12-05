<?php
/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\View\Configinj;

use Joomgallery\Component\Joomgallery\Api\Serializer\JoomgallerySerializer;
use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Controller\Exception\ResourceNotFound;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Database\DatabaseInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The images view
 *
 * @since  4.0.0
 */
class JsonapiView extends BaseApiView
{
    /**
     * The fields to render item in the documents
     *
     * @var  array
     * @since  4.0.0
     */
    protected $fieldsToRenderItem = [];

    /**
     * The fields to render items in the documents
     *
     * @var  array
     * @since  4.0.0
     */
    protected $fieldsToRenderList = [];

    /**
     * Constructor.
     *
     * @param   array  $config  A named configuration array for object construction.
     *                          contentType: the name (optional) of the content type to use for the serialization
     *
     * @since   4.0.0
     */
    public function __construct($config = [])
    {
        if(\array_key_exists('contentType', $config))
        {
            $this->serializer = new JoomgallerySerializer($config['contentType']);
        }

        $this->fieldsToRenderItem = $this->getConfigParameterNames();

        parent::__construct($config);
    }

    /**
     * Prepare item before render.
     *
     * @param   object  $item  The model item
     *
     * @return  object
     *
     * @since   4.0.0
     */
    protected function prepareItem($item)
    {
        // Media resources have no id.
        $item->id = '0';

        return $item;
    }


    /**
     * Method to get all configuration names
     *
     * @return  \stdClass  A file or folder object.
     *
     * @since   4.1.0
     * @throws  ResourceNotFound
     */
    public function getConfigParameterNames()
    {

        $componentName = 'com_joomgallery';

        $params   = [];
        $params[] = 'img size';
        $params[] = 'set';

        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
//            $db = $this->database;

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

            foreach($params as $name => $value)
            {
                $params[] = $name;
            }
        }
        catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage());
        }

        return $params;
    }
}
