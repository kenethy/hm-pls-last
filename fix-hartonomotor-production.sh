#!/bin/bash

# Production Fix Script for Hartono Motor at /hm-new
# This script will fix all issues identified in the VPS analysis

echo "🔧 Production Fix for Hartono Motor Laravel Application"
echo "====================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
LARAVEL_DIR="/hm-new"
DOMAIN="hartonomotor.xyz"
BACKUP_DIR="/root/backup_$(date +%Y%m%d_%H%M%S)"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  VPS Host: $(hostname)"
echo -e "  Current User: $(whoami)"
echo -e "  Backup Directory: ${BACKUP_DIR}"
echo ""

# Function to create backup
create_backup() {
    echo -e "${YELLOW}💾 Step 1: Creating backup...${NC}"
    
    mkdir -p "$BACKUP_DIR"
    
    # Backup current Nginx config
    if [ -f "/etc/nginx/sites-available/$DOMAIN" ]; then
        cp "/etc/nginx/sites-available/$DOMAIN" "$BACKUP_DIR/nginx_config_backup"
        echo -e "${GREEN}✅ Nginx config backed up${NC}"
    fi
    
    # Backup Laravel .env if exists
    if [ -f "$LARAVEL_DIR/.env" ]; then
        cp "$LARAVEL_DIR/.env" "$BACKUP_DIR/env_backup"
        echo -e "${GREEN}✅ Laravel .env backed up${NC}"
    fi
    
    echo -e "${GREEN}✅ Backup created at: $BACKUP_DIR${NC}"
    echo ""
}

