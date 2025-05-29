#!/bin/bash

# Complete WhatsApp Deployment without sudo (Root Mode)
# This script works when running as root or when sudo is not available

echo "🚀 Complete WhatsApp Deployment (No Sudo Mode)"
echo "==============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Starting deployment without sudo...${NC}"
echo -e "  Current User: $(whoami)"
echo -e "  User ID: $(id -u)"
echo ""

# Function to check if running as root
check_privileges() {
    if [ "$(id -u)" -eq 0 ]; then
        echo -e "${GREEN}✅ Running as root${NC}"
        USE_SUDO=""
        return 0
    elif command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
        echo -e "${GREEN}✅ sudo available${NC}"
        USE_SUDO="sudo"
        return 0
    else
        echo -e "${RED}❌ No root access and no sudo${NC}"
        echo -e "${YELLOW}Please run as root: su - then run this script${NC}"
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

# Function to install packages based on OS
install_packages() {
    echo -e "${YELLOW}📦 Installing required packages...${NC}"
    
    # Detect OS
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
    else
        OS=$(uname -s)
    fi
    
    echo -e "${BLUE}Detected OS: ${OS}${NC}"
    
    if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
        # Ubuntu/Debian
        $USE_SUDO apt-get update
        $USE_SUDO apt-get install -y nginx php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd curl unzip docker.io docker-compose
        $USE_SUDO systemctl start nginx php8.2-fpm docker
        $USE_SUDO systemctl enable nginx php8.2-fpm docker
        
    elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]] || [[ "$OS" == *"Rocky"* ]]; then
        # CentOS/RHEL/Rocky
        $USE_SUDO yum update -y
        $USE_SUDO yum install -y nginx php php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-gd curl unzip docker docker-compose
        $USE_SUDO systemctl start nginx php-fpm docker
        $USE_SUDO systemctl enable nginx php-fpm docker
        
    elif [[ "$OS" == *"Amazon Linux"* ]]; then
        # Amazon Linux
        $USE_SUDO yum update -y
        $USE_SUDO amazon-linux-extras install -y nginx1 docker
        $USE_SUDO yum install -y php php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-gd curl unzip
        $USE_SUDO systemctl start nginx php-fpm docker
        $USE_SUDO systemctl enable nginx php-fpm docker
        
    else
        echo -e "${RED}❌ Unsupported OS: ${OS}${NC}"
        return 1
    fi
    
    # Install Docker Compose if not available
    if ! command -v docker-compose >/dev/null 2>&1; then
        $USE_SUDO curl -L "https://github.com/docker/compose/releases/download/v2.20.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
        $USE_SUDO chmod +x /usr/local/bin/docker-compose
    fi
    
    echo -e "${GREEN}✅ Packages installed${NC}"
    return 0
}

# Function to configure Nginx
configure_nginx() {
    echo -e "${YELLOW}⚙️ Configuring Nginx...${NC}"
    
    # Create directories
    $USE_SUDO mkdir -p /etc/nginx/sites-available
    $USE_SUDO mkdir -p /etc/nginx/sites-enabled
    
    # Create Nginx configuration
    $USE_SUDO tee /etc/nginx/sites-available/$DOMAIN > /dev/null << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    
    root $LARAVEL_DIR/public;
    index index.php index.html index.htm;
    
    # WhatsApp API Reverse Proxy
    location /whatsapp-api/ {
        proxy_pass http://localhost:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
    }
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    access_log /var/log/nginx/$DOMAIN.access.log;
    error_log /var/log/nginx/$DOMAIN.error.log;
}
EOF
    
    # Enable site
    $USE_SUDO ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
    $USE_SUDO rm -f /etc/nginx/sites-enabled/default
    
    # Test and reload
    if $USE_SUDO nginx -t; then
        $USE_SUDO systemctl reload nginx
        echo -e "${GREEN}✅ Nginx configured${NC}"
        return 0
    else
        echo -e "${RED}❌ Nginx configuration error${NC}"
        return 1
    fi
}

