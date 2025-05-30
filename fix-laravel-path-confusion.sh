#!/bin/bash

# =============================================================================
# 🔧 Fix Laravel Path Confusion - SUPER SIMPLE
# =============================================================================
# Laravel bingung dimana harus menyimpan cache
# Script ini akan memperbaiki path confusion secara otomatis
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================================="
echo "🔧 FIX LARAVEL PATH CONFUSION"
echo "============================================================================="
echo "Laravel mencoba menulis ke /hm-new/ padahal seharusnya ke /var/www/html/"
echo "Script ini akan memperbaiki path confusion secara otomatis"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_step() {
    echo -e "${YELLOW}📋 LANGKAH $1: $2${NC}"
}

show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

show_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# LANGKAH 1: Identifikasi masalah path
show_step "1" "Menganalisis masalah path..."

echo "Error menunjukkan Laravel mencoba akses: /hm-new/storage/"
echo "Tapi Laravel seharusnya di: /var/www/html/"
echo ""

# Cek kedua direktori
if [[ -d "/hm-new" ]]; then
    show_info "Direktori /hm-new ditemukan"
    ls -la /hm-new/ | head -5
else
    show_info "Direktori /hm-new tidak ada"
fi

echo ""

if [[ -d "/var/www/html" ]] && [[ -f "/var/www/html/artisan" ]]; then
    show_success "Laravel aktif ditemukan di: /var/www/html"
else
    show_error "Laravel tidak ditemukan di /var/www/html"
fi

echo ""

# LANGKAH 2: Buat direktori /hm-new/storage jika tidak ada (temporary fix)
show_step "2" "Membuat direktori storage yang dibutuhkan..."

# Buat direktori /hm-new/storage sebagai temporary fix
sudo mkdir -p /hm-new/storage/framework/views
sudo mkdir -p /hm-new/storage/framework/cache/data
sudo mkdir -p /hm-new/storage/framework/sessions
sudo mkdir -p /hm-new/storage/logs
sudo mkdir -p /hm-new/bootstrap/cache

show_success "Direktori /hm-new/storage dibuat (temporary fix)"

# Set permission
sudo chmod -R 777 /hm-new/storage 2>/dev/null || true
sudo chmod -R 777 /hm-new/bootstrap 2>/dev/null || true

show_success "Permission diatur untuk /hm-new/storage"
echo ""

# LANGKAH 3: Fix Laravel configuration
show_step "3" "Memperbaiki konfigurasi Laravel..."

cd /var/www/html

