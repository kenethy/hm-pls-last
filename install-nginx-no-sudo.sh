#!/bin/bash

# Nginx Installation Script for VPS without sudo
# This script works with root access or installs sudo first

echo "🔧 Installing Nginx for hartonomotor.xyz VPS (No Sudo Mode)"
echo "==========================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"
LARAVEL_DIR="/var/www/hartonomotor.xyz"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  Current User: $(whoami)"
echo -e "  User ID: $(id -u)"
echo ""

# Function to check if running as root
check_root() {
    if [ "$(id -u)" -eq 0 ]; then
        echo -e "${GREEN}✅ Running as root${NC}"
        USE_SUDO=""
        return 0
    else
        echo -e "${YELLOW}⚠️ Not running as root${NC}"
        
        # Check if sudo exists
        if command -v sudo >/dev/null 2>&1; then
            echo -e "${GREEN}✅ sudo command available${NC}"
            USE_SUDO="sudo"
            return 0
        else
            echo -e "${RED}❌ sudo command not found${NC}"
            return 1
        fi
    fi
}

# Function to install sudo if needed
install_sudo() {
    echo -e "${YELLOW}📦 Installing sudo...${NC}"
    
    if [ "$(id -u)" -ne 0 ]; then
        echo -e "${RED}❌ Cannot install sudo without root access${NC}"
        echo -e "${YELLOW}Please run this script as root or contact your VPS provider${NC}"
        return 1
    fi
    
    # Detect OS and install sudo
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
    else
        OS=$(uname -s)
    fi
    
    echo -e "${BLUE}Detected OS: ${OS}${NC}"
    
    if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
        apt-get update
        apt-get install -y sudo
    elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]] || [[ "$OS" == *"Rocky"* ]]; then
        yum update -y
        yum install -y sudo
    elif [[ "$OS" == *"Amazon Linux"* ]]; then
        yum update -y
        yum install -y sudo
    elif [[ "$OS" == *"Alpine"* ]]; then
        apk update
        apk add sudo
    else
        echo -e "${RED}❌ Unsupported OS for automatic sudo installation${NC}"
        return 1
    fi
    
    echo -e "${GREEN}✅ sudo installed successfully${NC}"
    USE_SUDO="sudo"
    return 0
}

# Function to detect OS
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    elif type lsb_release >/dev/null 2>&1; then
        OS=$(lsb_release -si)
        VER=$(lsb_release -sr)
    else
        OS=$(uname -s)
        VER=$(uname -r)
    fi
    
    echo -e "${BLUE}Detected OS: ${OS} ${VER}${NC}"
}

# Function to install Nginx based on OS
install_nginx() {
    echo -e "${YELLOW}📦 Installing Nginx...${NC}"
    
    if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
        # Ubuntu/Debian
        $USE_SUDO apt-get update
        $USE_SUDO apt-get install -y nginx
        
        # Start and enable Nginx
        $USE_SUDO systemctl start nginx
        $USE_SUDO systemctl enable nginx
        
    elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]] || [[ "$OS" == *"Rocky"* ]]; then
        # CentOS/RHEL/Rocky
        $USE_SUDO yum update -y
        $USE_SUDO yum install -y nginx
        
        # Start and enable Nginx
        $USE_SUDO systemctl start nginx
        $USE_SUDO systemctl enable nginx
        
    elif [[ "$OS" == *"Amazon Linux"* ]]; then
        # Amazon Linux
        $USE_SUDO yum update -y
        $USE_SUDO amazon-linux-extras install -y nginx1
        
        # Start and enable Nginx
        $USE_SUDO systemctl start nginx
        $USE_SUDO systemctl enable nginx
        
    elif [[ "$OS" == *"Alpine"* ]]; then
        # Alpine Linux
        $USE_SUDO apk update
        $USE_SUDO apk add nginx
        
        # Start and enable Nginx
        $USE_SUDO rc-service nginx start
        $USE_SUDO rc-update add nginx default
        
    else
        echo -e "${RED}❌ Unsupported OS: ${OS}${NC}"
        echo -e "${YELLOW}Please install Nginx manually for your OS${NC}"
        return 1
    fi
    
    return 0
}

