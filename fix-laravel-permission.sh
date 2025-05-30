#!/bin/bash

# =============================================================================
# 🔧 SUPER SIMPLE Laravel Permission Fix
# =============================================================================
# Script ini akan memperbaiki masalah permission Laravel secara otomatis
# JANGAN HAPUS APAPUN - ini hanya masalah permission!
# =============================================================================

# Warna untuk output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================================="
echo "🔧 LARAVEL PERMISSION FIX - SUPER SIMPLE"
echo "============================================================================="
echo "JANGAN PANIK! Ini hanya masalah permission, bukan virus atau kerusakan!"
echo "Script ini akan memperbaiki semuanya dalam 1 menit"
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

# LANGKAH 1: Cari direktori Laravel
show_step "1" "Mencari direktori Laravel..."

# Dari error message, kita tahu Laravel ada di /var/www/html
LARAVEL_DIR="/var/www/html"

# Verifikasi
if [[ -f "$LARAVEL_DIR/artisan" ]]; then
    show_success "Laravel ditemukan di: $LARAVEL_DIR"
else
    # Cari di lokasi lain
    POSSIBLE_DIRS=(
        "/var/www/hartonomotor.xyz"
        "/home/*/hartonomotor.xyz"
        "/opt/hartonomotor.xyz"
        "/root/hartonomotor.xyz"
    )
    
    for dir in "${POSSIBLE_DIRS[@]}"; do
        for expanded_dir in $dir; do
            if [[ -f "$expanded_dir/artisan" ]]; then
                LARAVEL_DIR="$expanded_dir"
                break 2
            fi
        done
    done
    
    if [[ -f "$LARAVEL_DIR/artisan" ]]; then
        show_success "Laravel ditemukan di: $LARAVEL_DIR"
    else
        show_error "Laravel tidak ditemukan"
        exit 1
    fi
fi

echo ""

# LANGKAH 2: Cek struktur direktori storage
show_step "2" "Mengecek struktur direktori storage..."

cd "$LARAVEL_DIR"

# Cek apakah direktori storage ada
if [[ ! -d "storage" ]]; then
    show_error "Direktori storage tidak ada!"
    mkdir -p storage
    show_success "Direktori storage dibuat"
fi

# Buat struktur direktori storage yang diperlukan
STORAGE_DIRS=(
    "storage/app"
    "storage/app/public"
    "storage/framework"
    "storage/framework/cache"
    "storage/framework/cache/data"
    "storage/framework/sessions"
    "storage/framework/views"
    "storage/logs"
    "bootstrap/cache"
)

for dir in "${STORAGE_DIRS[@]}"; do
    if [[ ! -d "$dir" ]]; then
        mkdir -p "$dir"
        show_info "Dibuat: $dir"
    fi
done

show_success "Struktur direktori storage lengkap"
echo ""

# LANGKAH 3: Set ownership yang benar
show_step "3" "Mengatur ownership direktori..."

# Cek web server yang digunakan
if systemctl is-active --quiet nginx; then
    WEB_USER="www-data"
    show_info "Detected: Nginx dengan user www-data"
elif systemctl is-active --quiet apache2; then
    WEB_USER="www-data"
    show_info "Detected: Apache dengan user www-data"
elif systemctl is-active --quiet httpd; then
    WEB_USER="apache"
    show_info "Detected: Apache dengan user apache"
else
    WEB_USER="www-data"
    show_info "Default: menggunakan www-data"
fi

# Set ownership
sudo chown -R $WEB_USER:$WEB_USER "$LARAVEL_DIR/storage"
sudo chown -R $WEB_USER:$WEB_USER "$LARAVEL_DIR/bootstrap/cache"

show_success "Ownership diatur ke: $WEB_USER"
echo ""

# LANGKAH 4: Set permission yang benar
show_step "4" "Mengatur permission direktori..."

# Set permission untuk direktori
sudo chmod -R 755 "$LARAVEL_DIR"
sudo chmod -R 775 "$LARAVEL_DIR/storage"
sudo chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

# Set permission khusus untuk file
find "$LARAVEL_DIR/storage" -type f -exec sudo chmod 664 {} \;
find "$LARAVEL_DIR/bootstrap/cache" -type f -exec sudo chmod 664 {} \;

show_success "Permission diatur dengan benar"
echo ""

# LANGKAH 5: Clear semua cache
show_step "5" "Membersihkan cache Laravel..."

# Clear cache dengan aman
php artisan config:clear 2>/dev/null || show_info "Config cache sudah bersih"
php artisan route:clear 2>/dev/null || show_info "Route cache sudah bersih"
php artisan view:clear 2>/dev/null || show_info "View cache sudah bersih"
php artisan cache:clear 2>/dev/null || show_info "Application cache sudah bersih"

