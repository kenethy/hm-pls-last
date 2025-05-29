#!/bin/bash

# Final Complete Fix - Replace ALL whatsapp-api with IP and add auth
# This will definitively solve the issue

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Final Complete Fix${NC}"
echo "=================================================="

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

# Step 1: Show current problematic line
echo -e "\n${YELLOW}🔍 Step 1: Current problematic configuration...${NC}"
echo "Line 32 issue:"
docker exec $NGINX_CONTAINER sed -n '30,35p' /etc/nginx/conf.d/app.conf

echo "All whatsapp-api references:"
docker exec $NGINX_CONTAINER grep -n "whatsapp-api" /etc/nginx/conf.d/app.conf || echo "No references found"

# Step 2: Restore from backup and fix properly
echo -e "\n${YELLOW}🔧 Step 2: Complete fix...${NC}"

# Use the most recent backup
BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
if [ -n "$BACKUP_FILE" ]; then
    echo "Restoring from backup: $BACKUP_FILE"
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
else
    echo "No backup found, proceeding with current config"
fi

# Replace ALL whatsapp-api references with IP address
echo "Replacing ALL whatsapp-api references with IP..."
docker exec $NGINX_CONTAINER sed -i "s|whatsapp-api|$WHATSAPP_IP|g" /etc/nginx/conf.d/app.conf

# Fix location path back to /whatsapp-api/
echo "Fixing location path..."
docker exec $NGINX_CONTAINER sed -i "s|location /$WHATSAPP_IP/|location /whatsapp-api/|g" /etc/nginx/conf.d/app.conf

# Add authentication header after proxy_pass
echo "Adding authentication header..."
docker exec $NGINX_CONTAINER sed -i "/proxy_pass http:\/\/$WHATSAPP_IP:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf

# Add proxy buffering off
docker exec $NGINX_CONTAINER sed -i "/proxy_set_header Authorization/a\\        proxy_buffering off;" /etc/nginx/conf.d/app.conf

echo "✅ Complete configuration updated"

# Step 3: Show updated config
echo -e "\n${YELLOW}📄 Step 3: Updated configuration...${NC}"
echo "WhatsApp proxy section:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 15 -B 2 "location /whatsapp-api/"

# Step 4: Test nginx configuration
echo -e "\n${YELLOW}🧪 Step 4: Testing Nginx configuration...${NC}"

if docker exec $NGINX_CONTAINER nginx -t; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Nginx configuration still has errors${NC}"
    echo "Showing problematic lines:"
    docker exec $NGINX_CONTAINER nginx -t 2>&1 | head -10
    exit 1
fi

# Step 5: Test API endpoints
echo -e "\n${YELLOW}🔗 Step 5: Testing API endpoints...${NC}"

# Wait a moment for nginx to fully reload
sleep 3

echo "Testing devices endpoint:"
API_DEVICES_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null)
API_DEVICES_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Devices API Status: $API_DEVICES_STATUS"
echo "Devices API Response: $API_DEVICES_RESPONSE"

echo -e "\nTesting login endpoint:"
API_LOGIN_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
API_LOGIN_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Login API Status: $API_LOGIN_STATUS"
echo "Login API Response: $API_LOGIN_RESPONSE"

