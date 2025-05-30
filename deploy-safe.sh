#!/bin/bash

# 🚀 Safe Deployment Script for Hartono Motor
# This script preserves .env configuration during deployment

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🚀 Hartono Motor Safe Deployment Script${NC}"
echo -e "${BLUE}=======================================${NC}"

# Check if we're in the right directory
if [[ ! -f "docker-compose.yml" ]]; then
    echo -e "${RED}❌ docker-compose.yml not found. Please run this script from the project root.${NC}"
    exit 1
fi

# Backup existing .env if it exists
if [[ -f ".env" ]]; then
    echo -e "${YELLOW}📋 Backing up existing .env file...${NC}"
    cp .env .env.backup
    echo -e "${GREEN}✅ .env backed up to .env.backup${NC}"
else
    echo -e "${YELLOW}📋 No existing .env found, will create from .env.example${NC}"
fi

# Create .env from .env.example if it doesn't exist
if [[ ! -f ".env" ]]; then
    echo -e "${BLUE}📝 Creating .env from .env.example...${NC}"
    cp .env.example .env
fi

# Ensure WHATSAPP_MODE is set to full
echo -e "${BLUE}🔧 Ensuring WHATSAPP_MODE=full in .env...${NC}"
if grep -q "WHATSAPP_MODE=" .env; then
    sed -i 's/WHATSAPP_MODE=.*/WHATSAPP_MODE=full/' .env
else
    echo "WHATSAPP_MODE=full" >> .env
fi

# Show current WhatsApp configuration
echo -e "${BLUE}📊 Current WhatsApp Configuration:${NC}"
grep "WHATSAPP_" .env || echo "No WhatsApp config found"

# Generate APP_KEY if not set
if grep -q "APP_KEY=$" .env || grep -q "APP_KEY=base64:YOUR_APP_KEY_HERE" .env; then
    echo -e "${YELLOW}🔑 APP_KEY not set, generating...${NC}"
    # We'll generate this after containers are up
    NEED_APP_KEY=true
else
    NEED_APP_KEY=false
fi

echo -e "${BLUE}🔄 Stopping existing containers...${NC}"
docker-compose down

echo -e "${BLUE}🏗️  Building and starting containers...${NC}"
docker-compose up -d --build

echo -e "${BLUE}⏳ Waiting for containers to start...${NC}"
sleep 15

# Generate APP_KEY if needed
if [[ "$NEED_APP_KEY" == "true" ]]; then
    echo -e "${BLUE}🔑 Generating APP_KEY...${NC}"
    docker exec -it hartono-app php artisan key:generate --force
fi

# Check container status
echo -e "${BLUE}📊 Container Status:${NC}"
docker ps | grep hartono

# Check WhatsApp container specifically
if docker ps | grep -q "hartono-whatsapp-api"; then
    echo -e "${GREEN}✅ WhatsApp container is running!${NC}"
    
    # Wait a bit more for WhatsApp to initialize
    echo -e "${BLUE}⏳ Waiting for WhatsApp service to initialize...${NC}"
    sleep 10
    
    # Check WhatsApp mode
    echo -e "${BLUE}🔍 Checking WhatsApp mode...${NC}"
    docker logs hartono-whatsapp-api | tail -10
    
    # Test health endpoint
    if curl -s http://localhost:3001/health > /dev/null; then
        echo -e "${GREEN}✅ WhatsApp API is responding!${NC}"
    else
        echo -e "${YELLOW}⚠️  WhatsApp API not responding yet, check logs${NC}"
    fi
else
    echo -e "${RED}❌ WhatsApp container failed to start${NC}"
    echo -e "${YELLOW}📋 Checking logs...${NC}"
    docker logs hartono-whatsapp-api
fi

echo -e "${GREEN}🎉 Deployment completed!${NC}"
echo -e "${BLUE}=============================================${NC}"

echo -e "${GREEN}📱 WhatsApp Integration Status:${NC}"
echo -e "   • Configuration: Preserved in .env"
echo -e "   • Mode: FULL (Real WhatsApp Web.js)"
echo -e "   • Access: Filament Admin → WhatsApp Manager"

echo -e "${BLUE}📊 Useful Commands:${NC}"
echo -e "   • Check logs: docker logs -f hartono-whatsapp-api"
echo -e "   • Restart WhatsApp: docker-compose restart whatsapp-api"
echo -e "   • Check .env: cat .env | grep WHATSAPP"

echo -e "${GREEN}✅ Your .env configuration is preserved!${NC}"
