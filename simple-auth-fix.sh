#!/bin/bash

# Simple Authentication Header Fix
# Direct approach to add missing auth header

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Simple Authentication Header Fix${NC}"
echo "=================================================="

NGINX_CONTAINER="hartono-webserver"

# Step 1: Show current issue
echo -e "\n${YELLOW}🔍 Current Issue:${NC}"
echo "Direct API with auth works: ✅"
echo "Nginx proxy without auth: ❌ (401 Unauthorized)"
echo "Need to add: proxy_set_header Authorization"

# Step 2: Backup and fix
echo -e "\n${YELLOW}🔧 Adding auth header...${NC}"

# Backup
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Add auth header after proxy_pass line using a simpler approach
docker exec $NGINX_CONTAINER sh -c '
sed -i "/proxy_pass http:\/\/whatsapp-api:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf
'

echo "✅ Auth header added"

# Step 3: Test and reload
echo -e "\n${YELLOW}🔄 Testing and reloading...${NC}"

if docker exec $NGINX_CONTAINER nginx -t; then
    echo "✅ Config valid"
    docker exec $NGINX_CONTAINER nginx -s reload
    echo "✅ Nginx reloaded"
else
    echo "❌ Config error"
    exit 1
fi

# Step 4: Test API
echo -e "\n${YELLOW}🧪 Testing API...${NC}"

sleep 2

echo "Testing devices:"
API_DEVICES=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Result: $API_DEVICES"

echo "Testing login:"
API_LOGIN=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Result: $API_LOGIN"

# Step 5: Results
echo -e "\n${YELLOW}✅ Results:${NC}"

if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo -e "${GREEN}🎉 SUCCESS! API is working!${NC}"
    
    # Generate QR
    echo -e "\n${YELLOW}📱 Generating QR...${NC}"
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        echo -e "${GREEN}✅ QR generation working!${NC}"
        
        echo -e "\n${BLUE}📱 Test your QR system:${NC}"
        echo "https://hartonomotor.xyz/whatsapp-qr.html"
    fi
    
elif [ "$API_DEVICES" = "401" ]; then
    echo -e "${RED}❌ Still 401 - auth header not working${NC}"
    echo "Checking current config:"
    docker exec $NGINX_CONTAINER grep -A 5 "proxy_pass.*whatsapp-api" /etc/nginx/conf.d/app.conf
    
elif [ "$API_DEVICES" = "404" ]; then
    echo -e "${RED}❌ 404 - path issue${NC}"
    
else
    echo -e "${YELLOW}⚠️ Unexpected result: $API_DEVICES${NC}"
fi

echo -e "\n${BLUE}📊 Summary:${NC}"
echo "- API Devices: $API_DEVICES"
echo "- API Login: $API_LOGIN"
echo "- Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"

if [ "$API_DEVICES" = "200" ]; then
    echo -e "\n${GREEN}🎯 WhatsApp API is operational!${NC}"
    echo "You can now use the QR system for WhatsApp integration."
fi
