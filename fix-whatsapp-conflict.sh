#!/bin/bash

# =============================================================================
# 🔧 Fix WhatsApp Container Conflict
# =============================================================================
# Script untuk mengatasi konflik 2 container WhatsApp API
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================================="
echo "🔧 FIX WHATSAPP CONTAINER CONFLICT"
echo "============================================================================="
echo "Mengatasi konflik 2 container WhatsApp API yang berjalan bersamaan"
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

# LANGKAH 1: Analisis container yang berjalan
show_step "1" "Menganalisis container WhatsApp yang berjalan..."

echo "Container WhatsApp yang ditemukan:"
docker ps -a | grep whatsapp | while read line; do
    echo "  $line"
done

echo ""

# Identifikasi container
PRODUCTION_CONTAINER=$(docker ps --format "{{.Names}}" | grep "whatsapp-api-production" | head -1)
OLD_CONTAINER=$(docker ps --format "{{.Names}}" | grep -E "(hartono-whatsapp|hm-new)" | head -1)

if [[ -n "$PRODUCTION_CONTAINER" ]]; then
    show_success "Production container ditemukan: $PRODUCTION_CONTAINER"
else
    show_error "Production container tidak ditemukan"
fi

if [[ -n "$OLD_CONTAINER" ]]; then
    show_info "Container lama ditemukan: $OLD_CONTAINER"
else
    show_info "Tidak ada container lama yang konflik"
fi

echo ""

# LANGKAH 2: Stop container yang konflik
if [[ -n "$OLD_CONTAINER" ]]; then
    show_step "2" "Menghentikan container lama yang konflik..."
    
    docker stop "$OLD_CONTAINER"
    show_success "Container $OLD_CONTAINER dihentikan"
    
    # Tanya apakah ingin hapus permanent
    echo ""
    read -p "Hapus container lama permanent? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker rm "$OLD_CONTAINER"
        show_success "Container $OLD_CONTAINER dihapus permanent"
    else
        show_info "Container $OLD_CONTAINER hanya dihentikan (tidak dihapus)"
    fi
else
    show_step "2" "Tidak ada container konflik - SKIP"
fi

echo ""

# LANGKAH 3: Pastikan production container berjalan
show_step "3" "Memastikan production container berjalan..."

if [[ -n "$PRODUCTION_CONTAINER" ]]; then
    # Cek apakah berjalan
    if docker ps | grep -q "$PRODUCTION_CONTAINER"; then
        show_success "Production container sudah berjalan"
    else
        show_info "Starting production container..."
        docker start "$PRODUCTION_CONTAINER"
        sleep 5
        
        if docker ps | grep -q "$PRODUCTION_CONTAINER"; then
            show_success "Production container berhasil dijalankan"
        else
            show_error "Gagal menjalankan production container"
        fi
    fi
else
    show_error "Production container tidak ada"
    echo ""
    echo -e "${YELLOW}🔧 SOLUSI:${NC}"
    echo "1. Jalankan deployment production: ./deploy-whatsapp-production.sh"
    echo "2. Atau start manual: /opt/whatsapp-api-production/start.sh"
    exit 1
fi

echo ""

# LANGKAH 4: Test API connection
show_step "4" "Testing koneksi API..."

# Test beberapa URL
API_URLS=("http://localhost:3000" "http://127.0.0.1:3000")
WORKING_URL=""

for url in "${API_URLS[@]}"; do
    if curl -s -f "$url/app/devices" > /dev/null 2>&1; then
        WORKING_URL="$url"
        show_success "API merespons di: $url"
        break
    fi
done

if [[ -z "$WORKING_URL" ]]; then
    show_error "API tidak merespons di localhost:3000"
    echo ""
    echo "🔍 Debugging info:"
    docker logs "$PRODUCTION_CONTAINER" --tail 10
    exit 1
fi

echo ""

# LANGKAH 5: Update Laravel configuration
show_step "5" "Memperbarui konfigurasi Laravel..."

# Cari direktori Laravel
LARAVEL_DIRS=("/var/www/html" "/hm-new" "/var/www/hartonomotor.xyz")
LARAVEL_DIR=""

for dir in "${LARAVEL_DIRS[@]}"; do
    if [[ -f "$dir/.env" ]] && [[ -f "$dir/artisan" ]]; then
        LARAVEL_DIR="$dir"
        break
    fi
done

if [[ -z "$LARAVEL_DIR" ]]; then
    show_error "Laravel directory tidak ditemukan"
    echo "Cari manual dengan: find / -name 'artisan' -type f 2>/dev/null"
    exit 1
