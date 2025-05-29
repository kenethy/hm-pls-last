#!/bin/bash

# Restart and Verify WhatsApp Integration
# Proper restart sequence for VPS services

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔄 Restart and Verify WhatsApp Integration${NC}"
echo "=================================================="

echo -e "${YELLOW}🔧 Performing proper restart sequence for VPS...${NC}"

# Step 1: Check current status
echo -e "\n${YELLOW}📊 Step 1: Current Status${NC}"
echo "Current container status:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(whatsapp|nginx|hartono)"

# Step 2: Restart WhatsApp container first
echo -e "\n${YELLOW}🔄 Step 2: Restarting WhatsApp container${NC}"
echo "Stopping WhatsApp container..."
docker stop whatsapp-api-hartono || echo "Container already stopped"

echo "Starting WhatsApp container..."
docker start whatsapp-api-hartono

echo "Waiting for WhatsApp container to be ready..."
sleep 15

echo "WhatsApp container status:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep whatsapp

# Step 3: Test direct WhatsApp API
echo -e "\n${YELLOW}🧪 Step 3: Testing direct WhatsApp API${NC}"
echo "Testing direct API connection..."

# Wait a bit more for the API to be fully ready
sleep 10

DIRECT_API=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
echo "Direct API Status: $DIRECT_API"

if [ "$DIRECT_API" = "200" ]; then
    echo -e "${GREEN}✅ WhatsApp API is responding directly${NC}"
else
    echo -e "${RED}❌ WhatsApp API not responding directly${NC}"
    echo "Checking container logs:"
    docker logs --tail=10 whatsapp-api-hartono
fi

# Step 4: Restart Nginx container
echo -e "\n${YELLOW}🔄 Step 4: Restarting Nginx container${NC}"
echo "Restarting Nginx container..."
docker restart hartono-webserver

echo "Waiting for Nginx to be ready..."
sleep 10

echo "Nginx container status:"
docker ps --format "table {{.Names}}\t{{.Status}}" | grep nginx

# Step 5: Test Nginx configuration
echo -e "\n${YELLOW}🧪 Step 5: Testing Nginx configuration${NC}"
echo "Testing Nginx config:"
docker exec hartono-webserver nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    exit 1
fi

# Step 6: Test API through Nginx
echo -e "\n${YELLOW}🌐 Step 6: Testing API through Nginx${NC}"
echo "Waiting for services to be fully ready..."
sleep 10

echo "Testing devices endpoint:"
API_DEVICES_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null)
API_DEVICES_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Devices API Status: $API_DEVICES_STATUS"
echo "Devices API Response: $API_DEVICES_RESPONSE"

echo -e "\nTesting login endpoint:"
API_LOGIN_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
API_LOGIN_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Login API Status: $API_LOGIN_STATUS"
echo "Login API Response: $API_LOGIN_RESPONSE"

