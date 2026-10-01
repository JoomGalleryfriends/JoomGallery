ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_host` VARCHAR(50) NOT NULL DEFAULT "" AFTER `jg_detail_view_show_metadata`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_key` VARCHAR(300) NOT NULL DEFAULT "" AFTER `jg_aiint_host`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_force_slash` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_aiint_key`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_provider_keys` TEXT NOT NULLAFTER `jg_aiint_force_slash`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_imagetype` VARCHAR(25) NOT NULL DEFAULT "detail" AFTER `jg_aiint_provider_keys`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_maxdim` INT NOT NULL DEFAULT 500 AFTER `jg_aiint_tags_imagetype`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_preload` INT NOT NULL DEFAULT 0 AFTER `jg_aiint_tags_maxdim`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_casesens` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_aiint_tags_preload`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_aiint_tags_caseupper` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_aiint_tags_casesens`;
