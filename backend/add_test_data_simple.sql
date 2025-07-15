-- Basit test verisi ekleme
USE otoasist;

-- Test kullanıcısı ekle
INSERT IGNORE INTO users (phone, password, name, email, is_verified, created_at, updated_at) 
VALUES ('+905551234567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test Kullanıcı', 'test@test.com', 1, NOW(), NOW());

-- Kullanıcı ID'sini al
SET @user_id = (SELECT id FROM users WHERE phone = '+905551234567' LIMIT 1);

-- Test araçları ekle
INSERT IGNORE INTO vehicles (user_id, brand, model, year, plate, color, 
                            last_service_date, last_inspection_date, insurance_expiry_date,
                            kasko_expiry_date, registration_expiry_date, oil_change_date, tire_change_date,
                            created_at, updated_at)
VALUES 
(@user_id, 'BMW', 'X5', '2020', '34 ABC 123', 'Siyah', 
 '2024-01-15', '2024-02-01', '2024-12-31', '2024-12-31', '2024-11-30', '2024-03-01', '2024-04-01',
 NOW(), NOW()),

(@user_id, 'Mercedes', 'C200', '2021', '06 XYZ 789', 'Beyaz', 
 '2024-02-20', '2024-03-15', '2024-10-31', '2024-10-31', '2024-09-30', '2024-04-15', '2024-05-01',
 NOW(), NOW());

-- Araç ID'lerini al
SET @vehicle1_id = (SELECT id FROM vehicles WHERE plate = '34 ABC 123' AND user_id = @user_id LIMIT 1);
SET @vehicle2_id = (SELECT id FROM vehicles WHERE plate = '06 XYZ 789' AND user_id = @user_id LIMIT 1);

-- Test hatırlatmaları ekle
INSERT IGNORE INTO reminders (vehicle_id, title, description, date, reminder_time, type, reminder_days, is_completed, created_at, updated_at)
VALUES 
(@vehicle1_id, 'Yağ değişimi', 'Motor yağı değişimi yapılması gerekiyor', DATE_ADD(CURDATE(), INTERVAL 7 DAY), '09:00:00', 'service', 1, 0, NOW(), NOW()),
(@vehicle1_id, 'Fren kontrolü', 'Fren sistemi kontrol edilmeli', DATE_ADD(CURDATE(), INTERVAL 15 DAY), '09:00:00', 'service', 7, 0, NOW(), NOW()),
(@vehicle2_id, 'Lastik değişimi', 'Lastikler değiştirilmeli', DATE_ADD(CURDATE(), INTERVAL 30 DAY), '09:00:00', 'tire', 30, 0, NOW(), NOW());

-- Test kampanyaları ekle
INSERT IGNORE INTO campaigns (title, description, image_url, start_date, end_date, is_active, created_at, updated_at)
VALUES 
('Yaz Lastik İndirimi', 'Yaz lastiklerinde %20 indirim', '/images/campaigns/summer_tires.jpg', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), 1, NOW(), NOW()),
('Servis Paketi', '3 servis al 1 tanesi bedava', '/images/campaigns/service_package.jpg', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 60 DAY), 1, NOW(), NOW());

SELECT 'Test verisi eklendi!' as message;
SELECT CONCAT('Kullanıcı ID: ', @user_id) as user_info;
SELECT CONCAT('Araç 1 ID: ', @vehicle1_id) as vehicle1_info;
SELECT CONCAT('Araç 2 ID: ', @vehicle2_id) as vehicle2_info; 