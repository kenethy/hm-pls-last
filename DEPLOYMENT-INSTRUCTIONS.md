# WhatsApp QR Generator - Deployment Instructions

## 🚀 Git Pull Deployment untuk VPS

### **Step 1: Commit dan Push ke Repository**

```bash
# Di local development
git add .
git commit -m "Add WhatsApp QR Generator integration"
git push origin main
```

### **Step 2: Pull di VPS**

```bash
# SSH ke VPS
ssh user@hartonomotor.xyz

# Masuk ke direktori Laravel project
cd /path/to/laravel/project

# Pull latest changes
git pull origin main
```

### **Step 3: Setup Environment Variables**

```bash
# Edit file .env Laravel
nano .env

# Tambahkan konfigurasi WhatsApp (copy dari .env.whatsapp):
WHATSAPP_API_URL=http://localhost:3000
WHATSAPP_BASIC_AUTH_USERNAME=admin
WHATSAPP_BASIC_AUTH_PASSWORD=your_password_from_production_config
WHATSAPP_WEBHOOK_SECRET=your_webhook_secret
WHATSAPP_WEBHOOK_URL=https://hartonomotor.xyz/webhook/whatsapp
WHATSAPP_LOGGING_ENABLED=true
WHATSAPP_LOG_CHANNEL=daily
```

### **Step 4: Get Credentials dari Production WhatsApp API**

```bash
# Lihat credentials yang sudah di-generate
sudo cat /opt/whatsapp-api-production/config/.env

# Copy WHATSAPP_BASIC_AUTH_PASSWORD dan WHATSAPP_WEBHOOK_SECRET
# Paste ke .env Laravel
```

### **Step 5: Update Laravel**

```bash
# Install dependencies (jika ada yang baru)
composer install --no-dev --optimize-autoloader

# Clear cache
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache config untuk production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations (optional, untuk logging)
php artisan migrate
```

### **Step 6: Update Routes**

```bash
# Edit routes/web.php
nano routes/web.php

# Tambahkan di bagian bawah file:
```

```php
// WhatsApp QR Code Routes
use App\Http\Controllers\WhatsAppQRController;

Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
    Route::get('/qr-generator', [WhatsAppQRController::class, 'index'])->name('qr-generator');
    Route::post('/generate-qr', [WhatsAppQRController::class, 'generateFreshQR'])->name('generate-qr');
    Route::post('/check-status', [WhatsAppQRController::class, 'checkStatus'])->name('check-status');
    Route::post('/send-message', [WhatsAppQRController::class, 'sendMessage'])->name('send-message');
});

// Direct access
Route::get('/whatsapp-qr', [WhatsAppQRController::class, 'index'])->name('whatsapp-qr');
```

### **Step 7: Set Permissions**

```bash
# Set proper permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### **Step 8: Restart Services**

```bash
# Restart PHP-FPM (jika menggunakan)
sudo systemctl restart php8.1-fpm

# Restart Nginx/Apache
sudo systemctl restart nginx
# atau
sudo systemctl restart apache2

# Restart Laravel queue workers (jika ada)
sudo supervisorctl restart laravel-worker:*
```

## 🌐 **Akses WhatsApp QR Generator**

Setelah deployment berhasil, akses melalui:

### **URL Akses:**
```
https://hartonomotor.xyz/whatsapp/qr-generator
```

atau

```
https://hartonomotor.xyz/whatsapp-qr
```

## 🔧 **Troubleshooting**

### **Jika ada error 500:**
```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs
sudo tail -f /var/log/nginx/error.log
# atau
sudo tail -f /var/log/apache2/error.log
```

### **Jika WhatsApp API tidak terhubung:**
```bash
# Test koneksi internal
curl http://localhost:3000/app/devices

# Check WhatsApp API container
docker ps | grep whatsapp-api-production
docker logs whatsapp-api-production --tail 20
```

### **Jika QR tidak muncul:**
```bash
# Check file permissions
ls -la /opt/whatsapp-api-production/data/qrcode/

# Test QR generation
curl http://localhost:3000/app/login-fresh
```

## 📱 **Testing**

### **Test 1: Akses Halaman**
```bash
curl -I https://hartonomotor.xyz/whatsapp/qr-generator
# Should return 200 OK
```

### **Test 2: Generate QR**
1. Buka https://hartonomotor.xyz/whatsapp/qr-generator
2. Klik "Generate Fresh QR Code"
3. QR code harus muncul dalam 1-2 detik

### **Test 3: Scan QR**
1. Buka WhatsApp di ponsel
2. Settings → Linked Devices → Link a Device
3. Scan QR code yang muncul
4. Verifikasi koneksi berhasil

## 🎯 **Integration dengan Filament Admin**

Jika ingin menambahkan ke Filament admin panel:

```bash
# Buat Filament page
php artisan make:filament-page WhatsAppQR

# Copy content dari WhatsAppQRPage.php yang sudah dibuat
```

## 📊 **Monitoring**

### **Check Logs:**
```bash
# Laravel logs
tail -f storage/logs/laravel.log | grep WhatsApp

# WhatsApp API logs
docker logs whatsapp-api-production -f
```

### **Performance Monitoring:**
```bash
# Check response time
time curl -s https://hartonomotor.xyz/whatsapp/qr-generator > /dev/null

# Check API response time
time curl -s http://localhost:3000/app/devices > /dev/null
```

## ✅ **Verification Checklist**

- [ ] Git pull berhasil
- [ ] Environment variables ditambahkan
- [ ] Routes ditambahkan ke web.php
- [ ] Cache di-clear dan di-rebuild
- [ ] Permissions di-set dengan benar
- [ ] Services di-restart
- [ ] Halaman QR generator dapat diakses
- [ ] QR code dapat di-generate
- [ ] WhatsApp dapat scan dan link device
- [ ] Logs berjalan dengan normal

## 🆘 **Support**

Jika ada masalah:
1. Check troubleshooting section di atas
2. Run verification script: `./verify-deployment.sh`
3. Run troubleshooting script: `./whatsapp-troubleshoot-production.sh`
4. Check logs untuk error details
