ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_gap` VARCHAR(25) NOT NULL DEFAULT "small" AFTER `jg_gallery_view_ordering`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_masonry` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_uikit_gallery_gap`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_lightbox` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_uikit_gallery_masonry`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_overlay` VARCHAR(25) NOT NULL DEFAULT "default" AFTER `jg_uikit_gallery_lightbox`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_image_ratio` VARCHAR(25) NOT NULL DEFAULT "original" AFTER `jg_uikit_gallery_overlay`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_title_position` VARCHAR(25) NOT NULL DEFAULT "overlay" AFTER `jg_uikit_gallery_image_ratio`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_uikit_gallery_button_text` VARCHAR(100) NOT NULL DEFAULT "View" AFTER `jg_uikit_gallery_title_position`;
