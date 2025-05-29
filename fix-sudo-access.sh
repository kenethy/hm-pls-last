#!/bin/bash

# Quick Fix for Sudo Access Issues
# This script will diagnose and fix sudo/root access problems

echo "🔧 Sudo Access Fix for VPS"
echo "=========================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}📋 Diagnosing privilege access...${NC}"
echo ""

# Function to check current user status
check_user_status() {
    echo -e "${YELLOW}👤 Current User Information:${NC}"
    echo -e "  User: $(whoami)"
    echo -e "  User ID: $(id -u)"
    echo -e "  Group ID: $(id -g)"
    echo -e "  Groups: $(groups)"
    echo ""
    
    if [ "$(id -u)" -eq 0 ]; then
        echo -e "${GREEN}✅ Running as root - Full privileges available${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠️ Running as regular user${NC}"
        return 1
    fi
}

# Function to check sudo availability
check_sudo() {
    echo -e "${YELLOW}🔍 Checking sudo availability...${NC}"
    
    if command -v sudo >/dev/null 2>&1; then
        echo -e "${GREEN}✅ sudo command is available${NC}"
        
        # Test sudo access
        if sudo -n true 2>/dev/null; then
            echo -e "${GREEN}✅ sudo access confirmed (no password required)${NC}"
            return 0
        elif sudo -v 2>/dev/null; then
            echo -e "${GREEN}✅ sudo access available (password required)${NC}"
            return 0
        else
            echo -e "${RED}❌ sudo access denied${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ sudo command not found${NC}"
        return 1
    fi
}

# Function to install sudo (requires root)
install_sudo() {
    echo -e "${YELLOW}📦 Installing sudo...${NC}"
    
    if [ "$(id -u)" -ne 0 ]; then
        echo -e "${RED}❌ Cannot install sudo without root access${NC}"
        return 1
    fi
    
    # Detect OS
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
    else
        OS=$(uname -s)
    fi
    
    echo -e "${BLUE}Detected OS: ${OS}${NC}"
    
    # Install sudo based on OS
    if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
        apt-get update && apt-get install -y sudo
    elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]] || [[ "$OS" == *"Rocky"* ]]; then
        yum update -y && yum install -y sudo
    elif [[ "$OS" == *"Amazon Linux"* ]]; then
        yum update -y && yum install -y sudo
    elif [[ "$OS" == *"Alpine"* ]]; then
        apk update && apk add sudo
    else
        echo -e "${RED}❌ Unsupported OS for automatic sudo installation${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ sudo installed successfully${NC}"
    return 0
}

# Function to provide solutions
provide_solutions() {
    echo -e "${BLUE}🔧 Available Solutions:${NC}"
    echo ""
    
    if [ "$(id -u)" -eq 0 ]; then
        echo -e "${GREEN}SOLUTION 1: Continue as root${NC}"
        echo -e "  You're already running as root. Use the no-sudo script:"
        echo -e "  ${YELLOW}bash install-nginx-no-sudo.sh${NC}"
        echo ""
        
    elif command -v sudo >/dev/null 2>&1; then
        echo -e "${GREEN}SOLUTION 1: Use existing sudo${NC}"
        echo -e "  sudo is available. Run the original script with sudo:"
        echo -e "  ${YELLOW}sudo bash install-nginx-vps.sh${NC}"
        echo ""
        
    else
        echo -e "${YELLOW}SOLUTION 1: Switch to root user${NC}"
        echo -e "  Switch to root and run the no-sudo script:"
        echo -e "  ${YELLOW}su -${NC}"
        echo -e "  ${YELLOW}bash install-nginx-no-sudo.sh${NC}"
        echo ""
        
        echo -e "${YELLOW}SOLUTION 2: Install sudo as root${NC}"
        echo -e "  1. Switch to root: ${YELLOW}su -${NC}"
        echo -e "  2. Run this script as root to install sudo"
        echo -e "  3. Then run: ${YELLOW}sudo bash install-nginx-vps.sh${NC}"
        echo ""
    fi
    
    echo -e "${BLUE}SOLUTION 3: Manual commands (if scripts fail)${NC}"
    echo -e "  Run these commands manually as root:"
    echo -e "  ${YELLOW}# Install Nginx${NC}"
    echo -e "  ${YELLOW}apt-get update && apt-get install -y nginx${NC}  # Ubuntu/Debian"
    echo -e "  ${YELLOW}yum update -y && yum install -y nginx${NC}        # CentOS/RHEL"
    echo -e "  ${YELLOW}# Start Nginx${NC}"
    echo -e "  ${YELLOW}systemctl start nginx${NC}"
    echo -e "  ${YELLOW}systemctl enable nginx${NC}"
    echo ""
}

