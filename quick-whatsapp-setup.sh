#!/bin/bash

# =============================================================================
# 🚀 Quick WhatsApp Setup (Direct Go Run)
# =============================================================================
# Setup cepat untuk testing tanpa Docker
# =============================================================================

# Warna
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================================="
echo "🚀 QUICK WHATSAPP SETUP (NO DOCKER)"
echo "============================================================================="
echo -e "${NC}"

# STEP 1: Install dependencies
echo -e "${YELLOW}📋 Installing dependencies...${NC}"
sudo apt update
sudo apt install -y golang-go ffmpeg git

# STEP 2: Setup project
echo -e "${YELLOW}📋 Setting up project...${NC}"
cd /opt
sudo mkdir -p whatsapp-quick
sudo chown $USER:$USER whatsapp-quick
cd whatsapp-quick

# Clone if not exists
if [[ ! -d "go-whatsapp-web-multidevice-main" ]]; then
    echo "Copying existing source..."
    cp -r /path/to/go-whatsapp-web-multidevice-main .
fi

cd go-whatsapp-web-multidevice-main/src

# STEP 3: Create .env
echo -e "${YELLOW}📋 Creating configuration...${NC}"
cat > .env <<EOF
APP_PORT=3000
APP_DEBUG=true
APP_OS=HartonoMotor
APP_BASIC_AUTH=admin:admin123
APP_ACCOUNT_VALIDATION=false
EOF

# STEP 4: Run
echo -e "${YELLOW}📋 Starting WhatsApp API...${NC}"
echo "Running: go run main.go rest"
echo "API will be available at: http://localhost:3000"
echo "Basic Auth: admin / admin123"
echo ""
echo -e "${GREEN}Press Ctrl+C to stop${NC}"
echo ""

go run main.go rest
