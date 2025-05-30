#!/bin/bash

# =============================================================================
# 🚀 WhatsApp API Binary Deployment (No Docker)
# =============================================================================
# Deploy go-whatsapp-web-multidevice sebagai binary executable
# Lebih ringan dan mudah dibanding Docker approach
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
echo "🚀 WHATSAPP API BINARY DEPLOYMENT (NO DOCKER)"
echo "============================================================================="
echo "Deploy go-whatsapp-web-multidevice sebagai binary executable"
echo "Lebih ringan, cepat, dan mudah maintenance dibanding Docker"
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
PROJECT_DIR="/opt/whatsapp-api-binary"
BINARY_NAME="whatsapp"
SERVICE_NAME="whatsapp-api"
API_PORT="3000"
BASIC_AUTH_USER="admin"
BASIC_AUTH_PASS=$(openssl rand -base64 16)

# STEP 1: Install dependencies
show_step "1" "Installing system dependencies..."

# Update system
sudo apt update

# Install required packages
sudo apt install -y \
    wget \
    curl \
    ffmpeg \
    systemd \
    openssl

show_success "System dependencies installed"
echo ""

# STEP 2: Check Go installation
show_step "2" "Checking Go installation..."

if command -v go &> /dev/null; then
    GO_VERSION=$(go version | awk '{print $3}')
    show_success "Go already installed: $GO_VERSION"
    BUILD_FROM_SOURCE=true
else
    show_info "Go not installed, will download pre-built binary"
    BUILD_FROM_SOURCE=false
fi

echo ""

# STEP 3: Create project structure
show_step "3" "Creating project structure..."

# Create project directory
sudo mkdir -p $PROJECT_DIR
sudo chown $USER:$USER $PROJECT_DIR

# Create subdirectories
mkdir -p $PROJECT_DIR/{bin,config,data,logs}
mkdir -p $PROJECT_DIR/data/{storages,qrcode}

show_success "Project structure created at $PROJECT_DIR"
echo ""

# STEP 4: Get WhatsApp API binary
show_step "4" "Getting WhatsApp API binary..."

cd $PROJECT_DIR

if [[ "$BUILD_FROM_SOURCE" == true ]]; then
    show_info "Building from source..."
    
    # Clone repository if not exists
    if [[ ! -d "go-whatsapp-web-multidevice" ]]; then
        git clone https://github.com/aldinokemal/go-whatsapp-web-multidevice.git
    fi
    
    cd go-whatsapp-web-multidevice/src
    
    # Build binary
    go build -o $PROJECT_DIR/bin/$BINARY_NAME main.go
    
    show_success "Binary built from source"
    
