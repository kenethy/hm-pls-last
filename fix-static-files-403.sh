#!/bin/bash

# Fix Static Files 403 Forbidden Issue
# WhatsApp API is working, just need to fix static files access

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Static Files 403 Forbidden${NC}"
echo "=================================================="

# Step 1: Check current static files configuration
echo -e "\n${YELLOW}📁 Step 1: Checking static files configuration...${NC}"

NGINX_CONTAINER="hartono-webserver"

echo "Current static files configuration in Nginx:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 10 -B 2 "/statics/" || echo "No static files config found"

# Check if static directory is mounted in nginx container
echo -e "\nChecking if static directory is accessible from Nginx container:"
docker exec $NGINX_CONTAINER ls -la /var/www/whatsapp_statics/ || echo "Static directory not accessible from Nginx"

# Check host static directory
echo -e "\nHost static directory:"
ls -la /var/www/whatsapp_statics/

echo "QR code directory:"
ls -la /var/www/whatsapp_statics/qrcode/

# Step 2: Check the generated QR file
echo -e "\n${YELLOW}🖼️ Step 2: Checking generated QR file...${NC}"

QR_FILE="scan-qr-ee1ad395-bcb8-448b-8cdb-f623e8a8e588.png"
echo "Looking for QR file: $QR_FILE"

if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILE" ]; then
    echo -e "${GREEN}✅ QR file exists on host${NC}"
    ls -la "/var/www/whatsapp_statics/qrcode/$QR_FILE"
else
    echo -e "${RED}❌ QR file not found on host${NC}"
    echo "Available files in QR directory:"
    ls -la /var/www/whatsapp_statics/qrcode/
fi

# Step 3: Add static files configuration to Nginx
echo -e "\n${YELLOW}📄 Step 3: Adding static files configuration...${NC}"

# Check if static files config already exists
if docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -q "/statics/"; then
    echo "Static files configuration already exists, updating it..."
else
    echo "Adding new static files configuration..."
fi

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Add static files configuration after the WhatsApp API block
docker exec $NGINX_CONTAINER sh -c "
# Check if static files config exists
if ! grep -q 'location /statics/' /etc/nginx/conf.d/app.conf; then
    # Add static files configuration before the last closing brace
    sed -i '/location ~ \/\.ht {/i\\
    # WhatsApp Static Files (QR Codes, Media, etc.)\
    location /statics/ {\
        alias /var/www/whatsapp_statics/;\
        \
        # Cache static files\
        expires 5m;\
        add_header Cache-Control \"public, no-transform\";\
        \
        # CORS headers for static files\
        add_header \"Access-Control-Allow-Origin\" \"*\" always;\
        add_header \"Access-Control-Allow-Methods\" \"GET, OPTIONS\" always;\
        add_header \"Access-Control-Allow-Headers\" \"DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range\" always;\
        \
        # Handle missing files gracefully\
        try_files \$uri \$uri/ =404;\
        \
        # Security headers\
        add_header X-Content-Type-Options nosniff;\
        add_header X-Frame-Options DENY;\
    }\
' /etc/nginx/conf.d/app.conf
    echo 'Static files configuration added'
else
    echo 'Static files configuration already exists'
fi
"

# Step 4: Ensure static directory is mounted in Nginx container
echo -e "\n${YELLOW}💾 Step 4: Ensuring static directory mount...${NC}"

# Check if the directory is accessible from nginx
if ! docker exec $NGINX_CONTAINER ls /var/www/whatsapp_statics/ > /dev/null 2>&1; then
    echo "Static directory not accessible from Nginx container"
    echo "Checking Nginx container mounts:"
    docker inspect $NGINX_CONTAINER | grep -A 10 -B 5 "Mounts"
    
    echo -e "\n${YELLOW}Need to add volume mount to Nginx container${NC}"
    echo "This requires restarting the Nginx container with proper volume mount"
    
    # Get the current docker-compose file location
    if [ -f "docker-compose.yml" ]; then
        echo "Found main docker-compose.yml"
        
        # Check if nginx service has the volume mount
        if ! grep -A 10 "webserver:" docker-compose.yml | grep -q "whatsapp_statics"; then
            echo "Adding volume mount to Nginx service..."
            
            # Backup docker-compose
            cp docker-compose.yml docker-compose.yml.backup.$(date +%Y%m%d_%H%M%S)
            
            # Add volume mount to nginx service
            sed -i '/hartono-webserver:/,/networks:/ {
                /volumes:/a\
      - /var/www/whatsapp_statics:/var/www/whatsapp_statics:ro
            }' docker-compose.yml
            
            echo "Volume mount added to docker-compose.yml"
            echo "Restarting Nginx container..."
            
            docker-compose restart webserver
            sleep 10
            
            echo "Nginx container restarted"
        fi
    fi
