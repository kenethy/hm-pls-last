#!/bin/bash

# Complete 500 Error Diagnostic Script for hartonomotor.xyz
# This script will diagnose and help fix HTTP 500 errors

echo "🔍 HTTP 500 Error Diagnostic for hartonomotor.xyz"
echo "================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Starting comprehensive 500 error diagnosis...${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Timestamp: $(date)"
echo -e "  VPS Host: $(hostname)"
echo ""

# Function to check if running as root or with sudo
check_privileges() {
    if [ "$(id -u)" -eq 0 ]; then
        USE_SUDO=""
        echo -e "${GREEN}✅ Running as root${NC}"
    elif command -v sudo >/dev/null 2>&1 && sudo -n true 2>/dev/null; then
        USE_SUDO="sudo"
        echo -e "${GREEN}✅ sudo available${NC}"
    else
        USE_SUDO=""
        echo -e "${YELLOW}⚠️ Limited privileges (some checks may fail)${NC}"
    fi
}

# Function to find Laravel directory
find_laravel_directory() {
    echo -e "${PURPLE}1. FINDING LARAVEL PROJECT${NC}"
    echo "=========================="
    
    # Search for Laravel projects
    echo -e "${YELLOW}🔍 Searching for Laravel projects...${NC}"
    
    LARAVEL_DIRS=$(find /home /var/www /opt /hm -name "artisan" -type f 2>/dev/null | xargs dirname)
    
    if [ -z "$LARAVEL_DIRS" ]; then
        echo -e "${RED}❌ No Laravel projects found${NC}"
        return 1
    fi
    
    echo -e "${GREEN}📍 Laravel projects found:${NC}"
    for dir in $LARAVEL_DIRS; do
        echo -e "  📁 $dir"
        
        # Check if it's Hartono Motor project
        if [ -f "$dir/composer.json" ]; then
            if grep -q "hartonomotor\|Hartono" "$dir/composer.json" 2>/dev/null; then
                LARAVEL_DIR="$dir"
                echo -e "     ${GREEN}🎯 This is the Hartono Motor project!${NC}"
            fi
        fi
    done
    
    # If no specific match found, use first one
    if [ -z "$LARAVEL_DIR" ]; then
        LARAVEL_ARRAY=($LARAVEL_DIRS)
        LARAVEL_DIR="${LARAVEL_ARRAY[0]}"
        echo -e "${YELLOW}⚠️ Using first Laravel project: ${LARAVEL_DIR}${NC}"
    fi
    
    echo -e "${GREEN}✅ Selected Laravel directory: ${LARAVEL_DIR}${NC}"
    echo ""
    return 0
}

# Function to check Nginx configuration
check_nginx() {
    echo -e "${PURPLE}2. NGINX CONFIGURATION CHECK${NC}"
    echo "============================"
    
    # Check if Nginx is installed
    if ! command -v nginx >/dev/null 2>&1; then
        echo -e "${RED}❌ Nginx is not installed${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ Nginx is installed${NC}"
    
    # Check Nginx status
    if $USE_SUDO systemctl is-active --quiet nginx 2>/dev/null || pgrep nginx >/dev/null; then
        echo -e "${GREEN}✅ Nginx is running${NC}"
    else
        echo -e "${RED}❌ Nginx is not running${NC}"
        echo -e "${YELLOW}💡 Try: ${USE_SUDO} systemctl start nginx${NC}"
    fi
    
    # Check Nginx configuration syntax
    echo -e "${YELLOW}🔍 Testing Nginx configuration...${NC}"
    if $USE_SUDO nginx -t 2>/dev/null; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    else
        echo -e "${RED}❌ Nginx configuration has errors:${NC}"
        $USE_SUDO nginx -t
    fi
    
    # Check site configuration
    NGINX_SITE="/etc/nginx/sites-available/$DOMAIN"
    if [ -f "$NGINX_SITE" ]; then
        echo -e "${GREEN}✅ Site configuration exists: $NGINX_SITE${NC}"
        
        # Check document root
        DOC_ROOT=$(grep -E "^\s*root\s+" "$NGINX_SITE" | head -1 | awk '{print $2}' | sed 's/;//')
        echo -e "${BLUE}📁 Document root: $DOC_ROOT${NC}"
        
        if [ -d "$DOC_ROOT" ]; then
            echo -e "${GREEN}✅ Document root directory exists${NC}"
        else
            echo -e "${RED}❌ Document root directory does not exist${NC}"
        fi
        
        # Check if site is enabled
        if [ -L "/etc/nginx/sites-enabled/$DOMAIN" ]; then
            echo -e "${GREEN}✅ Site is enabled${NC}"
        else
            echo -e "${RED}❌ Site is not enabled${NC}"
            echo -e "${YELLOW}💡 Try: ${USE_SUDO} ln -sf $NGINX_SITE /etc/nginx/sites-enabled/${NC}"
        fi
    else
        echo -e "${RED}❌ Site configuration not found: $NGINX_SITE${NC}"
    fi
    
    echo ""
}

