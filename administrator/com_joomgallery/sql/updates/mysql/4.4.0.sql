ALTER TABLE `#__joomgallery_tasks`
DROP COLUMN `queue`,
DROP COLUMN `successful`,
DROP COLUMN `failed`,
DROP COLUMN `counter`,
DROP COLUMN `last_id`,
DROP COLUMN `completed`;

CREATE TABLE IF NOT EXISTS `#__joomgallery_task_items` (
`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
`task_id` INT(10) UNSIGNED NOT NULL,
`item_id` VARCHAR(255) NOT NULL,
`status` ENUM('pending','processing','success','failed') NOT NULL DEFAULT 'pending',
`error_message` TEXT DEFAULT NULL,
`created_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
`processed_time` DATETIME DEFAULT NULL,
PRIMARY KEY (`id`),
INDEX `idx_task_status` (`task_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE=utf8mb4_unicode_ci;