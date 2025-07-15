<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $sql = "INSERT INTO news (title, description, content, image_url, category, is_sponsored, author) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);

    $news = [
        ['Yeni Araç Bakım Teknolojileri', 'Akıllı sensörlerle araç bakımında devrim...', 'Otomotiv sektöründe akıllı sensör teknolojileri sayesinde araç bakımı artık daha kolay ve etkili hale geliyor.', 'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=200&h=120&fit=crop', 'Teknoloji', 1, 'Ahmet Yılmaz'],
        ['Kış Lastiği Değişim Zamanı', 'Güvenli sürüş için doğru lastik seçimi...', 'Kış mevsiminin yaklaşmasıyla birlikte araç sahiplerinin en önemli görevlerinden biri kış lastiklerine geçiş yapmak.', 'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=200&h=120&fit=crop', 'Güvenlik', 0, 'Mehmet Demir'],
        ['Motor Yağı Seçim Rehberi', 'Aracınız için en uygun motor yağını nasıl seçersiniz?', 'Motor yağı, aracınızın kalbinin sağlıklı çalışması için en önemli unsurlardan biridir.', 'https://images.unsplash.com/photo-1486754735734-325b5831c3ad?w=200&h=120&fit=crop', 'Bakım', 0, 'Can Özkan'],
        ['Araç Sigortasında Dikkat Edilmesi Gerekenler', 'Sigorta seçerken hangi kriterlere odaklanmalısınız?', 'Araç sigortası seçimi önemli bir karardır.', 'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=200&h=120&fit=crop', 'Sigorta', 1, 'Fatma Şen']
    ];

    foreach ($news as $item) {
        $stmt->execute($item);
        echo "✅ Haber eklendi: " . $item[0] . "\n";
    }

    echo "🎉 Tüm demo haberler eklendi!\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
