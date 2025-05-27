#!/bin/bash

# Script untuk memperbaiki kolom icon di spare_part_categories
# Mengubah dari VARCHAR(255) ke TEXT untuk menampung SVG yang panjang

# Warna untuk output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Memperbaiki Kolom Icon untuk Spare Part Categories${NC}\n"

# Cek apakah Docker container berjalan
CONTAINER_NAME="hartono-app"

if ! docker ps | grep -q $CONTAINER_NAME; then
    echo -e "${RED}❌ Error: Container $CONTAINER_NAME tidak berjalan!${NC}"
    echo -e "${YELLOW}💡 Jalankan: docker-compose up -d${NC}"
    exit 1
fi

echo -e "${YELLOW}📦 Menggunakan container: $CONTAINER_NAME${NC}"

# Backup database terlebih dahulu
echo -e "${YELLOW}💾 Membuat backup database...${NC}"
docker exec $CONTAINER_NAME php artisan tinker --execute="
    \$backupFile = 'spare_part_categories_backup_' . date('Y_m_d_H_i_s') . '.sql';
    \$command = 'mysqldump -h mysql -u root -proot hartono_motor spare_part_categories > /tmp/' . \$backupFile;
    exec(\$command);
    echo 'Backup saved to: /tmp/' . \$backupFile . PHP_EOL;
"

# Cek struktur kolom saat ini
echo -e "${YELLOW}🔍 Memeriksa struktur kolom icon saat ini...${NC}"
docker exec $CONTAINER_NAME php artisan tinker --execute="
    \$columns = DB::select('DESCRIBE spare_part_categories');
    foreach (\$columns as \$column) {
        if (\$column->Field === 'icon') {
            echo 'Kolom icon saat ini: ' . \$column->Type . PHP_EOL;
            echo 'Null: ' . \$column->Null . PHP_EOL;
            echo 'Default: ' . \$column->Default . PHP_EOL;
            break;
        }
    }
"

# Jalankan migration
echo -e "${YELLOW}🔄 Menjalankan migration untuk memperbesar kolom icon...${NC}"
docker exec $CONTAINER_NAME php artisan migrate --path=database/migrations/2025_05_27_140500_modify_spare_part_categories_icon_column.php --force

# Verifikasi perubahan
echo -e "${YELLOW}✅ Memverifikasi perubahan...${NC}"
docker exec $CONTAINER_NAME php artisan tinker --execute="
    \$columns = DB::select('DESCRIBE spare_part_categories');
    foreach (\$columns as \$column) {
        if (\$column->Field === 'icon') {
            echo 'Kolom icon setelah migration: ' . \$column->Type . PHP_EOL;
            echo 'Null: ' . \$column->Null . PHP_EOL;
            echo 'Default: ' . \$column->Default . PHP_EOL;
            break;
        }
    }
"

# Test dengan SVG sample
echo -e "${YELLOW}🧪 Testing dengan sample SVG...${NC}"
docker exec $CONTAINER_NAME php artisan tinker --execute="
    \$sampleSvg = '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\" /></svg>';
    
    echo 'Sample SVG length: ' . strlen(\$sampleSvg) . ' characters' . PHP_EOL;
    
    // Test apakah bisa menyimpan SVG panjang
    try {
        \$category = \App\Models\SparePartCategory::first();
        if (\$category) {
            \$originalIcon = \$category->icon;
            \$category->icon = \$sampleSvg;
            \$category->save();
            
            echo 'SUCCESS: SVG berhasil disimpan!' . PHP_EOL;
            
            // Restore original icon
            \$category->icon = \$originalIcon;
            \$category->save();
        } else {
            echo 'No categories found for testing' . PHP_EOL;
        }
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
    }
"

# Clear cache
echo -e "${YELLOW}🧹 Membersihkan cache...${NC}"
docker exec $CONTAINER_NAME php artisan cache:clear
docker exec $CONTAINER_NAME php artisan config:clear

echo -e "${GREEN}✅ Selesai! Kolom icon sekarang sudah bisa menampung SVG yang panjang.${NC}"
echo -e "${BLUE}💡 Sekarang Anda bisa paste SVG code di Filament admin tanpa error.${NC}"

# Tampilkan instruksi
echo -e "\n${YELLOW}📋 Cara menggunakan:${NC}"
echo -e "1. Buka Filament Admin: ${BLUE}https://hartonomotor.xyz/admin${NC}"
echo -e "2. Masuk ke ${BLUE}Spare Part Categories${NC}"
echo -e "3. Edit kategori yang ingin diberi icon"
echo -e "4. Paste SVG code di field ${BLUE}Icon${NC}"
echo -e "5. Save - Icon akan muncul di website"

echo -e "\n${YELLOW}📝 Contoh SVG untuk kategori Oli & Cairan:${NC}"
echo -e "${BLUE}<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\" /></svg>${NC}"
