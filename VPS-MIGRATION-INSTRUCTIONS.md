# 🔧 Instruksi Menjalankan Migration di VPS

## Masalah
Error: `SQLSTATE[22001]: String data, right truncated: 1406 Data too long for column 'icon'`

Kolom `icon` di tabel `spare_part_categories` terlalu kecil (VARCHAR(255)) untuk menampung SVG code yang panjang.

## Solusi
Mengubah kolom `icon` dari VARCHAR(255) menjadi TEXT.

## Langkah-langkah di VPS

### 1. Login ke VPS
```bash
ssh root@your-vps-ip
cd /path/to/your/laravel/project
```

### 2. Backup Database (Opsional tapi Disarankan)
```bash
# Backup tabel spare_part_categories
docker exec hartono-phpmyadmin mysqldump -u root -p hartono_motor spare_part_categories > spare_part_categories_backup_$(date +%Y%m%d_%H%M%S).sql
```

### 3. Jalankan Migration
```bash
# Jalankan migration untuk memperbesar kolom icon
docker exec hartono-app php artisan migrate --path=database/migrations/2025_05_27_140500_modify_spare_part_categories_icon_column.php --force
```

### 4. Verifikasi Perubahan
```bash
# Cek struktur tabel setelah migration
docker exec hartono-app php artisan tinker --execute="
\$columns = DB::select('DESCRIBE spare_part_categories');
foreach (\$columns as \$column) {
    if (\$column->Field === 'icon') {
        echo 'Kolom icon: ' . \$column->Type . PHP_EOL;
        break;
    }
}
"
```

### 5. Test dengan SVG Sample
```bash
# Test apakah bisa menyimpan SVG panjang
docker exec hartono-app php artisan tinker --execute="
\$sampleSvg = '<svg xmlns=\"http://www.w3.org/2000/svg\" class=\"h-8 w-8\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z\" /></svg>';

try {
    \$category = \App\Models\SparePartCategory::first();
    if (\$category) {
        \$originalIcon = \$category->icon;
        \$category->icon = \$sampleSvg;
        \$category->save();
        echo 'SUCCESS: SVG berhasil disimpan!' . PHP_EOL;
        \$category->icon = \$originalIcon;
        \$category->save();
    }
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage() . PHP_EOL;
}
"
```

### 6. Clear Cache
```bash
docker exec hartono-app php artisan cache:clear
docker exec hartono-app php artisan config:clear
```

## Setelah Migration Berhasil

### Cara Menggunakan di Filament Admin

1. **Buka Filament Admin**: `https://hartonomotor.xyz/admin`
2. **Masuk ke Spare Part Categories**
3. **Edit kategori** yang ingin diberi icon
4. **Paste SVG code** di field **Icon (SVG Code)** (sekarang sudah berupa textarea)
5. **Save** - Icon akan langsung muncul di website

### Contoh SVG untuk Berbagai Kategori

#### Oli & Cairan
```svg
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" />
</svg>
```

#### Sistem Rem
```svg
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
</svg>
```

#### Aki & Battery
```svg
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
</svg>
```

#### Filter
```svg
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.414A1 1 0 013 6.707V4z" />
</svg>
```

#### Suspensi
```svg
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
</svg>
```

#### Radiator
```svg
<svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
</svg>
```

## Troubleshooting

### Jika Migration Gagal
```bash
# Cek status migration
docker exec hartono-app php artisan migrate:status

# Rollback jika perlu
docker exec hartono-app php artisan migrate:rollback --step=1

# Jalankan ulang
docker exec hartono-app php artisan migrate --path=database/migrations/2025_05_27_140500_modify_spare_part_categories_icon_column.php --force
```

### Jika Masih Error
```bash
# Manual SQL (hati-hati!)
docker exec hartono-phpmyadmin mysql -u root -p -e "
USE hartono_motor;
ALTER TABLE spare_part_categories MODIFY COLUMN icon TEXT NULL;
"
```

## Verifikasi Hasil

Setelah migration berhasil:
1. ✅ Kolom `icon` berubah dari VARCHAR(255) ke TEXT
2. ✅ Bisa menyimpan SVG code yang panjang
3. ✅ Field di Filament Admin berubah jadi Textarea
4. ✅ Icon muncul di website (homepage & spare-parts page)

## Catatan Penting

- **Backup database** sebelum menjalankan migration
- **Test di staging** jika memungkinkan
- **Verifikasi** setelah migration selesai
- **Clear cache** setelah perubahan
