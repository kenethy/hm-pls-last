#!/bin/bash

# Fix Script for Laravel in /hm/new Directory
# This script specifically targets the /hm/new Laravel installation

echo "🔧 Fixing Laravel Application in /hm/new Directory"
echo "=================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LARAVEL_DIR="/hm/new"
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Current Host: $(hostname)"
echo -e "  Current User: $(whoami)"
echo ""

# Function to check if we're in VPS host (not container)
check_environment() {
    echo -e "${YELLOW}🔍 Checking environment...${NC}"
    
    HOSTNAME=$(hostname)
    if [[ "$HOSTNAME" =~ ^[a-f0-9]{12}$ ]]; then
        echo -e "${RED}❌ You appear to be inside a Docker container!${NC}"
        echo -e "${YELLOW}Container ID: $HOSTNAME${NC}"
        echo -e "${BLUE}Please exit the container first:${NC}"
        echo -e "  ${YELLOW}exit${NC}"
        echo -e "  ${YELLOW}# Then run this script on VPS host${NC}"
        return 1
    else
        echo -e "${GREEN}✅ Running on VPS host: $HOSTNAME${NC}"
    fi
    
    # Check if we can access Docker (should be available on VPS host)
    if command -v docker >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Docker command available (good - you're on VPS host)${NC}"
    else
        echo -e "${YELLOW}⚠️ Docker not available (might be okay)${NC}"
    fi
    
    echo ""
    return 0
}

# Function to verify Laravel directory
verify_laravel_directory() {
    echo -e "${YELLOW}🔍 Verifying Laravel directory...${NC}"
    
    if [ ! -d "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Directory $LARAVEL_DIR does not exist${NC}"
        
        # Search for alternative locations
        echo -e "${YELLOW}Searching for Laravel projects...${NC}"
        find /home /var/www /opt /hm -name "artisan" -type f 2>/dev/null | while read artisan_path; do
            laravel_dir=$(dirname "$artisan_path")
            echo -e "${BLUE}Found Laravel at: $laravel_dir${NC}"
        done
        
        return 1
    fi
    
    cd "$LARAVEL_DIR"
    
    # Check Laravel files
    if [ -f "artisan" ]; then
        echo -e "${GREEN}✅ artisan file found${NC}"
    else
        echo -e "${RED}❌ artisan file missing${NC}"
        return 1
    fi
    
    if [ -f "composer.json" ]; then
        echo -e "${GREEN}✅ composer.json found${NC}"
        
        # Check if it's Laravel
        if grep -q "laravel/framework" composer.json; then
            echo -e "${GREEN}✅ This is a Laravel project${NC}"
        else
            echo -e "${YELLOW}⚠️ This might not be a Laravel project${NC}"
        fi
    else
        echo -e "${RED}❌ composer.json missing${NC}"
    fi
    
    echo ""
    return 0
}

# Function to fix APP_KEY issue
fix_app_key() {
    echo -e "${YELLOW}🔧 Fixing APP_KEY issue...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Check .env file
    if [ ! -f ".env" ]; then
        echo -e "${YELLOW}Creating .env file...${NC}"
        if [ -f ".env.example" ]; then
            cp .env.example .env
            echo -e "${GREEN}✅ .env created from .env.example${NC}"
        else
            echo -e "${RED}❌ .env.example not found${NC}"
            # Create basic .env
            cat > .env << 'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://hartonomotor.xyz

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"
EOF
            echo -e "${GREEN}✅ Basic .env file created${NC}"
        fi
    fi
    
    # Generate APP_KEY
    echo -e "${YELLOW}Generating APP_KEY...${NC}"
    
    # Method 1: Using artisan
    if php artisan key:generate --force 2>/dev/null; then
        echo -e "${GREEN}✅ APP_KEY generated using artisan${NC}"
    else
        echo -e "${YELLOW}⚠️ Artisan failed, generating manually...${NC}"
        
        # Method 2: Manual generation
        APP_KEY="base64:$(openssl rand -base64 32)"
        sed -i "s/APP_KEY=.*/APP_KEY=$APP_KEY/" .env
        echo -e "${GREEN}✅ APP_KEY generated manually${NC}"
    fi
    
    # Verify APP_KEY
    if grep -q "APP_KEY=base64:" .env; then
        echo -e "${GREEN}✅ APP_KEY is properly set${NC}"
    else
        echo -e "${RED}❌ APP_KEY still not set properly${NC}"
        echo -e "${BLUE}Current APP_KEY line:${NC}"
        grep "APP_KEY=" .env || echo "APP_KEY line not found"
    fi
    
    echo ""
}

# Function to fix file permissions
fix_permissions() {
    echo -e "${YELLOW}🔧 Fixing file permissions...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Set ownership
    chown -R www-data:www-data . 2>/dev/null || chown -R nginx:nginx . 2>/dev/null || echo "Could not change ownership"
    
    # Set directory permissions
    chmod -R 755 .
    
    # Set writable permissions for Laravel directories
    if [ -d "storage" ]; then
        chmod -R 775 storage
        echo -e "${GREEN}✅ storage permissions set${NC}"
    fi
    
    if [ -d "bootstrap/cache" ]; then
        chmod -R 775 bootstrap/cache
        echo -e "${GREEN}✅ bootstrap/cache permissions set${NC}"
    fi
    
    # Create missing directories
    mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
    chmod -R 775 storage
    
    echo -e "${GREEN}✅ File permissions fixed${NC}"
    echo ""
}

# Function to clear Laravel caches
clear_laravel_caches() {
    echo -e "${YELLOW}🔧 Clearing Laravel caches...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Clear all caches
    php artisan config:clear 2>/dev/null && echo -e "${GREEN}✅ Config cache cleared${NC}"
    php artisan route:clear 2>/dev/null && echo -e "${GREEN}✅ Route cache cleared${NC}"
    php artisan view:clear 2>/dev/null && echo -e "${GREEN}✅ View cache cleared${NC}"
    php artisan cache:clear 2>/dev/null && echo -e "${GREEN}✅ Application cache cleared${NC}"
    
    # Clear compiled files
    if [ -f "bootstrap/cache/config.php" ]; then
        rm bootstrap/cache/config.php
        echo -e "${GREEN}✅ Compiled config removed${NC}"
    fi
    
    if [ -f "bootstrap/cache/routes.php" ]; then
        rm bootstrap/cache/routes.php
        echo -e "${GREEN}✅ Compiled routes removed${NC}"
    fi
    
    echo ""
}

# Function to create/fix Nginx configuration
fix_nginx_config() {
    echo -e "${YELLOW}🔧 Creating Nginx configuration...${NC}"
    
    # Create Nginx site configuration
    cat > /etc/nginx/sites-available/$DOMAIN << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    
    root $LARAVEL_DIR/public;
    index index.php index.html index.htm;
    
    # Laravel Application Routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # PHP-FPM Configuration
    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        
        # Increase timeouts
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
    }
    
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
    }
    
    # Static files
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Logs
    access_log /var/log/nginx/$DOMAIN.access.log;
    error_log /var/log/nginx/$DOMAIN.error.log;
}
EOF
    
    # Enable site
    ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    echo -e "${GREEN}✅ Nginx configuration created${NC}"
    
    # Test configuration
    if nginx -t 2>/dev/null; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
        
        # Reload Nginx
        if systemctl reload nginx 2>/dev/null; then
            echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
        else
            echo -e "${RED}❌ Failed to reload Nginx${NC}"
            systemctl status nginx
        fi
    else
        echo -e "${RED}❌ Nginx configuration has errors${NC}"
        nginx -t
    fi
    
    echo ""
}

