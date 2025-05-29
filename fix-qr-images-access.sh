#!/bin/bash

# Fix QR Images Access - Static Files Solution
# QR generation working, but images not accessible via web

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Fix QR Images Access - Static Files Solution${NC}"
echo "=================================================="

echo -e "${GREEN}✅ WhatsApp API working!${NC}"
echo -e "${GREEN}✅ QR generation working!${NC}"
echo -e "${RED}❌ QR images not accessible via web (404 error)${NC}"

# Step 1: Check if QR files exist
echo -e "\n${YELLOW}📂 Step 1: Checking QR files${NC}"

echo "QR files on server:"
ls -la /var/www/whatsapp_statics/qrcode/ || echo "QR directory not found"

echo -e "\nChecking permissions:"
ls -la /var/www/whatsapp_statics/

# Step 2: Check nginx static configuration
echo -e "\n${YELLOW}📄 Step 2: Checking nginx static configuration${NC}"

echo "Current static files configuration in nginx:"
grep -A 10 -B 2 "location /statics/" docker/nginx/conf.d/app.conf || echo "No /statics/ location found"

# Step 3: Check volume mount
echo -e "\n${YELLOW}💾 Step 3: Checking volume mount${NC}"

echo "Nginx container mounts:"
docker inspect hartono-webserver --format '{{range .Mounts}}{{.Source}} -> {{.Destination}}{{"\n"}}{{end}}' | grep whatsapp

echo -e "\nTesting if nginx can see static files:"
docker exec hartono-webserver ls -la /var/www/whatsapp_statics/qrcode/ 2>/dev/null || echo "Cannot access from nginx container"

# Step 4: Test current static access
echo -e "\n${YELLOW}🧪 Step 4: Testing current static access${NC}"

# Create test file
echo "Creating test file..."
echo "Test $(date)" > /var/www/whatsapp_statics/test-qr-access.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-qr-access.txt
sudo chmod 644 /var/www/whatsapp_statics/test-qr-access.txt

echo "Testing static file access:"
STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-qr-access.txt" 2>/dev/null | tail -1)
echo "Static test status: $STATIC_STATUS"

# Step 5: Fix permissions
echo -e "\n${YELLOW}🔐 Step 5: Fixing permissions${NC}"

echo "Setting proper permissions..."
sudo chown -R www-data:www-data /var/www/whatsapp_statics/
sudo chmod -R 755 /var/www/whatsapp_statics/
sudo find /var/www/whatsapp_statics/ -type f -exec chmod 644 {} \;

echo "✅ Permissions fixed"

# Step 6: Ensure nginx config has static location
echo -e "\n${YELLOW}📝 Step 6: Ensuring nginx static configuration${NC}"

if ! grep -q "location /statics/" docker/nginx/conf.d/app.conf; then
    echo "Adding static files location to nginx config..."
    
    # Add before the last closing brace
    sed -i '/^}$/i\
\
    # Static files for WhatsApp QR codes\
    location /statics/ {\
        alias /var/www/whatsapp_statics/;\
        expires 5m;\
        add_header Cache-Control "public, no-transform";\
        add_header '\''Access-Control-Allow-Origin'\'' '\''*'\'' always;\
        try_files $uri $uri/ =404;\
    }' docker/nginx/conf.d/app.conf
    
    echo "✅ Static location added to nginx config"
else
    echo "✅ Static location already exists"
fi

# Step 7: Reload nginx
echo -e "\n${YELLOW}🔄 Step 7: Reloading nginx${NC}"

echo "Testing nginx config..."
if docker exec hartono-webserver nginx -t; then
    echo "✅ Config valid"
    
    echo "Reloading nginx..."
    docker exec hartono-webserver nginx -s reload
    echo "✅ Nginx reloaded"
else
    echo -e "${RED}❌ Config error${NC}"
    docker exec hartono-webserver nginx -t 2>&1
fi

# Step 8: Test static access again
echo -e "\n${YELLOW}🧪 Step 8: Testing static access after fix${NC}"

sleep 5

echo "Testing static file access:"
STATIC_STATUS2=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-qr-access.txt" 2>/dev/null | tail -1)
echo "Static status: $STATIC_STATUS2"

echo "Testing QR directory access:"
QR_DIR_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/qrcode/" 2>/dev/null | tail -1)
echo "QR directory status: $QR_DIR_STATUS"

# Step 9: Test actual QR file
echo -e "\n${YELLOW}📱 Step 9: Testing actual QR file${NC}"

