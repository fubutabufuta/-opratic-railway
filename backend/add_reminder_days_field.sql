-- Hatırlatıcı tablosuna reminder_days alanı ekle
ALTER TABLE reminders
ADD COLUMN reminder_days INT DEFAULT 1 COMMENT 'Kaç gün önce hatırlatılacak';

-- Mevcut hatırlatıcıları 1 gün olarak güncelle
UPDATE reminders SET reminder_days = 1 WHERE reminder_days IS NULL;

-- Örnek veriler ekle (test için)
INSERT INTO
    reminders (
        vehicle_id,
        title,
        description,
        reminder_date,
        type,
        reminder_days
    )
VALUES (
        1,
        'Seyrüsefer Yenileme Hatırlatıcısı',
        'Seyrüsefer aboneliği yenileme zamanı yaklaşıyor',
        '2025-09-24',
        'navigation',
        30
    ),
    (
        1,
        'Seyrüsefer Yenileme Hatırlatıcısı',
        'Seyrüsefer aboneliği yenileme zamanı yaklaşıyor',
        '2025-09-30',
        'navigation',
        7
    ),
    (
        1,
        'Seyrüsefer Yenileme Hatırlatıcısı',
        'Seyrüsefer aboneliği yenileme zamanı yaklaşıyor',
        '2025-10-01',
        'navigation',
        1
    );