#!/bin/bash

# Fixed WhatsApp API Deployment Script for hartonomotor.xyz VPS
# This script automatically detects the correct Laravel directory

set -e  # Exit on any error

echo "🚀 Fixed WhatsApp API Deployment for hartonomotor.xyz"
echo "===================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to find Laravel directory automatically
find_laravel_directory() {
    echo -e "${YELLOW}🔍 Auto-detecting Laravel project directory...${NC}"
    
    # Search for artisan files
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
                echo -e "${GREEN}✅ Hartono Motor Laravel project found: ${LARAVEL_DIR}${NC}"
                return 0
            fi
        fi
    done
    
    # If no specific match, use the first Laravel project found
    LARAVEL_ARRAY=($LARAVEL_DIRS)
    LARAVEL_DIR="${LARAVEL_ARRAY[0]}"
    echo -e "${YELLOW}⚠️ Using first Laravel project found: ${LARAVEL_DIR}${NC}"
    
    return 0
}

# Auto-detect Laravel directory
if ! find_laravel_directory; then
    echo -e "${RED}❌ Could not find Laravel directory${NC}"
    echo -e "${YELLOW}Please run the diagnostic script first: bash diagnose-vps-structure.sh${NC}"
    exit 1
fi

# Configuration with auto-detected paths
WHATSAPP_DIR="/var/www/whatsapp-api"
WHATSAPP_SOURCE="$LARAVEL_DIR/go-whatsapp-web-multidevice-main"
DOMAIN="hartonomotor.xyz"
API_PORT="3000"
API_USER="admin"
API_PASS="HartonoMotor2025!"
WEBHOOK_SECRET="HartonoMotorWebhookSecret2025"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  WhatsApp Source: ${WHATSAPP_SOURCE}"
echo -e "  WhatsApp Deploy Dir: ${WHATSAPP_DIR}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  API Port: ${API_PORT}"
echo ""

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Step 1: Check and install Docker
echo -e "${YELLOW}🔍 Step 1: Checking Docker installation...${NC}"
if ! command_exists docker; then
    echo -e "${RED}Docker not found. Installing Docker...${NC}"
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker $USER
    echo -e "${GREEN}✅ Docker installed successfully${NC}"
else
    echo -e "${GREEN}✅ Docker already installed${NC}"
fi

if ! command_exists docker-compose; then
    echo -e "${RED}Docker Compose not found. Installing...${NC}"
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    echo -e "${GREEN}✅ Docker Compose installed successfully${NC}"
else
    echo -e "${GREEN}✅ Docker Compose already installed${NC}"
fi

# Step 2: Create WhatsApp API directory
echo -e "${YELLOW}📁 Step 2: Setting up WhatsApp API directory...${NC}"
sudo mkdir -p ${WHATSAPP_DIR}
sudo chown -R $USER:$USER ${WHATSAPP_DIR}

# Step 3: Check and copy WhatsApp API files
echo -e "${YELLOW}📋 Step 3: Checking WhatsApp API source files...${NC}"
if [ -d "${WHATSAPP_SOURCE}" ]; then
    echo -e "${GREEN}✅ WhatsApp API source found at: ${WHATSAPP_SOURCE}${NC}"
    
    # Copy files
    echo -e "${BLUE}Copying WhatsApp API files...${NC}"
    cp -r ${WHATSAPP_SOURCE} ${WHATSAPP_DIR}/
    echo -e "${GREEN}✅ WhatsApp API files copied${NC}"
else
    echo -e "${RED}❌ WhatsApp API source not found at: ${WHATSAPP_SOURCE}${NC}"
    echo -e "${YELLOW}Attempting to download WhatsApp API files...${NC}"
    
    # Download WhatsApp API
    TEMP_DIR=$(mktemp -d)
    cd "$TEMP_DIR"
    
    if curl -L -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"; then
        echo -e "${GREEN}✅ Download completed${NC}"
        
        # Install unzip if not available
        if ! command_exists unzip; then
            sudo apt-get update && sudo apt-get install -y unzip
        fi
        
        unzip -q whatsapp-api.zip
        mv go-whatsapp-web-multidevice-main ${WHATSAPP_DIR}/
        echo -e "${GREEN}✅ WhatsApp API files downloaded and installed${NC}"
    else
        echo -e "${RED}❌ Failed to download WhatsApp API${NC}"
        exit 1
    fi
    
    # Cleanup
    rm -rf "$TEMP_DIR"
fi

# Step 4: Create production environment file
echo -e "${YELLOW}⚙️ Step 4: Creating production environment file...${NC}"
cd ${WHATSAPP_DIR}/go-whatsapp-web-multidevice-main/src