# Function to check PHP-FPM
check_php_fpm() {
    echo -e "${PURPLE}3. PHP-FPM STATUS CHECK${NC}"
    echo "======================"
    
    # Check if PHP is installed
    if ! command -v php >/dev/null 2>&1; then
        echo -e "${RED}❌ PHP is not installed${NC}"
        return 1
    fi
    
    PHP_VERSION=$(php -v | head -1 | cut -d' ' -f2 | cut -d'.' -f1,2)
    echo -e "${GREEN}✅ PHP version: $PHP_VERSION${NC}"
    
    # Check PHP-FPM service
    PHP_FPM_SERVICE="php${PHP_VERSION}-fpm"
    if $USE_SUDO systemctl is-active --quiet "$PHP_FPM_SERVICE" 2>/dev/null; then
        echo -e "${GREEN}✅ PHP-FPM is running ($PHP_FPM_SERVICE)${NC}"
    else
        # Try alternative service names
        for service in php-fpm php8.2-fpm php8.1-fpm php8.0-fpm php7.4-fpm; do
            if $USE_SUDO systemctl is-active --quiet "$service" 2>/dev/null; then
                echo -e "${GREEN}✅ PHP-FPM is running ($service)${NC}"
                PHP_FPM_SERVICE="$service"
                break
            fi
        done
        
        if [ -z "$PHP_FPM_SERVICE" ]; then
            echo -e "${RED}❌ PHP-FPM is not running${NC}"
            echo -e "${YELLOW}💡 Try: ${USE_SUDO} systemctl start php${PHP_VERSION}-fpm${NC}"
        fi
    fi
    
    # Check PHP-FPM socket
    PHP_SOCKET="/var/run/php/php${PHP_VERSION}-fpm.sock"
    if [ -S "$PHP_SOCKET" ]; then
        echo -e "${GREEN}✅ PHP-FPM socket exists: $PHP_SOCKET${NC}"
    else
        echo -e "${RED}❌ PHP-FPM socket not found: $PHP_SOCKET${NC}"
        
        # Search for alternative sockets
        echo -e "${YELLOW}🔍 Searching for PHP-FPM sockets...${NC}"
        find /var/run -name "*fpm*.sock" 2>/dev/null | while read socket; do
            echo -e "  📍 Found: $socket"
        done
    fi
    
    echo ""
}

