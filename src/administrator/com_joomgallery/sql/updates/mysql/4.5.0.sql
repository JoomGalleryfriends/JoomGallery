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
ALTER TABLE `#__joomgallery_configs` ADD `jg_acl_cache_entries` INT(11) UNSIGNED NOT NULL DEFAULT 64 AFTER `jg_compatibility_mode`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_acl_cache_lifetime` INT(11) UNSIGNED NOT NULL DEFAULT 15 AFTER `jg_acl_cache_entries`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_config_cache_entries` INT(11) UNSIGNED NOT NULL DEFAULT 64 AFTER `jg_acl_cache_lifetime`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_config_cache_lifetime` INT(11) UNSIGNED NOT NULL DEFAULT 60 AFTER `jg_config_cache_entries`;
ALTER TABLE `#__joomgallery_configs` ADD UNIQUE INDEX `idx_unique_group` (`group_id`);

CREATE TABLE IF NOT EXISTS `#__joomgallery_cache_revisions` (
  `scope` VARCHAR(32) NOT NULL,
  `revision` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__joomgallery_cache_revisions` (`scope`, `revision`) VALUES ('config', 1), ('acl', 1), ('cleanup', 1);
