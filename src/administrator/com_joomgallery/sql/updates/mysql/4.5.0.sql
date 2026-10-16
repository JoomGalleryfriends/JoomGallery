ALTER TABLE `#__joomgallery`
ADD COLUMN `image_ref` INT(10) UNSIGNED DEFAULT NULL
AFTER `filename`;

CREATE TABLE IF NOT EXISTS `#__joomgallery_img_ref` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(2048) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `filesystem` VARCHAR(100) NOT NULL DEFAULT 'local-images',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;