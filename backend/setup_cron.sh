#!/bin/bash

# Otomatik hatırlatma sistemi için cron job kurulumu
# Bu script'i root olarak çalıştırın

echo "Oto Asist Otomatik Hatırlatma Sistemi Kurulumu"
echo "=============================================="

# PHP path'ini kontrol et
PHP_PATH=$(which php)
if [ -z "$PHP_PATH" ]; then
    echo "❌ PHP bulunamadı. Lütfen PHP'yi kurun."
    exit 1
fi

echo "✓ PHP bulundu: $PHP_PATH"

# Script path'ini al
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
CRON_SCRIPT="$SCRIPT_DIR/cron/check_reminders.php"

# Script'in var olduğunu kontrol et
if [ ! -f "$CRON_SCRIPT" ]; then
    echo "❌ Cron script bulunamadı: $CRON_SCRIPT"
    exit 1
fi

echo "✓ Cron script bulundu: $CRON_SCRIPT"

# Mevcut crontab'ı yedekle
echo "📋 Mevcut crontab yedekleniyor..."
crontab -l > crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || echo "Mevcut crontab bulunamadı"

# Yeni cron job'ı ekle
echo "⏰ Cron job ekleniyor..."

# Her gün saat 09:00'da çalışacak
CRON_JOB="0 9 * * * $PHP_PATH $CRON_SCRIPT >> $SCRIPT_DIR/logs/cron.log 2>&1"

# Log dizinini oluştur
mkdir -p "$SCRIPT_DIR/logs"

# Cron job'ı ekle
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -

echo "✓ Cron job başarıyla eklendi!"
echo ""
echo "Kurulum tamamlandı! Sistem her gün saat 09:00'da otomatik hatırlatmaları kontrol edecek."
echo ""
echo "📋 Eklenen cron job:"
echo "$CRON_JOB"
echo ""
echo "📄 Log dosyası: $SCRIPT_DIR/logs/cron.log"
echo ""
echo "🔧 Manuel test için şu komutu çalıştırabilirsiniz:"
echo "$PHP_PATH $CRON_SCRIPT"
echo ""
echo "📋 Mevcut cron job'ları görmek için: crontab -l"
echo "🗑️ Cron job'ı kaldırmak için: crontab -e" 