# Function to test the fix
test_application() {
    echo -e "${YELLOW}🧪 Testing the application...${NC}"
    
    # Test localhost
    echo -e "${BLUE}Testing localhost...${NC}"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
    echo -e "  HTTP Code: $HTTP_CODE"
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ localhost working${NC}"
    else
        echo -e "${RED}❌ localhost not working${NC}"
    fi
    
    # Test domain
    echo -e "${BLUE}Testing $DOMAIN...${NC}"
    DOMAIN_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
    echo -e "  HTTP Code: $DOMAIN_CODE"
    
    if [ "$DOMAIN_CODE" = "200" ]; then
        echo -e "${GREEN}✅ $DOMAIN working${NC}"
    else
        echo -e "${RED}❌ $DOMAIN not working${NC}"
    fi
    
    # Test Laravel specifically
    echo -e "${BLUE}Testing Laravel application...${NC}"
    cd "$LARAVEL_DIR"
    
    if php artisan --version 2>/dev/null; then
        echo -e "${GREEN}✅ Laravel artisan working${NC}"
    else
        echo -e "${RED}❌ Laravel artisan not working${NC}"
    fi
    
    echo ""
}

# Main execution
echo -e "${BLUE}Starting fix for /hm/new Laravel application...${NC}"
echo ""

# Check environment
if ! check_environment; then
    exit 1
fi

# Verify Laravel directory
if ! verify_laravel_directory; then
    exit 1
fi

# Apply fixes
fix_app_key
fix_permissions
clear_laravel_caches
fix_nginx_config

# Test the application
test_application

echo -e "${GREEN}🎉 Fix completed for /hm/new Laravel application!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Visit: http://$DOMAIN"
echo -e "2. Check logs if issues persist:"
echo -e "   tail -f $LARAVEL_DIR/storage/logs/laravel.log"
echo -e "   tail -f /var/log/nginx/error.log"
echo -e "3. If working, disable debug mode:"
echo -e "   Edit $LARAVEL_DIR/.env"
echo -e "   Set: APP_DEBUG=false"
echo ""
echo -e "${GREEN}✅ Script completed!${NC}"
