#!/bin/bash

# Automatic 500 Error Fix Script for hartonomotor.xyz
# This script will automatically fix common causes of HTTP 500 errors

echo "🔧 Automatic 500 Error Fix for hartonomotor.xyz"
echo "==============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Starting automatic 500 error fix...${NC}"
echo ""

# Function to check privileges
check_privileges() {
    if [ "$(id -u)" -eq 0 ]; then
        USE_SUDO=""
        echo -e "${GREEN}✅ Running as root${NC}"
    elif command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
        USE_SUDO="sudo"
        echo -e "${GREEN}✅ sudo available${NC}"
    else
        USE_SUDO=""
        echo -e "${YELLOW}⚠️ Limited privileges${NC}"
    fi
}

# Function to find Laravel directory
find_laravel_directory() {
    echo -e "${YELLOW}🔍 Finding Laravel directory...${NC}"
    
    LARAVEL_DIRS=$(find /home /var/www /opt /hm -name "artisan" -type f 2>/dev/null | xargs dirname)
    
    if [ -z "$LARAVEL_DIRS" ]; then
        echo -e "${RED}❌ No Laravel projects found${NC}"
        return 1
    fi
    
    # Try to find Hartono Motor project
    for dir in $LARAVEL_DIRS; do
        if [ -f "$dir/composer.json" ]; then
            if grep -q "hartonomotor\|Hartono" "$dir/composer.json" 2>/dev/null; then
                LARAVEL_DIR="$dir"
                echo -e "${GREEN}✅ Found Hartono Motor project: ${LARAVEL_DIR}${NC}"
                return 0
            fi
        fi
    done
    
    # Use first Laravel project
    LARAVEL_ARRAY=($LARAVEL_DIRS)
    LARAVEL_DIR="${LARAVEL_ARRAY[0]}"
    echo -e "${YELLOW}⚠️ Using first Laravel project: ${LARAVEL_DIR}${NC}"
    
    return 0
}

