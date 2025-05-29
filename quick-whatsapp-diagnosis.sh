#!/bin/bash

# Quick WhatsApp API Diagnosis
# Run this first to see current status

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}⚡ Quick WhatsApp API Diagnosis${NC}"
echo "=================================================="

# Check 1: Container Status
echo -e "\n${YELLOW}📦 Container Status:${NC}"
echo "Running containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" | grep -E "(whatsapp|nginx|webserver)" || echo "No WhatsApp/Nginx containers running"

echo -e "\nAll containers (including stopped):"
docker ps -a --format "table {{.Names}}\t{{.Status}}" | grep -E "(whatsapp|nginx|webserver)" || echo "No WhatsApp/Nginx containers found"

# Check 2: Port Status
echo -e "\n${YELLOW}🔌 Port Status:${NC}"
echo "Port 3000 (WhatsApp API):"
netstat -tlnp | grep :3000 || echo "Port 3000 not listening"

echo "Port 80/443 (Nginx):"
netstat -tlnp | grep -E ":80|:443" || echo "Nginx ports not listening"

# Check 3: API Connectivity
echo -e "\n${YELLOW}🔗 API Connectivity:${NC}"

echo "Testing localhost:3000 (direct):"
curl -s -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" 2>/dev/null || echo "❌ Direct API failed"

echo "Testing through Nginx:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null || echo "❌ Nginx proxy failed"

# Check 4: Container Logs (if exists)
WHATSAPP_CONTAINER=$(docker ps -a --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")
if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo -e "\n${YELLOW}📋 WhatsApp Container Logs (last 5 lines):${NC}"
    docker logs --tail=5 $WHATSAPP_CONTAINER 2>/dev/null || echo "Cannot access logs"
fi

# Check 5: Nginx Logs (if exists)
NGINX_CONTAINER=$(docker ps --format "{{.Names}}" | grep -E "nginx|webserver" | head -1 || echo "")
if [ -n "$NGINX_CONTAINER" ]; then
    echo -e "\n${YELLOW}🌐 Nginx Error Logs (last 3 lines):${NC}"
    docker exec $NGINX_CONTAINER tail -3 /var/log/nginx/error.log 2>/dev/null || echo "Cannot access Nginx logs"
fi

# Check 6: Docker Compose Files
echo -e "\n${YELLOW}📄 Docker Compose Files:${NC}"
if [ -f "docker-compose.yml" ]; then
    echo "✅ Main docker-compose.yml exists"
    if grep -q "whatsapp" docker-compose.yml; then
        echo "✅ WhatsApp service found in main compose"
    else
        echo "❌ No WhatsApp service in main compose"
    fi
else
    echo "❌ Main docker-compose.yml not found"
fi

if [ -f "go-whatsapp-web-multidevice-main/docker-compose.yml" ]; then
    echo "✅ WhatsApp docker-compose.yml exists"
else
    echo "❌ WhatsApp docker-compose.yml not found"
fi

# Check 7: Static Directory
echo -e "\n${YELLOW}📁 Static Directory:${NC}"
if [ -d "/var/www/whatsapp_statics" ]; then
    echo "✅ Static directory exists"
    ls -la /var/www/whatsapp_statics/ | head -5
else
    echo "❌ Static directory not found"
fi

# Summary
echo -e "\n${BLUE}📊 Quick Summary:${NC}"
echo "=================================================="

# Container check
if docker ps | grep -q whatsapp; then
    echo -e "${GREEN}✅ WhatsApp container: Running${NC}"
else
    echo -e "${RED}❌ WhatsApp container: Not running${NC}"
fi

# API check
if curl -s "http://localhost:3000/app/devices" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Direct API: Working${NC}"
else
    echo -e "${RED}❌ Direct API: Not working${NC}"
fi

# Nginx proxy check
if curl -s "https://hartonomotor.xyz/whatsapp-api/app/devices" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Nginx proxy: Working${NC}"
else
    echo -e "${RED}❌ Nginx proxy: Not working (502 error)${NC}"
fi

echo -e "\n${BLUE}💡 Next Steps:${NC}"
echo "If you see issues above, run: ./fix-502-whatsapp-api.sh"
echo "For detailed verification after fix: ./verify-whatsapp-qr-fix.sh"
