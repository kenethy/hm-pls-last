#!/bin/bash

# WhatsApp QR Code Verification Script for VPS
# Run this AFTER the fix script to verify everything works

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 WhatsApp QR Code Verification${NC}"
echo "=================================================="

# Step 1: Check container status
echo -e "\n${YELLOW}📦 Step 1: Verifying container status...${NC}"
WHATSAPP_CONTAINER=$(docker ps --format "{{.Names}}" | grep -i whatsapp | head -1 || echo "")

if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo -e "${GREEN}✅ WhatsApp container running: $WHATSAPP_CONTAINER${NC}"
    
    # Check container health
    HEALTH_STATUS=$(docker inspect --format='{{.State.Health.Status}}' $WHATSAPP_CONTAINER 2>/dev/null || echo "no-healthcheck")
    echo "Health status: $HEALTH_STATUS"
    
    # Check container logs for errors
    echo "Recent container logs:"
    docker logs --tail=5 $WHATSAPP_CONTAINER
else
    echo -e "${RED}❌ No WhatsApp container running${NC}"
    exit 1
fi

# Step 2: Verify volume mounts
echo -e "\n${YELLOW}💾 Step 2: Verifying volume mounts...${NC}"
echo "Container volume mounts:"
docker inspect $WHATSAPP_CONTAINER | grep -A 10 -B 5 "Mounts" || echo "Cannot inspect mounts"

# Check if static directory is properly mounted
echo "Checking static directory mount:"
docker exec $WHATSAPP_CONTAINER ls -la /app/statics/ || echo "Cannot access container statics"

# Step 3: Test static file serving
echo -e "\n${YELLOW}📁 Step 3: Testing static file serving...${NC}"

# Check host directory
echo "Host static directory:"
ls -la /var/www/whatsapp_statics/ || echo "Host directory not accessible"

if [ -d "/var/www/whatsapp_statics/qrcode" ]; then
    echo "QR code directory:"
    ls -la /var/www/whatsapp_statics/qrcode/
else
    echo -e "${RED}❌ QR code directory not found${NC}"
fi

# Test web access to static files
echo "Testing web access to static files:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static directory access failed"

# Step 4: Test QR code generation
echo -e "\n${YELLOW}📱 Step 4: Testing QR code generation...${NC}"

echo "Triggering QR code generation..."
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "API_FAILED")

if [ "$QR_RESPONSE" != "API_FAILED" ]; then
    echo "API Response received"
    
    # Try to extract QR link from response
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4 || echo "")
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link found: $QR_LINK"
        
        # Test QR image access
        echo "Testing QR image access:"
        curl -s -w "HTTP Status: %{http_code}\n" "$QR_LINK" || echo "QR image access failed"
        
        # Check if file exists on host
        QR_FILENAME=$(basename "$QR_LINK")
        if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
            echo -e "${GREEN}✅ QR file exists on host: $QR_FILENAME${NC}"
            ls -la "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
        else
            echo -e "${RED}❌ QR file not found on host${NC}"
        fi
    else
        echo -e "${YELLOW}⚠️ No QR link found in API response${NC}"
        echo "Full API response:"
        echo "$QR_RESPONSE"
    fi
else
    echo -e "${RED}❌ API call failed${NC}"
fi

# Step 5: Test complete flow
echo -e "\n${YELLOW}🔄 Step 5: Testing complete QR flow...${NC}"

# Test the actual webpage
echo "Testing QR webpage:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-qr.html" || echo "QR webpage access failed"

# Test API endpoints
echo "Testing API endpoints:"
curl -s -w "HTTP Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Devices endpoint failed"

# Step 6: Check Nginx logs
echo -e "\n${YELLOW}🌐 Step 6: Checking Nginx logs...${NC}"
NGINX_CONTAINER=$(docker ps --format "{{.Names}}" | grep nginx | head -1 || echo "")

if [ -n "$NGINX_CONTAINER" ]; then
    echo "Recent Nginx access logs:"
    docker logs --tail=10 $NGINX_CONTAINER | grep -E "(statics|whatsapp)" || echo "No relevant Nginx logs"
    
    echo "Recent Nginx error logs:"
    docker exec $NGINX_CONTAINER tail -5 /var/log/nginx/error.log 2>/dev/null || echo "Cannot access error logs"
else
    echo -e "${RED}❌ Nginx container not found${NC}"
fi

# Step 7: Performance test
echo -e "\n${YELLOW}⚡ Step 7: Performance test...${NC}"

echo "Testing API response time:"
time curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" > /dev/null || echo "Performance test failed"

echo "Testing static file response time:"
time curl -s "https://hartonomotor.xyz/statics/" > /dev/null || echo "Static file performance test failed"

# Step 8: Summary
echo -e "\n${BLUE}📋 Step 8: Verification Summary${NC}"
echo "=================================================="

# Check all critical components
ISSUES=0

# Container check
if docker ps | grep -q whatsapp; then
    echo -e "${GREEN}✅ WhatsApp container: Running${NC}"
else
    echo -e "${RED}❌ WhatsApp container: Not running${NC}"
    ((ISSUES++))
fi

# Static directory check
if [ -d "/var/www/whatsapp_statics/qrcode" ]; then
    echo -e "${GREEN}✅ Static directory: Exists${NC}"
else
    echo -e "${RED}❌ Static directory: Missing${NC}"
    ((ISSUES++))
fi

# API check
if curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ API endpoint: Accessible${NC}"
else
    echo -e "${RED}❌ API endpoint: Not accessible${NC}"
    ((ISSUES++))
fi

# Static files check
if curl -s "https://hartonomotor.xyz/statics/" > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Static files: Accessible${NC}"
else
    echo -e "${RED}❌ Static files: Not accessible${NC}"
    ((ISSUES++))
fi

# Nginx check
if docker ps | grep -q nginx; then
    echo -e "${GREEN}✅ Nginx: Running${NC}"
else
    echo -e "${RED}❌ Nginx: Not running${NC}"
    ((ISSUES++))
fi

echo ""
if [ $ISSUES -eq 0 ]; then
    echo -e "${GREEN}🎉 All checks passed! WhatsApp QR code should work now.${NC}"
    echo -e "${BLUE}📱 Test it at: https://hartonomotor.xyz/whatsapp-qr.html${NC}"
else
    echo -e "${RED}⚠️ Found $ISSUES issues. Please review the output above.${NC}"
    echo -e "${YELLOW}💡 Common solutions:${NC}"
    echo "1. Restart containers: docker-compose restart"
    echo "2. Check volume permissions: sudo chown -R www-data:www-data /var/www/whatsapp_statics"
    echo "3. Check Nginx config: docker exec nginx-container nginx -t"
    echo "4. Review container logs: docker logs whatsapp-container"
fi

echo -e "\n${BLUE}📋 Monitoring commands:${NC}"
echo "- Watch container logs: docker logs -f $WHATSAPP_CONTAINER"
echo "- Check static files: ls -la /var/www/whatsapp_statics/qrcode/"
echo "- Test API: curl https://hartonomotor.xyz/whatsapp-api/app/login"
echo "- Test static: curl https://hartonomotor.xyz/statics/"
