<?php
/**
 * Development ortamı için hatırlatma loop'u
 * Production'da KULLANMAYIN!
 */

echo "🔄 Oto Asist Development Hatırlatma Loop'u\n";
echo "==========================================\n";
echo "⚠️  Bu sadece development içindir!\n";
echo "💡 Production'da cron job kullanın\n\n";

$checkInterval = 3600; // 1 saat (3600 saniye)
$lastCheck = 0;

while (true) {
    $currentTime = time();
    
    // Her saat başı kontrol et
    if ($currentTime - $lastCheck >= $checkInterval) {
        echo "\n" . date('Y-m-d H:i:s') . " - Hatırlatma kontrolü başlatılıyor...\n";
        
        // Ana cron script'i çalıştır
        include_once __DIR__ . '/cron/check_reminders.php';
        
        $lastCheck = $currentTime;
        echo date('Y-m-d H:i:s') . " - Bir sonraki kontrol: " . date('Y-m-d H:i:s', $currentTime + $checkInterval) . "\n";
    }
    
    // 60 saniye bekle
    sleep(60);
    
    // Her dakika nokta yazdır (canlı olduğunu göstermek için)
    echo ".";
}
?> 