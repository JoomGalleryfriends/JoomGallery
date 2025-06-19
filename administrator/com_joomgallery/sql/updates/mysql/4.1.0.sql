ALTER TABLE `#__joomgallery_configs` CHANGE `jg_category_view_lightbox_image` `jg_lightbox_image` VARCHAR(25) NOT NULL DEFAULT "detail";
ALTER TABLE `#__joomgallery_configs` CHANGE `jg_category_view_lightbox_thumbnails` `jg_lightbox_thumbnails` TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_subcategories_caption_align` VARCHAR(25) NOT NULL DEFAULT "left" AFTER `jg_category_view_subcategories_pagination`;
<<<<<<< project-metadata
ALTER TABLE `#__joomgallery_configs` ADD `jg_metaprocessor` VARCHAR(15) NOT NULL DEFAULT "php" AFTER `jg_impath`;
=======
ALTER TABLE `#__joomgallery_configs` ADD `jg_lightbox_zoom` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_lightbox_thumbnails`;
>>>>>>> v4.1.0
ALTER TABLE `#__joomgallery_categories` ADD INDEX `idx_parent_id` (`parent_id`);
ALTER TABLE `#__joomgallery_categories` ADD INDEX `idx_created_by` (`created_by`);
ALTER TABLE `#__joomgallery_categories` ADD INDEX `idx_title` (`title`(191));
ALTER TABLE `#__joomgallery_categories` DROP INDEX `idx_alias`, ADD INDEX `idx_alias` (`alias`(191));
ALTER TABLE `#__joomgallery_categories` DROP INDEX `idx_path`, ADD INDEX `idx_path` (`path`(191));
