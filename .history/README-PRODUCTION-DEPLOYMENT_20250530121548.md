# WhatsApp API Production Deployment Guide

## 🚀 Overview

This guide provides comprehensive instructions for deploying the WhatsApp API with Smart Fresh QR implementation to a production VPS environment. The deployment includes security hardening, monitoring, backup systems, and troubleshooting tools.

## 📋 Prerequisites

### System Requirements (Perfect for Tiny VPS!)
- **OS**: Ubuntu 20.04+ or Debian 11+
- **RAM**: Minimum 256MB, Recommended 512MB+ (works great on tiny VPS!)
- **Storage**: Minimum 1GB, Recommended 2GB free space
- **Network**: Stable internet connection
- **Ports**: 3000 (WhatsApp API), 22 (SSH), 80/443 (if SSL enabled)

> 💡 **Tiny VPS Friendly**: This deployment is optimized for small VPS instances and will work perfectly on budget hosting!

### Access Requirements
- Root or sudo access to the VPS
- SSH access to the server
- Domain name (optional, for SSL)

## 🛠️ Installation Steps

### Step 1: Prepare the Server

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install basic dependencies
sudo apt install -y curl wget git unzip
```

### Step 2: Upload Deployment Files

Upload these files to your VPS:
- `deploy-whatsapp-production.sh` - Main deployment script
- `whatsapp-troubleshoot-production.sh` - Troubleshooting script
- `go-whatsapp-web-multidevice-main/` - WhatsApp API source code

```bash
# Make scripts executable
chmod +x deploy-whatsapp-production.sh
chmod +x whatsapp-troubleshoot-production.sh
```

### Step 3: Configure Environment Variables (Optional)

Set environment variables before deployment:

```bash
export WHATSAPP_PORT=3000
export WHATSAPP_BASIC_AUTH_USERNAME=admin
export WHATSAPP_BASIC_AUTH_PASSWORD=your_secure_password
export WEBHOOK_SECRET=your_webhook_secret
export SSL_ENABLED=false
export DOMAIN_NAME=your-domain.com
export BACKUP_RETENTION_DAYS=7
```

### Step 4: Run Deployment

```bash
# Run the production deployment script
./deploy-whatsapp-production.sh
```

The script will:
- ✅ Check system requirements
- ✅ Install Docker and dependencies
- ✅ Configure firewall and security
- ✅ Set up project structure
- ✅ Generate secure credentials
- ✅ Deploy WhatsApp API
- ✅ Configure monitoring and backups
- ✅ Perform health checks

## 🔧 Post-Deployment Configuration

### Verify Deployment

```bash
# Check container status
docker ps | grep whatsapp-api-production

# Test API endpoints
curl http://localhost:3000/app/devices
curl http://localhost:3000/app/login-fresh
```

### Access Credentials

Credentials are stored securely in:
```
/opt/whatsapp-api-production/config/.env
```

View credentials:
```bash
sudo cat /opt/whatsapp-api-production/config/.env
```

## 🛡️ Security Features

### Firewall Configuration
- UFW firewall enabled
- Only necessary ports open (SSH, WhatsApp API, HTTP/HTTPS if SSL)
- Rate limiting for SSH connections

### Fail2ban Protection
- Automatic IP blocking for failed login attempts
- SSH brute force protection
- Configurable ban times and retry limits

### File Permissions
- Restricted access to configuration files (600)
- Secure SSL certificate storage (700)
- Proper ownership and permissions

### Basic Authentication
- Username/password protection for API endpoints
- Secure credential generation
- Environment-based configuration

## 📊 Monitoring & Maintenance

### Management Scripts

Located in `/opt/whatsapp-api-production/`:

```bash
# Start the API
./start.sh

# Stop the API
./stop.sh

# Restart the API
./restart.sh

# View logs
./logs.sh

# Check status
./status.sh

