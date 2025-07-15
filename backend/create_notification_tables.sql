-- Notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    target_type ENUM(
        'all',
        'city',
        'vehicle_brand',
        'user'
    ) DEFAULT 'all',
    target_value VARCHAR(255),
    campaign_id INT,
    scheduled_at DATETIME,
    notification_type ENUM(
        'general',
        'reminder',
        'campaign',
        'alert'
    ) DEFAULT 'general',
    status ENUM(
        'active',
        'sent',
        'scheduled',
        'cancelled',
        'deleted'
    ) DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE SET NULL,
    INDEX idx_target (target_type, target_value),
    INDEX idx_scheduled (scheduled_at),
    INDEX idx_status (status)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- User notifications table (for tracking read status)
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_notification (notification_id, user_id),
    INDEX idx_user_read (user_id, is_read)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Notification settings table
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    reminders_enabled TINYINT(1) DEFAULT 1,
    campaigns_enabled TINYINT(1) DEFAULT 1,
    service_alerts_enabled TINYINT(1) DEFAULT 1,
    personal_offers_enabled TINYINT(1) DEFAULT 1,
    push_notifications_enabled TINYINT(1) DEFAULT 1,
    email_notifications_enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

-- Add FCM token column to users table if not exists
ALTER TABLE users
ADD COLUMN IF NOT EXISTS fcm_token VARCHAR(500) DEFAULT NULL;

ALTER TABLE users ADD INDEX IF NOT EXISTS idx_fcm_token (fcm_token);

-- Sample data for testing
INSERT INTO
    notifications (
        title,
        message,
        target_type,
        notification_type
    )
VALUES (
        'Hoş Geldiniz!',
        'Oto Asist uygulamasına hoş geldiniz. Araç bakımlarınızı kolayca takip edebilirsiniz.',
        'all',
        'general'
    ),
    (
        'Muayene Hatırlatması',
        'Aracınızın muayene tarihi yaklaşıyor. Randevu almayı unutmayın!',
        'all',
        'reminder'
    ),
    (
        'Özel Kampanya',
        'Bugüne özel tüm servis hizmetlerinde %20 indirim!',
        'all',
        'campaign'
    );