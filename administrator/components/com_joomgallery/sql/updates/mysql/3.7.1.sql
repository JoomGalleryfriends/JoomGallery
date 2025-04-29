ALTER TABLE `#__joomgallery_orphans` CHANGE `fullpath` `fullpath` varchar(500) NOT NULL;
ALTER TABLE `#__joomgallery_orphans` DROP INDEX `fullpath`, ADD INDEX `fullpath` (`fullpath`(500)) USING BTREE;