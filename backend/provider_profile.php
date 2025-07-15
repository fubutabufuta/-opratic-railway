<?php
require_once 'config/database.php';
session_start();

function isProviderLoggedIn()
{
    return isset($_SESSION['provider_id']) && !empty($_SESSION['provider_id']);
}

if (!isProviderLoggedIn()) {
    header('Location: provider_panel.php');
    exit;
}

$error = '';
$message = '';

// Şehirler listesi
$cities = [
    'Lefkoşa',
    'Girne',
    'Mağusa',
    'Güzelyurt',
    'İskele',
    'Değirmenlik',
    'Dipkarpaz',
    'Gazi Mağusa',
    'Yeni Erenköy'
];

// Hizmetler listesi
$services = [
    'Genel Servis ve Bakım',
    'Motor Bakımı',
    'Fren Sistemi',
    'Lastik Değişimi',
    'Akü Değişimi',
    'Yağ Değişimi',
    'Klima Servisi',
    'Elektrik Sistemleri',
    'Kaporta ve Boyama',
    'Cam Değişimi',
    'Egzoz Sistemi',
    'Transmisyon Bakımı',
    '24 Saat Çekici Hizmeti',
    'Araç Muayene',
    'Yedek Parça Satışı'
];

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'update_profile':
                $company_name = $_POST['company_name'] ?? '';
                $city = $_POST['city'] ?? '';
                $selected_services = $_POST['services'] ?? [];
                $services_text = implode(', ', $selected_services);
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
                    $services_text,
                    $description,
                    $address,
                    $working_hours,
                    $email,
                    $_SESSION['provider_id']
                ]);

                // Session'ı güncelle
                $_SESSION['provider_user']['company_name'] = $company_name;
                $_SESSION['provider_user']['city'] = $city;
                $_SESSION['provider_user']['services'] = $services_text;
                $_SESSION['provider_user']['description'] = $description;
                $_SESSION['provider_user']['address'] = $address;
                $_SESSION['provider_user']['working_hours'] = $working_hours;
                $_SESSION['provider_user']['email'] = $email;

                $message = 'Profil başarıyla güncellendi';
                break;

            case 'subscribe':
                $package = $_POST['package'] ?? '';
                $payment_method = $_POST['payment_method'] ?? '';

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
                break;

            case 'upload_payment_proof':
                $subscription_id = $_POST['subscription_id'] ?? '';
                $payment_proof = $_POST['payment_proof'] ?? '';

                $stmt = $conn->prepare("
                    UPDATE subscriptions 
                    SET payment_proof = ?, status = 'payment_uploaded' 
                    WHERE id = ? AND provider_id = ?
                ");
                $stmt->execute([$payment_proof, $subscription_id, $_SESSION['provider_id']]);

                $message = 'Ödeme belgesi yüklendi. Admin onayı bekleniyor.';
                break;
        }
    } catch (Exception $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}

// Get provider data
$provider = $_SESSION['provider_user'];

// Get subscription info
$subscription = null;
try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE provider_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['provider_id']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Subscriptions tablosu henüz oluşturulmamış olabilir
}

