CREATE TABLE IF NOT EXISTS `booster_games` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booster_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`booster_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `games` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `booster_game_unique` (`booster_id`, `game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 