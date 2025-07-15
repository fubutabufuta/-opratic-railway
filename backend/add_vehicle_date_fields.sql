-- Vehicles tablosuna eksik tarih alanlarını ekle
ALTER TABLE vehicles
ADD COLUMN IF NOT EXISTS kasko_expiry_date DATE NULL AFTER insurance_expiry_date;

-- Mevcut alanların varlığını kontrol et ve eksikleri ekle
ALTER TABLE vehicles
ADD COLUMN IF NOT EXISTS last_service_date DATE NULL,
ADD COLUMN IF NOT EXISTS last_inspection_date DATE NULL,
ADD COLUMN IF NOT EXISTS insurance_expiry_date DATE NULL,
ADD COLUMN IF NOT EXISTS kasko_expiry_date DATE NULL,
ADD COLUMN IF NOT EXISTS registration_expiry_date DATE NULL,
ADD COLUMN IF NOT EXISTS oil_change_date DATE NULL,
ADD COLUMN IF NOT EXISTS tire_change_date DATE NULL;

-- Veritabanı yapısını göster
DESCRIBE vehicles;