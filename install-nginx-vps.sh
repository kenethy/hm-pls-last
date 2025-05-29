#!/bin/bash

# Nginx Installation Script for VPS
# This script will install and configure Nginx for hartonomotor.xyz

echo "🔧 Installing Nginx for hartonomotor.xyz VPS"
echo "============================================"

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
echo ""

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
        sudo apt-get update
        sudo apt-get install -y nginx
        
        # Start and enable Nginx
        sudo systemctl start nginx
        sudo systemctl enable nginx
        
    elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]] || [[ "$OS" == *"Rocky"* ]]; then
        # CentOS/RHEL/Rocky
        sudo yum update -y
        sudo yum install -y nginx
        
        # Start and enable Nginx
        sudo systemctl start nginx
        sudo systemctl enable nginx
        
    elif [[ "$OS" == *"Amazon Linux"* ]]; then
        # Amazon Linux
        sudo yum update -y
        sudo amazon-linux-extras install -y nginx1
        
        # Start and enable Nginx
        sudo systemctl start nginx
        sudo systemctl enable nginx
        
    else
        echo -e "${RED}❌ Unsupported OS: ${OS}${NC}"
        echo -e "${YELLOW}Please install Nginx manually for your OS${NC}"
        return 1
    fi
    
    return 0
}

# Function to configure firewall
configure_firewall() {
    echo -e "${YELLOW}🔥 Configuring firewall...${NC}"
    
    # UFW (Ubuntu/Debian)
    if command -v ufw >/dev/null 2>&1; then
        sudo ufw allow 'Nginx Full'
        sudo ufw allow 22/tcp
        sudo ufw allow 3000/tcp
        echo -e "${GREEN}✅ UFW firewall configured${NC}"
        
    # Firewalld (CentOS/RHEL)
    elif command -v firewall-cmd >/dev/null 2>&1; then
        sudo firewall-cmd --permanent --add-service=http
        sudo firewall-cmd --permanent --add-service=https
        sudo firewall-cmd --permanent --add-port=3000/tcp
        sudo firewall-cmd --reload
        echo -e "${GREEN}✅ Firewalld configured${NC}"
        
    # iptables fallback
    else
        echo -e "${YELLOW}⚠️ No firewall manager found, please configure manually:${NC}"
        echo -e "  - Allow port 80 (HTTP)"
        echo -e "  - Allow port 443 (HTTPS)"
        echo -e "  - Allow port 3000 (WhatsApp API)"
    fi
}

# Function to create basic Nginx config
create_nginx_config() {
    echo -e "${YELLOW}⚙️ Creating Nginx configuration...${NC}"
    
    # Auto-detect Laravel directory if not found
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
    fi
    
    # Create Nginx site configuration
    sudo tee /etc/nginx/sites-available/$DOMAIN > /dev/null << EOF
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
        sudo ln -sf /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
        echo -e "${GREEN}✅ Site enabled${NC}"
    fi
    
    # Remove default site if it exists
    if [ -f "/etc/nginx/sites-enabled/default" ]; then
        sudo rm /etc/nginx/sites-enabled/default
        echo -e "${GREEN}✅ Default site removed${NC}"
    fi
    
    # Test Nginx configuration
    if sudo nginx -t; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
        
        # Reload Nginx
        if sudo systemctl reload nginx; then
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

# Function to install PHP if needed
install_php() {
    echo -e "${YELLOW}🐘 Checking PHP installation...${NC}"
    
    if ! command -v php >/dev/null 2>&1; then
        echo -e "${YELLOW}PHP not found, installing...${NC}"
        
        if [[ "$OS" == *"Ubuntu"* ]] || [[ "$OS" == *"Debian"* ]]; then
            sudo apt-get install -y php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd
            sudo systemctl start php8.2-fpm
            sudo systemctl enable php8.2-fpm
        elif [[ "$OS" == *"CentOS"* ]] || [[ "$OS" == *"Red Hat"* ]]; then
            sudo yum install -y php php-fpm php-mysql php-xml php-mbstring php-curl php-zip php-gd
            sudo systemctl start php-fpm
            sudo systemctl enable php-fpm
        fi
        
        echo -e "${GREEN}✅ PHP installed${NC}"
    else
        echo -e "${GREEN}✅ PHP already installed${NC}"
    fi
}

# Main execution
echo -e "${BLUE}Starting Nginx installation...${NC}"
echo ""

# Step 1: Detect OS
detect_os
echo ""

# Step 2: Install Nginx
if ! install_nginx; then
    echo -e "${RED}❌ Failed to install Nginx${NC}"
    exit 1
fi
echo ""

# Step 3: Install PHP if needed
install_php
echo ""

# Step 4: Configure firewall
configure_firewall
echo ""

# Step 5: Create Nginx configuration
create_nginx_config
echo ""

# Step 6: Enable site and test
if ! enable_and_test; then
    echo -e "${RED}❌ Failed to configure Nginx${NC}"
    exit 1
fi

# Step 7: Final verification
echo ""
echo -e "${YELLOW}🧪 Final verification...${NC}"

# Check Nginx status
if sudo systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✅ Nginx is running${NC}"
else
    echo -e "${RED}❌ Nginx is not running${NC}"
fi

# Test HTTP response
if curl -s -o /dev/null -w "%{http_code}" http://localhost | grep -q "200\|301\|302"; then
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
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Continue with WhatsApp deployment:"
echo -e "   ${YELLOW}sudo bash fix-whatsapp-deployment.sh${NC}"
echo ""
echo -e "2. Or run the complete deployment:"
echo -e "   ${YELLOW}sudo bash deploy-whatsapp-complete.sh${NC}"
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo -e "  Check Nginx status: ${YELLOW}sudo systemctl status nginx${NC}"
echo -e "  View Nginx logs: ${YELLOW}sudo tail -f /var/log/nginx/$DOMAIN.error.log${NC}"
echo -e "  Test config: ${YELLOW}sudo nginx -t${NC}"
echo -e "  Reload Nginx: ${YELLOW}sudo systemctl reload nginx${NC}"
echo ""
echo -e "${GREEN}✅ Nginx installation script completed successfully!${NC}"
