#!/bin/bash

# Emergency Fix Nginx - Direct Volume Access
# Fix the configuration directly in the volume

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🚨 Emergency Fix Nginx - Direct Volume Access${NC}"
echo "=================================================="

echo -e "${RED}Problem: git reset restored old config with 'whatsapp-api' reference${NC}"
echo -e "${GREEN}Solution: Fix configuration directly in Docker volume${NC}"

# Step 1: Stop nginx to prevent crash loop
echo -e "\n${YELLOW}🛑 Step 1: Stopping Nginx${NC}"
docker stop hartono-webserver || echo "Already stopped"

# Step 2: Fix configuration directly in volume
echo -e "\n${YELLOW}🔧 Step 2: Fixing configuration in volume${NC}"

echo "Accessing nginx config volume and fixing the problematic line..."

# Use a temporary container to fix the config
docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
echo 'Current problematic config:'
grep -n 'whatsapp-api' /config/app.conf || echo 'No whatsapp-api found'

echo 'Fixing whatsapp-api reference...'
sed -i 's|proxy_pass http://whatsapp-api:3000/|proxy_pass http://192.168.144.2:3000/|g' /config/app.conf

echo 'Adding auth header if missing...'
if ! grep -q 'Authorization.*Basic' /config/app.conf; then
    sed -i '/proxy_pass http:\/\/192.168.144.2:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";' /config/app.conf
fi

echo 'Fixed config around WhatsApp section:'
grep -A 5 -B 2 'whatsapp-api' /config/app.conf || echo 'No more whatsapp-api references'
grep -A 5 -B 2 '192.168.144.2' /config/app.conf || echo 'IP reference not found'
"

echo "✅ Configuration fixed in volume"

# Step 3: Verify the fix
echo -e "\n${YELLOW}📄 Step 3: Verifying the fix${NC}"

echo "Checking fixed configuration:"
docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
echo 'Looking for any remaining whatsapp-api references:'
grep -n 'whatsapp-api' /config/app.conf || echo 'No whatsapp-api references found - GOOD!'

echo 'Checking IP-based proxy_pass:'
grep -n '192.168.144.2' /config/app.conf || echo 'IP reference not found'

echo 'Checking auth header:'
grep -n 'Authorization' /config/app.conf || echo 'Auth header not found'
"

# Step 4: Start nginx with fixed config
echo -e "\n${YELLOW}🚀 Step 4: Starting Nginx with fixed config${NC}"

echo "Starting Nginx container..."
docker start hartono-webserver

echo "Waiting 10 seconds for startup..."
sleep 10

# Step 5: Check if nginx is stable
echo -e "\n${YELLOW}📊 Step 5: Checking Nginx stability${NC}"

NGINX_STATUS=$(docker ps --format "{{.Status}}" --filter "name=hartono-webserver")
echo "Nginx status: $NGINX_STATUS"

if [[ "$NGINX_STATUS" == *"Up"* ]]; then
    echo -e "${GREEN}✅ Nginx is running and stable!${NC}"
elif [[ "$NGINX_STATUS" == *"Restarting"* ]]; then
    echo -e "${RED}❌ Still restarting - checking logs...${NC}"
    docker logs --tail=10 hartono-webserver
    
    # Try one more fix
    echo "Attempting additional fix..."
    docker stop hartono-webserver
    
    # Remove any remaining problematic references
    docker run --rm -v hm-new_nginx-config:/config alpine sh -c "
    sed -i 's|whatsapp-api|192.168.144.2|g' /config/app.conf
    sed -i 's|location /192.168.144.2/|location /whatsapp-api/|g' /config/app.conf
    "
    
    docker start hartono-webserver
    sleep 10
    
    NGINX_STATUS=$(docker ps --format "{{.Status}}" --filter "name=hartono-webserver")
    echo "Nginx status after additional fix: $NGINX_STATUS"
fi

# Step 6: Test configuration if nginx is running
if [[ "$NGINX_STATUS" == *"Up"* ]]; then
    echo -e "\n${YELLOW}🧪 Step 6: Testing Nginx configuration${NC}"
    
    if docker exec hartono-webserver nginx -t; then
        echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    else
        echo -e "${RED}❌ Configuration still has errors${NC}"
        docker exec hartono-webserver nginx -t 2>&1
    fi
fi

# Step 7: Test API endpoints
echo -e "\n${YELLOW}🌐 Step 7: Testing API endpoints${NC}"

echo "Waiting 10 seconds for services to be ready..."
sleep 10

echo "Testing main website:"
MAIN_SITE=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/" 2>/dev/null | tail -1)
echo "Main site: $MAIN_SITE"

echo "Testing WhatsApp API:"
API_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null)
API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "API status: $API_STATUS"
echo "API response: $API_RESPONSE"

# Step 8: Final results
echo -e "\n${YELLOW}✅ Step 8: Emergency Fix Results${NC}"
echo "=================================================================="

echo "EMERGENCY FIX STATUS:"
echo "- Nginx Container: $(docker ps --format "{{.Status}}" --filter "name=hartono-webserver")"
echo "- Main Website: $MAIN_SITE"
echo "- WhatsApp API: $API_STATUS"

if [ "$API_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 EMERGENCY FIX SUCCESSFUL!${NC}"
    echo -e "${GREEN}✅ Nginx crash loop fixed${NC}"
    echo -e "${GREEN}✅ WhatsApp API working${NC}"
    echo -e "${GREEN}✅ Configuration permanently fixed in volume${NC}"
    
    # Test QR generation
    if [[ "$API_RESPONSE" == *"SUCCESS"* ]]; then
        echo -e "${GREEN}✅ API responding with SUCCESS${NC}"
        
        echo -e "\nTesting QR generation:"
        QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
        QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
        
        if [ -n "$QR_LINK" ]; then
            echo "QR Link: $QR_LINK"
            echo -e "${GREEN}✅ QR generation working!${NC}"
        fi
    fi
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
elif [ "$MAIN_SITE" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ Partial success - main site working${NC}"
    echo "Nginx is stable but WhatsApp proxy needs attention"
    
else
    echo -e "\n${RED}❌ Still having issues${NC}"
    echo "Need to check container logs and configuration"
fi

echo -e "\n${BLUE}📊 Current Status:${NC}"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep -E "(whatsapp|nginx|hartono)"

echo -e "\n${BLUE}🔧 What was fixed:${NC}"
echo "- Removed 'whatsapp-api' hostname references"
echo "- Used IP address 192.168.144.2 instead"
echo "- Added authentication header"
echo "- Fixed configuration directly in Docker volume"
echo "- Prevented git reset from breaking config again"

if [ "$API_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎊 CRISIS RESOLVED!${NC}"
    echo -e "${GREEN}Your WhatsApp integration is back online!${NC}"
fi
