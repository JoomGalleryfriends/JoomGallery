ALTER TABLE `#__joomgallery_tags_ref` ADD INDEX `idx_imgid` (`imgid`);
ALTER TABLE `#__joomgallery_tags_ref` ADD INDEX `idx_tagid` (`tagid`);
ALTER TABLE `#__joomgallery_tags_ref` ADD INDEX `idx_tag_img` (`tagid`, `imgid`);
ALTER TABLE `#__joomgallery_collections_ref` ADD INDEX `idx_imgid` (`imgid`);
ALTER TABLE `#__joomgallery_collections_ref` ADD INDEX `idx_collectionid` (`collectionid`);
ALTER TABLE `#__joomgallery_collections_ref` ADD INDEX `idx_col_img` (`collectionid`, `imgid`);
