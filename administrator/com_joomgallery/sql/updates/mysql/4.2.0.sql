ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_description_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_description`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_subcategories_category_description` TINYINT(1) NOT NULL DEFAULT 0, AFTER `jg_category_view_subcategories_caption_align`;
ALTER TABLE `#__joomgallery_configs` MODIFY COLUMN `jg_maxfilesize` DOUBLE NOT NULL DEFAULT 2;
UPDATE `#__joomgallery_configs` SET `jg_maxfilesize` = `jg_maxfilesize` / 1000000;
