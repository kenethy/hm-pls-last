#!/bin/bash

# Fix Nginx to WhatsApp Container Connection
# Specific fix for 502 Bad Gateway issue

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Nginx to WhatsApp Container Connection${NC}"
echo "=================================================="

# Step 1: Check container network connectivity
echo -e "\n${YELLOW}🌐 Step 1: Checking container network connectivity...${NC}"

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_CONTAINER="whatsapp-api-hartono"

echo "Testing network connectivity between containers:"

# Check if containers are on the same network
echo "Nginx container networks:"
docker inspect $NGINX_CONTAINER | grep -A 10 "Networks" || echo "Cannot inspect nginx networks"

echo -e "\nWhatsApp container networks:"
docker inspect $WHATSAPP_CONTAINER | grep -A 10 "Networks" || echo "Cannot inspect whatsapp networks"

# Test connectivity from nginx to whatsapp
echo -e "\nTesting connectivity from Nginx to WhatsApp container:"
docker exec $NGINX_CONTAINER ping -c 2 $WHATSAPP_CONTAINER || echo "Ping failed - containers not on same network"

# Test HTTP connectivity
echo "Testing HTTP connectivity from Nginx to WhatsApp:"
docker exec $NGINX_CONTAINER wget -qO- --timeout=5 "http://$WHATSAPP_CONTAINER:3000/app/devices" || echo "HTTP connection failed"

# Step 2: Check current Nginx configuration
echo -e "\n${YELLOW}📄 Step 2: Checking current Nginx configuration...${NC}"

echo "Current WhatsApp API configuration in Nginx:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 15 -B 5 "whatsapp-api" || echo "No WhatsApp config found"

# Step 3: Fix Nginx configuration
echo -e "\n${YELLOW}🔧 Step 3: Fixing Nginx configuration...${NC}"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Create the corrected nginx configuration
cat > /tmp/nginx_fix.conf << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name hartonomotor.xyz www.hartonomotor.xyz;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name hartonomotor.xyz www.hartonomotor.xyz;

    ssl_certificate /etc/letsencrypt/live/hartonomotor.xyz/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/hartonomotor.xyz/privkey.pem;

    root /var/www/html;
    index index.php index.html index.htm;

    client_max_body_size 100M;

    # Main Laravel application
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass hartono-app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_connect_timeout 300;
    }

    # WhatsApp API Reverse Proxy - FIXED VERSION
    location /whatsapp-api/ {
        # Use container name with proper network resolution
        proxy_pass http://whatsapp-api-hartono:3000/;
        
        # Essential proxy headers
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # Add basic auth for WhatsApp API
        proxy_set_header Authorization "Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=";
        
        # Timeout settings
        proxy_connect_timeout 60s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # Disable buffering for real-time responses
        proxy_buffering off;
        proxy_cache_bypass $http_upgrade;
        
        # CORS headers
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
        try_files $uri $uri/ =404;
        
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

# Apply the new configuration
echo "Applying new Nginx configuration..."
docker cp /tmp/nginx_fix.conf $NGINX_CONTAINER:/etc/nginx/conf.d/app.conf

# Test nginx configuration
echo "Testing Nginx configuration:"
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
    docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S) /etc/nginx/conf.d/app.conf
    docker exec $NGINX_CONTAINER nginx -s reload
    exit 1
fi

# Step 4: Ensure containers are on the same network
echo -e "\n${YELLOW}🔗 Step 4: Ensuring containers are on the same network...${NC}"

# Check if both containers are on the same network
NGINX_NETWORK=$(docker inspect $NGINX_CONTAINER | grep -A 5 '"Networks"' | grep -o '"[^"]*"' | head -2 | tail -1 | tr -d '"')
WHATSAPP_NETWORK=$(docker inspect $WHATSAPP_CONTAINER | grep -A 5 '"Networks"' | grep -o '"[^"]*"' | head -2 | tail -1 | tr -d '"')

echo "Nginx network: $NGINX_NETWORK"
echo "WhatsApp network: $WHATSAPP_NETWORK"

if [ "$NGINX_NETWORK" != "$WHATSAPP_NETWORK" ]; then
    echo "Containers are on different networks. Connecting WhatsApp to Nginx network..."
    docker network connect $NGINX_NETWORK $WHATSAPP_CONTAINER || echo "Failed to connect networks"
fi

# Step 5: Test connectivity again
echo -e "\n${YELLOW}🧪 Step 5: Testing connectivity after fix...${NC}"

# Test container to container connectivity
echo "Testing container-to-container connectivity:"
docker exec $NGINX_CONTAINER wget -qO- --timeout=10 "http://$WHATSAPP_CONTAINER:3000/app/devices" || echo "Still cannot connect"

# Test API endpoints
echo -e "\nTesting API endpoints:"

echo "1. Direct API test:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct API failed"

echo "2. Nginx proxy test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Nginx proxy failed"

echo "3. Login endpoint test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Login endpoint failed"

# Step 6: Check static files
echo -e "\n${YELLOW}📁 Step 6: Testing static files access...${NC}"

echo "Testing static files access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static files access failed"

# Create a test QR file
echo "Creating test QR file..."
echo "Test QR Code $(date)" > /var/www/whatsapp_statics/qrcode/test-qr.txt

echo "Testing test QR file access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/test-qr.txt" || echo "Test QR file access failed"

# Step 7: Final verification
echo -e "\n${YELLOW}✅ Step 7: Final verification...${NC}"

echo "Container status:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "(whatsapp|nginx)"

echo -e "\nNginx error logs (last 5 lines):"
docker exec $NGINX_CONTAINER tail -5 /var/log/nginx/error.log 2>/dev/null || echo "No error logs"

echo -e "\nWhatsApp container logs (last 3 lines):"
docker logs --tail=3 $WHATSAPP_CONTAINER

echo -e "\n${GREEN}🎉 Fix completed!${NC}"
echo -e "\n${BLUE}📋 Test Results Summary:${NC}"

# Quick test summary
API_DIRECT=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
API_NGINX=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
STATIC_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/" 2>/dev/null | tail -1)

echo "- Direct API: $API_DIRECT"
echo "- Nginx Proxy: $API_NGINX"
echo "- Static Files: $STATIC_TEST"

if [ "$API_NGINX" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Now test the QR page:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
else
    echo -e "\n${RED}⚠️ Still having issues. Check the logs above.${NC}"
fi

echo -e "\n${BLUE}📊 Monitoring commands:${NC}"
echo "- Check containers: docker ps"
echo "- Nginx logs: docker logs hartono-webserver"
echo "- WhatsApp logs: docker logs whatsapp-api-hartono"
echo "- Test API: curl https://hartonomotor.xyz/whatsapp-api/app/devices"