# Function to check Laravel application
check_laravel() {
    echo -e "${PURPLE}4. LARAVEL APPLICATION CHECK${NC}"
    echo "============================"
    
    if [ -z "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Laravel directory not found${NC}"
        return 1
    fi
    
    cd "$LARAVEL_DIR"
    
    # Check Laravel files
    echo -e "${YELLOW}🔍 Checking Laravel files...${NC}"
    
    if [ -f "artisan" ]; then
        echo -e "${GREEN}✅ artisan file exists${NC}"
    else
        echo -e "${RED}❌ artisan file missing${NC}"
    fi
    
    if [ -f "composer.json" ]; then
        echo -e "${GREEN}✅ composer.json exists${NC}"
    else
        echo -e "${RED}❌ composer.json missing${NC}"
    fi
    
    if [ -d "vendor" ]; then
        echo -e "${GREEN}✅ vendor directory exists${NC}"
    else
        echo -e "${RED}❌ vendor directory missing${NC}"
        echo -e "${YELLOW}💡 Try: composer install${NC}"
    fi
    
    if [ -d "public" ]; then
        echo -e "${GREEN}✅ public directory exists${NC}"
        
        if [ -f "public/index.php" ]; then
            echo -e "${GREEN}✅ public/index.php exists${NC}"
        else
            echo -e "${RED}❌ public/index.php missing${NC}"
        fi
    else
        echo -e "${RED}❌ public directory missing${NC}"
    fi
    
    # Check .env file
    if [ -f ".env" ]; then
        echo -e "${GREEN}✅ .env file exists${NC}"
        
        # Check important .env variables
        if grep -q "APP_KEY=" .env && [ -n "$(grep "APP_KEY=" .env | cut -d'=' -f2)" ]; then
            echo -e "${GREEN}✅ APP_KEY is set${NC}"
        else
            echo -e "${RED}❌ APP_KEY is missing or empty${NC}"
            echo -e "${YELLOW}💡 Try: php artisan key:generate${NC}"
        fi
        
        if grep -q "APP_DEBUG=true" .env; then
            echo -e "${YELLOW}⚠️ APP_DEBUG is enabled (good for debugging)${NC}"
        else
            echo -e "${BLUE}ℹ️ APP_DEBUG is disabled${NC}"
        fi
    else
        echo -e "${RED}❌ .env file missing${NC}"
        echo -e "${YELLOW}💡 Try: cp .env.example .env${NC}"
    fi
    
    echo ""
}

# Function to check file permissions
check_permissions() {
    echo -e "${PURPLE}5. FILE PERMISSIONS CHECK${NC}"
    echo "========================="
    
    if [ -z "$LARAVEL_DIR" ]; then
        echo -e "${RED}❌ Laravel directory not found${NC}"
        return 1
    fi
    
    cd "$LARAVEL_DIR"
    
    echo -e "${YELLOW}🔍 Checking file permissions...${NC}"
    
    # Check directory ownership
    OWNER=$(stat -c '%U:%G' . 2>/dev/null || stat -f '%Su:%Sg' . 2>/dev/null)
    echo -e "${BLUE}📁 Directory owner: $OWNER${NC}"
    
    # Check storage directory
    if [ -d "storage" ]; then
        STORAGE_PERMS=$(stat -c '%a' storage 2>/dev/null || stat -f '%A' storage 2>/dev/null)
        echo -e "${BLUE}📁 storage permissions: $STORAGE_PERMS${NC}"
        
        if [ "$STORAGE_PERMS" -ge 775 ]; then
            echo -e "${GREEN}✅ storage directory is writable${NC}"
        else
            echo -e "${RED}❌ storage directory may not be writable${NC}"
            echo -e "${YELLOW}💡 Try: ${USE_SUDO} chmod -R 775 storage${NC}"
        fi
    else
        echo -e "${RED}❌ storage directory missing${NC}"
    fi
    
    # Check bootstrap/cache directory
    if [ -d "bootstrap/cache" ]; then
        CACHE_PERMS=$(stat -c '%a' bootstrap/cache 2>/dev/null || stat -f '%A' bootstrap/cache 2>/dev/null)
        echo -e "${BLUE}📁 bootstrap/cache permissions: $CACHE_PERMS${NC}"
        
        if [ "$CACHE_PERMS" -ge 775 ]; then
            echo -e "${GREEN}✅ bootstrap/cache directory is writable${NC}"
        else
            echo -e "${RED}❌ bootstrap/cache directory may not be writable${NC}"
            echo -e "${YELLOW}💡 Try: ${USE_SUDO} chmod -R 775 bootstrap/cache${NC}"
        fi
    else
        echo -e "${RED}❌ bootstrap/cache directory missing${NC}"
    fi
    
    # Check public directory permissions
    if [ -d "public" ]; then
        PUBLIC_PERMS=$(stat -c '%a' public 2>/dev/null || stat -f '%A' public 2>/dev/null)
        echo -e "${BLUE}📁 public permissions: $PUBLIC_PERMS${NC}"
    fi
    
    echo ""
}

# Function to check logs
check_logs() {
    echo -e "${PURPLE}6. LOG FILES ANALYSIS${NC}"
    echo "===================="
    
    echo -e "${YELLOW}🔍 Checking recent error logs...${NC}"
    
    # Nginx error logs
    echo -e "${BLUE}📋 Nginx Error Logs (last 10 lines):${NC}"
    if [ -f "/var/log/nginx/error.log" ]; then
        $USE_SUDO tail -10 /var/log/nginx/error.log 2>/dev/null || echo "Cannot read Nginx error log"
    elif [ -f "/var/log/nginx/$DOMAIN.error.log" ]; then
        $USE_SUDO tail -10 "/var/log/nginx/$DOMAIN.error.log" 2>/dev/null || echo "Cannot read site error log"
    else
        echo -e "${YELLOW}⚠️ Nginx error log not found${NC}"
    fi
    
    echo ""
    
    # Laravel logs
    echo -e "${BLUE}📋 Laravel Error Logs (last 10 lines):${NC}"
    if [ -n "$LARAVEL_DIR" ] && [ -f "$LARAVEL_DIR/storage/logs/laravel.log" ]; then
        tail -10 "$LARAVEL_DIR/storage/logs/laravel.log" 2>/dev/null || echo "Cannot read Laravel log"
    else
        echo -e "${YELLOW}⚠️ Laravel log not found${NC}"
    fi
    
    echo ""
    
    # PHP-FPM logs
    echo -e "${BLUE}📋 PHP-FPM Error Logs (last 5 lines):${NC}"
    for log in /var/log/php*fpm.log /var/log/php*/fpm.log; do
        if [ -f "$log" ]; then
            echo -e "${BLUE}From $log:${NC}"
            $USE_SUDO tail -5 "$log" 2>/dev/null || echo "Cannot read PHP-FPM log"
            break
        fi
    done
    
    echo ""
}

# Function to test HTTP response
test_http_response() {
    echo -e "${PURPLE}7. HTTP RESPONSE TEST${NC}"
    echo "===================="
    
    echo -e "${YELLOW}🔍 Testing HTTP responses...${NC}"
    
    # Test localhost
    echo -e "${BLUE}Testing localhost:${NC}"
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null || echo "000")
    echo -e "  HTTP Code: $HTTP_CODE"
    
    if [ "$HTTP_CODE" = "200" ]; then
        echo -e "${GREEN}✅ localhost responds with 200 OK${NC}"
    elif [ "$HTTP_CODE" = "500" ]; then
        echo -e "${RED}❌ localhost responds with 500 Error${NC}"
    else
        echo -e "${YELLOW}⚠️ localhost responds with $HTTP_CODE${NC}"
    fi
    
    # Test domain
    echo -e "${BLUE}Testing $DOMAIN:${NC}"
    DOMAIN_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://$DOMAIN" 2>/dev/null || echo "000")
    echo -e "  HTTP Code: $DOMAIN_CODE"
    
    if [ "$DOMAIN_CODE" = "200" ]; then
        echo -e "${GREEN}✅ $DOMAIN responds with 200 OK${NC}"
    elif [ "$DOMAIN_CODE" = "500" ]; then
        echo -e "${RED}❌ $DOMAIN responds with 500 Error${NC}"
    else
        echo -e "${YELLOW}⚠️ $DOMAIN responds with $DOMAIN_CODE${NC}"
    fi
    
    echo ""
}

