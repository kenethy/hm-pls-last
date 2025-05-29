# WhatsApp Integration Troubleshooting Guide

## 🔍 Common Issues and Solutions

### 1. **404 Not Found Error**

**Symptoms:**
- "Koneksi Gagal" message in Filament admin
- "API returned error: 404 Not Found"

**Causes & Solutions:**

#### A. WhatsApp API Server Not Running
```bash
# Check if container is running
docker-compose ps

# If whatsapp-api is not running, start it
docker-compose up -d whatsapp-api

# Check logs for errors
docker-compose logs whatsapp-api
```

#### B. Wrong API URL Configuration
1. Go to Filament Admin > WhatsApp Integration > Konfigurasi WhatsApp
2. Check API URL is set to: `http://whatsapp-api:3000`
3. For local development use: `http://localhost:3000`

#### C. Network Connectivity Issues
```bash
# Test connectivity from Laravel container
docker-compose exec app curl http://whatsapp-api:3000/app/devices

# Test from host machine
curl http://localhost:3000/app/devices
```

### 2. **Container Build Failures**

**Symptoms:**
- Docker build errors
- Container fails to start

**Solutions:**

#### A. Go Module Issues
```bash
# Clean build cache
docker system prune -f

# Rebuild without cache
docker-compose build --no-cache whatsapp-api
```

#### B. Missing Dependencies
```bash
# Check Dockerfile has all required packages
# Ensure ffmpeg is installed for media processing
```

### 3. **Database Connection Issues**

**Symptoms:**
- WhatsApp API logs show database errors
- SQLite file permission issues

**Solutions:**

#### A. Volume Permissions
```bash
# Fix volume permissions
docker-compose exec whatsapp-api chown -R 1000:1000 /app/storages
```

#### B. SQLite Database Issues
```bash
# Check if database file exists
docker-compose exec whatsapp-api ls -la /app/storages/

# Create directory if missing
docker-compose exec whatsapp-api mkdir -p /app/storages
```

### 4. **Authentication Issues**

**Symptoms:**
- 401 Unauthorized errors
- Basic auth failures

**Solutions:**

#### A. Configure Basic Auth (Optional)
```bash
# Add to .env file
WHATSAPP_BASIC_AUTH=username:password

# Restart container
docker-compose restart whatsapp-api
```

#### B. Update Laravel Configuration
1. Go to WhatsApp Configuration in admin
2. Set API Username and Password if using basic auth

### 5. **WhatsApp QR Code Issues**

**Symptoms:**
- Cannot generate QR code
- QR code not accessible

**Solutions:**

#### A. Check QR Code Generation
```bash
# Test QR code endpoint
curl http://localhost:3000/app/login

# Check if statics volume is mounted
docker-compose exec whatsapp-api ls -la /app/statics/qrcode/
```

## 🛠️ Diagnostic Commands

### Container Status
```bash
# Check all containers
docker-compose ps

# Check specific container
docker-compose ps whatsapp-api
```

### Logs Analysis
```bash
# View real-time logs
docker-compose logs -f whatsapp-api

# View last 100 lines
docker-compose logs --tail=100 whatsapp-api

# View Laravel logs
docker-compose logs app
```

### Network Testing
```bash
# Test API endpoints
curl http://localhost:3000/app/devices
curl http://localhost:3000/app/login

# Test from Laravel container
docker-compose exec app curl http://whatsapp-api:3000/app/devices
```

### File System Check
```bash
# Check WhatsApp API file structure
docker-compose exec whatsapp-api ls -la /app/

# Check volumes
docker volume ls | grep whatsapp

# Check volume contents
docker-compose exec whatsapp-api ls -la /app/storages/
docker-compose exec whatsapp-api ls -la /app/statics/
```

## 🔧 Configuration Verification

### Environment Variables
```bash
# Check WhatsApp API environment
docker-compose exec whatsapp-api env | grep -E "(APP_|WHATSAPP_|DB_)"
```

### Laravel Configuration
```bash
# Check Laravel environment
docker-compose exec app php artisan config:show

# Check database connection
docker-compose exec app php artisan migrate:status
```

## 📞 Support Checklist

Before seeking help, please provide:

1. **Container Status:**
   ```bash
   docker-compose ps
   ```

2. **WhatsApp API Logs:**
   ```bash
   docker-compose logs --tail=50 whatsapp-api
   ```

3. **Laravel Logs:**
   ```bash
   docker-compose exec app tail -50 storage/logs/laravel.log
   ```

4. **Network Test:**
   ```bash
   curl -v http://localhost:3000/app/devices
   ```

5. **Configuration:**
   - Screenshot of WhatsApp Configuration in Filament admin
   - Current API URL setting
   - Any custom environment variables

## 🚀 Quick Recovery Steps

If everything fails, try this complete reset:

```bash
# Stop all containers
docker-compose down

# Remove WhatsApp volumes (WARNING: This will delete WhatsApp session data)
docker volume rm $(docker volume ls -q | grep whatsapp)

# Rebuild and restart
docker-compose build --no-cache whatsapp-api
docker-compose up -d

# Wait and test
sleep 30
curl http://localhost:3000/app/devices
```
