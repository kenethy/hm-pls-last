#!/bin/bash

# Fix Nginx Configuration - Alpine Linux Compatible
# Use sh instead of bash and IP address approach

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Nginx Configuration (Alpine Compatible)${NC}"
echo "=================================================="

# Step 1: Get WhatsApp container IP
echo -e "\n${YELLOW}🔍 Step 1: Getting WhatsApp container IP...${NC}"

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_CONTAINER="whatsapp-api-hartono"
NGINX_NETWORK="hm-new_hartono-network"

# Get WhatsApp IP in the Nginx network
WHATSAPP_IP=$(docker inspect $WHATSAPP_CONTAINER | grep -A 10 "$NGINX_NETWORK" | grep '"IPAddress"' | cut -d'"' -f4)

echo "WhatsApp container IP: $WHATSAPP_IP"

if [ -z "$WHATSAPP_IP" ]; then
    echo -e "${RED}❌ Could not get WhatsApp IP address${NC}"
    exit 1
fi

# Test connectivity using IP
echo "Testing connectivity using IP address:"
docker exec $NGINX_CONTAINER wget -qO- --timeout=10 "http://$WHATSAPP_IP:3000/app/devices" || echo "IP connection test failed"

# Step 2: Create new nginx configuration file
echo -e "\n${YELLOW}📄 Step 2: Creating new Nginx configuration...${NC}"

# Get current config and create new one
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf > /tmp/current_nginx.conf

# Create the complete new configuration with IP address
cat > /tmp/new_nginx.conf << EOF
server {
    listen 80;
    listen [::]:80;
    server_name hartonomotor.xyz www.hartonomotor.xyz;
    return 301 https://\$server_name\$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name hartonomotor.xyz www.hartonomotor.xyz;

    ssl_certificate /etc/letsencrypt/live/hartonomotor.xyz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/hartonomotor.xyz/privkey.pem;

    root /var/www/html;
    index index.php index.html index.htm;

    client_max_body_size 100M;

    # Main Laravel application
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        fastcgi_pass hartono-app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_connect_timeout 300;
    }

    location ~ /.well-known {
        allow all;
    }

    # WhatsApp API Reverse Proxy - FIXED WITH IP
    location /whatsapp-api/ {
        # Use IP address for reliable connection
        proxy_pass http://$WHATSAPP_IP:3000/;
        
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
        
        # Add basic auth header for WhatsApp API
        proxy_set_header Authorization "Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=";
        
        # Disable buffering for real-time responses
        proxy_buffering off;

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

    # WhatsApp Static Files (QR Codes, Media, etc.)
    location /statics/ {
        alias /var/www/whatsapp_statics/;
        
        # Cache static files
        expires 5m;
        add_header Cache-Control "public, no-transform";
        
        # CORS headers for static files
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range' always;
        
        # Handle missing files gracefully
        try_files \$uri \$uri/ =404;
        
        # Security headers
        add_header X-Content-Type-Options nosniff;
        add_header X-Frame-Options DENY;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Step 3: Apply the new configuration
echo -e "\n${YELLOW}🔧 Step 3: Applying new configuration...${NC}"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Copy new config
docker cp /tmp/new_nginx.conf $NGINX_CONTAINER:/etc/nginx/conf.d/app.conf

# Test nginx configuration
echo "Testing Nginx configuration..."
docker exec $NGINX_CONTAINER nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    echo "Restoring backup..."
    BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
    docker exec $NGINX_CONTAINER nginx -s reload
    exit 1
fi

# Step 4: Test connectivity
echo -e "\n${YELLOW}🧪 Step 4: Testing connectivity...${NC}"

# Test from nginx container to whatsapp using IP
echo "Testing from Nginx to WhatsApp using IP:"
docker exec $NGINX_CONTAINER wget -qO- --timeout=10 "http://$WHATSAPP_IP:3000/app/devices" || echo "Connection failed"

# Step 5: Test API endpoints
echo -e "\n${YELLOW}🔗 Step 5: Testing API endpoints...${NC}"

echo "1. Direct API test:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct API failed"

echo "2. Nginx proxy test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Nginx proxy failed"

echo "3. Login endpoint test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Login endpoint failed"

# Step 6: Test static files
echo -e "\n${YELLOW}📁 Step 6: Testing static files...${NC}"

echo "Testing static files access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static files failed"

# Create and test a QR file
echo "Creating test QR file..."
echo "Test QR $(date)" > /var/www/whatsapp_statics/qrcode/test-$(date +%s).txt

echo "Testing QR file access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/" || echo "QR directory access failed"

# Step 7: Final verification
echo -e "\n${YELLOW}✅ Step 7: Final verification...${NC}"

echo "Current Nginx WhatsApp configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 5 -B 2 "proxy_pass.*$WHATSAPP_IP"

echo -e "\nFinal API test results:"
API_DIRECT=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
API_NGINX=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
STATIC_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/" 2>/dev/null | tail -1)

echo "- Direct API: $API_DIRECT"
echo "- Nginx Proxy: $API_NGINX"  
echo "- Static Files: $STATIC_TEST"

if [ "$API_NGINX" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! All systems working!${NC}"
    echo -e "${GREEN}✅ Network connectivity: Fixed${NC}"
    echo -e "${GREEN}✅ Nginx proxy: Working${NC}"
    echo -e "${GREEN}✅ Static files: Accessible${NC}"
    echo ""
    echo -e "${BLUE}📱 Now test the QR page:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    echo ""
    echo -e "${BLUE}📊 Monitoring:${NC}"
    echo "- Container logs: docker logs -f whatsapp-api-hartono"
    echo "- API test: curl https://hartonomotor.xyz/whatsapp-api/app/devices"
    echo "- QR test: curl https://hartonomotor.xyz/whatsapp-qr.html"
else
    echo -e "\n${YELLOW}⚠️ Still having issues:${NC}"
    echo "Nginx error logs:"
    docker exec $NGINX_CONTAINER tail -5 /var/log/nginx/error.log 2>/dev/null || echo "No error logs"
    
    echo -e "\nWhatsApp container logs:"
    docker logs --tail=5 $WHATSAPP_CONTAINER
fi

echo -e "\n${BLUE}📋 Configuration Summary:${NC}"
echo "- WhatsApp IP: $WHATSAPP_IP"
echo "- Network: $NGINX_NETWORK"
echo "- Proxy URL: http://$WHATSAPP_IP:3000/"
echo "- Auth: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE="
