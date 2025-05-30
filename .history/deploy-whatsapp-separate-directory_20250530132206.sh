#!/bin/bash

# =============================================================================
# 🚀 WhatsApp API Deployment (Same VPS, Separate Directory)
# =============================================================================
# Deploy WhatsApp API di VPS yang sama dengan Laravel tapi directory terpisah
# Approach yang clean, professional, dan mudah maintenance
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
echo "🚀 WHATSAPP API DEPLOYMENT (SEPARATE DIRECTORY)"
echo "============================================================================="
echo "Deploy WhatsApp API di VPS yang sama dengan Laravel"
echo "Directory terpisah untuk clean separation dan easy maintenance"
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

# Configuration
WHATSAPP_DIR="/opt/whatsapp-api"
SERVICE_NAME="whatsapp-api"
API_PORT="3000"
BASIC_AUTH_USER="admin"
BASIC_AUTH_PASS="hartonomotor$(date +%s | tail -c 6)"

# STEP 1: Check current environment
show_step "1" "Checking current environment..."

# Auto-detect Laravel location
POSSIBLE_LARAVEL_DIRS=(
    "/var/www/html"
    "/hm-new"
    "/var/www/hartonomotor.xyz"
    "/home/*/hartonomotor.xyz"
    "/opt/hartonomotor.xyz"
    "/root/hartonomotor.xyz"
    "$(pwd)"
)

LARAVEL_DIR=""
for dir in "${POSSIBLE_LARAVEL_DIRS[@]}"; do
    # Expand wildcard
    for expanded_dir in $dir; do
        if [[ -f "$expanded_dir/artisan" ]]; then
            LARAVEL_DIR="$expanded_dir"
            show_success "Laravel found at $LARAVEL_DIR"
            break 2
        fi
    done
done

if [[ -z "$LARAVEL_DIR" ]]; then
    show_error "Laravel not found in common locations"
    echo ""
    echo "Searched in:"
    for dir in "${POSSIBLE_LARAVEL_DIRS[@]}"; do
        echo "  - $dir"
    done
    echo ""
    read -p "Enter Laravel directory path manually: " LARAVEL_DIR

    if [[ ! -f "$LARAVEL_DIR/artisan" ]]; then
        show_error "Invalid Laravel directory: $LARAVEL_DIR"
        exit 1
    fi
    show_success "Laravel confirmed at $LARAVEL_DIR"
fi

# Check available resources
MEMORY_GB=$(free -g | awk 'NR==2{printf "%.1f", $2}')
DISK_GB=$(df -BG / | awk 'NR==2{print $4}' | sed 's/G//')

show_info "Available memory: ${MEMORY_GB}GB"
show_info "Available disk: ${DISK_GB}GB"

if (( $(echo "$MEMORY_GB < 1.5" | bc -l) )); then
    show_error "Insufficient memory. Minimum 1.5GB required."
    exit 1
fi

echo ""

# STEP 2: Install dependencies
show_step "2" "Installing system dependencies..."

# Update system
sudo apt update

# Install required packages
sudo apt install -y \
    wget \
    curl \
    ffmpeg \
    systemd \
    openssl \
    jq \
    bc

show_success "System dependencies installed"
echo ""

# STEP 3: Create project structure
show_step "3" "Creating WhatsApp API project structure..."

# Create main directory
sudo mkdir -p $WHATSAPP_DIR
sudo chown $USER:$USER $WHATSAPP_DIR

# Create subdirectories
mkdir -p $WHATSAPP_DIR/{bin,config,data,logs,scripts}
mkdir -p $WHATSAPP_DIR/data/{sessions,qrcode}

show_success "Project structure created at $WHATSAPP_DIR"

# Show directory structure
echo ""
show_info "Directory structure:"
tree $WHATSAPP_DIR 2>/dev/null || ls -la $WHATSAPP_DIR
echo ""

# STEP 4: Download WhatsApp API binary
show_step "4" "Downloading WhatsApp API binary..."

cd $WHATSAPP_DIR

# Detect architecture
ARCH=$(uname -m)
case $ARCH in
    x86_64)
        DOWNLOAD_ARCH="amd64"
        ;;
    aarch64)
        DOWNLOAD_ARCH="arm64"
        ;;
    *)
        show_error "Unsupported architecture: $ARCH"
        exit 1
        ;;
esac

show_info "Detected architecture: $ARCH -> $DOWNLOAD_ARCH"

