#!/bin/bash

# =============================================================================
# 🚀 Deploy Docker WhatsApp API di VPS (Exact Same as Local)
# =============================================================================
# Replicate exact local Docker configuration yang berhasil ke VPS
# Menggunakan Docker Compose seperti di local development
# =============================================================================

# Warna
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${PURPLE}"
echo "============================================================================="
echo "🚀 DEPLOY DOCKER WHATSAPP API DI VPS (EXACT SAME AS LOCAL)"
echo "============================================================================="
echo "Replicate konfigurasi local Docker yang berhasil ke VPS 45.32.116.20"
echo "Menggunakan Docker Compose approach yang sama seperti di local"
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

# Configuration
PROJECT_DIR="/opt/whatsapp-docker"
CONTAINER_NAME="whatsapp-api-vps"
API_PORT="3000"
BASIC_AUTH_USER="admin"
BASIC_AUTH_PASS="hartonomotor123"

# STEP 1: Check system requirements
show_step "1" "Checking system requirements..."

# Check if running as root or with sudo
if [[ $EUID -ne 0 ]]; then
    show_error "This script must be run as root or with sudo"
    exit 1
fi

# Check system resources with better detection
MEMORY_KB=$(grep MemTotal /proc/meminfo | awk '{print $2}')
MEMORY_MB=$((MEMORY_KB / 1024))
MEMORY_GB=$(echo "scale=1; $MEMORY_MB / 1024" | bc -l 2>/dev/null || echo "$(($MEMORY_MB / 1024))")

DISK_GB=$(df -BG / | awk 'NR==2{print $4}' | sed 's/G//')

show_info "System: $(uname -a)"
show_info "Memory: ${MEMORY_MB}MB (${MEMORY_GB}GB)"
show_info "Disk: ${DISK_GB}GB available"
show_info "IP: 45.32.116.20"

# More detailed memory info
show_info "Memory details:"
free -h | head -2

# Check if we have enough memory (minimum 512MB for basic operation)
if [[ $MEMORY_MB -lt 512 ]]; then
    show_error "Insufficient memory. Found: ${MEMORY_MB}MB, Minimum: 512MB required."
    show_info "Consider upgrading VPS plan or closing other services."
    exit 1
elif [[ $MEMORY_MB -lt 1024 ]]; then
    show_warning "Low memory detected: ${MEMORY_MB}MB. WhatsApp API may run slowly."
    show_info "Recommended: 1GB+ for optimal performance."
    echo ""
    read -p "Continue anyway? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
else
    show_success "Memory check passed: ${MEMORY_MB}MB available"
fi

echo ""

# STEP 2: Install Docker (if not installed)
show_step "2" "Installing Docker and Docker Compose..."

# Check if Docker is installed
if command -v docker &> /dev/null; then
    show_info "Docker already installed: $(docker --version)"
else
    show_info "Installing Docker..."

    # Update system
    apt update

    # Install dependencies
    apt install -y \
        apt-transport-https \
        ca-certificates \
        curl \
        gnupg \
        lsb-release

    # Add Docker GPG key
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

    # Add Docker repository
    echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null

    # Install Docker
    apt update
    apt install -y docker-ce docker-ce-cli containerd.io

    show_success "Docker installed"
fi

# Check if Docker Compose is installed
if command -v docker-compose &> /dev/null; then
    show_info "Docker Compose already installed: $(docker-compose --version)"
else
    show_info "Installing Docker Compose..."

    # Install Docker Compose
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose

    show_success "Docker Compose installed"
fi

# Start Docker service
systemctl enable docker
systemctl start docker

# Test Docker
if docker run hello-world > /dev/null 2>&1; then
    show_success "Docker is working correctly"
else
    show_error "Docker test failed"
    exit 1
fi

echo ""

# STEP 3: Create project structure (same as local)
show_step "3" "Creating project structure (same as local)..."

# Create project directory
mkdir -p $PROJECT_DIR
cd $PROJECT_DIR

# Create subdirectories (same structure as local)
mkdir -p {data,config,logs}
mkdir -p data/{sessions,qrcode}

show_success "Project structure created at $PROJECT_DIR"

# Show structure
show_info "Directory structure:"
tree $PROJECT_DIR 2>/dev/null || ls -la $PROJECT_DIR
echo ""

# STEP 4: Create Docker Compose file (exact same as local)
show_step "4" "Creating Docker Compose configuration..."

cat > docker-compose.yml <<EOF
version: '3.8'

services:
  whatsapp-api:
    image: aldinokemal/go-whatsapp-web-multidevice:latest
    container_name: $CONTAINER_NAME
    restart: unless-stopped
    ports:
      - "$API_PORT:3000"
    environment:
      - WHATSAPP_BASIC_AUTH=$BASIC_AUTH_USER:$BASIC_AUTH_PASS
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