# Step 7: Test QR generation
if [ "$API_LOGIN_STATUS" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 7: Testing QR generation${NC}"
    
    QR_LINK=$(echo "$API_LOGIN_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        
        # Wait for file creation
        sleep 5
        
        # Test QR image access
        QR_STATUS=$(curl -s -w "%{http_code}" "$QR_LINK" 2>/dev/null | tail -1)
        echo "QR Image Status: $QR_STATUS"
        
        # Check if file exists
        QR_FILENAME=$(basename "$QR_LINK")
        if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
            echo -e "${GREEN}✅ QR file created: $QR_FILENAME${NC}"
        else
            echo -e "${YELLOW}⚠️ QR file not found on server${NC}"
        fi
    fi
fi

# Step 8: Test static files
echo -e "\n${YELLOW}📂 Step 8: Testing static files${NC}"
echo "Creating test file..."
echo "Test after restart $(date)" > /var/www/whatsapp_statics/test-restart.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-restart.txt
sudo chmod 644 /var/www/whatsapp_statics/test-restart.txt

STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-restart.txt" 2>/dev/null | tail -1)
echo "Static File Status: $STATIC_STATUS"

# Step 9: Test QR page
echo -e "\n${YELLOW}🌐 Step 9: Testing QR page${NC}"
QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)
echo "QR Page Status: $QR_PAGE_STATUS"

# Step 10: Final comprehensive results
echo -e "\n${YELLOW}✅ Step 10: Final Results After Restart${NC}"
echo "=================================================================="

echo "COMPREHENSIVE STATUS AFTER RESTART:"
echo "- Direct WhatsApp API: $DIRECT_API"
echo "- API Devices (via Nginx): $API_DEVICES_STATUS"
echo "- API Login (via Nginx): $API_LOGIN_STATUS"
echo "- QR Generation: $([ -n "$QR_LINK" ] && echo "✅ Working" || echo "❌ Failed")"
echo "- QR Image Access: $QR_STATUS"
echo "- Static Files: $STATIC_STATUS"
echo "- QR Page: $QR_PAGE_STATUS"

# Success evaluation
if [ "$API_DEVICES_STATUS" = "200" ] && [ "$API_LOGIN_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Everything working after restart!${NC}"
    echo -e "${GREEN}✅ WhatsApp API fully operational${NC}"
    echo -e "${GREEN}✅ Nginx proxy working${NC}"
    echo -e "${GREEN}✅ QR generation functional${NC}"
    
    if [ "$QR_STATUS" = "200" ] && [ "$STATIC_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Static files serving working${NC}"
        echo -e "\n${GREEN}🎊 COMPLETE SUCCESS! All systems operational!${NC}"
    fi
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system is ready:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
elif [ "$DIRECT_API" = "200" ] && [ "$API_DEVICES_STATUS" != "200" ]; then
    echo -e "\n${YELLOW}⚠️ WhatsApp API working, but Nginx proxy issue${NC}"
    echo "Direct API works, but proxy needs attention"
    
elif [ "$DIRECT_API" != "200" ]; then
    echo -e "\n${RED}❌ WhatsApp API container issue${NC}"
    echo "Container needs attention"
    echo "Recent container logs:"
    docker logs --tail=10 whatsapp-api-hartono
    
else
    echo -e "\n${YELLOW}⚠️ Mixed results - some components working${NC}"
fi

# Step 11: Container health check
echo -e "\n${YELLOW}🏥 Step 11: Container Health Check${NC}"
echo "Container statuses:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(whatsapp|nginx|hartono)"

echo -e "\nContainer resource usage:"
docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" | grep -E "(whatsapp|nginx|hartono)"

# Step 12: Network connectivity check
echo -e "\n${YELLOW}🌐 Step 12: Network Connectivity Check${NC}"
echo "Testing container-to-container connectivity:"
docker exec hartono-webserver ping -c 2 192.168.144.2 || echo "Cannot ping WhatsApp container"

echo -e "\nTesting from Nginx to WhatsApp API:"
docker exec hartono-webserver wget -qO- --timeout=5 "http://192.168.144.2:3000/app/devices" || echo "Cannot connect to WhatsApp API"

# Step 13: Recommendations
echo -e "\n${BLUE}📋 Recommendations:${NC}"

if [ "$API_DEVICES_STATUS" = "200" ] && [ "$API_LOGIN_STATUS" = "200" ]; then
    echo "✅ System is working perfectly!"
    echo "✅ No further action needed"
    echo "✅ You can start using the WhatsApp integration"
    
elif [ "$DIRECT_API" = "200" ]; then
    echo "🔧 WhatsApp API is healthy, fix Nginx proxy configuration"
    echo "🔧 Check nginx error logs: docker logs hartono-webserver"
    
else
    echo "🔧 Restart WhatsApp container: docker restart whatsapp-api-hartono"
    echo "🔧 Check container logs: docker logs whatsapp-api-hartono"
    echo "🔧 Verify container health: docker inspect whatsapp-api-hartono"
fi

echo -e "\n${BLUE}📊 Monitoring Commands:${NC}"
echo "- Check containers: docker ps"
echo "- Check API: curl https://hartonomotor.xyz/whatsapp-api/app/devices"
echo "- Check logs: docker logs -f whatsapp-api-hartono"
echo "- Check nginx: docker logs -f hartono-webserver"

if [ "$API_DEVICES_STATUS" = "200" ] && [ "$API_LOGIN_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 RESTART SUCCESSFUL!${NC}"
    echo -e "${GREEN}Your WhatsApp integration is ready for use!${NC}"
fi
