-- Reminders tablosuna otomatik hatırlatma için gerekli alanları ekle
ALTER TABLE reminders ADD COLUMN IF NOT EXISTS auto_reminder_added TINYINT(1) DEFAULT 0 COMMENT 'Otomatik hatırlatma eklenmiş mi?';
ALTER TABLE reminders ADD COLUMN IF NOT EXISTS auto_generated TINYINT(1) DEFAULT 0 COMMENT 'Otomatik olarak oluşturulmuş mu?';

-- User settings tablosunu oluştur (eğer yoksa)
CREATE TABLE IF NOT EXISTS user_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    default_reminder_days INT DEFAULT 7 COMMENT 'Varsayılan hatırlatma gün sayısı',
    default_reminder_time TIME DEFAULT '09:00:00' COMMENT 'Varsayılan hatırlatma saati',
    notifications_enabled TINYINT(1) DEFAULT 1 COMMENT 'Bildirimler aktif mi?',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_settings (user_id)
);

-- Mevcut kullanıcılar için varsayılan ayarları ekle
INSERT IGNORE INTO user_settings (user_id, default_reminder_days, default_reminder_time, notifications_enabled)
SELECT id, 7, '09:00:00', 1 FROM users WHERE status = 'active';

-- İndeks ekle
CREATE INDEX IF NOT EXISTS idx_reminders_auto_reminder ON reminders(auto_reminder_added);
CREATE INDEX IF NOT EXISTS idx_reminders_end_date ON reminders(end_date);
CREATE INDEX IF NOT EXISTS idx_reminders_auto_generated ON reminders(auto_generated); 