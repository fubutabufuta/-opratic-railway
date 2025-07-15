<?php
// Bu script her gün çalıştırılmalıdır (örn: 09:00)
// Crontab örneği: 0 9 * * * /usr/bin/php /path/to/check_reminders.php

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo date('Y-m-d H:i:s') . " - Hatırlatma kontrolü başladı\n";

// 1. Otomatik hatırlatma ekleme (yeni özellik)
addAutoReminders($db);

// 2. Muayene hatırlatmaları
checkInspectionReminders($db);

// 3. Sigorta hatırlatmaları  
checkInsuranceReminders($db);

// 4. Servis hatırlatmaları
checkServiceReminders($db);

echo date('Y-m-d H:i:s') . " - Hatırlatma kontrolü tamamlandı\n";

function addAutoReminders($db)
{
    echo "Otomatik hatırlatmalar ekleniyor...\n";

    // 90 gün içinde bitecek tüm yenilemeleri bul
    // Şimdilik basit bir kontrol - aynı araç ve tip için zaten hatırlatma varsa ekleme
    $query = "SELECT r.*, u.id as user_id, u.name, v.plate, v.brand, v.model
              FROM reminders r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON v.user_id = u.id
              WHERE r.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
              ORDER BY r.due_date ASC";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $renewals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($renewals as $renewal) {
        // Aynı araç ve tip için zaten otomatik hatırlatma var mı kontrol et
        $checkQuery = "SELECT COUNT(*) as count FROM reminders 
                       WHERE vehicle_id = ? 
                       AND reminder_type = ? 
                       AND title LIKE '%Hatırlatması%'
                       AND is_completed = 0
                       AND due_date < ?";
        
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([
            $renewal['vehicle_id'], 
            $renewal['reminder_type'], 
            $renewal['due_date']
        ]);
        $existingCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['count'];

        if ($existingCount > 0) {
            echo "⏭️ Zaten hatırlatma var: " . $renewal['plate'] . " - " . getReminderType($renewal['reminder_type']) . "\n";
            continue;
        }

        // Varsayılan ayarları kullan
        $defaultDays = 7; // Varsayılan 7 gün
        $defaultTime = '09:00:00'; // Varsayılan 09:00

        // User settings tablosu varsa kullanıcının ayarlarını al
        try {
            $settingsQuery = "SELECT default_reminder_days, default_reminder_time 
                             FROM user_settings WHERE user_id = ?";
            $settingsStmt = $db->prepare($settingsQuery);
            $settingsStmt->execute([$renewal['user_id']]);
            $userSettings = $settingsStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($userSettings) {
                $defaultDays = $userSettings['default_reminder_days'] ?? 7;
                $defaultTime = $userSettings['default_reminder_time'] ?? '09:00:00';
            }
        } catch (Exception $e) {
            // User settings tablosu yoksa varsayılan değerleri kullan
            echo "⚠️ User settings tablosu bulunamadı, varsayılan değerler kullanılıyor\n";
        }

        // Hatırlatma tarihini hesapla
        $reminderDate = calculateReminderDate($renewal['due_date'], $defaultDays, $defaultTime);

        // Sadece gelecek tarihli hatırlatmalar için ekle
        if ($reminderDate > date('Y-m-d H:i:s')) {
            // Hatırlatma tipini belirle
            $reminderType = getReminderType($renewal['reminder_type']);
            
            // Hatırlatma başlığını oluştur
            $title = sprintf('%s Hatırlatması', $reminderType);
            
            // Hatırlatma açıklamasını oluştur
            $description = sprintf(
                '%s plakalı %s %s aracınızın %s yenilemesi yaklaşıyor. Tarih: %s',
                $renewal['plate'],
                $renewal['brand'],
                $renewal['model'],
                strtolower($reminderType),
                date('d/m/Y', strtotime($renewal['due_date']))
            );

            // Hatırlatmayı ekle
            $insertQuery = "INSERT INTO reminders (
                vehicle_id, 
                reminder_type, 
                title, 
                description, 
                due_date,
                is_completed, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())";

            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(1, $renewal['vehicle_id']);
            $insertStmt->bindParam(2, $renewal['reminder_type']);
            $insertStmt->bindParam(3, $title);
            $insertStmt->bindParam(4, $description);
            $insertStmt->bindParam(5, $reminderDate);

            if ($insertStmt->execute()) {
                echo "✓ Otomatik hatırlatma eklendi: " . $renewal['plate'] . " - " . $reminderType . " (" . date('d/m/Y H:i', strtotime($reminderDate)) . ")\n";
            } else {
                echo "❌ Hatırlatma eklenirken hata: " . $renewal['plate'] . " - " . $reminderType . "\n";
            }
        } else {
            echo "⏭️ Geçmiş tarihli hatırlatma atlandı: " . $renewal['plate'] . " - " . getReminderType($renewal['reminder_type']) . "\n";
        }
    }
}

function calculateReminderDate($endDate, $daysBefore, $time)
{
    $endDateTime = new DateTime($endDate);
    $reminderDate = $endDateTime->sub(new DateInterval('P' . $daysBefore . 'D'));
    
    // Saati ayarla
    $timeParts = explode(':', $time);
    $reminderDate->setTime($timeParts[0], $timeParts[1], $timeParts[2] ?? 0);
    
    return $reminderDate->format('Y-m-d H:i:s');
}

function getReminderType($type)
{
    switch ($type) {
        case 'inspection':
            return 'Muayene';
        case 'insurance':
            return 'Trafik Sigortası';
        case 'kasko':
            return 'Kasko';
        case 'service':
            return 'Servis';
        case 'tire':
            return 'Lastik';
        case 'oil_change':
            return 'Yağ Değişimi';
        default:
            return ucfirst($type);
    }
}