volumes:
  whatsapp-data:
    driver: local
EOF

show_success "Docker Compose file created"

# STEP 5: Create environment file
show_step "5" "Creating environment configuration..."

cat > .env <<EOF
# WhatsApp API Configuration for VPS
COMPOSE_PROJECT_NAME=whatsapp-hartono
WHATSAPP_PORT=$API_PORT
WHATSAPP_BASIC_AUTH_USER=$BASIC_AUTH_USER
WHATSAPP_BASIC_AUTH_PASS=$BASIC_AUTH_PASS
WHATSAPP_WEBHOOK_URL=https://hartonomotor.xyz/webhook/whatsapp
WHATSAPP_WEBHOOK_SECRET=hartonomotor_webhook_secret
EOF

show_success "Environment file created"
echo ""

# STEP 6: Configure firewall
show_step "6" "Configuring firewall..."

# Install ufw if not installed
if ! command -v ufw &> /dev/null; then
    apt install -y ufw
fi

# Configure firewall rules
ufw allow ssh
ufw allow 80/tcp
ufw allow 443/tcp
ufw allow $API_PORT/tcp

# Enable firewall
ufw --force enable

show_success "Firewall configured"
echo ""

# STEP 7: Start WhatsApp API (same as local)
show_step "7" "Starting WhatsApp API container..."

# Pull latest image
docker-compose pull

# Start services
docker-compose up -d

# Wait for container to be ready
show_info "Waiting for container to start..."
sleep 15

# Check container status
if docker ps | grep -q $CONTAINER_NAME; then
    show_success "Container started successfully"

    # Show container info
    show_info "Container status:"
    docker ps --filter name=$CONTAINER_NAME --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

else
    show_error "Container failed to start"
    echo ""
    show_info "Container logs:"
    docker-compose logs
    exit 1
fi

echo ""

# STEP 8: Test API connectivity
show_step "8" "Testing API connectivity..."

# Wait a bit more for API to be ready
sleep 10

# Test internal connectivity
if curl -s -f http://localhost:$API_PORT/app/devices > /dev/null 2>&1; then
    show_success "API responding on localhost:$API_PORT"
    INTERNAL_OK=true
else
    show_warning "API not responding on localhost yet"
    INTERNAL_OK=false
fi

# Test external connectivity
if curl -s -f http://45.32.116.20:$API_PORT/app/devices > /dev/null 2>&1; then
    show_success "API responding on external IP"
    EXTERNAL_OK=true
else
    show_warning "API not responding on external IP (may need authentication)"
    EXTERNAL_OK=false
fi

