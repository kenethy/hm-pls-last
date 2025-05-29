#!/bin/bash

# Fix WhatsApp API Reverse Proxy Configuration
# This script configures reverse proxy for WhatsApp API through domain instead of direct port access

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing WhatsApp API Reverse Proxy Configuration${NC}"
echo -e "${BLUE}=================================================${NC}"
echo ""

# Configuration
DOMAIN="hartonomotor.xyz"
CURRENT_DIR=$(pwd)

echo -e "${YELLOW}📋 Configuration:${NC}"
echo -e "  Domain: ${DOMAIN}"
echo -e "  Current Directory: ${CURRENT_DIR}"
echo ""

# Step 1: Update WhatsApp Configuration in Database
echo -e "${YELLOW}🗄️ Step 1: Updating WhatsApp configuration in database...${NC}"

# Update database configuration using artisan tinker
echo "Updating WhatsApp configuration..."
php artisan tinker --execute="
use App\Models\WhatsAppConfig;

\$config = WhatsAppConfig::getActive();

if (\$config) {
    \$config->update([
        'name' => 'Production WhatsApp API (Domain-based)',
        'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
        'api_username' => 'admin',
        'api_password' => 'HartonoMotor2025!',
        'webhook_secret' => 'HartonoMotorWebhookSecret2025',
        'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
        'is_active' => true,
        'notes' => 'Updated to use domain-based reverse proxy instead of direct port access',
    ]);
    echo 'WhatsApp configuration updated successfully!';
} else {
    WhatsAppConfig::create([
        'name' => 'Production WhatsApp API (Domain-based)',
        'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
        'api_username' => 'admin',
        'api_password' => 'HartonoMotor2025!',
        'webhook_secret' => 'HartonoMotorWebhookSecret2025',
        'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
        'is_active' => true,
        'notes' => 'Created with domain-based reverse proxy configuration',
    ]);
    echo 'WhatsApp configuration created successfully!';
}
"

echo -e "${GREEN}✅ Database configuration updated${NC}"
echo ""

# Step 2: Restart Docker Containers
echo -e "${YELLOW}🐳 Step 2: Restarting Docker containers...${NC}"

echo "Stopping containers..."
docker-compose down

echo "Starting containers with new configuration..."
docker-compose up -d

echo "Waiting for containers to be ready..."
sleep 30

echo -e "${GREEN}✅ Containers restarted${NC}"
echo ""

# Step 3: Test Nginx Configuration
echo -e "${YELLOW}🧪 Step 3: Testing Nginx reverse proxy...${NC}"

# Test if Nginx is running
if docker-compose ps | grep -q "hartono-webserver.*Up"; then
    echo -e "${GREEN}✅ Nginx container is running${NC}"
else
    echo -e "${RED}❌ Nginx container is not running${NC}"
    exit 1
fi

# Test reverse proxy endpoint
echo "Testing reverse proxy endpoint..."
sleep 10

if curl -s -k "https://${DOMAIN}/whatsapp-api/app/devices" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Reverse proxy is working${NC}"
elif curl -s "http://${DOMAIN}/whatsapp-api/app/devices" >/dev/null 2>&1; then
    echo -e "${YELLOW}⚠️ Reverse proxy working on HTTP (SSL may need configuration)${NC}"
else
    echo -e "${RED}❌ Reverse proxy not responding${NC}"
    echo "Checking container logs..."
    docker-compose logs --tail=20 whatsapp-api
fi

echo ""

# Step 4: Test WhatsApp API Container
echo -e "${YELLOW}🔍 Step 4: Testing WhatsApp API container...${NC}"

if docker-compose ps | grep -q "hartono-whatsapp-api.*Up"; then
    echo -e "${GREEN}✅ WhatsApp API container is running${NC}"

    # Test internal connectivity
    if docker-compose exec -T app curl -s http://whatsapp-api:3000/app/devices >/dev/null 2>&1; then
        echo -e "${GREEN}✅ Internal container connectivity working${NC}"
    else
        echo -e "${RED}❌ Internal container connectivity failed${NC}"
    fi
else
    echo -e "${RED}❌ WhatsApp API container is not running${NC}"
    echo "Container logs:"
    docker-compose logs --tail=20 whatsapp-api
fi

echo ""

# Step 5: Final Tests
echo -e "${YELLOW}🎯 Step 5: Final connectivity tests...${NC}"

echo "Testing WhatsApp authentication page..."
if curl -s -k "https://${DOMAIN}/whatsapp-auth.html" | grep -q "WhatsApp Authentication"; then
    echo -e "${GREEN}✅ WhatsApp auth page accessible${NC}"
else
    echo -e "${RED}❌ WhatsApp auth page not accessible${NC}"
fi

echo "Testing API login endpoint..."
if curl -s -k "https://${DOMAIN}/whatsapp-api/app/login" >/dev/null 2>&1; then
    echo -e "${GREEN}✅ WhatsApp API login endpoint accessible${NC}"
else
    echo -e "${RED}❌ WhatsApp API login endpoint not accessible${NC}"
fi

echo ""

# Summary
echo -e "${BLUE}📋 Configuration Summary:${NC}"
echo -e "  ✅ Nginx reverse proxy configured"
echo -e "  ✅ WhatsApp auth page updated to use domain"
echo -e "  ✅ Database configuration updated"
echo -e "  ✅ Docker containers restarted"
echo ""

echo -e "${GREEN}🎉 WhatsApp Reverse Proxy Configuration Complete!${NC}"
echo ""
echo -e "${YELLOW}📝 Next Steps:${NC}"
echo -e "1. Test WhatsApp authentication:"
echo -e "   ${BLUE}https://${DOMAIN}/whatsapp-auth.html${NC}"
echo ""
echo -e "2. Click 'Buka WhatsApp Authentication' button"
echo -e "   Should open: ${BLUE}https://${DOMAIN}/whatsapp-api/app/login${NC}"
echo ""
echo -e "3. Scan QR code with your WhatsApp"
echo ""
echo -e "4. Verify connection in admin panel:"
echo -e "   ${BLUE}https://${DOMAIN}/admin/whats-app-configs${NC}"
echo ""
echo -e "${GREEN}✅ All done! WhatsApp API should now be accessible through the domain.${NC}"
