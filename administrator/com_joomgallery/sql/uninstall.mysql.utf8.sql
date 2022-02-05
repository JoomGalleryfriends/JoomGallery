DROP TABLE IF EXISTS `#__joomgallery`;
DROP TABLE IF EXISTS `#__joomgallery_categories`;
DROP TABLE IF EXISTS `#__joomgallery_configs`;
DROP TABLE IF EXISTS `#__joomgallery_faulties`;
DROP TABLE IF EXISTS `#__joomgallery_fields`;
DROP TABLE IF EXISTS `#__joomgallery_img_types`;
DROP TABLE IF EXISTS `#__joomgallery_tags`;
DROP TABLE IF EXISTS `#__joomgallery_tags_ref`;
DROP TABLE IF EXISTS `#__joomgallery_users`;
DROP TABLE IF EXISTS `#__joomgallery_users_ref`;
DROP TABLE IF EXISTS `#__joomgallery_votes`;

DELETE FROM `#__assets` WHERE (name LIKE 'com_joomgallery%');
DELETE FROM `#__content_types` WHERE (type_alias LIKE 'com_joomgallery%');