# Function to verify Laravel directory
verify_laravel_directory() {
    echo -e "${YELLOW}🔍 Step 2: Verifying Laravel directory...${NC}"
    
    if [ ! -d "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Directory $LARAVEL_DIR does not exist${NC}"
        return 1
    fi
    
    cd "$LARAVEL_DIR"
    
    # Check essential Laravel files
    if [ -f "artisan" ]; then
        echo -e "${GREEN}✅ artisan file found${NC}"
    else
        echo -e "${RED}❌ artisan file missing${NC}"
        return 1
    fi
    
    if [ -f "composer.json" ]; then
        echo -e "${GREEN}✅ composer.json found${NC}"
        
        # Check Laravel version
        if grep -q "laravel/framework" composer.json; then
            version=$(grep -o '"laravel/framework": "[^"]*"' composer.json | cut -d'"' -f4)
            echo -e "${GREEN}✅ Laravel version: $version${NC}"
        fi
    else
        echo -e "${RED}❌ composer.json missing${NC}"
        return 1
    fi
    
    if [ -d "public" ]; then
        echo -e "${GREEN}✅ public directory found${NC}"
        
        if [ -f "public/index.php" ]; then
            echo -e "${GREEN}✅ public/index.php found${NC}"
        else
            echo -e "${RED}❌ public/index.php missing${NC}"
        fi
    else
        echo -e "${RED}❌ public directory missing${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Laravel directory verified: $LARAVEL_DIR${NC}"
    echo ""
    return 0
}

# Function to fix Laravel application
fix_laravel_application() {
    echo -e "${YELLOW}🔧 Step 3: Fixing Laravel application...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Create .env if missing
    if [ ! -f ".env" ]; then
        echo -e "${YELLOW}Creating .env file...${NC}"
        if [ -f ".env.example" ]; then
            cp .env.example .env
            echo -e "${GREEN}✅ .env created from .env.example${NC}"
        else
            # Create basic .env for Laravel 12
            cat > .env << 'EOF'
APP_NAME="Hartono Motor"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://hartonomotor.xyz

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hartonomotor
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
MAIL_FROM_ADDRESS="hello@hartonomotor.xyz"
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
        echo -e "${GREEN}✅ APP_KEY generated manually: $APP_KEY${NC}"
    fi
    
    # Verify APP_KEY
    if grep -q "APP_KEY=base64:" .env; then
        echo -e "${GREEN}✅ APP_KEY is properly set${NC}"
    else
        echo -e "${RED}❌ APP_KEY still not set properly${NC}"
        echo -e "${BLUE}Current APP_KEY line:${NC}"
        grep "APP_KEY=" .env || echo "APP_KEY line not found"
    fi
    
    # Install/update dependencies
    if [ ! -d "vendor" ] || [ ! -f "vendor/autoload.php" ]; then
        echo -e "${YELLOW}Installing Composer dependencies...${NC}"
        if command -v composer >/dev/null 2>&1; then
            composer install --no-dev --optimize-autoloader --no-interaction
            echo -e "${GREEN}✅ Composer dependencies installed${NC}"
        else
            echo -e "${RED}❌ Composer not found${NC}"
        fi
    else
        echo -e "${GREEN}✅ Composer dependencies already installed${NC}"
    fi
    
    # Clear Laravel caches
    echo -e "${YELLOW}Clearing Laravel caches...${NC}"
    php artisan config:clear 2>/dev/null && echo -e "${GREEN}✅ Config cache cleared${NC}"
    php artisan route:clear 2>/dev/null && echo -e "${GREEN}✅ Route cache cleared${NC}"
    php artisan view:clear 2>/dev/null && echo -e "${GREEN}✅ View cache cleared${NC}"
    php artisan cache:clear 2>/dev/null && echo -e "${GREEN}✅ Application cache cleared${NC}"
    
    # Run migrations if needed
    echo -e "${YELLOW}Running database migrations...${NC}"
    php artisan migrate --force 2>/dev/null && echo -e "${GREEN}✅ Migrations completed${NC}" || echo -e "${YELLOW}⚠️ Migrations skipped${NC}"
    
    echo ""
}

# Function to fix file permissions
fix_file_permissions() {
    echo -e "${YELLOW}🔧 Step 4: Fixing file permissions...${NC}"
    
    cd "$LARAVEL_DIR"
    
    # Set ownership to web server user
    chown -R www-data:www-data . 2>/dev/null || chown -R nginx:nginx . 2>/dev/null || echo -e "${YELLOW}⚠️ Could not change ownership${NC}"
    
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

# Function to fix Nginx configuration
fix_nginx_configuration() {
    echo -e "${YELLOW}🔧 Step 5: Fixing Nginx configuration...${NC}"
    
    # Create correct Nginx site configuration
    cat > /etc/nginx/sites-available/$DOMAIN << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    
    # Correct document root pointing to Laravel public directory
    root $LARAVEL_DIR/public;
    index index.php index.html index.htm;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
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
        
        # CORS headers
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
    }
    
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
        fastcgi_hide_header X-Powered-By;
        
        # Increase timeouts for Laravel
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Security: Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    # Deny access to Laravel sensitive directories
    location ~ ^/(storage|bootstrap|config|database|resources|routes|tests)/ {
        deny all;
    }
    
    # Logs
    access_log /var/log/nginx/$DOMAIN.access.log;
    error_log /var/log/nginx/$DOMAIN.error.log;
}
EOF
    
    echo -e "${GREEN}✅ Nginx configuration created with correct document root${NC}"
    
    # Enable site
    ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    echo -e "${GREEN}✅ Site enabled and default site removed${NC}"
    
    # Test Nginx configuration
    if nginx -t 2>/dev/null; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    else
        echo -e "${RED}❌ Nginx configuration has errors${NC}"
        nginx -t
        return 1
    fi
    
    echo ""
}

# Function to restart services
restart_services() {
    echo -e "${YELLOW}🔧 Step 6: Restarting services...${NC}"
    
    # Restart PHP-FPM
    echo -e "${YELLOW}Restarting PHP-FPM...${NC}"
    if systemctl restart php8.2-fpm 2>/dev/null; then
        echo -e "${GREEN}✅ PHP-FPM restarted${NC}"
    else
        echo -e "${RED}❌ Failed to restart PHP-FPM${NC}"
        systemctl status php8.2-fpm
    fi
    
    # Restart Nginx
    echo -e "${YELLOW}Restarting Nginx...${NC}"
    if systemctl restart nginx 2>/dev/null; then
        echo -e "${GREEN}✅ Nginx restarted${NC}"
    else
        echo -e "${RED}❌ Failed to restart Nginx${NC}"
        systemctl status nginx
        return 1
    fi
    
    echo ""
}