# Test with authentication
if curl -s -f -u $BASIC_AUTH_USER:$BASIC_AUTH_PASS http://localhost:$API_PORT/app/devices > /dev/null 2>&1; then
    show_success "API responding with authentication"
    AUTH_OK=true

    # Get API response
    API_RESPONSE=$(curl -s -u $BASIC_AUTH_USER:$BASIC_AUTH_PASS http://localhost:$API_PORT/app/devices)
    show_info "API Response: $API_RESPONSE"
else
    show_error "API not responding with authentication"
    AUTH_OK=false
fi

echo ""

# STEP 9: Create management scripts
show_step "9" "Creating management scripts..."

# Start script
cat > start.sh <<EOF
#!/bin/bash
cd $PROJECT_DIR
docker-compose up -d
echo "WhatsApp API started"
docker-compose ps
EOF

# Stop script
cat > stop.sh <<EOF
#!/bin/bash
cd $PROJECT_DIR
docker-compose down
echo "WhatsApp API stopped"
EOF

# Restart script
cat > restart.sh <<EOF
#!/bin/bash
cd $PROJECT_DIR
docker-compose restart
echo "WhatsApp API restarted"
docker-compose ps
EOF

# Status script
cat > status.sh <<EOF
#!/bin/bash
cd $PROJECT_DIR
echo "=== Container Status ==="
docker-compose ps
echo ""
echo "=== API Health Check ==="
curl -s http://localhost:$API_PORT/app/devices -u $BASIC_AUTH_USER:$BASIC_AUTH_PASS | jq . || echo "API not responding"
echo ""
echo "=== Container Logs (last 20 lines) ==="
docker-compose logs --tail=20
EOF

# Logs script
cat > logs.sh <<EOF
#!/bin/bash
cd $PROJECT_DIR
echo "Following WhatsApp API logs (Ctrl+C to stop)..."
docker-compose logs -f
EOF

# QR script
cat > qr.sh <<EOF
#!/bin/bash
cd $PROJECT_DIR
echo "=== QR Code Login ==="
echo "Open browser and go to: http://45.32.116.20:$API_PORT"
echo "Username: $BASIC_AUTH_USER"
echo "Password: $BASIC_AUTH_PASS"
echo ""
echo "Or use curl to get QR:"
curl -s -u $BASIC_AUTH_USER:$BASIC_AUTH_PASS http://localhost:$API_PORT/app/login
EOF

# Make scripts executable
chmod +x *.sh

show_success "Management scripts created"
echo ""

# STEP 10: Auto-detect Laravel and update configuration
show_step "10" "Updating Laravel configuration..."

# Auto-detect Laravel location
POSSIBLE_LARAVEL_DIRS=(
    "/var/www/html"
    "/hm-new"
    "/var/www/hartonomotor.xyz"
    "/home/*/hartonomotor.xyz"
    "/opt/hartonomotor.xyz"
    "/root/hartonomotor.xyz"
)

LARAVEL_DIR=""
for dir in "${POSSIBLE_LARAVEL_DIRS[@]}"; do
    for expanded_dir in $dir; do
        if [[ -f "$expanded_dir/artisan" ]]; then
            LARAVEL_DIR="$expanded_dir"
            show_success "Laravel found at $LARAVEL_DIR"
            break 2
        fi
    done
done

if [[ -n "$LARAVEL_DIR" ]]; then
    cd "$LARAVEL_DIR"

    # Backup .env
    cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

    # Update Laravel .env
    if grep -q "WHATSAPP_API_URL=" .env; then
        sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=http://localhost:$API_PORT|" .env
    else
        echo "" >> .env
        echo "# WhatsApp API Configuration (Docker)" >> .env
        echo "WHATSAPP_API_URL=http://localhost:$API_PORT" >> .env
    fi

    if ! grep -q "WHATSAPP_BASIC_AUTH_USERNAME=" .env; then
        echo "WHATSAPP_BASIC_AUTH_USERNAME=$BASIC_AUTH_USER" >> .env
        echo "WHATSAPP_BASIC_AUTH_PASSWORD=$BASIC_AUTH_PASS" >> .env
    fi

    # Clear Laravel cache
    php artisan config:clear 2>/dev/null || true
    php artisan config:cache 2>/dev/null || true

    show_success "Laravel configuration updated"
else
    show_warning "Laravel not found, manual configuration needed"
fi

echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 DOCKER WHATSAPP API DEPLOYMENT COMPLETED"
echo "============================================================================="
echo -e "${NC}"

if [[ "$AUTH_OK" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! WhatsApp API deployed successfully using Docker${NC}"
else
    echo -e "${YELLOW}⚠️  Container started but API needs attention${NC}"
fi

echo ""
echo -e "${CYAN}📊 Deployment Summary:${NC}"
echo "  • Deployment Type: Docker Compose (Same as Local)"
echo "  • Project Directory: $PROJECT_DIR"
echo "  • Container Name: $CONTAINER_NAME"
echo "  • API Port: $API_PORT"
echo "  • External Access: http://45.32.116.20:$API_PORT"
echo "  • Internal Access: http://localhost:$API_PORT"
echo "  • Basic Auth: $BASIC_AUTH_USER:$BASIC_AUTH_PASS"
echo ""

echo -e "${CYAN}🛠️ Management Commands:${NC}"
echo "  • Start: $PROJECT_DIR/start.sh"
echo "  • Stop: $PROJECT_DIR/stop.sh"
echo "  • Restart: $PROJECT_DIR/restart.sh"
echo "  • Status: $PROJECT_DIR/status.sh"
echo "  • Logs: $PROJECT_DIR/logs.sh"
echo "  • QR Login: $PROJECT_DIR/qr.sh"
echo ""

echo -e "${CYAN}🌐 Access URLs:${NC}"
echo "  • Web Interface: http://45.32.116.20:$API_PORT"
echo "  • API Docs: http://45.32.116.20:$API_PORT/docs"
echo "  • Device Status: http://45.32.116.20:$API_PORT/app/devices"
echo "  • QR Login: http://45.32.116.20:$API_PORT/app/login"
echo ""

echo -e "${CYAN}🔐 Authentication:${NC}"
echo "  • Username: $BASIC_AUTH_USER"
echo "  • Password: $BASIC_AUTH_PASS"
echo ""

echo -e "${YELLOW}📋 Next Steps:${NC}"
echo "1. Open browser: http://45.32.116.20:$API_PORT"
echo "2. Login with credentials above"
echo "3. Click 'Login' to generate QR code"
echo "4. Scan QR with WhatsApp mobile app"
echo "5. Test Laravel integration: https://hartonomotor.xyz/whatsapp/qr-generator"
echo ""

echo -e "${GREEN}🎯 Docker deployment completed! Same as local environment! 🎉${NC}"

# Show final status
echo ""
echo -e "${BLUE}🔍 Final Status Check:${NC}"
cd $PROJECT_DIR
./status.sh
