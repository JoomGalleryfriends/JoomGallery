ALTER TABLE `#__joomgallery_maintenance` ADD `alias` tinyint(1) NOT NULL default '0' AFTER `type`;
ALTER TABLE `#__joomgallery_maintenance` ADD `catpath` tinyint(1) NOT NULL default '0' AFTER `alias`;