# Function to setup WhatsApp API
setup_whatsapp_api() {
    echo -e "${YELLOW}📱 Setting up WhatsApp API...${NC}"
    
    # Download WhatsApp API if not exists
    WHATSAPP_SOURCE="$LARAVEL_DIR/go-whatsapp-web-multidevice-main"
    
    if [ ! -d "$WHATSAPP_SOURCE" ]; then
        echo -e "${YELLOW}Downloading WhatsApp API...${NC}"
        cd "$LARAVEL_DIR"
        curl -L -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"
        unzip -q whatsapp-api.zip
        rm whatsapp-api.zip
    fi
    
    # Setup deployment directory
    WHATSAPP_DIR="/var/www/whatsapp-api"
    $USE_SUDO mkdir -p $WHATSAPP_DIR
    $USE_SUDO cp -r "$WHATSAPP_SOURCE" "$WHATSAPP_DIR/"
    
    # Create environment file
    cd "$WHATSAPP_DIR/go-whatsapp-web-multidevice-main/src"
    $USE_SUDO tee .env > /dev/null << EOF
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
    $USE_SUDO tee docker-compose.yml > /dev/null << EOF
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
    echo -e "${YELLOW}Building and starting WhatsApp API...${NC}"
    $USE_SUDO docker-compose down 2>/dev/null || true
    $USE_SUDO docker-compose up -d --build
    
    sleep 15
    
    if $USE_SUDO docker-compose ps | grep -q "Up"; then
        echo -e "${GREEN}✅ WhatsApp API started${NC}"
        return 0
    else
        echo -e "${RED}❌ WhatsApp API failed to start${NC}"
        return 1
    fi
}

# Function to update Laravel
update_laravel() {
    echo -e "${YELLOW}🔄 Updating Laravel project...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Git pull
    git pull origin main 2>/dev/null || echo "Git pull skipped"
    
    # Composer install
    if command -v composer >/dev/null 2>&1; then
        composer install --no-dev --optimize-autoloader
    else
        echo -e "${YELLOW}Composer not found, skipping...${NC}"
    fi
    
    # Laravel commands
    php artisan migrate --force 2>/dev/null || echo "Migration skipped"
    php artisan db:seed --class=WhatsAppIntegrationSeeder --force 2>/dev/null || echo "Seeding skipped"
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    php artisan cache:clear
    
    # Set permissions
    $USE_SUDO chown -R www-data:www-data "$LARAVEL_DIR" 2>/dev/null || true
    $USE_SUDO chmod -R 755 "$LARAVEL_DIR"
    $USE_SUDO chmod -R 775 "$LARAVEL_DIR/storage" "$LARAVEL_DIR/bootstrap/cache" 2>/dev/null || true
    
    echo -e "${GREEN}✅ Laravel updated${NC}"
}

# Main execution
echo -e "${BLUE}Starting complete deployment...${NC}"
echo ""

# Step 1: Check privileges
if ! check_privileges; then
    exit 1
fi
echo ""

# Step 2: Find Laravel directory
if ! find_laravel_directory; then
    exit 1
fi
echo ""

# Step 3: Install packages
if ! install_packages; then
    exit 1
fi
echo ""

# Step 4: Configure Nginx
if ! configure_nginx; then
    exit 1
fi
echo ""

# Step 5: Update Laravel
update_laravel
echo ""

# Step 6: Setup WhatsApp API
if ! setup_whatsapp_api; then
    exit 1
fi

# Step 7: Final verification
echo ""
echo -e "${YELLOW}🧪 Final verification...${NC}"

# Test services
if systemctl is-active --quiet nginx 2>/dev/null || pgrep nginx >/dev/null; then
    echo -e "${GREEN}✅ Nginx is running${NC}"
else
    echo -e "${RED}❌ Nginx is not running${NC}"
fi

if curl -s -u admin:HartonoMotor2025! http://localhost:3000/app/devices >/dev/null 2>&1; then
    echo -e "${GREEN}✅ WhatsApp API is responding${NC}"
else
    echo -e "${YELLOW}⚠️ WhatsApp API not responding yet${NC}"
fi

# Final message
echo ""
echo -e "${GREEN}🎉 DEPLOYMENT COMPLETED!${NC}"
echo "======================="
echo ""
echo -e "${BLUE}📋 NEXT STEPS:${NC}"
echo -e "1. Test admin panel: ${YELLOW}https://$DOMAIN/admin${NC}"
echo -e "2. Go to: WhatsApp Integration → Konfigurasi WhatsApp"
echo -e "3. Test connection (should work now)"
echo -e "4. Scan QR code: ${YELLOW}https://$DOMAIN/whatsapp-api/app/login${NC}"
echo ""
echo -e "${BLUE}🔧 TROUBLESHOOTING:${NC}"
echo -e "  API logs: ${YELLOW}cd /var/www/whatsapp-api/go-whatsapp-web-multidevice-main && docker-compose logs -f${NC}"
echo -e "  Laravel logs: ${YELLOW}tail -f $LARAVEL_DIR/storage/logs/laravel.log${NC}"
echo -e "  Nginx logs: ${YELLOW}tail -f /var/log/nginx/$DOMAIN.error.log${NC}"
echo ""
echo -e "${GREEN}✅ Complete deployment without sudo finished!${NC}"
