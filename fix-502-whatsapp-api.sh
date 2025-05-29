#!/bin/bash

# Fix 502 Bad Gateway for WhatsApp API
# This script should be run ON THE VPS SERVER

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing 502 Bad Gateway for WhatsApp API${NC}"
echo "=================================================="

# Step 1: Diagnose the 502 error
echo -e "\n${YELLOW}🔍 Step 1: Diagnosing 502 Bad Gateway...${NC}"

echo "Checking all running containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

echo -e "\nChecking all containers (including stopped):"
docker ps -a --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Find WhatsApp container
WHATSAPP_CONTAINER=$(docker ps -a --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")
if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo -e "\n${YELLOW}Found WhatsApp container: $WHATSAPP_CONTAINER${NC}"
    
    # Check container status
    CONTAINER_STATUS=$(docker ps -a --filter "name=$WHATSAPP_CONTAINER" --format "{{.Status}}")
    echo "Container status: $CONTAINER_STATUS"
    
    if [[ "$CONTAINER_STATUS" == *"Up"* ]]; then
        echo -e "${GREEN}✅ Container is running${NC}"
        
        # Test internal connectivity
        echo "Testing internal API connectivity:"
        docker exec $WHATSAPP_CONTAINER curl -s "http://localhost:3000/app/devices" || echo "Internal API not responding"
        
        # Check container logs
        echo -e "\nRecent container logs:"
        docker logs --tail=10 $WHATSAPP_CONTAINER
        
    else
        echo -e "${RED}❌ Container is not running${NC}"
        echo "Container logs:"
        docker logs --tail=20 $WHATSAPP_CONTAINER
    fi
else
    echo -e "${RED}❌ No WhatsApp container found${NC}"
fi

# Step 2: Check network connectivity
echo -e "\n${YELLOW}🌐 Step 2: Checking network connectivity...${NC}"

# Check if port 3000 is accessible
echo "Checking port 3000 accessibility:"
netstat -tlnp | grep :3000 || echo "Port 3000 not listening"

# Test direct API access
echo "Testing direct API access:"
curl -s -w "HTTP Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct API access failed"

# Check Docker networks
echo -e "\nDocker networks:"
docker network ls

# Step 3: Check Nginx configuration
echo -e "\n${YELLOW}📄 Step 3: Checking Nginx configuration...${NC}"

NGINX_CONTAINER=$(docker ps --format "{{.Names}}" | grep -E "nginx|webserver" | head -1 || echo "")
if [ -n "$NGINX_CONTAINER" ]; then
    echo "Nginx container: $NGINX_CONTAINER"
    
    # Test nginx config
    echo "Testing Nginx configuration:"
    docker exec $NGINX_CONTAINER nginx -t || echo "Nginx config has errors"
    
    # Check nginx error logs
    echo -e "\nNginx error logs:"
    docker exec $NGINX_CONTAINER tail -20 /var/log/nginx/error.log 2>/dev/null || echo "Cannot access error logs"
    
    # Check nginx access logs for 502 errors
    echo -e "\nRecent 502 errors in access logs:"
    docker exec $NGINX_CONTAINER tail -50 /var/log/nginx/access.log | grep " 502 " || echo "No recent 502 errors in access log"
    
else
    echo -e "${RED}❌ Nginx container not found${NC}"
fi

# Step 4: Fix WhatsApp container
echo -e "\n${YELLOW}🔧 Step 4: Fixing WhatsApp container...${NC}"

# Find the correct directory and docker-compose file
if [ -f "docker-compose.yml" ] && grep -q "whatsapp" docker-compose.yml; then
    COMPOSE_DIR="."
    echo "Using main docker-compose.yml"
elif [ -f "go-whatsapp-web-multidevice-main/docker-compose.yml" ]; then
    COMPOSE_DIR="go-whatsapp-web-multidevice-main"
    echo "Using WhatsApp-specific docker-compose.yml"
    cd go-whatsapp-web-multidevice-main
else
    echo -e "${RED}❌ No docker-compose.yml found${NC}"
    exit 1
fi

# Stop existing container if running
if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo "Stopping existing container..."
    docker stop $WHATSAPP_CONTAINER 2>/dev/null || echo "Container already stopped"
    docker rm $WHATSAPP_CONTAINER 2>/dev/null || echo "Container already removed"
fi

# Create/update docker-compose.yml with correct configuration
echo "Creating corrected docker-compose.yml..."
cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  whatsapp-api:
    build:
      context: .
      dockerfile: ./docker/golang.Dockerfile
    container_name: whatsapp-api-hartono
    restart: unless-stopped
    ports:
      - "3000:3000"
    volumes:
      - /var/www/whatsapp_statics:/app/statics
      - whatsapp_sessions:/app/storages
    environment:
      - APP_PORT=3000
      - APP_DEBUG=true
      - APP_OS=HartonoMotor
      - APP_BASIC_AUTH=admin:HartonoMotor2025!
      - WHATSAPP_WEBHOOK=https://hartonomotor.xyz/api/whatsapp/webhook
      - WHATSAPP_WEBHOOK_SECRET=HartonoMotorWebhookSecret2025
      - DB_URI=file:storages/whatsapp.db?_foreign_keys=on
    networks:
      - default
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

volumes:
  whatsapp_sessions:
EOF

echo -e "${GREEN}✅ Docker Compose configuration created${NC}"

# Step 5: Ensure static directory exists
echo -e "\n${YELLOW}📁 Step 5: Setting up static directory...${NC}"
sudo mkdir -p /var/www/whatsapp_statics/qrcode
sudo mkdir -p /var/www/whatsapp_statics/media
sudo mkdir -p /var/www/whatsapp_statics/senditems
sudo chown -R www-data:www-data /var/www/whatsapp_statics
sudo chmod -R 755 /var/www/whatsapp_statics
echo -e "${GREEN}✅ Static directory configured${NC}"

# Step 6: Build and start container
echo -e "\n${YELLOW}🚀 Step 6: Building and starting WhatsApp container...${NC}"

# Build the image
echo "Building WhatsApp API image..."
docker-compose build whatsapp-api

# Start the container
echo "Starting WhatsApp API container..."
docker-compose up -d whatsapp-api

# Wait for container to be ready
echo "Waiting for container to start..."
sleep 15

# Check if container is running
NEW_CONTAINER=$(docker ps --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")
if [ -n "$NEW_CONTAINER" ]; then
    echo -e "${GREEN}✅ Container started: $NEW_CONTAINER${NC}"
    
    # Wait for health check
    echo "Waiting for health check..."
    sleep 30
    
    # Check health status
    HEALTH=$(docker inspect --format='{{.State.Health.Status}}' $NEW_CONTAINER 2>/dev/null || echo "no-healthcheck")
    echo "Health status: $HEALTH"
    
    # Show recent logs
    echo "Recent container logs:"
    docker logs --tail=15 $NEW_CONTAINER
    
else
    echo -e "${RED}❌ Failed to start container${NC}"
    echo "Docker compose logs:"
    docker-compose logs whatsapp-api
    exit 1
fi

# Step 7: Test API connectivity
echo -e "\n${YELLOW}🧪 Step 7: Testing API connectivity...${NC}"

# Test internal API
echo "Testing internal API (from host):"
curl -s -w "HTTP Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Internal API test failed"

# Test API through nginx
echo "Testing API through Nginx:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Nginx proxy test failed"

# Test login endpoint
echo "Testing login endpoint:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Login endpoint test failed"

# Step 8: Restart Nginx if needed
echo -e "\n${YELLOW}🔄 Step 8: Restarting Nginx...${NC}"
if [ -n "$NGINX_CONTAINER" ]; then
    echo "Reloading Nginx configuration..."
    docker exec $NGINX_CONTAINER nginx -s reload || echo "Nginx reload failed"
    
    # Test nginx config again
    docker exec $NGINX_CONTAINER nginx -t || echo "Nginx config still has errors"
    
    echo -e "${GREEN}✅ Nginx reloaded${NC}"
fi

# Step 9: Final verification
echo -e "\n${YELLOW}✅ Step 9: Final verification...${NC}"

echo "Testing complete API flow:"

# Test 1: Direct API
API_DIRECT=$(curl -s -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
if [ "$API_DIRECT" = "200" ]; then
    echo -e "${GREEN}✅ Direct API: Working (200)${NC}"
else
    echo -e "${RED}❌ Direct API: Failed ($API_DIRECT)${NC}"
fi

# Test 2: API through Nginx
API_NGINX=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
if [ "$API_NGINX" = "200" ]; then
    echo -e "${GREEN}✅ API through Nginx: Working (200)${NC}"
else
    echo -e "${RED}❌ API through Nginx: Failed ($API_NGINX)${NC}"
fi

# Test 3: Login endpoint
LOGIN_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
if [ "$LOGIN_TEST" = "200" ]; then
    echo -e "${GREEN}✅ Login endpoint: Working (200)${NC}"
else
    echo -e "${RED}❌ Login endpoint: Failed ($LOGIN_TEST)${NC}"
fi

echo -e "\n${BLUE}🎉 Fix completed!${NC}"
echo -e "\n${BLUE}📋 Next steps:${NC}"
echo "1. Test QR page: https://hartonomotor.xyz/whatsapp-qr.html"
echo "2. Monitor logs: docker logs -f $NEW_CONTAINER"
echo "3. If still 502, check: docker exec $NEW_CONTAINER curl http://localhost:3000/app/devices"

echo -e "\n${BLUE}📊 Monitoring commands:${NC}"
echo "- Container status: docker ps"
echo "- Container logs: docker logs $NEW_CONTAINER"
echo "- API test: curl http://localhost:3000/app/devices"
echo "- Nginx test: curl https://hartonomotor.xyz/whatsapp-api/app/devices"
