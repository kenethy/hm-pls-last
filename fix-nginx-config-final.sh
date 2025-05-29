#!/bin/bash

# Fix Nginx Configuration - Remove old whatsapp-api references
# Final fix for static files and clean configuration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Final Nginx Configuration Fix${NC}"
echo "=================================================="

# Step 1: Check and fix nginx configuration
echo -e "\n${YELLOW}📄 Step 1: Fixing Nginx configuration...${NC}"

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

echo "Current problematic configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -n "whatsapp-api" || echo "No whatsapp-api references found"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Fix all references to whatsapp-api to use IP address
echo "Replacing all whatsapp-api references with IP address..."
docker exec $NGINX_CONTAINER sed -i "s|whatsapp-api|$WHATSAPP_IP|g" /etc/nginx/conf.d/app.conf

# Show what was changed
echo "Updated configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 5 -B 2 "$WHATSAPP_IP"

# Step 2: Test nginx configuration
echo -e "\n${YELLOW}🧪 Step 2: Testing Nginx configuration...${NC}"

echo "Testing Nginx configuration:"
docker exec $NGINX_CONTAINER nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Nginx configuration still has errors${NC}"
    echo "Showing current config around line 32:"
    docker exec $NGINX_CONTAINER sed -n '25,40p' /etc/nginx/conf.d/app.conf
    
    echo "Restoring backup..."
    BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
    docker exec $NGINX_CONTAINER nginx -s reload
    exit 1
fi

# Step 3: Fix file permissions for static files
echo -e "\n${YELLOW}🔐 Step 3: Fixing file permissions...${NC}"

echo "Current QR files permissions:"
ls -la /var/www/whatsapp_statics/qrcode/ | head -5

echo "Fixing permissions for static files..."
# Fix ownership and permissions
sudo chown -R www-data:www-data /var/www/whatsapp_statics/
sudo chmod -R 755 /var/www/whatsapp_statics/
sudo chmod 644 /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null || echo "No PNG files to fix"

echo "Updated permissions:"
ls -la /var/www/whatsapp_statics/qrcode/ | head -5

# Step 4: Test static files access
echo -e "\n${YELLOW}📂 Step 4: Testing static files access...${NC}"

echo "Testing static directory access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static directory test failed"

echo "Testing QR directory access:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/" || echo "QR directory test failed"

