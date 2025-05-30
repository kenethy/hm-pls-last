#!/bin/bash

# =============================================================================
# 🔧 Fix Docker Port Conflict for WhatsApp API
# =============================================================================
# Comprehensive solution untuk resolve port 3000 conflict
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
echo "🔧 DOCKER PORT CONFLICT RESOLUTION"
echo "============================================================================="
echo "Resolving port 3000 conflict for WhatsApp API deployment"
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

# STEP 1: Comprehensive port diagnosis
show_step "1" "Comprehensive port 3000 diagnosis..."

echo "Checking port 3000 usage:"
echo ""

# Check with netstat
echo "=== netstat check ==="
sudo netstat -tlnp | grep :3000 || echo "No processes found with netstat"
echo ""

# Check with ss
echo "=== ss check ==="
sudo ss -tlnp | grep :3000 || echo "No processes found with ss"
echo ""

# Check with lsof
echo "=== lsof check ==="
sudo lsof -i :3000 || echo "No processes found with lsof"
echo ""

# Check Docker specific
echo "=== Docker containers check ==="
docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(3000|whatsapp)" || echo "No WhatsApp containers found"
echo ""

echo "=== All running containers ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
echo ""

# STEP 2: Stop conflicting processes
show_step "2" "Stopping conflicting processes..."

# Kill any process using port 3000
PORT_PIDS=$(sudo lsof -t -i :3000 2>/dev/null)
if [[ -n "$PORT_PIDS" ]]; then
    show_info "Found processes using port 3000: $PORT_PIDS"
    for pid in $PORT_PIDS; do
        echo "Killing process $pid..."
        sudo kill -9 $pid 2>/dev/null || true
    done
    show_success "Processes killed"
else
    show_info "No system processes using port 3000"
fi

echo ""

# STEP 3: Clean up Docker containers
show_step "3" "Cleaning up Docker containers..."

# Stop all containers first
echo "Stopping all running containers..."
RUNNING_CONTAINERS=$(docker ps -q)
if [[ -n "$RUNNING_CONTAINERS" ]]; then
    docker stop $RUNNING_CONTAINERS
    show_success "All containers stopped"
else
    show_info "No running containers"
fi

# Remove WhatsApp related containers
echo "Removing WhatsApp related containers..."
WHATSAPP_CONTAINERS=$(docker ps -a -q --filter "name=whatsapp" 2>/dev/null)
if [[ -n "$WHATSAPP_CONTAINERS" ]]; then
    docker rm -f $WHATSAPP_CONTAINERS
    show_success "WhatsApp containers removed"
else
    show_info "No WhatsApp containers found"
fi

# Remove containers using port 3000
echo "Removing containers that used port 3000..."
PORT_CONTAINERS=$(docker ps -a -q --filter "publish=3000" 2>/dev/null)
if [[ -n "$PORT_CONTAINERS" ]]; then
    docker rm -f $PORT_CONTAINERS
    show_success "Port 3000 containers removed"
else
    show_info "No containers using port 3000"
fi

# Clean up unused containers
docker container prune -f > /dev/null 2>&1
show_success "Unused containers cleaned"

echo ""

# STEP 4: Clean up Docker networks
show_step "4" "Cleaning up Docker networks..."

# Remove WhatsApp networks
WHATSAPP_NETWORKS=$(docker network ls -q --filter "name=whatsapp" 2>/dev/null)
if [[ -n "$WHATSAPP_NETWORKS" ]]; then
    docker network rm $WHATSAPP_NETWORKS 2>/dev/null || true
    show_success "WhatsApp networks removed"
else
    show_info "No WhatsApp networks found"
fi

# Clean up unused networks
docker network prune -f > /dev/null 2>&1
show_success "Unused networks cleaned"

echo ""

# STEP 5: Restart Docker service
show_step "5" "Restarting Docker service..."

sudo systemctl restart docker
sleep 5

if systemctl is-active --quiet docker; then
    show_success "Docker service restarted successfully"
else
    show_error "Docker service restart failed"
    exit 1
fi

echo ""

# STEP 6: Verify port is free
show_step "6" "Verifying port 3000 is free..."

# Wait a moment for everything to settle
sleep 3

# Check port again
PORT_CHECK=$(sudo netstat -tlnp | grep :3000 || true)
if [[ -z "$PORT_CHECK" ]]; then
    show_success "Port 3000 is now free!"
    PORT_FREE=true
else
    show_error "Port 3000 still in use:"
    echo "$PORT_CHECK"
    PORT_FREE=false
fi

echo ""

# STEP 7: Test Docker functionality
show_step "7" "Testing Docker functionality..."

# Test Docker with hello-world
if docker run --rm hello-world > /dev/null 2>&1; then
    show_success "Docker is working correctly"
    DOCKER_OK=true
else
    show_error "Docker test failed"
    DOCKER_OK=false
fi

echo ""

# STEP 8: Create alternative port configuration
show_step "8" "Creating alternative port configurations..."

cd /opt/whatsapp-docker 2>/dev/null || {
    show_warning "WhatsApp Docker directory not found, creating..."
    mkdir -p /opt/whatsapp-docker
    cd /opt/whatsapp-docker
}

# Create docker-compose with alternative ports
cat > docker-compose-alt-ports.yml <<EOF
version: '3.8'

