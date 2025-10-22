<?php

/**
 * @package     
 * @subpackage  
 *
 * @copyright   
 * @license     
 */

namespace Joomgallery\Component\Joomgallery\Api\View\Configs;

//use Joomgallery\Component\Joomgallery\Api\Helper\JoomgalleryHelper;
use Joomgallery\Component\Joomgallery\Api\Serializer\JoomgallerySerializer;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\TagsHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\View\JsonApiView as BaseApiView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Fields\Administrator\Helper\FieldsHelper;
use Joomla\Registry\Registry;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The configs view
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
    protected $fieldsToRenderItem = [
	    'id',
	    'asset_id',
	    'title',
	    'note',
	    'group_id',
	    'published',
	    'ordering',
	    'checked_out',
	    'checked_out_time',
	    'created_by',
	    'modified_by',
	    'jg_filesystem',
	    'jg_imagetypes',
	    'jg_pathftpupload',
	    'jg_wmfile',
	    'jg_use_real_paths',
	    'jg_router',
	    'jg_router_ids',
	    'jg_compatibility_mode',
	    'jg_replaceinfo',
	    'jg_replaceshowwarning',
	    'jg_useorigfilename',
	    'jg_uploadorder',
	    'jg_filenamenumber',
	    'jg_parallelprocesses',
	    'jg_imgprocessor',
	    'jg_fastgd2creation',
	    'jg_impath',
	    'jg_staticprocessing',
	    'jg_dynamicprocessing',
	    'jg_dynamic_watermark',
	    'jg_record_hits',
	    'jg_record_hits_select',
	    'jg_gallery_view_browse_categories_link',
	    'jg_gallery_view_class',
	    'jg_gallery_view_num_columns',
	    'jg_gallery_view_image_class',
	    'jg_gallery_view_justified_height',
	    'jg_gallery_view_justified_gap',
	    'jg_gallery_view_numb_images',
	    'jg_gallery_view_ordering',
	    'jg_gallery_view_type_image',
	    'jg_gallery_view_image_link',
	    'jg_category_view_browse_images_link',
	    'jg_category_view_subcategory_class',
	    'jg_category_view_subcategory_num_columns',
	    'jg_category_view_subcategory_image_class',
	    'jg_category_view_numb_subcategories',
	    'jg_category_view_subcategory_type_images',
	    'jg_category_view_subcategories_pagination',
	    'jg_category_view_subcategories_random_image',
	    'jg_category_view_subcategories_random_subimages',
	    'jg_category_view_class',
	    'jg_category_view_num_columns',
	    'jg_category_view_image_class',
	    'jg_category_view_justified_height',
	    'jg_category_view_justified_gap',
	    'jg_category_view_numb_images',
	    'jg_category_view_ord_images',
	    'jg_category_view_type_images',
	    'jg_category_view_pagination',
	    'jg_category_view_number_of_reloaded_images',
	    'jg_category_view_image_link',
	    'jg_category_view_caption_align',
	    'jg_category_view_images_show_title',
	    'jg_category_view_title_link',
	    'jg_category_view_lightbox_image',
	    'jg_category_view_lightbox_thumbnails',
	    'jg_category_view_show_description',
	    'jg_category_view_show_imgdate',
	    'jg_category_view_show_imgauthor',
	    'jg_category_view_show_tags',
	    'jg_detail_view_type_image',
	    'jg_detail_view_show_title',
	    'jg_detail_view_show_category',
	    'jg_detail_view_show_description',
	    'jg_detail_view_show_imgdate',
	    'jg_detail_view_show_imgauthor',
	    'jg_detail_view_show_created_by',
	    'jg_detail_view_show_votes',
	    'jg_detail_view_show_rating',
	    'jg_detail_view_show_hits',
	    'jg_detail_view_show_downloads',
	    'jg_detail_view_show_tags',
	    'jg_detail_view_show_metadata',
	    'jg_msg_upload_type',
	    'jg_msg_upload_recipients',
	    'jg_msg_download_type',
	    'jg_msg_download_recipients',
	    'jg_msg_zipdownload',
	    'jg_msg_comment_type',
	    'jg_msg_comment_recipients',
	    'jg_msg_comment_toowner',
	    'jg_msg_report_type',
	    'jg_msg_report_recipients',
	    'jg_msg_report_toowner',
	    'jg_msg_rejectimg_type',
	    'jg_msg_global_from',
	    'jg_userspace',
	    'jg_approve',
	    'jg_maxusercat',
	    'jg_maxuserimage',
	    'jg_maxuserimage_timespan',
	    'jg_maxfilesize',
	    'jg_userupload',
	    'jg_newpiccopyright',
	    'jg_uploaddefaultcat',
	    'jg_useruploadsingle',
	    'jg_maxuploadfields',
	    'jg_useruploadajax',
	    'jg_useruploadbatch',
	    'jg_special_upload',
	    'jg_newpicnote',
	    'jg_redirect_after_upload',
	    'jg_download',
	    'jg_download_hint',
	    'jg_downloadfile',
	    'jg_downloadwithwatermark',
	    'jg_showrating',
	    'jg_maxvoting',
	    'jg_ratingcalctype',
	    'jg_votingonlyonce',
	    'jg_report_images',
	    'jg_report_hint',
	    'jg_showcomments',
    ];

    /**
     * The fields to render items in the documents
     *
     * @var  array
     * @since  4.0.0
     */
    protected $fieldsToRenderList = [
	    'id',
	    'asset_id',
	    'title',
	    'note',
	    'group_id',
	    'published',
	    'ordering',
	    'checked_out',
	    'checked_out_time',
	    'created_by',
	    'modified_by',
	    'jg_filesystem',
	    'jg_imagetypes',
	    'jg_pathftpupload',
//	    'jg_wmfile',
//	    'jg_use_real_paths',
//	    'jg_router',
//	    'jg_router_ids',
//	    'jg_compatibility_mode',
//	    'jg_replaceinfo',
//	    'jg_replaceshowwarning',
//	    'jg_useorigfilename',
//	    'jg_uploadorder',
//	    'jg_filenamenumber',
//	    'jg_parallelprocesses',
//	    'jg_imgprocessor',
//	    'jg_fastgd2creation',
//	    'jg_impath',
//	    'jg_staticprocessing',
//	    'jg_dynamicprocessing',
//	    'jg_dynamic_watermark',
//	    'jg_record_hits',
//	    'jg_record_hits_select',
//	    'jg_gallery_view_browse_categories_link',
//	    'jg_gallery_view_class',
//	    'jg_gallery_view_num_columns',
//	    'jg_gallery_view_image_class',
//	    'jg_gallery_view_justified_height',
//	    'jg_gallery_view_justified_gap',
//	    'jg_gallery_view_numb_images',
//	    'jg_gallery_view_ordering',
//	    'jg_gallery_view_type_image',
//	    'jg_gallery_view_image_link',
//	    'jg_category_view_browse_images_link',
//	    'jg_category_view_subcategory_class',
//	    'jg_category_view_subcategory_num_columns',
//	    'jg_category_view_subcategory_image_class',
//	    'jg_category_view_numb_subcategories',
//	    'jg_category_view_subcategory_type_images',
//	    'jg_category_view_subcategories_pagination',
//	    'jg_category_view_subcategories_random_image',
//	    'jg_category_view_subcategories_random_subimages',
//	    'jg_category_view_class',
//	    'jg_category_view_num_columns',
//	    'jg_category_view_image_class',
//	    'jg_category_view_justified_height',
//	    'jg_category_view_justified_gap',
//	    'jg_category_view_numb_images',
//	    'jg_category_view_ord_images',
//	    'jg_category_view_type_images',
//	    'jg_category_view_pagination',
//	    'jg_category_view_number_of_reloaded_images',
//	    'jg_category_view_image_link',
//	    'jg_category_view_caption_align',
//	    'jg_category_view_images_show_title',
//	    'jg_category_view_title_link',
//	    'jg_category_view_lightbox_image',
//	    'jg_category_view_lightbox_thumbnails',
//	    'jg_category_view_show_description',
//	    'jg_category_view_show_imgdate',
//	    'jg_category_view_show_imgauthor',
//	    'jg_category_view_show_tags',
//	    'jg_detail_view_type_image',
//	    'jg_detail_view_show_title',
//	    'jg_detail_view_show_category',
//	    'jg_detail_view_show_description',
//	    'jg_detail_view_show_imgdate',
//	    'jg_detail_view_show_imgauthor',
//	    'jg_detail_view_show_created_by',
//	    'jg_detail_view_show_votes',
//	    'jg_detail_view_show_rating',
//	    'jg_detail_view_show_hits',
//	    'jg_detail_view_show_downloads',
//	    'jg_detail_view_show_tags',
//	    'jg_detail_view_show_metadata',
//	    'jg_msg_upload_type',
//	    'jg_msg_upload_recipients',
//	    'jg_msg_download_type',
//	    'jg_msg_download_recipients',
//	    'jg_msg_zipdownload',
//	    'jg_msg_comment_type',
//	    'jg_msg_comment_recipients',
//	    'jg_msg_comment_toowner',
//	    'jg_msg_report_type',
//	    'jg_msg_report_recipients',
//	    'jg_msg_report_toowner',
//	    'jg_msg_rejectimg_type',
//	    'jg_msg_global_from',
//	    'jg_userspace',
//	    'jg_approve',
//	    'jg_maxusercat',
//	    'jg_maxuserimage',
//	    'jg_maxuserimage_timespan',
//	    'jg_maxfilesize',
//	    'jg_userupload',
//	    'jg_newpiccopyright',
//	    'jg_uploaddefaultcat',
//	    'jg_useruploadsingle',
//	    'jg_maxuploadfields',
//	    'jg_useruploadajax',
//	    'jg_useruploadbatch',
//	    'jg_special_upload',
//	    'jg_newpicnote',
//	    'jg_redirect_after_upload',
//	    'jg_download',
//	    'jg_download_hint',
//	    'jg_downloadfile',
//	    'jg_downloadwithwatermark',
//	    'jg_showrating',
//	    'jg_maxvoting',
//	    'jg_ratingcalctype',
//	    'jg_votingonlyonce',
//	    'jg_report_images',
//	    'jg_report_hint',
//	    'jg_showcomments',
    ];

