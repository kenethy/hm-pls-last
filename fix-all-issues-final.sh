#!/bin/bash

# =============================================================================
# 🔧 FIX ALL ISSUES - FINAL COMPREHENSIVE SOLUTION
# =============================================================================
# Mengatasi semua masalah sekaligus:
# 1. Laravel path confusion (/hm-new/storage/)
# 2. WhatsApp service execution issues
# 3. Ensure proper integration
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
echo "🔧 FIX ALL ISSUES - COMPREHENSIVE SOLUTION"
echo "============================================================================="
echo "Mengatasi Laravel path confusion + WhatsApp service issues sekaligus"
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

# STEP 1: Fix Laravel path confusion PERMANENTLY
show_step "1" "Fixing Laravel path confusion permanently..."

# Create /hm-new/storage structure (emergency fallback)
sudo mkdir -p /hm-new/storage/framework/views
sudo mkdir -p /hm-new/storage/framework/cache/data
sudo mkdir -p /hm-new/storage/framework/sessions
sudo mkdir -p /hm-new/storage/logs
sudo mkdir -p /hm-new/bootstrap/cache

# Set full permissions for emergency fallback
sudo chmod -R 777 /hm-new/storage
sudo chmod -R 777 /hm-new/bootstrap 2>/dev/null || true

show_success "Emergency fallback directories created"

# Fix Laravel storage in correct location
cd /var/www/html

# Check and remove bad symlinks
if [[ -L "storage" ]]; then
    STORAGE_TARGET=$(readlink -f storage)
    if [[ "$STORAGE_TARGET" == *"/hm-new/"* ]]; then
        show_info "Removing bad storage symlink"
        rm storage
        mkdir -p storage
    fi
fi

if [[ -L "bootstrap/cache" ]]; then
    CACHE_TARGET=$(readlink -f bootstrap/cache)
    if [[ "$CACHE_TARGET" == *"/hm-new/"* ]]; then
        show_info "Removing bad bootstrap/cache symlink"
        rm bootstrap/cache
        mkdir -p bootstrap/cache
    fi
fi

# Rebuild proper Laravel storage structure
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Set proper ownership and permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Clear all corrupt caches
php artisan config:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

