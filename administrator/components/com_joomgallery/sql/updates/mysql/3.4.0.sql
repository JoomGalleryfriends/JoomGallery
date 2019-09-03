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