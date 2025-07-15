-- B2B Clients table
CREATE TABLE IF NOT EXISTS b2b_clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(255) NOT NULL,
    contact_person VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    api_key VARCHAR(100) UNIQUE NOT NULL,
    client_type ENUM(
        'gallery',
        'insurance',
        'service',
        'other'
    ) DEFAULT 'other',
    allowed_targets JSON, -- ["all", "city", "vehicle_brand"]
    monthly_quota INT DEFAULT 1000,
    total_notifications_sent INT DEFAULT 0,
    last_notification_at DATETIME,
    status ENUM(
        'active',
        'suspended',
        'inactive'
    ) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- B2B Notification Logs table
CREATE TABLE IF NOT EXISTS b2b_notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    notification_id INT NOT NULL,
    sent_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES b2b_clients (id) ON DELETE CASCADE,
    FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE,
    INDEX idx_client_date (client_id, created_at)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Add B2B related columns to notifications table
ALTER TABLE notifications
ADD COLUMN IF NOT EXISTS b2b_client_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS action_url VARCHAR(500) DEFAULT NULL,
ADD FOREIGN KEY (b2b_client_id) REFERENCES b2b_clients (id) ON DELETE SET NULL;

-- Add notification tracking columns to reminders table
ALTER TABLE reminders
ADD COLUMN IF NOT EXISTS notification_sent TINYINT(1) DEFAULT 0,
ADD COLUMN IF NOT EXISTS notification_sent_at DATETIME DEFAULT NULL;

-- Sample B2B clients for testing
INSERT INTO
    b2b_clients (
        company_name,
        contact_person,
        email,
        phone,
        api_key,
        client_type,
        allowed_targets,
        monthly_quota
    )
VALUES (
        'Örnek Galeri',
        'Ahmet Yılmaz',
        'galeri@example.com',
        '+905551234567',
        'b2b_galeri_test_key_123456',
        'gallery',
        '["all", "city"]',
        2000
    ),
    (
        'Güvenli Sigorta',
        'Ayşe Kaya',
        'sigorta@example.com',
        '+905559876543',
        'b2b_sigorta_test_key_789012',
        'insurance',
        '["all", "vehicle_brand"]',
        5000
    ),
    (
        'Hızlı Servis',
        'Mehmet Demir',
        'servis@example.com',
        '+905553334455',
        'b2b_servis_test_key_345678',
        'service',
        '["city"]',
        1000
    );