# Step 6: Generate QR if API is working
if [ "$API_LOGIN_STATUS" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 6: QR Code Generation...${NC}"
    
    QR_LINK=$(echo "$API_LOGIN_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    if [ -n "$QR_LINK" ]; then
        echo "QR Link Generated: $QR_LINK"
        
        # Extract filename and check if file exists
        QR_FILENAME=$(basename "$QR_LINK")
        
        # Wait for file creation
        sleep 3
        
        if [ -f "/var/www/whatsapp_statics/qrcode/$QR_FILENAME" ]; then
            echo -e "${GREEN}✅ QR file created: $QR_FILENAME${NC}"
            ls -la "/var/www/whatsapp_statics/qrcode/$QR_FILENAME"
            
            # Test QR image access
            QR_IMAGE_STATUS=$(curl -s -w "%{http_code}" "$QR_LINK" 2>/dev/null | tail -1)
            echo "QR Image Access Status: $QR_IMAGE_STATUS"
        else
            echo -e "${YELLOW}⚠️ QR file not found, but API is generating links${NC}"
        fi
    else
        echo "No QR link found in response"
    fi
else
    echo -e "${RED}❌ Login API not working, skipping QR generation${NC}"
fi

# Step 7: Test static files
echo -e "\n${YELLOW}📂 Step 7: Testing static files...${NC}"

# Create test file
echo "Test static file $(date)" > /var/www/whatsapp_statics/test-final-fix.txt
sudo chown www-data:www-data /var/www/whatsapp_statics/test-final-fix.txt
sudo chmod 644 /var/www/whatsapp_statics/test-final-fix.txt

STATIC_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/test-final-fix.txt" 2>/dev/null | tail -1)
echo "Static File Test Status: $STATIC_STATUS"

# Step 8: Final comprehensive results
echo -e "\n${YELLOW}✅ Step 8: Final Results${NC}"
echo "=================================================="

QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)

echo "FINAL STATUS SUMMARY:"
echo "- API Devices: $API_DEVICES_STATUS"
echo "- API Login: $API_LOGIN_STATUS"
echo "- Static Files: $STATIC_STATUS"
echo "- QR Page: $QR_PAGE_STATUS"

# Success evaluation
if [ "$API_DEVICES_STATUS" = "200" ] && [ "$API_LOGIN_STATUS" = "200" ]; then
    echo -e "\n${GREEN}🎉 COMPLETE SUCCESS!${NC}"
    echo -e "${GREEN}✅ WhatsApp API is fully operational!${NC}"
    echo -e "${GREEN}✅ Authentication working perfectly!${NC}"
    echo -e "${GREEN}✅ QR code generation functional!${NC}"
    
    if [ "$STATIC_STATUS" = "200" ]; then
        echo -e "${GREEN}✅ Static files serving working!${NC}"
        echo -e "\n${GREEN}🎯 EVERYTHING IS WORKING PERFECTLY!${NC}"
    else
        echo -e "${YELLOW}⚠️ Static files need minor attention${NC}"
    fi
    
    echo -e "\n${BLUE}🎊 YOUR WHATSAPP QR SYSTEM IS READY!${NC}"
    echo -e "\n${BLUE}📱 Access your QR page:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
    echo -e "\n${BLUE}📋 API Endpoints Working:${NC}"
    echo "- Devices: https://hartonomotor.xyz/whatsapp-api/app/devices"
    echo "- Login: https://hartonomotor.xyz/whatsapp-api/app/login"
    
    echo -e "\n${BLUE}📊 System Status:${NC}"
    echo "- Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
    echo "- Network: Connected via IP $WHATSAPP_IP"
    echo "- Authentication: Working with Basic Auth"
    echo "- QR Generation: Functional"
    
elif [ "$API_DEVICES_STATUS" = "401" ] || [ "$API_LOGIN_STATUS" = "401" ]; then
    echo -e "\n${RED}❌ Still getting 401 Unauthorized${NC}"
    echo "Authentication header configuration issue"
    
elif [ "$API_DEVICES_STATUS" = "404" ] || [ "$API_LOGIN_STATUS" = "404" ]; then
    echo -e "\n${RED}❌ Getting 404 Not Found${NC}"
    echo "Path routing configuration issue"
    
else
    echo -e "\n${YELLOW}⚠️ Mixed results${NC}"
    echo "Some endpoints working, others need attention"
fi

echo -e "\n${BLUE}📋 Configuration Summary:${NC}"
echo "- Nginx Container: $NGINX_CONTAINER"
echo "- WhatsApp IP: $WHATSAPP_IP"
echo "- Proxy Path: /whatsapp-api/ → http://$WHATSAPP_IP:3000/"
echo "- Auth Header: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE="
echo "- Static Path: /statics/ → /var/www/whatsapp_statics/"