cat > .env << EOF
# Application Settings
APP_PORT=${API_PORT}
APP_DEBUG=false
APP_OS=HartonoMotor
APP_BASIC_AUTH=${API_USER}:${API_PASS}

# WhatsApp Settings
WHATSAPP_WEBHOOK=https://${DOMAIN}/api/whatsapp/webhook
WHATSAPP_WEBHOOK_SECRET=${WEBHOOK_SECRET}
WHATSAPP_AUTO_REPLY=""
WHATSAPP_ACCOUNT_VALIDATION=true

# Database
DB_TYPE=sqlite
DB_PATH=./database.db

# Security
CORS_ALLOWED_ORIGINS=https://${DOMAIN},https://www.${DOMAIN}
EOF

echo -e "${GREEN}✅ Environment file created${NC}"

# Step 5: Create production Docker Compose file
echo -e "${YELLOW}🐳 Step 5: Creating Docker Compose configuration...${NC}"
cd ${WHATSAPP_DIR}/go-whatsapp-web-multidevice-main

cat > docker-compose.yml << EOF
version: '3.8'

services:
  whatsapp-api:
    build: .
    container_name: whatsapp-api-hartono
    ports:
      - "${API_PORT}:${API_PORT}"
    volumes:
      - ./src:/app/src
      - whatsapp_sessions:/app/sessions
      - whatsapp_media:/app/media
    environment:
      - APP_PORT=${API_PORT}
      - APP_DEBUG=false
      - APP_OS=HartonoMotor
      - APP_BASIC_AUTH=${API_USER}:${API_PASS}
      - WHATSAPP_WEBHOOK=https://${DOMAIN}/api/whatsapp/webhook
      - WHATSAPP_WEBHOOK_SECRET=${WEBHOOK_SECRET}
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

# Step 6: Build and start WhatsApp API
echo -e "${YELLOW}🔨 Step 6: Building and starting WhatsApp API...${NC}"
docker-compose down 2>/dev/null || true
docker-compose up -d --build

# Wait for container to start
echo -e "${BLUE}⏳ Waiting for container to start...${NC}"
sleep 15

# Check container status
if docker-compose ps | grep -q "Up"; then
    echo -e "${GREEN}✅ WhatsApp API container started successfully${NC}"
else
    echo -e "${RED}❌ Failed to start WhatsApp API container${NC}"
    echo -e "${YELLOW}Container logs:${NC}"
    docker-compose logs
    exit 1
fi

# Step 7: Configure firewall
echo -e "${YELLOW}🔥 Step 7: Configuring firewall...${NC}"
if command_exists ufw; then
    sudo ufw allow ${API_PORT}/tcp
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
    echo -e "${GREEN}✅ Firewall configured${NC}"
else
    echo -e "${YELLOW}⚠️ UFW not found, please configure firewall manually${NC}"
fi

# Step 8: Test API connection
echo -e "${YELLOW}🧪 Step 8: Testing API connection...${NC}"
sleep 5

if curl -s -u ${API_USER}:${API_PASS} http://localhost:${API_PORT}/app/devices >/dev/null; then
    echo -e "${GREEN}✅ WhatsApp API is responding${NC}"
else
    echo -e "${RED}❌ WhatsApp API not responding${NC}"
    echo -e "${YELLOW}Please check logs: docker-compose logs -f${NC}"
fi

# Step 9: Display next steps
echo -e "${GREEN}🎉 WhatsApp API deployment completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Configure Nginx reverse proxy (run: ${YELLOW}sudo bash configure-nginx.sh${NC})"
echo -e "2. Update WhatsApp config in admin panel:"
echo -e "   - URL: ${YELLOW}https://${DOMAIN}/whatsapp-api${NC}"
echo -e "   - Username: ${YELLOW}${API_USER}${NC}"
echo -e "   - Password: ${YELLOW}${API_PASS}${NC}"
echo -e "3. Scan QR code: ${YELLOW}https://${DOMAIN}/whatsapp-api/app/login${NC}"
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo -e "  View logs: ${YELLOW}cd ${WHATSAPP_DIR}/go-whatsapp-web-multidevice-main && docker-compose logs -f${NC}"
echo -e "  Restart: ${YELLOW}cd ${WHATSAPP_DIR}/go-whatsapp-web-multidevice-main && docker-compose restart${NC}"
echo -e "  Stop: ${YELLOW}cd ${WHATSAPP_DIR}/go-whatsapp-web-multidevice-main && docker-compose down${NC}"
echo ""
echo -e "${GREEN}✅ Fixed deployment script completed successfully!${NC}"
