<?php
require_once 'config/database.php';
session_start();

// Token kontrolü
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if ($token !== '+905551234567') {
    die('Geçersiz admin token!');
}

$error = '';
$message = '';

// Handle POST actions
if ($_POST) {
    $action = $_POST['action'] ?? '';

    try {
        $database = new Database();
        $conn = $database->getConnection();

        switch ($action) {
            case 'approve_subscription':
                $subscription_id = $_POST['subscription_id'];
                $months = $_POST['months'] ?? 1;

                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime($start_date . " +{$months} months"));

                $stmt = $conn->prepare("
                    UPDATE subscriptions 
                    SET status = 'active', start_date = ?, end_date = ?, approved_by = 1, approved_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$start_date, $end_date, $subscription_id]);

                // Provider status'unu güncelle
                $stmt = $conn->prepare("
                    SELECT provider_id FROM subscriptions WHERE id = ?
                ");
                $stmt->execute([$subscription_id]);
                $provider_id = $stmt->fetchColumn();

                if ($provider_id) {
                    $stmt = $conn->prepare("
                        UPDATE users SET provider_status = 'premium' WHERE id = ?
                    ");
                    $stmt->execute([$provider_id]);
                }

                $message = 'Abonelik onaylandı ve aktif edildi';
                break;

            case 'reject_subscription':
                $subscription_id = $_POST['subscription_id'];
                $notes = $_POST['notes'] ?? '';

                $stmt = $conn->prepare("
                    UPDATE subscriptions 
                    SET status = 'rejected', notes = ?, approved_by = 1, approved_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$notes, $subscription_id]);

                $message = 'Abonelik reddedildi';
                break;
        }
    } catch (Exception $e) {
        $error = 'Bir hata oluştu: ' . $e->getMessage();
    }
}

// Get all subscriptions
$subscriptions = [];
try {
    $database = new Database();
    $conn = $database->getConnection();

    $stmt = $conn->prepare("
        SELECT s.*, u.company_name, u.full_name, u.phone, u.email 
        FROM subscriptions s 
        JOIN users u ON s.provider_id = u.id 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Veri yüklenirken hata oluştu: ' . $e->getMessage();
}

// Get statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'active' => 0,
    'expired' => 0,
    'revenue' => 0
];