//    /**
//     * The relationships the item has
//     *
//     * @var    array
//     * @since  4.0.0
//     */
//    protected $relationship = [
//        'category',
//        'created_by',
//        'tags',
//    ];

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
        if (\array_key_exists('contentType', $config)) {
            $this->serializer = new JoomgallerySerializer($config['contentType']);
        }

        parent::__construct($config);
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
        foreach (FieldsHelper::getFields('com_joomgallery.configs') as $field) {
            $this->fieldsToRenderList[] = $field->name;
        }

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
        $this->relationship[] = 'modified_by';

        foreach (FieldsHelper::getFields('com_joomgallery.subproject') as $field) {
            $this->fieldsToRenderItem[] = $field->name;
        }

        if (Multilanguage::isEnabled()) {
            $this->fieldsToRenderItem[] = 'languageAssociations';
            $this->relationship[]       = 'languageAssociations';
        }

        return parent::displayItem();
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
        if (!$item) {
            return $item;
        }

        $item->text = $item->introtext . ' ' . $item->fulltext;

        // Process the joomgallery plugins.
        PluginHelper::importPlugin('joomgallery');
        Factory::getApplication()->triggerEvent('onContentPrepare', ['com_joomgallery.subproject', &$item, &$item->params]);

        foreach (FieldsHelper::getFields('com_joomgallery.subproject', $item, true) as $field) {
            $item->{$field->name} = $field->apivalue ?? $field->rawvalue;
        }

        if (Multilanguage::isEnabled() && !empty($item->associations)) {
            $associations = [];

            foreach ($item->associations as $language => $association) {
                $itemId = explode(':', $association)[0];

                $associations[] = (object) [
                    'id'       => $itemId,
                    'language' => $language,
                ];
            }

            $item->associations = $associations;
        }

        if (!empty($item->tags->tags)) {
            $tagsIds    = explode(',', $item->tags->tags);
            $item->tags = $item->tagsHelper->getTags($tagsIds);
        } else {
            $item->tags = [];
            $tags       = new TagsHelper();
            $tagsIds    = $tags->getTagIds($item->id, 'com_joomgallery.subproject');

            if (!empty($tagsIds)) {
                $tagsIds    = explode(',', $tagsIds);
                $item->tags = $tags->getTags($tagsIds);
            }
        }

//        if (isset($item->configs)) {
//            $registry     = new Registry($item->configs);
//            $item->configs = $registry->toArray();
//
//            if (!empty($item->configs['image_intro'])) {
//                $item->configs['image_intro'] = JoomgalleryHelper::resolve($item->configs['image_intro']);
//            }
//
//            if (!empty($item->configs['image_fulltext'])) {
//                $item->configs['image_fulltext'] = JoomgalleryHelper::resolve($item->configs['image_fulltext']);
//            }
//        }

        return parent::prepareItem($item);
    }

// ToDo: Later The hidden gem of the API view is another string array property, $relationship. In that view you list all the field names returned by your model which refer to related data.


}
