CREATE TABLE IF NOT EXISTS `#__joomgallery` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`ordering` INT(11)  NULL  DEFAULT 0,
`checked_out` INT(11)  UNSIGNED,
`hits` INT(11)  NULL  DEFAULT 0,
`downloads` INT(11)  NULL  DEFAULT 0,
`imgvotes` INT(11)  NULL  DEFAULT 0,
`imgvotesum` INT(11)  NULL  DEFAULT 0,
`approved` TINYINT(1)  NULL  DEFAULT 0,
`useruploaded` TINYINT(1)  NULL  DEFAULT 0,
`imgtitle` VARCHAR(255)  NOT NULL ,
`alias` VARCHAR(255) COLLATE utf8_bin NULL ,
`catid` INT(10)  NOT NULL  DEFAULT 0,
`published` TINYINT(1)  NULL  DEFAULT 0,
`imgauthor` VARCHAR(50)  NULL  DEFAULT "",
`language` VARCHAR(5)  NULL  DEFAULT "",
`imgtext` TEXT NULL ,
`access` INT(11)  NULL  DEFAULT 0,
`hidden` TINYINT(1)  NULL  DEFAULT 0,
`featured` TINYINT(1)  NULL  DEFAULT 0,
`created_time` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_time` DATETIME NULL  DEFAULT NULL ,
`modified_by` INT(11)  NULL  DEFAULT 0,
`metadesc` TEXT NULL ,
`metakey` TEXT NULL ,
`robots` VARCHAR(255)  NULL  DEFAULT "0",
`filename` VARCHAR(255)  NOT NULL ,
`imgdate` DATETIME NULL  DEFAULT NULL ,
`imgmetadata` TEXT NULL ,
`params` TEXT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_categories` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`checked_out` INT(11)  UNSIGNED,
`lft` INT(11)  NULL  DEFAULT 0,
`rgt` INT(11)  NULL  DEFAULT 0,
`level` INT(10)  NULL  DEFAULT 0,
`path` VARCHAR(2048)  NULL  DEFAULT "",
`in_hidden` TINYINT(1)  NULL  DEFAULT 0,
`title` VARCHAR(255)  NOT NULL ,
`alias` VARCHAR(255) COLLATE utf8_bin NULL ,
`parent_id` INT(11)  NULL  DEFAULT 0,
`published` TINYINT(1)  NULL  DEFAULT 0,
`access` INT(11)  NULL  DEFAULT 0,
`password` VARCHAR(255)  NULL  DEFAULT "",
`language` VARCHAR(5)  NULL  DEFAULT "",
`description` TEXT NULL ,
`hidden` VARCHAR(255)  NULL  DEFAULT "0",
`exclude_toplist` VARCHAR(255)  NULL  DEFAULT "0",
`exclude_search` VARCHAR(255)  NULL  DEFAULT "0",
`thumbnail` VARCHAR(255)  NULL  DEFAULT "",
`created_time` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_by` INT(11)  NULL  DEFAULT 0,
`modified_time` DATETIME NULL  DEFAULT NULL ,
`metadesc` TEXT NULL ,
`metakey` TEXT NULL ,
`robots` VARCHAR(255)  NULL  DEFAULT "0",
`params` TEXT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_configs` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`asset_id` INT(10) UNSIGNED NOT NULL DEFAULT '0',

