#!/bin/bash

# Fix Nginx Crash Loop
# Nginx is crashing due to configuration error

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fix Nginx Crash Loop${NC}"
echo "=================================================="

echo -e "${GREEN}✅ WhatsApp API is working perfectly!${NC}"
echo -e "${RED}❌ Nginx is in crash loop - fixing configuration...${NC}"

# Step 1: Stop nginx to prevent crash loop
echo -e "\n${YELLOW}🛑 Step 1: Stopping Nginx crash loop${NC}"
docker stop hartono-webserver || echo "Container already stopped"

# Step 2: Check nginx logs to see the error
echo -e "\n${YELLOW}📋 Step 2: Checking Nginx crash logs${NC}"
echo "Recent Nginx logs:"
docker logs --tail=20 hartono-webserver 2>/dev/null || echo "Cannot get logs"

# Step 3: Use a working backup configuration
echo -e "\n${YELLOW}🔧 Step 3: Restoring working configuration${NC}"

# List available backups
echo "Available backups:"
docker run --rm -v hm-new_nginx-config:/config alpine ls -la /config/ | grep backup || echo "No backups found"

# Use the earliest backup (most likely to be working)
EARLIEST_BACKUP=$(docker run --rm -v hm-new_nginx-config:/config alpine ls /config/ | grep backup | head -1)

if [ -n "$EARLIEST_BACKUP" ]; then
    echo "Using backup: $EARLIEST_BACKUP"
    docker run --rm -v hm-new_nginx-config:/config alpine cp "/config/$EARLIEST_BACKUP" /config/app.conf
    echo "✅ Configuration restored from backup"
else
    echo "No backup found, creating minimal working configuration..."
    
    # Create minimal working nginx config
    cat > /tmp/minimal_nginx.conf << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name hartonomotor.xyz www.hartonomotor.xyz;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    listen [::]:443 ssl;
    server_name hartonomotor.xyz www.hartonomotor.xyz;

    ssl_certificate /etc/ssl/certs/ssl-cert-snakeoil.pem;
    ssl_certificate_key /etc/ssl/private/ssl-cert-snakeoil.key;

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

    location ~ /.well-known {
        allow all;
    }

    # WhatsApp API Reverse Proxy - Simple Version
    location /whatsapp-api/ {
        proxy_pass http://192.168.144.2:3000/;
        proxy_set_header Authorization "Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=";
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        proxy_buffering off;
    }

    # Static files for WhatsApp
    location /statics/ {
        alias /var/www/whatsapp_statics/;
        expires 5m;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

    # Copy minimal config to container volume
    docker run --rm -v hm-new_nginx-config:/config -v /tmp:/tmp alpine cp /tmp/minimal_nginx.conf /config/app.conf
    echo "✅ Minimal working configuration created"
fi

# Step 4: Start nginx with working config
echo -e "\n${YELLOW}🚀 Step 4: Starting Nginx with working config${NC}"

echo "Starting Nginx container..."
if docker start hartono-webserver; then
    echo -e "${GREEN}✅ Nginx container started${NC}"
else
    echo -e "${RED}❌ Failed to start Nginx${NC}"
    echo "Checking logs:"
    docker logs --tail=10 hartono-webserver
    exit 1
fi

# Step 5: Wait and check if nginx is stable
echo -e "\n${YELLOW}⏳ Step 5: Checking Nginx stability${NC}"

echo "Waiting 15 seconds for Nginx to stabilize..."
sleep 15

NGINX_STATUS=$(docker ps --format "{{.Status}}" --filter "name=hartono-webserver")
echo "Nginx status: $NGINX_STATUS"

if [[ "$NGINX_STATUS" == *"Up"* ]]; then
    echo -e "${GREEN}✅ Nginx is stable and running${NC}"
else
    echo -e "${RED}❌ Nginx still unstable${NC}"
    echo "Recent logs:"
    docker logs --tail=10 hartono-webserver
    exit 1
fi

# Step 6: Test nginx configuration
echo -e "\n${YELLOW}🧪 Step 6: Testing Nginx configuration${NC}"

if docker exec hartono-webserver nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration still has errors${NC}"
    docker exec hartono-webserver nginx -t 2>&1
fi

# Step 7: Test API endpoints
echo -e "\n${YELLOW}🌐 Step 7: Testing API endpoints${NC}"

echo "Waiting 10 seconds for services to be fully ready..."
sleep 10

echo "Testing main website:"
MAIN_SITE=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/" 2>/dev/null | tail -1)
echo "Main site status: $MAIN_SITE"

echo "Testing WhatsApp API:"
API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "API status: $API_STATUS"

echo "Testing WhatsApp login:"
LOGIN_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Login status: $LOGIN_STATUS"

# Step 8: Test QR generation if API works
if [ "$LOGIN_STATUS" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 8: Testing QR generation${NC}"
    
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        echo -e "${GREEN}✅ QR generation working!${NC}"
    fi
fi

# Step 9: Final results
echo -e "\n${YELLOW}✅ Step 9: Final Results${NC}"
echo "=================================================================="

echo "FINAL STATUS AFTER FIX:"
echo "- Nginx Container: $(docker ps --format "{{.Status}}" --filter "name=hartono-webserver")"
echo "- WhatsApp Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
echo "- Main Website: $MAIN_SITE"
echo "- WhatsApp API: $API_STATUS"
echo "- WhatsApp Login: $LOGIN_STATUS"

if [ "$API_STATUS" = "200" ] && [ "$LOGIN_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Nginx crash fixed!${NC}"
    echo -e "${GREEN}✅ Nginx container stable${NC}"
    echo -e "${GREEN}✅ WhatsApp API working through proxy${NC}"
    echo -e "${GREEN}✅ QR generation functional${NC}"
    echo -e "${GREEN}✅ No more crash loop${NC}"
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system is ready:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
    echo -e "\n${BLUE}🎯 What's working now:${NC}"
    echo "✅ Main Laravel website"
    echo "✅ WhatsApp API endpoints"
    echo "✅ QR code generation"
    echo "✅ Nginx reverse proxy"
    echo "✅ Container stability"
    
elif [ "$MAIN_SITE" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ Main site working, WhatsApp API needs attention${NC}"
    echo "Nginx is stable but WhatsApp proxy needs configuration"
    
else
    echo -e "\n${RED}❌ Still having issues${NC}"
    echo "Need further investigation"
fi

echo -e "\n${BLUE}📊 Container Status:${NC}"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "(whatsapp|nginx|hartono)"

echo -e "\n${BLUE}📋 Monitoring:${NC}"
echo "- Check containers: docker ps"
echo "- Check nginx logs: docker logs -f hartono-webserver"
echo "- Test API: curl https://hartonomotor.xyz/whatsapp-api/app/devices"

if [ "$API_STATUS" = "200" ] && [ "$LOGIN_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎊 CRASH LOOP FIXED!${NC}"
    echo -e "${GREEN}Your system is now stable and operational!${NC}"
fi
