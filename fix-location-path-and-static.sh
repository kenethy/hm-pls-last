#!/bin/bash

# Fix Location Path and Static Files Issues
# Careful fix to restore proper paths and static access

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Location Path and Static Files${NC}"
echo "=================================================="

# Step 1: Fix the location path issue
echo -e "\n${YELLOW}📄 Step 1: Fixing location path...${NC}"

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

echo "Current problematic configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 3 -B 1 "location.*192.168.144.2"

# Fix the location path back to /whatsapp-api/ but keep IP in proxy_pass
echo "Fixing location path..."
docker exec $NGINX_CONTAINER sed -i "s|location /192.168.144.2/|location /whatsapp-api/|g" /etc/nginx/conf.d/app.conf

echo "Updated configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 5 -B 2 "whatsapp-api"

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
    echo -e "${RED}❌ Nginx configuration has errors${NC}"
    exit 1
fi

# Step 3: Test API endpoints after fix
echo -e "\n${YELLOW}🔗 Step 3: Testing API endpoints...${NC}"

echo "1. API devices test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "API devices failed"

echo "2. API login test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "API login failed"

# Step 4: Generate new QR code
echo -e "\n${YELLOW}📱 Step 4: Generating new QR code...${NC}"

echo "Generating fresh QR code:"
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login")
echo "QR Response received"

# Extract QR link
QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
if [ -n "$QR_LINK" ]; then
    echo "QR Link: $QR_LINK"
    
    # Extract filename
    QR_FILENAME=$(basename "$QR_LINK")
    echo "QR Filename: $QR_FILENAME"
    
    # Wait for file to be created
    sleep 3
    
    # Check if file exists
    if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
        echo -e "${GREEN}✅ QR file created: $QR_FILENAME${NC}"
        ls -la "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
    else
        echo -e "${RED}❌ QR file not created${NC}"
        echo "Checking QR directory:"
        ls -la /var/www/whatsapp_statics/qrcode/
    fi
else
    echo "No QR link found in response"
    echo "Full response: $QR_RESPONSE"
fi

# Step 5: Debug static files issue
echo -e "\n${YELLOW}🔍 Step 5: Debugging static files issue...${NC}"

echo "Current static files configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 15 -B 2 "/statics/"

echo -e "\nChecking static directory from Nginx container:"
docker exec $NGINX_CONTAINER ls -la /var/www/whatsapp_statics/ || echo "Directory not accessible from Nginx"

echo -e "\nChecking static directory from host:"
ls -la /var/www/whatsapp_statics/

echo -e "\nChecking QR directory from host:"
ls -la /var/www/whatsapp_statics/qrcode/

# Step 6: Check Nginx error logs for static files
echo -e "\n${YELLOW}📋 Step 6: Checking Nginx error logs...${NC}"

echo "Recent Nginx error logs:"
docker exec $NGINX_CONTAINER tail -10 /var/log/nginx/error.log 2>/dev/null || echo "No error logs accessible"

# Step 7: Test static files with different approaches
echo -e "\n${YELLOW}📂 Step 7: Testing static files access...${NC}"

# Create a simple test file
echo "Creating simple test file..."
echo "Test $(date)" > /var/www/whatsapp_statics/simple-test.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/simple-test.txt
sudo chmod 644 /var/www/whatsapp_statics/simple-test.txt

echo "Testing simple test file:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/simple-test.txt" || echo "Simple test file failed"

# Test directory listing
echo "Testing directory listing:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Directory listing failed"

# Step 8: Check if static files config is properly placed
echo -e "\n${YELLOW}📄 Step 8: Checking static files configuration placement...${NC}"

echo "Full Nginx configuration structure:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -n -E "(server|location|alias)" | head -20

# Check if there are conflicting location blocks
echo -e "\nChecking for conflicting location blocks:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -n "location.*/" | head -10

# Step 9: Alternative static files configuration
echo -e "\n${YELLOW}🔧 Step 9: Trying alternative static files configuration...${NC}"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Try a simpler static files configuration
echo "Adding simpler static files configuration..."
docker exec $NGINX_CONTAINER sh -c "
# Remove existing statics location if it exists
sed -i '/# WhatsApp Static Files/,/^    }/d' /etc/nginx/conf.d/app.conf

# Add new simple static files configuration before the last location block
sed -i '/location ~ \/\.ht {/i\\
    # Static Files - Simple Configuration\
    location /statics/ {\
        alias /var/www/whatsapp_statics/;\
        autoindex on;\
        expires 1h;\
    }\
' /etc/nginx/conf.d/app.conf
"

# Test the new configuration
echo "Testing new static files configuration:"
docker exec $NGINX_CONTAINER nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ New static configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx with new static config..."
    docker exec $NGINX_CONTAINER nginx -s reload
    
    # Test static files again
    echo "Testing static files with new config:"
    curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Still failed"
    
    curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/simple-test.txt" || echo "Test file still failed"
    
else
    echo -e "${RED}❌ New static configuration has errors${NC}"
    echo "Restoring backup..."
    BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
    docker exec $NGINX_CONTAINER nginx -s reload
fi

# Step 10: Final status check
echo -e "\n${YELLOW}✅ Step 10: Final status check...${NC}"

echo "Final test results:"
API_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
LOGIN_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
STATIC_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/" 2>/dev/null | tail -1)

echo "- API Devices: $API_TEST"
echo "- API Login: $LOGIN_TEST"
echo "- Static Files: $STATIC_TEST"

if [ "$API_TEST" = "200" ] && [ "$LOGIN_TEST" = "200" ]; then
    echo -e "\n${GREEN}✅ WhatsApp API is fully working!${NC}"
    
    if [ "$STATIC_TEST" = "200" ]; then
        echo -e "${GREEN}✅ Static files are also working!${NC}"
        echo -e "\n${GREEN}🎉 COMPLETE SUCCESS! Test your QR page:${NC}"
        echo "https://hartonomotor.xyz/whatsapp-qr.html"
    else
        echo -e "${YELLOW}⚠️ Static files still need attention${NC}"
        echo "But WhatsApp API is working, so QR generation works"
        echo "You can still test: https://hartonomotor.xyz/whatsapp-qr.html"
    fi
else
    echo -e "${RED}❌ API endpoints need attention${NC}"
fi

echo -e "\n${BLUE}📋 Current Status:${NC}"
echo "- WhatsApp API: $([ "$API_TEST" = "200" ] && echo "✅ Working" || echo "❌ Issue")"
echo "- QR Generation: $([ "$LOGIN_TEST" = "200" ] && echo "✅ Working" || echo "❌ Issue")"
echo "- Static Files: $([ "$STATIC_TEST" = "200" ] && echo "✅ Working" || echo "❌ Issue")"
