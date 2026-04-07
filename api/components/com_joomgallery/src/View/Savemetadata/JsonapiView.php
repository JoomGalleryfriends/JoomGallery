<?php

/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\View\SaveMetadata;

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The version view
 *
 * @since  4.0.0
 */
class JsonapiView extends BaseApiView
{
    use ProviderManagerHelperTrait;

    /**
     * The fields to render item in the documents
     *
     * @var    array
     * @since  4.1.0
     */
    protected $fieldsToRenderItem = [
        'version',
        'creationDate',
    ];

    /**
     * Prepare item before render.
     *
     * @param   object  $item  The model item
     *
     * @return  object
     *
     * @since   4.1.0
     */
    protected function prepareItem($item)
    {
        // Media resources have no id.
        $item->id = '0';

        return $item;
    }

    /**
     * Execute and display a template script.
     *
     * @param   ?array  $items  Array of items
     *
     * @return  string
     *
     * @since   4.0.0
     */
    public function displayList(?array $items = null)
    {
//        foreach(FieldsHelper::getFields('com_joomgallery.configs') as $field)
//        {
//            $this->fieldsToRenderList[] = $field->name;
//        }

        return parent::displayList();
    }

    /**
     * Execute and display a template script.
     *
     * @param   object  $item  Item
     *
     * @return  string
     *
     * @since   4.0.0
     */
    public function displayItem($item = null)
    {
//        $this->relationship[] = 'modified_by';
//
//        foreach(FieldsHelper::getFields('com_joomgallery.subproject') as $field)
//        {
//            $this->fieldsToRenderItem[] = $field->name;
//        }
//
//        if(Multilanguage::isEnabled())
//        {
//            $this->fieldsToRenderItem[] = 'languageAssociations';
//            $this->relationship[]       = 'languageAssociations';
//        }

        return parent::displayItem();
    }





}