// Parse existing services
$currentServices = [];
if (!empty($provider['services'])) {
    $currentServices = array_map('trim', explode(',', $provider['services']));
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Oto Asist Provider</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .package-card {
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .package-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .status-badge {
            font-size: 0.85em;
            padding: 0.5rem 1rem;
        }

        .service-checkbox {
            margin-bottom: 0.5rem;
        }

        .services-grid {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg gradient-bg navbar-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="provider_panel.php">
                <i class="fas fa-wrench me-2"></i>Oto Asist Provider
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="provider_panel.php">
                    <i class="fas fa-home me-1"></i>Ana Sayfa
                </a>
                <a class="nav-link active" href="provider_profile.php">
                    <i class="fas fa-user me-1"></i>Profil
                </a>
                <a class="nav-link" href="provider_panel.php?action=logout">
                    <i class="fas fa-sign-out-alt me-1"></i>Çıkış
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profil Bilgileri -->
            <div class="col-lg-8">
                <div class="card card-hover shadow">
                    <div class="card-header gradient-bg text-white">
                        <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Profil Bilgilerini Düzenle</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Şirket Adı</label>
                                    <input type="text" class="form-control" name="company_name"
                                        value="<?= htmlspecialchars($provider['company_name'] ?? '') ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-bold">Şehir</label>
                                    <select class="form-select" name="city" required>
                                        <option value="">Şehir Seçin</option>
                                        <?php foreach ($cities as $city): ?>
                                            <option value="<?= htmlspecialchars($city) ?>"
                                                <?= ($provider['city'] ?? '') == $city ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($city) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">E-posta</label>
                                <input type="email" class="form-control" name="email"
                                    value="<?= htmlspecialchars($provider['email'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Hizmetler</label>
                                <div class="services-grid">
                                    <?php foreach ($services as $service): ?>
                                        <div class="form-check service-checkbox">
                                            <input class="form-check-input" type="checkbox"
                                                name="services[]" value="<?= htmlspecialchars($service) ?>"
                                                id="service_<?= md5($service) ?>"
                                                <?= in_array($service, $currentServices) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="service_<?= md5($service) ?>">
                                                <?= htmlspecialchars($service) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <small class="text-muted">Sunduğunuz hizmetleri seçin</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Adres</label>
                                <textarea class="form-control" name="address" rows="2"
                                    placeholder="İş yeri adresinizi girin"><?= htmlspecialchars($provider['address'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Çalışma Saatleri</label>
                                <input type="text" class="form-control" name="working_hours"
                                    placeholder="Örn: Pazartesi-Cuma 08:00-18:00, Cumartesi 08:00-14:00"
                                    value="<?= htmlspecialchars($provider['working_hours'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Açıklama</label>
                                <textarea class="form-control" name="description" rows="4"
                                    placeholder="İşletmeniz hakkında kısa bilgi"><?= htmlspecialchars($provider['description'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Profili Güncelle
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Abonelik Durumu -->
            <div class="col-lg-4">
                <div class="card card-hover shadow">
                    <div class="card-header gradient-bg text-white">
                        <h5 class="mb-0"><i class="fas fa-crown me-2"></i>Abonelik Durumu</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($subscription): ?>
                            <div class="text-center mb-3">
                                <h6 class="text-uppercase fw-bold"><?= ucfirst($subscription['package_type']) ?> Paket</h6>
                                <span class="badge status-badge 
                                    <?php
                                    switch ($subscription['status']) {
                                        case 'active':
                                            echo 'bg-success';
                                            break;
                                        case 'pending':
                                            echo 'bg-warning';
                                            break;
                                        case 'payment_uploaded':
                                            echo 'bg-info';
                                            break;
                                        case 'rejected':
                                            echo 'bg-danger';
                                            break;
                                        default:
                                            echo 'bg-secondary';
                                    }
                                    ?>">
                                    <?php
                                    $statusTexts = [
                                        'pending' => 'Ödeme Bekleniyor',
                                        'payment_uploaded' => 'Onay Bekleniyor',
                                        'active' => 'Aktif',
                                        'rejected' => 'Reddedildi',
                                        'expired' => 'Süresi Doldu'
                                    ];
                                    echo $statusTexts[$subscription['status']] ?? 'Bilinmiyor';
                                    ?>
                                </span>
                            </div>

                            <div class="small text-muted">
                                <div><strong>Ücret:</strong> $<?= number_format($subscription['price'], 2) ?></div>
                                <div><strong>Ödeme:</strong> <?= ucfirst($subscription['payment_method']) ?></div>
                                <div><strong>Talep:</strong> <?= date('d.m.Y', strtotime($subscription['created_at'])) ?></div>
                                <?php if ($subscription['start_date']): ?>
                                    <div><strong>Başlangıç:</strong> <?= date('d.m.Y', strtotime($subscription['start_date'])) ?></div>
                                <?php endif; ?>
                                <?php if ($subscription['end_date']): ?>
                                    <div><strong>Bitiş:</strong> <?= date('d.m.Y', strtotime($subscription['end_date'])) ?></div>
                                <?php endif; ?>
                            </div>

                            <?php if ($subscription['status'] == 'pending'): ?>
                                <hr>
                                <h6>Ödeme Bilgileri</h6>
                                <p class="small text-muted">
                                    Havale/EFT için:<br>
                                    <strong>Banka:</strong> Örnek Bank<br>
                                    <strong>IBAN:</strong> TR00 0000 0000 0000 0000 0000 00<br>
                                    <strong>Açıklama:</strong> <?= $provider['company_name'] ?> - <?= $subscription['package_type'] ?>
                                </p>

                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="action" value="upload_payment_proof">
                                    <input type="hidden" name="subscription_id" value="<?= $subscription['id'] ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Ödeme Belgesi</label>
                                        <textarea class="form-control" name="payment_proof" rows="3"
                                            placeholder="Ödeme dekont bilgilerini buraya girin"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-success btn-sm w-100">
                                        <i class="fas fa-upload me-1"></i>Ödeme Belgesini Yükle
                                    </button>
                                </form>
                            <?php endif; ?>

                        <?php else: ?>
                            <p class="text-muted text-center">Henüz aktif aboneliğiniz yok.</p>
                            <a href="#subscription" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>Abonelik Satın Al
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Abonelik Paketleri -->
                <?php if (!$subscription || $subscription['status'] == 'rejected' || $subscription['status'] == 'expired'): ?>
                    <div class="card card-hover shadow mt-4">
                        <div class="card-header gradient-bg text-white">
                            <h5 class="mb-0" id="subscription"><i class="fas fa-shopping-cart me-2"></i>Abonelik Paketleri</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="subscribe">

                                <div class="mb-3">
                                    <div class="package-card border rounded p-3 mb-2">
                                        <input type="radio" name="package" value="basic" id="basic" class="form-check-input">
                                        <label for="basic" class="form-check-label w-100">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="mb-1">Temel</h6>
                                                    <small class="text-muted">5 teklif/ay</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>$29.99</strong>/ay
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="package-card border rounded p-3 mb-2">
                                        <input type="radio" name="package" value="premium" id="premium" class="form-check-input">
                                        <label for="premium" class="form-check-label w-100">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="mb-1">Premium</h6>
                                                    <small class="text-muted">15 teklif/ay</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>$49.99</strong>/ay
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="package-card border rounded p-3 mb-3">
                                        <input type="radio" name="package" value="enterprise" id="enterprise" class="form-check-input">
                                        <label for="enterprise" class="form-check-label w-100">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <h6 class="mb-1">Kurumsal</h6>
                                                    <small class="text-muted">Sınırsız teklif</small>
                                                </div>
                                                <div class="text-end">
                                                    <strong>$99.99</strong>/ay
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Ödeme Yöntemi</label>
                                    <select class="form-control" name="payment_method" required>
                                        <option value="">Seçiniz</option>
                                        <option value="bank_transfer">Havale/EFT</option>
                                        <option value="cash">Nakit</option>
                                    </select>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-credit-card me-2"></i>Abonelik Başlat
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Package selection
        document.querySelectorAll('input[name="package"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.package-card').forEach(card => {
                    card.classList.remove('selected');
                });
                this.closest('.package-card').classList.add('selected');
            });
        });

        // Service selection feedback
        document.querySelectorAll('input[name="services[]"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedCount = document.querySelectorAll('input[name="services[]"]:checked').length;
                if (checkedCount === 0) {
                    this.setCustomValidity('En az bir hizmet seçmelisiniz');
                } else {
                    document.querySelectorAll('input[name="services[]"]').forEach(cb => {
                        cb.setCustomValidity('');
                    });
                }
            });
        });
    </script>
</body>

</html>