else
    show_info "Downloading pre-built binary..."
    
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
    
    # Get latest release URL
    LATEST_URL=$(curl -s https://api.github.com/repos/aldinokemal/go-whatsapp-web-multidevice/releases/latest | grep "browser_download_url.*linux.*$DOWNLOAD_ARCH" | cut -d '"' -f 4)
    
    if [[ -n "$LATEST_URL" ]]; then
        show_info "Downloading: $LATEST_URL"
        wget -O whatsapp-linux.tar.gz "$LATEST_URL"
        
        # Extract binary
        tar -xzf whatsapp-linux.tar.gz
        mv whatsapp $PROJECT_DIR/bin/$BINARY_NAME
        rm whatsapp-linux.tar.gz
        
        show_success "Pre-built binary downloaded"
    else
        show_error "Could not find pre-built binary for $ARCH"
        exit 1
    fi
fi

# Make binary executable
chmod +x $PROJECT_DIR/bin/$BINARY_NAME

echo ""

# STEP 5: Create configuration
show_step "5" "Creating configuration..."

# Create .env file
cat > $PROJECT_DIR/config/.env <<EOF
# WhatsApp API Configuration
APP_PORT=$API_PORT
APP_DEBUG=false
APP_OS=HartonoMotor
APP_BASIC_AUTH=$BASIC_AUTH_USER:$BASIC_AUTH_PASS
APP_ACCOUNT_VALIDATION=false
APP_WEBHOOK=
APP_WEBHOOK_SECRET=
APP_AUTOREPLY=
EOF

show_success "Configuration created"

# Display credentials
echo ""
echo -e "${CYAN}🔐 Generated Credentials:${NC}"
echo "Username: $BASIC_AUTH_USER"
echo "Password: $BASIC_AUTH_PASS"
echo "Port: $API_PORT"
echo ""

# STEP 6: Create systemd service
show_step "6" "Creating systemd service..."

sudo tee /etc/systemd/system/$SERVICE_NAME.service > /dev/null <<EOF
[Unit]
Description=WhatsApp API Service
After=network.target
Wants=network.target

[Service]
Type=simple
User=$USER
Group=$USER
WorkingDirectory=$PROJECT_DIR
ExecStart=$PROJECT_DIR/bin/$BINARY_NAME rest --port=$API_PORT --basic-auth=$BASIC_AUTH_USER:$BASIC_AUTH_PASS --os=HartonoMotor
Restart=always
RestartSec=5
StandardOutput=append:$PROJECT_DIR/logs/whatsapp.log
StandardError=append:$PROJECT_DIR/logs/whatsapp-error.log

# Security settings
NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=true
ReadWritePaths=$PROJECT_DIR

[Install]
WantedBy=multi-user.target
EOF

# Reload systemd and enable service
sudo systemctl daemon-reload
sudo systemctl enable $SERVICE_NAME

show_success "Systemd service created and enabled"
echo ""

# STEP 7: Configure firewall
show_step "7" "Configuring firewall..."

# Allow API port
sudo ufw allow $API_PORT/tcp

show_success "Firewall configured"
echo ""

# STEP 8: Create management scripts
show_step "8" "Creating management scripts..."

# Start script
cat > $PROJECT_DIR/start.sh <<EOF
#!/bin/bash
sudo systemctl start $SERVICE_NAME
echo "WhatsApp API started"
systemctl status $SERVICE_NAME --no-pager
EOF

# Stop script
cat > $PROJECT_DIR/stop.sh <<EOF
#!/bin/bash
sudo systemctl stop $SERVICE_NAME
echo "WhatsApp API stopped"
EOF

# Restart script
cat > $PROJECT_DIR/restart.sh <<EOF
#!/bin/bash
sudo systemctl restart $SERVICE_NAME
echo "WhatsApp API restarted"
systemctl status $SERVICE_NAME --no-pager
EOF

# Status script
cat > $PROJECT_DIR/status.sh <<EOF
#!/bin/bash
echo "=== Service Status ==="
systemctl status $SERVICE_NAME --no-pager
echo ""
echo "=== API Health Check ==="
curl -s http://localhost:$API_PORT/app/devices -u $BASIC_AUTH_USER:$BASIC_AUTH_PASS | jq . || echo "API not responding"
echo ""
echo "=== Recent Logs ==="
tail -n 20 $PROJECT_DIR/logs/whatsapp.log
EOF

# Logs script
cat > $PROJECT_DIR/logs.sh <<EOF
#!/bin/bash
echo "Following WhatsApp API logs (Ctrl+C to stop)..."
tail -f $PROJECT_DIR/logs/whatsapp.log
EOF

# Make scripts executable
chmod +x $PROJECT_DIR/{start,stop,restart,status,logs}.sh

show_success "Management scripts created"
echo ""

# STEP 9: Start the service
show_step "9" "Starting WhatsApp API service..."

# Start service
sudo systemctl start $SERVICE_NAME

# Wait for startup
sleep 5

# Check if service is running
if systemctl is-active --quiet $SERVICE_NAME; then
    show_success "WhatsApp API service started successfully"
    
    # Test API
    sleep 5
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

# STEP 10: Create Laravel integration helper
show_step "10" "Creating Laravel integration helper..."

cat > $PROJECT_DIR/laravel-integration.md <<EOF
# Laravel Integration Guide

## 1. Update Laravel .env
Add these lines to your Laravel .env file:

\`\`\`
WHATSAPP_API_URL=http://localhost:$API_PORT
WHATSAPP_BASIC_AUTH_USERNAME=$BASIC_AUTH_USER
WHATSAPP_BASIC_AUTH_PASSWORD=$BASIC_AUTH_PASS
\`\`\`

## 2. Test from Laravel
\`\`\`php
// Test in Laravel tinker
\$response = \Illuminate\Support\Facades\Http::withBasicAuth('$BASIC_AUTH_USER', '$BASIC_AUTH_PASS')
    ->get('http://localhost:$API_PORT/app/devices');
    
echo \$response->json();
\`\`\`

## 3. Clear Laravel cache
\`\`\`bash
cd /var/www/html
php artisan config:clear
php artisan config:cache
\`\`\`

## 4. Test QR Generator
Visit: https://hartonomotor.xyz/whatsapp/qr-generator
EOF

show_success "Laravel integration guide created"
echo ""

# FINAL RESULTS
echo -e "${PURPLE}"
echo "============================================================================="
echo "🎉 WHATSAPP API BINARY DEPLOYMENT COMPLETED"
echo "============================================================================="
echo -e "${NC}"

if [[ "$API_WORKING" == true ]]; then
    echo -e "${GREEN}✅ SUCCESS! WhatsApp API is running as binary service${NC}"
else
    echo -e "${YELLOW}⚠️  Service started but API may still be initializing${NC}"
fi

echo ""
echo -e "${CYAN}📊 Deployment Summary:${NC}"
echo "  • Installation Type: Binary (No Docker)"
echo "  • Project Directory: $PROJECT_DIR"
echo "  • Service Name: $SERVICE_NAME"
echo "  • API Port: $API_PORT"
echo "  • Basic Auth: $BASIC_AUTH_USER:$BASIC_AUTH_PASS"
echo ""

echo -e "${CYAN}🛠️ Management Commands:${NC}"
echo "  • Start API: $PROJECT_DIR/start.sh"
echo "  • Stop API: $PROJECT_DIR/stop.sh"
echo "  • Restart API: $PROJECT_DIR/restart.sh"
echo "  • Check Status: $PROJECT_DIR/status.sh"
echo "  • View Logs: $PROJECT_DIR/logs.sh"
echo ""

echo -e "${CYAN}🔗 API Endpoints:${NC}"
echo "  • Device Status: http://localhost:$API_PORT/app/devices"
echo "  • QR Login: http://localhost:$API_PORT/app/login"
echo "  • API Docs: http://localhost:$API_PORT"
echo ""

echo -e "${CYAN}🔐 Authentication:${NC}"
echo "  • Username: $BASIC_AUTH_USER"
echo "  • Password: $BASIC_AUTH_PASS"
echo "  • (Saved in: $PROJECT_DIR/config/.env)"
echo ""

echo -e "${YELLOW}📋 Next Steps:${NC}"
echo "1. Update Laravel .env with credentials above"
echo "2. Clear Laravel cache: php artisan config:clear && php artisan config:cache"
echo "3. Test QR generator: https://hartonomotor.xyz/whatsapp/qr-generator"
echo "4. Check integration guide: $PROJECT_DIR/laravel-integration.md"
echo ""

echo -e "${GREEN}🎯 Binary deployment completed! No Docker needed! 🎉${NC}"