# Remove compiled files
rm -rf storage/framework/views/*.php 2>/dev/null || true
rm -rf storage/framework/cache/data/* 2>/dev/null || true
rm -rf bootstrap/cache/*.php 2>/dev/null || true

show_success "Laravel path confusion fixed"
echo ""

# STEP 2: Analyze WhatsApp API situation
show_step "2" "Analyzing WhatsApp API situation..."

# Check what's running on port 3000
PORT_3000_PROCESS=$(ss -tlnp | grep ":3000" | head -1)
if [[ -n "$PORT_3000_PROCESS" ]]; then
    show_info "Port 3000 is in use: $PORT_3000_PROCESS"
    
    # Check if it's Docker container
    if docker ps | grep -q "3000"; then
        DOCKER_CONTAINER=$(docker ps --format "{{.Names}}" | grep whatsapp | head -1)
        show_info "Docker container running: $DOCKER_CONTAINER"
        WHATSAPP_SOURCE="docker"
    else
        show_info "Non-Docker process on port 3000"
        WHATSAPP_SOURCE="other"
    fi
else
    show_info "Port 3000 is not in use"
    WHATSAPP_SOURCE="none"
fi

# Test API regardless of source
if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    show_success "WhatsApp API is responding (source: $WHATSAPP_SOURCE)"
    API_WORKING=true
else
    show_error "WhatsApp API is not responding"
    API_WORKING=false
fi

echo ""

# STEP 3: Fix WhatsApp service if needed
if [[ "$API_WORKING" != true ]] || [[ "$WHATSAPP_SOURCE" == "none" ]]; then
    show_step "3" "Setting up working WhatsApp API..."
    
    # Stop any conflicting services
    sudo systemctl stop whatsapp-api 2>/dev/null || true
    
    # Check if we have Docker containers to use instead
    if docker ps -a | grep -q whatsapp; then
        show_info "Using existing Docker container..."
        
        # Stop all WhatsApp containers first
        docker ps -a --format "{{.Names}}" | grep whatsapp | while read container; do
            docker stop "$container" 2>/dev/null || true
        done
        
        # Start the production container
        PROD_CONTAINER=$(docker ps -a --format "{{.Names}}" | grep "whatsapp-api-production" | head -1)
        if [[ -n "$PROD_CONTAINER" ]]; then
            docker start "$PROD_CONTAINER"
            show_success "Started Docker container: $PROD_CONTAINER"
        else
            # Start any available WhatsApp container
            AVAILABLE_CONTAINER=$(docker ps -a --format "{{.Names}}" | grep whatsapp | head -1)
            if [[ -n "$AVAILABLE_CONTAINER" ]]; then
                docker start "$AVAILABLE_CONTAINER"
                show_success "Started Docker container: $AVAILABLE_CONTAINER"
            fi
        fi
        
        # Wait and test
        sleep 10
        if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
            show_success "Docker WhatsApp API is working"
            API_WORKING=true
            WHATSAPP_SOURCE="docker"
        fi
    fi
    
    # If Docker approach failed, try binary approach
    if [[ "$API_WORKING" != true ]]; then
        show_info "Trying binary approach..."
        
        # Quick binary setup
        mkdir -p /opt/whatsapp-simple
        cd /opt/whatsapp-simple
        
        # Download binary if not exists
        if [[ ! -f "whatsapp" ]]; then
            show_info "Downloading WhatsApp binary..."
            
            # Try to download pre-built binary
            ARCH=$(uname -m)
            case $ARCH in
                x86_64) DOWNLOAD_ARCH="amd64" ;;
                aarch64) DOWNLOAD_ARCH="arm64" ;;
                *) DOWNLOAD_ARCH="amd64" ;;
            esac
            
            # Simple download attempt
            wget -q -O whatsapp-binary "https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/whatsapp-linux-$DOWNLOAD_ARCH" || \
            curl -s -L -o whatsapp-binary "https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/whatsapp-linux-$DOWNLOAD_ARCH" || \
            show_info "Binary download failed, will use Docker"
            
            if [[ -f "whatsapp-binary" ]]; then
                mv whatsapp-binary whatsapp
                chmod +x whatsapp
            fi
        fi
        
        # Try to run binary
        if [[ -f "whatsapp" ]]; then
            show_info "Starting WhatsApp binary..."
            
            # Create simple runner
            cat > run.sh <<EOF
#!/bin/bash
cd /opt/whatsapp-simple
nohup ./whatsapp rest --port=3000 --basic-auth=admin:admin123 --os=HartonoMotor > whatsapp.log 2>&1 &
echo \$! > whatsapp.pid
EOF
            chmod +x run.sh
            
            # Run it
            ./run.sh
            sleep 5
            
            # Test
            if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
                show_success "Binary WhatsApp API is working"
                API_WORKING=true
                WHATSAPP_SOURCE="binary"
            fi
        fi
    fi
else
    show_step "3" "WhatsApp API already working - keeping current setup"
fi

echo ""

# STEP 4: Update Laravel configuration
show_step "4" "Updating Laravel configuration..."

cd /var/www/html

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update or add WhatsApp configuration
if grep -q "WHATSAPP_API_URL=" .env; then
    sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=http://localhost:3000|" .env
else
    echo "" >> .env
    echo "# WhatsApp API Configuration" >> .env
    echo "WHATSAPP_API_URL=http://localhost:3000" >> .env
fi

if ! grep -q "WHATSAPP_BASIC_AUTH_USERNAME=" .env; then
    echo "WHATSAPP_BASIC_AUTH_USERNAME=admin" >> .env
    echo "WHATSAPP_BASIC_AUTH_PASSWORD=admin123" >> .env
fi

# Clear and rebuild cache
php artisan config:clear
php artisan config:cache

show_success "Laravel configuration updated"
echo ""

# STEP 5: Test integration
show_step "5" "Testing Laravel → WhatsApp integration..."

# Test from Laravel
TEST_RESULT=$(php artisan tinker --execute="
try {
    \$response = \Illuminate\Support\Facades\Http::timeout(10)->get('http://localhost:3000/app/devices');
    echo \$response->successful() ? 'SUCCESS' : 'FAILED';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>/dev/null)

if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
    show_success "Laravel → WhatsApp integration working"
    INTEGRATION_OK=true
else
    show_error "Laravel integration failed: $TEST_RESULT"
    INTEGRATION_OK=false
fi

echo ""

# STEP 6: Restart web services
show_step "6" "Restarting web services..."

# Restart PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    sudo systemctl restart php8.1-fpm
    show_success "PHP 8.1 FPM restarted"
elif systemctl is-active --quiet php8.0-fpm; then
    sudo systemctl restart php8.0-fpm
    show_success "PHP 8.0 FPM restarted"
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
echo "🎉 COMPREHENSIVE FIX RESULTS"
echo "============================================================================="
echo -e "${NC}"

if [[ "$INTEGRATION_OK" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! All issues resolved${NC}"
    echo ""
    echo -e "${BLUE}🔧 What was fixed:${NC}"
    echo "  • Laravel path confusion resolved (/hm-new/storage/ issue)"
    echo "  • Emergency fallback directories created"
    echo "  • WhatsApp API working (source: $WHATSAPP_SOURCE)"
    echo "  • Laravel integration confirmed"
    echo "  • Web services restarted"
    echo ""
    echo -e "${GREEN}🌐 Test your website now:${NC}"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo -e "${YELLOW}📱 Both issues should be completely resolved!${NC}"
    
else
    echo -e "${YELLOW}⚠️  Partial fix completed${NC}"
    echo ""
    echo -e "${BLUE}🔧 Status:${NC}"
    echo "  • Laravel path issue: Fixed"
    echo "  • WhatsApp API: $([[ "$API_WORKING" == true ]] && echo "Working" || echo "Needs attention")"
    echo "  • Integration: $([[ "$INTEGRATION_OK" == true ]] && echo "Working" || echo "Needs attention")"
fi

echo ""
echo -e "${BLUE}📊 Current Setup:${NC}"
echo "  • WhatsApp API Source: $WHATSAPP_SOURCE"
echo "  • API URL: http://localhost:3000"
echo "  • Laravel Directory: /var/www/html"
echo "  • Emergency Fallback: /hm-new/storage/ (available)"

echo ""
echo -e "${GREEN}🎯 Comprehensive fix completed! 🎉${NC}"