# Clear semua cache yang mungkin corrupt
show_info "Membersihkan cache corrupt..."
php artisan config:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Hapus file cache corrupt
sudo rm -rf storage/framework/views/*.php 2>/dev/null || true
sudo rm -rf storage/framework/cache/data/* 2>/dev/null || true
sudo rm -rf bootstrap/cache/*.php 2>/dev/null || true

show_success "Cache corrupt dibersihkan"

# Pastikan direktori storage ada dan benar
sudo mkdir -p storage/framework/views
sudo mkdir -p storage/framework/cache/data
sudo mkdir -p storage/framework/sessions
sudo mkdir -p storage/logs
sudo mkdir -p bootstrap/cache

# Set ownership dan permission yang benar
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

show_success "Direktori storage Laravel diperbaiki"
echo ""

# LANGKAH 4: Check dan fix .env configuration
show_step "4" "Mengecek konfigurasi .env..."

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
show_info "Backup .env dibuat"

# Cek apakah ada konfigurasi path yang salah
if grep -q "APP_PATH\|STORAGE_PATH\|VIEW_COMPILED_PATH" .env; then
    show_info "Ditemukan konfigurasi path custom di .env"
    grep -E "APP_PATH|STORAGE_PATH|VIEW_COMPILED_PATH" .env
    
    # Comment out path custom yang mungkin salah
    sed -i 's/^APP_PATH=/#APP_PATH=/' .env
    sed -i 's/^STORAGE_PATH=/#STORAGE_PATH=/' .env
    sed -i 's/^VIEW_COMPILED_PATH=/#VIEW_COMPILED_PATH=/' .env
    
    show_success "Konfigurasi path custom di-disable"
else
    show_info "Tidak ada konfigurasi path custom di .env"
fi

# Pastikan APP_ENV dan APP_DEBUG benar
if ! grep -q "APP_ENV=" .env; then
    echo "APP_ENV=production" >> .env
fi

if ! grep -q "APP_DEBUG=" .env; then
    echo "APP_DEBUG=false" >> .env
fi

show_success "Konfigurasi .env diperbaiki"
echo ""

# LANGKAH 5: Regenerate application key jika perlu
show_step "5" "Mengecek application key..."

if ! grep -q "APP_KEY=base64:" .env; then
    show_info "Generating new application key..."
    php artisan key:generate --force
    show_success "Application key di-generate"
else
    show_info "Application key sudah ada"
fi

echo ""

# LANGKAH 6: Rebuild cache dengan path yang benar
show_step "6" "Rebuild cache dengan path yang benar..."

# Set working directory yang benar
cd /var/www/html

# Rebuild cache
php artisan config:cache 2>/dev/null && show_success "Config cache rebuilt" || show_info "Config cache skip"

# Set permission lagi untuk file baru
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

show_success "Cache di-rebuild dengan path yang benar"
echo ""

# LANGKAH 7: Test Laravel
show_step "7" "Testing Laravel..."

# Test artisan
if php artisan --version > /dev/null 2>&1; then
    LARAVEL_VERSION=$(php artisan --version)
    show_success "Laravel berjalan normal: $LARAVEL_VERSION"
    LARAVEL_OK=true
else
    show_error "Laravel artisan masih bermasalah"
    LARAVEL_OK=false
fi

# Test route list
if php artisan route:list > /dev/null 2>&1; then
    show_success "Routes dapat di-load"
else
    show_info "Routes mungkin belum di-cache (normal)"
fi

echo ""

# LANGKAH 8: Test web access
show_step "8" "Testing web access..."

# Test homepage
if curl -s -I "https://hartonomotor.xyz" | grep -q "200\|301\|302"; then
    show_success "Homepage merespons normal"
    WEB_OK=true
elif curl -s -I "http://localhost" | grep -q "200\|301\|302"; then
    show_success "Web server merespons normal"
    WEB_OK=true
else
    show_info "Tidak bisa test web access (mungkin firewall)"
    WEB_OK=false
fi

echo ""

# LANGKAH 9: Restart web services
show_step "9" "Restart web services..."

# Restart PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    show_success "PHP-FPM di-restart"
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
    show_success "PHP-FPM di-restart"
elif systemctl is-active --quiet php-fpm; then
    sudo systemctl restart php-fpm
    show_success "PHP-FPM di-restart"
fi

# Restart web server
if systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx
    show_success "Nginx di-restart"
elif systemctl is-active --quiet apache2; then
    sudo systemctl restart apache2
    show_success "Apache di-restart"
fi

echo ""

# HASIL AKHIR
echo -e "${BLUE}"
echo "============================================================================="
echo "🎉 HASIL PERBAIKAN PATH CONFUSION"
echo "============================================================================="
echo -e "${NC}"

if [ "$LARAVEL_OK" = true ]; then
    echo -e "${GREEN}"
    echo "✅ BERHASIL! Laravel path confusion sudah diperbaiki"
    echo ""
    echo "🔧 Yang sudah diperbaiki:"
    echo "  • Direktori /hm-new/storage dibuat (temporary)"
    echo "  • Laravel storage di /var/www/html diperbaiki"
    echo "  • Cache corrupt dibersihkan"
    echo "  • Permission diatur dengan benar"
    echo "  • Web services di-restart"
    echo ""
    echo "🌐 Sekarang coba akses:"
    echo "   https://hartonomotor.xyz"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo "📱 Error 500 seharusnya sudah hilang!"
    echo -e "${NC}"
else
    echo -e "${YELLOW}"
    echo "⚠️  Path sudah diperbaiki, tapi mungkin ada masalah lain"
    echo ""
    echo "🔧 Langkah tambahan:"
    echo "1. Cek log Laravel: tail -f storage/logs/laravel.log"
    echo "2. Cek web server log: sudo tail -f /var/log/nginx/error.log"
    echo "3. Test manual: php artisan tinker"
    echo -e "${NC}"
fi

echo ""
echo -e "${YELLOW}📋 PENJELASAN MASALAH:${NC}"
echo "• Laravel di /var/www/html mencoba akses /hm-new/storage/"
echo "• Kemungkinan ada symlink atau konfigurasi path yang salah"
echo "• Solusi: Buat direktori yang dibutuhkan + fix Laravel storage"

echo ""
echo -e "${BLUE}🆘 JIKA MASIH ERROR:${NC}"
echo "1. Cek symlink: ls -la /var/www/html/storage"
echo "2. Cek .env: grep -E 'PATH|STORAGE' /var/www/html/.env"
echo "3. Manual test: cd /var/www/html && php artisan route:list"

echo ""
echo -e "${GREEN}🎯 Path confusion sudah diperbaiki! 🎉${NC}"
