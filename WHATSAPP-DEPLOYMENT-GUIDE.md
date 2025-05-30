# 🚗 Hartono Motor - WhatsApp API Deployment Guide

## 📋 Panduan Lengkap Deployment WhatsApp API di VPS

Panduan ini dibuat khusus untuk mengatasi masalah QR code caching/expiration di VPS yang sensitif.

## 🎯 Masalah yang Dipecahkan

- ❌ QR code lama/expired yang di-cache
- ❌ Session WhatsApp yang tidak bisa di-reset
- ❌ Error "Session Saved" saat generate QR code baru
- ✅ Fresh QR code generation yang selalu berhasil

## 🔧 Fitur Baru yang Ditambahkan

### 1. **Fresh QR Code Endpoint**
- **Endpoint baru**: `/app/login-fresh`
- **Fungsi**: Membersihkan session dan generate QR code baru
- **Keunggulan**: Selalu berhasil, tidak peduli ada session atau tidak

### 2. **Enhanced Filament Admin**
- **Tombol "QR Code Baru"**: Generate fresh QR code dengan konfirmasi
- **Tombol "Dapatkan QR Code"**: Generate QR code regular
- **Notifikasi real-time**: Link langsung ke QR code

### 3. **Improved Session Management**
- **Auto logout**: Sebelum generate fresh QR
- **File cleanup**: Hapus QR code lama otomatis
- **Better logging**: Log yang lebih detail untuk debugging

## 🚀 Cara Deployment di VPS

### **Metode 1: Menggunakan Script Otomatis (RECOMMENDED)**

```bash
# 1. Jalankan script deployment
./deploy-whatsapp-vps.sh

# Script akan otomatis:
# - Backup data existing
# - Stop container lama
# - Build image baru
# - Start container baru
# - Test semua endpoint
# - Show logs dan status
```

### **Metode 2: Manual Step-by-Step**

```bash
# 1. Stop container existing
docker-compose down whatsapp-api

# 2. Backup data (opsional)
docker run --rm -v hm-cukupya_whatsapp_data:/data -v $(pwd)/backup:/backup alpine tar czf /backup/whatsapp_data.tar.gz -C /data .

# 3. Build ulang image
docker-compose build --no-cache whatsapp-api

# 4. Start container baru
docker-compose up -d whatsapp-api

# 5. Check status
docker logs hartono-whatsapp-api --tail 20
```

## 🧪 Testing dan Troubleshooting

### **Menggunakan Script Troubleshooting**

```bash
# Jalankan script troubleshooting interaktif
./whatsapp-troubleshoot.sh

# Menu yang tersedia:
# 1. Check container status
# 2. Show logs
# 3. Test API endpoints
# 4. Generate fresh QR code
# 5. Clear session data
# 6. Restart container
# 7. Show container info
```

### **Testing Manual**

```bash
# Test endpoint devices
curl -X GET "http://localhost:3000/app/devices"

# Test fresh QR generation
curl -X GET "http://localhost:3000/app/login-fresh"

# Test regular QR generation
curl -X GET "http://localhost:3000/app/login"
```

## 📱 Cara Menggunakan di Filament Admin

### **1. Generate QR Code Biasa**
1. Masuk ke **WhatsApp Configuration**
2. Klik **"Dapatkan QR Code"**
3. Klik **"Lihat QR Code"** di notifikasi
4. Scan dengan WhatsApp

### **2. Generate Fresh QR Code (RECOMMENDED)**
1. Masuk ke **WhatsApp Configuration**
2. Klik **"QR Code Baru"**
3. Konfirmasi dengan **"Ya"**
4. Klik **"Lihat QR Code Baru"** di notifikasi
5. Scan dengan WhatsApp

## 🔍 Perbedaan Endpoint

| Endpoint | Fungsi | Kapan Digunakan |
|----------|--------|-----------------|
| `/app/login` | QR code regular | Saat belum ada session |
| `/app/login-fresh` | QR code fresh | **Selalu berhasil**, hapus session dulu |

## 📊 Monitoring dan Logs

### **Melihat Logs Real-time**
```bash
# Follow logs container
docker logs hartono-whatsapp-api -f

# Logs dengan timestamp
docker logs hartono-whatsapp-api --timestamps

# Logs terakhir 50 baris
docker logs hartono-whatsapp-api --tail 50
```

### **Check Status Container**
```bash
# Status container
docker ps --filter name=hartono-whatsapp-api

# Resource usage
docker stats hartono-whatsapp-api --no-stream

# Health check
docker exec hartono-whatsapp-api wget --quiet --tries=1 --spider http://localhost:3000/app/devices
```

## 🆘 Troubleshooting Common Issues

### **1. Container Tidak Start**
```bash
# Check logs error
docker logs hartono-whatsapp-api

# Restart container
docker restart hartono-whatsapp-api

# Rebuild jika perlu
docker-compose build --no-cache whatsapp-api
docker-compose up -d whatsapp-api
```

### **2. QR Code Masih Expired**
```bash
# Clear session manual
docker exec hartono-whatsapp-api rm -f /app/storages/whatsapp.db
docker exec hartono-whatsapp-api rm -f /app/statics/qrcode/scan-qr*.png
docker restart hartono-whatsapp-api

# Atau gunakan fresh endpoint
curl -X GET "http://localhost:3000/app/login-fresh"
```

### **3. API Tidak Respond**
```bash
# Check port binding
docker port hartono-whatsapp-api

# Check network
docker network ls
docker network inspect hm-cukupya_hartono-network

# Restart networking
docker-compose down
docker-compose up -d whatsapp-api
```

## 📁 File Structure

```
├── deploy-whatsapp-vps.sh          # Script deployment otomatis
├── whatsapp-troubleshoot.sh        # Script troubleshooting
├── whatsapp-qr-test.html           # Test page untuk QR code
├── docker-compose.local.yml        # Docker compose untuk testing lokal
├── go-whatsapp-web-multidevice-main/
│   ├── src/
│   │   ├── usecase/app.go          # ✅ LoginFresh method
│   │   ├── domains/app/app.go      # ✅ Interface update
│   │   └── ui/rest/app.go          # ✅ Fresh endpoint
├── app/Services/WhatsAppService.php # ✅ Enhanced service
└── app/Filament/Resources/
    └── WhatsAppConfigResource.php   # ✅ Fresh QR button
```

## 🎉 Expected Results

Setelah deployment berhasil:

- ✅ **Fresh QR Code**: Selalu generate QR baru
- ✅ **No More Caching**: Tidak ada QR code expired
- ✅ **Better UX**: Tombol yang jelas di admin
- ✅ **Reliable**: Sistem yang stabil dan dapat diandalkan

## 📞 Support

Jika ada masalah:
1. Jalankan `./whatsapp-troubleshoot.sh`
2. Check logs dengan `docker logs hartono-whatsapp-api`
3. Test endpoint dengan script atau curl
4. Restart container jika perlu

---

**🚗 Hartono Motor - Reliable WhatsApp Integration**
