ALTER TABLE `#__joomgallery_countstop` MODIFY `csip` VARCHAR(45) NOT NULL DEFAULT '';

ALTER TABLE `#__joomgallery_config` ADD `jg_storecommentip` INT(1) NOT NULL AFTER `jg_approvecom`;
UPDATE `#__joomgallery_config` SET `jg_storecommentip` = 1;

ALTER TABLE `#__joomgallery_comments` MODIFY `cmtip` VARCHAR(45) NOT NULL DEFAULT '';

ALTER TABLE `#__joomgallery_config` ADD `jg_storenametagip` INT(1) NOT NULL AFTER `jg_show_nameshields_unreg`;
UPDATE `#__joomgallery_config` SET `jg_storenametagip` = 1;

ALTER TABLE `#__joomgallery_nameshields` MODIFY `nuserip` VARCHAR(45) NOT NULL DEFAULT '';

ALTER TABLE `#__joomgallery_votes` MODIFY `userip` VARCHAR(45) NOT NULL DEFAULT '';

ALTER TABLE `#__joomgallery_catg` ADD `allow_download` int(1) NOT NULL default -1 AFTER `exclude_search`;
ALTER TABLE `#__joomgallery_catg` ADD `allow_comment` int(1) NOT NULL default -1 AFTER `allow_download`;
ALTER TABLE `#__joomgallery_catg` ADD `allow_rating` int(1) NOT NULL default -1 AFTER `allow_comment`;
ALTER TABLE `#__joomgallery_catg` ADD `allow_watermark` int(1) NOT NULL default -1 AFTER `allow_rating`;
ALTER TABLE `#__joomgallery_catg` ADD `allow_watermark_download` int(1) NOT NULL default -1 AFTER `allow_watermark`;

ALTER TABLE `#__joomgallery_config` ADD `jg_upload_exif_rotation` int(1) NOT NULL AFTER `jg_thumbquality`;
UPDATE `#__joomgallery_config` SET `jg_upload_exif_rotation` = 0;
ALTER TABLE `#__joomgallery_config` ADD `jg_originalquality` int(3) NOT NULL AFTER `jg_upload_exif_rotation`;
UPDATE `#__joomgallery_config` SET `jg_originalquality` = 100;

ALTER TABLE `#__joomgallery_config` ADD `jg_replaceimgtitle` int(5) NOT NULL DEFAULT 0 AFTER `jg_filenamereplace`;
ALTER TABLE `#__joomgallery_config` ADD `jg_replaceimgtext` int(5) NOT NULL DEFAULT 0 AFTER `jg_replaceimgtitle`;
ALTER TABLE `#__joomgallery_config` ADD `jg_replaceimgauthor` int(5) NOT NULL DEFAULT 0 AFTER `jg_replaceimgtext`;
ALTER TABLE `#__joomgallery_config` ADD `jg_replaceimgdate` int(5) NOT NULL DEFAULT 0 AFTER `jg_replaceimgauthor`;
ALTER TABLE `#__joomgallery_config` ADD `jg_replacemetakey` int(5) NOT NULL DEFAULT 0 AFTER `jg_replaceimgdate`;
ALTER TABLE `#__joomgallery_config` ADD `jg_replacemetadesc` int(5) NOT NULL DEFAULT 0 AFTER `jg_replacemetakey`;
ALTER TABLE `#__joomgallery_config` ADD `jg_replaceshowwarning` int(1) NOT NULL DEFAULT 0 AFTER `jg_replacemetadesc`;