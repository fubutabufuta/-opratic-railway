<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>B2B Müşteri Yönetimi - Oto Asist Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f6f9;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
        }

        .client-list {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #555;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-active {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge-suspended {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-inactive {
            background: #ffebee;
            color: #d32f2f;
        }

        .api-key {
            font-family: monospace;
            background: #f5f5f5;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.85rem;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="number"],
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .checkbox-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
            margin-bottom: 0;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🏢 B2B Müşteri Yönetimi</h1>
            <p>API erişimi olan B2B müşterilerini yönetin</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" id="totalClients">0</div>
                <div class="stat-label">Toplam Müşteri</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="activeClients">0</div>
                <div class="stat-label">Aktif Müşteri</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="totalNotifications">0</div>
                <div class="stat-label">Gönderilen Bildirim</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" id="monthlyNotifications">0</div>
                <div class="stat-label">Bu Ay Gönderilen</div>
            </div>
        </div>

        <div class="client-list">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h2>B2B Müşteri Listesi</h2>
                <button class="btn btn-primary" onclick="openNewClientModal()">+ Yeni Müşteri</button>
            </div>

            <table id="clientsTable">
                <thead>
                    <tr>
                        <th>Şirket Adı</th>
                        <th>İletişim</th>
                        <th>API Anahtarı</th>
                        <th>Aylık Kota</th>
                        <th>Kullanım</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- JavaScript ile doldurulacak -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Yeni Müşteri Modal -->
    <div id="newClientModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Yeni B2B Müşteri Ekle</h2>
            <form id="newClientForm">
                <div class="form-group">
                    <label for="company_name">Şirket Adı</label>
                    <input type="text" id="company_name" name="company_name" required>
                </div>

                <div class="form-group">
                    <label for="contact_person">İletişim Kişisi</label>
                    <input type="text" id="contact_person" name="contact_person" required>
                </div>

                <div class="form-group">
                    <label for="email">E-posta</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="text" id="phone" name="phone">
                </div>

                <div class="form-group">
                    <label for="client_type">Müşteri Tipi</label>
                    <select id="client_type" name="client_type">
                        <option value="gallery">Galeri</option>
                        <option value="insurance">Sigorta</option>
                        <option value="service">Servis</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="monthly_quota">Aylık Kota</label>
                    <input type="number" id="monthly_quota" name="monthly_quota" value="1000" min="100">
                </div>

                <div class="form-group">
                    <label>İzin Verilen Hedefler</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="allowed_targets" value="all" checked>
                            Tüm Kullanıcılar
                        </label>
                        <label>
                            <input type="checkbox" name="allowed_targets" value="city">
                            Şehir Bazlı
                        </label>
                        <label>
                            <input type="checkbox" name="allowed_targets" value="vehicle_brand">
                            Araç Markası Bazlı
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Müşteri Ekle</button>
            </form>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde verileri yükle
        document.addEventListener('DOMContentLoaded', function() {
            loadClients();
            loadStats();
        });

        // B2B müşterilerini yükle
        async function loadClients() {
            try {
                const response = await fetch('/api/v1/admin/b2b-clients');
                const result = await response.json();

                if (result.success && result.data) {
                    displayClients(result.data);
                }
            } catch (error) {
                console.error('Müşteriler yüklenemedi:', error);
            }
        }

        // İstatistikleri yükle
        async function loadStats() {
            try {
                const response = await fetch('/api/v1/admin/b2b-stats');
                const result = await response.json();

                if (result.success && result.data) {
                    document.getElementById('totalClients').textContent = result.data.total_clients;
                    document.getElementById('activeClients').textContent = result.data.active_clients;
                    document.getElementById('totalNotifications').textContent = result.data.total_notifications;
                    document.getElementById('monthlyNotifications').textContent = result.data.monthly_notifications;
                }
            } catch (error) {
                console.error('İstatistikler yüklenemedi:', error);
            }
        }

        // Müşterileri tabloda göster
        function displayClients(clients) {
            const tbody = document.querySelector('#clientsTable tbody');

            if (clients.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">Henüz B2B müşteri bulunmuyor.</td></tr>';
                return;
            }

            let html = '';
            clients.forEach(client => {
                const usagePercent = Math.round((client.total_notifications_sent / client.monthly_quota) * 100);
                html += `
                    <tr>
                        <td>
                            <strong>${client.company_name}</strong><br>
                            <small class="text-muted">${getClientTypeLabel(client.client_type)}</small>
                        </td>
                        <td>
                            ${client.contact_person}<br>
                            <small>${client.email}</small>
                        </td>
                        <td>
                            <span class="api-key">${client.api_key}</span>
                        </td>
                        <td>${client.monthly_quota.toLocaleString()}</td>
                        <td>
                            ${client.total_notifications_sent.toLocaleString()}<br>
                            <small>${usagePercent}% kullanım</small>
                        </td>
                        <td>
                            <span class="badge badge-${client.status}">${getStatusLabel(client.status)}</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="toggleStatus(${client.id}, '${client.status}')">
                                ${client.status === 'active' ? 'Durdur' : 'Aktifleştir'}
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteClient(${client.id})">Sil</button>
                        </td>
                    </tr>
                `;
            });

            tbody.innerHTML = html;
        }

        // Yeni müşteri modalını aç
        function openNewClientModal() {
            document.getElementById('newClientModal').style.display = 'block';
        }

        // Modalı kapat
        function closeModal() {
            document.getElementById('newClientModal').style.display = 'none';
            document.getElementById('newClientForm').reset();
        }

        // Yeni müşteri formu
        document.getElementById('newClientForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = {};

            // Form verilerini topla
            formData.forEach((value, key) => {
                if (key === 'allowed_targets') {
                    if (!data.allowed_targets) data.allowed_targets = [];
                    data.allowed_targets.push(value);
                } else {
                    data[key] = value;
                }
            });

            try {
                const response = await fetch('/api/v1/admin/b2b-clients', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('B2B müşteri başarıyla eklendi!');
                    closeModal();
                    loadClients();
                    loadStats();
                } else {
                    alert('Hata: ' + result.message);
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        });

        // Durum değiştir
        async function toggleStatus(clientId, currentStatus) {
            const newStatus = currentStatus === 'active' ? 'suspended' : 'active';

            try {
                const response = await fetch(`/api/v1/admin/b2b-clients/${clientId}/status`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        status: newStatus
                    })
                });

                const result = await response.json();

                if (result.success) {
                    loadClients();
                } else {
                    alert('Hata: ' + result.message);
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        }

        // Müşteri sil
        async function deleteClient(clientId) {
            if (!confirm('Bu müşteriyi silmek istediğinizden emin misiniz?')) {
                return;
            }

            try {
                const response = await fetch(`/api/v1/admin/b2b-clients/${clientId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();

                if (result.success) {
                    loadClients();
                    loadStats();
                } else {
                    alert('Hata: ' + result.message);
                }
            } catch (error) {
                alert('Bir hata oluştu: ' + error.message);
            }
        }

        // Yardımcı fonksiyonlar
        function getClientTypeLabel(type) {
            const labels = {
                'gallery': 'Galeri',
                'insurance': 'Sigorta',
                'service': 'Servis',
                'other': 'Diğer'
            };
            return labels[type] || type;
        }

        function getStatusLabel(status) {
            const labels = {
                'active': 'Aktif',
                'suspended': 'Durduruldu',
                'inactive': 'Pasif'
            };
            return labels[status] || status;
        }

        // Modal dışına tıklandığında kapat
        window.onclick = function(event) {
            const modal = document.getElementById('newClientModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>