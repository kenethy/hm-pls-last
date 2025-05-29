#!/bin/bash

# WhatsApp QR Code Issue Diagnosis Script
# For VPS Environment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 WhatsApp QR Code Issue Diagnosis${NC}"
echo "=================================================="

# Step 1: Check Docker containers
echo -e "\n${YELLOW}📦 Step 1: Checking Docker containers...${NC}"
echo "Current running containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo -e "\n${YELLOW}🔍 WhatsApp API container details:${NC}"
if docker ps | grep -q "whatsapp"; then
    WHATSAPP_CONTAINER=$(docker ps --format "{{.Names}}" | grep whatsapp | head -1)
    echo "Container name: $WHATSAPP_CONTAINER"
    echo "Container status:"
    docker ps --filter "name=$WHATSAPP_CONTAINER" --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"
    
    echo -e "\n${YELLOW}📋 Container environment variables:${NC}"
    docker exec $WHATSAPP_CONTAINER env | grep -E "(APP_|WHATSAPP_|DB_)" || echo "No WhatsApp env vars found"
    
    echo -e "\n${YELLOW}📁 Container file structure:${NC}"
    echo "Checking /app directory:"
    docker exec $WHATSAPP_CONTAINER ls -la /app/ || echo "Cannot access /app directory"
    
    echo "Checking statics directory:"
    docker exec $WHATSAPP_CONTAINER ls -la /app/statics/ || echo "Cannot access /app/statics directory"
    
    echo "Checking qrcode directory:"
    docker exec $WHATSAPP_CONTAINER ls -la /app/statics/qrcode/ || echo "Cannot access /app/statics/qrcode directory"
    
else
    echo -e "${RED}❌ No WhatsApp container found running${NC}"
fi

# Step 2: Check Docker Compose configuration
echo -e "\n${YELLOW}📄 Step 2: Checking Docker Compose configuration...${NC}"
if [ -f "docker-compose.yml" ]; then
    echo "Main docker-compose.yml found:"
    grep -A 20 "whatsapp" docker-compose.yml || echo "No whatsapp service found in main docker-compose.yml"
fi

if [ -f "go-whatsapp-web-multidevice-main/docker-compose.yml" ]; then
    echo -e "\nWhatsApp docker-compose.yml found:"
    cat go-whatsapp-web-multidevice-main/docker-compose.yml
fi

# Step 3: Check volume mounts
echo -e "\n${YELLOW}💾 Step 3: Checking volume mounts...${NC}"
if docker ps | grep -q "whatsapp"; then
    echo "Volume mounts for WhatsApp container:"
    docker inspect $WHATSAPP_CONTAINER | grep -A 10 -B 5 "Mounts" || echo "Cannot inspect container mounts"
fi

# Step 4: Check host static files directory
echo -e "\n${YELLOW}📁 Step 4: Checking host static files...${NC}"
if [ -d "whatsapp_statics" ]; then
    echo "Host whatsapp_statics directory:"
    ls -la whatsapp_statics/
    if [ -d "whatsapp_statics/qrcode" ]; then
        echo "QR code files:"
        ls -la whatsapp_statics/qrcode/
    else
        echo "No qrcode directory found in whatsapp_statics"
    fi
else
    echo "No whatsapp_statics directory found on host"
fi

# Step 5: Check Nginx configuration
echo -e "\n${YELLOW}🌐 Step 5: Checking Nginx configuration...${NC}"
if [ -f "docker/nginx/conf.d/app.conf" ]; then
    echo "Nginx configuration for static files:"
    grep -A 15 -B 5 "/statics/" docker/nginx/conf.d/app.conf || echo "No /statics/ configuration found"
fi

# Step 6: Test API endpoints
echo -e "\n${YELLOW}🔗 Step 6: Testing API endpoints...${NC}"
echo "Testing WhatsApp API login endpoint:"
curl -s -w "\nHTTP Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Failed to connect to API"

echo -e "\nTesting direct static file access:"
curl -s -w "\nHTTP Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Failed to access statics directory"

# Step 7: Check container logs
echo -e "\n${YELLOW}📋 Step 7: Checking container logs...${NC}"
if docker ps | grep -q "whatsapp"; then
    echo "Recent WhatsApp container logs:"
    docker logs --tail=20 $WHATSAPP_CONTAINER || echo "Cannot access container logs"
fi

echo -e "\nNginx container logs:"
if docker ps | grep -q "nginx\|webserver"; then
    NGINX_CONTAINER=$(docker ps --format "{{.Names}}" | grep -E "nginx|webserver" | head -1)
    docker logs --tail=10 $NGINX_CONTAINER || echo "Cannot access nginx logs"
fi

# Step 8: Generate test QR code
echo -e "\n${YELLOW}🧪 Step 8: Testing QR code generation...${NC}"
if docker ps | grep -q "whatsapp"; then
    echo "Attempting to generate QR code via API:"
    curl -s -X POST "https://hartonomotor.xyz/whatsapp-api/app/login" \
         -H "Content-Type: application/json" \
         -w "\nHTTP Status: %{http_code}\n" || echo "Failed to trigger QR generation"
fi

echo -e "\n${GREEN}✅ Diagnosis complete!${NC}"
echo -e "\n${BLUE}📋 Summary of findings will help identify the root cause.${NC}"
