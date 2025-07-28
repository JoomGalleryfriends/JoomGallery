ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_description_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_description`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_subcategories_category_description` TINYINT(1) NOT NULL DEFAULT 0, AFTER `jg_category_view_subcategories_caption_align`;
DROP TABLE IF EXISTS `#__joomgallery_fields`;
ALTER TABLE `#__joomgallery_tags_ref` ADD INDEX `idx_imgid` (`imgid`);
ALTER TABLE `#__joomgallery_tags_ref` ADD INDEX `idx_tagid` (`tagid`);
ALTER TABLE `#__joomgallery_tags_ref` ADD INDEX `idx_tag_img` (`tagid`, `imgid`);
ALTER TABLE `#__joomgallery_collections_ref` ADD INDEX `idx_imgid` (`imgid`);
ALTER TABLE `#__joomgallery_collections_ref` ADD INDEX `idx_collectionid` (`collectionid`);
ALTER TABLE `#__joomgallery_collections_ref` ADD INDEX `idx_col_img` (`collectionid`, `imgid`);
ALTER TABLE `#__joomgallery_configs` MODIFY COLUMN `jg_maxfilesize` DOUBLE NOT NULL DEFAULT 2;
UPDATE `#__joomgallery_configs` SET `jg_maxfilesize` = `jg_maxfilesize` / 1000000;
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
