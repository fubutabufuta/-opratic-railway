@echo off
echo Oto Asist Windows Task Scheduler Kurulumu
echo ==========================================

REM PHP path'ini kontrol et
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ PHP bulunamadı. Lütfen PHP'yi PATH'e ekleyin.
    pause
    exit /b 1
)

echo ✓ PHP bulundu

REM Script path'ini al
set SCRIPT_DIR=%~dp0
set CRON_SCRIPT=%SCRIPT_DIR%cron\check_reminders.php

REM Script'in var olduğunu kontrol et
if not exist "%CRON_SCRIPT%" (
    echo ❌ Cron script bulunamadı: %CRON_SCRIPT%
    pause
    exit /b 1
)

echo ✓ Cron script bulundu: %CRON_SCRIPT%

REM Log dizinini oluştur
if not exist "%SCRIPT_DIR%logs" mkdir "%SCRIPT_DIR%logs"

REM Task Scheduler ile günlük task oluştur
echo ⏰ Windows Task Scheduler'da görev oluşturuluyor...

schtasks /create /tn "OtoAsistReminders" /tr "php \"%CRON_SCRIPT%\"" /sc daily /st 09:00 /f

if %errorlevel% equ 0 (
    echo ✓ Task başarıyla oluşturuldu!
    echo.
    echo 📋 Oluşturulan görev: OtoAsistReminders
    echo ⏰ Çalışma zamanı: Her gün 09:00
    echo 📄 Script: %CRON_SCRIPT%
    echo.
    echo 🔧 Manuel test için:
    echo php "%CRON_SCRIPT%"
    echo.
    echo 📋 Görevleri görmek için: schtasks /query /tn "OtoAsistReminders"
    echo 🗑️ Görevi silmek için: schtasks /delete /tn "OtoAsistReminders" /f
) else (
    echo ❌ Task oluşturulamadı. Yönetici olarak çalıştırın.
)

echo.
pause 