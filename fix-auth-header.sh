#!/bin/bash

# Fix Authentication Header and Test Direct Connection
# Restore auth header that was lost during config changes

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fixing Authentication Header${NC}"
echo "=================================================="

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

# Step 1: Check current WhatsApp config
echo -e "\n${YELLOW}🔍 Step 1: Current WhatsApp configuration...${NC}"
echo "Current WhatsApp proxy configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 15 -B 2 "location /whatsapp-api/"

# Step 2: Test direct connection to container
echo -e "\n${YELLOW}🧪 Step 2: Testing direct container connection...${NC}"

echo "Testing direct connection to WhatsApp container:"
curl -s -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct connection failed"

echo "Testing with authentication:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Auth connection failed"

# Step 3: Add missing authentication header
echo -e "\n${YELLOW}🔧 Step 3: Adding authentication header...${NC}"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Add authorization header after proxy_pass line
docker exec $NGINX_CONTAINER sed -i "/proxy_pass http:\/\/$WHATSAPP_IP:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf

# Add proxy buffering off
docker exec $NGINX_CONTAINER sed -i "/proxy_set_header Authorization/a\\        proxy_buffering off;" /etc/nginx/conf.d/app.conf

echo "Updated configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 10 -B 2 "proxy_pass.*$WHATSAPP_IP"

# Step 4: Test nginx and reload
echo -e "\n${YELLOW}🔄 Step 4: Testing and reloading Nginx...${NC}"

if docker exec $NGINX_CONTAINER nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    exit 1
fi

# Step 5: Test API endpoints after fix
echo -e "\n${YELLOW}🔗 Step 5: Testing API endpoints after fix...${NC}"

echo "Testing devices endpoint:"
API_DEVICES=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Devices API: $API_DEVICES"

if [ "$API_DEVICES" = "200" ]; then
    echo -e "${GREEN}✅ Devices API working!${NC}"
else
    echo -e "${RED}❌ Devices API still not working${NC}"
    echo "Testing with verbose output:"
    curl -v "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>&1 | head -20
fi

echo "Testing login endpoint:"
API_LOGIN=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Login API: $API_LOGIN"

# Step 6: Generate QR if working
if [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 6: Generating QR code...${NC}"
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    echo "QR Response: $QR_RESPONSE"
    
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        
        # Test QR link access
        QR_STATUS=$(curl -s -w "%{http_code}" "$QR_LINK" 2>/dev/null | tail -1)
        echo "QR Image Status: $QR_STATUS"
    fi
else
    echo -e "${RED}❌ Login API not working${NC}"
fi

# Step 7: Check static files configuration
echo -e "\n${YELLOW}📂 Step 7: Checking static files...${NC}"

echo "Static files configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 10 -B 2 "/statics/" || echo "No static config found"

# Create and test static file
echo "Creating test static file:"
echo "Test static $(date)" > /var/www/whatsapp_statics/test-auth.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-auth.txt
sudo chmod 644 /var/www/whatsapp_statics/test-auth.txt

STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-auth.txt" 2>/dev/null | tail -1)
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
    echo -e "${GREEN}✅ Authentication header fixed${NC}"
    echo -e "${GREEN}✅ API endpoints operational${NC}"
    echo -e "${GREEN}✅ QR generation working${NC}"
    
    if [ "$STATIC_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Static files working${NC}"
        echo -e "\n${GREEN}🎯 COMPLETE SUCCESS! Everything operational!${NC}"
    else
        echo -e "${YELLOW}⚠️ Static files need attention${NC}"
    fi
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system is ready:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
elif [ "$API_DEVICES" = "401" ] || [ "$API_LOGIN" = "401" ]; then
    echo -e "\n${RED}❌ Still getting 401 Unauthorized${NC}"
    echo "Authentication header might not be working properly"
    echo "Check if the base64 encoding is correct"
    
elif [ "$API_DEVICES" = "404" ] || [ "$API_LOGIN" = "404" ]; then
    echo -e "\n${RED}❌ Getting 404 Not Found${NC}"
    echo "Path routing issue - check nginx location blocks"
    
else
    echo -e "\n${YELLOW}⚠️ Mixed results - some endpoints working${NC}"
fi

echo -e "\n${BLUE}📊 Debug Information:${NC}"
echo "- Container Status: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
echo "- Container IP: $WHATSAPP_IP"
echo "- Auth Header: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE="

echo -e "\n${BLUE}📋 Next Steps:${NC}"
if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo "✅ System is working! Test the QR page"
elif [ "$API_DEVICES" = "401" ]; then
    echo "🔧 Fix authentication header encoding"
elif [ "$API_DEVICES" = "404" ]; then
    echo "🔧 Check nginx location path configuration"
else
    echo "🔧 Check container logs: docker logs whatsapp-api-hartono"
fi
