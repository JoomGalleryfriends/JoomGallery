ALTER TABLE `#__joomgallery_configs` ADD `jg_lightbox_show_tags` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_lightbox_thumbnails`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_tags_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_tags`;
ALTER TABLE `#__joomgallery_configs` DROP COLUMN `jg_maxuploadfields`;
ALTER TABLE `#__joomgallery_configs`
ADD `jg_link_associated_images` TINYINT(1) NOT NULL DEFAULT 1;
ALTER TABLE `#__joomgallery_configs` ADD `jg_link_associated_categories` TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE `#__joomgallery`
ADD COLUMN `image_ref` INT(10) UNSIGNED DEFAULT NULL
AFTER `filename`;

CREATE TABLE IF NOT EXISTS `#__joomgallery_img_ref` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(2048) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `filesystem` VARCHAR(100) NOT NULL DEFAULT 'local-images',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
