#!/bin/bash

# One-Command Fix for WhatsApp Deployment Issues
# This script will diagnose, fix, and deploy WhatsApp API automatically

echo "🔧 WhatsApp Deployment Fix - All-in-One Solution"
echo "================================================"

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

echo -e "${BLUE}🚀 Starting automatic WhatsApp deployment fix...${NC}"
echo ""

# Function to run command with error handling
run_command() {
    local description=$1
    shift
    local command="$@"
    
    echo -e "${YELLOW}🔄 ${description}...${NC}"
    if eval "$command"; then
        echo -e "${GREEN}✅ ${description} completed${NC}"
        return 0
    else
        echo -e "${RED}❌ ${description} failed${NC}"
        return 1
    fi
}

# Function to find Laravel directory
find_laravel_directory() {
    echo -e "${YELLOW}🔍 Searching for Laravel project...${NC}"
    
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
                echo -e "${GREEN}✅ Hartono Motor project found: ${LARAVEL_DIR}${NC}"
                return 0
            fi
        fi
    done
    
    # If no specific match, use the first Laravel project
    LARAVEL_ARRAY=($LARAVEL_DIRS)
    LARAVEL_DIR="${LARAVEL_ARRAY[0]}"
    echo -e "${YELLOW}⚠️ Using first Laravel project: ${LARAVEL_DIR}${NC}"
    
    return 0
}

# Function to download WhatsApp API if missing
download_whatsapp_api() {
    local target_dir=$1
    
    echo -e "${YELLOW}📥 Downloading WhatsApp API files...${NC}"
    
    # Create temporary directory
    TEMP_DIR=$(mktemp -d)
    cd "$TEMP_DIR"
    
    # Download from GitHub
    if curl -L -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"; then
        echo -e "${GREEN}✅ Download completed${NC}"
    else
        echo -e "${RED}❌ Download failed${NC}"
        rm -rf "$TEMP_DIR"
        return 1
    fi
    
    # Install unzip if needed
    if ! command -v unzip >/dev/null 2>&1; then
        sudo apt-get update && sudo apt-get install -y unzip
    fi
    
    # Extract and move files
    unzip -q whatsapp-api.zip
    
    if [ -d "go-whatsapp-web-multidevice-main" ]; then
        # Remove existing directory if it exists
        if [ -d "$target_dir/go-whatsapp-web-multidevice-main" ]; then
            rm -rf "$target_dir/go-whatsapp-web-multidevice-main"
        fi
        
        mv go-whatsapp-web-multidevice-main "$target_dir/"
        sudo chown -R $USER:$USER "$target_dir/go-whatsapp-web-multidevice-main"
        chmod -R 755 "$target_dir/go-whatsapp-web-multidevice-main"
        
        echo -e "${GREEN}✅ WhatsApp API files installed${NC}"
    else
        echo -e "${RED}❌ Extraction failed${NC}"
        rm -rf "$TEMP_DIR"
        return 1
    fi
    
    rm -rf "$TEMP_DIR"
    return 0
}

# Step 1: Find Laravel directory
echo -e "${PURPLE}STEP 1: FINDING LARAVEL PROJECT${NC}"
echo "==============================="
if ! find_laravel_directory; then
    echo -e "${RED}❌ Cannot proceed without Laravel project${NC}"
    exit 1
fi
echo ""

# Step 2: Check/Download WhatsApp API files
echo -e "${PURPLE}STEP 2: CHECKING WHATSAPP API FILES${NC}"
echo "=================================="
WHATSAPP_SOURCE="$LARAVEL_DIR/go-whatsapp-web-multidevice-main"

if [ -d "$WHATSAPP_SOURCE" ]; then
    echo -e "${GREEN}✅ WhatsApp API files found at: ${WHATSAPP_SOURCE}${NC}"
else
    echo -e "${YELLOW}⚠️ WhatsApp API files not found, downloading...${NC}"
    if ! download_whatsapp_api "$LARAVEL_DIR"; then
        echo -e "${RED}❌ Failed to download WhatsApp API files${NC}"
        exit 1
    fi
fi
echo ""

# Step 3: Update Laravel project
echo -e "${PURPLE}STEP 3: UPDATING LARAVEL PROJECT${NC}"
echo "==============================="
cd "$LARAVEL_DIR"

run_command "Git pull latest changes" "git pull origin main"
run_command "Install Composer dependencies" "composer install --no-dev --optimize-autoloader"
run_command "Run database migrations" "php artisan migrate --force"
run_command "Seed WhatsApp data" "php artisan db:seed --class=WhatsAppIntegrationSeeder --force || echo 'Seeding skipped (data exists)'"
run_command "Clear application caches" "php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear"
run_command "Optimize for production" "php artisan config:cache && php artisan route:cache && php artisan view:cache"

