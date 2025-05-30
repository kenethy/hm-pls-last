#!/bin/bash

# =============================================================================
# Fix Dockerfile Path Issue - Quick Fix Script
# =============================================================================

set -euo pipefail

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
PROJECT_ROOT="/opt/whatsapp-api-production"

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if project exists
if [[ ! -d "$PROJECT_ROOT" ]]; then
    log_error "Project directory not found: $PROJECT_ROOT"
    log_info "Please run the deployment script first: ./deploy-whatsapp-production.sh"
    exit 1
fi

echo -e "${GREEN}"
echo "============================================================================="
echo "🔧 Fixing Dockerfile Path Issue"
echo "============================================================================="
echo -e "${NC}"

log_info "Fixing Docker Compose configuration..."

# Fix the Docker Compose file
cd "$PROJECT_ROOT"

# Check if the correct Dockerfile exists
if [[ -f "go-whatsapp-web-multidevice-main/docker/golang.Dockerfile" ]]; then
    log_success "Found correct Dockerfile at: go-whatsapp-web-multidevice-main/docker/golang.Dockerfile"
else
    log_error "Dockerfile not found at expected location"
    log_info "Checking available Dockerfiles..."
    find go-whatsapp-web-multidevice-main -name "*Dockerfile*" -type f || log_warning "No Dockerfiles found"
    exit 1
fi

# Create corrected Docker Compose file
log_info "Creating corrected docker-compose.production.yml..."

# Load environment variables
source config/.env

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
      device: $PROJECT_ROOT/data/sessions
  whatsapp_statics:
    driver: local
    driver_opts:
      type: none
      o: bind
      device: $PROJECT_ROOT/data/qrcode

networks:
  whatsapp_network:
    driver: bridge
EOF

log_success "Docker Compose file corrected"

# Stop existing containers
log_info "Stopping existing containers..."
docker-compose -f docker-compose.production.yml down || true

# Clean up any failed builds
log_info "Cleaning up failed builds..."
docker system prune -f

# Build with corrected configuration
log_info "Building with corrected Dockerfile path..."
docker-compose -f docker-compose.production.yml build --no-cache

if [[ $? -eq 0 ]]; then
    log_success "Build completed successfully!"
    
    # Start the service
    log_info "Starting WhatsApp API service..."
    docker-compose -f docker-compose.production.yml up -d
    
    # Wait for startup
    log_info "Waiting for service to start..."
    sleep 15
    
    # Check if container is running
    if docker ps --format 'table {{.Names}}' | grep -q "whatsapp-api-production"; then
        log_success "Container is running!"
        
        # Test API
        log_info "Testing API endpoints..."
        if curl -s -f "http://localhost:${WHATSAPP_PORT}/app/devices" > /dev/null 2>&1; then
            log_success "API is responding!"
            echo ""
            echo -e "${GREEN}✅ Fix completed successfully!${NC}"
            echo ""
            echo "You can now:"
            echo "1. Test QR generation: curl http://localhost:${WHATSAPP_PORT}/app/login-fresh"
            echo "2. Check status: ./status.sh"
            echo "3. View logs: ./logs.sh"
        else
            log_warning "Container is running but API is not responding yet"
            log_info "Check logs: docker logs whatsapp-api-production"
        fi
    else
        log_error "Container failed to start"
        log_info "Check logs: docker logs whatsapp-api-production"
        exit 1
    fi
else
    log_error "Build failed"
    log_info "Please check the error messages above"
    exit 1
fi

echo ""
echo -e "${GREEN}"
echo "============================================================================="
echo "🎉 Dockerfile Path Fix Completed!"
echo "============================================================================="
echo -e "${NC}"