foreach ($subscriptions as $sub) {
    $stats['total']++;
    $stats[$sub['status']]++;
    if ($sub['status'] == 'active') {
        $stats['revenue'] += $sub['price'];
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abonelik Yönetimi - Oto Asist Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .status-badge {
            font-size: 0.85em;
            padding: 0.5rem 1rem;
        }

        .card-hover {
            transition: transform 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-2px);
        }

        .sidebar {
            min-height: 100vh;
            background: #343a40 !important;
        }

        .sidebar-menu {
            padding: 0;
        }

        .menu-item {
            display: block;
            padding: 1rem 1.5rem;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid #495057;
            transition: all 0.3s ease;
        }

        .menu-item:hover {
            background: #495057;
            color: #fff;
            text-decoration: none;
        }

        .menu-item.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
        }

        .menu-item i {
            width: 20px;
            margin-right: 10px;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg gradient-bg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="admin_modern.php?token=<?= urlencode($token) ?>">
                <i class="fas fa-tools me-2"></i>Oto Asist Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="admin_modern.php?token=<?= urlencode($token) ?>">
                    <i class="fas fa-home me-1"></i>Ana Sayfa
                </a>
                <a class="nav-link active" href="admin_subscriptions.php?token=<?= urlencode($token) ?>">
                    <i class="fas fa-crown me-1"></i>Abonelikler
                </a>
            </div>
        </div>
    </nav>

    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar bg-dark text-white p-0" style="min-height: 100vh; width: 250px;">
            <div class="sidebar-menu">
                <a href="admin_modern.php?action=dashboard&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="admin_modern.php?action=users&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-users"></i> Kullanıcılar
                </a>
                <a href="admin_modern.php?action=providers&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-building"></i> Servis Sağlayıcılar
                </a>
                <a href="admin_modern.php?action=packages&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-box"></i> Paketler
                </a>
                <a href="admin_subscriptions.php?token=<?= urlencode($token) ?>" class="menu-item active">
                    <i class="fas fa-credit-card"></i> Abonelik Talepleri
                </a>
                <a href="admin_modern.php?action=news&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-newspaper"></i> Haberler
                </a>
                <a href="admin_modern.php?action=sliders&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-images"></i> Sliderlar
                </a>
                <a href="admin_modern.php?action=quotes&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-clipboard-list"></i> Teklifler
                </a>
                <a href="admin_modern.php?action=notifications&token=<?= urlencode($token) ?>" class="menu-item">
                    <i class="fas fa-bell"></i> Bildirimler
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <div class="container-fluid my-4">
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

                <!-- İstatistikler -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card card-hover text-center gradient-bg text-white">
                            <div class="card-body">
                                <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                <h4><?= $stats['total'] ?></h4>
                                <small>Toplam Abonelik</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-hover text-center bg-warning text-dark">
                            <div class="card-body">
                                <i class="fas fa-clock fa-2x mb-2"></i>
                                <h4><?= $stats['pending'] ?></h4>
                                <small>Bekleyen</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-hover text-center bg-success text-white">
                            <div class="card-body">
                                <i class="fas fa-check-circle fa-2x mb-2"></i>
                                <h4><?= $stats['active'] ?></h4>
                                <small>Aktif</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card card-hover text-center bg-info text-white">
                            <div class="card-body">
                                <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                <h4>$<?= number_format($stats['revenue'], 0) ?></h4>
                                <small>Aylık Gelir</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Abonelik Listesi -->
                <div class="card shadow">
                    <div class="card-header gradient-bg text-white">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Abonelik Talepleri</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Şirket</th>
                                        <th>İletişim</th>
                                        <th>Paket</th>
                                        <th>Ücret</th>
                                        <th>Ödeme</th>
                                        <th>Durum</th>
                                        <th>Tarih</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subscriptions as $sub): ?>
                                        <tr>
                                            <td><?= $sub['id'] ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($sub['company_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($sub['full_name']) ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-phone me-1"></i><?= htmlspecialchars($sub['phone']) ?><br>
                                                    <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($sub['email'] ?? 'N/A') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?= ucfirst($sub['package_type']) ?></span>
                                            </td>
                                            <td>$<?= number_format($sub['price'], 2) ?></td>
                                            <td><?= ucfirst($sub['payment_method']) ?></td>
                                            <td>
                                                <span class="badge status-badge 
                                                <?php
                                                switch ($sub['status']) {
                                                    case 'active':
                                                        echo 'bg-success';
                                                        break;
                                                    case 'pending':
                                                        echo 'bg-warning text-dark';
                                                        break;
                                                    case 'payment_uploaded':
                                                        echo 'bg-info';
                                                        break;
                                                    case 'rejected':
                                                        echo 'bg-danger';
                                                        break;
                                                    case 'expired':
                                                        echo 'bg-secondary';
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
                                                    echo $statusTexts[$sub['status']] ?? 'Bilinmiyor';
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <strong>Talep:</strong> <?= date('d.m.Y', strtotime($sub['created_at'])) ?><br>
                                                    <?php if ($sub['start_date']): ?>
                                                        <strong>Başlangıç:</strong> <?= date('d.m.Y', strtotime($sub['start_date'])) ?><br>
                                                    <?php endif; ?>
                                                    <?php if ($sub['end_date']): ?>
                                                        <strong>Bitiş:</strong> <?= date('d.m.Y', strtotime($sub['end_date'])) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($sub['status'] == 'pending' || $sub['status'] == 'payment_uploaded'): ?>
                                                    <button class="btn btn-success btn-sm"
                                                        onclick="approveSubscription(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['company_name']) ?>')">
                                                        <i class="fas fa-check me-1"></i>Onayla
                                                    </button>
                                                    <button class="btn btn-danger btn-sm ms-1"
                                                        onclick="rejectSubscription(<?= $sub['id'] ?>, '<?= htmlspecialchars($sub['company_name']) ?>')">
                                                        <i class="fas fa-times me-1"></i>Reddet
                                                    </button>
                                                <?php endif; ?>

                                                <?php if ($sub['payment_proof']): ?>
                                                    <button class="btn btn-info btn-sm ms-1"
                                                        onclick="viewPaymentProof('<?= htmlspecialchars($sub['payment_proof']) ?>')">
                                                        <i class="fas fa-receipt me-1"></i>Belge
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Onaylama Modal -->
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Abonelik Onaylama</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="approve_subscription">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="subscription_id" id="approve_subscription_id">

                        <p>Bu aboneliği onaylamak istediğinizden emin misiniz?</p>
                        <p><strong>Şirket:</strong> <span id="approve_company_name"></span></p>

                        <div class="mb-3">
                            <label class="form-label">Abonelik Süresi (Ay)</label>
                            <select class="form-control" name="months" required>
                                <option value="1">1 Ay</option>
                                <option value="3">3 Ay</option>
                                <option value="6">6 Ay</option>
                                <option value="12" selected>12 Ay</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Not (Opsiyonel)</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Onay ile ilgili notlar..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-success">Onayla</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reddetme Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Abonelik Reddetme</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reject_subscription">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                        <input type="hidden" name="subscription_id" id="reject_subscription_id">

                        <p>Bu aboneliği reddetmek istediğinizden emin misiniz?</p>
                        <p><strong>Şirket:</strong> <span id="reject_company_name"></span></p>

                        <div class="mb-3">
                            <label class="form-label">Reddetme Sebebi</label>
                            <textarea class="form-control" name="notes" rows="3" placeholder="Reddetme sebebini açıklayın..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-danger">Reddet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Ödeme Belgesi Modal -->
    <div class="modal fade" id="paymentProofModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ödeme Belgesi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentProofContent"></div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function approveSubscription(id, companyName) {
            document.getElementById('approve_subscription_id').value = id;
            document.getElementById('approve_company_name').textContent = companyName;
            new bootstrap.Modal(document.getElementById('approveModal')).show();
        }

        function rejectSubscription(id, companyName) {
            document.getElementById('reject_subscription_id').value = id;
            document.getElementById('reject_company_name').textContent = companyName;
            new bootstrap.Modal(document.getElementById('rejectModal')).show();
        }

        function viewPaymentProof(proof) {
            document.getElementById('paymentProofContent').innerHTML = '<pre>' + proof + '</pre>';
            new bootstrap.Modal(document.getElementById('paymentProofModal')).show();
        }
    </script>
</body>

</html>