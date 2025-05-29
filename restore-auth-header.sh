#!/bin/bash

# Restore Authentication Header
# The auth header was lost when we fixed the proxy_pass

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Restoring Authentication Header${NC}"
echo "=================================================="

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

echo -e "${YELLOW}🔍 Problem: Auth header was lost when fixing proxy_pass${NC}"
echo -e "${YELLOW}🔧 Solution: Add auth header back${NC}"

# Step 1: Show current config
echo -e "\n${YELLOW}📄 Step 1: Current WhatsApp config...${NC}"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 10 -B 2 "proxy_pass.*$WHATSAPP_IP"

# Step 2: Add auth header back
echo -e "\n${YELLOW}🔧 Step 2: Adding auth header...${NC}"

# Add authorization header after proxy_pass line
docker exec $NGINX_CONTAINER sed -i "/proxy_pass http:\/\/$WHATSAPP_IP:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf

echo "✅ Auth header added"

# Step 3: Test and reload
echo -e "\n${YELLOW}🔄 Step 3: Testing and reloading...${NC}"

if docker exec $NGINX_CONTAINER nginx -t; then
    echo "✅ Config valid"
    docker exec $NGINX_CONTAINER nginx -s reload
    echo "✅ Nginx reloaded"
else
    echo "❌ Config error"
    exit 1
fi

# Step 4: Show updated config
echo -e "\n${YELLOW}📄 Step 4: Updated config...${NC}"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 5 -B 2 "proxy_pass.*$WHATSAPP_IP"

# Step 5: Test API
echo -e "\n${YELLOW}🧪 Step 5: Testing API...${NC}"

sleep 2

echo "Testing devices endpoint:"
API_DEVICES=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "API Devices: $API_DEVICES"

echo "Testing login endpoint:"
API_LOGIN=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "API Login: $API_LOGIN"

# Step 6: If API works, test QR generation
if [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 6: Testing QR generation...${NC}"
    
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        
        # Wait for file creation
        sleep 3
        
        # Test QR image access
        QR_STATUS=$(curl -s -w "%{http_code}" "$QR_LINK" 2>/dev/null | tail -1)
        echo "QR Image Status: $QR_STATUS"
        
        # Check if file exists on server
        QR_FILENAME=$(basename "$QR_LINK")
        if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
            echo "✅ QR file exists: $QR_FILENAME"
            ls -la "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
        fi
    fi
fi

# Step 7: Test static files
echo -e "\n${YELLOW}📂 Step 7: Testing static files...${NC}"

# Create test file
echo "Test $(date)" > /var/www/whatsapp_statics/test-auth-restored.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-auth-restored.txt
sudo chmod 644 /var/www/whatsapp_statics/test-auth-restored.txt

STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-auth-restored.txt" 2>/dev/null | tail -1)
echo "Static File Status: $STATIC_STATUS"

# Step 8: Final results
echo -e "\n${YELLOW}✅ Step 8: Final Results${NC}"
echo "=================================================="

QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)

echo "FINAL STATUS SUMMARY:"
echo "- API Devices: $API_DEVICES"
echo "- API Login: $API_LOGIN"
echo "- Static Files: $STATIC_STATUS"
echo "- QR Page: $QR_PAGE_STATUS"

if [ -n "$QR_STATUS" ]; then
    echo "- QR Image: $QR_STATUS"
fi

# Success evaluation
if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! API is working again!${NC}"
    echo -e "${GREEN}✅ Authentication header restored${NC}"
    echo -e "${GREEN}✅ WhatsApp API fully operational${NC}"
    
    if [ "$STATIC_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Static files working${NC}"
        echo -e "\n${GREEN}🎊 COMPLETE SUCCESS!${NC}"
        echo -e "${GREEN}Everything is working perfectly!${NC}"
    else
        echo -e "${YELLOW}⚠️ Static files need attention${NC}"
        echo "But core API functionality is working"
    fi
    
    if [ "$QR_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ QR images accessible${NC}"
        echo -e "\n${GREEN}🎯 FULL SYSTEM OPERATIONAL!${NC}"
    fi
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
elif [ "$API_DEVICES" = "401" ] || [ "$API_LOGIN" = "401" ]; then
    echo -e "\n${RED}❌ Still getting 401 Unauthorized${NC}"
    echo "Auth header might not be properly configured"
    
else
    echo -e "\n${YELLOW}⚠️ Mixed results${NC}"
    echo "Some endpoints working, others need attention"
fi

echo -e "\n${BLUE}📊 System Status:${NC}"
echo "- Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
echo "- Config: Using IP $WHATSAPP_IP with auth header"
echo "- QR Files: $(ls /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | wc -l) files"

echo -e "\n${BLUE}📋 Next Steps:${NC}"
if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo "✅ API is working - test the QR page!"
    if [ "$STATIC_STATUS" != "200" ]; then
        echo "🔧 Static files serving needs minor attention"
    fi
else
    echo "🔧 Check auth header configuration"
fi