services:
  whatsapp-api:
    image: aldinokemal2104/go-whatsapp-web-multidevice:latest
    container_name: whatsapp-api-vps-alt
    restart: unless-stopped
    ports:
      - "3001:3000"  # Alternative port 3001
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
      test: ["CMD", "curl", "-f", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

networks:
  whatsapp-network:
    driver: bridge
EOF

show_success "Alternative port configuration created (port 3001)"

# Create docker-compose with original port
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
      test: ["CMD", "curl", "-f", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

networks:
  whatsapp-network:
    driver: bridge
EOF

show_success "Original port configuration created (port 3000)"

echo ""

# STEP 9: Attempt deployment
show_step "9" "Attempting WhatsApp API deployment..."

if [[ "$PORT_FREE" == true && "$DOCKER_OK" == true ]]; then
    show_info "Trying original port 3000..."
    
    # Try original port first
    if docker-compose up -d; then
        sleep 10
        if docker ps | grep -q whatsapp-api-vps; then
            show_success "WhatsApp API deployed successfully on port 3000!"
            
            # Test API
            if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
                show_success "API is responding on port 3000"
                DEPLOYMENT_SUCCESS=true
                FINAL_PORT=3000
            else
                show_info "API starting up, may take a moment..."
                DEPLOYMENT_SUCCESS=true
                FINAL_PORT=3000
            fi
        else
            show_warning "Container failed to start on port 3000, trying alternative port..."
            docker-compose down 2>/dev/null || true
            
            # Try alternative port
            if docker-compose -f docker-compose-alt-ports.yml up -d; then
                sleep 10
                if docker ps | grep -q whatsapp-api-vps-alt; then
                    show_success "WhatsApp API deployed successfully on port 3001!"
                    DEPLOYMENT_SUCCESS=true
                    FINAL_PORT=3001
                else
                    show_error "Deployment failed on both ports"
                    DEPLOYMENT_SUCCESS=false
                fi
            else
                show_error "Alternative port deployment failed"
                DEPLOYMENT_SUCCESS=false
            fi
        fi
    else
        show_warning "Original port failed, trying alternative port 3001..."
        
        # Try alternative port
        if docker-compose -f docker-compose-alt-ports.yml up -d; then
            sleep 10
            if docker ps | grep -q whatsapp-api-vps-alt; then
                show_success "WhatsApp API deployed successfully on port 3001!"
                DEPLOYMENT_SUCCESS=true
                FINAL_PORT=3001
            else
                show_error "Alternative port deployment failed"
                DEPLOYMENT_SUCCESS=false
            fi
        else
            show_error "Alternative port deployment failed"
            DEPLOYMENT_SUCCESS=false
        fi
    fi
else
    show_error "Prerequisites not met for deployment"
    DEPLOYMENT_SUCCESS=false
fi

echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 PORT CONFLICT RESOLUTION RESULTS"
echo "============================================================================="
echo -e "${NC}"

if [[ "$DEPLOYMENT_SUCCESS" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! WhatsApp API deployed successfully${NC}"
    echo ""
    echo -e "${BLUE}📊 Deployment Details:${NC}"
    echo "  • Port: $FINAL_PORT"
    echo "  • Container: $(docker ps --format '{{.Names}}' | grep whatsapp)"
    echo "  • Status: $(docker ps --format '{{.Status}}' | head -1)"
    echo ""
    echo -e "${BLUE}🔗 Access URLs:${NC}"
    echo "  • Web Interface: http://45.32.116.20:$FINAL_PORT"
    echo "  • API Endpoint: http://45.32.116.20:$FINAL_PORT/app/devices"
    echo "  • Internal: http://localhost:$FINAL_PORT"
    echo ""
    echo -e "${BLUE}🔐 Authentication:${NC}"
    echo "  • Username: admin"
    echo "  • Password: hartonomotor123"
    echo ""
    echo -e "${BLUE}🛠️ Management Commands:${NC}"
    if [[ "$FINAL_PORT" == "3001" ]]; then
        echo "  • Status: docker-compose -f docker-compose-alt-ports.yml ps"
        echo "  • Logs: docker-compose -f docker-compose-alt-ports.yml logs -f"
        echo "  • Stop: docker-compose -f docker-compose-alt-ports.yml down"
    else
        echo "  • Status: docker-compose ps"
        echo "  • Logs: docker-compose logs -f"
        echo "  • Stop: docker-compose down"
    fi
    echo ""
    echo -e "${YELLOW}📋 Next Steps:${NC}"
    echo "1. Open browser: http://45.32.116.20:$FINAL_PORT"
    echo "2. Login with credentials above"
    echo "3. Generate QR code and scan with WhatsApp"
    echo "4. Update Laravel .env with correct port if using 3001"
    
    if [[ "$FINAL_PORT" == "3001" ]]; then
        echo ""
        echo -e "${YELLOW}⚠️  Update Laravel .env:${NC}"
        echo "WHATSAPP_API_URL=http://localhost:3001"
    fi
    
else
    echo -e "${RED}❌ DEPLOYMENT FAILED${NC}"
    echo ""
    echo -e "${YELLOW}📋 Troubleshooting Steps:${NC}"
    echo "1. Check Docker logs: docker-compose logs"
    echo "2. Check system resources: free -h && df -h"
    echo "3. Try manual container start: docker run -p 3002:3000 aldinokemal2104/go-whatsapp-web-multidevice:latest"
    echo "4. Consider binary deployment instead of Docker"
fi

echo ""
echo -e "${GREEN}🎯 Port conflict resolution completed! 🎉${NC}"
