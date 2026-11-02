ALTER TABLE `#__joomgallery_configs` ADD `jg_lightbox_show_tags` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_lightbox_thumbnails`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_show_tags_label` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_show_tags`;
ALTER TABLE `#__joomgallery_configs` DROP COLUMN `jg_maxuploadfields`;
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
