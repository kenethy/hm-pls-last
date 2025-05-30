# 📱 Hartono Motor WhatsApp Server

## 🎯 **Overview**

Custom WhatsApp Web.js REST API server untuk integrasi dengan sistem Hartono Motor. Server ini menyediakan dua mode operasi:

### **🧪 Mock Mode (Default)**
- **File**: `server-simple.js`
- **Purpose**: Testing dan development
- **Features**: Mock responses untuk semua API endpoints
- **Benefits**: Tidak memerlukan WhatsApp Web connection, ideal untuk testing

### **🚀 Full Mode**
- **File**: `server.js`
- **Purpose**: Production dengan WhatsApp Web.js asli
- **Features**: Real WhatsApp integration dengan QR code scanning
- **Requirements**: VPS dengan GUI support atau headless browser

## 🔧 **Installation & Setup**

### **1. Dependencies**
```bash
npm install
```

### **2. Running Locally**

**Mock Mode (Recommended for testing):**
```bash
npm start
# atau
npm run start-mock
```

**Full Mode (Production):**
```bash
npm run start-full
```

**Development Mode:**
```bash
npm run dev        # Mock mode dengan nodemon
npm run dev-full   # Full mode dengan nodemon
```

## 🐳 **Docker Deployment**

### **Mock Mode (Default)**
```bash
docker-compose up -d --build whatsapp-api
```

### **Full Mode**
```bash
WHATSAPP_MODE=full docker-compose up -d --build whatsapp-api
```

## 📡 **API Endpoints**

### **Health Check**
```
GET /health
```

### **Session Management**
```
GET  /session/status      # Check session status
POST /session/start       # Start WhatsApp session
DELETE /session/terminate # Terminate session
```

### **QR Code**
```
GET /session/qr           # Get QR code data
GET /session/qr/image     # Get QR code as image
```

### **Messaging**
```
POST /message/send        # Send text message
POST /number/check        # Check if number is registered
```

### **Client Info**
```
GET /client/info          # Get WhatsApp client information
```

## 🔍 **API Usage Examples**

### **Start Session**
```bash
curl -X POST http://localhost:3001/session/start \
  -H "X-API-Key: hartonomotor2024"
```

### **Send Message**
```bash
curl -X POST http://localhost:3001/message/send \
  -H "Content-Type: application/json" \
  -H "X-API-Key: hartonomotor2024" \
  -d '{
    "phone": "628123456789",
    "message": "Hello from Hartono Motor!"
  }'
```

### **Check Session Status**
```bash
curl http://localhost:3001/session/status \
  -H "X-API-Key: hartonomotor2024"
```

## 🛠️ **Configuration**

### **Environment Variables**
```env
PORT=3000                                    # Server port
API_KEY=hartonomotor2024                    # API authentication key
WEBHOOK_URL=http://app:80/api/whatsapp/webhook  # Laravel webhook URL
NODE_ENV=production                         # Node environment
WHATSAPP_MODE=mock                          # Server mode (mock/full)
```

## 🔄 **Integration with Laravel**

Server ini terintegrasi dengan Laravel Filament melalui:
- **WhatsAppService**: `app/Services/WhatsAppService.php`
- **WhatsAppManager**: `app/Filament/Pages/WhatsAppManager.php`
- **API Routes**: `routes/api.php`

## 🧪 **Testing Flow**

1. **Start Mock Server**: `npm start`
2. **Access Filament Admin**: Go to WhatsApp Manager
3. **Click "Start Session"**: Should work without errors
4. **View Mock QR Code**: QR code will be displayed
5. **Send Test Message**: Test message sending functionality
6. **Check Logs**: Monitor server logs for API calls

## 🚀 **Production Deployment**

### **Step 1: Upload to VPS**
```bash
# Upload semua files ke VPS
scp -r whatsapp-server/ user@your-vps:/path/to/app/
```

### **Step 2: Build Container**
```bash
# Di VPS
cd /path/to/app
docker-compose up -d --build whatsapp-api
```

### **Step 3: Monitor Logs**
```bash
docker logs -f hartono-whatsapp-api
```

### **Step 4: Test Integration**
- Access Laravel Filament admin
- Go to WhatsApp Manager
- Test all functionality

## 🔧 **Troubleshooting**

### **Container Won't Start**
```bash
# Check logs
docker logs hartono-whatsapp-api

# Restart container
docker restart hartono-whatsapp-api
```

### **API Connection Issues**
```bash
# Test health endpoint
curl http://localhost:3001/health

# Check network connectivity
docker exec -it hartono-whatsapp-api ping app
```

### **Switch to Full Mode**
```bash
# Stop current container
docker stop hartono-whatsapp-api

# Start with full mode
WHATSAPP_MODE=full docker-compose up -d whatsapp-api
```

## 📝 **Development Notes**

- **Mock Mode**: Ideal untuk development dan testing UI integration
- **Full Mode**: Gunakan setelah yakin integration Laravel sudah bekerja
- **VPS Compatibility**: Full mode sudah dioptimasi untuk VPS environment
- **Error Handling**: Semua endpoints memiliki proper error handling

## 🎯 **Next Steps**

1. **Test Mock Integration**: Pastikan Laravel integration bekerja
2. **Fix Any Issues**: Debug dan perbaiki masalah integration
3. **Switch to Full Mode**: Setelah yakin semua bekerja
4. **Production Testing**: Test dengan real WhatsApp account

---

**Developed for Hartono Motor** 🚗⚡
