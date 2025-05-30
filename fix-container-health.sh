#!/bin/bash

# =============================================================================
# 🔧 Fix WhatsApp Container Health & External Access
# =============================================================================
# Diagnose and fix "unhealthy" container status
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
echo "🔧 FIX WHATSAPP CONTAINER HEALTH & EXTERNAL ACCESS"
echo "============================================================================="
echo "Diagnose and fix 'unhealthy' container status yang mencegah external access"
echo "============================================================================="
echo -e "${NC}"

# Fungsi helper
show_step() {
    echo -e "${YELLOW}📋 STEP $1: $2${NC}"
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

# STEP 1: Detailed container health diagnosis
show_step "1" "Detailed container health diagnosis..."

echo "=== Container Status ==="
docker ps | grep whatsapp
echo ""

echo "=== Container Health Details ==="
docker inspect whatsapp-api-vps | jq '.[0].State.Health' 2>/dev/null || docker inspect whatsapp-api-vps | grep -A 20 '"Health"'
echo ""

echo "=== Container Logs (Last 30 lines) ==="
docker logs whatsapp-api-vps --tail 30
echo ""

echo "=== Container Resource Usage ==="
docker stats whatsapp-api-vps --no-stream
echo ""

# STEP 2: Test internal container connectivity
show_step "2" "Testing internal container connectivity..."

# Test if curl is available in container
if docker exec whatsapp-api-vps which curl > /dev/null 2>&1; then
    show_success "curl available in container"
    
    # Test internal API
    if docker exec whatsapp-api-vps curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
        show_success "Internal API responding inside container"
        INTERNAL_CONTAINER_OK=true
    else
        show_error "Internal API not responding inside container"
        INTERNAL_CONTAINER_OK=false
        
        # Get detailed error
        echo "Detailed curl error:"
        docker exec whatsapp-api-vps curl -v http://localhost:3000/app/devices 2>&1 || echo "Curl failed"
    fi
else
    show_warning "curl not available in container, installing..."
    docker exec whatsapp-api-vps apk add --no-cache curl 2>/dev/null || \
    docker exec whatsapp-api-vps apt-get update && apt-get install -y curl 2>/dev/null || \
    show_error "Failed to install curl in container"
fi

echo ""

# STEP 3: Test host-level connectivity
show_step "3" "Testing host-level connectivity..."

# Test from host to container
if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    show_success "Host can access container on localhost:3000"
    HOST_LOCAL_OK=true
else
    show_error "Host cannot access container on localhost:3000"
    HOST_LOCAL_OK=false
fi

# Test from host to external IP
if curl -s -f http://45.32.116.20:3000/app/devices > /dev/null 2>&1; then
    show_success "Host can access container on external IP"
    HOST_EXTERNAL_OK=true
else
    show_error "Host cannot access container on external IP"
    HOST_EXTERNAL_OK=false
fi

echo ""

# STEP 4: Fix healthcheck if needed
show_step "4" "Fixing container healthcheck..."

if [[ "$INTERNAL_CONTAINER_OK" != true ]]; then
    show_info "Container internal API not working, investigating..."
    
    # Check if WhatsApp service is actually running
    echo "Processes inside container:"
    docker exec whatsapp-api-vps ps aux 2>/dev/null || docker exec whatsapp-api-vps ps 2>/dev/null
    echo ""
    
    # Check if port 3000 is listening inside container
    echo "Ports listening inside container:"
    docker exec whatsapp-api-vps netstat -tlnp 2>/dev/null || docker exec whatsapp-api-vps ss -tlnp 2>/dev/null
    echo ""
    
    # Restart WhatsApp service inside container
    show_info "Attempting to restart WhatsApp service..."
    docker restart whatsapp-api-vps
    
    # Wait for restart
    sleep 15
    
    # Test again
    if docker exec whatsapp-api-vps curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
        show_success "Container internal API working after restart"
        INTERNAL_CONTAINER_OK=true
    else
        show_error "Container internal API still not working"
    fi
fi

echo ""

# STEP 5: Fix docker-compose healthcheck
show_step "5" "Fixing docker-compose healthcheck configuration..."

cd /opt/whatsapp-docker

# Check current healthcheck
echo "Current healthcheck in docker-compose.yml:"
grep -A 5 "healthcheck:" docker-compose.yml
echo ""

# Create improved docker-compose.yml
show_info "Creating improved docker-compose.yml with better healthcheck..."

cp docker-compose.yml docker-compose.yml.backup

cat > docker-compose.yml <<EOF
version: '3.8'

services:
  whatsapp-api:
    image: aldinokemal2104/go-whatsapp-web-multidevice:latest
    container_name: whatsapp-api-vps
    restart: unless-stopped
    ports:
      - "3000:3000"
    environment:
      - WHATSAPP_BASIC_AUTH=admin:hartonomotor123
      - WHATSAPP_WEBHOOK=https://hartonomotor.xyz/webhook/whatsapp
      - WHATSAPP_WEBHOOK_SECRET=hartonomotor_webhook_secret
      - APP_DEBUG=false
      - APP_OS=HartonoMotor
    volumes:
      - ./data:/app/storages
      - ./logs:/app/logs
    networks:
      - whatsapp-network
    healthcheck:
      test: ["CMD-SHELL", "wget --no-verbose --tries=1 --spider http://localhost:3000/app/devices || exit 1"]
      interval: 30s
      timeout: 10s
      retries: 5
      start_period: 60s

networks:
  whatsapp-network:
    driver: bridge

volumes:
  whatsapp-data:
    driver: local
EOF

show_success "Improved docker-compose.yml created"

# Restart with new configuration
show_info "Restarting container with improved healthcheck..."
docker-compose down
sleep 5
docker-compose up -d

# Wait for container to start
show_info "Waiting for container to start (60 seconds)..."
sleep 60

echo ""

# STEP 6: Test external access
show_step "6" "Testing external access..."

# Test without authentication (should get 401)
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://45.32.116.20:3000/app/devices)
if [[ "$HTTP_CODE" == "401" ]]; then
    show_success "External access working (got 401 - authentication required)"
    EXTERNAL_ACCESS_OK=true
elif [[ "$HTTP_CODE" == "200" ]]; then
    show_success "External access working (got 200 - no auth required)"
    EXTERNAL_ACCESS_OK=true
else
    show_error "External access failed (HTTP $HTTP_CODE)"
    EXTERNAL_ACCESS_OK=false
fi

# Test with authentication
if curl -s -f -u admin:hartonomotor123 http://45.32.116.20:3000/app/devices > /dev/null 2>&1; then
    show_success "External access with authentication working"
    EXTERNAL_AUTH_OK=true
    
    # Get actual response
    API_RESPONSE=$(curl -s -u admin:hartonomotor123 http://45.32.116.20:3000/app/devices)
    show_info "API Response: $API_RESPONSE"
else
    show_error "External access with authentication failed"
    EXTERNAL_AUTH_OK=false
fi

echo ""

# STEP 7: Final container status
show_step "7" "Final container status check..."

echo "=== Container Status ==="
docker ps | grep whatsapp
echo ""

echo "=== Container Health ==="
docker inspect whatsapp-api-vps | jq '.[0].State.Health.Status' 2>/dev/null || echo "Health status not available"
echo ""

echo "=== Port Binding ==="
docker port whatsapp-api-vps
echo ""

# STEP 8: Browser test instructions
show_step "8" "Browser test instructions..."

if [[ "$EXTERNAL_AUTH_OK" == true ]]; then
    echo -e "${GREEN}🎉 SUCCESS! External access is working${NC}"
    echo ""
    echo "Test in your browser:"
    echo "1. Open: http://45.32.116.20:3000"
    echo "2. Enter credentials:"
    echo "   Username: admin"
    echo "   Password: hartonomotor123"
    echo "3. You should see WhatsApp API interface"
    echo "4. Click 'Login' to generate QR code"
    echo "5. Scan QR with WhatsApp mobile app"
    echo ""
    
    echo "API Endpoints to test:"
    echo "• Device Status: http://45.32.116.20:3000/app/devices"
    echo "• QR Login: http://45.32.116.20:3000/app/login"
    echo "• API Docs: http://45.32.116.20:3000/docs"
    
else
    echo -e "${YELLOW}⚠️  External access still has issues${NC}"
    echo ""
    echo "Troubleshooting steps:"
    echo "1. Check Vultr dashboard firewall settings"
    echo "2. Verify VPS network configuration"
    echo "3. Try accessing from different network"
    echo "4. Check container logs: docker logs whatsapp-api-vps"
fi

echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 CONTAINER HEALTH FIX COMPLETED"
echo "============================================================================="
echo -e "${NC}"

echo "Status Summary:"
echo "  • Container Internal API: $([[ "$INTERNAL_CONTAINER_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo "  • Host Local Access: $([[ "$HOST_LOCAL_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo "  • Host External Access: $([[ "$HOST_EXTERNAL_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo "  • External Access: $([[ "$EXTERNAL_ACCESS_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo "  • External Auth Access: $([[ "$EXTERNAL_AUTH_OK" == true ]] && echo "✅ Working" || echo "❌ Failed")"
echo ""

if [[ "$EXTERNAL_AUTH_OK" == true ]]; then
    echo -e "${GREEN}🎯 SUCCESS! WhatsApp API is now accessible externally!${NC}"
    echo ""
    echo "Ready for Laravel integration:"
    echo "https://hartonomotor.xyz/whatsapp/qr-generator"
else
    echo -e "${YELLOW}⚠️  Additional troubleshooting may be needed${NC}"
    echo ""
    echo "Check container logs for more details:"
    echo "docker logs whatsapp-api-vps --tail 50"
fi

echo ""
echo "Current container status:"
docker ps | grep whatsapp
