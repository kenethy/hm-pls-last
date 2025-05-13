#!/bin/bash
# Script untuk memperbaiki rekap montir secara manual

# Warna untuk output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Memulai perbaikan rekap montir secara manual...${NC}\n"

# Minta input nama container
echo -e "${YELLOW}Masukkan nama container aplikasi Laravel Anda:${NC}"
read -p "Nama container: " CONTAINER_NAME

if [ -z "$CONTAINER_NAME" ]; then
    echo -e "${RED}Error: Nama container tidak boleh kosong.${NC}"
    exit 1
fi

# Minta input path artisan
echo -e "${YELLOW}Masukkan path lengkap ke file artisan (default: /var/www/html/artisan):${NC}"
read -p "Path artisan [/var/www/html/artisan]: " ARTISAN_PATH

if [ -z "$ARTISAN_PATH" ]; then
    ARTISAN_PATH="/var/www/html/artisan"
fi

# Verifikasi file artisan
if ! docker exec $CONTAINER_NAME ls -la $ARTISAN_PATH &>/dev/null; then
    echo -e "${RED}Error: File artisan tidak ditemukan di path $ARTISAN_PATH pada container $CONTAINER_NAME.${NC}"
    exit 1
fi

echo -e "${GREEN}File artisan ditemukan di path $ARTISAN_PATH pada container $CONTAINER_NAME.${NC}"

# Tentukan direktori kerja
WORK_DIR=$(dirname $ARTISAN_PATH)
echo -e "${YELLOW}Menggunakan direktori kerja: $WORK_DIR${NC}"

# Bersihkan cache Laravel
echo -e "${YELLOW}Membersihkan cache Laravel...${NC}"
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH cache:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH config:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH route:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH view:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH optimize:clear

# Jalankan perintah regenerate-mechanic-reports
echo -e "${YELLOW}Menjalankan perintah regenerate-mechanic-reports...${NC}"
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH mechanic:regenerate-reports

# Jalankan perintah sync-reports
echo -e "${YELLOW}Menjalankan perintah sync-reports...${NC}"
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH mechanic:sync-reports --force

# Periksa apakah ada laporan montir dengan biaya jasa 0
echo -e "${YELLOW}Memeriksa laporan montir dengan biaya jasa 0...${NC}"
ZERO_REPORTS=$(docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH tinker --execute="echo \App\Models\MechanicReport::where('total_labor_cost', 0)->count();")

if [[ $ZERO_REPORTS -gt 0 ]]; then
    echo -e "${YELLOW}Ditemukan $ZERO_REPORTS laporan montir dengan biaya jasa 0.${NC}"
    echo -e "${YELLOW}Memperbaiki laporan montir dengan biaya jasa default...${NC}"

    # Jalankan perintah untuk memperbaiki laporan montir dengan biaya jasa 0
    docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH tinker --execute="
        \$reports = \App\Models\MechanicReport::where('total_labor_cost', 0)->get();
        foreach (\$reports as \$report) {
            \$services = \$report->mechanic->services()
                ->wherePivot('week_start', \$report->week_start)
                ->wherePivot('week_end', \$report->week_end)
                ->get();

            if (\$services->count() > 0) {
                \$report->total_labor_cost = 50000 * \$services->count();
                \$report->services_count = \$services->count();
                \$report->save();
                echo \"Fixed report ID: {\$report->id} for mechanic {\$report->mechanic->name}, new labor cost: {\$report->total_labor_cost}\\n\";
            }
        }
    "
fi

# Bersihkan cache lagi
echo -e "${YELLOW}Membersihkan cache akhir...${NC}"
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH cache:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH config:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH route:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH view:clear
docker exec -w $WORK_DIR $CONTAINER_NAME php $ARTISAN_PATH optimize

echo -e "\n${GREEN}Selesai! Rekap montir telah diperbaiki.${NC}"
echo -e "${YELLOW}Sekarang biaya jasa seharusnya ditampilkan dengan benar di rekap montir.${NC}"
