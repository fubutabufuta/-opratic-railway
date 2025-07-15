-- Aynı araç için aynı tarih ve aynı saatte sadece 1 hatırlatıcı olması için unique constraint
ALTER TABLE reminders
ADD CONSTRAINT unique_vehicle_date_time UNIQUE (
    vehicle_id,
    date,
    reminder_time
);

-- Mevcut duplicate kayıtları temizlemek için (isteğe bağlı)
-- DELETE r1 FROM reminders r1
-- INNER JOIN reminders r2
-- WHERE r1.id > r2.id
-- AND r1.vehicle_id = r2.vehicle_id
-- AND r1.date = r2.date
-- AND r1.reminder_time = r2.reminder_time;