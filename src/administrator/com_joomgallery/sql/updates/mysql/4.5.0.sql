CREATE TABLE IF NOT EXISTS `#__joomgallery_image_translations` (
`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
`image_id` INT(11) UNSIGNED NOT NULL,
`language` CHAR(7) NOT NULL,
`title` VARCHAR(255) NOT NULL DEFAULT "",
`alias` VARCHAR(400) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT "",
`description` TEXT NOT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `idx_image_language` (`image_id`, `language`),
KEY `idx_image_id` (`image_id`),
KEY `idx_language` (`language`),
KEY `idx_alias` (`alias`(191))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;
