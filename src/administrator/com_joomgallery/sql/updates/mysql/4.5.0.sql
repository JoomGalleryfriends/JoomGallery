ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_host` VARCHAR(50) NOT NULL DEFAULT "" AFTER `jg_detail_view_show_metadata`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_keys_storage` VARCHAR(20) NOT NULL DEFAULT "configuration" AFTER `jg_aiint_host`;
UPDATE `#__joomgallery_configs` SET `jg_aiint_keys_storage` = "-1" WHERE `id` <> 1;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_key` VARCHAR(300) NOT NULL DEFAULT "" AFTER `jg_aiint_keys_storage`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_force_slash` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_aiint_key`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_provider_keys` TEXT NOT NULL AFTER `jg_aiint_force_slash`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_imagetype` VARCHAR(25) NOT NULL DEFAULT "detail" AFTER `jg_aiint_provider_keys`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_maxdim` INT NOT NULL DEFAULT 500 AFTER `jg_aiint_tags_imagetype`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_preload` INT NOT NULL DEFAULT 0 AFTER `jg_aiint_tags_maxdim`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_privacy_checked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_aiint_tags_preload`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_casesens` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_aiint_tags_privacy_checked`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_caseupper` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_aiint_tags_casesens`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_lightbox_show_tags` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_lightbox_thumbnails`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_tags_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_tags`;
ALTER TABLE `#__joomgallery_configs` DROP COLUMN `jg_maxuploadfields`;
UPDATE `#__joomgallery_configs` SET `jg_pathftpupload` = "images/joomgallery/FTP/" WHERE `jg_pathftpupload` = "administrator/components/com_joomgallery/temp/ftp_upload/";
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_social_image_type` VARCHAR(25) NOT NULL DEFAULT 'detail' AFTER `jg_detail_view_type_image`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_metadata_keys` VARCHAR(1024) NOT NULL DEFAULT 'Model,Make,DateTimeOriginal,DateTime' AFTER `jg_detail_view_show_metadata`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_image_size` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_metadata_keys`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_comments` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_image_size`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_copyright` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_comments`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_voting_btn` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_copyright`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_download_btn` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_voting_btn`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_comment_btn` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_download_btn`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_favorite_btn` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_comment_btn`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_show_share_btn` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_detail_view_show_favorite_btn`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_lightbox_show_tags` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_lightbox_thumbnails`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_tags_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_tags`;
ALTER TABLE `#__joomgallery_configs` DROP COLUMN `jg_maxuploadfields`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_detail_view_title_position` VARCHAR(5) NOT NULL DEFAULT 'below' AFTER `jg_detail_view_show_title`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_create_user` VARCHAR(10) NOT NULL DEFAULT 'all';
ALTER TABLE `#__joomgallery_configs` ADD `jg_create_user_groups` VARCHAR(2048) NOT NULL DEFAULT '';
ALTER TABLE `#__joomgallery_configs` ADD `jg_create_user_ids` VARCHAR(2048) NOT NULL DEFAULT '';

-- Do not silently merge existing profiles: duplicate cmsuser values must be resolved before upgrading.
ALTER TABLE `#__joomgallery_users` DROP INDEX `idx_user`, ADD UNIQUE KEY `idx_user` (`cmsuser`);

ALTER TABLE `#__joomgallery_collections`
  ADD `is_default` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 AFTER `description`,
  DROP INDEX `idx_list_collections`,
  DROP COLUMN `language`,
  ADD INDEX `idx_list_collections` (`userid`, `is_default`, `published`, `access`, `ordering`);

-- Keep the oldest default when repairing data created before uniqueness was enforced.
UPDATE `#__joomgallery_collections` AS `duplicate`
INNER JOIN `#__joomgallery_collections` AS `keeper`
  ON `keeper`.`userid` = `duplicate`.`userid`
  AND `keeper`.`is_default` = 1
  AND `keeper`.`id` < `duplicate`.`id`
SET `duplicate`.`is_default` = 0
WHERE `duplicate`.`is_default` = 1;

-- NULL values are not considered equal by a unique index, so normal collections
-- remain unlimited while each userid can occur only once for a default collection.
ALTER TABLE `#__joomgallery_collections`
  ADD `default_userid` INT(11) UNSIGNED
    GENERATED ALWAYS AS (CASE WHEN `is_default` = 1 THEN `userid` ELSE NULL END) STORED
    AFTER `is_default`,
  ADD UNIQUE KEY `idx_default_collection` (`default_userid`);

-- Preserve approval when consolidating duplicate image/collection mappings.
UPDATE `#__joomgallery_collections_ref` AS `mapping`
INNER JOIN `#__joomgallery_collections_ref` AS `approved_mapping`
  ON `approved_mapping`.`collectionid` = `mapping`.`collectionid`
  AND `approved_mapping`.`imgid` = `mapping`.`imgid`
  AND `approved_mapping`.`approved` = 1
SET `mapping`.`approved` = 1;

-- Keep the oldest row for every image/collection pair.
DELETE `duplicate`
FROM `#__joomgallery_collections_ref` AS `duplicate`
INNER JOIN `#__joomgallery_collections_ref` AS `keeper`
  ON `keeper`.`collectionid` = `duplicate`.`collectionid`
  AND `keeper`.`imgid` = `duplicate`.`imgid`
  AND `keeper`.`id` < `duplicate`.`id`;

ALTER TABLE `#__joomgallery_collections_ref`
  DROP INDEX `idx_col_img`,
  ADD UNIQUE KEY `idx_col_img` (`collectionid`, `imgid`);
