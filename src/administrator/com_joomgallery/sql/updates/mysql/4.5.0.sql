ALTER TABLE `#__joomgallery_configs` ADD `jg_acl_cache_entries` INT(11) UNSIGNED NOT NULL DEFAULT 64 AFTER `jg_compatibility_mode`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_acl_cache_lifetime` INT(11) UNSIGNED NOT NULL DEFAULT 15 AFTER `jg_acl_cache_entries`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_config_cache_entries` INT(11) UNSIGNED NOT NULL DEFAULT 64 AFTER `jg_acl_cache_lifetime`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_config_cache_lifetime` INT(11) UNSIGNED NOT NULL DEFAULT 60 AFTER `jg_config_cache_entries`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_guest_cache_entries` INT(11) UNSIGNED NOT NULL DEFAULT 4096 AFTER `jg_config_cache_lifetime`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_guest_cache_lifetime` INT(11) UNSIGNED NOT NULL DEFAULT 60 AFTER `jg_guest_cache_entries`;
ALTER TABLE `#__joomgallery_configs` ADD UNIQUE INDEX `idx_unique_group` (`group_id`);

CREATE TABLE IF NOT EXISTS `#__joomgallery_cache_revisions` (
  `scope` VARCHAR(32) NOT NULL,
  `revision` BIGINT UNSIGNED NOT NULL DEFAULT 1,
  PRIMARY KEY (`scope`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `#__joomgallery_cache_revisions` (`scope`, `revision`)
VALUES ('config', 1), ('acl', 1), ('cleanup', 1);
