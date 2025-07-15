<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bildirim Yönetimi - Oto Asist Admin</title>
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

        .form-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
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
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .target-options {
            display: none;
            margin-top: 1rem;
        }

        .notifications-list {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-item:last-child {
            border-bottom: none;
        }

        .notification-info h3 {
            color: #333;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }

        .notification-meta {
            font-size: 0.9rem;
            color: #666;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .badge-general {
            background: #e3f2fd;
            color: #1976d2;
        }

        .badge-reminder {
            background: #fff3e0;
            color: #f57c00;
        }

        .badge-campaign {
            background: #e8f5e9;
            color: #388e3c;
        }

        .badge-alert {
            background: #ffebee;
            color: #d32f2f;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .success-message,
        .error-message {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: none;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🔔 Bildirim Yönetimi</h1>
            <p>Kullanıcılara bildirim gönderin ve mevcut bildirimleri yönetin</p>
        </div>

        <div class="success-message" id="successMessage"></div>
        <div class="error-message" id="errorMessage"></div>

        <div class="form-container">
            <h2>Yeni Bildirim Gönder</h2>
            <form id="notificationForm">
                <div class="form-group">
                    <label for="title">Bildirim Başlığı</label>
                    <input type="text" id="title" name="title" required placeholder="Örn: Özel Kampanya">
                </div>

                <div class="form-group">
                    <label for="message">Bildirim Mesajı</label>
                    <textarea id="message" name="message" required placeholder="Bildirim içeriğini buraya yazın..."></textarea>
                </div>

                <div class="form-group">
                    <label for="notification_type">Bildirim Türü</label>
                    <select id="notification_type" name="notification_type">
                        <option value="general">Genel Bildirim</option>
                        <option value="reminder">Hatırlatma</option>
                        <option value="campaign">Kampanya</option>
                        <option value="alert">Uyarı</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="target_type">Hedef Kitle</label>
                    <select id="target_type" name="target_type" onchange="toggleTargetOptions()">
                        <option value="all">Tüm Kullanıcılar</option>
                        <option value="city">Şehir Bazlı</option>
                        <option value="vehicle_brand">Araç Markası Bazlı</option>
                        <option value="user">Belirli Kullanıcı</option>
                    </select>
                </div>

                <div class="form-group target-options" id="targetOptions">
                    <label for="target_value">Hedef Değeri</label>
                    <input type="text" id="target_value" name="target_value" placeholder="Örn: İstanbul veya Toyota">
                </div>

                <div class="form-group">
                    <label for="scheduled_at">Zamanlama (Opsiyonel)</label>
                    <input type="datetime-local" id="scheduled_at" name="scheduled_at">
                </div>

                <button type="submit" class="btn btn-primary">Bildirimi Gönder</button>
                <button type="button" class="btn btn-secondary" onclick="resetForm()">Temizle</button>
            </form>
        </div>

        <div class="notifications-list">
            <h2>Son Gönderilen Bildirimler</h2>
            <div id="notificationsList" class="empty-state">
                <p>Henüz bildirim bulunmuyor.</p>
            </div>
        </div>
    </div>

    <script>
        // Sayfa yüklendiğinde bildirimleri yükle
        document.addEventListener('DOMContentLoaded', function() {
            loadNotifications();
        });

        // Hedef seçeneğini göster/gizle
        function toggleTargetOptions() {
            const targetType = document.getElementById('target_type').value;
            const targetOptions = document.getElementById('targetOptions');

            if (targetType === 'all') {
                targetOptions.style.display = 'none';
                document.getElementById('target_value').value = '';
            } else {
                targetOptions.style.display = 'block';
            }
        }

        // Formu temizle
        function resetForm() {
            document.getElementById('notificationForm').reset();
            toggleTargetOptions();
        }

        // Bildirim gönder
        document.getElementById('notificationForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = {
                title: document.getElementById('title').value,
                message: document.getElementById('message').value,
                notification_type: document.getElementById('notification_type').value,
                target_type: document.getElementById('target_type').value,
                target_value: document.getElementById('target_value').value || null,
                scheduled_at: document.getElementById('scheduled_at').value || null
            };

            try {
                const response = await fetch('/api/v1/notifications', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const result = await response.json();

                if (result.success) {
                    showMessage('success', 'Bildirim başarıyla gönderildi!');
                    resetForm();
                    loadNotifications();
                } else {
                    showMessage('error', result.message || 'Bildirim gönderilemedi.');
                }
            } catch (error) {
                showMessage('error', 'Bir hata oluştu: ' + error.message);
            }
        });

        // Bildirimleri yükle
        async function loadNotifications() {
            try {
                const response = await fetch('/api/v1/notifications?limit=10');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    displayNotifications(result.data);
                }
            } catch (error) {
                console.error('Bildirimler yüklenemedi:', error);
            }
        }

        // Bildirimleri göster
        function displayNotifications(notifications) {
            const container = document.getElementById('notificationsList');

            if (notifications.length === 0) {
                container.innerHTML = '<div class="empty-state"><p>Henüz bildirim bulunmuyor.</p></div>';
                return;
            }

            let html = '';
            notifications.forEach(notification => {
                html += `
                    <div class="notification-item">
                        <div class="notification-info">
                            <h3>${notification.title}</h3>
                            <p>${notification.message}</p>
                            <div class="notification-meta">
                                <span class="badge badge-${notification.notification_type}">${getTypeLabel(notification.notification_type)}</span>
                                <span>Hedef: ${getTargetLabel(notification.target_type, notification.target_value)}</span>
                                <span> • ${formatDate(notification.created_at)}</span>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        // Bildirim türü etiketini getir
        function getTypeLabel(type) {
            const labels = {
                'general': 'Genel',
                'reminder': 'Hatırlatma',
                'campaign': 'Kampanya',
                'alert': 'Uyarı'
            };
            return labels[type] || type;
        }

        // Hedef etiketi getir
        function getTargetLabel(type, value) {
            if (type === 'all') return 'Tüm Kullanıcılar';
            if (type === 'city') return `Şehir: ${value}`;
            if (type === 'vehicle_brand') return `Marka: ${value}`;
            if (type === 'user') return `Kullanıcı ID: ${value}`;
            return type;
        }

        // Tarih formatla
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('tr-TR') + ' ' + date.toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Mesaj göster
        function showMessage(type, message) {
            const messageEl = type === 'success' ? document.getElementById('successMessage') : document.getElementById('errorMessage');
            messageEl.textContent = message;
            messageEl.style.display = 'block';

            setTimeout(() => {
                messageEl.style.display = 'none';
            }, 5000);
        }
    </script>
</body>

</html>