function checkInspectionReminders($db)
{
    echo "Muayene hatırlatmaları kontrol ediliyor...\n";

    // 30 gün içinde muayenesi bitecek araçları bul
    $query = "SELECT r.*, u.id as user_id, u.name, v.plate, v.brand, v.model
              FROM reminders r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON v.user_id = u.id
              WHERE r.reminder_type = 'inspection'
              AND r.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND IFNULL(r.notification_sent, 0) = 0";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reminders as $reminder) {
        $daysLeft = calculateDaysLeft($reminder['due_date']);

        // Bildirim mesajını oluştur
        $title = "Muayene Hatırlatması";
        $message = sprintf(
            "%s plakalı %s %s aracınızın muayenesi %d gün sonra bitiyor!",
            $reminder['plate'],
            $reminder['brand'],
            $reminder['model'],
            $daysLeft
        );

        // Bildirimi gönder
        sendNotification($db, $reminder['user_id'], $title, $message, 'reminder');

        // Hatırlatmayı gönderildi olarak işaretle
        markReminderSent($db, $reminder['id']);

        echo "✓ Muayene hatırlatması gönderildi: " . $reminder['plate'] . "\n";
    }
}

function checkInsuranceReminders($db)
{
    echo "Sigorta hatırlatmaları kontrol ediliyor...\n";

    // 30 gün içinde sigortası bitecek araçları bul
    $query = "SELECT r.*, u.id as user_id, u.name, v.plate, v.brand, v.model
              FROM reminders r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON v.user_id = u.id
              WHERE r.reminder_type IN ('insurance', 'kasko')
              AND r.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
              AND IFNULL(r.notification_sent, 0) = 0";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reminders as $reminder) {
        $daysLeft = calculateDaysLeft($reminder['due_date']);
        $insuranceType = $reminder['reminder_type'] == 'kasko' ? 'Kasko' : 'Trafik Sigortası';

        // Bildirim mesajını oluştur
        $title = $insuranceType . " Hatırlatması";
        $message = sprintf(
            "%s plakalı aracınızın %s %d gün sonra bitiyor! Yenilemeyi unutmayın.",
            $reminder['plate'],
            strtolower($insuranceType) . 'su',
            $daysLeft
        );

        // Bildirimi gönder
        sendNotification($db, $reminder['user_id'], $title, $message, 'reminder');

        // Hatırlatmayı gönderildi olarak işaretle
        markReminderSent($db, $reminder['id']);

        echo "✓ Sigorta hatırlatması gönderildi: " . $reminder['plate'] . "\n";
    }
}

function checkServiceReminders($db)
{
    echo "Servis hatırlatmaları kontrol ediliyor...\n";

    // Servis tarihi yaklaşan araçları bul
    $query = "SELECT r.*, u.id as user_id, u.name, v.plate, v.brand, v.model
              FROM reminders r
              JOIN vehicles v ON r.vehicle_id = v.id
              JOIN users u ON v.user_id = u.id
              WHERE r.reminder_type = 'service'
              AND r.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              AND IFNULL(r.notification_sent, 0) = 0";

    $stmt = $db->prepare($query);
    $stmt->execute();
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reminders as $reminder) {
        $daysLeft = calculateDaysLeft($reminder['due_date']);

        // Bildirim mesajını oluştur
        $title = "Servis Hatırlatması";
        $message = sprintf(
            "%s plakalı aracınız için servis zamanı yaklaşıyor! (%d gün kaldı)",
            $reminder['plate'],
            $daysLeft
        );

        // Bildirimi gönder
        sendNotification($db, $reminder['user_id'], $title, $message, 'reminder');

        // Hatırlatmayı gönderildi olarak işaretle
        markReminderSent($db, $reminder['id']);

        echo "✓ Servis hatırlatması gönderildi: " . $reminder['plate'] . "\n";
    }
}

function calculateDaysLeft($endDate)
{
    $today = new DateTime();
    $end = new DateTime($endDate);
    $interval = $today->diff($end);
    return $interval->days;
}

function sendNotification($db, $userId, $title, $message, $type = 'reminder')
{
    // Notifications tablosuna ekle
    $query = "INSERT INTO notifications (title, message, target_type, target_value, notification_type, status, created_at) 
              VALUES (?, ?, 'user', ?, ?, 'active', NOW())";

    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $title);
    $stmt->bindParam(2, $message);
    $stmt->bindParam(3, $userId);
    $stmt->bindParam(4, $type);

    if ($stmt->execute()) {
        $notificationId = $db->lastInsertId();

        // User notifications tablosuna ekle
        $query = "INSERT INTO user_notifications (notification_id, user_id, is_read, created_at) 
                  VALUES (?, ?, 0, NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $notificationId);
        $stmt->bindParam(2, $userId);
        $stmt->execute();

        // TODO: FCM push notification gönder
        // sendPushNotification($userId, $title, $message);

        return true;
    }

    return false;
}

function markReminderSent($db, $reminderId)
{
    $query = "UPDATE reminders SET notification_sent = 1, notification_sent_at = NOW() WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $reminderId);
    $stmt->execute();
}

// TODO: Push notification gönderme fonksiyonu
function sendPushNotification($userId, $title, $body)
{
    // FCM token'ı al
    $query = "SELECT fcm_token FROM users WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['fcm_token']) {
        // FCM API çağrısı yapılacak
        // require_once '../fcm/FCMService.php';
        // FCMService::sendNotification($user['fcm_token'], $title, $body);
    }
}