# Get latest release URL
show_info "Fetching latest release information..."
LATEST_URL=$(curl -s https://api.github.com/repos/aldinokemal/go-whatsapp-web-multidevice/releases/latest | jq -r ".assets[] | select(.name | contains(\"linux\") and contains(\"$DOWNLOAD_ARCH\")) | .browser_download_url")

if [[ -n "$LATEST_URL" && "$LATEST_URL" != "null" ]]; then
    show_info "Downloading: $LATEST_URL"
    wget -O whatsapp-linux.tar.gz "$LATEST_URL"

    # Extract binary
    tar -xzf whatsapp-linux.tar.gz
    mv whatsapp bin/whatsapp
    rm whatsapp-linux.tar.gz

    show_success "Binary downloaded and extracted"
else
    show_error "Could not find binary for $DOWNLOAD_ARCH"
    exit 1
fi

# Make binary executable
chmod +x bin/whatsapp

# Test binary
if ./bin/whatsapp --help > /dev/null 2>&1; then
    show_success "Binary is working correctly"
else
    show_error "Binary test failed"
    exit 1
fi

echo ""

# STEP 5: Create configuration
show_step "5" "Creating configuration files..."

# Create .env file
cat > config/.env <<EOF
# WhatsApp API Configuration for Hartono Motor
APP_PORT=$API_PORT
APP_DEBUG=false
APP_OS=HartonoMotor
APP_BASIC_AUTH=$BASIC_AUTH_USER:$BASIC_AUTH_PASS
APP_ACCOUNT_VALIDATION=false
APP_WEBHOOK=https://hartonomotor.xyz/webhook/whatsapp
APP_WEBHOOK_SECRET=$(openssl rand -base64 32)
APP_AUTOREPLY=false
EOF

show_success "Configuration created"

# Display credentials
echo ""
echo -e "${CYAN}🔐 Generated Credentials:${NC}"
echo "API URL: http://localhost:$API_PORT"
echo "Username: $BASIC_AUTH_USER"
echo "Password: $BASIC_AUTH_PASS"
echo "Webhook: https://hartonomotor.xyz/webhook/whatsapp"
echo ""

# STEP 6: Create systemd service
show_step "6" "Creating systemd service..."

sudo tee /etc/systemd/system/$SERVICE_NAME.service > /dev/null <<EOF
[Unit]
Description=WhatsApp API Service for Hartono Motor
After=network.target
Wants=network.target

[Service]
Type=simple
User=$USER
Group=$USER
WorkingDirectory=$WHATSAPP_DIR
Environment=PATH=/usr/local/bin:/usr/bin:/bin
ExecStart=$WHATSAPP_DIR/bin/whatsapp rest --port=$API_PORT --basic-auth=$BASIC_AUTH_USER:$BASIC_AUTH_PASS --os=HartonoMotor
Restart=always
RestartSec=10
StandardOutput=append:$WHATSAPP_DIR/logs/whatsapp.log
StandardError=append:$WHATSAPP_DIR/logs/whatsapp-error.log

# Security settings
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=$WHATSAPP_DIR

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd and enable service
sudo systemctl daemon-reload
sudo systemctl enable $SERVICE_NAME

show_success "Systemd service created and enabled"
echo ""

# STEP 7: Create management scripts
show_step "7" "Creating management scripts..."

# Start script
cat > scripts/start.sh <<EOF
#!/bin/bash
echo "Starting WhatsApp API..."
sudo systemctl start $SERVICE_NAME
sleep 3
systemctl status $SERVICE_NAME --no-pager
EOF

# Stop script
cat > scripts/stop.sh <<EOF
#!/bin/bash
echo "Stopping WhatsApp API..."
sudo systemctl stop $SERVICE_NAME
echo "WhatsApp API stopped"
EOF

# Restart script
cat > scripts/restart.sh <<EOF
#!/bin/bash
echo "Restarting WhatsApp API..."
sudo systemctl restart $SERVICE_NAME
sleep 3
systemctl status $SERVICE_NAME --no-pager
EOF

# Status script
cat > scripts/status.sh <<EOF
#!/bin/bash
echo "=== Service Status ==="
systemctl status $SERVICE_NAME --no-pager
echo ""
echo "=== API Health Check ==="
curl -s http://localhost:$API_PORT/app/devices -u $BASIC_AUTH_USER:$BASIC_AUTH_PASS | jq . || echo "API not responding"
echo ""
echo "=== Recent Logs ==="
tail -n 20 $WHATSAPP_DIR/logs/whatsapp.log
EOF

# Logs script
cat > scripts/logs.sh <<EOF
#!/bin/bash
echo "Following WhatsApp API logs (Ctrl+C to stop)..."
tail -f $WHATSAPP_DIR/logs/whatsapp.log
EOF

# Make scripts executable
chmod +x scripts/*.sh

show_success "Management scripts created"
echo ""

# STEP 8: Configure firewall (internal only)
show_step "8" "Configuring firewall..."

# Allow port 3000 only from localhost (internal communication)
sudo ufw allow from 127.0.0.1 to any port $API_PORT

show_success "Firewall configured for internal communication"
echo ""

# STEP 9: Start the service
show_step "9" "Starting WhatsApp API service..."

# Start service
sudo systemctl start $SERVICE_NAME

# Wait for startup
sleep 10

# Check if service is running
if systemctl is-active --quiet $SERVICE_NAME; then
    show_success "WhatsApp API service started successfully"

    # Test API
    if curl -s -f http://localhost:$API_PORT/app/devices > /dev/null 2>&1; then
        show_success "API is responding"
        API_WORKING=true
    else
        show_info "API starting up, may take a moment..."
        API_WORKING=false
    fi
else
    show_error "Failed to start WhatsApp API service"
    echo "Check logs: journalctl -u $SERVICE_NAME -f"
    API_WORKING=false
fi

echo ""

# STEP 10: Update Laravel configuration
show_step "10" "Updating Laravel configuration..."

cd $LARAVEL_DIR

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# Update or add WhatsApp configuration
if grep -q "WHATSAPP_API_URL=" .env; then
    sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=http://localhost:$API_PORT|" .env
else
    echo "" >> .env
    echo "# WhatsApp API Configuration" >> .env
    echo "WHATSAPP_API_URL=http://localhost:$API_PORT" >> .env
fi

if ! grep -q "WHATSAPP_BASIC_AUTH_USERNAME=" .env; then
    echo "WHATSAPP_BASIC_AUTH_USERNAME=$BASIC_AUTH_USER" >> .env
    echo "WHATSAPP_BASIC_AUTH_PASSWORD=$BASIC_AUTH_PASS" >> .env
fi

# Clear Laravel cache
php artisan config:clear
php artisan config:cache

show_success "Laravel configuration updated"
echo ""

# STEP 11: Test integration
show_step "11" "Testing Laravel → WhatsApp integration..."

# Test from Laravel
TEST_RESULT=$(php artisan tinker --execute="
try {
    \$response = \Illuminate\Support\Facades\Http::withBasicAuth('$BASIC_AUTH_USER', '$BASIC_AUTH_PASS')
        ->timeout(10)
        ->get('http://localhost:$API_PORT/app/devices');
    echo \$response->successful() ? 'SUCCESS' : 'FAILED';
} catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
}
" 2>/dev/null)

if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
    show_success "Laravel → WhatsApp integration working"
    INTEGRATION_OK=true
else
    show_error "Laravel integration test failed: $TEST_RESULT"
    INTEGRATION_OK=false
fi

echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 WHATSAPP API DEPLOYMENT COMPLETED"
echo "============================================================================="
echo -e "${NC}"

if [[ "$INTEGRATION_OK" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! WhatsApp API deployed successfully${NC}"
else
    echo -e "${YELLOW}⚠️  Service deployed but integration needs attention${NC}"
fi

echo ""
echo -e "${CYAN}📊 Deployment Summary:${NC}"
echo "  • Installation Type: Separate Directory (Same VPS)"
echo "  • WhatsApp Directory: $WHATSAPP_DIR"
echo "  • Laravel Directory: $LARAVEL_DIR"
echo "  • Service Name: $SERVICE_NAME"
echo "  • API Port: $API_PORT (internal only)"
echo "  • Basic Auth: $BASIC_AUTH_USER:$BASIC_AUTH_PASS"
echo ""

echo -e "${CYAN}🛠️ Management Commands:${NC}"
echo "  • Start API: $WHATSAPP_DIR/scripts/start.sh"
echo "  • Stop API: $WHATSAPP_DIR/scripts/stop.sh"
echo "  • Restart API: $WHATSAPP_DIR/scripts/restart.sh"
echo "  • Check Status: $WHATSAPP_DIR/scripts/status.sh"
echo "  • View Logs: $WHATSAPP_DIR/scripts/logs.sh"
echo ""

echo -e "${CYAN}🔗 API Endpoints (Internal):${NC}"
echo "  • Device Status: http://localhost:$API_PORT/app/devices"
echo "  • QR Login: http://localhost:$API_PORT/app/login"
echo "  • Send Message: http://localhost:$API_PORT/send/message"
echo ""

echo -e "${YELLOW}📋 Next Steps:${NC}"
echo "1. Test QR generation: $WHATSAPP_DIR/scripts/status.sh"
echo "2. Scan QR code with WhatsApp mobile app"
echo "3. Test Laravel integration: https://hartonomotor.xyz/whatsapp/qr-generator"
echo "4. Implement service completion button integration"
echo ""

echo -e "${GREEN}🎯 Clean separation achieved! Laravel + WhatsApp API in harmony! 🎉${NC}"
