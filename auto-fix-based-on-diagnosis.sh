#!/bin/bash

# =============================================================================
# 🤖 AUTO FIX BASED ON DIAGNOSIS
# =============================================================================
# Script yang akan otomatis memperbaiki masalah berdasarkan diagnosis
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
echo "🤖 AUTO FIX BASED ON SMART DIAGNOSIS"
echo "============================================================================="
echo "Script akan otomatis mendeteksi dan memperbaiki masalah"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_step() {
    echo -e "${YELLOW}🔧 $1${NC}"
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

# STEP 1: Detect WhatsApp API status
show_step "Detecting WhatsApp API status..."

# Check containers
RUNNING_CONTAINER=$(docker ps --format "{{.Names}}" | grep whatsapp | head -1)
if [[ -n "$RUNNING_CONTAINER" ]]; then
    show_success "Container found: $RUNNING_CONTAINER"
    
    # Test different connection methods
    CONNECTION_TESTS=(
        "http://localhost:3000"
        "http://127.0.0.1:3000"
    )
    
    WORKING_URL=""
    for url in "${CONNECTION_TESTS[@]}"; do
        if timeout 5 curl -s -f "$url/app/devices" > /dev/null 2>&1; then
            WORKING_URL="$url"
            show_success "API responding at: $url"
            break
        fi
    done
    
    # If localhost doesn't work, try container IP
    if [[ -z "$WORKING_URL" ]]; then
        CONTAINER_IP=$(docker inspect "$RUNNING_CONTAINER" --format='{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' 2>/dev/null)
        if [[ -n "$CONTAINER_IP" ]]; then
            if timeout 5 curl -s -f "http://$CONTAINER_IP:3000/app/devices" > /dev/null 2>&1; then
                WORKING_URL="http://$CONTAINER_IP:3000"
                show_success "API responding at container IP: $WORKING_URL"
            fi
        fi
    fi
    
    # If still no working URL, container might be unhealthy
    if [[ -z "$WORKING_URL" ]]; then
        show_error "Container running but API not responding"
        
        show_step "Attempting to fix unhealthy container..."
        
        # Check container logs for errors
        show_info "Checking container logs..."
        docker logs "$RUNNING_CONTAINER" --tail 10
        
        # Restart container
        show_info "Restarting container..."
        docker restart "$RUNNING_CONTAINER"
        
        # Wait and test again
        show_info "Waiting for container to be ready..."
        sleep 15
        
        for url in "${CONNECTION_TESTS[@]}"; do
            if timeout 5 curl -s -f "$url/app/devices" > /dev/null 2>&1; then
                WORKING_URL="$url"
                show_success "API responding after restart: $url"
                break
            fi
        done
    fi
    
else
    show_error "No WhatsApp container running"
    
    show_step "Attempting to start WhatsApp API..."
    
    # Try to start production container
    if [[ -f "/opt/whatsapp-api-production/start.sh" ]]; then
        show_info "Starting production container..."
        /opt/whatsapp-api-production/start.sh
        
        # Wait for startup
        sleep 15
        
        # Test connection
        if timeout 5 curl -s -f "http://localhost:3000/app/devices" > /dev/null 2>&1; then
            WORKING_URL="http://localhost:3000"
            show_success "Production API started successfully"
        fi
    fi
    
    # If production start failed, try manual docker start
    if [[ -z "$WORKING_URL" ]]; then
        STOPPED_CONTAINER=$(docker ps -a --format "{{.Names}}" | grep whatsapp | head -1)
        if [[ -n "$STOPPED_CONTAINER" ]]; then
            show_info "Starting stopped container: $STOPPED_CONTAINER"
            docker start "$STOPPED_CONTAINER"
            sleep 10
            
            if timeout 5 curl -s -f "http://localhost:3000/app/devices" > /dev/null 2>&1; then
                WORKING_URL="http://localhost:3000"
                show_success "Container started successfully"
            fi
        fi
    fi
fi

echo ""

# STEP 2: Update Laravel configuration if API is working
if [[ -n "$WORKING_URL" ]]; then
    show_step "Updating Laravel configuration..."
    
    # Find Laravel directory
    LARAVEL_DIRS=("/var/www/html" "/hm-new")
    LARAVEL_DIR=""
    
    for dir in "${LARAVEL_DIRS[@]}"; do
        if [[ -f "$dir/.env" ]] && [[ -f "$dir/artisan" ]]; then
            LARAVEL_DIR="$dir"
            break
        fi
    done
    
    if [[ -n "$LARAVEL_DIR" ]]; then
        show_success "Laravel found at: $LARAVEL_DIR"
        
        cd "$LARAVEL_DIR"
        
        # Backup .env
        cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
        show_info "Backup .env created"
        
        # Update or add WHATSAPP_API_URL
        if grep -q "WHATSAPP_API_URL=" .env; then
            sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=$WORKING_URL|" .env
            show_success "Updated WHATSAPP_API_URL to: $WORKING_URL"
        else
            echo "" >> .env
            echo "# WhatsApp API Configuration" >> .env
            echo "WHATSAPP_API_URL=$WORKING_URL" >> .env
            show_success "Added WHATSAPP_API_URL: $WORKING_URL"
        fi
        
        # Add basic auth config if missing
        if ! grep -q "WHATSAPP_BASIC_AUTH_USERNAME=" .env; then
            echo "WHATSAPP_BASIC_AUTH_USERNAME=admin" >> .env
            echo "WHATSAPP_BASIC_AUTH_PASSWORD=" >> .env
            show_info "Added basic auth configuration"
        fi
        
        # Clear and rebuild cache
        show_info "Clearing Laravel cache..."
        php artisan config:clear > /dev/null 2>&1
        php artisan config:cache > /dev/null 2>&1
        show_success "Laravel cache updated"
        
        # Test Laravel connection
        show_step "Testing Laravel → WhatsApp API connection..."
        
        TEST_RESULT=$(php artisan tinker --execute="
            try {
                \$response = \Illuminate\Support\Facades\Http::timeout(10)->get(config('whatsapp.api_url') . '/app/devices');
                echo \$response->successful() ? 'SUCCESS' : 'FAILED';
            } catch (Exception \$e) {
                echo 'ERROR: ' . \$e->getMessage();
            }
        " 2>/dev/null)
        
        if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
            show_success "Laravel successfully connected to WhatsApp API"
            CONNECTION_FIXED=true
        else
            show_error "Laravel connection test failed: $TEST_RESULT"
            CONNECTION_FIXED=false
        fi
        
    else
        show_error "Laravel directory not found"
        CONNECTION_FIXED=false
    fi
    
else
    show_error "Could not get WhatsApp API working"
    CONNECTION_FIXED=false
fi

echo ""

# STEP 3: Final verification
if [[ "$CONNECTION_FIXED" == true ]]; then
    show_step "Final verification..."
    
    # Test web endpoint
    if curl -s "https://hartonomotor.xyz/whatsapp/check-status" | grep -q "success\|devices"; then
        show_success "Web endpoint is working"
        WEB_WORKING=true
    else
        show_error "Web endpoint still not working"
        WEB_WORKING=false
    fi
fi

echo ""

# RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 AUTO FIX RESULTS"
echo "============================================================================="
echo -e "${NC}"

if [[ "$CONNECTION_FIXED" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! WhatsApp API connection fixed${NC}"
    echo ""
    echo -e "${BLUE}📊 Configuration:${NC}"
    echo "  • WhatsApp API URL: $WORKING_URL"
    echo "  • Laravel Directory: $LARAVEL_DIR"
    echo "  • Container: $RUNNING_CONTAINER"
    echo ""
    echo -e "${GREEN}🌐 Test your QR generator now:${NC}"
    echo "   https://hartonomotor.xyz/whatsapp/qr-generator"
    echo ""
    echo -e "${YELLOW}📱 Click 'Generate Fresh QR Code' - it should work now!${NC}"
    
elif [[ -n "$WORKING_URL" ]]; then
    echo -e "${YELLOW}⚠️  WhatsApp API is working but Laravel connection failed${NC}"
    echo ""
    echo -e "${BLUE}🔧 Manual steps needed:${NC}"
    echo "1. Edit Laravel .env manually:"
    echo "   nano $LARAVEL_DIR/.env"
    echo "   Add: WHATSAPP_API_URL=$WORKING_URL"
    echo ""
    echo "2. Clear cache:"
    echo "   cd $LARAVEL_DIR"
    echo "   php artisan config:clear && php artisan config:cache"
    
else
    echo -e "${RED}❌ Could not fix WhatsApp API${NC}"
    echo ""
    echo -e "${BLUE}🆘 Manual intervention needed:${NC}"
    echo "1. Check if containers exist:"
    echo "   docker ps -a | grep whatsapp"
    echo ""
    echo "2. Deploy WhatsApp API:"
    echo "   ./deploy-whatsapp-production.sh"
    echo ""
    echo "3. Check production directory:"
    echo "   ls -la /opt/whatsapp-api-production/"
fi

echo ""
echo -e "${BLUE}🔍 Remember: This is NOT a compatibility issue!${NC}"
echo -e "${BLUE}The project works fine, it just needs proper network configuration.${NC}"