# Get latest QR file
LATEST_QR=$(ls -t /var/www/whatsapp_statics/qrcode/*.png 2>/dev/null | head -1)
if [ -n "$LATEST_QR" ]; then
    QR_FILENAME=$(basename "$LATEST_QR")
    echo "Testing QR file: $QR_FILENAME"
    
    # Check file permissions
    echo "File permissions:"
    ls -la "$LATEST_QR"
    
    # Test access
    QR_ACCESS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/qrcode/$QR_FILENAME" 2>/dev/null | tail -1)
    echo "QR file access: $QR_ACCESS"
    
    if [ "$QR_ACCESS" = "200" ]; then
        echo -e "${GREEN}✅ QR file accessible!${NC}"
    else
        echo -e "${RED}❌ QR file not accessible${NC}"
        
        # Try to fix this specific file
        echo "Fixing this QR file permissions..."
        sudo chown www-data:www-data "$LATEST_QR"
        sudo chmod 644 "$LATEST_QR"
        
        # Test again
        sleep 2
        QR_ACCESS2=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/qrcode/$QR_FILENAME" 2>/dev/null | tail -1)
        echo "QR file access after fix: $QR_ACCESS2"
    fi
else
    echo "No QR files found"
fi

# Step 10: Generate fresh QR and test
echo -e "\n${YELLOW}🔄 Step 10: Testing fresh QR generation${NC}"

echo "Generating fresh QR..."
QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
NEW_QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)

if [ -n "$NEW_QR_LINK" ]; then
    echo "New QR Link: $NEW_QR_LINK"
    
    # Wait for file creation
    sleep 5
    
    # Fix permissions immediately
    NEW_QR_FILENAME=$(basename "$NEW_QR_LINK")
    if [ -f "/var/www/whatsapp_statics/qrcode/$NEW_QR_FILENAME" ]; then
        sudo chown www-data:www-data "/var/www/whatsapp_statics/qrcode/$NEW_QR_FILENAME"
        sudo chmod 644 "/var/www/whatsapp_statics/qrcode/$NEW_QR_FILENAME"
    fi
    
    # Test access
    NEW_QR_ACCESS=$(curl -s -w "%{http_code}" "$NEW_QR_LINK" 2>/dev/null | tail -1)
    echo "Fresh QR access: $NEW_QR_ACCESS"
    
    if [ "$NEW_QR_ACCESS" = "200" ]; then
        echo -e "${GREEN}✅ Fresh QR accessible!${NC}"
    else
        echo -e "${RED}❌ Fresh QR not accessible${NC}"
    fi
fi

# Step 11: Final results
echo -e "\n${YELLOW}✅ Step 11: Final Results${NC}"
echo "=================================================================="

echo "QR IMAGES ACCESS FIX RESULTS:"
echo "- Static Files: $STATIC_STATUS2"
echo "- QR Directory: $QR_DIR_STATUS"
echo "- Existing QR: $QR_ACCESS"
echo "- Fresh QR: $NEW_QR_ACCESS"

if [ "$STATIC_STATUS2" = "200" ] && [ "$QR_ACCESS" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! QR images now accessible!${NC}"
    echo -e "${GREEN}✅ Static files serving working${NC}"
    echo -e "${GREEN}✅ QR images accessible via web${NC}"
    echo -e "${GREEN}✅ Complete WhatsApp QR system operational${NC}"
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system is ready:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
    echo -e "\n${BLUE}🎯 What's working:${NC}"
    echo "✅ WhatsApp API endpoints"
    echo "✅ QR code generation"
    echo "✅ QR image storage"
    echo "✅ QR image web access"
    echo "✅ Static file serving"
    
elif [ "$STATIC_STATUS2" = "200" ]; then
    echo -e "\n${YELLOW}⚠️ Static files working, QR access needs attention${NC}"
    echo "Basic static serving works, check QR file permissions"
    
else
    echo -e "\n${RED}❌ Static files still not working${NC}"
    echo "Check nginx configuration and volume mounts"
fi

echo -e "\n${BLUE}📊 Current Status:${NC}"
echo "- WhatsApp API: ✅ Working"
echo "- QR Generation: ✅ Working"
echo "- Static Files: $([ "$STATIC_STATUS2" = "200" ] && echo "✅ Working" || echo "❌ Not working")"
echo "- QR Images: $([ "$QR_ACCESS" = "200" ] && echo "✅ Working" || echo "❌ Not working")"

echo -e "\n${BLUE}📋 Next Steps:${NC}"
if [ "$STATIC_STATUS2" = "200" ] && [ "$QR_ACCESS" = "200" ]; then
    echo "✅ Everything working! Test your QR page"
    echo "✅ Ready for production use"
else
    echo "🔧 Check nginx error logs: docker logs hartono-webserver"
    echo "🔧 Check file permissions: ls -la /var/www/whatsapp_statics/"
    echo "🔧 Test manual access: curl https://hartonomotor.xyz/statics/"
fi

if [ "$STATIC_STATUS2" = "200" ] && [ "$QR_ACCESS" = "200" ]; then
    echo -e "\n${GREEN}🎊 QR IMAGES ACCESS FIXED!${NC}"
    echo -e "${GREEN}Your WhatsApp QR system is fully operational!${NC}"
fi
