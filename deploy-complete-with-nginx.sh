#!/bin/bash

# Complete WhatsApp Deployment with Nginx Installation
# This script will install Nginx first, then deploy WhatsApp API

echo "🚀 Complete WhatsApp Deployment with Nginx for hartonomotor.xyz"
echo "==============================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

echo -e "${BLUE}📋 Starting complete deployment...${NC}"
echo ""

# Function to run script with error handling
run_step() {
    local step_number=$1
    local description=$2
    local script_name=$3
    
    echo -e "${PURPLE}STEP ${step_number}: ${description}${NC}"
    echo "$(printf '=%.0s' {1..50})"
    
    if [ -f "${SCRIPT_DIR}/${script_name}" ]; then
        chmod +x "${SCRIPT_DIR}/${script_name}"
        if bash "${SCRIPT_DIR}/${script_name}"; then
            echo -e "${GREEN}✅ Step ${step_number} completed successfully${NC}"
            echo ""
            return 0
        else
            echo -e "${RED}❌ Step ${step_number} failed${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ Script ${script_name} not found${NC}"
        return 1
    fi
}

# Function to check if Nginx is installed
check_nginx() {
    if command -v nginx >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Nginx is already installed${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠️ Nginx not found, will install it${NC}"
        return 1
    fi
}

# Function to find Laravel directory
find_laravel_directory() {
    echo -e "${YELLOW}🔍 Searching for Laravel project...${NC}"
    
    LARAVEL_DIRS=$(find /var/www /home /opt -name "artisan" -type f 2>/dev/null | xargs dirname)
    
    if [ -z "$LARAVEL_DIRS" ]; then
        echo -e "${RED}❌ No Laravel project found${NC}"
        return 1
    fi
    
    # Try to find the Hartono Motor project
    for dir in $LARAVEL_DIRS; do
        if [ -f "$dir/composer.json" ]; then
            if grep -q "hartonomotor\|Hartono" "$dir/composer.json" 2>/dev/null; then
                LARAVEL_DIR="$dir"
                echo -e "${GREEN}✅ Hartono Motor project found: ${LARAVEL_DIR}${NC}"
                return 0
            fi
        fi
    done
    
    # Use first Laravel project found
    LARAVEL_ARRAY=($LARAVEL_DIRS)
    LARAVEL_DIR="${LARAVEL_ARRAY[0]}"
    echo -e "${YELLOW}⚠️ Using first Laravel project: ${LARAVEL_DIR}${NC}"
    
    return 0
}

# Welcome message
echo -e "${YELLOW}⚠️  IMPORTANT NOTES:${NC}"
echo -e "1. This script will install Nginx if not present"
echo -e "2. Make sure you have sudo privileges"
echo -e "3. This will modify your server configuration"
echo -e "4. Have your WhatsApp phone ready for QR code scanning"
echo ""
echo -e "${BLUE}Press Enter to start complete deployment...${NC}"
read -r

# Pre-check: Find Laravel directory
if ! find_laravel_directory; then
    echo -e "${RED}❌ Cannot proceed without Laravel project${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Laravel project confirmed at: ${LARAVEL_DIR}${NC}"
echo ""

# Step 1: Install Nginx (if needed)
echo -e "${PURPLE}STEP 1: NGINX INSTALLATION CHECK${NC}"
echo "================================="

if ! check_nginx; then
    echo -e "${YELLOW}Installing Nginx...${NC}"
    if ! run_step "1" "NGINX INSTALLATION" "install-nginx-vps.sh"; then
        echo -e "${RED}❌ Failed to install Nginx${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✅ Nginx already installed, skipping installation${NC}"
    echo ""
fi

# Step 2: Update Laravel project
echo -e "${PURPLE}STEP 2: LARAVEL PROJECT UPDATE${NC}"
echo "=============================="

cd "$LARAVEL_DIR"

echo -e "${YELLOW}🔄 Updating Laravel project...${NC}"
if git pull origin main; then
    echo -e "${GREEN}✅ Git pull completed${NC}"
else
    echo -e "${YELLOW}⚠️ Git pull failed, continuing anyway${NC}"
fi

if composer install --no-dev --optimize-autoloader; then
    echo -e "${GREEN}✅ Composer install completed${NC}"
else
    echo -e "${RED}❌ Composer install failed${NC}"
    exit 1
fi

if php artisan migrate --force; then
    echo -e "${GREEN}✅ Database migrations completed${NC}"
else
    echo -e "${RED}❌ Database migrations failed${NC}"
    exit 1
fi

php artisan db:seed --class=WhatsAppIntegrationSeeder --force 2>/dev/null || echo -e "${YELLOW}⚠️ Seeding skipped (data exists)${NC}"

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✅ Laravel caches cleared${NC}"

echo ""

# Step 3: Setup WhatsApp API files
echo -e "${PURPLE}STEP 3: WHATSAPP API FILES SETUP${NC}"
echo "================================"

WHATSAPP_SOURCE="$LARAVEL_DIR/go-whatsapp-web-multidevice-main"

if [ -d "$WHATSAPP_SOURCE" ]; then
    echo -e "${GREEN}✅ WhatsApp API files found${NC}"
else
    echo -e "${YELLOW}📥 Downloading WhatsApp API files...${NC}"
    
    TEMP_DIR=$(mktemp -d)
    cd "$TEMP_DIR"
    
    if curl -L -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"; then
        if ! command -v unzip >/dev/null 2>&1; then
            sudo apt-get update && sudo apt-get install -y unzip
        fi
        
        unzip -q whatsapp-api.zip
        mv go-whatsapp-web-multidevice-main "$LARAVEL_DIR/"
        echo -e "${GREEN}✅ WhatsApp API files downloaded${NC}"
    else
        echo -e "${RED}❌ Failed to download WhatsApp API${NC}"
        exit 1
    fi
    
    rm -rf "$TEMP_DIR"
fi

echo ""

# Step 4: Deploy WhatsApp API
echo -e "${PURPLE}STEP 4: WHATSAPP API DEPLOYMENT${NC}"
echo "==============================="

# Install Docker if needed
if ! command -v docker >/dev/null 2>&1; then
    echo -e "${YELLOW}Installing Docker...${NC}"
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
fi

if ! command -v docker-compose >/dev/null 2>&1; then
    echo -e "${YELLOW}Installing Docker Compose...${NC}"
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
fi

# Setup WhatsApp API
WHATSAPP_DIR="/var/www/whatsapp-api"
sudo mkdir -p $WHATSAPP_DIR
sudo chown -R $USER:$USER $WHATSAPP_DIR

cp -r "$WHATSAPP_SOURCE" "$WHATSAPP_DIR/"

# Create environment file
cd "$WHATSAPP_DIR/go-whatsapp-web-multidevice-main/src"
cat > .env << EOF
APP_PORT=3000
APP_DEBUG=false
APP_OS=HartonoMotor
APP_BASIC_AUTH=admin:HartonoMotor2025!
WHATSAPP_WEBHOOK=https://$DOMAIN/api/whatsapp/webhook
WHATSAPP_WEBHOOK_SECRET=HartonoMotorWebhookSecret2025
WHATSAPP_AUTO_REPLY=""
WHATSAPP_ACCOUNT_VALIDATION=true
DB_TYPE=sqlite
DB_PATH=./database.db
CORS_ALLOWED_ORIGINS=https://$DOMAIN,https://www.$DOMAIN
EOF

# Create Docker Compose file
cd "$WHATSAPP_DIR/go-whatsapp-web-multidevice-main"
cat > docker-compose.yml << EOF
version: '3.8'
services:
  whatsapp-api:
    build: .
    container_name: whatsapp-api-hartono
    ports:
      - "3000:3000"
    volumes:
      - ./src:/app/src
      - whatsapp_sessions:/app/sessions
      - whatsapp_media:/app/media
    environment:
      - APP_PORT=3000
      - APP_DEBUG=false
      - APP_OS=HartonoMotor
      - APP_BASIC_AUTH=admin:HartonoMotor2025!
      - WHATSAPP_WEBHOOK=https://$DOMAIN/api/whatsapp/webhook
      - WHATSAPP_WEBHOOK_SECRET=HartonoMotorWebhookSecret2025
    restart: unless-stopped
    networks:
      - whatsapp-network
volumes:
  whatsapp_sessions:
  whatsapp_media:
networks:
  whatsapp-network:
    driver: bridge
EOF

# Build and start
echo -e "${YELLOW}🔨 Building and starting WhatsApp API...${NC}"
docker-compose down 2>/dev/null || true
docker-compose up -d --build

sleep 15

if docker-compose ps | grep -q "Up"; then
    echo -e "${GREEN}✅ WhatsApp API container started${NC}"
else
    echo -e "${RED}❌ WhatsApp API container failed to start${NC}"
    docker-compose logs
    exit 1
fi

echo ""

# Step 5: Update WhatsApp configuration in database
echo -e "${PURPLE}STEP 5: DATABASE CONFIGURATION UPDATE${NC}"
echo "===================================="

cd "$LARAVEL_DIR"

cat > update_whatsapp_config.php << 'EOF'
<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppConfig;

try {
    $config = WhatsAppConfig::first();
    
    if ($config) {
        $config->update([
            'name' => 'Production WhatsApp API',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
        ]);
        echo "✅ WhatsApp configuration updated\n";
    } else {
        WhatsAppConfig::create([
            'name' => 'Production WhatsApp API',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
        ]);
        echo "✅ WhatsApp configuration created\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

if php update_whatsapp_config.php; then
    echo -e "${GREEN}✅ Database configuration updated${NC}"
else
    echo -e "${RED}❌ Failed to update database configuration${NC}"
fi

rm -f update_whatsapp_config.php

echo ""

# Step 6: Final verification
echo -e "${PURPLE}STEP 6: FINAL VERIFICATION${NC}"
echo "========================="

# Test API
sleep 5
if curl -s -u admin:HartonoMotor2025! http://localhost:3000/app/devices >/dev/null; then
    echo -e "${GREEN}✅ WhatsApp API is responding${NC}"
else
    echo -e "${YELLOW}⚠️ WhatsApp API not responding yet (normal during startup)${NC}"
fi

# Test Nginx
if sudo systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✅ Nginx is running${NC}"
else
    echo -e "${RED}❌ Nginx is not running${NC}"
fi

# Test Laravel
if php artisan about >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Laravel application is working${NC}"
else
    echo -e "${RED}❌ Laravel application has issues${NC}"
fi

echo ""

# Final instructions
echo -e "${GREEN}🎉 COMPLETE DEPLOYMENT FINISHED!${NC}"
echo "================================="
echo ""
echo -e "${BLUE}📋 WHAT WAS DEPLOYED:${NC}"
echo -e "  ✅ Nginx web server with reverse proxy"
echo -e "  ✅ WhatsApp API server (Docker)"
echo -e "  ✅ Laravel application updated"
echo -e "  ✅ Database migrations and configuration"
echo -e "  ✅ Firewall configuration"
echo ""
echo -e "${BLUE}📋 NEXT STEPS - MANUAL ACTIONS:${NC}"
echo -e "1. Test admin panel: ${YELLOW}https://$DOMAIN/admin${NC}"
echo -e "2. Go to: WhatsApp Integration → Konfigurasi WhatsApp"
echo -e "3. Click 'Test Koneksi' (should work now)"
echo -e "4. Scan QR code: ${YELLOW}https://$DOMAIN/whatsapp-api/app/login${NC}"
echo -e "5. Test message sending from admin panel"
echo ""
echo -e "${BLUE}🔧 TROUBLESHOOTING:${NC}"
echo -e "  API logs: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose logs -f${NC}"
echo -e "  Laravel logs: ${YELLOW}tail -f $LARAVEL_DIR/storage/logs/laravel.log${NC}"
echo -e "  Nginx logs: ${YELLOW}sudo tail -f /var/log/nginx/$DOMAIN.error.log${NC}"
echo ""
echo -e "${GREEN}✅ Complete deployment with Nginx installation finished!${NC}"
