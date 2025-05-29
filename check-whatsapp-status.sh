#!/bin/bash

# WhatsApp Integration Status Check Script
# Run this script to check the status of WhatsApp integration

echo "🔍 WhatsApp Integration Status Check"
echo "===================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="hartonomotor.xyz"
API_PORT="3000"
WHATSAPP_DIR="/var/www/whatsapp-api/go-whatsapp-web-multidevice-main"
LARAVEL_DIR="/var/www/hartonomotor.xyz"

echo -e "${BLUE}📋 Checking WhatsApp Integration Status...${NC}"
echo ""

# Check 1: Docker Container Status
echo -e "${YELLOW}1. Docker Container Status:${NC}"
if [ -d "$WHATSAPP_DIR" ]; then
    cd $WHATSAPP_DIR
    if docker-compose ps | grep -q "Up"; then
        echo -e "   ${GREEN}✅ WhatsApp API container is running${NC}"
    else
        echo -e "   ${RED}❌ WhatsApp API container is not running${NC}"
        echo -e "   ${YELLOW}   Try: cd $WHATSAPP_DIR && docker-compose up -d${NC}"
    fi
else
    echo -e "   ${RED}❌ WhatsApp API directory not found${NC}"
fi

# Check 2: API Port Accessibility
echo -e "${YELLOW}2. API Port Accessibility:${NC}"
if curl -s --connect-timeout 5 http://localhost:$API_PORT >/dev/null 2>&1; then
    echo -e "   ${GREEN}✅ API port $API_PORT is accessible${NC}"
else
    echo -e "   ${RED}❌ API port $API_PORT is not accessible${NC}"
fi

# Check 3: API Authentication
echo -e "${YELLOW}3. API Authentication:${NC}"
if curl -s -u admin:HartonoMotor2025! http://localhost:$API_PORT/app/devices >/dev/null 2>&1; then
    echo -e "   ${GREEN}✅ API authentication is working${NC}"
else
    echo -e "   ${RED}❌ API authentication failed${NC}"
fi

# Check 4: Nginx Proxy
echo -e "${YELLOW}4. Nginx Reverse Proxy:${NC}"
if curl -s -k https://$DOMAIN/whatsapp-api/app/devices >/dev/null 2>&1; then
    echo -e "   ${GREEN}✅ Nginx reverse proxy is working${NC}"
else
    echo -e "   ${RED}❌ Nginx reverse proxy is not working${NC}"
fi

# Check 5: Laravel Application
echo -e "${YELLOW}5. Laravel Application:${NC}"
if [ -d "$LARAVEL_DIR" ]; then
    cd $LARAVEL_DIR
    if php artisan about >/dev/null 2>&1; then
        echo -e "   ${GREEN}✅ Laravel application is working${NC}"
    else
        echo -e "   ${RED}❌ Laravel application has issues${NC}"
    fi
else
    echo -e "   ${RED}❌ Laravel directory not found${NC}"
fi

# Check 6: Database Tables
echo -e "${YELLOW}6. WhatsApp Database Tables:${NC}"
if [ -d "$LARAVEL_DIR" ]; then
    cd $LARAVEL_DIR
    if php artisan tinker --execute="echo App\Models\WhatsAppConfig::count() . ' configs, ' . App\Models\FollowUpTemplate::count() . ' templates';" 2>/dev/null; then
        echo -e "   ${GREEN}✅ WhatsApp database tables are accessible${NC}"
    else
        echo -e "   ${RED}❌ WhatsApp database tables are not accessible${NC}"
    fi
fi

# Check 7: SSL Certificate
echo -e "${YELLOW}7. SSL Certificate:${NC}"
if curl -s -I https://$DOMAIN >/dev/null 2>&1; then
    echo -e "   ${GREEN}✅ SSL certificate is working${NC}"
else
    echo -e "   ${RED}❌ SSL certificate has issues${NC}"
fi

# Check 8: Firewall Ports
echo -e "${YELLOW}8. Firewall Configuration:${NC}"
if command -v ufw >/dev/null 2>&1; then
    if sudo ufw status | grep -q "$API_PORT"; then
        echo -e "   ${GREEN}✅ Port $API_PORT is allowed in firewall${NC}"
    else
        echo -e "   ${YELLOW}⚠️ Port $API_PORT not explicitly allowed in firewall${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠️ UFW firewall not found${NC}"
fi

echo ""
echo -e "${BLUE}📊 Summary:${NC}"
echo -e "  Domain: $DOMAIN"
echo -e "  WhatsApp API: https://$DOMAIN/whatsapp-api"
echo -e "  Admin Panel: https://$DOMAIN/admin"
echo -e "  QR Code: https://$DOMAIN/whatsapp-api/app/login"

echo ""
echo -e "${BLUE}🔧 Quick Commands:${NC}"
echo -e "  View API logs: ${YELLOW}cd $WHATSAPP_DIR && docker-compose logs -f${NC}"
echo -e "  Restart API: ${YELLOW}cd $WHATSAPP_DIR && docker-compose restart${NC}"
echo -e "  Check Laravel: ${YELLOW}cd $LARAVEL_DIR && php artisan about${NC}"
echo -e "  View Laravel logs: ${YELLOW}tail -f $LARAVEL_DIR/storage/logs/laravel.log${NC}"

echo ""
echo -e "${GREEN}✅ Status check completed!${NC}"