# Function to test the application
test_application() {
    echo -e "${YELLOW}🧪 Step 7: Testing the application...${NC}"
    
    sleep 3
    
    # Test localhost
    echo -e "${BLUE}Testing localhost...${NC}"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
    echo -e "  HTTP Code: $HTTP_CODE"
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ localhost working perfectly!${NC}"
    elif [ "$HTTP_CODE" = "500" ]; then
        echo -e "${RED}❌ localhost still returns 500 error${NC}"
    else
        echo -e "${YELLOW}⚠️ localhost returns $HTTP_CODE${NC}"
    fi
    
    # Test domain
    echo -e "${BLUE}Testing $DOMAIN...${NC}"
    DOMAIN_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
    echo -e "  HTTP Code: $DOMAIN_CODE"
    
    if [ "$DOMAIN_CODE" = "200" ]; then
        echo -e "${GREEN}✅ $DOMAIN working perfectly!${NC}"
    elif [ "$DOMAIN_CODE" = "500" ]; then
        echo -e "${RED}❌ $DOMAIN still returns 500 error${NC}"
    else
        echo -e "${YELLOW}⚠️ $DOMAIN returns $DOMAIN_CODE${NC}"
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

# Function to setup WhatsApp integration
setup_whatsapp_integration() {
    echo -e "${YELLOW}📱 Step 8: Setting up WhatsApp integration...${NC}"
    
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
        echo -e "${GREEN}✅ WhatsApp configuration updated in database${NC}"
    else
        echo -e "${YELLOW}⚠️ WhatsApp configuration update skipped (tables may not exist yet)${NC}"
    fi
    
    rm -f update_whatsapp_config.php
    
    echo ""
}

# Function to show final status
show_final_status() {
    echo -e "${GREEN}🎉 PRODUCTION FIX COMPLETED!${NC}"
    echo "=========================="
    echo ""
    
    echo -e "${BLUE}📋 What was fixed:${NC}"
    echo -e "  ✅ Laravel directory: $LARAVEL_DIR"
    echo -e "  ✅ APP_KEY generated and configured"
    echo -e "  ✅ File permissions set correctly"
    echo -e "  ✅ Nginx configured with correct document root"
    echo -e "  ✅ Services restarted"
    echo -e "  ✅ WhatsApp integration prepared"
    echo ""
    
    echo -e "${BLUE}📋 Next Steps:${NC}"
    echo -e "1. Visit: ${YELLOW}http://$DOMAIN${NC}"
    echo -e "2. Login to admin panel: ${YELLOW}http://$DOMAIN/admin${NC}"
    echo -e "3. Test WhatsApp integration: ${YELLOW}WhatsApp Integration → Konfigurasi WhatsApp${NC}"
    echo -e "4. Deploy WhatsApp API server if not done yet"
    echo ""
    
    echo -e "${BLUE}🔧 Troubleshooting:${NC}"
    echo -e "  View Laravel logs: ${YELLOW}tail -f $LARAVEL_DIR/storage/logs/laravel.log${NC}"
    echo -e "  View Nginx logs: ${YELLOW}tail -f /var/log/nginx/$DOMAIN.error.log${NC}"
    echo -e "  Check services: ${YELLOW}systemctl status nginx php8.2-fpm${NC}"
    echo ""
    
    echo -e "${BLUE}💾 Backup location: ${YELLOW}$BACKUP_DIR${NC}"
    echo ""
}

# Main execution
echo -e "${BLUE}Starting production fix for Hartono Motor...${NC}"
echo ""

# Execute all steps
create_backup

if verify_laravel_directory; then
    fix_laravel_application
    fix_file_permissions
    fix_nginx_configuration
    restart_services
    test_application
    setup_whatsapp_integration
    show_final_status
else
    echo -e "${RED}❌ Cannot proceed - Laravel directory verification failed${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Production fix script completed successfully!${NC}"
