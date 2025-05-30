#!/bin/bash

# =============================================================================
# Quick Fix for VPS Deployment Issue
# Run this on your VPS to fix the Dockerfile path problem
# =============================================================================

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${GREEN}"
echo "============================================================================="
echo "🚀 Quick Fix for WhatsApp API VPS Deployment"
echo "============================================================================="
echo -e "${NC}"

# Check if we're in the right location
if [[ ! -d "/opt/whatsapp-api-production" ]]; then
    echo -e "${RED}Error: /opt/whatsapp-api-production not found${NC}"
    echo "Please make sure you're running this on the VPS where deployment was attempted."
    exit 1
fi

cd /opt/whatsapp-api-production

echo -e "${BLUE}[INFO]${NC} Fixing Docker Compose configuration..."

# Check if source code exists
if [[ ! -d "go-whatsapp-web-multidevice-main" ]]; then
    echo -e "${RED}Error: Source code directory not found${NC}"
    echo "Please copy the go-whatsapp-web-multidevice-main directory to /opt/whatsapp-api-production/"
    exit 1
fi

# Check if Dockerfile exists in correct location
if [[ ! -f "go-whatsapp-web-multidevice-main/docker/golang.Dockerfile" ]]; then
    echo -e "${RED}Error: Dockerfile not found at expected location${NC}"
    echo "Looking for Dockerfiles..."
    find go-whatsapp-web-multidevice-main -name "*Dockerfile*" -type f
    exit 1
fi

echo -e "${GREEN}✓${NC} Found Dockerfile at: go-whatsapp-web-multidevice-main/docker/golang.Dockerfile"

# Load environment variables
if [[ -f "config/.env" ]]; then
    source config/.env
    echo -e "${GREEN}✓${NC} Loaded environment variables"
else
    echo -e "${RED}Error: config/.env not found${NC}"
    exit 1
fi

# Create corrected Docker Compose file
echo -e "${BLUE}[INFO]${NC} Creating corrected docker-compose.production.yml..."

cat > docker-compose.production.yml <<EOF
version: '3.8'

services:
  whatsapp-api:
    build:
      context: ./go-whatsapp-web-multidevice-main
      dockerfile: docker/golang.Dockerfile
    container_name: whatsapp-api-production
    restart: unless-stopped
    ports:
      - "${WHATSAPP_PORT}:3000"
    environment:
      - WHATSAPP_BASIC_AUTH_USERNAME=${WHATSAPP_BASIC_AUTH_USERNAME}
      - WHATSAPP_BASIC_AUTH_PASSWORD=${WHATSAPP_BASIC_AUTH_PASSWORD}
      - WEBHOOK_SECRET=${WEBHOOK_SECRET}
      - ENVIRONMENT=${ENVIRONMENT}
    volumes:
      - whatsapp_data:/app/data
      - whatsapp_statics:/app/statics
      - ./logs/app:/app/logs
    networks:
      - whatsapp_network
    healthcheck:
      test: ["CMD", "wget", "--quiet", "--tries=1", "--spider", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s
    logging:
      driver: "json-file"
      options:
        max-size: "5m"
        max-file: "2"

volumes:
  whatsapp_data:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: /opt/whatsapp-api-production/data/sessions
  whatsapp_statics:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: /opt/whatsapp-api-production/data/qrcode

networks:
  whatsapp_network:
    driver: bridge
EOF

echo -e "${GREEN}✓${NC} Docker Compose file corrected"

# Stop any existing containers
echo -e "${BLUE}[INFO]${NC} Stopping existing containers..."
docker-compose -f docker-compose.production.yml down 2>/dev/null || true

# Clean up failed builds
echo -e "${BLUE}[INFO]${NC} Cleaning up Docker resources..."
docker system prune -f

# Build with correct configuration
echo -e "${BLUE}[INFO]${NC} Building WhatsApp API (this may take a few minutes)..."
if docker-compose -f docker-compose.production.yml build --no-cache; then
    echo -e "${GREEN}✓${NC} Build completed successfully!"
else
    echo -e "${RED}✗${NC} Build failed. Check the error messages above."
    exit 1
fi

# Start the service
echo -e "${BLUE}[INFO]${NC} Starting WhatsApp API service..."
if docker-compose -f docker-compose.production.yml up -d; then
    echo -e "${GREEN}✓${NC} Service started successfully!"
else
    echo -e "${RED}✗${NC} Failed to start service."
    exit 1
fi

# Wait for startup
echo -e "${BLUE}[INFO]${NC} Waiting for service to initialize..."
sleep 15

# Check container status
if docker ps --format 'table {{.Names}}' | grep -q "whatsapp-api-production"; then
    echo -e "${GREEN}✓${NC} Container is running!"
    
    # Test API
    echo -e "${BLUE}[INFO]${NC} Testing API endpoints..."
    if curl -s -f "http://localhost:${WHATSAPP_PORT}/app/devices" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} API is responding!"
        
        # Test Fresh QR endpoint
        echo -e "${BLUE}[INFO]${NC} Testing Fresh QR endpoint..."
        if response=$(curl -s "http://localhost:${WHATSAPP_PORT}/app/login-fresh" 2>/dev/null); then
            echo -e "${GREEN}✓${NC} Fresh QR endpoint is working!"
            echo "QR Response: $(echo "$response" | jq -r '.message' 2>/dev/null || echo 'QR generated successfully')"
        else
            echo -e "${YELLOW}!${NC} Fresh QR endpoint test failed, but container is running"
        fi
    else
        echo -e "${YELLOW}!${NC} Container is running but API is not responding yet"
        echo "This is normal, the API may need more time to initialize."
    fi
else
    echo -e "${RED}✗${NC} Container failed to start"
    echo "Checking logs..."
    docker logs whatsapp-api-production --tail 20
    exit 1
fi

echo ""
echo -e "${GREEN}"
echo "============================================================================="
echo "🎉 Quick Fix Completed Successfully!"
echo "============================================================================="
echo -e "${NC}"

echo "Your WhatsApp API is now running!"
echo ""
echo "📋 Next Steps:"
echo "1. Test QR generation: curl http://localhost:${WHATSAPP_PORT}/app/login-fresh"
echo "2. Check container status: docker ps"
echo "3. View logs: docker logs whatsapp-api-production -f"
echo "4. Use management scripts:"
echo "   - ./start.sh    (start service)"
echo "   - ./stop.sh     (stop service)"
echo "   - ./restart.sh  (restart service)"
echo "   - ./status.sh   (check status)"
echo "   - ./logs.sh     (view logs)"
echo ""
echo "🔗 API Endpoints:"
echo "- Device Status: http://localhost:${WHATSAPP_PORT}/app/devices"
echo "- Fresh QR Login: http://localhost:${WHATSAPP_PORT}/app/login-fresh"
echo "- Regular QR Login: http://localhost:${WHATSAPP_PORT}/app/login"
echo ""
echo -e "${GREEN}✅ Your WhatsApp API is ready to use!${NC}"