`published` TINYINT(1)  NULL  DEFAULT 1,
`ordering` INT(11)  NULL  DEFAULT 0,
`checked_out` INT(11)  UNSIGNED,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_by` INT(11)  NULL  DEFAULT 0,
`jg_pathftpupload` VARCHAR(100)  NULL  DEFAULT "administrator/components/com_joomgallery/temp/ftp_upload/",
`jg_pathtemp` VARCHAR(100)  NULL  DEFAULT "administrator/components/com_joomgallery/temp/",
`jg_wmfile` VARCHAR(255)  NULL  DEFAULT "",
`jg_use_real_paths` VARCHAR(255)  NULL  DEFAULT "0",
`jg_checkupdate` VARCHAR(255)  NULL  DEFAULT "1",
`jg_listbox_max_items` DOUBLE NULL DEFAULT 25,
`title` VARCHAR(255)  NOT NULL ,
`jg_filenamewithjs` VARCHAR(255)  NULL  DEFAULT "1",
`jg_filenamereplace` TEXT NULL ,
`jg_replaceinfo` TEXT NULL ,
`jg_replaceshowwarning` VARCHAR(255)  NULL  DEFAULT "0",
`jg_useorigfilename` VARCHAR(255)  NULL  DEFAULT "0",
`jg_uploadorder` VARCHAR(255)  NULL  DEFAULT "2",
`jg_filenamenumber` VARCHAR(255)  NULL  DEFAULT "1",
`jg_delete_original` VARCHAR(255)  NULL  DEFAULT "0",
`jg_imgprocessor` VARCHAR(255)  NULL  DEFAULT "1",
`jg_fastgd2creation` VARCHAR(255)  NULL  DEFAULT "1",
`jg_impath` VARCHAR(100)  NULL  DEFAULT " ",
`jg_staticprocessing` TEXT NULL ,
`jg_dynamicprocessing` TEXT NULL ,
`jg_msg_upload_type` VARCHAR(255)  NULL  DEFAULT "2",
`jg_msg_upload_recipients` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_download_type` VARCHAR(255)  NULL  DEFAULT "2",
`jg_msg_download_recipients` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_zipdownload` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_comment_type` VARCHAR(255)  NULL  DEFAULT "2",
`jg_msg_comment_recipients` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_comment_toowner` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_report_type` VARCHAR(255)  NULL  DEFAULT "2",
`jg_msg_report_recipients` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_report_toowner` VARCHAR(255)  NULL  DEFAULT "0",
`jg_msg_rejectimg_type` VARCHAR(255)  NULL  DEFAULT "1",
`jg_msg_global_from` VARCHAR(255)  NULL  DEFAULT "0",
`group_id` TEXT NOT NULL ,
`jg_userspace` VARCHAR(255)  NULL  DEFAULT "1",
`jg_approve` VARCHAR(255)  NULL  DEFAULT "0",
`jg_maxusercat` DOUBLE NULL DEFAULT 10,
`jg_maxuserimage` DOUBLE NULL DEFAULT 500,
`jg_maxuserimage_timespan` DOUBLE NULL DEFAULT 0,
`jg_maxfilesize` DOUBLE NULL DEFAULT 2000000,
`jg_newpiccopyright` VARCHAR(255)  NULL  DEFAULT "1",
`jg_uploaddefaultcat` VARCHAR(255)  NULL  DEFAULT "0",
`jg_useruploadsingle` VARCHAR(255)  NULL  DEFAULT "1",
`jg_maxuploadfields` DOUBLE NULL DEFAULT 3,
`jg_useruploadajax` VARCHAR(255)  NULL  DEFAULT "1",
`jg_useruploadbatch` VARCHAR(255)  NULL  DEFAULT "1",
`jg_special_upload` VARCHAR(255)  NULL  DEFAULT "1",
`jg_newpicnote` VARCHAR(255)  NULL  DEFAULT "1",
`jg_redirect_after_upload` VARCHAR(255)  NULL  DEFAULT "1",
`jg_download` VARCHAR(255)  NULL  DEFAULT "1",
`jg_download_hint` VARCHAR(255)  NULL  DEFAULT "1",
`jg_downloadfile` VARCHAR(255)  NULL  DEFAULT "2",
`jg_downloadwithwatermark` VARCHAR(255)  NULL  DEFAULT "1",
`jg_showrating` VARCHAR(255)  NULL  DEFAULT "1",
`jg_maxvoting` DOUBLE NULL DEFAULT 5,
`jg_ratingcalctype` VARCHAR(255)  NULL  DEFAULT "0",
`jg_votingonlyonce` VARCHAR(255)  NULL  DEFAULT "1",
`jg_report_images` VARCHAR(255)  NULL  DEFAULT "1",
`jg_report_hint` VARCHAR(255)  NULL  DEFAULT "1",
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_faulties` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`create_date` DATETIME NULL  DEFAULT NULL ,
`refid` INT(11)  NULL  DEFAULT 0,
`type` TEXT NULL ,
`paths` TEXT NULL ,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_fields` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`ordering` INT(11)  NULL  DEFAULT 0,
`created_time` DATETIME NULL  DEFAULT NULL ,
`type` VARCHAR(7)  NULL  DEFAULT "",
`key` VARCHAR(255)  NULL  DEFAULT "",
`value` TEXT NULL ,
`asset_id` INT(11)  NULL  DEFAULT 0,
`created_by` INT(11)  NULL  DEFAULT 0,
`language` VARCHAR(5)  NULL  DEFAULT "",
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_img_types` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`typename` VARCHAR(11)  NULL  DEFAULT "",
`path` VARCHAR(100)  NULL  DEFAULT "",
`ordering` INT(11)  NULL  DEFAULT 0,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_tags` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`ordering` INT(11)  NULL  DEFAULT 0,
`checked_out` INT(11)  UNSIGNED,
`asset_id` INT(11)  NULL  DEFAULT 0,
`title` VARCHAR(255)  NOT NULL ,
`published` TINYINT(1)  NULL  DEFAULT 1,
`access` INT(11)  NULL  DEFAULT 0,
`language` VARCHAR(5)  NULL  DEFAULT "",
`description` TEXT NULL ,
`created_time` DATETIME NULL  DEFAULT NULL ,
`created_by` INT(11)  NULL  DEFAULT 0,
`modified_time` DATETIME NULL  DEFAULT NULL ,
`modified_by` INT(11)  NULL  DEFAULT 0,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_tags_ref` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`imgid` INT(11)  NULL  DEFAULT 0,
`tagid` INT(11)  NULL  DEFAULT 0,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_users` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`cmsuser` INT(11)  NULL  DEFAULT 0,
`created_time` DATETIME NULL  DEFAULT NULL ,
`zipname` VARCHAR(70)  NULL  DEFAULT "",
`layout` INT(1)  NULL  DEFAULT 0,
`session_id` VARCHAR(200)  NULL  DEFAULT "",
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_users_ref` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`imgid` INT(11)  NULL  DEFAULT 0,
`userid` INT(11)  NULL  DEFAULT 0,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `#__joomgallery_votes` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`imgid` INT(11)  NULL  DEFAULT 0,
`userid` INT(11)  NULL  DEFAULT 0,
`created_time` DATETIME NULL  DEFAULT NULL ,
`score` INT(11)  NULL  DEFAULT 0,
PRIMARY KEY (`id`)
) DEFAULT COLLATE=utf8mb4_unicode_ci;


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
