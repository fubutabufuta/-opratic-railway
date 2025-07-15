-- Verification codes tablosu
CREATE TABLE IF NOT EXISTS `verification_codes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `code` varchar(6) NOT NULL,
    `type` enum(
        'register',
        'login',
        'password_reset'
    ) NOT NULL,
    `expires_at` timestamp NOT NULL,
    `used_at` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `code_type` (`code`, `type`),
    KEY `expires_at` (`expires_at`),
    CONSTRAINT `verification_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Users tablosunu güncelle (password_hash alanı için)
ALTER TABLE `users`
CHANGE `password_hash` `password` varchar(255) NOT NULL;

-- Last login alanı ekle
ALTER TABLE `users`
ADD COLUMN `last_login` timestamp NULL DEFAULT NULL AFTER `is_verified`;