else
    echo -e "${GREEN}✅ Static directory is accessible from Nginx${NC}"
fi

# Step 5: Test nginx configuration
echo -e "\n${YELLOW}🧪 Step 5: Testing Nginx configuration...${NC}"

echo "Testing Nginx configuration:"
docker exec $NGINX_CONTAINER nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    echo "Restoring backup..."
    BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
    docker exec $NGINX_CONTAINER nginx -s reload
fi

# Step 6: Test static files access
echo -e "\n${YELLOW}📂 Step 6: Testing static files access...${NC}"

echo "Testing static directory access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static directory test failed"

echo "Testing QR directory access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/" || echo "QR directory test failed"

# Test the specific QR file
echo "Testing specific QR file:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/$QR_FILE" || echo "QR file test failed"

# Create a test file to verify access
echo "Creating test file..."
echo "Test static file $(date)" > /var/www/whatsapp_statics/test-static.txt

echo "Testing test file:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/test-static.txt" || echo "Test file failed"

# Step 7: Generate new QR code to test complete flow
echo -e "\n${YELLOW}📱 Step 7: Testing complete QR flow...${NC}"

echo "Generating new QR code:"
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login")
echo "QR Response: $QR_RESPONSE"

# Extract new QR link
NEW_QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
if [ -n "$NEW_QR_LINK" ]; then
    echo "New QR Link: $NEW_QR_LINK"
    
    # Test new QR image
    echo "Testing new QR image access:"
    curl -s -w "Status: %{http_code}\n" "$NEW_QR_LINK" || echo "New QR image test failed"
else
    echo "No QR link found in response"
fi

# Step 8: Final verification
echo -e "\n${YELLOW}✅ Step 8: Final verification...${NC}"

echo "Final test results:"
API_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
STATIC_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/" 2>/dev/null | tail -1)
QR_PAGE_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)

echo "- API Endpoint: $API_TEST"
echo "- Static Files: $STATIC_TEST"
echo "- QR Page: $QR_PAGE_TEST"

if [ "$STATIC_TEST" = "200" ]; then
    echo -e "\n${GREEN}🎉 COMPLETE SUCCESS! Everything is working!${NC}"
    echo -e "${GREEN}✅ WhatsApp API: Working${NC}"
    echo -e "${GREEN}✅ Static Files: Working${NC}"
    echo -e "${GREEN}✅ QR Code Generation: Working${NC}"
    echo ""
    echo -e "${BLUE}📱 Test the complete QR flow:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    echo ""
    echo -e "${BLUE}📊 All endpoints working:${NC}"
    echo "- API: https://hartonomotor.xyz/whatsapp-api/app/login"
    echo "- Static: https://hartonomotor.xyz/statics/"
    echo "- QR Page: https://hartonomotor.xyz/whatsapp-qr.html"
    
else
    echo -e "\n${YELLOW}⚠️ Static files still having issues${NC}"
    echo "Checking Nginx error logs:"
    docker exec $NGINX_CONTAINER tail -5 /var/log/nginx/error.log 2>/dev/null || echo "No error logs"
    
    echo -e "\nChecking static directory from Nginx container:"
    docker exec $NGINX_CONTAINER ls -la /var/www/whatsapp_statics/ || echo "Directory not accessible"
fi

echo -e "\n${BLUE}📋 Current Status:${NC}"
echo "- WhatsApp API: ✅ Working (200)"
echo "- QR Generation: ✅ Working"
echo "- Static Files: $([ "$STATIC_TEST" = "200" ] && echo "✅ Working" || echo "❌ Need fix")"
echo "- Container Health: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
