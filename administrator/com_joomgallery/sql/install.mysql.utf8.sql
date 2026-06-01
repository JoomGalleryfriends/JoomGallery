SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Table structure for table `#__joomgallery`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`catid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`alias` VARCHAR(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT "",
`title` VARCHAR(255) NOT NULL DEFAULT "",
`description` TEXT NOT NULL,
`author` VARCHAR(50) NULL DEFAULT "",
`date` DATETIME NOT NULL,
`imgmetadata` TEXT NOT NULL,
`published` TINYINT(1)  NOT NULL DEFAULT 0,
`filename` VARCHAR(255) NOT NULL,
`filesystem` VARCHAR(100) NOT NULL DEFAULT "local-images",
`hits` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`downloads` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`votes` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`votesum` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`approved` TINYINT(1) NOT NULL DEFAULT 0,
`useruploaded` TINYINT(1) NOT NULL DEFAULT 0,
`access` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`hidden` TINYINT(1) NOT NULL DEFAULT 0,
`featured` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT "Set if image is featured.",
`ordering` INT(11) NOT NULL DEFAULT 0,
`params` TEXT NOT NULL,
`language` CHAR(7) NOT NULL DEFAULT "*" COMMENT "The language code.",
`created_time` DATETIME NOT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`modified_time` DATETIME NOT NULL,
`modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`metadesc` TEXT NOT NULL,
`metakey` TEXT NOT NULL,
`robots` VARCHAR(255) NOT NULL DEFAULT "0",
PRIMARY KEY (`id`),
KEY `idx_access` (`access`),
KEY `idx_checkout` (`checked_out`),
KEY `idx_createdby` (`created_by`),
KEY `idx_state` (`published`),
KEY `idx_catid` (`catid`),
KEY `idx_language` (`language`),
KEY `idx_alias` (`alias`(191)),
INDEX `idx_list_images` (`catid`, `published`, `access`, `approved`, `hidden`, `language`, `ordering`),
FULLTEXT INDEX `idx_text_language` (`title`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_categories`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_categories` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`asset_id_image` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`parent_id` INT(11) NOT NULL DEFAULT 0,
`lft` INT(11) NOT NULL DEFAULT 0,
`rgt` INT(11) NOT NULL DEFAULT 0,
`level` INT(1) UNSIGNED NOT NULL DEFAULT 0,
`path` VARCHAR(2048) NOT NULL DEFAULT "",
`title` VARCHAR(255) NOT NULL DEFAULT "",
`alias` VARCHAR(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT "",
`description` TEXT NOT NULL,
`access` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`published` TINYINT(1) NOT NULL DEFAULT 0,
`hidden` TINYINT(1) NOT NULL DEFAULT 0,
`in_hidden` TINYINT(1) NOT NULL DEFAULT 0,
`password` VARCHAR(100) NOT NULL DEFAULT "",
`exclude_toplist` INT(1) UNSIGNED NOT NULL DEFAULT 0,
`exclude_search` INT(1) UNSIGNED NOT NULL DEFAULT 0,
`thumbnail` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`static_path` VARCHAR(2048) NOT NULL DEFAULT "",
`params` TEXT NOT NULL,
`language` CHAR(7) NOT NULL DEFAULT "*" COMMENT "The language code.",
`created_time` DATETIME NOT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`modified_time` DATETIME NOT NULL,
`modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`metadesc` TEXT NOT NULL,
`metakey` TEXT NOT NULL,
`robots` INT(11) UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `idx_left_right` (`lft`,`rgt`),
KEY `idx_parent_id` (`parent_id`),
KEY `idx_language` (`language`),
KEY `idx_access` (`access`),
KEY `idx_checkout` (`checked_out`),
KEY `idx_created_by` (`created_by`),
KEY `idx_alias` (`alias`(191)),
KEY `idx_title` (`title`(191)),
KEY `idx_path` (`path`(191)),
KEY `idx_list_subcategories` (`published`, `access`, `hidden`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_configs`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_configs` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`title` VARCHAR(255) NOT NULL DEFAULT "",
`note` TEXT,
`group_id` INT(11) UNSIGNED NOT NULL DEFAULT 1,
`published` TINYINT(1) NOT NULL DEFAULT 0,
`ordering` INT(11) NOT NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`jg_filesystem` VARCHAR(100) NOT NULL DEFAULT "",
`jg_imagetypes` VARCHAR(100) NOT NULL DEFAULT "jpg,jpeg,png,gif,webp",
`jg_pathftpupload` VARCHAR(100) NOT NULL DEFAULT "administrator/components/com_joomgallery/temp/ftp_upload/",
`jg_wmfile` VARCHAR(100) NOT NULL DEFAULT "media/joomgallery/images/watermark.png",
`jg_use_real_paths` TINYINT(1) NOT NULL DEFAULT 0,
`jg_router` VARCHAR(100) NOT NULL DEFAULT "DefaultRouter",
`jg_router_ids` TINYINT(1) NOT NULL DEFAULT 0,
`jg_router_imgids` TINYINT(1) NOT NULL DEFAULT 0,
`jg_compatibility_mode` TINYINT(1) NOT NULL DEFAULT 0,
`jg_replaceinfo` TEXT NOT NULL,
`jg_replaceshowwarning` TINYINT(1) NOT NULL DEFAULT 0,
`jg_useorigfilename` TINYINT(1) NOT NULL DEFAULT 0,
`jg_uploadorder` TINYINT(1) NOT NULL DEFAULT 2,
`jg_filenamenumber` TINYINT(1) NOT NULL DEFAULT 1,
`jg_parallelprocesses` TINYINT(1) NOT NULL DEFAULT 1,
`jg_imgprocessor` VARCHAR(5) NOT NULL DEFAULT "gd",
`jg_fastgd2creation` TINYINT(1) NOT NULL DEFAULT 1,
`jg_impath` VARCHAR(100) NOT NULL DEFAULT "",
`jg_metaprocessor` VARCHAR(15) NOT NULL DEFAULT "php",
`jg_staticprocessing` TEXT NOT NULL,
`jg_dynamicprocessing` TEXT NOT NULL,
`jg_dynamic_watermark` TINYINT(1) NOT NULL DEFAULT 1,
`jg_record_hits` TINYINT(1) NOT NULL DEFAULT 1,
`jg_record_hits_select` VARCHAR(25) NOT NULL DEFAULT "detail",
`jg_lightbox_image` VARCHAR(25) NOT NULL DEFAULT "detail",
`jg_lightbox_thumbnails` TINYINT(1) NOT NULL DEFAULT 0,
`jg_lightbox_zoom` TINYINT(1) NOT NULL DEFAULT 0,
`jg_gallery_view_browse_categories_link` TINYINT(1) NOT NULL DEFAULT 1,
`jg_gallery_view_class` VARCHAR(25) NOT NULL DEFAULT "columns",
`jg_gallery_view_num_columns` TINYINT(1) NOT NULL DEFAULT 3,
`jg_gallery_view_image_class` TINYINT(1) NOT NULL DEFAULT 0,
`jg_gallery_view_justified_height` INT NOT NULL DEFAULT 200,
`jg_gallery_view_justified_gap` TINYINT(1) NOT NULL DEFAULT 5,
`jg_gallery_view_numb_images` INT NOT NULL DEFAULT 15,
`jg_gallery_view_ordering` VARCHAR(25) NOT NULL DEFAULT "a.hits DESC",
`jg_gallery_view_type_image` VARCHAR(25) NOT NULL DEFAULT "thumbnail",
`jg_gallery_view_image_link` VARCHAR(25) NOT NULL DEFAULT "defaultview",
`jg_category_view_browse_images_link` TINYINT(1) NOT NULL DEFAULT 1,
`jg_category_view_subcategory_class` VARCHAR(25) NOT NULL DEFAULT "columns",
`jg_category_view_subcategory_num_columns` TINYINT(1) NOT NULL DEFAULT 3,
`jg_category_view_subcategory_image_class` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_numb_subcategories` INT NOT NULL DEFAULT 12,
`jg_category_view_subcategory_type_images` VARCHAR(25) NOT NULL DEFAULT "thumbnail",
`jg_category_view_subcategories_pagination` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_subcategories_caption_align` VARCHAR(25) NOT NULL DEFAULT "left",
`jg_category_view_subcategories_category_description` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_subcategories_random_image` TINYINT(1) NOT NULL DEFAULT 1,
`jg_category_view_subcategories_random_subimages` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_subcategories_image_count` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_class` VARCHAR(25) NOT NULL DEFAULT "columns",
`jg_category_view_num_columns` TINYINT(1) NOT NULL DEFAULT 3,
`jg_category_view_image_class` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_justified_height` INT NOT NULL DEFAULT 200,
`jg_category_view_justified_gap` TINYINT(1) NOT NULL DEFAULT 5,
`jg_category_view_numb_images` INT NOT NULL DEFAULT 12,
`jg_category_view_ord_images` VARCHAR(50) NOT NULL DEFAULT "a.date ASC",
`jg_category_view_type_images` VARCHAR(25) NOT NULL DEFAULT "thumbnail",
`jg_category_view_pagination` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_number_of_reloaded_images` INT NOT NULL DEFAULT 3,
`jg_category_view_image_link` VARCHAR(25) NOT NULL DEFAULT "defaultview",
`jg_category_view_caption_align` VARCHAR(25) NOT NULL DEFAULT "center",
`jg_category_view_images_show_title` TINYINT(1) NOT NULL DEFAULT 1,
`jg_category_view_title_link` VARCHAR(25) NOT NULL DEFAULT "defaultview",
`jg_category_view_show_description` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_show_description_label` TINYINT(1) NOT NULL DEFAULT 1,
`jg_category_view_show_imgdate` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_show_imgauthor` TINYINT(1) NOT NULL DEFAULT 0,
`jg_category_view_show_tags` TINYINT(1) NOT NULL DEFAULT 0,
`jg_detail_view_type_image` VARCHAR(25) NOT NULL DEFAULT "detail",
`jg_detail_view_show_title` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_category` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_description` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_imgdate` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_imgauthor` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_created_by` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_votes` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_rating` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_hits` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_downloads` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_tags` TINYINT(1) NOT NULL DEFAULT 1,
`jg_detail_view_show_metadata` TINYINT(1) NOT NULL DEFAULT 1,
`jg_msg_upload_type` VARCHAR(10) NOT NULL DEFAULT "none",
`jg_msg_upload_recipients` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_download_type` VARCHAR(10) NOT NULL DEFAULT "none",
`jg_msg_download_recipients` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_zipdownload` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_comment_type` VARCHAR(10) NOT NULL DEFAULT "none",
`jg_msg_comment_recipients` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_comment_toowner` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_report_type` VARCHAR(10) NOT NULL DEFAULT "none",
`jg_msg_report_recipients` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_report_toowner` TINYINT(1) NOT NULL DEFAULT 0,
`jg_msg_rejectimg_type` VARCHAR(10) NOT NULL DEFAULT "none",
`jg_msg_global_from` TINYINT(1) NOT NULL DEFAULT 0,
`jg_userspace` TINYINT(1) NOT NULL DEFAULT 1,
`jg_approve` TINYINT(1) NOT NULL DEFAULT 0,
`jg_maxusercat` DOUBLE NOT NULL DEFAULT 10,
`jg_maxuserimage` DOUBLE NOT NULL DEFAULT 500,
`jg_maxuserimage_timespan` DOUBLE NOT NULL DEFAULT 0,
`jg_maxfilesize` DOUBLE NOT NULL DEFAULT 2,
`jg_userupload` TINYINT(1) NOT NULL DEFAULT 1,
`jg_newpiccopyright` TINYINT(1) NOT NULL DEFAULT 1,
`jg_uploaddefaultcat` TINYINT(1) NOT NULL DEFAULT 0,
`jg_useruploadsingle` TINYINT(1) NOT NULL DEFAULT 1,
`jg_maxuploadfields` DOUBLE NOT NULL DEFAULT 3,
`jg_useruploadajax` TINYINT(1) NOT NULL DEFAULT 1,
`jg_useruploadbatch` TINYINT(1) NOT NULL DEFAULT 1,
`jg_special_upload` TINYINT(1) NOT NULL DEFAULT 1,
`jg_newpicnote` TINYINT(1) NOT NULL DEFAULT 1,
`jg_redirect_after_upload` TINYINT(1) NOT NULL DEFAULT 1,
`jg_download` TINYINT(1) NOT NULL DEFAULT 1,
`jg_download_hint` TINYINT(1) NOT NULL DEFAULT 1,
`jg_downloadfile` TINYINT(1) NOT NULL DEFAULT 2,
`jg_downloadwithwatermark` TINYINT(1) NOT NULL DEFAULT 1,
`jg_showrating` TINYINT(1) NOT NULL DEFAULT 1,
`jg_maxvoting` DOUBLE NOT NULL DEFAULT 5,
`jg_ratingcalctype` TINYINT(1) NOT NULL DEFAULT 0,
`jg_votingonlyonce` TINYINT(1) NOT NULL DEFAULT 1,
`jg_report_images` TINYINT(1) NOT NULL DEFAULT 1,
`jg_report_hint` TINYINT(1) NOT NULL DEFAULT 1,
`jg_showcomments` TINYINT(1) NOT NULL DEFAULT 1,
PRIMARY KEY (`id`),
KEY `idx_checkout` (`checked_out`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_faulties`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_faulties` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`ref_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`ref_type` VARCHAR(50) NOT NULL DEFAULT "",
`type` VARCHAR(50) NOT NULL DEFAULT "",
`paths` TEXT NOT NULL,
`created_time` DATETIME NOT NULL,
PRIMARY KEY (`id`),
KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_img_types`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_img_types` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`typename` VARCHAR(50) NOT NULL DEFAULT "",
`type_alias` VARCHAR(25) NOT NULL DEFAULT "",
`path` VARCHAR(100) NOT NULL DEFAULT "",
`params` TEXT NOT NULL,
`ordering` INT(11) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `idx_typename` (`typename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_tags`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_tags` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`alias` VARCHAR(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT "",
`title` VARCHAR(255) NOT NULL DEFAULT "",
`description` TEXT NOT NULL,
`access` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`published` TINYINT(1) NOT NULL DEFAULT 1,
`ordering` INT(11) NOT NULL DEFAULT 0,
`language` CHAR(7) NOT NULL DEFAULT "*" COMMENT "The language code.",
`created_time` DATETIME NOT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`modified_time` DATETIME NOT NULL,
`modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_access` (`access`),
KEY `idx_checkout` (`checked_out`),
INDEX `idx_list_tags` (`published`, `access`, `language`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_tags_ref`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_tags_ref` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`imgid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`tagid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
INDEX `idx_imgid` (`imgid`),
INDEX `idx_tagid` (`tagid`),
INDEX `idx_tag_img` (`tagid`, `imgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_collections`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_collections` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`userid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`alias` VARCHAR(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT "",
`title` VARCHAR(255) NOT NULL DEFAULT "",
`description` TEXT NOT NULL,
`access` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`published` TINYINT(1) NOT NULL DEFAULT 1,
`hidden` TINYINT(1) NOT NULL DEFAULT 0,
`ordering` INT(11) NOT NULL DEFAULT 0,
`language` CHAR(7) NOT NULL DEFAULT "*" COMMENT "The language code.",
`created_time` DATETIME NOT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`modified_time` DATETIME NOT NULL,
`modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
`metadesc` TEXT NOT NULL,
`metakey` TEXT NOT NULL,
`robots` VARCHAR(255) NOT NULL DEFAULT "0",
PRIMARY KEY (`id`),
KEY `idx_access` (`access`),
KEY `idx_checkout` (`checked_out`),
KEY `idx_created_by` (`created_by`),
INDEX `idx_list_collections` (`userid`, `published`, `access`, `language`, `ordering`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_collections_ref`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_collections_ref` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`collectionid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`imgid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`approved` TINYINT(1) NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
INDEX `idx_imgid` (`imgid`),
INDEX `idx_collectionid` (`collectionid`),
INDEX `idx_col_img` (`collectionid`, `imgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_users`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_users` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`cmsuser` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__users table.",
`description` TEXT NOT NULL,
`avatar` VARCHAR(255) NULL DEFAULT "",
`image` VARCHAR(255) NULL DEFAULT "",
`files` TEXT NOT NULL,
`params` TEXT NOT NULL,
`created_time` DATETIME NOT NULL,
PRIMARY KEY (`id`),
KEY `idx_user` (`cmsuser`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_votes`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_votes` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`imgid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`score` INT(11) NOT NULL DEFAULT 0,
`identication` VARCHAR(255) NULL DEFAULT "",
`created_time` datetime NOT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `idx_createdby` (`created_by`),
KEY `idx_imgid` (`imgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_comments`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_comments` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT "FK to the #__assets table.",
`imgid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`title` VARCHAR(255) NOT NULL DEFAULT "",
`description` TEXT NOT NULL,
`published` TINYINT(1) NOT NULL DEFAULT 0,
`approved` TINYINT(1) NOT NULL DEFAULT 0,
`created_time` DATETIME NOT NULL,
`created_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`modified_time` DATETIME NOT NULL,
`modified_by` INT(11) UNSIGNED NOT NULL DEFAULT 0,
PRIMARY KEY (`id`),
KEY `idx_published` (`published`),
KEY `idx_imgid` (`imgid`),
KEY `idx_createdby` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_migration`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_migration` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`script` VARCHAR(50) NOT NULL DEFAULT "",
`type` VARCHAR(50) NOT NULL DEFAULT "",
`src_table` VARCHAR(255) NOT NULL DEFAULT "",
`src_pk` VARCHAR(25) NOT NULL DEFAULT "id",
`dst_table` VARCHAR(255) NOT NULL DEFAULT "",
`dst_pk` VARCHAR(25) NOT NULL DEFAULT "id",
`queue` LONGTEXT NOT NULL,
`successful` LONGTEXT NOT NULL,
`failed` LONGTEXT NOT NULL,
`counter` LONGTEXT NOT NULL,
`last` VARCHAR(25) NOT NULL DEFAULT "0",
`params` TEXT NOT NULL,
`created_time` DATETIME NOT NULL,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `#__joomgallery_tasks`
--

CREATE TABLE IF NOT EXISTS `#__joomgallery_tasks` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`title` VARCHAR(255) NOT NULL DEFAULT "",
`taskid` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`type` VARCHAR(128) NOT NULL DEFAULT "",
`queue` LONGTEXT NOT NULL,
`successful` LONGTEXT NOT NULL,
`failed` LONGTEXT NOT NULL,
`counter` LONGTEXT NOT NULL,
`last_id` VARCHAR(25) NOT NULL DEFAULT "0",
`completed` TINYINT(1) NOT NULL DEFAULT 0,
`last_execution` DATETIME DEFAULT NULL COMMENT 'timestamp of last run',
`times_executed` INT(11) UNSIGNED DEFAULT 0 COMMENT 'count of successful runs',
`params` TEXT NOT NULL,
`published` TINYINT(1) NOT NULL DEFAULT 1,
`ordering` INT(11) NOT NULL DEFAULT 0,
`note` TEXT NOT NULL,
`created_time` DATETIME NOT NULL,
`checked_out` INT(11) UNSIGNED NOT NULL DEFAULT 0,
`checked_out_time` DATETIME DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Dumping data for table `#__content_types`
--

INSERT INTO `#__content_types` (`type_title`, `type_alias`, `table`, `rules`, `field_mappings`, `content_history_options`)
SELECT * FROM ( SELECT 'Image','com_joomgallery.image','{"special":{"dbtable":"#__joomgallery","key":"id","type":"ImageTable","prefix":"Joomla\\\\Component\\\\Joomgallery\\\\Administrator\\\\Table\\\\"}}', CASE
                                    WHEN 'rules' is null THEN ''
                                    ELSE ''
                                    END as rules, CASE
                                    WHEN 'field_mappings' is null THEN ''
                                    ELSE ''
                                    END as field_mappings, '{"formFile":"administrator\/components\/com_joomgallery\/forms\/image.xml", "hideFields":["checked_out","checked_out_time","params","language" ,"imgmetadata"], "ignoreChanges":["modified_by", "modified", "checked_out", "checked_out_time"], "convertToInt":["publish_up", "publish_down"], "displayLookup":[{"sourceColumn":"catid","targetTable":"#__categories","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"group_id","targetTable":"#__usergroups","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"created_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"access","targetTable":"#__viewlevels","targetColumn":"id","displayColumn":"title"},{"sourceColumn":"modified_by","targetTable":"#__users","targetColumn":"id","displayColumn":"name"},{"sourceColumn":"catid","targetTable":"#__joomgallery_categories","targetColumn":"id","displayColumn":"title"}]}') AS tmp
WHERE NOT EXISTS (
	SELECT type_alias FROM `#__content_types` WHERE (`type_alias` = 'com_joomgallery.image')
) LIMIT 1;