# Function to create a simple Nginx config manually
create_manual_config() {
    echo -e "${YELLOW}📝 Creating manual Nginx configuration...${NC}"
    
    if [ "$(id -u)" -ne 0 ]; then
        echo -e "${RED}❌ Need root access to create Nginx configuration${NC}"
        return 1
    fi
    
    # Find Laravel directory
    LARAVEL_DIR="/var/www/hartonomotor.xyz"
    if [ ! -d "$LARAVEL_DIR" ]; then
        FOUND_LARAVEL=$(find /var/www /home /opt -name "artisan" -type f 2>/dev/null | head -1 | xargs dirname)
        if [ -n "$FOUND_LARAVEL" ]; then
            LARAVEL_DIR="$FOUND_LARAVEL"
        else
            LARAVEL_DIR="/var/www/html"
        fi
    fi
    
    echo -e "${BLUE}Using Laravel directory: ${LARAVEL_DIR}${NC}"
    
    # Create directories
    mkdir -p /etc/nginx/sites-available
    mkdir -p /etc/nginx/sites-enabled
    
    # Create basic Nginx config
    cat > /etc/nginx/sites-available/hartonomotor.xyz << EOF
server {
    listen 80;
    server_name hartonomotor.xyz www.hartonomotor.xyz;
    root $LARAVEL_DIR/public;
    index index.php index.html;
    
    location /whatsapp-api/ {
        proxy_pass http://localhost:3000/;
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF
    
    # Enable site
    ln -sf /etc/nginx/sites-available/hartonomotor.xyz /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Test and reload
    if nginx -t; then
        systemctl reload nginx
        echo -e "${GREEN}✅ Nginx configuration created and loaded${NC}"
        return 0
    else
        echo -e "${RED}❌ Nginx configuration has errors${NC}"
        return 1
    fi
}

# Main execution
echo -e "${BLUE}Starting sudo access diagnosis...${NC}"
echo ""

# Step 1: Check current user status
check_user_status
IS_ROOT=$?

# Step 2: Check sudo availability
check_sudo
HAS_SUDO=$?

echo ""

# Step 3: Provide diagnosis and solutions
echo -e "${PURPLE}📊 DIAGNOSIS RESULTS${NC}"
echo "==================="

if [ $IS_ROOT -eq 0 ]; then
    echo -e "${GREEN}✅ Status: Running as root - No sudo needed${NC}"
    echo -e "${GREEN}✅ Recommendation: Use install-nginx-no-sudo.sh${NC}"
    
    echo ""
    echo -e "${BLUE}Do you want to install sudo anyway? (y/n):${NC}"
    read -r install_sudo_choice
    
    if [[ "$install_sudo_choice" =~ ^[Yy]$ ]]; then
        install_sudo
    fi
    
    echo ""
    echo -e "${BLUE}Do you want to create Nginx configuration now? (y/n):${NC}"
    read -r create_config_choice
    
    if [[ "$create_config_choice" =~ ^[Yy]$ ]]; then
        create_manual_config
    fi
    
elif [ $HAS_SUDO -eq 0 ]; then
    echo -e "${GREEN}✅ Status: sudo is available${NC}"
    echo -e "${GREEN}✅ Recommendation: Use original scripts with sudo${NC}"
    
else
    echo -e "${RED}❌ Status: No root access and no sudo${NC}"
    echo -e "${YELLOW}⚠️ Recommendation: Switch to root or install sudo${NC}"
    
    echo ""
    echo -e "${BLUE}Attempting to switch to root...${NC}"
    echo -e "${YELLOW}Please enter root password when prompted:${NC}"
    
    if su - -c "bash $(realpath $0) --as-root"; then
        echo -e "${GREEN}✅ Root access successful${NC}"
        exit 0
    else
        echo -e "${RED}❌ Root access failed${NC}"
    fi
fi

echo ""
provide_solutions

echo ""
echo -e "${GREEN}✅ Sudo access diagnosis completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Choose one of the solutions above"
echo -e "2. Run the appropriate Nginx installation script"
echo -e "3. Continue with WhatsApp deployment"
echo ""
echo -e "${YELLOW}💡 Quick Commands:${NC}"
echo -e "  As root: ${YELLOW}bash install-nginx-no-sudo.sh${NC}"
echo -e "  With sudo: ${YELLOW}sudo bash install-nginx-vps.sh${NC}"
