<?php
require_once 'config/database.php';
session_start();

// Include authentication functions
function isProviderLoggedIn()
{
    return isset($_SESSION['provider_id']) && !empty($_SESSION['provider_id']);
}

function authenticateProvider($phone, $password)
{
    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Users tablosundan servis sağlayıcıyı çek (role_id = 2)
        $stmt = $conn->prepare("
            SELECT id, full_name, phone, password, email,
                   company_name, city, services, rating, provider_status,
                   description, address, working_hours
            FROM users 
            WHERE phone = ? AND role_id = 2
        ");

        $stmt->execute([$phone]);
        $provider = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($provider && password_verify($password, $provider['password'])) {
            return $provider;
        }

        return false;
    } catch (Exception $e) {
        return false;
    }
}

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'login':
                $phone = $_POST['phone'] ?? '';
                $password = $_POST['password'] ?? '';

                if ($provider = authenticateProvider($phone, $password)) {
                    $_SESSION['provider_id'] = $provider['id'];
                    $_SESSION['provider_user'] = $provider;
                    header('Location: provider_panel.php');
                    exit;
                } else {
                    $error = "Geçersiz telefon numarası veya şifre!";
                }
                break;

            case 'respond_quote':
                if (isProviderLoggedIn()) {
                    $quote_id = $_POST['quote_id'];
                    $response_message = $_POST['response_message'];
                    $estimated_price = $_POST['estimated_price'] ?? null;
                    $estimated_duration = $_POST['estimated_duration'] ?? null;

                    // Quote responses tablosunu oluştur
                    $conn->exec("CREATE TABLE IF NOT EXISTS quote_responses (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        quote_request_id INT NOT NULL,
                        provider_id INT NOT NULL,
                        response_message TEXT NOT NULL,
                        estimated_price DECIMAL(10,2) NULL,
                        estimated_duration VARCHAR(100) NULL,
                        status VARCHAR(50) DEFAULT 'sent',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");

                    // Teklif yanıtını kaydet
                    $stmt = $conn->prepare("
                        INSERT INTO quote_responses 
                        (quote_request_id, provider_id, response_message, estimated_price, estimated_duration, status) 
                        VALUES (?, ?, ?, ?, ?, 'sent')
                    ");
                    $stmt->execute([
                        $quote_id,
                        $_SESSION['provider_id'],
                        $response_message,
                        $estimated_price,
                        $estimated_duration
                    ]);

                    // Teklif talebinin durumunu güncelle
                    $stmt = $conn->prepare("UPDATE quote_requests SET status = 'responded' WHERE id = ?");
                    $stmt->execute([$quote_id]);

                    // Kullanıcıya bildirim gönder
                    $stmt = $conn->prepare("SELECT user_id FROM quote_requests WHERE id = ?");
                    $stmt->execute([$quote_id]);
                    $user_id = $stmt->fetchColumn();

                    if ($user_id) {
                        $provider_name = $_SESSION['provider_user']['company_name'];
                        $stmt = $conn->prepare("
                            INSERT INTO notifications 
                            (title, message, user_id, notification_type, status) 
                            VALUES (?, ?, ?, 'quote_response', 'active')
                        ");
                        $stmt->execute([
                            "Teklif Yanıtı Alındı",
                            "{$provider_name} firmasından teklif yanıtı aldınız.",
                            $user_id
                        ]);
                    }

                    $message = 'Teklif yanıtı başarıyla gönderildi';
                }
                break;

            case 'update_profile':
                if (isProviderLoggedIn()) {
                    $company_name = $_POST['company_name'] ?? '';
                    $city = $_POST['city'] ?? '';
                    $services = $_POST['services'] ?? '';
                    $description = $_POST['description'] ?? '';
                    $address = $_POST['address'] ?? '';
                    $working_hours = $_POST['working_hours'] ?? '';
                    $email = $_POST['email'] ?? '';

                    $stmt = $conn->prepare("
                        UPDATE users SET 
                        company_name = ?, city = ?, services = ?, 
                        description = ?, address = ?, working_hours = ?, email = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $company_name,
                        $city,
                        $services,
                        $description,
                        $address,
                        $working_hours,
                        $email,
                        $_SESSION['provider_id']
                    ]);

                    // Session'ı güncelle
                    $_SESSION['provider_user']['company_name'] = $company_name;
                    $_SESSION['provider_user']['city'] = $city;
                    $_SESSION['provider_user']['services'] = $services;
                    $_SESSION['provider_user']['description'] = $description;
                    $_SESSION['provider_user']['address'] = $address;
                    $_SESSION['provider_user']['working_hours'] = $working_hours;
                    $_SESSION['provider_user']['email'] = $email;

                    $message = 'Profil başarıyla güncellendi';
                }
                break;

            case 'subscribe':
                if (isProviderLoggedIn()) {
                    $package = $_POST['package'] ?? '';
                    $payment_method = $_POST['payment_method'] ?? '';

                    // Subscriptions tablosunu oluştur
                    $conn->exec("CREATE TABLE IF NOT EXISTS subscriptions (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        provider_id INT NOT NULL,
                        package_type VARCHAR(50) NOT NULL,
                        price DECIMAL(10,2) NOT NULL,
                        payment_method VARCHAR(50) NOT NULL,
                        status VARCHAR(50) DEFAULT 'pending',
                        start_date DATE NULL,
                        end_date DATE NULL,
                        payment_proof TEXT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (provider_id) REFERENCES users(id)
                    )");

                    $prices = [
                        'basic' => 29.99,
                        'premium' => 49.99,
                        'enterprise' => 99.99
                    ];

                    $price = $prices[$package] ?? 0;

                    $stmt = $conn->prepare("
                        INSERT INTO subscriptions 
                        (provider_id, package_type, price, payment_method, status) 
                        VALUES (?, ?, ?, ?, 'pending')
                    ");
                    $stmt->execute([
                        $_SESSION['provider_id'],
                        $package,
                        $price,
                        $payment_method
                    ]);

                    $message = 'Abonelik talebi oluşturuldu. Ödeme yapıldıktan sonra admin tarafından onaylanacaktır.';
                }
                break;

            case 'logout':
                session_destroy();
                header('Location: provider_panel.php');
                exit;
                break;
        }
    } catch (Exception $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}

// Get data for logged in provider
$quotes = [];
$stats = [];
if (isProviderLoggedIn()) {
    try {
        $database = new Database();
        $conn = $database->getConnection();

        // Quote responses tablosunu oluştur
        $conn->exec("CREATE TABLE IF NOT EXISTS quote_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            quote_request_id INT NOT NULL,
            provider_id INT NOT NULL,
            response_message TEXT NOT NULL,
            estimated_price DECIMAL(10,2) NULL,
            estimated_duration VARCHAR(100) NULL,
            status VARCHAR(50) DEFAULT 'sent',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Servis sağlayıcıya uygun teklif taleplerini getir
        $provider_services = $_SESSION['provider_user']['services'];
        $provider_city = $_SESSION['provider_user']['city'];

        // Debug için provider bilgilerini logla
        error_log("Provider services: " . $provider_services);
        error_log("Provider city: " . $provider_city);

        // Daha esnek servis eşleştirmesi için alternatif arama kriterleri
        $serviceTerms = [
            $provider_services,
            'Genel Servis',
            'Servis ve Bakım',
            'Motor Bakımı',
            'Servis',
            'Bakım'
        ];

        // Birden fazla WHERE koşulu için SQL hazırla
        $whereClauses = [];
        $params = [$_SESSION['provider_id']];

        // Şehir koşulu (varsa)
        if (!empty($provider_city)) {
            $whereClauses[] = "qr.city = ?";
            $params[] = $provider_city;
        }

        // Servis türü eşleştirme - daha esnek
        $serviceWhere = "(";
        $serviceConditions = [];

        foreach ($serviceTerms as $term) {
            if (!empty($term)) {
                $serviceConditions[] = "qr.service_type LIKE ?";
                $params[] = '%' . $term . '%';

                $serviceConditions[] = "qr.title LIKE ?";
                $params[] = '%' . $term . '%';

                $serviceConditions[] = "qr.description LIKE ?";
                $params[] = '%' . $term . '%';
            }
        }

        // Spesifik service_type eşleştirmeleri ekle
        $serviceTypes = ['maintenance', 'service', 'repair', 'parts', 'towing', 'other'];
        foreach ($serviceTypes as $type) {
            if (
                stripos($provider_services, $type) !== false ||
                stripos($provider_services, 'Servis') !== false ||
                stripos($provider_services, 'Bakım') !== false
            ) {
                $serviceConditions[] = "qr.service_type = ?";
                $params[] = $type;
            }
        }

        $serviceWhere .= implode(' OR ', $serviceConditions) . ")";

        if (!empty($serviceConditions)) {
            $whereClauses[] = $serviceWhere;
        }

        // Final WHERE clause
        $whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

        $sql = "
            SELECT qr.*, u.full_name, u.phone, v.brand, v.model, v.year, v.plate,
                   (SELECT COUNT(*) FROM quote_responses qres WHERE qres.quote_request_id = qr.id AND qres.provider_id = ?) as has_responded
            FROM quote_requests qr 
            JOIN users u ON qr.user_id = u.id 
            LEFT JOIN vehicles v ON qr.vehicle_id = v.id 
            $whereSQL
            ORDER BY qr.created_at DESC 
            LIMIT 50
        ";

        error_log("Provider quotes SQL: " . $sql);
        error_log("Provider quotes params: " . print_r($params, true));

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Found " . count($quotes) . " quotes for provider");

        // İstatistikler - aynı kriterleri kullan
        $statsWhereSQL = str_replace('qr.', '', $whereSQL);
        $statsParams = array_slice($params, 1); // İlk parametre provider_id olduğu için atla

        $statsSql = "SELECT COUNT(*) FROM quote_requests $statsWhereSQL";
        $stmt = $conn->prepare($statsSql);
        $stmt->execute($statsParams);
        $stats['total_quotes'] = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM quote_responses WHERE provider_id = ?");
        $stmt->execute([$_SESSION['provider_id']]);
        $stats['responded_quotes'] = $stmt->fetchColumn();

        $stmt = $conn->prepare("SELECT COUNT(*) FROM quote_requests qr JOIN quote_responses qres ON qr.id = qres.quote_request_id WHERE qres.provider_id = ? AND qr.status = 'completed'");
        $stmt->execute([$_SESSION['provider_id']]);
        $stats['completed_quotes'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $error = 'Veri yüklenirken hata oluştu: ' . $e->getMessage();
    }
}

// Get quote details for modal
$quoteDetails = null;
if (isset($_GET['view_quote']) && isset($_GET['id']) && isProviderLoggedIn()) {
    try {
        $database = new Database();
        $conn = $database->getConnection();

        $stmt = $conn->prepare("
            SELECT qr.*, u.full_name, u.phone, u.email, v.brand, v.model, v.year, v.plate 
            FROM quote_requests qr 
            JOIN users u ON qr.user_id = u.id 
            LEFT JOIN vehicles v ON qr.vehicle_id = v.id 
            WHERE qr.id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $quoteDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = 'Teklif detayı yüklenirken hata oluştu: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Servis Sağlayıcı Paneli - Oto Asist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .quote-card {
            transition: transform 0.2s;
        }

        .quote-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            font-size: 0.8em;
        }

        .company-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>

<body class="bg-light">

    <?php if (!isProviderLoggedIn()): ?>
        <!-- Login Form -->
        <div class="container mt-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header company-header text-center">
                            <h4><i class="fas fa-tools me-2"></i>Servis Sağlayıcı Paneli</h4>
                            <p class="mb-0">Oto Asist'e Hoş Geldiniz</p>
                        </div>
                        <div class="card-body">
                            <?php if (isset($error)): ?>
                                <div class="alert alert-danger"><?= $error ?></div>
                            <?php endif; ?>

                            <form method="POST">
                                <input type="hidden" name="action" value="login">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Telefon Numarası</label>
                                    <input type="text" class="form-control" id="phone" name="phone" placeholder="+905551234567" required>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Şifre</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Giriş Yap
                                </button>
                            </form>

                            <hr>
                            <div class="text-center">
                                <small class="text-muted">Demo Giriş Bilgileri:</small><br>
                                <small><strong>Telefon:</strong> +905551234567</small><br>
                                <small><strong>Şifre:</strong> 123456</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Main Dashboard -->
        <nav class="navbar navbar-expand-lg navbar-dark company-header">
            <div class="container">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-tools me-2"></i>
                    <?= htmlspecialchars($_SESSION['provider_user']['company_name']) ?>
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="provider_profile.php">
                        <i class="fas fa-user me-1"></i>Profil
                    </a>
                    <span class="nav-link">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        <?= htmlspecialchars($_SESSION['provider_user']['city']) ?>
                    </span>
                    <span class="nav-link">
                        <i class="fas fa-star me-1"></i>
                        <?= number_format($_SESSION['provider_user']['rating'], 1) ?>
                    </span>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i>Çıkış
                        </button>
                    </form>
                </div>
            </div>
        </nav>

        <div class="container mt-4">
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-clipboard-list fa-2x text-primary mb-2"></i>
                            <h4><?= $stats['total_quotes'] ?? 0 ?></h4>
                            <p class="text-muted">Toplam Teklif Talebi</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-reply fa-2x text-success mb-2"></i>
                            <h4><?= $stats['responded_quotes'] ?? 0 ?></h4>
                            <p class="text-muted">Yanıtlanan Teklifler</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-2x text-warning mb-2"></i>
                            <h4><?= $stats['completed_quotes'] ?? 0 ?></h4>
                            <p class="text-muted">Tamamlanan İşler</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quote Requests -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-list me-2"></i>Teklif Talepleri</h5>
                    <small class="text-muted">Size uygun teklif talepleri aşağıda listelenmektedir</small>
                </div>
                <div class="card-body">
                    <?php if (!empty($quotes)): ?>
                        <div class="row">
                            <?php foreach ($quotes as $quote): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card quote-card">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title"><?= htmlspecialchars($quote['title']) ?></h6>
                                                <?php if ($quote['has_responded'] > 0): ?>
                                                    <span class="badge bg-success status-badge">Yanıtlandı</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning status-badge">Bekliyor</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="card-text"><?= htmlspecialchars(substr($quote['description'], 0, 100)) ?>...</p>
                                            <div class="row text-sm">
                                                <div class="col-6">
                                                    <small><strong>Müşteri:</strong> <?= htmlspecialchars($quote['full_name']) ?></small><br>
                                                    <small><strong>Telefon:</strong> <?= htmlspecialchars($quote['phone']) ?></small>
                                                </div>
                                                <div class="col-6">
                                                    <small><strong>Araç:</strong> <?= htmlspecialchars(($quote['brand'] ?? '') . ' ' . ($quote['model'] ?? '')) ?></small><br>
                                                    <small><strong>Hizmet:</strong> <?= htmlspecialchars($quote['service_type']) ?></small>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d.m.Y H:i', strtotime($quote['created_at'])) ?>
                                                </small>
                                                <div>
                                                    <button class="btn btn-outline-primary btn-sm"
                                                        onclick="viewQuote(<?= $quote['id'] ?>)">
                                                        <i class="fas fa-eye me-1"></i>Detay
                                                    </button>
                                                    <?php if ($quote['has_responded'] == 0): ?>
                                                        <button class="btn btn-primary btn-sm"
                                                            onclick="respondQuote(<?= $quote['id'] ?>)">
                                                            <i class="fas fa-reply me-1"></i>Yanıtla
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>Henüz teklif talebi bulunmuyor</h5>
                            <p class="text-muted">Size uygun yeni teklif talepleri geldiğinde burada görünecektir.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quote Details Modal -->
        <div class="modal fade" id="quoteModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Teklif Detayları</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="quoteModalBody">
                        <!-- Content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Response Modal -->
        <div class="modal fade" id="responseModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Teklif Yanıtı Gönder</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="action" value="respond_quote">
                        <input type="hidden" name="quote_id" id="responseQuoteId">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Yanıt Mesajı</label>
                                <textarea name="response_message" class="form-control" rows="4" required
                                    placeholder="Müşteriye gönderilecek yanıt mesajınızı yazın..."></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Tahmini Fiyat (₺)</label>
                                    <input type="number" name="estimated_price" class="form-control" step="0.01"
                                        placeholder="Örn: 1500">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tahmini Süre</label>
                                    <input type="text" name="estimated_duration" class="form-control"
                                        placeholder="Örn: 2-3 gün">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary">Yanıt Gönder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewQuote(quoteId) {
            document.getElementById('quoteModalBody').innerHTML = `
                <div class="d-flex justify-content-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            `;

            // Modal'ı göster
            var modal = new bootstrap.Modal(document.getElementById('quoteModal'));
            modal.show();

            // AJAX ile teklif detaylarını yükle
            fetch(`provider_panel.php?view_quote=1&id=${quoteId}`)
                .then(response => response.text())
                .then(data => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(data, 'text/html');
                    const quoteDetails = doc.querySelector('#quoteDetailsContent');

                    if (quoteDetails) {
                        document.getElementById('quoteModalBody').innerHTML = quoteDetails.innerHTML;
                    } else {
                        document.getElementById('quoteModalBody').innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Teklif detayları yüklenirken hata oluştu.
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('quoteModalBody').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Ağ hatası: Teklif detayları yüklenemedi.
                        </div>
                    `;
                });
        }

        function respondQuote(quoteId) {
            document.getElementById('responseQuoteId').value = quoteId;
            var modal = new bootstrap.Modal(document.getElementById('responseModal'));
            modal.show();
        }

        // Auto-refresh every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>

    <!-- Quote Details Content (Hidden, for AJAX) -->
    <?php if (isset($_GET['view_quote']) && $quoteDetails): ?>
        <div id="quoteDetailsContent" style="display: none;">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-user me-2"></i>Müşteri Bilgileri</h6>
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Ad Soyad:</strong></td>
                                    <td><?= htmlspecialchars($quoteDetails['full_name']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Telefon:</strong></td>
                                    <td><?= htmlspecialchars($quoteDetails['phone']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>E-posta:</strong></td>
                                    <td><?= htmlspecialchars($quoteDetails['email']) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6><i class="fas fa-car me-2"></i>Araç Bilgileri</h6>
                    <div class="card mb-3">
                        <div class="card-body">
                            <?php if ($quoteDetails['brand']): ?>
                                <table class="table table-borderless table-sm">
                                    <tr>
                                        <td><strong>Marka:</strong></td>
                                        <td><?= htmlspecialchars($quoteDetails['brand']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Model:</strong></td>
                                        <td><?= htmlspecialchars($quoteDetails['model']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Yıl:</strong></td>
                                        <td><?= htmlspecialchars($quoteDetails['year']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Plaka:</strong></td>
                                        <td><?= htmlspecialchars($quoteDetails['plate']) ?></td>
                                    </tr>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">Araç bilgileri mevcut değil</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <h6><i class="fas fa-clipboard-list me-2"></i>Teklif Detayları</h6>
                    <div class="card mb-3">
                        <div class="card-body">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Başlık:</strong></td>
                                    <td><?= htmlspecialchars($quoteDetails['title']) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Hizmet Türü:</strong></td>
                                    <td>
                                        <?php
                                        $serviceNames = [
                                            'maintenance' => 'Bakım',
                                            'repair' => 'Tamir',
                                            'parts' => 'Yedek Parça',
                                            'towing' => 'Çekici',
                                            'insurance' => 'Sigorta',
                                            'other' => 'Diğer'
                                        ];
                                        echo htmlspecialchars($serviceNames[$quoteDetails['service_type']] ?? $quoteDetails['service_type']);
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Tarih:</strong></td>
                                    <td><?= date('d.m.Y H:i', strtotime($quoteDetails['created_at'])) ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Durum:</strong></td>
                                    <td>
                                        <?php if ($quoteDetails['status'] == 'pending'): ?>
                                            <span class="badge bg-warning">Bekliyor</span>
                                        <?php elseif ($quoteDetails['status'] == 'responded'): ?>
                                            <span class="badge bg-success">Yanıtlandı</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= ucfirst($quoteDetails['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <h6><i class="fas fa-comment me-2"></i>Açıklama</h6>
                    <div class="card">
                        <div class="card-body">
                            <p><?= nl2br(htmlspecialchars($quoteDetails['description'])) ?></p>

                            <?php if (!empty($quoteDetails['user_notes'])): ?>
                                <hr>
                                <h6><small>Müşteri Notları:</small></h6>
                                <p class="text-muted"><small><?= nl2br(htmlspecialchars($quoteDetails['user_notes'])) ?></small></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
                        <?php
                        // Check if already responded
                        $stmt = $conn->prepare("SELECT COUNT(*) FROM quote_responses WHERE quote_request_id = ? AND provider_id = ?");
                        $stmt->execute([$quoteDetails['id'], $_SESSION['provider_id']]);
                        $hasResponded = $stmt->fetchColumn() > 0;
                        ?>
                        <?php if (!$hasResponded): ?>
                            <button type="button" class="btn btn-primary" onclick="respondQuote(<?= $quoteDetails['id'] ?>)" data-bs-dismiss="modal">
                                <i class="fas fa-reply me-1"></i>Teklif Ver
                            </button>
                        <?php else: ?>
                            <span class="badge bg-success fs-6 p-2">
                                <i class="fas fa-check me-1"></i>Zaten Yanıtladınız
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</body>

</html>