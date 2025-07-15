<?php
require_once 'config/database.php';
session_start();

// Include all functions from admin.php
include 'admin.php';

$action = $_GET['action'] ?? 'dashboard';
$data = [];
$stats = [];

if (isAdminLoggedIn()) {
    $stats = getDashboardStats();
    if ($action !== 'dashboard') {
        $data = getData($action);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oto Asist - Tam Admin Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .admin-panel {
            padding: 40px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 25px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card:nth-child(2) {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
        }

        .stat-card:nth-child(3) {
            background: linear-gradient(45deg, #45b7d1, #96c93d);
        }

        .stat-card:nth-child(4) {
            background: linear-gradient(45deg, #f093fb, #f5576c);
        }

        .stat-card h3 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .navigation {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
        }

        .nav-btn {
            background: white;
            border: 2px solid #e0e0e0;
            padding: 15px 20px;
            border-radius: 8px;
            text-decoration: none;
            color: #2c3e50;
            font-weight: 600;
            transition: all 0.3s;
        }

        .nav-btn:hover,
        .nav-btn.active {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .section {
            margin-bottom: 40px;
        }

        .section h3 {
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .form-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }

        .btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-danger {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
        }

        .btn-success {
            background: linear-gradient(45deg, #4ecdc4, #44a08d);
        }

        .data-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #2c3e50;
            color: white;
        }

        .data-table th,
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .data-table tbody tr:hover {
            background: #e3f2fd;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success {
            background: #4ecdc4;
            color: white;
        }

        .error {
            background: #ff6b6b;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 5px;
        }

        .action-buttons button {
            padding: 5px 10px;
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Oto Asist</h1>
            <p>Kapsamlı Admin Panel Yönetim Sistemi</p>
            <?php if (isAdminLoggedIn()): ?>
                <p>Hoş geldiniz, <?= htmlspecialchars($_SESSION['admin_user']['full_name']) ?>!</p>
            <?php endif; ?>
        </div>

        <?php if (!isAdminLoggedIn()): ?>
            <div style="padding: 40px; text-align: center;">
                <h2>Admin Girişi</h2>
                <form method="POST" style="max-width: 400px; margin: 0 auto;">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label>Admin Token:</label>
                        <input type="text" name="token" value="+905551234567" required>
                    </div>
                    <button type="submit" class="btn">🔐 Giriş Yap</button>
                </form>
            </div>
        <?php else: ?>
            <div class="admin-panel">
                <a href="?logout=1" style="float: right; background: #ff6b6b; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">🚪 Çıkış</a>
                <div style="clear: both;"></div>

                <!-- Dashboard Stats -->
                <h2>📊 Dashboard İstatistikleri</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?= $stats['total_users'] ?></h3>
                        <p>Toplam Kullanıcı</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $stats['total_providers'] ?></h3>
                        <p>Servis Sağlayıcı</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $stats['active_subscriptions'] ?></h3>
                        <p>Aktif Abonelik</p>
                    </div>
                    <div class="stat-card">
                        <h3><?= $stats['monthly_quotes'] ?></h3>
                        <p>Aylık Teklif</p>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="navigation">
                    <a href="?action=dashboard" class="nav-btn <?= $action === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a>
                    <a href="?action=users" class="nav-btn <?= $action === 'users' ? 'active' : '' ?>">👥 Kullanıcılar</a>
                    <a href="?action=providers" class="nav-btn <?= $action === 'providers' ? 'active' : '' ?>">🏢 Servis Sağlayıcılar</a>
                    <a href="?action=packages" class="nav-btn <?= $action === 'packages' ? 'active' : '' ?>">📦 Paketler</a>
                    <a href="?action=news" class="nav-btn <?= $action === 'news' ? 'active' : '' ?>">📰 Haberler</a>
                    <a href="?action=sliders" class="nav-btn <?= $action === 'sliders' ? 'active' : '' ?>">🖼️ Sliderlar</a>
                    <a href="?action=quotes" class="nav-btn <?= $action === 'quotes' ? 'active' : '' ?>">💬 Teklifler</a>
                    <a href="?action=provider_requests" class="nav-btn <?= $action === 'provider_requests' ? 'active' : '' ?>">📋 Provider Talepleri</a>
                </div>

                <?php if (isset($message)): ?>
                    <div class="message <?= strpos($message, 'başarıyla') !== false ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Content Sections -->
                <?php if ($action === 'users'): ?>
                    <div class="section">
                        <h3>👥 Kullanıcı Yönetimi</h3>

                        <!-- Add User Form -->
                        <div class="form-grid">
                            <div class="form-card">
                                <h4>Yeni Kullanıcı Ekle</h4>
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_user">
                                    <div class="form-group">
                                        <label>Ad Soyad:</label>
                                        <input type="text" name="full_name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Email:</label>
                                        <input type="email" name="email">
                                    </div>
                                    <div class="form-group">
                                        <label>Telefon:</label>
                                        <input type="text" name="phone" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Rol:</label>
                                        <select name="role_id">
                                            <option value="1">User</option>
                                            <option value="2">Provider</option>
                                            <option value="3">Admin</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn">➕ Kullanıcı Ekle</button>
                                </form>
                            </div>
                        </div>

                        <!-- Users Table -->
                        <?php if (!empty($data)): ?>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Ad Soyad</th>
                                            <th>Telefon</th>
                                            <th>Email</th>
                                            <th>Rol</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                                <td><?= htmlspecialchars($user['phone']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= htmlspecialchars($user['role_name'] ?? 'User') ?></td>
                                                <td class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_user">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Emin misiniz?')">🗑️</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($action === 'packages'): ?>
                    <div class="section">
                        <h3>📦 Paket Yönetimi</h3>

                        <!-- Add Package Form -->
                        <div class="form-grid">
                            <div class="form-card">
                                <h4>Yeni Paket Ekle</h4>
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_package">
                                    <div class="form-group">
                                        <label>Paket Adı:</label>
                                        <input type="text" name="name" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Açıklama:</label>
                                        <textarea name="description" rows="3"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Fiyat (₺):</label>
                                        <input type="number" name="price" step="0.01" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Süre (Ay):</label>
                                        <input type="number" name="duration_months" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Max İstek (Aylık):</label>
                                        <input type="number" name="max_requests_per_month">
                                    </div>
                                    <button type="submit" class="btn">➕ Paket Ekle</button>
                                </form>
                            </div>

                            <!-- Assign Package Form -->
                            <div class="form-card">
                                <h4>Paket Ata</h4>
                                <form method="POST">
                                    <input type="hidden" name="action" value="assign_package">
                                    <div class="form-group">
                                        <label>Provider ID:</label>
                                        <input type="number" name="provider_id" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Paket ID:</label>
                                        <input type="number" name="package_id" required>
                                    </div>
                                    <button type="submit" class="btn btn-success">🎯 Paket Ata</button>
                                </form>
                            </div>
                        </div>

                        <!-- Packages Table -->
                        <?php if (!empty($data)): ?>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Paket Adı</th>
                                            <th>Fiyat</th>
                                            <th>Süre</th>
                                            <th>Max İstek</th>
                                            <th>Aktif Abonelik</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $package): ?>
                                            <tr>
                                                <td><?= $package['id'] ?></td>
                                                <td><?= htmlspecialchars($package['name']) ?></td>
                                                <td><?= $package['price'] ?> ₺</td>
                                                <td><?= $package['duration_months'] ?> ay</td>
                                                <td><?= $package['max_requests_per_month'] ?? '∞' ?></td>
                                                <td><?= $package['active_subscriptions'] ?></td>
                                                <td class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_package">
                                                        <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Emin misiniz?')">🗑️</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($action === 'news'): ?>
                    <div class="section">
                        <h3>📰 Haber Yönetimi</h3>

                        <!-- Add News Form -->
                        <div class="form-card">
                            <h4>Yeni Haber Ekle</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_news">
                                <div class="form-group">
                                    <label>Başlık:</label>
                                    <input type="text" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label>İçerik:</label>
                                    <textarea name="content" rows="5" required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Özet:</label>
                                    <textarea name="excerpt" rows="2"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Resim URL:</label>
                                    <input type="url" name="image_url">
                                </div>
                                <div class="form-group">
                                    <label>Kategori:</label>
                                    <select name="category">
                                        <option value="general">Genel</option>
                                        <option value="teknoloji">Teknoloji</option>
                                        <option value="sigorta">Sigorta</option>
                                        <option value="bakım">Bakım</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="is_sponsored"> Sponsor Haber</label>
                                </div>
                                <div class="form-group">
                                    <label><input type="checkbox" name="is_featured"> Öne Çıkan</label>
                                </div>
                                <button type="submit" class="btn">➕ Haber Ekle</button>
                            </form>
                        </div>

                        <!-- News Table -->
                        <?php if (!empty($data)): ?>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Başlık</th>
                                            <th>Kategori</th>
                                            <th>Tip</th>
                                            <th>Yazar</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $news): ?>
                                            <tr>
                                                <td><?= $news['id'] ?></td>
                                                <td><?= htmlspecialchars(substr($news['title'], 0, 50)) ?>...</td>
                                                <td><?= htmlspecialchars($news['category']) ?></td>
                                                <td><?= $news['news_type'] ?></td>
                                                <td><?= htmlspecialchars($news['author']) ?></td>
                                                <td class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_news">
                                                        <input type="hidden" name="news_id" value="<?= $news['id'] ?>">
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Emin misiniz?')">🗑️</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($action === 'sliders'): ?>
                    <div class="section">
                        <h3>🖼️ Slider Yönetimi</h3>

                        <!-- Add Slider Form -->
                        <div class="form-card">
                            <h4>Yeni Slider Ekle</h4>
                            <form method="POST">
                                <input type="hidden" name="action" value="create_slider">
                                <div class="form-group">
                                    <label>Başlık:</label>
                                    <input type="text" name="title" required>
                                </div>
                                <div class="form-group">
                                    <label>Açıklama:</label>
                                    <textarea name="description" rows="3"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Resim URL:</label>
                                    <input type="url" name="image_url" required>
                                </div>
                                <div class="form-group">
                                    <label>Link URL:</label>
                                    <input type="url" name="link_url">
                                </div>
                                <div class="form-group">
                                    <label>Sıra:</label>
                                    <input type="number" name="sort_order" value="0">
                                </div>
                                <button type="submit" class="btn">➕ Slider Ekle</button>
                            </form>
                        </div>

                        <!-- Sliders Table -->
                        <?php if (!empty($data)): ?>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Başlık</th>
                                            <th>Açıklama</th>
                                            <th>Sıra</th>
                                            <th>Aktif</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $slider): ?>
                                            <tr>
                                                <td><?= $slider['id'] ?></td>
                                                <td><?= htmlspecialchars($slider['title']) ?></td>
                                                <td><?= htmlspecialchars(substr($slider['description'], 0, 30)) ?>...</td>
                                                <td><?= $slider['sort_order'] ?></td>
                                                <td><?= $slider['is_active'] ? '✅' : '❌' ?></td>
                                                <td class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_slider">
                                                        <input type="hidden" name="slider_id" value="<?= $slider['id'] ?>">
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Emin misiniz?')">🗑️</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($action === 'quotes'): ?>
                    <div class="section">
                        <h3>💬 Teklif Yönetimi</h3>

                        <?php if (!empty($data)): ?>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Başlık</th>
                                            <th>Kullanıcı</th>
                                            <th>Telefon</th>
                                            <th>Araç</th>
                                            <th>Durum</th>
                                            <th>İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $quote): ?>
                                            <tr>
                                                <td><?= $quote['id'] ?></td>
                                                <td><?= htmlspecialchars($quote['title']) ?></td>
                                                <td><?= htmlspecialchars($quote['user_name']) ?></td>
                                                <td><?= htmlspecialchars($quote['user_phone']) ?></td>
                                                <td><?= htmlspecialchars($quote['brand'] . ' ' . $quote['model']) ?></td>
                                                <td><?= htmlspecialchars($quote['status']) ?></td>
                                                <td class="action-buttons">
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="delete_quote_request">
                                                        <input type="hidden" name="quote_request_id" value="<?= $quote['id'] ?>">
                                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Emin misiniz?')">🗑️</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                <?php elseif ($action === 'provider_requests'): ?>
                    <div class="section">
                        <h3>📋 Provider Talepleri</h3>

                        <?php if (!empty($data)): ?>
                            <div class="data-table">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Provider</th>
                                            <th>Tip</th>
                                            <th>Başlık</th>
                                            <th>Durum</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data as $request): ?>
                                            <tr>
                                                <td><?= $request['id'] ?></td>
                                                <td><?= htmlspecialchars($request['provider_name']) ?></td>
                                                <td><?= htmlspecialchars($request['request_type']) ?></td>
                                                <td><?= htmlspecialchars($request['title']) ?></td>
                                                <td><?= htmlspecialchars($request['status']) ?></td>
                                                <td><?= $request['created_at'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p>📭 Henüz provider talebi bulunmuyor.</p>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <div class="section">
                        <h3>📊 Admin Panel Özellikleri</h3>
                        <div class="form-grid">
                            <div class="form-card">
                                <h4>✅ Kullanıcı Yönetimi</h4>
                                <p>• Kullanıcı ekleme/düzenleme/silme<br>• Rol yönetimi (User/Provider/Admin)<br>• Kullanıcı aktivitesi izleme</p>
                            </div>
                            <div class="form-card">
                                <h4>✅ Paket Yönetimi</h4>
                                <p>• Abonelik paketleri oluşturma<br>• Fiyat ve süre belirleme<br>• Provider'lara paket atama</p>
                            </div>
                            <div class="form-card">
                                <h4>✅ İçerik Yönetimi</h4>
                                <p>• Sponsor haber ekleme/düzenleme<br>• Slider yönetimi<br>• Kategori ve etiket sistemi</p>
                            </div>
                            <div class="form-card">
                                <h4>✅ Teklif Sistemi</h4>
                                <p>• Teklif taleplerini görüntüleme<br>• Durum güncelleme<br>• Provider talepleri yönetimi</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>