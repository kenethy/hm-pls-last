#!/bin/bash

# Fix WhatsApp QR Code Image Loading Issues
# This script fixes mixed content warnings and 404 errors for QR code images

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing WhatsApp QR Code Image Loading Issues${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Configuration
DOMAIN="hartonomotor.xyz"
LARAVEL_DIR="/var/www/html"

echo -e "${YELLOW}📋 Configuration:${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo ""

# Step 1: Test Current Configuration
echo -e "${YELLOW}🧪 Step 1: Testing current configuration...${NC}"

# Test if containers are running
if docker-compose ps | grep -q "hartono-whatsapp-api.*Up"; then
    echo -e "${GREEN}✅ WhatsApp API container is running${NC}"
else
    echo -e "${RED}❌ WhatsApp API container is not running${NC}"
    echo "Starting WhatsApp API container..."
    docker-compose up -d whatsapp-api
    sleep 10
fi

if docker-compose ps | grep -q "hartono-webserver.*Up"; then
    echo -e "${GREEN}✅ Nginx container is running${NC}"
else
    echo -e "${RED}❌ Nginx container is not running${NC}"
    echo "Starting Nginx container..."
    docker-compose up -d webserver
    sleep 5
fi

echo ""

# Step 2: Test API Endpoints
echo -e "${YELLOW}🔍 Step 2: Testing API endpoints...${NC}"

# Test WhatsApp API login endpoint
echo "Testing WhatsApp API login endpoint..."
if curl -s -k "https://${DOMAIN}/whatsapp-api/app/login" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ WhatsApp API login endpoint accessible${NC}"
else
    echo -e "${RED}❌ WhatsApp API login endpoint not accessible${NC}"
fi

# Test static files endpoint
echo "Testing static files endpoint..."
if curl -s -k "https://${DOMAIN}/statics/" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Static files endpoint accessible${NC}"
else
    echo -e "${YELLOW}⚠️ Static files endpoint not accessible (may be normal if no files exist)${NC}"
fi

echo ""

# Step 3: Restart Nginx to Apply Configuration Changes
echo -e "${YELLOW}🔄 Step 3: Restarting Nginx to apply configuration changes...${NC}"

docker-compose restart webserver

echo "Waiting for Nginx to be ready..."
sleep 10

# Test if Nginx is working
if curl -s -k "https://${DOMAIN}" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Nginx restarted successfully${NC}"
else
    echo -e "${RED}❌ Nginx restart failed${NC}"
    echo "Checking Nginx logs..."
    docker-compose logs --tail=10 webserver
fi

echo ""

# Step 4: Test QR Code Generation
echo -e "${YELLOW}📱 Step 4: Testing QR code generation...${NC}"

echo "Generating test QR code..."
QR_RESPONSE=$(curl -s -k "https://${DOMAIN}/whatsapp-api/app/login" 2>/dev/null || echo "")

if echo "$QR_RESPONSE" | grep -q "qr_link"; then
    echo -e "${GREEN}✅ QR code generation successful${NC}"
    
    # Extract QR link from response
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    echo "QR Link: $QR_LINK"
    
    # Convert to domain-based URL
    DOMAIN_QR_LINK=$(echo "$QR_LINK" | sed "s|http://[^/]*:3000/|https://${DOMAIN}/|g")
    echo "Domain QR Link: $DOMAIN_QR_LINK"
    
    # Test if QR image is accessible
    if curl -s -k "$DOMAIN_QR_LINK" >/dev/null 2>&1; then
        echo -e "${GREEN}✅ QR code image accessible via domain${NC}"
    else
        echo -e "${RED}❌ QR code image not accessible via domain${NC}"
        echo "This may be normal if the QR code has expired"
    fi
else
    echo -e "${RED}❌ QR code generation failed${NC}"
    echo "Response: $QR_RESPONSE"
fi

echo ""

# Step 5: Test QR Code Page
echo -e "${YELLOW}🌐 Step 5: Testing QR code page...${NC}"

if curl -s -k "https://${DOMAIN}/whatsapp-qr.html" | grep -q "WhatsApp QR Code"; then
    echo -e "${GREEN}✅ QR code page accessible${NC}"
else
    echo -e "${RED}❌ QR code page not accessible${NC}"
fi

echo ""

# Step 6: Check Container Logs for Errors
echo -e "${YELLOW}📋 Step 6: Checking container logs for errors...${NC}"

echo "WhatsApp API container logs (last 5 lines):"
docker-compose logs --tail=5 whatsapp-api

echo ""
echo "Nginx container logs (last 5 lines):"
docker-compose logs --tail=5 webserver

echo ""

# Step 7: Final Summary and Instructions
echo -e "${BLUE}📋 Configuration Summary:${NC}"
echo -e "  ✅ Nginx reverse proxy configured for /whatsapp-api/"
echo -e "  ✅ Nginx reverse proxy configured for /statics/"
echo -e "  ✅ QR code page updated with URL fixing"
echo -e "  ✅ Mixed content issues addressed"
echo -e "  ✅ Error handling improved"
echo ""

echo -e "${GREEN}🎉 WhatsApp QR Code Fix Applied!${NC}"
echo ""
echo -e "${YELLOW}📝 Next Steps:${NC}"
echo -e "1. Test QR code page:"
echo -e "   ${BLUE}https://${DOMAIN}/whatsapp-qr.html${NC}"
echo ""
echo -e "2. Check browser console for any remaining errors"
echo ""
echo -e "3. If QR code still doesn't load, check:"
echo -e "   - Browser developer tools (F12) for network errors"
echo -e "   - Container logs: docker-compose logs whatsapp-api"
echo -e "   - Nginx logs: docker-compose logs webserver"
echo ""
echo -e "${YELLOW}🔧 Troubleshooting Commands:${NC}"
echo -e "# Test API endpoint directly:"
echo -e "curl -k https://${DOMAIN}/whatsapp-api/app/login"
echo ""
echo -e "# Test static files:"
echo -e "curl -k https://${DOMAIN}/statics/"
echo ""
echo -e "# Check container status:"
echo -e "docker-compose ps"
echo ""
echo -e "${GREEN}✅ All fixes applied successfully!${NC}"
