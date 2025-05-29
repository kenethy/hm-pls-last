#!/bin/bash

# Debug Restart Issues
# Check what's happening with containers

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔍 Debug Restart Issues${NC}"
echo "=================================================="

# Step 1: Check current container status
echo -e "\n${YELLOW}📊 Step 1: Current Container Status${NC}"
echo "All containers:"
docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo -e "\nSpecific containers we care about:"
docker ps -a | grep -E "(whatsapp|nginx|hartono)" || echo "No matching containers found"

# Step 2: Check what happened to Nginx
echo -e "\n${YELLOW}🔍 Step 2: Nginx Container Investigation${NC}"

NGINX_STATUS=$(docker ps -a --format "{{.Status}}" --filter "name=hartono-webserver")
echo "Nginx container status: $NGINX_STATUS"

if [[ "$NGINX_STATUS" == *"Exited"* ]]; then
    echo -e "${RED}❌ Nginx container exited! Checking logs...${NC}"
    echo "Recent Nginx logs:"
    docker logs --tail=20 hartono-webserver
    
    echo -e "\nTrying to start Nginx container..."
    docker start hartono-webserver || echo "Failed to start Nginx"
    
elif [[ "$NGINX_STATUS" == *"Up"* ]]; then
    echo -e "${GREEN}✅ Nginx container is running${NC}"
else
    echo -e "${YELLOW}⚠️ Nginx container in unknown state: $NGINX_STATUS${NC}"
fi

# Step 3: Check WhatsApp container
echo -e "\n${YELLOW}🔍 Step 3: WhatsApp Container Investigation${NC}"

WHATSAPP_STATUS=$(docker ps -a --format "{{.Status}}" --filter "name=whatsapp-api-hartono")
echo "WhatsApp container status: $WHATSAPP_STATUS"

if [[ "$WHATSAPP_STATUS" == *"Exited"* ]]; then
    echo -e "${RED}❌ WhatsApp container exited! Checking logs...${NC}"
    echo "Recent WhatsApp logs:"
    docker logs --tail=20 whatsapp-api-hartono
    
elif [[ "$WHATSAPP_STATUS" == *"Up"* ]]; then
    echo -e "${GREEN}✅ WhatsApp container is running${NC}"
else
    echo -e "${YELLOW}⚠️ WhatsApp container in unknown state: $WHATSAPP_STATUS${NC}"
fi

# Step 4: Test basic connectivity
echo -e "\n${YELLOW}🧪 Step 4: Basic Connectivity Tests${NC}"

echo "Testing if containers are accessible:"

# Test WhatsApp direct
echo "1. Testing WhatsApp direct API:"
DIRECT_TEST=$(curl -s -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
echo "   Direct API status: $DIRECT_TEST"

# Test with auth
echo "2. Testing WhatsApp with auth:"
AUTH_TEST=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
echo "   Auth API status: $AUTH_TEST"

# Test nginx
echo "3. Testing Nginx response:"
NGINX_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/" 2>/dev/null | tail -1)
echo "   Nginx status: $NGINX_TEST"

# Step 5: Check nginx configuration
echo -e "\n${YELLOW}📄 Step 5: Nginx Configuration Check${NC}"

if docker exec hartono-webserver nginx -t 2>/dev/null; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
else
    echo -e "${RED}❌ Nginx configuration has errors:${NC}"
    docker exec hartono-webserver nginx -t 2>&1 || echo "Cannot test nginx config"
fi

# Step 6: Manual restart approach
echo -e "\n${YELLOW}🔄 Step 6: Manual Restart Approach${NC}"

echo "Attempting manual restart of containers..."

# Stop containers gracefully
echo "Stopping containers..."
docker stop hartono-webserver whatsapp-api-hartono 2>/dev/null || echo "Some containers already stopped"

# Wait a moment
echo "Waiting 5 seconds..."
sleep 5

# Start WhatsApp first
echo "Starting WhatsApp container..."
if docker start whatsapp-api-hartono; then
    echo -e "${GREEN}✅ WhatsApp container started${NC}"
else
    echo -e "${RED}❌ Failed to start WhatsApp container${NC}"
fi

# Wait for WhatsApp to be ready
echo "Waiting 10 seconds for WhatsApp to be ready..."
sleep 10

# Start Nginx
echo "Starting Nginx container..."
if docker start hartono-webserver; then
    echo -e "${GREEN}✅ Nginx container started${NC}"
else
    echo -e "${RED}❌ Failed to start Nginx container${NC}"
    echo "Nginx logs:"
    docker logs --tail=10 hartono-webserver
fi

# Step 7: Final status check
echo -e "\n${YELLOW}📊 Step 7: Final Status Check${NC}"

echo "Container status after manual restart:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(whatsapp|nginx|hartono)" || echo "No containers running"

# Step 8: Quick API test
echo -e "\n${YELLOW}🧪 Step 8: Quick API Test${NC}"

echo "Waiting 10 seconds for services to be ready..."
sleep 10

echo "Testing API endpoints:"

# Test direct API
FINAL_DIRECT=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
echo "Direct API: $FINAL_DIRECT"

# Test via nginx
FINAL_NGINX=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Nginx API: $FINAL_NGINX"

# Step 9: Results and recommendations
echo -e "\n${YELLOW}✅ Step 9: Results and Recommendations${NC}"
echo "=================================================================="

echo "FINAL STATUS:"
echo "- WhatsApp Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono" 2>/dev/null || echo "Not running")"
echo "- Nginx Container: $(docker ps --format "{{.Status}}" --filter "name=hartono-webserver" 2>/dev/null || echo "Not running")"
echo "- Direct API: $FINAL_DIRECT"
echo "- Nginx API: $FINAL_NGINX"

if [ "$FINAL_DIRECT" = "200" ] && [ "$FINAL_NGINX" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Both containers working!${NC}"
    echo -e "${GREEN}✅ WhatsApp API operational${NC}"
    echo -e "${GREEN}✅ Nginx proxy working${NC}"
    
    echo -e "\n${BLUE}📱 Test your QR system:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
elif [ "$FINAL_DIRECT" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ WhatsApp working, Nginx needs attention${NC}"
    echo "Recommendations:"
    echo "- Check nginx configuration"
    echo "- Check nginx error logs: docker logs hartono-webserver"
    
elif [ "$FINAL_NGINX" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ Nginx working, WhatsApp needs attention${NC}"
    echo "Recommendations:"
    echo "- Check WhatsApp container logs: docker logs whatsapp-api-hartono"
    echo "- Restart WhatsApp container: docker restart whatsapp-api-hartono"
    
else
    echo -e "\n${RED}❌ Both services need attention${NC}"
    echo "Recommendations:"
    echo "- Check container logs"
    echo "- Check docker-compose configuration"
    echo "- Restart entire stack: docker-compose restart"
fi

echo -e "\n${BLUE}📋 Monitoring Commands:${NC}"
echo "- Check containers: docker ps"
echo "- Check logs: docker logs -f [container-name]"
echo "- Restart container: docker restart [container-name]"
echo "- Test API: curl https://hartonomotor.xyz/whatsapp-api/app/devices"

echo -e "\n${BLUE}🔧 If problems persist:${NC}"
echo "1. Check docker-compose.yml configuration"
echo "2. Restart entire stack: docker-compose down && docker-compose up -d"
echo "3. Check VPS resources: df -h && free -h"
echo "4. Check network connectivity between containers"
