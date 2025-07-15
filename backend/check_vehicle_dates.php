<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception("Veritabanı bağlantısı kurulamadı");
    }

    echo "=== Veritabanı Bağlantısı Başarılı ===\n";

    // Araçları ve tarih alanlarını kontrol et
    $stmt = $pdo->query("SELECT id, brand, model, plate, 
                         navigation_end_date, 
                         inspection_date, 
                         insurance_end_date, 
                         kasko_end_date, 
                         last_service_date, 
                         last_tire_change_date,
                         created_at
                         FROM vehicles ORDER BY id");

    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\n=== Araçlar ve Tarihler ===\n";
    foreach ($vehicles as $vehicle) {
        echo "ID: {$vehicle['id']} | {$vehicle['brand']} {$vehicle['model']} | {$vehicle['plate']}\n";
        echo "  Seyrüsefer Bitiş: " . ($vehicle['navigation_end_date'] ?: 'NULL') . "\n";
        echo "  Muayene Tarihi: " . ($vehicle['inspection_date'] ?: 'NULL') . "\n";
        echo "  Sigorta Bitiş: " . ($vehicle['insurance_end_date'] ?: 'NULL') . "\n";
        echo "  Kasko Bitiş: " . ($vehicle['kasko_end_date'] ?: 'NULL') . "\n";
        echo "  Son Servis: " . ($vehicle['last_service_date'] ?: 'NULL') . "\n";
        echo "  Son Lastik Değişim: " . ($vehicle['last_tire_change_date'] ?: 'NULL') . "\n";
        echo "  Oluşturulma: " . $vehicle['created_at'] . "\n";
        echo "---\n";
    }

    // Hatırlatıcıları kontrol et
    echo "\n=== Hatırlatıcılar ===\n";
    $stmt = $pdo->query("SELECT id, vehicle_id, title, description, reminder_date, type FROM reminders ORDER BY id");
    $reminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reminders as $reminder) {
        echo "ID: {$reminder['id']} | Araç ID: {$reminder['vehicle_id']} | {$reminder['title']}\n";
        echo "  Açıklama: {$reminder['description']}\n";
        echo "  Hatırlatma Tarihi: {$reminder['reminder_date']}\n";
        echo "  Tip: {$reminder['type']}\n";
        echo "---\n";
    }

    // Yaklaşan yenilemeler için hesaplama
    echo "\n=== Yaklaşan Yenilemeler Hesaplaması ===\n";
    foreach ($vehicles as $vehicle) {
        $renewals = [];

        // Seyrüsefer bitiş tarihi + 1 yıl
        if ($vehicle['navigation_end_date']) {
            $navDate = new DateTime($vehicle['navigation_end_date']);
            $navDate->add(new DateInterval('P1Y'));
            $renewals[] = [
                'type' => 'Seyrüsefer Yenileme',
                'date' => $navDate->format('Y-m-d'),
                'original' => $vehicle['navigation_end_date']
            ];
        }

        // Muayene tarihi + 1 yıl
        if ($vehicle['inspection_date']) {
            $inspDate = new DateTime($vehicle['inspection_date']);
            $inspDate->add(new DateInterval('P1Y'));
            $renewals[] = [
                'type' => 'Muayene Yenileme',
                'date' => $inspDate->format('Y-m-d'),
                'original' => $vehicle['inspection_date']
            ];
        }

        // Sigorta bitiş tarihi + 1 yıl
        if ($vehicle['insurance_end_date']) {
            $insDate = new DateTime($vehicle['insurance_end_date']);
            $insDate->add(new DateInterval('P1Y'));
            $renewals[] = [
                'type' => 'Sigorta Yenileme',
                'date' => $insDate->format('Y-m-d'),
                'original' => $vehicle['insurance_end_date']
            ];
        }

        // Kasko bitiş tarihi + 1 yıl
        if ($vehicle['kasko_end_date']) {
            $kaskoDate = new DateTime($vehicle['kasko_end_date']);
            $kaskoDate->add(new DateInterval('P1Y'));
            $renewals[] = [
                'type' => 'Kasko Yenileme',
                'date' => $kaskoDate->format('Y-m-d'),
                'original' => $vehicle['kasko_end_date']
            ];
        }

        // Son servis + 1 yıl
        if ($vehicle['last_service_date']) {
            $serviceDate = new DateTime($vehicle['last_service_date']);
            $serviceDate->add(new DateInterval('P1Y'));
            $renewals[] = [
                'type' => 'Servis Yenileme',
                'date' => $serviceDate->format('Y-m-d'),
                'original' => $vehicle['last_service_date']
            ];
        }

        // Son lastik değişim + 1 yıl
        if ($vehicle['last_tire_change_date']) {
            $tireDate = new DateTime($vehicle['last_tire_change_date']);
            $tireDate->add(new DateInterval('P1Y'));
            $renewals[] = [
                'type' => 'Lastik Değişim Yenileme',
                'date' => $tireDate->format('Y-m-d'),
                'original' => $vehicle['last_tire_change_date']
            ];
        }

        if (!empty($renewals)) {
            echo "Araç: {$vehicle['brand']} {$vehicle['model']} ({$vehicle['plate']})\n";
            foreach ($renewals as $renewal) {
                $renewalDate = new DateTime($renewal['date']);
                $now = new DateTime();
                $diff = $now->diff($renewalDate);
                $daysLeft = $diff->invert ? -$diff->days : $diff->days;

                // 3 ay (90 gün) kalan yenilemeleri göster
                if ($daysLeft <= 90 && $daysLeft >= -30) {
                    $status = $daysLeft < 0 ? "GECİKMİŞ" : "YAKLAŞIYOR";
                    echo "  {$renewal['type']}: {$renewal['date']} (Orijinal: {$renewal['original']}) - {$daysLeft} gün kaldı - {$status}\n";
                }
            }
            echo "---\n";
        }
    }
} catch (PDOException $e) {
    echo "Veritabanı hatası: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Hata: " . $e->getMessage() . "\n";
}
