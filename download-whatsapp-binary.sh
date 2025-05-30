#!/bin/bash

# =============================================================================
# 📥 Download WhatsApp Binary (Easiest Method)
# =============================================================================
# Download pre-built binary dari GitHub releases
# =============================================================================

# Warna
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}"
echo "============================================================================="
echo "📥 DOWNLOAD WHATSAPP BINARY (EASIEST METHOD)"
echo "============================================================================="
echo -e "${NC}"

# STEP 1: Install dependencies
echo -e "${YELLOW}📋 Installing dependencies...${NC}"
sudo apt update
sudo apt install -y wget curl jq ffmpeg

# STEP 2: Create directory
echo -e "${YELLOW}📋 Creating directory...${NC}"
mkdir -p /opt/whatsapp-binary
cd /opt/whatsapp-binary

# STEP 3: Detect architecture
ARCH=$(uname -m)
case $ARCH in
    x86_64)
        DOWNLOAD_ARCH="amd64"
        ;;
    aarch64)
        DOWNLOAD_ARCH="arm64"
        ;;
    *)
        echo "Unsupported architecture: $ARCH"
        exit 1
        ;;
esac

echo "Detected architecture: $ARCH -> $DOWNLOAD_ARCH"

# STEP 4: Download latest binary
echo -e "${YELLOW}📋 Downloading latest binary...${NC}"

# Get latest release URL
LATEST_URL=$(curl -s https://api.github.com/repos/aldinokemal/go-whatsapp-web-multidevice/releases/latest | jq -r ".assets[] | select(.name | contains(\"linux\") and contains(\"$DOWNLOAD_ARCH\")) | .browser_download_url")

if [[ -n "$LATEST_URL" && "$LATEST_URL" != "null" ]]; then
    echo "Downloading: $LATEST_URL"
    wget -O whatsapp-linux.tar.gz "$LATEST_URL"
    
    # Extract
    tar -xzf whatsapp-linux.tar.gz
    chmod +x whatsapp
    
    echo -e "${GREEN}✅ Binary downloaded successfully${NC}"
else
    echo "Could not find binary for $DOWNLOAD_ARCH, trying direct download..."
    
    # Fallback: try common naming patterns
    FALLBACK_URLS=(
        "https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/whatsapp-linux-$DOWNLOAD_ARCH.tar.gz"
        "https://github.com/aldinokemal/go-whatsapp-web-multidevice/releases/latest/download/whatsapp-linux-$DOWNLOAD_ARCH"
    )
    
    for url in "${FALLBACK_URLS[@]}"; do
        if wget -q --spider "$url"; then
            echo "Downloading: $url"
            wget -O whatsapp-binary "$url"
            
            if [[ "$url" == *.tar.gz ]]; then
                tar -xzf whatsapp-binary
                mv whatsapp-* whatsapp 2>/dev/null || true
            else
                mv whatsapp-binary whatsapp
            fi
            
            chmod +x whatsapp
            break
        fi
    done
fi

# STEP 5: Test binary
if [[ -f "whatsapp" ]]; then
    echo -e "${YELLOW}📋 Testing binary...${NC}"
    ./whatsapp --help | head -10
    echo -e "${GREEN}✅ Binary is working${NC}"
else
    echo "❌ Binary not found or download failed"
    exit 1
fi

# STEP 6: Create simple runner
echo -e "${YELLOW}📋 Creating runner script...${NC}"

cat > run-whatsapp.sh <<EOF
#!/bin/bash
echo "Starting WhatsApp API..."
echo "API will be available at: http://localhost:3000"
echo "Basic Auth: admin / admin123"
echo "Press Ctrl+C to stop"
echo ""

./whatsapp rest --port=3000 --basic-auth=admin:admin123 --os=HartonoMotor --debug=true
EOF

chmod +x run-whatsapp.sh

# STEP 7: Create systemd service (optional)
cat > whatsapp-api.service <<EOF
[Unit]
Description=WhatsApp API Service
After=network.target

[Service]
Type=simple
User=$USER
WorkingDirectory=/opt/whatsapp-binary
ExecStart=/opt/whatsapp-binary/whatsapp rest --port=3000 --basic-auth=admin:admin123 --os=HartonoMotor
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
EOF

echo ""
echo -e "${GREEN}✅ Setup completed!${NC}"
echo ""
echo -e "${BLUE}🚀 To start WhatsApp API:${NC}"
echo "cd /opt/whatsapp-binary"
echo "./run-whatsapp.sh"
echo ""
echo -e "${BLUE}🔗 API will be available at:${NC}"
echo "http://localhost:3000"
echo ""
echo -e "${BLUE}🔐 Basic Auth:${NC}"
echo "Username: admin"
echo "Password: admin123"
echo ""
echo -e "${BLUE}📋 To install as service:${NC}"
echo "sudo cp whatsapp-api.service /etc/systemd/system/"
echo "sudo systemctl daemon-reload"
echo "sudo systemctl enable whatsapp-api"
echo "sudo systemctl start whatsapp-api"
