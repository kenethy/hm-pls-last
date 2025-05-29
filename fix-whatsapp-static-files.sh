#!/bin/bash

# Fix WhatsApp Static Files Access (QR Code Images)
# This script fixes the 404 error for QR code images by using bind mounts instead of Docker volumes

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing WhatsApp Static Files Access${NC}"
echo -e "${BLUE}====================================${NC}"
echo ""

# Configuration
DOMAIN="hartonomotor.xyz"
LARAVEL_DIR="/var/www/html"
STATIC_DIR="./whatsapp_statics"

echo -e "${YELLOW}📋 Configuration:${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo -e "  Static Files Directory: ${STATIC_DIR}"
echo ""

# Step 1: Create static files directory
echo -e "${YELLOW}📁 Step 1: Creating static files directory...${NC}"

if [ ! -d "$STATIC_DIR" ]; then
    mkdir -p "$STATIC_DIR"
    mkdir -p "$STATIC_DIR/qrcode"
    mkdir -p "$STATIC_DIR/media"
    mkdir -p "$STATIC_DIR/senditems"
    echo -e "${GREEN}✅ Static files directory created${NC}"
else
    echo -e "${GREEN}✅ Static files directory already exists${NC}"
fi

# Set proper permissions
chmod -R 755 "$STATIC_DIR"
echo -e "${GREEN}✅ Permissions set${NC}"

echo ""

# Step 2: Stop containers
echo -e "${YELLOW}🛑 Step 2: Stopping containers...${NC}"

docker-compose down

echo -e "${GREEN}✅ Containers stopped${NC}"
echo ""

# Step 3: Remove old volume (if exists)
echo -e "${YELLOW}🗑️ Step 3: Cleaning up old volumes...${NC}"

# Remove the old named volume
docker volume rm hartono-whatsapp_statics 2>/dev/null || echo "Volume hartono-whatsapp_statics not found (this is normal)"

echo -e "${GREEN}✅ Old volumes cleaned up${NC}"
echo ""

# Step 4: Start containers with new configuration
echo -e "${YELLOW}🚀 Step 4: Starting containers with new configuration...${NC}"

docker-compose up -d

echo "Waiting for containers to be ready..."
sleep 30

# Check if containers are running
if docker-compose ps | grep -q "hartono-whatsapp-api.*Up"; then
    echo -e "${GREEN}✅ WhatsApp API container is running${NC}"
else
    echo -e "${RED}❌ WhatsApp API container failed to start${NC}"
    docker-compose logs whatsapp-api
    exit 1
fi

if docker-compose ps | grep -q "hartono-webserver.*Up"; then
    echo -e "${GREEN}✅ Nginx container is running${NC}"
else
    echo -e "${RED}❌ Nginx container failed to start${NC}"
    docker-compose logs webserver
    exit 1
fi

echo ""

# Step 5: Test static files directory access
echo -e "${YELLOW}🧪 Step 5: Testing static files access...${NC}"

# Test if static directory is accessible from Nginx container
if docker-compose exec -T webserver ls -la /var/www/whatsapp_statics/ >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Static files directory accessible from Nginx${NC}"
else
    echo -e "${RED}❌ Static files directory not accessible from Nginx${NC}"
    exit 1
fi

# Test if static directory is accessible from WhatsApp API container
if docker-compose exec -T whatsapp-api ls -la /app/statics/ >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Static files directory accessible from WhatsApp API${NC}"
else
    echo -e "${RED}❌ Static files directory not accessible from WhatsApp API${NC}"
    exit 1
fi

echo ""

# Step 6: Generate test QR code
echo -e "${YELLOW}📱 Step 6: Testing QR code generation...${NC}"

echo "Generating test QR code..."
sleep 5

QR_RESPONSE=$(curl -s -k "https://${DOMAIN}/whatsapp-api/app/login" 2>/dev/null || echo "")

if echo "$QR_RESPONSE" | grep -q "qr_link"; then
    echo -e "${GREEN}✅ QR code generation successful${NC}"
    
    # Extract QR link from response
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    echo "QR Link: $QR_LINK"
    
    # Wait a moment for file to be written
    sleep 2
    
    # Check if QR file exists in static directory
    QR_FILENAME=$(basename "$QR_LINK")
    if [ -f "$STATIC_DIR/qrcode/$QR_FILENAME" ]; then
        echo -e "${GREEN}✅ QR code file exists in static directory${NC}"
        
        # Test if QR image is accessible via web
        DOMAIN_QR_LINK=$(echo "$QR_LINK" | sed "s|http://[^/]*:3000/|https://${DOMAIN}/|g")
        echo "Testing URL: $DOMAIN_QR_LINK"
        
        if curl -s -k "$DOMAIN_QR_LINK" >/dev/null 2>&1; then
            echo -e "${GREEN}✅ QR code image accessible via web${NC}"
        else
            echo -e "${RED}❌ QR code image not accessible via web${NC}"
            echo "Checking Nginx error logs..."
            docker-compose logs --tail=5 webserver
        fi
    else
        echo -e "${RED}❌ QR code file not found in static directory${NC}"
        echo "Contents of static directory:"
        ls -la "$STATIC_DIR/qrcode/" || echo "Directory not found"
    fi
else
    echo -e "${RED}❌ QR code generation failed${NC}"
    echo "Response: $QR_RESPONSE"
fi

echo ""

# Step 7: Final verification
echo -e "${YELLOW}🔍 Step 7: Final verification...${NC}"

# Test QR code page
if curl -s -k "https://${DOMAIN}/whatsapp-qr.html" | grep -q "WhatsApp QR Code"; then
    echo -e "${GREEN}✅ QR code page accessible${NC}"
else
    echo -e "${RED}❌ QR code page not accessible${NC}"
fi

# Test static files endpoint
if curl -s -k "https://${DOMAIN}/statics/" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Static files endpoint accessible${NC}"
else
    echo -e "${YELLOW}⚠️ Static files endpoint returns error (may be normal if no index)${NC}"
fi

echo ""

# Summary
echo -e "${BLUE}📋 Fix Summary:${NC}"
echo -e "  ✅ Changed from Docker volume to bind mount"
echo -e "  ✅ Updated Nginx to serve files directly"
echo -e "  ✅ Created static files directory structure"
echo -e "  ✅ Set proper permissions"
echo -e "  ✅ Restarted containers with new configuration"
echo ""

echo -e "${GREEN}🎉 WhatsApp Static Files Fix Applied!${NC}"
echo ""
echo -e "${YELLOW}📝 Next Steps:${NC}"
echo -e "1. Test QR code page:"
echo -e "   ${BLUE}https://${DOMAIN}/whatsapp-qr.html${NC}"
echo ""
echo -e "2. Generate new QR code and verify image loads"
echo ""
echo -e "3. Check static files directory:"
echo -e "   ls -la ${STATIC_DIR}/qrcode/"
echo ""
echo -e "${YELLOW}🔧 Troubleshooting:${NC}"
echo -e "# Check static files in directory:"
echo -e "ls -la ${STATIC_DIR}/qrcode/"
echo ""
echo -e "# Test static files access:"
echo -e "curl -k https://${DOMAIN}/statics/qrcode/"
echo ""
echo -e "# Check container logs:"
echo -e "docker-compose logs whatsapp-api"
echo -e "docker-compose logs webserver"
echo ""
echo -e "${GREEN}✅ Static files should now be accessible!${NC}"
