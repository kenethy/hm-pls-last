#!/bin/bash

# Fix All Nginx References to Use IP Address
# Complete fix for whatsapp-api references

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fixing All Nginx References${NC}"
echo "=================================================="

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

# Step 1: Show current problematic config
echo -e "\n${YELLOW}🔍 Step 1: Current problematic config...${NC}"
echo "Line 32 issue:"
docker exec $NGINX_CONTAINER sed -n '30,35p' /etc/nginx/conf.d/app.conf

echo -e "\nAll whatsapp-api references:"
docker exec $NGINX_CONTAINER grep -n "whatsapp-api" /etc/nginx/conf.d/app.conf || echo "No references found"

# Step 2: Fix ALL references to use IP
echo -e "\n${YELLOW}🔧 Step 2: Fixing ALL references...${NC}"

# Backup first
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Replace ALL occurrences of whatsapp-api with IP address
docker exec $NGINX_CONTAINER sed -i "s|whatsapp-api|$WHATSAPP_IP|g" /etc/nginx/conf.d/app.conf

echo "Updated configuration around line 32:"
docker exec $NGINX_CONTAINER sed -n '30,35p' /etc/nginx/conf.d/app.conf

# Step 3: Fix location path back to /whatsapp-api/
echo -e "\n${YELLOW}📄 Step 3: Fixing location path...${NC}"
docker exec $NGINX_CONTAINER sed -i "s|location /$WHATSAPP_IP/|location /whatsapp-api/|g" /etc/nginx/conf.d/app.conf

echo "Fixed location path:"
docker exec $NGINX_CONTAINER grep -A 2 "location /whatsapp-api/" /etc/nginx/conf.d/app.conf

# Step 4: Test nginx configuration
echo -e "\n${YELLOW}🧪 Step 4: Testing Nginx configuration...${NC}"
if docker exec $NGINX_CONTAINER nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Nginx configuration still has errors${NC}"
    echo "Showing full WhatsApp configuration:"
    docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 20 -B 5 "whatsapp"
    exit 1
fi

# Step 5: Test API endpoints
echo -e "\n${YELLOW}🔗 Step 5: Testing API endpoints...${NC}"

echo "Testing devices endpoint:"
API_DEVICES=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Devices API: $API_DEVICES"

echo "Testing login endpoint:"
API_LOGIN=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Login API: $API_LOGIN"

# Step 6: Generate QR if API is working
if [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 6: Generating QR code...${NC}"
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        QR_FILENAME=$(basename "$QR_LINK")
        
        # Wait for file creation
        sleep 3
        
        if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
            echo -e "${GREEN}✅ QR file created: $QR_FILENAME${NC}"
        else
            echo -e "${YELLOW}⚠️ QR file not found, but API is working${NC}"
        fi
    else
        echo "No QR link in response"
    fi
else
    echo -e "${RED}❌ API not working, skipping QR generation${NC}"
fi

# Step 7: Fix static files
echo -e "\n${YELLOW}📂 Step 7: Testing static files...${NC}"

# Create test file
echo "Test static $(date)" > /var/www/whatsapp_statics/test-final.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-final.txt
sudo chmod 644 /var/www/whatsapp_statics/test-final.txt

# Test static access
STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-final.txt" 2>/dev/null | tail -1)
echo "Static file test: $STATIC_STATUS"

# Step 8: Final comprehensive test
echo -e "\n${YELLOW}✅ Step 8: Final Results${NC}"
echo "=================================================="

QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)

echo "Final Status Summary:"
echo "- API Devices: $API_DEVICES"
echo "- API Login: $API_LOGIN"
echo "- Static Files: $STATIC_STATUS"
echo "- QR Page: $QR_PAGE_STATUS"

# Success evaluation
if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! WhatsApp API is fully working!${NC}"
    echo -e "${GREEN}✅ You can generate QR codes${NC}"
    echo -e "${GREEN}✅ API endpoints are operational${NC}"
    
    if [ "$STATIC_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Static files are also working!${NC}"
        echo -e "\n${GREEN}🎯 COMPLETE SUCCESS! Everything is operational!${NC}"
        echo -e "\n${BLUE}📱 Your QR system is ready:${NC}"
        echo "https://hartonomotor.xyz/whatsapp-qr.html"
    else
        echo -e "${YELLOW}⚠️ Static files need attention, but core API works${NC}"
        echo -e "\n${BLUE}📱 QR generation works, but images might not display${NC}"
        echo "API is functional for QR generation"
    fi
    
elif [ "$API_DEVICES" = "200" ] || [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ Partial success - some API endpoints working${NC}"
    
else
    echo -e "\n${RED}❌ API endpoints still not working${NC}"
    echo "Need to check container and network connectivity"
fi

echo -e "\n${BLUE}📊 System Status:${NC}"
echo "- Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
echo "- Network IP: $WHATSAPP_IP"
echo "- Config: Using IP address in proxy_pass"

echo -e "\n${BLUE}📋 Monitoring Commands:${NC}"
echo "- Check API: curl https://hartonomotor.xyz/whatsapp-api/app/devices"
echo "- Check logs: docker logs -f whatsapp-api-hartono"
echo "- Check nginx: docker logs -f hartono-webserver"