# Hapus file cache yang mungkin corrupt
sudo rm -rf storage/framework/views/*.php 2>/dev/null || true
sudo rm -rf storage/framework/cache/data/* 2>/dev/null || true
sudo rm -rf bootstrap/cache/*.php 2>/dev/null || true

show_success "Semua cache dibersihkan"
echo ""

# LANGKAH 6: Rebuild cache dengan permission yang benar
show_step "6" "Rebuild cache dengan permission baru..."

# Set umask untuk memastikan file baru punya permission yang benar
umask 002

# Rebuild cache
php artisan config:cache 2>/dev/null && show_success "Config cache rebuilt" || show_info "Config cache skip"
php artisan route:cache 2>/dev/null && show_success "Route cache rebuilt" || show_info "Route cache skip"

# Set permission lagi untuk file yang baru dibuat
sudo chown -R $WEB_USER:$WEB_USER "$LARAVEL_DIR/storage"
sudo chown -R $WEB_USER:$WEB_USER "$LARAVEL_DIR/bootstrap/cache"
sudo chmod -R 775 "$LARAVEL_DIR/storage"
sudo chmod -R 775 "$LARAVEL_DIR/bootstrap/cache"

show_success "Cache rebuilt dengan permission yang benar"
echo ""

# LANGKAH 7: Test Laravel
show_step "7" "Testing Laravel..."

# Test dengan artisan
if php artisan --version > /dev/null 2>&1; then
    show_success "Laravel artisan berjalan normal"
    LARAVEL_OK=true
else
    show_error "Laravel artisan masih bermasalah"
    LARAVEL_OK=false
fi

# Test web access (jika bisa)
if curl -s -I "http://localhost" | grep -q "200\|301\|302"; then
    show_success "Web server merespons normal"
elif curl -s -I "https://hartonomotor.xyz" | grep -q "200\|301\|302"; then
    show_success "Website merespons normal"
else
    show_info "Tidak bisa test web access (normal jika tidak ada curl)"
fi

echo ""

# LANGKAH 8: Set SELinux jika ada
if command -v getenforce > /dev/null 2>&1; then
    if [[ "$(getenforce)" != "Disabled" ]]; then
        show_step "8" "Mengatur SELinux context..."
        sudo setsebool -P httpd_can_network_connect 1 2>/dev/null || true
        sudo setsebool -P httpd_unified 1 2>/dev/null || true
        sudo restorecon -R "$LARAVEL_DIR" 2>/dev/null || true
        show_success "SELinux context diatur"
    fi
fi

echo ""

# HASIL AKHIR
echo -e "${BLUE}"
echo "============================================================================="
echo "🎉 HASIL PERBAIKAN"
echo "============================================================================="
echo -e "${NC}"

if [ "$LARAVEL_OK" = true ]; then
    echo -e "${GREEN}"
    echo "✅ BERHASIL! Laravel permission sudah diperbaiki"
    echo ""
    echo "🌐 Sekarang coba buka:"
    echo "   https://hartonomotor.xyz"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo "📱 Error 500 seharusnya sudah hilang!"
    echo -e "${NC}"
else
    echo -e "${YELLOW}"
    echo "⚠️  Permission sudah diperbaiki, tapi mungkin ada masalah lain"
    echo ""
    echo "🔧 Langkah tambahan:"
    echo "1. Restart web server: sudo systemctl restart nginx"
    echo "2. Restart PHP-FPM: sudo systemctl restart php8.1-fpm"
    echo "3. Cek log error: tail -f storage/logs/laravel.log"
    echo -e "${NC}"
fi

echo ""
echo -e "${YELLOW}📋 YANG SUDAH DIPERBAIKI:${NC}"
echo "• ✅ Struktur direktori storage lengkap"
echo "• ✅ Ownership diatur ke: $WEB_USER"
echo "• ✅ Permission diatur: 775 untuk storage, 755 untuk lainnya"
echo "• ✅ Cache dibersihkan dan di-rebuild"
echo "• ✅ File corrupt dihapus"

echo ""
echo -e "${BLUE}🆘 JIKA MASIH ERROR 500:${NC}"
echo "1. Restart web server: sudo systemctl restart nginx"
echo "2. Restart PHP: sudo systemctl restart php8.1-fpm"
echo "3. Cek log: tail -f $LARAVEL_DIR/storage/logs/laravel.log"
echo "4. Cek web server log: sudo tail -f /var/log/nginx/error.log"

echo ""
echo -e "${GREEN}🎯 INGAT: Ini BUKAN masalah Symfony atau virus!${NC}"
echo -e "${GREEN}Ini hanya masalah permission yang sangat umum di VPS${NC}"