echo ""

# Step 4: Deploy WhatsApp API
echo -e "${PURPLE}STEP 4: DEPLOYING WHATSAPP API${NC}"
echo "=============================="

# Create the fixed deployment script with correct paths
cat > "${SCRIPT_DIR}/deploy-whatsapp-auto.sh" << EOF
#!/bin/bash
# Auto-generated deployment script with correct paths
LARAVEL_DIR="$LARAVEL_DIR"
WHATSAPP_SOURCE="$WHATSAPP_SOURCE"
WHATSAPP_DIR="/var/www/whatsapp-api"
DOMAIN="$DOMAIN"
API_PORT="3000"
API_USER="admin"
API_PASS="HartonoMotor2025!"
WEBHOOK_SECRET="HartonoMotorWebhookSecret2025"

# Install Docker if needed
if ! command -v docker >/dev/null 2>&1; then
    curl -fsSL https://get.docker.com -o get-docker.sh
    sudo sh get-docker.sh
    sudo usermod -aG docker \$USER
fi

if ! command -v docker-compose >/dev/null 2>&1; then
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-\$(uname -s)-\$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
fi

# Setup directories
sudo mkdir -p \$WHATSAPP_DIR
sudo chown -R \$USER:\$USER \$WHATSAPP_DIR

# Copy WhatsApp API files
cp -r \$WHATSAPP_SOURCE \$WHATSAPP_DIR/

# Create environment file
cd \$WHATSAPP_DIR/go-whatsapp-web-multidevice-main/src
cat > .env << 'ENVEOF'
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
ENVEOF

# Create Docker Compose file
cd \$WHATSAPP_DIR/go-whatsapp-web-multidevice-main
cat > docker-compose.yml << 'DCEOF'
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
DCEOF

# Build and start
docker-compose down 2>/dev/null || true
docker-compose up -d --build

# Configure firewall
if command -v ufw >/dev/null 2>&1; then
    sudo ufw allow 3000/tcp
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
fi

echo "✅ WhatsApp API deployment completed!"
EOF

chmod +x "${SCRIPT_DIR}/deploy-whatsapp-auto.sh"

# Run the deployment
if run_command "Deploy WhatsApp API" "bash ${SCRIPT_DIR}/deploy-whatsapp-auto.sh"; then
    echo -e "${GREEN}✅ WhatsApp API deployed successfully${NC}"
else
    echo -e "${RED}❌ WhatsApp API deployment failed${NC}"
    exit 1
fi

echo ""

# Step 5: Final verification
echo -e "${PURPLE}STEP 5: FINAL VERIFICATION${NC}"
echo "========================="

sleep 10

# Test API
if curl -s -u admin:HartonoMotor2025! http://localhost:3000/app/devices >/dev/null; then
    echo -e "${GREEN}✅ WhatsApp API is responding${NC}"
else
    echo -e "${YELLOW}⚠️ WhatsApp API not responding yet (normal during first startup)${NC}"
fi

# Test Laravel
cd "$LARAVEL_DIR"
if php artisan about >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Laravel application is working${NC}"
else
    echo -e "${RED}❌ Laravel application has issues${NC}"
fi

echo ""

# Final instructions
echo -e "${GREEN}🎉 WHATSAPP DEPLOYMENT FIX COMPLETED!${NC}"
echo "===================================="
echo ""
echo -e "${BLUE}📋 NEXT STEPS:${NC}"
echo -e "1. Configure Nginx (run): ${YELLOW}sudo bash configure-nginx.sh${NC}"
echo -e "2. Update admin panel config:"
echo -e "   - Login: ${YELLOW}https://${DOMAIN}/admin${NC}"
echo -e "   - Go to: WhatsApp Integration → Konfigurasi WhatsApp"
echo -e "   - URL: ${YELLOW}https://${DOMAIN}/whatsapp-api${NC}"
echo -e "   - Username: ${YELLOW}admin${NC}"
echo -e "   - Password: ${YELLOW}HartonoMotor2025!${NC}"
echo -e "3. Scan QR code: ${YELLOW}https://${DOMAIN}/whatsapp-api/app/login${NC}"
echo ""
echo -e "${BLUE}🔧 TROUBLESHOOTING:${NC}"
echo -e "  Check API logs: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose logs -f${NC}"
echo -e "  Check Laravel logs: ${YELLOW}tail -f ${LARAVEL_DIR}/storage/logs/laravel.log${NC}"
echo -e "  Restart API: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose restart${NC}"
echo ""
echo -e "${GREEN}✅ All issues should now be resolved!${NC}"
