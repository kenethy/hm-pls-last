#!/bin/bash

# Quick Final Fix for WhatsApp QR System
# Simple and direct approach

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚀 Quick Final Fix for WhatsApp QR System${NC}"
echo "=================================================="

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

# Step 1: Fix location path
echo -e "\n${YELLOW}📄 Step 1: Fixing location path...${NC}"
docker exec $NGINX_CONTAINER sed -i "s|location /192.168.144.2/|location /whatsapp-api/|g" /etc/nginx/conf.d/app.conf
echo "✅ Location path fixed"

# Step 2: Test and reload nginx
echo -e "\n${YELLOW}🔧 Step 2: Reloading Nginx...${NC}"
docker exec $NGINX_CONTAINER nginx -t && docker exec $NGINX_CONTAINER nginx -s reload
echo "✅ Nginx reloaded"

# Step 3: Test API
echo -e "\n${YELLOW}🧪 Step 3: Testing API...${NC}"
API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "API Status: $API_STATUS"

# Step 4: Generate QR
echo -e "\n${YELLOW}📱 Step 4: Generating QR...${NC}"
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
echo "QR Generated: $(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)"

# Step 5: Fix static files permissions
echo -e "\n${YELLOW}🔐 Step 5: Fixing permissions...${NC}"
sudo chown -R www-data:www-data /var/www/whatsapp_statics/
sudo chmod -R 755 /var/www/whatsapp_statics/
echo "✅ Permissions fixed"

# Step 6: Test static files
echo -e "\n${YELLOW}📂 Step 6: Testing static files...${NC}"
echo "Test file" > /var/www/whatsapp_statics/test.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test.txt
STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test.txt" 2>/dev/null | tail -1)
echo "Static Status: $STATIC_STATUS"

# Step 7: Final test
echo -e "\n${YELLOW}✅ Step 7: Final Results${NC}"
QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)

echo "Final Status:"
echo "- API: $API_STATUS"
echo "- Static: $STATIC_STATUS"  
echo "- QR Page: $QR_PAGE_STATUS"

if [ "$API_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 WhatsApp API is working!${NC}"
    echo "✅ You can generate QR codes"
    echo "📱 Test at: https://hartonomotor.xyz/whatsapp-qr.html"
    
    if [ "$STATIC_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Static files also working!${NC}"
        echo -e "${GREEN}🎯 Complete success! Everything operational!${NC}"
    else
        echo -e "${YELLOW}⚠️ Static files still need work, but API is functional${NC}"
    fi
else
    echo -e "${RED}❌ API needs attention${NC}"
fi

echo -e "\n${BLUE}📊 Quick Status Check:${NC}"
echo "Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
echo "Files: $(ls -la /var/www/whatsapp_statics/qrcode/ | wc -l) items in QR directory"
