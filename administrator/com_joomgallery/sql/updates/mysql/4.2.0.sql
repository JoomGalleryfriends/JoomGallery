ALTER TABLE `#__joomgallery_configs` MODIFY COLUMN `jg_maxfilesize` DOUBLE NOT NULL DEFAULT 2;
UPDATE `#__joomgallery_configs` SET `jg_maxfilesize` = `jg_maxfilesize` / 1000000;
