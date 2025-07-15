<?php
// Bottom navigation overflow analizi
echo "=== Bottom Navigation Overflow Analizi ===\n\n";

$tabs = [
    'Ana Sayfa' => 'Icons.home',
    'Araçlar' => 'Icons.directions_car', 
    'Kampanyalar' => 'Icons.local_offer',
    'Hatırlatmalar' => 'Icons.schedule',
    'Yenilemeler' => 'Icons.history',
    'Tekliflerim' => 'Icons.request_quote',
    'Profil' => 'Icons.person'
];

echo "Mevcut Tab Sayısı: " . count($tabs) . "\n\n";

$index = 1;
foreach ($tabs as $tabName => $tabIcon) {
    echo $index . ". $tabName\n";
    $index++;
}

echo "\n=== Problem Analizi ===\n";
echo "• Toplam 7 tab var\n";
echo "• Her tab için minimum 60-80px genişlik gerekli\n";
echo "• 7 tab × 80px = 560px minimum genişlik\n";
echo "• Mobil ekranlar 360-400px genişlik\n";
echo "• Overflow: 560px - 400px = 160px fazla\n";
echo "• 16px overflow hatası = sadece görünen kısım\n\n";

echo "=== Çözüm Önerileri ===\n";
echo "1. Tab sayısını azalt (6'ya düşür)\n";
echo "2. Tab genişliklerini küçült\n";
echo "3. Scrollable navigation yap\n";
echo "4. Dropdown menu kullan\n";
echo "5. Bottom sheet navigation\n\n";

echo "=== Önerilen Tab Düzeni ===\n";
$suggestedTabs = [
    'Ana Sayfa' => 'Icons.home',
    'Araçlar' => 'Icons.directions_car',
    'Kampanyalar' => 'Icons.local_offer', 
    'Hatırlatmalar' => 'Icons.schedule',
    'Yenilemeler' => 'Icons.history',
    'Profil' => 'Icons.person'
];

echo "Yeni Tab Sayısı: " . count($suggestedTabs) . "\n";
echo "Tekliflerim tabı kaldırıldı, profil içine taşınabilir\n";
?> 