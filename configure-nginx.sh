#!/bin/bash

# Nginx Configuration Script for WhatsApp API Reverse Proxy
# Run this script on your VPS as root or with sudo privileges

set -e  # Exit on any error

echo "🔧 Configuring Nginx reverse proxy for WhatsApp API..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"
NGINX_SITE="/etc/nginx/sites-available/${DOMAIN}"
API_PORT="3000"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Nginx Site: ${NGINX_SITE}"
echo -e "  API Port: ${API_PORT}"
echo ""

# Check if nginx is installed
if ! command -v nginx >/dev/null 2>&1; then
    echo -e "${RED}❌ Nginx not found. Please install Nginx first.${NC}"
    exit 1
fi

# Backup existing nginx config
echo -e "${YELLOW}💾 Step 1: Backing up existing Nginx configuration...${NC}"
if [ -f "${NGINX_SITE}" ]; then
    sudo cp ${NGINX_SITE} ${NGINX_SITE}.backup.$(date +%Y%m%d_%H%M%S)
    echo -e "${GREEN}✅ Backup created${NC}"
else
    echo -e "${YELLOW}⚠️ No existing config found, creating new one${NC}"
fi

# Create new nginx configuration with WhatsApp API proxy
echo -e "${YELLOW}⚙️ Step 2: Creating Nginx configuration...${NC}"

sudo tee ${NGINX_SITE} > /dev/null << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name hartonomotor.xyz www.hartonomotor.xyz;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name hartonomotor.xyz www.hartonomotor.xyz;

    # SSL Configuration (adjust paths as needed)
    ssl_certificate /etc/ssl/certs/hartonomotor.xyz.crt;
    ssl_certificate_key /etc/ssl/private/hartonomotor.xyz.key;
    
    # SSL Security Settings
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Root directory for Laravel
    root /var/www/hartonomotor.xyz/public;
    index index.php index.html index.htm;

    # WhatsApp API Reverse Proxy
    location /whatsapp-api/ {
        proxy_pass http://localhost:3000/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        
        # CORS headers for WhatsApp API
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }
    }

    # Laravel Application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    # Static files caching
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
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
    access_log /var/log/nginx/hartonomotor.xyz.access.log;
    error_log /var/log/nginx/hartonomotor.xyz.error.log;
}
EOF

echo -e "${GREEN}✅ Nginx configuration created${NC}"

# Test nginx configuration
echo -e "${YELLOW}🧪 Step 3: Testing Nginx configuration...${NC}"
if sudo nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    echo -e "${YELLOW}Please check the configuration and try again${NC}"
    exit 1
fi

# Enable site (if using sites-enabled)
echo -e "${YELLOW}🔗 Step 4: Enabling site...${NC}"
if [ -d "/etc/nginx/sites-enabled" ]; then
    sudo ln -sf ${NGINX_SITE} /etc/nginx/sites-enabled/
    echo -e "${GREEN}✅ Site enabled${NC}"
fi

# Reload nginx
echo -e "${YELLOW}🔄 Step 5: Reloading Nginx...${NC}"
if sudo systemctl reload nginx; then
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Failed to reload Nginx${NC}"
    echo -e "${YELLOW}Please check nginx status: sudo systemctl status nginx${NC}"
    exit 1
fi

# Test WhatsApp API proxy
echo -e "${YELLOW}🧪 Step 6: Testing WhatsApp API proxy...${NC}"
sleep 2

if curl -s -k https://${DOMAIN}/whatsapp-api/app/devices >/dev/null; then
    echo -e "${GREEN}✅ WhatsApp API proxy is working${NC}"
else
    echo -e "${YELLOW}⚠️ WhatsApp API proxy test failed (this is normal if API is not authenticated yet)${NC}"
fi

# Display completion message
echo -e "${GREEN}🎉 Nginx configuration completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Update WhatsApp config in admin panel:"
echo -e "   - URL: ${YELLOW}https://${DOMAIN}/whatsapp-api${NC}"
echo -e "   - Test connection in admin panel"
echo -e "2. Scan QR code: ${YELLOW}https://${DOMAIN}/whatsapp-api/app/login${NC}"
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo -e "  Check Nginx status: ${YELLOW}sudo systemctl status nginx${NC}"
echo -e "  View Nginx logs: ${YELLOW}sudo tail -f /var/log/nginx/hartonomotor.xyz.error.log${NC}"
echo -e "  Test config: ${YELLOW}sudo nginx -t${NC}"
echo ""
echo -e "${GREEN}✅ Nginx configuration script completed successfully!${NC}"