# Function to fix Laravel application
fix_laravel_application() {
    echo -e "${YELLOW}🔧 Step 1: Fixing Laravel application...${NC}"
    
    if [ -z "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Laravel directory not found${NC}"
        return 1
    fi
    
    cd "$LARAVEL_DIR"
    
    # Create .env if missing
    if [ ! -f ".env" ]; then
        echo -e "${YELLOW}Creating .env file...${NC}"
        if [ -f ".env.example" ]; then
            cp .env.example .env
            echo -e "${GREEN}✅ .env file created from .env.example${NC}"
        else
            echo -e "${RED}❌ .env.example not found${NC}"
        fi
    fi
    
    # Generate APP_KEY if missing
    if [ -f ".env" ]; then
        if ! grep -q "APP_KEY=" .env || [ -z "$(grep "APP_KEY=" .env | cut -d'=' -f2)" ]; then
            echo -e "${YELLOW}Generating APP_KEY...${NC}"
            php artisan key:generate --force 2>/dev/null && echo -e "${GREEN}✅ APP_KEY generated${NC}" || echo -e "${RED}❌ Failed to generate APP_KEY${NC}"
        fi
        
        # Enable debug mode temporarily
        sed -i 's/APP_DEBUG=false/APP_DEBUG=true/' .env 2>/dev/null
        echo -e "${GREEN}✅ Debug mode enabled${NC}"
    fi
    
    # Install composer dependencies
    if [ ! -d "vendor" ]; then
        echo -e "${YELLOW}Installing Composer dependencies...${NC}"
        if command -v composer >/dev/null 2>&1; then
            composer install --no-dev --optimize-autoloader 2>/dev/null && echo -e "${GREEN}✅ Composer install completed${NC}" || echo -e "${RED}❌ Composer install failed${NC}"
        else
            echo -e "${RED}❌ Composer not found${NC}"
        fi
    fi
    
    # Clear Laravel caches
    echo -e "${YELLOW}Clearing Laravel caches...${NC}"
    php artisan config:clear 2>/dev/null
    php artisan route:clear 2>/dev/null
    php artisan view:clear 2>/dev/null
    php artisan cache:clear 2>/dev/null
    echo -e "${GREEN}✅ Laravel caches cleared${NC}"
    
    echo ""
}

# Function to fix file permissions
fix_file_permissions() {
    echo -e "${YELLOW}🔧 Step 2: Fixing file permissions...${NC}"
    
    if [ -z "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Laravel directory not found${NC}"
        return 1
    fi
    
    cd "$LARAVEL_DIR"
    
    # Set proper ownership
    echo -e "${YELLOW}Setting file ownership...${NC}"
    $USE_SUDO chown -R www-data:www-data . 2>/dev/null && echo -e "${GREEN}✅ Ownership set to www-data${NC}" || echo -e "${YELLOW}⚠️ Could not set ownership${NC}"
    
    # Set directory permissions
    echo -e "${YELLOW}Setting directory permissions...${NC}"
    $USE_SUDO chmod -R 755 . 2>/dev/null
    
    # Set writable permissions for storage and cache
    if [ -d "storage" ]; then
        $USE_SUDO chmod -R 775 storage 2>/dev/null && echo -e "${GREEN}✅ storage directory permissions set${NC}"
    fi
    
    if [ -d "bootstrap/cache" ]; then
        $USE_SUDO chmod -R 775 bootstrap/cache 2>/dev/null && echo -e "${GREEN}✅ bootstrap/cache permissions set${NC}"
    fi
    
    # Create missing directories
    if [ ! -d "storage/logs" ]; then
        mkdir -p storage/logs 2>/dev/null && echo -e "${GREEN}✅ storage/logs directory created${NC}"
    fi
    
    if [ ! -d "storage/framework/cache" ]; then
        mkdir -p storage/framework/cache 2>/dev/null && echo -e "${GREEN}✅ storage/framework/cache directory created${NC}"
    fi
    
    if [ ! -d "storage/framework/sessions" ]; then
        mkdir -p storage/framework/sessions 2>/dev/null && echo -e "${GREEN}✅ storage/framework/sessions directory created${NC}"
    fi
    
    if [ ! -d "storage/framework/views" ]; then
        mkdir -p storage/framework/views 2>/dev/null && echo -e "${GREEN}✅ storage/framework/views directory created${NC}"
    fi
    
    echo ""
}

# Function to fix Nginx configuration
fix_nginx_configuration() {
    echo -e "${YELLOW}🔧 Step 3: Fixing Nginx configuration...${NC}"
    
    if [ -z "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Laravel directory not found${NC}"
        return 1
    fi
    
    # Create Nginx site configuration
    NGINX_SITE="/etc/nginx/sites-available/$DOMAIN"
    
    echo -e "${YELLOW}Creating Nginx site configuration...${NC}"
    $USE_SUDO tee "$NGINX_SITE" > /dev/null << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    
    root $LARAVEL_DIR/public;
    index index.php index.html index.htm;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
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
        
        # Increase timeouts
        fastcgi_read_timeout 300;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
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
    
    # Deny access to storage and other sensitive directories
    location ~ ^/(storage|bootstrap|config|database|resources|routes|tests)/ {
        deny all;
    }
    
    # Logs
    access_log /var/log/nginx/$DOMAIN.access.log;
    error_log /var/log/nginx/$DOMAIN.error.log;
}
EOF
    
    echo -e "${GREEN}✅ Nginx site configuration created${NC}"
    
    # Enable site
    $USE_SUDO mkdir -p /etc/nginx/sites-enabled
    $USE_SUDO ln -sf "$NGINX_SITE" /etc/nginx/sites-enabled/
    echo -e "${GREEN}✅ Site enabled${NC}"
    
    # Remove default site
    $USE_SUDO rm -f /etc/nginx/sites-enabled/default
    echo -e "${GREEN}✅ Default site removed${NC}"
    
    # Test Nginx configuration
    if $USE_SUDO nginx -t 2>/dev/null; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    else
        echo -e "${RED}❌ Nginx configuration has errors${NC}"
        $USE_SUDO nginx -t
    fi
    
    echo ""
}

# Function to restart services
restart_services() {
    echo -e "${YELLOW}🔧 Step 4: Restarting services...${NC}"
    
    # Restart PHP-FPM
    echo -e "${YELLOW}Restarting PHP-FPM...${NC}"
    for service in php8.2-fpm php8.1-fpm php8.0-fpm php7.4-fpm php-fpm; do
        if $USE_SUDO systemctl is-active --quiet "$service" 2>/dev/null; then
            $USE_SUDO systemctl restart "$service" && echo -e "${GREEN}✅ $service restarted${NC}"
            break
        fi
    done
    
    # Restart Nginx
    echo -e "${YELLOW}Restarting Nginx...${NC}"
    if $USE_SUDO systemctl restart nginx 2>/dev/null; then
        echo -e "${GREEN}✅ Nginx restarted${NC}"
    else
        echo -e "${RED}❌ Failed to restart Nginx${NC}"
    fi
    
    echo ""
}

# Function to test fix
test_fix() {
    echo -e "${YELLOW}🧪 Step 5: Testing the fix...${NC}"
    
    sleep 3
    
    # Test localhost
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
    echo -e "${BLUE}localhost HTTP code: $HTTP_CODE${NC}"
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ localhost is working!${NC}"
    elif [ "$HTTP_CODE" = "500" ]; then
        echo -e "${RED}❌ localhost still returns 500 error${NC}"
    else
        echo -e "${YELLOW}⚠️ localhost returns $HTTP_CODE${NC}"
    fi
    
    # Test domain
    DOMAIN_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
    echo -e "${BLUE}$DOMAIN HTTP code: $DOMAIN_CODE${NC}"
    
    if [ "$DOMAIN_CODE" = "200" ]; then
        echo -e "${GREEN}✅ $DOMAIN is working!${NC}"
    elif [ "$DOMAIN_CODE" = "500" ]; then
        echo -e "${RED}❌ $DOMAIN still returns 500 error${NC}"
    else
        echo -e "${YELLOW}⚠️ $DOMAIN returns $DOMAIN_CODE${NC}"
    fi
    
    echo ""
}

# Function to show next steps
show_next_steps() {
    echo -e "${BLUE}📋 Next Steps:${NC}"
    echo ""
    
    if [ -n "$LARAVEL_DIR" ]; then
        echo -e "${YELLOW}1. Check detailed error logs:${NC}"
        echo -e "   tail -f $LARAVEL_DIR/storage/logs/laravel.log"
        echo -e "   ${USE_SUDO} tail -f /var/log/nginx/error.log"
        echo ""
        
        echo -e "${YELLOW}2. If still having issues, disable debug mode:${NC}"
        echo -e "   Edit $LARAVEL_DIR/.env"
        echo -e "   Set: APP_DEBUG=false"
        echo ""
    fi
    
    echo -e "${YELLOW}3. Test WhatsApp integration:${NC}"
    echo -e "   Visit: https://$DOMAIN/admin"
    echo -e "   Go to: WhatsApp Integration → Konfigurasi WhatsApp"
    echo ""
    
    echo -e "${YELLOW}4. If problems persist, run diagnostic:${NC}"
    echo -e "   bash diagnose-500-error.sh"
    echo ""
}

# Main execution
check_privileges
echo ""

if find_laravel_directory; then
    fix_laravel_application
    fix_file_permissions
    fix_nginx_configuration
    restart_services
    test_fix
    
    echo -e "${GREEN}🎉 500 Error Fix Completed!${NC}"
    echo "=========================="
    echo ""
    
    show_next_steps
else
    echo -e "${RED}❌ Cannot proceed without Laravel directory${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Automatic fix script completed!${NC}"