# Function to provide solutions
provide_solutions() {
    echo -e "${PURPLE}8. RECOMMENDED SOLUTIONS${NC}"
    echo "======================="
    
    echo -e "${BLUE}🔧 Based on the diagnosis above, try these solutions:${NC}"
    echo ""
    
    echo -e "${YELLOW}1. Fix Laravel Application:${NC}"
    if [ -n "$LARAVEL_DIR" ]; then
        echo -e "   cd $LARAVEL_DIR"
        echo -e "   cp .env.example .env  # if .env missing"
        echo -e "   php artisan key:generate"
        echo -e "   composer install"
        echo -e "   php artisan config:clear"
        echo -e "   php artisan cache:clear"
    fi
    echo ""
    
    echo -e "${YELLOW}2. Fix File Permissions:${NC}"
    if [ -n "$LARAVEL_DIR" ]; then
        echo -e "   ${USE_SUDO} chown -R www-data:www-data $LARAVEL_DIR"
        echo -e "   ${USE_SUDO} chmod -R 755 $LARAVEL_DIR"
        echo -e "   ${USE_SUDO} chmod -R 775 $LARAVEL_DIR/storage"
        echo -e "   ${USE_SUDO} chmod -R 775 $LARAVEL_DIR/bootstrap/cache"
    fi
    echo ""
    
    echo -e "${YELLOW}3. Restart Services:${NC}"
    echo -e "   ${USE_SUDO} systemctl restart nginx"
    echo -e "   ${USE_SUDO} systemctl restart php8.2-fpm  # or appropriate PHP version"
    echo ""
    
    echo -e "${YELLOW}4. Enable Debug Mode (temporarily):${NC}"
    if [ -n "$LARAVEL_DIR" ]; then
        echo -e "   Edit $LARAVEL_DIR/.env"
        echo -e "   Set: APP_DEBUG=true"
        echo -e "   Then visit the site to see detailed error messages"
    fi
    echo ""
    
    echo -e "${YELLOW}5. Check Detailed Logs:${NC}"
    echo -e "   ${USE_SUDO} tail -f /var/log/nginx/error.log"
    if [ -n "$LARAVEL_DIR" ]; then
        echo -e "   tail -f $LARAVEL_DIR/storage/logs/laravel.log"
    fi
    echo ""
}

# Main execution
check_privileges
echo ""

find_laravel_directory
check_nginx
check_php_fpm
check_laravel
check_permissions
check_logs
test_http_response
provide_solutions

echo -e "${GREEN}🎉 Diagnostic completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Review the issues found above"
echo -e "2. Apply the recommended solutions"
echo -e "3. Test the website again"
echo -e "4. If still having issues, check the detailed logs"
echo ""
echo -e "${YELLOW}💡 Quick Fix Command:${NC}"
echo -e "   bash fix-500-error.sh  # (will be created next)"
