<?php
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception('Veritabanı bağlantısı kurulamadı');
    }

    // Haberler tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS news (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        content TEXT,
        image_url VARCHAR(500),
        category VARCHAR(100),
        is_sponsored BOOLEAN DEFAULT FALSE,
        author VARCHAR(100),
        publish_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_active BOOLEAN DEFAULT TRUE,
        view_count INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    if ($db->exec($sql) !== false) {
        echo "✅ News tablosu oluşturuldu!\n";
    } else {
        throw new Exception('Tablo oluşturulamadı');
    }

    // Demo verileri ekle
    $insertSql = "INSERT INTO news (title, description, content, image_url, category, is_sponsored, author) VALUES
    ('Yeni Araç Bakım Teknolojileri', 
     'Akıllı sensörlerle araç bakımında devrim...', 
     'Otomotiv sektöründe akıllı sensör teknolojileri sayesinde araç bakımı artık daha kolay ve etkili hale geliyor.',
     'https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?w=200&h=120&fit=crop',
     'Teknoloji', 1, 'Ahmet Yılmaz'),
    
    ('Kış Lastiği Değişim Zamanı', 
     'Güvenli sürüş için doğru lastik seçimi...', 
     'Kış mevsiminin yaklaşmasıyla birlikte araç sahiplerinin en önemli görevlerinden biri kış lastiklerine geçiş yapmak.',
     'https://images.unsplash.com/photo-1558618047-3c8c76ca7d13?w=200&h=120&fit=crop',
     'Güvenlik', 0, 'Mehmet Demir'),
    
    ('Motor Yağı Seçim Rehberi', 
     'Aracınız için en uygun motor yağını nasıl seçersiniz?', 
     'Motor yağı, aracınızın kalbinin sağlıklı çalışması için en önemli unsurlardan biridir.',
     'https://images.unsplash.com/photo-1486754735734-325b5831c3ad?w=200&h=120&fit=crop',
     'Bakım', 0, 'Can Özkan'),
    
    ('Araç Sigortasında Dikkat Edilmesi Gerekenler', 
     'Sigorta seçerken hangi kriterlere odaklanmalısınız?', 
     'Araç sigortası seçimi önemli bir karardır. Bu yazıda dikkat etmeniz gereken temel kriterleri bulacaksınız.',
     'https://images.unsplash.com/photo-1450101499163-c8848c66ca85?w=200&h=120&fit=crop',
     'Sigorta', 1, 'Fatma Şen')";

    $stmt = $db->prepare($insertSql);
    if ($stmt->execute()) {
        echo "✅ Demo haberler eklendi!\n";
    } else {
        echo "⚠️ Demo haberler eklenirken hata oluştu\n";
    }

    echo "🎉 News sistemi hazır!\n";
} catch (Exception $e) {
    echo "❌ Hata: " . $e->getMessage() . "\n";
}
