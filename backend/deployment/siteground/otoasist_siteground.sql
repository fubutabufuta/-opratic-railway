-- OtoAsist SiteGround MySQL Import
-- Backend ile uyumlu güncel veritabanı yapısı
-- Tarih: 2025-06-25

SET FOREIGN_KEY_CHECKS = 0;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";

SET AUTOCOMMIT = 0;

START TRANSACTION;

SET time_zone = "+00:00";

-- Kullanıcılar tablosu
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `phone` varchar(20) NOT NULL,
    `email` varchar(255) DEFAULT NULL,
    `full_name` varchar(255) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `is_verified` tinyint(1) DEFAULT 0,
    `verification_code` varchar(6) DEFAULT NULL,
    `profile_image` varchar(500) DEFAULT NULL,
    `birth_date` date DEFAULT NULL,
    `gender` enum('male', 'female', 'other') DEFAULT NULL,
    `address` text DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `phone` (`phone`),
    UNIQUE KEY `email` (`email`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Doğrulama tablosu
CREATE TABLE `verification` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `phone` varchar(20) NOT NULL,
    `verification_code` varchar(6) NOT NULL,
    `is_verified` tinyint(1) DEFAULT 0,
    `expires_at` timestamp NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `phone` (`phone`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Araçlar tablosu (backend uyumlu)
CREATE TABLE `vehicles` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `plate` varchar(20) NOT NULL,
    `brand` varchar(100) NOT NULL,
    `model` varchar(100) NOT NULL,
    `year` int(4) NOT NULL,
    `color` varchar(50) DEFAULT NULL,
    `fuel_type` enum(
        'gasoline',
        'diesel',
        'lpg',
        'electric',
        'hybrid'
    ) DEFAULT 'gasoline',
    `transmission` enum('manual', 'automatic', 'cvt') DEFAULT 'manual',
    `engine_volume` decimal(3, 1) DEFAULT NULL,
    `mileage` int(11) DEFAULT 0,
    `vin_number` varchar(17) DEFAULT NULL,
    `image` varchar(500) DEFAULT NULL,
    `last_service_date` date DEFAULT NULL,
    `last_inspection_date` date DEFAULT NULL,
    `insurance_expiry_date` date DEFAULT NULL,
    `kasko_expiry_date` date DEFAULT NULL,
    `registration_expiry_date` date DEFAULT NULL,
    `oil_change_date` date DEFAULT NULL,
    `tire_change_date` date DEFAULT NULL,
    `insurance_company` varchar(255) DEFAULT NULL,
    `insurance_policy_number` varchar(100) DEFAULT NULL,
    `notes` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `plate` (`plate`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Hatırlatıcılar tablosu (güncel backend yapısı)
CREATE TABLE `reminders` (
    `id` varchar(50) NOT NULL,
    `vehicle_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `date` date NOT NULL,
    `reminder_time` varchar(8) DEFAULT '09:00:00',
    `type` varchar(50) DEFAULT 'general',
    `reminder_days` int(11) DEFAULT 1,
    `is_completed` tinyint(1) DEFAULT 0,
    `completed_at` timestamp NULL DEFAULT NULL,
    `last_notification_sent` timestamp NULL DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `vehicle_id` (`vehicle_id`),
    KEY `date` (`date`),
    KEY `is_completed` (`is_completed`),
    UNIQUE KEY `unique_vehicle_date_time` (
        `vehicle_id`,
        `date`,
        `reminder_time`
    ),
    CONSTRAINT `reminders_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Kampanyalar tablosu
CREATE TABLE `campaigns` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `campaign_type` enum(
        'service',
        'insurance',
        'parts',
        'fuel',
        'general'
    ) NOT NULL,
    `target_brands` json DEFAULT NULL,
    `discount_percentage` decimal(5, 2) DEFAULT NULL,
    `company_name` varchar(255) NOT NULL,
    `company_phone` varchar(20) DEFAULT NULL,
    `company_email` varchar(255) DEFAULT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `start_date` date NOT NULL,
    `end_date` date NOT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    KEY `is_active` (`is_active`)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Uygulama ayarları tablosu
CREATE TABLE `app_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `language` varchar(10) DEFAULT 'tr',
    `theme` enum('light', 'dark', 'system') DEFAULT 'system',
    `notifications_enabled` tinyint(1) DEFAULT 1,
    `reminder_notifications` tinyint(1) DEFAULT 1,
    `campaign_notifications` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `user_id` (`user_id`),
    CONSTRAINT `app_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Örnek veriler
INSERT INTO
    `users` (
        `id`,
        `phone`,
        `email`,
        `full_name`,
        `password_hash`,
        `is_verified`,
        `created_at`,
        `updated_at`
    )
VALUES (
        1,
        '+905551234567',
        'admin@otoasist.com',
        'OtoAsist Admin',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        1,
        '2024-01-15 10:30:00',
        '2024-01-15 10:30:00'
    ),
    (
        2,
        '+905321123456',
        'test@example.com',
        'Test Kullanıcısı',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        1,
        '2024-01-18 10:00:00',
        '2024-01-18 10:00:00'
    );

INSERT INTO
    `vehicles` (
        `id`,
        `user_id`,
        `plate`,
        `brand`,
        `model`,
        `year`,
        `color`,
        `fuel_type`,
        `transmission`,
        `last_service_date`,
        `insurance_expiry_date`,
        `created_at`,
        `updated_at`
    )
VALUES (
        1,
        1,
        '06ABC123',
        'Volkswagen',
        'Passat',
        2020,
        'Beyaz',
        'gasoline',
        'manual',
        '2024-08-05',
        '2025-06-15',
        '2024-01-15 13:00:00',
        '2024-06-24 15:50:15'
    ),
    (
        2,
        1,
        '34ABC123',
        'Toyota',
        'Corolla',
        2020,
        'Beyaz',
        'gasoline',
        'manual',
        '2025-06-19',
        NULL,
        '2025-06-24 07:46:47',
        '2025-06-24 15:36:11'
    );

INSERT INTO
    `reminders` (
        `id`,
        `vehicle_id`,
        `title`,
        `description`,
        `date`,
        `reminder_time`,
        `type`,
        `reminder_days`,
        `is_completed`,
        `created_at`,
        `updated_at`
    )
VALUES (
        'demo_reminder_1',
        1,
        'Servis Hatırlatıcısı',
        'Aracınızın servis zamanı yaklaşıyor',
        '2025-08-05',
        '09:00:00',
        'service',
        1,
        0,
        '2024-06-24 15:50:09',
        '2024-06-24 15:50:09'
    ),
    (
        'demo_reminder_2',
        2,
        'Muayene Hatırlatıcısı',
        'Araç muayenesi yapılacak',
        '2026-05-23',
        '10:00:00',
        'inspection',
        7,
        0,
        '2025-06-25 10:11:12',
        '2025-06-25 10:11:12'
    );

INSERT INTO
    `campaigns` (
        `id`,
        `title`,
        `description`,
        `campaign_type`,
        `target_brands`,
        `discount_percentage`,
        `company_name`,
        `start_date`,
        `end_date`,
        `is_active`,
        `created_at`,
        `updated_at`
    )
VALUES (
        1,
        'Yaz Bakım Kampanyası',
        'Tüm araçlar için özel bakım fiyatları',
        'service',
        '["Toyota", "Volkswagen"]',
        20.00,
        'OtoAsist Servis',
        '2024-06-01',
        '2024-12-31',
        1,
        '2024-01-15 10:00:00',
        '2024-01-15 10:00:00'
    );

-- Performans indexleri
CREATE INDEX idx_vehicles_user_active ON vehicles (user_id, is_active);

CREATE INDEX idx_reminders_date_completed ON reminders (date, is_completed);

CREATE INDEX idx_reminders_vehicle_date ON reminders (vehicle_id, date);

SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

-- Kullanım:
-- 1. SiteGround phpMyAdmin'e giriş yap
-- 2. Yeni veritabanı oluştur
-- 3. Bu dosyayı import et
-- 4. config/database.php'yi güncelle