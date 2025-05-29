#!/bin/bash

# WhatsApp Deployment for Current Directory
# This script assumes you're already in the Laravel project directory

echo "📱 WhatsApp API Deployment for Current Directory"
echo "==============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LARAVEL_DIR="$(pwd)"
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Current Directory: ${LARAVEL_DIR}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  VPS Host: $(hostname)"
echo ""

# Function to verify this is Laravel directory
verify_laravel() {
    echo -e "${YELLOW}🔍 Verifying this is a Laravel project...${NC}"
    
    if [ -f "artisan" ]; then
        echo -e "${GREEN}✅ artisan file found${NC}"
    else
        echo -e "${RED}❌ artisan file not found - this is not a Laravel directory${NC}"
        return 1
    fi
    
    if [ -f "composer.json" ]; then
        echo -e "${GREEN}✅ composer.json found${NC}"
        
        if grep -q "laravel/framework" composer.json; then
            echo -e "${GREEN}✅ This is a Laravel project${NC}"
        else
            echo -e "${RED}❌ This is not a Laravel project${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ composer.json not found${NC}"
        return 1
    fi
    
    if [ -d "public" ]; then
        echo -e "${GREEN}✅ public directory found${NC}"
    else
        echo -e "${RED}❌ public directory not found${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Laravel project verified in: ${LARAVEL_DIR}${NC}"
    echo ""
    return 0
}

# Function to download WhatsApp API
download_whatsapp_api() {
    echo -e "${YELLOW}📥 Downloading WhatsApp API...${NC}"
    
    if [ -d "go-whatsapp-web-multidevice-main" ]; then
        echo -e "${GREEN}✅ WhatsApp API already exists${NC}"
        return 0
    fi
    
    # Download WhatsApp API
    if curl -L -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"; then
        echo -e "${GREEN}✅ Download completed${NC}"
    else
        echo -e "${RED}❌ Download failed${NC}"
        return 1
    fi
    
    # Extract
    if command -v unzip >/dev/null 2>&1; then
        unzip -q whatsapp-api.zip
        rm whatsapp-api.zip
        echo -e "${GREEN}✅ WhatsApp API extracted${NC}"
    else
        echo -e "${RED}❌ unzip command not found${NC}"
        return 1
    fi
    
    echo ""
    return 0
}

# Function to setup WhatsApp API deployment
setup_whatsapp_deployment() {
    echo -e "${YELLOW}🔧 Setting up WhatsApp API deployment...${NC}"
    
    # Create deployment directory
    WHATSAPP_DIR="/var/www/whatsapp-api"
    mkdir -p "$WHATSAPP_DIR"
    
    # Copy WhatsApp API files
    if [ -d "go-whatsapp-web-multidevice-main" ]; then
        cp -r go-whatsapp-web-multidevice-main "$WHATSAPP_DIR/"
        echo -e "${GREEN}✅ WhatsApp API files copied to deployment directory${NC}"
    else
        echo -e "${RED}❌ WhatsApp API source directory not found${NC}"
        return 1
    fi
    
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
    
    echo -e "${GREEN}✅ Environment file created${NC}"
    
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
    
    echo -e "${GREEN}✅ Docker Compose file created${NC}"
    echo ""
    return 0
}

# Function to build and start WhatsApp API
start_whatsapp_api() {
    echo -e "${YELLOW}🚀 Building and starting WhatsApp API...${NC}"
    
    cd "$WHATSAPP_DIR/go-whatsapp-web-multidevice-main"
    
    # Stop existing container if running
    docker-compose down 2>/dev/null || true
    
    # Build and start
    if docker-compose up -d --build; then
        echo -e "${GREEN}✅ WhatsApp API container started${NC}"
    else
        echo -e "${RED}❌ Failed to start WhatsApp API container${NC}"
        return 1
    fi
    
    # Wait for container to start
    echo -e "${BLUE}⏳ Waiting for container to start...${NC}"
    sleep 15
    
    # Check container status
    if docker-compose ps | grep -q "Up"; then
        echo -e "${GREEN}✅ WhatsApp API is running${NC}"
    else
        echo -e "${RED}❌ WhatsApp API container failed to start${NC}"
        echo -e "${YELLOW}Container logs:${NC}"
        docker-compose logs
        return 1
    fi
    
    echo ""
    return 0
}

# Function to update Laravel configuration
update_laravel_config() {
    echo -e "${YELLOW}⚙️ Updating Laravel WhatsApp configuration...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Update WhatsApp configuration in database
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
}
EOF
    
    if php update_whatsapp_config.php 2>/dev/null; then
        echo -e "${GREEN}✅ Laravel WhatsApp configuration updated${NC}"
    else
        echo -e "${YELLOW}⚠️ Laravel configuration update skipped (database may not be ready)${NC}"
    fi
    
    rm -f update_whatsapp_config.php
    echo ""
}