fi

show_success "Laravel ditemukan di: $LARAVEL_DIR"

# Backup .env
cp "$LARAVEL_DIR/.env" "$LARAVEL_DIR/.env.backup.$(date +%Y%m%d_%H%M%S)"
show_info "Backup .env dibuat"

# Update .env
cd "$LARAVEL_DIR"

# Update atau tambah WHATSAPP_API_URL
if grep -q "WHATSAPP_API_URL=" .env; then
    sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=$WORKING_URL|" .env
    show_success "WHATSAPP_API_URL diperbarui"
else
    echo "" >> .env
    echo "# WhatsApp API Configuration" >> .env
    echo "WHATSAPP_API_URL=$WORKING_URL" >> .env
    show_success "WHATSAPP_API_URL ditambahkan"
fi

# Tambah konfigurasi basic auth jika belum ada
if ! grep -q "WHATSAPP_BASIC_AUTH_USERNAME=" .env; then
    echo "WHATSAPP_BASIC_AUTH_USERNAME=admin" >> .env
    echo "WHATSAPP_BASIC_AUTH_PASSWORD=" >> .env
    show_info "Basic auth config ditambahkan"
fi

echo ""

# LANGKAH 6: Clear cache Laravel
show_step "6" "Membersihkan cache Laravel..."

php artisan config:clear > /dev/null 2>&1
php artisan config:cache > /dev/null 2>&1
show_success "Laravel cache dibersihkan dan di-rebuild"

echo ""

# LANGKAH 7: Test final connection
show_step "7" "Testing koneksi final Laravel → WhatsApp API..."

# Test dari Laravel
TEST_RESULT=$(php artisan tinker --execute="
try {
    \$response = \Illuminate\Support\Facades\Http::timeout(10)->get(config('whatsapp.api_url', '$WORKING_URL') . '/app/devices');
    echo \$response->successful() ? 'SUCCESS' : 'FAILED';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>/dev/null)

if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
    show_success "Laravel berhasil terhubung ke WhatsApp API"
    CONNECTION_OK=true
else
    show_error "Laravel gagal terhubung: $TEST_RESULT"
    CONNECTION_OK=false
fi

echo ""

# HASIL AKHIR
echo -e "${BLUE}"
echo "============================================================================="
echo "🎉 HASIL PERBAIKAN KONFLIK CONTAINER"
echo "============================================================================="
echo -e "${NC}"

if [ "$CONNECTION_OK" = true ]; then
    echo -e "${GREEN}"
    echo "✅ BERHASIL! Konflik container sudah diatasi"
    echo ""
    echo "📊 Status Container:"
    echo "  • Production: $PRODUCTION_CONTAINER (AKTIF)"
    if [[ -n "$OLD_CONTAINER" ]]; then
        echo "  • Old Container: $OLD_CONTAINER (DIHENTIKAN)"
    fi
    echo ""
    echo "🌐 Sekarang coba akses:"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo "📱 Generate Fresh QR Code seharusnya berhasil!"
    echo -e "${NC}"
else
    echo -e "${YELLOW}"
    echo "⚠️  Container conflict sudah diatasi, tapi masih ada masalah koneksi"
    echo ""
    echo "🔧 Langkah tambahan:"
    echo "1. Restart production container: docker restart $PRODUCTION_CONTAINER"
    echo "2. Cek logs: docker logs $PRODUCTION_CONTAINER"
    echo "3. Test manual: curl $WORKING_URL/app/devices"
    echo -e "${NC}"
fi

echo ""
echo -e "${YELLOW}📋 RINGKASAN YANG DILAKUKAN:${NC}"
echo "• ✅ Analisis container conflict"
if [[ -n "$OLD_CONTAINER" ]]; then
    echo "• ✅ Menghentikan container konflik: $OLD_CONTAINER"
fi
echo "• ✅ Memastikan production container aktif: $PRODUCTION_CONTAINER"
echo "• ✅ Update Laravel config: WHATSAPP_API_URL=$WORKING_URL"
echo "• ✅ Clear Laravel cache"
echo "• ✅ Test koneksi Laravel → WhatsApp API"

echo ""
echo -e "${BLUE}🎯 CONTAINER YANG SEHARUSNYA AKTIF:${NC}"
echo "HANYA: whatsapp-api-production (dengan port 3000)"
echo ""
echo -e "${GREEN}Konflik container sudah diatasi! 🎉${NC}"
