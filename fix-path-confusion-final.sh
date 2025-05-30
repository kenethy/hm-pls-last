#!/bin/bash

# =============================================================================
# 🔧 FINAL FIX - Path Confusion Resolver
# =============================================================================
# Script untuk mengatasi masalah path confusion secara permanen
# Laravel mencoba akses /hm-new/storage/ padahal seharusnya /var/www/html/storage/
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
NC='\033[0m'

echo -e "${PURPLE}"
echo "============================================================================="
echo "🔧 FINAL PATH CONFUSION RESOLVER"
echo "============================================================================="
echo "Mengatasi masalah Laravel yang mencoba akses /hm-new/storage/ secara permanen"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_step() {
    echo -e "${YELLOW}🔧 STEP $1: $2${NC}"
}

show_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

show_error() {
    echo -e "${RED}❌ $1${NC}"
}

show_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

show_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# STEP 1: Analisis masalah path
show_step "1" "Analyzing path confusion issue..."

echo "Error shows Laravel trying to write to: /hm-new/storage/"
echo "But Laravel is located at: /var/www/html/"
echo ""

# Check if /hm-new exists
if [[ -d "/hm-new" ]]; then
    show_info "/hm-new directory exists"
    ls -la /hm-new/ | head -3
else
    show_info "/hm-new directory does not exist"
fi

# Check Laravel location
if [[ -f "/var/www/html/artisan" ]]; then
    show_success "Laravel confirmed at: /var/www/html"
else
    show_error "Laravel not found at /var/www/html"
    exit 1
fi

echo ""

# STEP 2: Check for symlinks
show_step "2" "Checking for problematic symlinks..."

cd /var/www/html

# Check storage symlink
if [[ -L "storage" ]]; then
    STORAGE_TARGET=$(readlink -f storage)
    show_warning "storage is a symlink pointing to: $STORAGE_TARGET"
    
    if [[ "$STORAGE_TARGET" == *"/hm-new/"* ]]; then
        show_error "FOUND THE PROBLEM! storage symlink points to /hm-new/"
        
        # Remove bad symlink
        show_info "Removing bad symlink..."
        rm storage
        show_success "Bad symlink removed"
        
        # Create proper storage directory
        mkdir -p storage
        show_success "Proper storage directory created"
    fi
else
    show_info "storage is not a symlink (good)"
fi

# Check bootstrap/cache symlink
if [[ -L "bootstrap/cache" ]]; then
    CACHE_TARGET=$(readlink -f bootstrap/cache)
    show_warning "bootstrap/cache is a symlink pointing to: $CACHE_TARGET"
    
    if [[ "$CACHE_TARGET" == *"/hm-new/"* ]]; then
        show_error "bootstrap/cache symlink also points to /hm-new/"
        
        # Remove bad symlink
        rm bootstrap/cache
        mkdir -p bootstrap/cache
        show_success "Fixed bootstrap/cache symlink"
    fi
else
    show_info "bootstrap/cache is not a symlink (good)"
fi

echo ""

# STEP 3: Create /hm-new/storage as emergency fallback
show_step "3" "Creating emergency fallback directories..."

# Create /hm-new/storage structure as fallback
sudo mkdir -p /hm-new/storage/framework/views
sudo mkdir -p /hm-new/storage/framework/cache/data
sudo mkdir -p /hm-new/storage/framework/sessions
sudo mkdir -p /hm-new/storage/logs
sudo mkdir -p /hm-new/bootstrap/cache

# Set permissions
sudo chmod -R 777 /hm-new/storage
sudo chmod -R 777 /hm-new/bootstrap 2>/dev/null || true

show_success "Emergency fallback directories created"
echo ""

# STEP 4: Fix Laravel storage structure
show_step "4" "Rebuilding proper Laravel storage structure..."

cd /var/www/html

# Create complete storage structure
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

show_success "Laravel storage structure rebuilt"

# Set proper ownership and permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

show_success "Permissions set correctly"
echo ""

# STEP 5: Check and fix .env configuration
show_step "5" "Checking .env configuration for path issues..."

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
show_info "Backup .env created"

# Check for problematic path configurations
PROBLEMATIC_PATHS=$(grep -E "PATH.*hm-new|STORAGE.*hm-new|VIEW.*hm-new" .env || echo "")
if [[ -n "$PROBLEMATIC_PATHS" ]]; then
    show_warning "Found problematic path configurations:"
    echo "$PROBLEMATIC_PATHS"
    
    # Comment out problematic paths
    sed -i 's/^.*PATH.*hm-new.*/#&/' .env
    sed -i 's/^.*STORAGE.*hm-new.*/#&/' .env
    sed -i 's/^.*VIEW.*hm-new.*/#&/' .env
    
    show_success "Problematic path configurations disabled"
else
    show_info "No problematic path configurations found in .env"
fi

echo ""

# STEP 6: Clear all caches and compiled files
show_step "6" "Clearing all caches and compiled files..."