# Test one of the existing QR files
EXISTING_QR=$(ls /var/www/whatsapp_statics/qrcode/*.png | head -1 | xargs basename)
if [ -n "$EXISTING_QR" ]; then
    echo "Testing existing QR file: $EXISTING_QR"
    curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/qrcode/$EXISTING_QR" || echo "QR file test failed"
else
    echo "No QR files found to test"
fi

# Create a test file
echo "Creating test file..."
echo "Test static file $(date)" > /var/www/whatsapp_statics/test-final.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-final.txt
sudo chmod 644 /var/www/whatsapp_statics/test-final.txt

echo "Testing test file:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/test-final.txt" || echo "Test file failed"

# Step 5: Generate fresh QR code and test complete flow
echo -e "\n${YELLOW}📱 Step 5: Testing complete QR flow...${NC}"

echo "Generating fresh QR code:"
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login")
echo "QR API Response: $QR_RESPONSE"

# Extract QR link
QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
if [ -n "$QR_LINK" ]; then
    echo "QR Link: $QR_LINK"
    
    # Extract filename
    QR_FILENAME=$(basename "$QR_LINK")
    echo "QR Filename: $QR_FILENAME"
    
    # Wait a moment for file to be created
    sleep 2
    
    # Check if file exists
    if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
        echo -e "${GREEN}✅ QR file created: $QR_FILENAME${NC}"
        ls -la "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
        
        # Fix permissions for new file
        sudo chown www-data:www-data "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
        sudo chmod 644 "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
        
        # Test access to new QR file
        echo "Testing new QR file access:"
        curl -s -w "Status: %{http_code}\n" "$QR_LINK" || echo "New QR file access failed"
    else
        echo -e "${RED}❌ QR file not created${NC}"
    fi
else
    echo "No QR link found in response"
fi

# Step 6: Test QR page
echo -e "\n${YELLOW}🌐 Step 6: Testing QR page...${NC}"

echo "Testing QR page access:"
QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)
echo "QR page status: $QR_PAGE_STATUS"

# Step 7: Final comprehensive test
echo -e "\n${YELLOW}✅ Step 7: Final comprehensive test...${NC}"

echo "Final test results:"
API_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
LOGIN_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
STATIC_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/" 2>/dev/null | tail -1)
QR_PAGE_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)

echo "- API Devices: $API_TEST"
echo "- API Login: $LOGIN_TEST"
echo "- Static Files: $STATIC_TEST"
echo "- QR Page: $QR_PAGE_TEST"

# Test specific QR file if available
if [ -n "$QR_LINK" ]; then
    QR_FILE_TEST=$(curl -s -w "%{http_code}" "$QR_LINK" 2>/dev/null | tail -1)
    echo "- QR File: $QR_FILE_TEST"
fi

# Step 8: Success summary
echo -e "\n${BLUE}📊 Final Status Summary${NC}"
echo "=================================================="

if [ "$API_TEST" = "200" ] && [ "$LOGIN_TEST" = "200" ] && [ "$STATIC_TEST" = "200" ] && [ "$QR_PAGE_TEST" = "200" ]; then
    echo -e "${GREEN}🎉 COMPLETE SUCCESS! All systems operational!${NC}"
    echo ""
    echo -e "${GREEN}✅ WhatsApp API: Working (200)${NC}"
    echo -e "${GREEN}✅ QR Generation: Working (200)${NC}"
    echo -e "${GREEN}✅ Static Files: Working (200)${NC}"
    echo -e "${GREEN}✅ QR Page: Working (200)${NC}"
    echo ""
    echo -e "${BLUE}🎯 Your WhatsApp QR system is fully operational!${NC}"
    echo ""
    echo -e "${BLUE}📱 Access your QR page:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    echo ""
    echo -e "${BLUE}📋 API Endpoints:${NC}"
    echo "- Devices: https://hartonomotor.xyz/whatsapp-api/app/devices"
    echo "- Login: https://hartonomotor.xyz/whatsapp-api/app/login"
    echo "- Static: https://hartonomotor.xyz/statics/"
    echo ""
    echo -e "${BLUE}📊 Monitoring:${NC}"
    echo "- Container: docker logs -f whatsapp-api-hartono"
    echo "- Nginx: docker logs -f hartono-webserver"
    echo "- Health: docker ps | grep whatsapp"
    
elif [ "$STATIC_TEST" != "200" ]; then
    echo -e "${YELLOW}⚠️ Static files still having issues (Status: $STATIC_TEST)${NC}"
    echo "API is working but static file serving needs attention"
    
    echo -e "\nDebugging static files:"
    echo "Nginx error logs:"
    docker exec $NGINX_CONTAINER tail -5 /var/log/nginx/error.log 2>/dev/null || echo "No error logs"
    
    echo -e "\nStatic directory from Nginx container:"
    docker exec $NGINX_CONTAINER ls -la /var/www/whatsapp_statics/ || echo "Directory not accessible"
    
else
    echo -e "${RED}❌ Some components still not working${NC}"
    echo "Check the individual status codes above"
fi

echo -e "\n${BLUE}📋 Configuration Summary:${NC}"
echo "- WhatsApp Container: whatsapp-api-hartono"
echo "- WhatsApp IP: $WHATSAPP_IP"
echo "- Static Directory: /var/www/whatsapp_statics/"
echo "- Nginx Container: $NGINX_CONTAINER"
echo "- SSL: Working with existing certificates"
