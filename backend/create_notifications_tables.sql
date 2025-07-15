-- Bildirim Sistemi Tabloları

-- 1. Notifications tablosu (Ana bildirimler)
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
    target_value VARCHAR(255) NULL,
    campaign_id INT NULL,
    scheduled_at DATETIME NULL,
    notification_type ENUM(
        'general',
        'reminder',
        'campaign',
        'alert'
    ) DEFAULT 'general',
    status ENUM(
        'active',
        'inactive',
        'deleted'
    ) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_target_type (target_type),
    INDEX idx_status (status),
    INDEX idx_scheduled_at (scheduled_at),
    FOREIGN KEY (campaign_id) REFERENCES campaigns (id) ON DELETE SET NULL
);

-- 2. User Notifications tablosu (Kullanıcıya özel bildirim durumu)
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_notification (notification_id, user_id),
    INDEX idx_user_read (user_id, is_read),
    FOREIGN KEY (notification_id) REFERENCES notifications (id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 3. Notification Settings tablosu (Kullanıcı bildirim ayarları)
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    reminders_enabled BOOLEAN DEFAULT TRUE,
    campaigns_enabled BOOLEAN DEFAULT TRUE,
    service_alerts_enabled BOOLEAN DEFAULT TRUE,
    personal_offers_enabled BOOLEAN DEFAULT TRUE,
    push_notifications_enabled BOOLEAN DEFAULT TRUE,
    email_notifications_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- 4. Users tablosuna FCM token alanı ekleme (eğer yoksa)
ALTER TABLE users
ADD COLUMN IF NOT EXISTS fcm_token VARCHAR(255) NULL,
ADD INDEX idx_fcm_token (fcm_token);

-- Demo verileri oluştur
INSERT INTO
    notifications (
        title,
        message,
        target_type,
        notification_type
    )
VALUES (
        'Hoş Geldiniz!',
        'Oto Asist uygulamasına hoş geldiniz. Tüm araç ihtiyaçlarınız için buradayız.',
        'all',
        'general'
    ),
    (
        'Araç Muayene Hatırlatması',
        'Aracınızın muayene tarihi yaklaşıyor. Randevu almayı unutmayın!',
        'all',
        'reminder'
    ),
    (
        'Özel Kampanya',
        'Bu hafta lastik değişiminde %20 indirim fırsatı!',
        'all',
        'campaign'
    );

-- İlk kullanıcı için notification settings oluştur
INSERT INTO
    notification_settings (user_id)
SELECT id
FROM users
WHERE
    id NOT IN(
        SELECT user_id
        FROM notification_settings
    )
LIMIT 5;