# Manual backup
./backup.sh
```

### Automated Monitoring

- **Health checks**: Every 5 minutes
- **Backups**: Daily at 2:00 AM
- **Log rotation**: Automatic cleanup
- **Resource monitoring**: CPU, memory, disk usage

### Troubleshooting

Run the troubleshooting script:
```bash
./whatsapp-troubleshoot-production.sh
```

Features:
- Quick status checks
- Log analysis
- API endpoint testing
- Performance diagnostics
- Security checks
- Quick fixes
- Diagnostic reports

## 📁 Directory Structure

```
/opt/whatsapp-api-production/
├── config/
│   └── .env                    # Environment variables
├── data/
│   ├── sessions/              # WhatsApp session data
│   ├── qrcode/               # Generated QR codes
│   └── uploads/              # File uploads
├── logs/
│   ├── app/                  # Application logs
│   ├── deployment.log        # Deployment logs
│   └── monitor.log           # Monitoring logs
├── backups/                  # Automated backups
├── ssl/                      # SSL certificates
├── go-whatsapp-web-multidevice-main/  # Source code
├── docker-compose.production.yml      # Docker configuration
└── *.sh                      # Management scripts
```

## 🔄 Backup & Recovery

### Automated Backups

- **Schedule**: Daily at 2:00 AM
- **Retention**: 7 days (configurable)
- **Contents**: Session data, QR codes, configuration
- **Location**: `/opt/whatsapp-api-production/backups/`

### Manual Backup

```bash
# Run manual backup
/opt/whatsapp-api-production/backup.sh

# List backups
ls -la /opt/whatsapp-api-production/backups/
```

### Recovery

```bash
# Stop the service
./stop.sh

# Restore from backup
cd /opt/whatsapp-api-production/backups/
tar -xzf whatsapp-backup-YYYYMMDD_HHMMSS/whatsapp_data.tar.gz -C ../data/sessions/
tar -xzf whatsapp-backup-YYYYMMDD_HHMMSS/whatsapp_statics.tar.gz -C ../data/qrcode/

# Start the service
./start.sh
```

## 🔗 API Usage

### Endpoints

- **Device Status**: `GET /app/devices`
- **Fresh QR Login**: `GET /app/login-fresh`
- **Regular QR Login**: `GET /app/login`
- **Send Message**: `POST /send/message`

### Authentication

All endpoints require Basic Authentication:
```bash
curl -u username:password http://your-server:3000/app/devices
```

### Smart Fresh QR

The Smart Fresh QR implementation provides:
- ✅ Fast QR generation (< 1 second)
- ✅ Reliable device linking
- ✅ Session preservation
- ✅ Comprehensive logging
- ✅ Timestamp debugging

Example usage:
```bash
curl -u admin:password http://your-server:3000/app/login-fresh
```

Response:
```json
{
  "code": "SUCCESS",
  "message": "Fresh login success - new QR code generated",
  "results": {
    "qr_link": "http://your-server:3000/statics/qrcode/scan-qr-fresh-uuid.png",
    "qr_duration": 30,
    "fresh": true,
    "generated_at": "2025-05-30 04:57:12.874",
    "expires_at": "2025-05-30 04:57:42.874",
    "total_time_ms": 870
  }
}
```

## 🚨 Troubleshooting

### Common Issues

1. **Container not starting**
   ```bash
   # Check logs
   docker logs whatsapp-api-production

   # Restart container
   ./restart.sh
   ```

2. **API not responding**
   ```bash
   # Check if port is open
   ss -tlnp | grep :3000

   # Test locally
   curl http://localhost:3000/app/devices
   ```

3. **QR code generation fails**
   ```bash
   # Clear QR codes
   rm -f /opt/whatsapp-api-production/data/qrcode/*.png

   # Restart service
   ./restart.sh
   ```

4. **High resource usage**
   ```bash
   # Check resource usage
   docker stats whatsapp-api-production

   # Check system resources
   htop
   ```

### Getting Help

1. Run troubleshooting script: `./whatsapp-troubleshoot-production.sh`
2. Generate diagnostic report: Option 7 in troubleshooting menu
3. Check logs: `./logs.sh`
4. Review monitoring logs: `tail -f /opt/whatsapp-api-production/logs/monitor.log`

## 📞 Support

For additional support:
- Check the troubleshooting script output
- Review deployment logs
- Examine container logs
- Generate and review diagnostic reports

## 🔄 Updates

To update the WhatsApp API:

1. Backup current deployment
2. Upload new source code
3. Rebuild container:
   ```bash
   cd /opt/whatsapp-api-production
   docker-compose -f docker-compose.production.yml down
   docker-compose -f docker-compose.production.yml build --no-cache
   docker-compose -f docker-compose.production.yml up -d
   ```

## 📝 License

This deployment configuration is provided as-is for production use with the WhatsApp API implementation.