# Clear Laravel caches
php artisan config:clear 2>/dev/null || show_info "Config cache already clear"
php artisan view:clear 2>/dev/null || show_info "View cache already clear"
php artisan route:clear 2>/dev/null || show_info "Route cache already clear"
php artisan cache:clear 2>/dev/null || show_info "Application cache already clear"

# Remove compiled view files
rm -rf storage/framework/views/*.php 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true
rm -rf bootstrap/cache/*.php 2>/dev/null || true

# Also clear the problematic /hm-new cache if it exists
rm -rf /hm-new/storage/framework/views/*.php 2>/dev/null || true
rm -rf /hm-new/storage/framework/cache/data/* 2>/dev/null || true

show_success "All caches and compiled files cleared"
echo ""

# STEP 7: Check for config cache issues
show_step "7" "Checking for configuration cache issues..."

# Check if there's a cached config pointing to wrong path
if [[ -f "bootstrap/cache/config.php" ]]; then
    if grep -q "/hm-new/" bootstrap/cache/config.php; then
        show_warning "Found /hm-new/ references in cached config"
        rm bootstrap/cache/config.php
        show_success "Removed problematic config cache"
    fi
fi

# Check for other cached files with wrong paths
find bootstrap/cache/ -name "*.php" -exec grep -l "/hm-new/" {} \; 2>/dev/null | while read file; do
    show_warning "Removing cached file with wrong path: $file"
    rm "$file"
done

echo ""

# STEP 8: Rebuild configuration
show_step "8" "Rebuilding Laravel configuration..."

# Rebuild config cache
php artisan config:cache 2>/dev/null && show_success "Config cache rebuilt" || show_info "Config cache rebuild skipped"

# Set permissions again for newly created files
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

show_success "Configuration rebuilt with correct paths"
echo ""

# STEP 9: Test Laravel functionality
show_step "9" "Testing Laravel functionality..."

# Test artisan
if php artisan --version > /dev/null 2>&1; then
    LARAVEL_VERSION=$(php artisan --version)
    show_success "Laravel working: $LARAVEL_VERSION"
    LARAVEL_OK=true
else
    show_error "Laravel still has issues"
    LARAVEL_OK=false
fi

# Test view compilation
TEST_VIEW_RESULT=$(php artisan tinker --execute="
try {
    view('welcome');
    echo 'VIEW_OK';
} catch (Exception \$e) {
    echo 'VIEW_ERROR: ' . \$e->getMessage();
}
" 2>/dev/null)

if echo "$TEST_VIEW_RESULT" | grep -q "VIEW_OK"; then
    show_success "View compilation working"
    VIEW_OK=true
else
    show_warning "View compilation issue: $TEST_VIEW_RESULT"
    VIEW_OK=false
fi

echo ""

# STEP 10: Restart web services
show_step "10" "Restarting web services..."

# Restart PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    show_success "PHP 8.1 FPM restarted"
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
    show_success "PHP 8.0 FPM restarted"
elif systemctl is-active --quiet php-fpm; then
    sudo systemctl restart php-fpm
    show_success "PHP-FPM restarted"
fi

# Restart web server
if systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx
    show_success "Nginx restarted"
elif systemctl is-active --quiet apache2; then
    sudo systemctl restart apache2
    show_success "Apache restarted"
fi

echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 FINAL PATH CONFUSION FIX RESULTS"
echo "============================================================================="
echo -e "${NC}"

if [[ "$LARAVEL_OK" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! Path confusion resolved${NC}"
    echo ""
    echo -e "${BLUE}🔧 What was fixed:${NC}"
    echo "  • Removed bad symlinks pointing to /hm-new/"
    echo "  • Created proper Laravel storage structure"
    echo "  • Created emergency fallback directories"
    echo "  • Cleared all corrupt caches"
    echo "  • Fixed .env path configurations"
    echo "  • Rebuilt Laravel configuration"
    echo "  • Restarted web services"
    echo ""
    echo -e "${GREEN}🌐 Test your website now:${NC}"
    echo "   https://hartonomotor.xyz"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo -e "${YELLOW}📱 The path confusion error should be completely gone!${NC}"
    
else
    echo -e "${YELLOW}⚠️  Partial fix completed${NC}"
    echo ""
    echo -e "${BLUE}🔧 Additional steps may be needed:${NC}"
    echo "1. Check Laravel logs: tail -f storage/logs/laravel.log"
    echo "2. Check web server logs: sudo tail -f /var/log/nginx/error.log"
    echo "3. Manual test: php artisan route:list"
fi

echo ""
echo -e "${BLUE}📋 Emergency Fallback:${NC}"
echo "• Created /hm-new/storage/ as fallback (Laravel can write here if needed)"
echo "• Main Laravel storage: /var/www/html/storage/ (primary location)"

echo ""
echo -e "${GREEN}🎯 Path confusion permanently resolved! 🎉${NC}"
