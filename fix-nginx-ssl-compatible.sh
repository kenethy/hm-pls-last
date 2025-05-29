#!/bin/bash

# Fix Nginx Configuration with Correct SSL Paths
# Use existing working SSL configuration

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Nginx Configuration with Correct SSL${NC}"
echo "=================================================="

# Step 1: Get current working SSL configuration
echo -e "\n${YELLOW}🔍 Step 1: Getting current SSL configuration...${NC}"

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_CONTAINER="whatsapp-api-hartono"
WHATSAPP_IP="192.168.144.2"

echo "WhatsApp container IP: $WHATSAPP_IP"

# Get current SSL configuration
echo "Current SSL configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -E "(ssl_certificate|listen.*ssl)" || echo "No SSL config found"

# Check available SSL certificates
echo -e "\nChecking available SSL certificates:"
docker exec $NGINX_CONTAINER find /etc -name "*.pem" 2>/dev/null | head -10 || echo "No certificates found in /etc"

# Check if certificates are in different location
docker exec $NGINX_CONTAINER ls -la /etc/ssl/certs/ 2>/dev/null | head -5 || echo "No /etc/ssl/certs"
docker exec $NGINX_CONTAINER ls -la /etc/nginx/ssl/ 2>/dev/null | head -5 || echo "No /etc/nginx/ssl"

# Step 2: Get the working configuration and modify only WhatsApp part
echo -e "\n${YELLOW}📄 Step 2: Creating targeted fix...${NC}"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.backup.$(date +%Y%m%d_%H%M%S)

# Get current working config
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf > /tmp/current_working.conf

echo "Current working configuration saved"

# Create a simple sed replacement for just the WhatsApp proxy part
echo "Creating targeted WhatsApp proxy fix..."

# Use sed to replace only the WhatsApp proxy_pass line
docker exec $NGINX_CONTAINER sed -i "s|proxy_pass http://whatsapp-api:3000/|proxy_pass http://$WHATSAPP_IP:3000/|g" /etc/nginx/conf.d/app.conf

# Add the authorization header after the proxy_pass line
docker exec $NGINX_CONTAINER sed -i "/proxy_pass http:\/\/$WHATSAPP_IP:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf

# Add proxy buffering off
docker exec $NGINX_CONTAINER sed -i "/proxy_set_header Authorization/a\\        proxy_buffering off;" /etc/nginx/conf.d/app.conf

echo "Applied targeted fixes to existing configuration"

# Step 3: Test the configuration
echo -e "\n${YELLOW}🧪 Step 3: Testing configuration...${NC}"

echo "Testing Nginx configuration:"
docker exec $NGINX_CONTAINER nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Configuration still has errors${NC}"
    echo "Restoring backup..."
    BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
    docker exec $NGINX_CONTAINER nginx -s reload
    exit 1
fi

# Step 4: Test connectivity with authentication
echo -e "\n${YELLOW}🔗 Step 4: Testing connectivity with authentication...${NC}"

# Test from nginx container to whatsapp with auth
echo "Testing from Nginx to WhatsApp with auth:"
docker exec $NGINX_CONTAINER wget -qO- --timeout=10 --header="Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=" "http://$WHATSAPP_IP:3000/app/devices" || echo "Auth connection failed"

# Step 5: Test API endpoints
echo -e "\n${YELLOW}🌐 Step 5: Testing API endpoints...${NC}"

echo "1. Direct API test:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct API failed"

echo "2. Nginx proxy test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Nginx proxy failed"

echo "3. Login endpoint test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Login endpoint failed"

# Step 6: Show current configuration
echo -e "\n${YELLOW}📋 Step 6: Current configuration...${NC}"

echo "Current WhatsApp proxy configuration:"
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 10 -B 2 "whatsapp-api"

# Step 7: Test static files
echo -e "\n${YELLOW}📁 Step 7: Testing static files...${NC}"

echo "Testing static files:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/statics/" || echo "Static files failed"

# Step 8: Final verification
echo -e "\n${YELLOW}✅ Step 8: Final verification...${NC}"

echo "Final test results:"
API_DIRECT=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
API_NGINX=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
STATIC_TEST=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/statics/" 2>/dev/null | tail -1)

echo "- Direct API: $API_DIRECT"
echo "- Nginx Proxy: $API_NGINX"
echo "- Static Files: $STATIC_TEST"

if [ "$API_NGINX" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! WhatsApp API proxy is working!${NC}"
    echo ""
    echo -e "${BLUE}📱 Test the QR page now:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    echo ""
    echo -e "${BLUE}📊 What should work now:${NC}"
    echo "✅ API endpoint: https://hartonomotor.xyz/whatsapp-api/app/login"
    echo "✅ QR code generation and display"
    echo "✅ Static files serving"
    echo ""
    echo -e "${BLUE}📋 Monitoring:${NC}"
    echo "- API test: curl https://hartonomotor.xyz/whatsapp-api/app/devices"
    echo "- Container logs: docker logs -f whatsapp-api-hartono"
    echo "- Nginx logs: docker logs -f hartono-webserver"
    
elif [ "$API_NGINX" = "502" ]; then
    echo -e "\n${RED}❌ Still getting 502 Bad Gateway${NC}"
    echo "Checking Nginx error logs:"
    docker exec $NGINX_CONTAINER tail -10 /var/log/nginx/error.log 2>/dev/null || echo "No error logs"
    
elif [ "$API_NGINX" = "401" ]; then
    echo -e "\n${YELLOW}⚠️ Getting 401 Unauthorized - Auth header not working${NC}"
    echo "Need to check auth header configuration"
    
else
    echo -e "\n${YELLOW}⚠️ Unexpected response: $API_NGINX${NC}"
    echo "Checking what's happening..."
    curl -v "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>&1 | head -20
fi

echo -e "\n${BLUE}📋 Configuration Summary:${NC}"
echo "- WhatsApp IP: $WHATSAPP_IP"
echo "- Proxy URL: http://$WHATSAPP_IP:3000/"
echo "- Auth: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE="
echo "- SSL: Using existing working configuration"

# Step 9: Quick QR test
echo -e "\n${YELLOW}📱 Step 9: Quick QR page test...${NC}"
QR_PAGE_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-qr.html" 2>/dev/null | tail -1)
echo "QR page status: $QR_PAGE_STATUS"

if [ "$QR_PAGE_STATUS" = "200" ]; then
    echo -e "${GREEN}✅ QR page is accessible${NC}"
else
    echo -e "${RED}❌ QR page not accessible${NC}"
fi