# Function to test WhatsApp API
test_whatsapp_api() {
    echo -e "${YELLOW}🧪 Testing WhatsApp API...${NC}"
    
    # Test API connection
    sleep 5
    
    if curl -s -u admin:HartonoMotor2025! http://localhost:3000/app/devices >/dev/null; then
        echo -e "${GREEN}✅ WhatsApp API is responding${NC}"
    else
        echo -e "${RED}❌ WhatsApp API not responding${NC}"
        echo -e "${YELLOW}Check logs: cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose logs${NC}"
    fi
    
    # Test via domain (if Nginx is configured)
    if curl -s -k https://$DOMAIN/whatsapp-api/app/devices >/dev/null 2>&1; then
        echo -e "${GREEN}✅ WhatsApp API accessible via domain${NC}"
    else
        echo -e "${YELLOW}⚠️ WhatsApp API not accessible via domain (Nginx may need configuration)${NC}"
    fi
    
    echo ""
}

# Function to show next steps
show_next_steps() {
    echo -e "${GREEN}🎉 WhatsApp API Deployment Completed!${NC}"
    echo "===================================="
    echo ""
    
    echo -e "${BLUE}📋 What was deployed:${NC}"
    echo -e "  ✅ WhatsApp API server running on port 3000"
    echo -e "  ✅ Docker container: whatsapp-api-hartono"
    echo -e "  ✅ Laravel configuration updated"
    echo -e "  ✅ Webhook endpoint configured"
    echo ""
    
    echo -e "${BLUE}📋 Next Steps:${NC}"
    echo -e "1. ${YELLOW}Configure Nginx reverse proxy:${NC}"
    echo -e "   Add this to your Nginx config if not already done:"
    echo -e "   ${BLUE}location /whatsapp-api/ {${NC}"
    echo -e "   ${BLUE}    proxy_pass http://localhost:3000/;${NC}"
    echo -e "   ${BLUE}    proxy_set_header Host \$host;${NC}"
    echo -e "   ${BLUE}}${NC}"
    echo ""
    echo -e "2. ${YELLOW}Test admin panel:${NC}"
    echo -e "   Visit: https://$DOMAIN/admin"
    echo -e "   Go to: WhatsApp Integration → Konfigurasi WhatsApp"
    echo -e "   Click: Test Koneksi"
    echo ""
    echo -e "3. ${YELLOW}Scan QR code:${NC}"
    echo -e "   Visit: https://$DOMAIN/whatsapp-api/app/login"
    echo -e "   Scan QR code with WhatsApp mobile app"
    echo ""
    echo -e "4. ${YELLOW}Test message sending:${NC}"
    echo -e "   Use admin panel to send test messages"
    echo -e "   Complete a service to test auto follow-up"
    echo ""
    
    echo -e "${BLUE}🔧 Troubleshooting:${NC}"
    echo -e "  View API logs: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose logs -f${NC}"
    echo -e "  Restart API: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose restart${NC}"
    echo -e "  Check status: ${YELLOW}docker ps | grep whatsapp${NC}"
    echo ""
    
    echo -e "${BLUE}🔗 Important URLs:${NC}"
    echo -e "  Admin Panel: ${YELLOW}https://$DOMAIN/admin${NC}"
    echo -e "  WhatsApp QR: ${YELLOW}https://$DOMAIN/whatsapp-api/app/login${NC}"
    echo -e "  API Status: ${YELLOW}https://$DOMAIN/whatsapp-api/app/devices${NC}"
    echo ""
}

# Main execution
echo -e "${BLUE}Starting WhatsApp deployment in current directory...${NC}"
echo ""

# Execute all steps
if verify_laravel; then
    if download_whatsapp_api; then
        if setup_whatsapp_deployment; then
            if start_whatsapp_api; then
                update_laravel_config
                test_whatsapp_api
                show_next_steps
            else
                echo -e "${RED}❌ Failed to start WhatsApp API${NC}"
                exit 1
            fi
        else
            echo -e "${RED}❌ Failed to setup WhatsApp deployment${NC}"
            exit 1
        fi
    else
        echo -e "${RED}❌ Failed to download WhatsApp API${NC}"
        exit 1
    fi
else
    echo -e "${RED}❌ This is not a Laravel directory${NC}"
    echo -e "${YELLOW}Please run this script from your Laravel project directory${NC}"
    exit 1
fi

echo -e "${GREEN}✅ WhatsApp deployment script completed successfully!${NC}"