# Function to install PHP if needed
install_php() {
    echo -e "${YELLOW}🐘 Checking PHP installation...${NC}"
    
    if ! command -v php >/dev/null 2>&1; then
        echo -e "${YELLOW}PHP not found, installing...${NC}"
        
        if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
            $USE_SUDO apt-get install -y php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd
            $USE_SUDO systemctl start php8.2-fpm
            $USE_SUDO systemctl enable php8.2-fpm
        elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]]; then
            $USE_SUDO yum install -y php php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-gd
            $USE_SUDO systemctl start php-fpm
            $USE_SUDO systemctl enable php-fpm
        elif [[ "$OS" == *"Alpine"* ]]; then
            $USE_SUDO apk add php8 php8-fpm php8-mysql php8-xml php8-mbstring php8-curl php8-zip php8-gd
            $USE_SUDO rc-service php-fpm8 start
            $USE_SUDO rc-update add php-fpm8 default
        fi
        
        echo -e "${GREEN}✅ PHP installed${NC}"
    else
        echo -e "${GREEN}✅ PHP already installed${NC}"
    fi
}

# Function to configure firewall
configure_firewall() {
    echo -e "${YELLOW}🔥 Configuring firewall...${NC}"
    
    # UFW (Ubuntu/Debian)
    if command -v ufw >/dev/null 2>&1; then
        $USE_SUDO ufw allow 'Nginx Full'
        $USE_SUDO ufw allow 22/tcp
        $USE_SUDO ufw allow 3000/tcp
        echo -e "${GREEN}✅ UFW firewall configured${NC}"
        
    # Firewalld (CentOS/RHEL)
    elif command -v firewall-cmd >/dev/null 2>&1; then
        $USE_SUDO firewall-cmd --permanent --add-service=http
        $USE_SUDO firewall-cmd --permanent --add-service=https
        $USE_SUDO firewall-cmd --permanent --add-port=3000/tcp
        $USE_SUDO firewall-cmd --reload
        echo -e "${GREEN}✅ Firewalld configured${NC}"
        
    # iptables fallback
    else
        echo -e "${YELLOW}⚠️ No firewall manager found, configuring iptables...${NC}"
        $USE_SUDO iptables -A INPUT -p tcp --dport 80 -j ACCEPT
        $USE_SUDO iptables -A INPUT -p tcp --dport 443 -j ACCEPT
        $USE_SUDO iptables -A INPUT -p tcp --dport 3000 -j ACCEPT
        $USE_SUDO iptables -A INPUT -p tcp --dport 22 -j ACCEPT
        
        # Save iptables rules
        if command -v iptables-save >/dev/null 2>&1; then
            $USE_SUDO iptables-save > /etc/iptables/rules.v4 2>/dev/null || true
        fi
        
        echo -e "${GREEN}✅ iptables configured${NC}"
    fi
}

# Function to find Laravel directory
find_laravel_directory() {
    echo -e "${YELLOW}🔍 Searching for Laravel directory...${NC}"
    
    if [ ! -d "$LARAVEL_DIR" ]; then
        echo -e "${YELLOW}Laravel directory not found at $LARAVEL_DIR, searching...${NC}"
        FOUND_LARAVEL=$(find /var/www /home /opt -name "artisan" -type f 2>/dev/null | head -1 | xargs dirname)
        if [ -n "$FOUND_LARAVEL" ]; then
            LARAVEL_DIR="$FOUND_LARAVEL"
            echo -e "${GREEN}✅ Laravel found at: $LARAVEL_DIR${NC}"
        else
            echo -e "${RED}❌ Laravel directory not found${NC}"
            LARAVEL_DIR="/var/www/html"
            echo -e "${YELLOW}Using default: $LARAVEL_DIR${NC}"
        fi
    else
        echo -e "${GREEN}✅ Laravel directory confirmed: $LARAVEL_DIR${NC}"
    fi
}

# Function to create Nginx configuration
create_nginx_config() {
    echo -e "${YELLOW}⚙️ Creating Nginx configuration...${NC}"
    
    # Create sites-available directory if it doesn't exist
    $USE_SUDO mkdir -p /etc/nginx/sites-available
    $USE_SUDO mkdir -p /etc/nginx/sites-enabled
    
    # Create Nginx site configuration
    $USE_SUDO tee /etc/nginx/sites-available/$DOMAIN > /dev/null << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $DOMAIN www.$DOMAIN;
    
    # Laravel Application
    root $LARAVEL_DIR/public;
    index index.php index.html index.htm;
    
    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # WhatsApp API Reverse Proxy
    location /whatsapp-api/ {
        proxy_pass http://localhost:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        
        # CORS headers for WhatsApp API
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        
        # Handle preflight requests
        if (\$request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }
    }
    
    # Laravel Application Routes
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    # PHP-FPM Configuration
    location ~ \.php\$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security: Deny access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    # Logs
    access_log /var/log/nginx/$DOMAIN.access.log;
    error_log /var/log/nginx/$DOMAIN.error.log;
}
EOF
    
    echo -e "${GREEN}✅ Nginx configuration created${NC}"
}

