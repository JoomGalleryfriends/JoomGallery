ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_subcategories_image_count` TINYINT(1) NOT NULL DEFAULT 0 AFTER `jg_category_view_subcategories_random_subimages`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_backend_searchprovider` VARCHAR(15) NOT NULL DEFAULT "sql" AFTER `jg_dynamic_watermark`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_gallery_view_limit_images` INT NOT NULL DEFAULT 100 AFTER `jg_gallery_view_numb_images`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_gallery_view_searchprovider` VARCHAR(15) NOT NULL DEFAULT "finder" AFTER `jg_gallery_view_limit_images`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_gallery_view_autosuggest` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_gallery_view_searchprovider`;
ALTER TABLE `#__joomgallery_configs` ADD `jg_category_view_browse_categories_link` TINYINT(1) NOT NULL DEFAULT 1 AFTER `jg_category_view_browse_images_link`;
