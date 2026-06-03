<?php

/**
 * *********************************************************************************
 *    @package    com_joomgallery                                                 **
 *    @author     JoomGallery::ProjectTeam <team@joomgalleryfriends.net>          **
 *    @copyright  2008 - 2025  JoomGallery::ProjectTeam                           **
 *    @license    GNU General Public License version 3 or later                   **
 * *********************************************************************************
 */

namespace Joomgallery\Component\Joomgallery\Api\View\Uploadimgfile;

use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Component\Media\Administrator\Provider\ProviderManagerHelperTrait;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') || die;
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
        // created paths ?
        'id',
        'catid',
        'alias',
        'title',
        //        'description',
        //        'author',
        //
        //        'date',
        //        'imgmetadata',

        'published',
        'filename',
        'filesystem',

        //        'hits',
        //        'downloads',
        //
        //        'votes',
        //        'votesum',
        //        'approved',
        //        'useruploaded',
        //        'access',
        //        'hidden',
        //
        //        'featured',
        //        'ordering',
        //        'params',
        //        'language',

        'created_time',
        'created_by',
        'modified_time',
        'modified_by',
        'checked_out',
        'checked_out_time',

        //        'metadesc',
        //        'metakey',
        //        'robots',
    ];

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
//        foreach(FieldsHelper::getFields('com_joomgallery.images') as $field)
//        {
//            $this->fieldsToRenderItem[] = $field->name;
//        }
//
//        if(Multilanguage::isEnabled())
//        {
//            $this->fieldsToRenderItem[] = 'languageAssociations';
//            $this->relationship[]       = 'languageAssociations';
//        }

        // ToDo: load catId->image data


        return parent::displayItem();
    }

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
}