# Function to enable site and test
enable_and_test() {
    echo -e "${YELLOW}🔗 Enabling site and testing...${NC}"
    
    # Enable site (if sites-enabled directory exists)
    if [ -d "/etc/nginx/sites-enabled" ]; then
        $USE_SUDO ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
        echo -e "${GREEN}✅ Site enabled${NC}"
    fi
    
    # Remove default site if it exists
    if [ -f "/etc/nginx/sites-enabled/default" ]; then
        $USE_SUDO rm /etc/nginx/sites-enabled/default
        echo -e "${GREEN}✅ Default site removed${NC}"
    fi
    
    # Test Nginx configuration
    if $USE_SUDO nginx -t; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
        
        # Reload Nginx
        if $USE_SUDO systemctl reload nginx 2>/dev/null || $USE_SUDO service nginx reload 2>/dev/null || $USE_SUDO nginx -s reload; then
            echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
        else
            echo -e "${RED}❌ Failed to reload Nginx${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ Nginx configuration has errors${NC}"
        return 1
    fi
    
    return 0
}

# Main execution
echo -e "${BLUE}Starting Nginx installation...${NC}"
echo ""

# Step 1: Check root/sudo access
echo -e "${YELLOW}🔍 Step 1: Checking privileges...${NC}"
if ! check_root; then
    echo -e "${YELLOW}Attempting to install sudo...${NC}"
    if ! install_sudo; then
        echo -e "${RED}❌ Cannot proceed without root access or sudo${NC}"
        echo -e "${YELLOW}Please run this script as root: ${NC}"
        echo -e "${BLUE}  su -${NC}"
        echo -e "${BLUE}  bash install-nginx-no-sudo.sh${NC}"
        exit 1
    fi
fi
echo ""

# Step 2: Detect OS
detect_os
echo ""

# Step 3: Find Laravel directory
find_laravel_directory
echo ""

# Step 4: Install Nginx
if ! install_nginx; then
    echo -e "${RED}❌ Failed to install Nginx${NC}"
    exit 1
fi
echo ""

# Step 5: Install PHP if needed
install_php
echo ""

# Step 6: Configure firewall
configure_firewall
echo ""

# Step 7: Create Nginx configuration
create_nginx_config
echo ""

# Step 8: Enable site and test
if ! enable_and_test; then
    echo -e "${RED}❌ Failed to configure Nginx${NC}"
    exit 1
fi

# Step 9: Final verification
echo ""
echo -e "${YELLOW}🧪 Final verification...${NC}"

# Check Nginx status
if $USE_SUDO systemctl is-active --quiet nginx 2>/dev/null || pgrep nginx >/dev/null; then
    echo -e "${GREEN}✅ Nginx is running${NC}"
else
    echo -e "${RED}❌ Nginx is not running${NC}"
fi

# Test HTTP response
if curl -s -o /dev/null -w "%{http_code}" http://localhost 2>/dev/null | grep -q "200\|301\|302"; then
    echo -e "${GREEN}✅ HTTP response is working${NC}"
else
    echo -e "${YELLOW}⚠️ HTTP response test failed (normal if Laravel not fully configured)${NC}"
fi

# Display completion message
echo ""
echo -e "${GREEN}🎉 Nginx installation completed!${NC}"
echo "================================="
echo ""
echo -e "${BLUE}📋 What was installed:${NC}"
echo -e "  ✅ Nginx web server"
echo -e "  ✅ PHP-FPM (if needed)"
echo -e "  ✅ Firewall configuration"
echo -e "  ✅ Site configuration for $DOMAIN"
echo -e "  ✅ WhatsApp API reverse proxy setup"
echo -e "  ✅ sudo command (if was missing)"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Continue with WhatsApp deployment:"
echo -e "   ${YELLOW}bash fix-whatsapp-deployment.sh${NC}"
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo -e "  Check Nginx status: ${YELLOW}${USE_SUDO} systemctl status nginx${NC}"
echo -e "  View Nginx logs: ${YELLOW}${USE_SUDO} tail -f /var/log/nginx/$DOMAIN.error.log${NC}"
echo -e "  Test config: ${YELLOW}${USE_SUDO} nginx -t${NC}"
echo -e "  Reload Nginx: ${YELLOW}${USE_SUDO} systemctl reload nginx${NC}"
echo ""
echo -e "${GREEN}✅ Nginx installation script completed successfully!${NC}"
