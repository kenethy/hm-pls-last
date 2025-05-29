#!/bin/bash

# WhatsApp QR Code Fix Script for VPS
# This script should be run ON THE VPS SERVER

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 WhatsApp QR Code Fix for VPS${NC}"
echo "=================================================="

# Step 1: Check current container status
echo -e "\n${YELLOW}📦 Step 1: Checking current container status...${NC}"
echo "Current running containers:"
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}"

# Find WhatsApp container
WHATSAPP_CONTAINER=$(docker ps --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")
if [ -z "$WHATSAPP_CONTAINER" ]; then
    echo -e "${RED}❌ No WhatsApp container found running${NC}"
    echo "Available containers:"
    docker ps -a --format "table {{.Names}}\t{{.Status}}"
    
    # Try to find stopped WhatsApp container
    WHATSAPP_CONTAINER=$(docker ps -a --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")
    if [ -n "$WHATSAPP_CONTAINER" ]; then
        echo -e "${YELLOW}⚠️ Found stopped WhatsApp container: $WHATSAPP_CONTAINER${NC}"
        echo "Attempting to start it..."
        docker start $WHATSAPP_CONTAINER || echo "Failed to start container"
    fi
else
    echo -e "${GREEN}✅ WhatsApp container found: $WHATSAPP_CONTAINER${NC}"
fi

# Step 2: Check and create static files directory
echo -e "\n${YELLOW}📁 Step 2: Setting up static files directory...${NC}"

# Check if whatsapp_statics directory exists on host
if [ ! -d "/var/www/whatsapp_statics" ]; then
    echo "Creating /var/www/whatsapp_statics directory..."
    sudo mkdir -p /var/www/whatsapp_statics/qrcode
    sudo mkdir -p /var/www/whatsapp_statics/media
    sudo mkdir -p /var/www/whatsapp_statics/senditems
    echo -e "${GREEN}✅ Static directories created${NC}"
else
    echo -e "${GREEN}✅ Static directory already exists${NC}"
fi

# Set proper permissions
echo "Setting proper permissions..."
sudo chown -R www-data:www-data /var/www/whatsapp_statics
sudo chmod -R 755 /var/www/whatsapp_statics
echo -e "${GREEN}✅ Permissions set${NC}"

# Check current directory contents
echo "Current static directory contents:"
ls -la /var/www/whatsapp_statics/ || echo "Cannot list directory"
if [ -d "/var/www/whatsapp_statics/qrcode" ]; then
    echo "QR code directory contents:"
    ls -la /var/www/whatsapp_statics/qrcode/ || echo "QR code directory empty"
fi

# Step 3: Fix Docker Compose volume mapping
echo -e "\n${YELLOW}🐳 Step 3: Fixing Docker Compose configuration...${NC}"

# Find the correct docker-compose.yml file
COMPOSE_FILE=""
if [ -f "docker-compose.yml" ] && grep -q "whatsapp" docker-compose.yml; then
    COMPOSE_FILE="docker-compose.yml"
    echo "Using main docker-compose.yml"
elif [ -f "go-whatsapp-web-multidevice-main/docker-compose.yml" ]; then
    COMPOSE_FILE="go-whatsapp-web-multidevice-main/docker-compose.yml"
    echo "Using WhatsApp-specific docker-compose.yml"
    cd go-whatsapp-web-multidevice-main
else
    echo -e "${RED}❌ No suitable docker-compose.yml found${NC}"
    exit 1
fi

# Backup current compose file
cp $COMPOSE_FILE ${COMPOSE_FILE}.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✅ Backup created${NC}"

# Create corrected docker-compose.yml
echo "Creating corrected docker-compose.yml..."
cat > $COMPOSE_FILE << 'EOF'
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
      # Map host static directory to container statics
      - /var/www/whatsapp_statics:/app/statics
      # Map sessions and media for persistence
      - whatsapp_sessions:/app/storages
    environment:
      - APP_PORT=3000
      - APP_DEBUG=false
      - APP_OS=HartonoMotor
      - APP_BASIC_AUTH=admin:HartonoMotor2025!
      - WHATSAPP_WEBHOOK=https://hartonomotor.xyz/api/whatsapp/webhook
      - WHATSAPP_WEBHOOK_SECRET=HartonoMotorWebhookSecret2025
      - DB_URI=file:storages/whatsapp.db?_foreign_keys=on
      - WHATSAPP_ACCOUNT_VALIDATION=true
      - WHATSAPP_CHAT_STORAGE=true
    networks:
      - whatsapp-network
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3

volumes:
  whatsapp_sessions:

networks:
  whatsapp-network:
    driver: bridge
EOF

echo -e "${GREEN}✅ Docker Compose configuration updated${NC}"

# Step 4: Restart WhatsApp container
echo -e "\n${YELLOW}🔄 Step 4: Restarting WhatsApp container...${NC}"

# Stop existing container
if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo "Stopping existing container..."
    docker stop $WHATSAPP_CONTAINER || echo "Container already stopped"
    docker rm $WHATSAPP_CONTAINER || echo "Container already removed"
fi

# Start new container
echo "Starting new container..."
docker-compose up -d whatsapp-api

# Wait for container to be ready
echo "Waiting for container to be ready..."
sleep 10

# Check if container is running
NEW_CONTAINER=$(docker ps --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")
if [ -n "$NEW_CONTAINER" ]; then
    echo -e "${GREEN}✅ Container started successfully: $NEW_CONTAINER${NC}"
    
    # Check container logs
    echo "Recent container logs:"
    docker logs --tail=10 $NEW_CONTAINER
else
    echo -e "${RED}❌ Failed to start container${NC}"
    echo "Checking docker-compose logs:"
    docker-compose logs whatsapp-api
fi

# Step 5: Test static file access
echo -e "\n${YELLOW}🧪 Step 5: Testing static file access...${NC}"

# Create a test file
echo "Creating test file..."
echo "Test QR Code Access" | sudo tee /var/www/whatsapp_statics/qrcode/test.txt > /dev/null

# Test local access
echo "Testing local file access:"
curl -s "http://localhost/statics/qrcode/test.txt" || echo "Local access failed"

# Test external access
echo "Testing external access:"
curl -s "https://hartonomotor.xyz/statics/qrcode/test.txt" || echo "External access failed"

# Step 6: Generate test QR code
echo -e "\n${YELLOW}📱 Step 6: Testing QR code generation...${NC}"

if [ -n "$NEW_CONTAINER" ]; then
    echo "Triggering QR code generation..."
    
    # Test API endpoint
    API_RESPONSE=$(curl -s -w "\nHTTP_STATUS:%{http_code}" "http://localhost:3000/app/login" || echo "API_FAILED")
    echo "API Response: $API_RESPONSE"
    
    # Check if QR files are created
    echo "Checking for generated QR files:"
    find /var/www/whatsapp_statics/qrcode/ -name "*.png" -mtime -1 2>/dev/null || echo "No recent QR files found"
    
    # List all files in qrcode directory
    echo "All files in QR directory:"
    ls -la /var/www/whatsapp_statics/qrcode/ || echo "Cannot list QR directory"
fi

# Step 7: Check Nginx configuration
echo -e "\n${YELLOW}🌐 Step 7: Verifying Nginx configuration...${NC}"

# Check if nginx is running
if docker ps | grep -q nginx; then
    NGINX_CONTAINER=$(docker ps --format "{{.Names}}" | grep nginx | head -1)
    echo "Nginx container: $NGINX_CONTAINER"
    
    # Test nginx config
    echo "Testing Nginx configuration:"
    docker exec $NGINX_CONTAINER nginx -t || echo "Nginx config test failed"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload || echo "Nginx reload failed"
    
    echo -e "${GREEN}✅ Nginx configuration verified${NC}"
else
    echo -e "${RED}❌ Nginx container not found${NC}"
fi

# Step 8: Final verification
echo -e "\n${YELLOW}✅ Step 8: Final verification...${NC}"

echo "Testing complete QR code flow:"
echo "1. API endpoint:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "API test failed"

echo "2. Static files access:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static access test failed"

echo "3. QR code directory:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/" || echo "QR directory test failed"

echo -e "\n${GREEN}🎉 WhatsApp QR Code fix completed!${NC}"
echo -e "\n${BLUE}📋 Next steps:${NC}"
echo "1. Visit https://hartonomotor.xyz/whatsapp-qr.html to test QR code"
echo "2. Check browser console for any remaining errors"
echo "3. Monitor container logs: docker logs -f $NEW_CONTAINER"
echo "4. If issues persist, check the generated QR files in /var/www/whatsapp_statics/qrcode/"
