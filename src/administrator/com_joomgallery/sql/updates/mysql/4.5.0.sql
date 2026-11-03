ALTER TABLE `#__joomgallery_configs` ADD `jg_lightbox_show_tags` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_lightbox_thumbnails`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_tags_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_tags`;
ALTER TABLE `#__joomgallery_configs` DROP COLUMN `jg_maxuploadfields`;
UPDATE `#__joomgallery_configs` SET `jg_pathftpupload` = "images/joomgallery/FTP/" WHERE `jg_pathftpupload` = "administrator/components/com_joomgallery/temp/ftp_upload/";
