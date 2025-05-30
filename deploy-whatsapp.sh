#!/bin/bash

# 🚀 Hartono Motor WhatsApp Deployment Script
# Usage: ./deploy-whatsapp.sh [mock|full]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default mode
MODE=${1:-mock}

echo -e "${BLUE}🚀 Hartono Motor WhatsApp Deployment Script${NC}"
echo -e "${BLUE}=============================================${NC}"

# Validate mode
if [[ "$MODE" != "mock" && "$MODE" != "full" ]]; then
    echo -e "${RED}❌ Invalid mode: $MODE${NC}"
    echo -e "${YELLOW}Usage: ./deploy-whatsapp.sh [mock|full]${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Deployment Mode: $MODE${NC}"

# Check if we're in the right directory
if [[ ! -f "docker-compose.yml" ]]; then
    echo -e "${RED}❌ docker-compose.yml not found. Please run this script from the project root.${NC}"
    exit 1
fi

# Check if whatsapp-server directory exists
if [[ ! -d "whatsapp-server" ]]; then
    echo -e "${RED}❌ whatsapp-server directory not found. Please ensure all files are uploaded.${NC}"
    exit 1
fi

echo -e "${BLUE}🔄 Stopping existing containers...${NC}"
docker-compose down

echo -e "${BLUE}🏗️  Building WhatsApp container...${NC}"
if [[ "$MODE" == "full" ]]; then
    echo -e "${YELLOW}⚠️  Building in FULL mode - requires real WhatsApp connection${NC}"
    WHATSAPP_MODE=full docker-compose up -d --build whatsapp-api
else
    echo -e "${GREEN}✅ Building in MOCK mode - perfect for testing${NC}"
    docker-compose up -d --build whatsapp-api
fi

echo -e "${BLUE}⏳ Waiting for container to start...${NC}"
sleep 10

# Check if container is running
if docker ps | grep -q "hartono-whatsapp-api"; then
    echo -e "${GREEN}✅ WhatsApp container is running!${NC}"
else
    echo -e "${RED}❌ WhatsApp container failed to start${NC}"
    echo -e "${YELLOW}📋 Checking logs...${NC}"
    docker logs hartono-whatsapp-api
    exit 1
fi

# Test health endpoint
echo -e "${BLUE}🔍 Testing health endpoint...${NC}"
if curl -s http://localhost:3001/health > /dev/null; then
    echo -e "${GREEN}✅ Health endpoint is responding!${NC}"
else
    echo -e "${RED}❌ Health endpoint is not responding${NC}"
    echo -e "${YELLOW}📋 Checking logs...${NC}"
    docker logs hartono-whatsapp-api
    exit 1
fi

echo -e "${GREEN}🎉 Deployment successful!${NC}"
echo -e "${BLUE}=============================================${NC}"

if [[ "$MODE" == "mock" ]]; then
    echo -e "${GREEN}📱 Mock Mode Active:${NC}"
    echo -e "   • Perfect for testing Laravel integration"
    echo -e "   • All API endpoints are functional"
    echo -e "   • No real WhatsApp connection required"
    echo -e "   • Access Filament Admin → WhatsApp Manager to test"
    echo ""
    echo -e "${YELLOW}🔄 To switch to Full Mode later:${NC}"
    echo -e "   ./deploy-whatsapp.sh full"
else
    echo -e "${GREEN}🚀 Full Mode Active:${NC}"
    echo -e "   • Real WhatsApp Web.js integration"
    echo -e "   • Scan QR code to connect"
    echo -e "   • Access Filament Admin → WhatsApp Manager"
    echo -e "   • Monitor logs: docker logs -f hartono-whatsapp-api"
fi

echo ""
echo -e "${BLUE}📊 Useful Commands:${NC}"
echo -e "   • Check status: docker ps | grep whatsapp"
echo -e "   • View logs: docker logs -f hartono-whatsapp-api"
echo -e "   • Test health: curl http://localhost:3001/health"
echo -e "   • Restart: docker restart hartono-whatsapp-api"

echo ""
echo -e "${GREEN}✅ Ready to test WhatsApp integration!${NC}"
