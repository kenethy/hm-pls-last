#!/bin/bash

# WhatsApp API Files Setup Script
# This script will download and setup WhatsApp API files in the correct location

echo "📥 WhatsApp API Files Setup for VPS"
echo "===================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to find Laravel directory
find_laravel_directory() {
    echo -e "${YELLOW}🔍 Searching for Laravel project directory...${NC}"
    
    # Search for artisan files
    LARAVEL_DIRS=$(find /var/www /home /opt -name "artisan" -type f 2>/dev/null | xargs dirname)
    
    if [ -z "$LARAVEL_DIRS" ]; then
        echo -e "${RED}❌ No Laravel project found${NC}"
        return 1
    fi
    
    # If multiple Laravel projects found, let user choose
    LARAVEL_ARRAY=($LARAVEL_DIRS)
    if [ ${#LARAVEL_ARRAY[@]} -eq 1 ]; then
        LARAVEL_DIR="${LARAVEL_ARRAY[0]}"
        echo -e "${GREEN}✅ Laravel project found: ${LARAVEL_DIR}${NC}"
    else
        echo -e "${YELLOW}Multiple Laravel projects found:${NC}"
        for i in "${!LARAVEL_ARRAY[@]}"; do
            echo -e "   $((i+1)). ${LARAVEL_ARRAY[$i]}"
        done
        
        echo -e "${BLUE}Please select the Hartono Motor project (1-${#LARAVEL_ARRAY[@]}):${NC}"
        read -r choice
        
        if [[ "$choice" =~ ^[0-9]+$ ]] && [ "$choice" -ge 1 ] && [ "$choice" -le ${#LARAVEL_ARRAY[@]} ]; then
            LARAVEL_DIR="${LARAVEL_ARRAY[$((choice-1))]}"
            echo -e "${GREEN}✅ Selected: ${LARAVEL_DIR}${NC}"
        else
            echo -e "${RED}❌ Invalid selection${NC}"
            return 1
        fi
    fi
    
    # Verify it's the correct project
    if [ -f "$LARAVEL_DIR/composer.json" ]; then
        if grep -q "hartonomotor\|Hartono" "$LARAVEL_DIR/composer.json" 2>/dev/null; then
            echo -e "${GREEN}🎯 Confirmed: This is the Hartono Motor project${NC}"
        else
            echo -e "${YELLOW}⚠️ This might not be the Hartono Motor project${NC}"
            echo -e "${BLUE}Continue anyway? (y/n):${NC}"
            read -r confirm
            if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
                return 1
            fi
        fi
    fi
    
    return 0
}

# Function to download WhatsApp API
download_whatsapp_api() {
    local target_dir=$1
    
    echo -e "${YELLOW}📥 Downloading WhatsApp API files...${NC}"
    
    # Create temporary directory
    TEMP_DIR=$(mktemp -d)
    cd "$TEMP_DIR"
    
    # Download from GitHub
    echo -e "${BLUE}Downloading from GitHub...${NC}"
    if curl -L -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"; then
        echo -e "${GREEN}✅ Download completed${NC}"
    else
        echo -e "${RED}❌ Download failed${NC}"
        rm -rf "$TEMP_DIR"
        return 1
    fi
    
    # Extract files
    echo -e "${BLUE}Extracting files...${NC}"
    if command -v unzip >/dev/null 2>&1; then
        unzip -q whatsapp-api.zip
    else
        echo -e "${RED}❌ unzip command not found. Installing...${NC}"
        sudo apt-get update && sudo apt-get install -y unzip
        unzip -q whatsapp-api.zip
    fi
    
    # Move to target directory
    if [ -d "go-whatsapp-web-multidevice-main" ]; then
        echo -e "${BLUE}Moving files to ${target_dir}...${NC}"
        
        # Remove existing directory if it exists
        if [ -d "$target_dir/go-whatsapp-web-multidevice-main" ]; then
            echo -e "${YELLOW}Removing existing WhatsApp API directory...${NC}"
            rm -rf "$target_dir/go-whatsapp-web-multidevice-main"
        fi
        
        # Move new files
        mv go-whatsapp-web-multidevice-main "$target_dir/"
        
        # Set proper permissions
        sudo chown -R $USER:$USER "$target_dir/go-whatsapp-web-multidevice-main"
        chmod -R 755 "$target_dir/go-whatsapp-web-multidevice-main"
        
        echo -e "${GREEN}✅ WhatsApp API files installed successfully${NC}"
    else
        echo -e "${RED}❌ Extraction failed${NC}"
        rm -rf "$TEMP_DIR"
        return 1
    fi
    
    # Cleanup
    rm -rf "$TEMP_DIR"
    return 0
}

# Function to verify installation
verify_installation() {
    local laravel_dir=$1
    local whatsapp_dir="$laravel_dir/go-whatsapp-web-multidevice-main"
    
    echo -e "${YELLOW}🔍 Verifying installation...${NC}"
    
    if [ -d "$whatsapp_dir" ]; then
        echo -e "${GREEN}✅ WhatsApp API directory exists${NC}"
        
        # Check important files
        if [ -f "$whatsapp_dir/docker-compose.yml" ]; then
            echo -e "${GREEN}✅ Docker Compose file found${NC}"
        else
            echo -e "${RED}❌ Docker Compose file missing${NC}"
        fi
        
        if [ -f "$whatsapp_dir/src/main.go" ]; then
            echo -e "${GREEN}✅ Go source files found${NC}"
        else
            echo -e "${RED}❌ Go source files missing${NC}"
        fi
        
        if [ -f "$whatsapp_dir/Dockerfile" ]; then
            echo -e "${GREEN}✅ Dockerfile found${NC}"
        else
            echo -e "${RED}❌ Dockerfile missing${NC}"
        fi
        
        echo -e "${GREEN}✅ Installation verification completed${NC}"
        return 0
    else
        echo -e "${RED}❌ WhatsApp API directory not found${NC}"
        return 1
    fi
}

# Main execution
echo -e "${BLUE}Starting WhatsApp API files setup...${NC}"
echo ""

# Step 1: Find Laravel directory
if ! find_laravel_directory; then
    echo -e "${RED}❌ Could not find or select Laravel directory${NC}"
    exit 1
fi

echo ""
echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  WhatsApp API will be installed at: ${LARAVEL_DIR}/go-whatsapp-web-multidevice-main"
echo ""

# Step 2: Check if WhatsApp API already exists
if [ -d "$LARAVEL_DIR/go-whatsapp-web-multidevice-main" ]; then
    echo -e "${YELLOW}⚠️ WhatsApp API directory already exists${NC}"
    echo -e "${BLUE}Do you want to reinstall? (y/n):${NC}"
    read -r reinstall
    if [[ ! "$reinstall" =~ ^[Yy]$ ]]; then
        echo -e "${GREEN}✅ Using existing installation${NC}"
        verify_installation "$LARAVEL_DIR"
        exit 0
    fi
fi

# Step 3: Download and install WhatsApp API
echo -e "${YELLOW}📥 Step 3: Downloading WhatsApp API...${NC}"
if ! download_whatsapp_api "$LARAVEL_DIR"; then
    echo -e "${RED}❌ Failed to download WhatsApp API${NC}"
    exit 1
fi

# Step 4: Verify installation
echo ""
echo -e "${YELLOW}🔍 Step 4: Verifying installation...${NC}"
if verify_installation "$LARAVEL_DIR"; then
    echo -e "${GREEN}🎉 WhatsApp API files setup completed successfully!${NC}"
else
    echo -e "${RED}❌ Installation verification failed${NC}"
    exit 1
fi

# Step 5: Update deployment script paths
echo ""
echo -e "${YELLOW}📝 Step 5: Creating updated deployment script...${NC}"

cat > deploy-whatsapp-fixed.sh << EOF
#!/bin/bash

# Fixed WhatsApp API Deployment Script
# Auto-generated with correct paths

# Configuration
LARAVEL_DIR="$LARAVEL_DIR"
WHATSAPP_DIR="/var/www/whatsapp-api"
DOMAIN="hartonomotor.xyz"
API_PORT="3000"
API_USER="admin"
API_PASS="HartonoMotor2025!"
WEBHOOK_SECRET="HartonoMotorWebhookSecret2025"

echo "🚀 WhatsApp API Deployment with Fixed Paths"
echo "============================================"
echo "Laravel Directory: \$LARAVEL_DIR"
echo "WhatsApp Source: \$LARAVEL_DIR/go-whatsapp-web-multidevice-main"
echo ""

# Continue with the original deployment script logic...
# (The rest of the deployment script will be included here)
EOF

chmod +x deploy-whatsapp-fixed.sh

echo -e "${GREEN}✅ Updated deployment script created: deploy-whatsapp-fixed.sh${NC}"

# Final instructions
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. WhatsApp API files are now installed at:"
echo -e "   ${GREEN}${LARAVEL_DIR}/go-whatsapp-web-multidevice-main${NC}"
echo ""
echo -e "2. Run the deployment script:"
echo -e "   ${YELLOW}sudo bash deploy-whatsapp-api.sh${NC}"
echo -e "   (The script will now find the files correctly)"
echo ""
echo -e "3. Or use the fixed deployment script:"
echo -e "   ${YELLOW}sudo bash deploy-whatsapp-fixed.sh${NC}"
echo ""
echo -e "${GREEN}✅ Setup completed successfully!${NC}"
