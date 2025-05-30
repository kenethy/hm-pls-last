#!/bin/bash

# =============================================================================
# 🔧 SUPER SIMPLE WhatsApp Fix - Untuk Pemula
# =============================================================================
# Script ini akan memperbaiki koneksi WhatsApp API secara otomatis
# Anda tinggal jalankan dan ikuti instruksi
# =============================================================================

# Warna untuk output yang mudah dibaca
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}"
echo "============================================================================="
echo "🔧 SUPER SIMPLE WhatsApp API Fix"
echo "============================================================================="
echo "Script ini akan memperbaiki masalah koneksi WhatsApp API secara otomatis"
echo "============================================================================="
echo -e "${NC}"

# Fungsi untuk menampilkan pesan
show_step() {
    echo -e "${YELLOW}📋 LANGKAH $1: $2${NC}"
    echo ""
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

# LANGKAH 1: Cek apakah WhatsApp API berjalan
show_step "1" "Mengecek status WhatsApp API..."

if docker ps | grep -q "whatsapp-api"; then
    CONTAINER_NAME=$(docker ps --format "{{.Names}}" | grep whatsapp-api | head -1)
    show_success "WhatsApp API ditemukan: $CONTAINER_NAME"
    
    # Test koneksi
    if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
        show_success "WhatsApp API berjalan dengan baik"
        API_WORKING=true
    else
        show_error "WhatsApp API tidak merespons"
        API_WORKING=false
    fi
else
    show_error "WhatsApp API tidak berjalan"
    API_WORKING=false
fi

echo ""

# LANGKAH 2: Start WhatsApp API jika belum berjalan
if [ "$API_WORKING" != true ]; then
    show_step "2" "Menjalankan WhatsApp API..."
    
    # Coba start production container
    if [[ -f "/opt/whatsapp-api-production/start.sh" ]]; then
        show_info "Menjalankan production WhatsApp API..."
        /opt/whatsapp-api-production/start.sh
        sleep 10
        
        if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
            show_success "Production WhatsApp API berhasil dijalankan"
            API_WORKING=true
        fi
    fi
    
    # Jika masih gagal, coba local container
    if [ "$API_WORKING" != true ]; then
        show_info "Mencoba menjalankan local WhatsApp API..."
        if docker ps -a | grep -q "whatsapp-api-local"; then
            docker start whatsapp-api-local
            sleep 10
            
            if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
                show_success "Local WhatsApp API berhasil dijalankan"
                API_WORKING=true
            fi
        fi
    fi
    
    # Jika masih gagal
    if [ "$API_WORKING" != true ]; then
        show_error "Tidak bisa menjalankan WhatsApp API"
        echo ""
        echo -e "${RED}🆘 SOLUSI MANUAL:${NC}"
        echo "1. Jalankan: docker ps -a"
        echo "2. Cari container dengan nama 'whatsapp'"
        echo "3. Jalankan: docker start [nama-container]"
        echo "4. Jalankan script ini lagi"
        exit 1
    fi
else
    show_step "2" "WhatsApp API sudah berjalan - SKIP"
fi

echo ""

# LANGKAH 3: Cari direktori Laravel
show_step "3" "Mencari direktori Laravel..."

# Daftar lokasi umum Laravel
POSSIBLE_DIRS=(
    "/var/www/html"
    "/var/www/hartonomotor.xyz"
    "/home/*/hartonomotor.xyz"
    "/opt/hartonomotor.xyz"
    "/root/hartonomotor.xyz"
    "$(pwd)"
)

LARAVEL_DIR=""
for dir in "${POSSIBLE_DIRS[@]}"; do
    # Expand wildcard
    for expanded_dir in $dir; do
        if [[ -f "$expanded_dir/.env" ]] && [[ -f "$expanded_dir/artisan" ]]; then
            LARAVEL_DIR="$expanded_dir"
            break 2
        fi
    done
done

if [[ -n "$LARAVEL_DIR" ]]; then
    show_success "Laravel ditemukan di: $LARAVEL_DIR"
else
    show_error "Direktori Laravel tidak ditemukan"
    echo ""
    echo -e "${YELLOW}🔍 BANTUAN MENCARI LARAVEL:${NC}"
    echo "Jalankan perintah ini untuk mencari Laravel:"
    echo "find / -name 'artisan' -type f 2>/dev/null | head -5"
    echo ""
    read -p "Masukkan path lengkap ke direktori Laravel: " LARAVEL_DIR
    
    if [[ ! -f "$LARAVEL_DIR/.env" ]] || [[ ! -f "$LARAVEL_DIR/artisan" ]]; then
        show_error "Path yang dimasukkan tidak valid"
        exit 1
    fi
fi

echo ""

# LANGKAH 4: Backup dan update .env
show_step "4" "Memperbarui konfigurasi Laravel..."

# Backup .env
BACKUP_FILE="$LARAVEL_DIR/.env.backup.$(date +%Y%m%d_%H%M%S)"
cp "$LARAVEL_DIR/.env" "$BACKUP_FILE"
show_success "Backup .env dibuat: $BACKUP_FILE"

# Tentukan URL API yang benar
API_URL="http://localhost:3000"
if curl -s -f http://127.0.0.1:3000/app/devices > /dev/null 2>&1; then
    API_URL="http://127.0.0.1:3000"
fi

# Update atau tambah WHATSAPP_API_URL
if grep -q "WHATSAPP_API_URL=" "$LARAVEL_DIR/.env"; then
    # Update existing
    sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=$API_URL|" "$LARAVEL_DIR/.env"
    show_success "WHATSAPP_API_URL diperbarui ke: $API_URL"
else
    # Add new
    echo "" >> "$LARAVEL_DIR/.env"
    echo "# WhatsApp API Configuration" >> "$LARAVEL_DIR/.env"
    echo "WHATSAPP_API_URL=$API_URL" >> "$LARAVEL_DIR/.env"
    show_success "WHATSAPP_API_URL ditambahkan: $API_URL"
fi

# Tambahkan konfigurasi lain jika belum ada
if ! grep -q "WHATSAPP_BASIC_AUTH_USERNAME=" "$LARAVEL_DIR/.env"; then
    echo "WHATSAPP_BASIC_AUTH_USERNAME=admin" >> "$LARAVEL_DIR/.env"
    echo "WHATSAPP_BASIC_AUTH_PASSWORD=" >> "$LARAVEL_DIR/.env"
    show_info "Konfigurasi basic auth ditambahkan (password kosong untuk sementara)"
fi

echo ""

# LANGKAH 5: Clear cache Laravel
show_step "5" "Membersihkan cache Laravel..."

cd "$LARAVEL_DIR"
php artisan config:clear > /dev/null 2>&1
show_success "Config cache dibersihkan"

php artisan config:cache > /dev/null 2>&1
show_success "Config cache dibuat ulang"

echo ""

# LANGKAH 6: Test koneksi final
show_step "6" "Testing koneksi final..."

# Test API langsung
if curl -s -f "$API_URL/app/devices" > /dev/null 2>&1; then
    show_success "WhatsApp API merespons dengan baik"
    
    # Test dari Laravel
    TEST_RESULT=$(php artisan tinker --execute="
        try {
            \$response = \Illuminate\Support\Facades\Http::timeout(5)->get(config('whatsapp.api_url', '$API_URL') . '/app/devices');
            echo \$response->successful() ? 'SUCCESS' : 'FAILED';
        } catch (Exception \$e) {
            echo 'ERROR: ' . \$e->getMessage();
        }
    " 2>/dev/null)
    
    if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
        show_success "Laravel berhasil terhubung ke WhatsApp API"
        CONNECTION_OK=true
    else
        show_error "Laravel tidak bisa terhubung: $TEST_RESULT"
        CONNECTION_OK=false
    fi
else
    show_error "WhatsApp API tidak merespons"
    CONNECTION_OK=false
fi

echo ""

# HASIL AKHIR
echo -e "${BLUE}"
echo "============================================================================="
echo "🎉 HASIL PERBAIKAN"
echo "============================================================================="
echo -e "${NC}"

if [ "$CONNECTION_OK" = true ]; then
    echo -e "${GREEN}"
    echo "✅ BERHASIL! WhatsApp API sudah terhubung dengan Laravel"
    echo ""
    echo "🌐 Sekarang coba buka:"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo "📱 Dan klik 'Generate Fresh QR Code'"
    echo -e "${NC}"
else
    echo -e "${RED}"
    echo "❌ MASIH ADA MASALAH"
    echo ""
    echo "🔧 Langkah manual yang perlu dilakukan:"
    echo "1. Pastikan WhatsApp API berjalan: docker ps | grep whatsapp"
    echo "2. Test manual: curl $API_URL/app/devices"
    echo "3. Jika masih error, restart container: docker restart [container-name]"
    echo -e "${NC}"
fi

echo ""
echo -e "${YELLOW}📋 INFORMASI PENTING:${NC}"
echo "• Backup .env disimpan di: $BACKUP_FILE"
echo "• Laravel directory: $LARAVEL_DIR"
echo "• WhatsApp API URL: $API_URL"
echo "• Jika ada masalah, restore backup: cp $BACKUP_FILE $LARAVEL_DIR/.env"

echo ""
echo -e "${BLUE}🆘 JIKA MASIH BERMASALAH:${NC}"
echo "1. Screenshot error yang muncul"
echo "2. Jalankan: docker logs [container-name]"
echo "3. Jalankan: tail -f $LARAVEL_DIR/storage/logs/laravel.log"
