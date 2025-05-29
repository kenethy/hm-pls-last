#!/bin/bash

# Fix Network Connectivity and Container Name Issues
# Specific fix for containers on different networks

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔧 Fixing Network Connectivity and Container Names${NC}"
echo "=================================================="

# Step 1: Identify the network issue
echo -e "\n${YELLOW}🌐 Step 1: Analyzing network configuration...${NC}"

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_CONTAINER="whatsapp-api-hartono"

# Get network names
NGINX_NETWORK="hm-new_hartono-network"
WHATSAPP_NETWORK="hm-new_whatsapp-network"

echo "Nginx is on network: $NGINX_NETWORK"
echo "WhatsApp is on network: $WHATSAPP_NETWORK"
echo "Problem: Containers are on different networks!"

# Step 2: Connect WhatsApp container to Nginx network
echo -e "\n${YELLOW}🔗 Step 2: Connecting WhatsApp to Nginx network...${NC}"

echo "Connecting $WHATSAPP_CONTAINER to $NGINX_NETWORK..."
docker network connect $NGINX_NETWORK $WHATSAPP_CONTAINER

# Verify connection
echo "Verifying network connection..."
docker inspect $WHATSAPP_CONTAINER | grep -A 10 "Networks" | grep -E "(hartono-network|whatsapp-network)"

# Test connectivity
echo "Testing connectivity after network connection:"
docker exec $NGINX_CONTAINER ping -c 2 $WHATSAPP_CONTAINER || echo "Still cannot ping by container name"

# Try with alias
echo "Testing with alias 'whatsapp-api':"
docker exec $NGINX_CONTAINER ping -c 2 whatsapp-api || echo "Cannot ping by alias"

# Step 3: Fix Nginx configuration with correct container reference
echo -e "\n${YELLOW}📄 Step 3: Fixing Nginx configuration with correct container reference...${NC}"

# Restore backup first
echo "Restoring previous Nginx configuration..."
BACKUP_FILE=$(docker exec $NGINX_CONTAINER ls /etc/nginx/conf.d/ | grep "app.conf.backup" | tail -1)
if [ -n "$BACKUP_FILE" ]; then
    docker exec $NGINX_CONTAINER cp "/etc/nginx/conf.d/$BACKUP_FILE" /etc/nginx/conf.d/app.conf
    echo "Restored from backup: $BACKUP_FILE"
fi

# Get the current working configuration and modify only the WhatsApp part
echo "Getting current working Nginx configuration..."
docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf > /tmp/current_nginx.conf

# Create a targeted fix for just the WhatsApp proxy section
echo "Creating targeted fix for WhatsApp proxy..."
cat > /tmp/whatsapp_proxy_fix.txt << 'EOF'
    # WhatsApp API Reverse Proxy - FIXED
    location /whatsapp-api/ {
        # Use the correct container name that works in the network
        proxy_pass http://whatsapp-api:3000/;
        
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        
        # Add basic auth header for WhatsApp API
        proxy_set_header Authorization "Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=";
        
        # Disable buffering for real-time responses
        proxy_buffering off;

        # CORS headers for WhatsApp API
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        
        # Handle preflight requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
            add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }
    }
EOF

# Apply the targeted fix using sed
echo "Applying targeted fix to Nginx configuration..."
docker exec $NGINX_CONTAINER bash -c "
# Replace the WhatsApp API location block with the fixed version
sed -i '/# WhatsApp API Reverse Proxy/,/^    }/c\
    # WhatsApp API Reverse Proxy - FIXED\
    location /whatsapp-api/ {\
        # Use the correct container name that works in the network\
        proxy_pass http://whatsapp-api:3000/;\
        \
        proxy_http_version 1.1;\
        proxy_set_header Upgrade \$http_upgrade;\
        proxy_set_header Connection \"upgrade\";\
        proxy_set_header Host \$host;\
        proxy_set_header X-Real-IP \$remote_addr;\
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;\
        proxy_set_header X-Forwarded-Proto \$scheme;\
        proxy_cache_bypass \$http_upgrade;\
        proxy_read_timeout 300s;\
        proxy_connect_timeout 75s;\
        \
        # Add basic auth header for WhatsApp API\
        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";\
        \
        # Disable buffering for real-time responses\
        proxy_buffering off;\
\
        # CORS headers for WhatsApp API\
        add_header \"Access-Control-Allow-Origin\" \"*\" always;\
        add_header \"Access-Control-Allow-Methods\" \"GET, POST, OPTIONS\" always;\
        add_header \"Access-Control-Allow-Headers\" \"DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization\" always;\
        \
        # Handle preflight requests\
        if (\$request_method = \"OPTIONS\") {\
            add_header \"Access-Control-Allow-Origin\" \"*\";\
            add_header \"Access-Control-Allow-Methods\" \"GET, POST, OPTIONS\";\
            add_header \"Access-Control-Allow-Headers\" \"DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization\";\
            add_header \"Access-Control-Max-Age\" 1728000;\
            add_header \"Content-Type\" \"text/plain; charset=utf-8\";\
            add_header \"Content-Length\" 0;\
            return 204;\
        }\
    }' /etc/nginx/conf.d/app.conf
"

# Test nginx configuration
echo "Testing Nginx configuration..."
docker exec $NGINX_CONTAINER nginx -t

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Nginx configuration is valid${NC}"
    
    # Reload nginx
    echo "Reloading Nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"
else
    echo -e "${RED}❌ Nginx configuration still has errors${NC}"
    echo "Showing current WhatsApp config:"
    docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 20 -B 5 "whatsapp-api"
fi

# Step 4: Test connectivity after fix
echo -e "\n${YELLOW}🧪 Step 4: Testing connectivity after fix...${NC}"

# Test container to container connectivity
echo "Testing container-to-container connectivity:"
docker exec $NGINX_CONTAINER wget -qO- --timeout=10 "http://whatsapp-api:3000/app/devices" || echo "Still cannot connect to whatsapp-api"

# Try with the full container name
docker exec $NGINX_CONTAINER wget -qO- --timeout=10 "http://$WHATSAPP_CONTAINER:3000/app/devices" || echo "Cannot connect with full container name"

# Step 5: Test API endpoints
echo -e "\n${YELLOW}🔗 Step 5: Testing API endpoints...${NC}"

echo "1. Direct API test:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" || echo "Direct API failed"

echo "2. Nginx proxy test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" || echo "Nginx proxy failed"

echo "3. Login endpoint test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/login" || echo "Login endpoint failed"

# Step 6: Check container aliases and networks
echo -e "\n${YELLOW}🔍 Step 6: Checking container network details...${NC}"

echo "WhatsApp container network details:"
docker inspect $WHATSAPP_CONTAINER | grep -A 20 "Networks"

echo -e "\nAvailable container names in Nginx network:"
docker network inspect $NGINX_NETWORK | grep -A 5 -B 5 "Containers"

# Step 7: Alternative fix if still not working
echo -e "\n${YELLOW}🔧 Step 7: Alternative fix using IP address...${NC}"

# Get WhatsApp container IP in the Nginx network
WHATSAPP_IP=$(docker inspect $WHATSAPP_CONTAINER | grep -A 10 "$NGINX_NETWORK" | grep '"IPAddress"' | cut -d'"' -f4)

if [ -n "$WHATSAPP_IP" ]; then
    echo "WhatsApp container IP in Nginx network: $WHATSAPP_IP"
    
    # Test connectivity using IP
    echo "Testing connectivity using IP address:"
    docker exec $NGINX_CONTAINER wget -qO- --timeout=10 "http://$WHATSAPP_IP:3000/app/devices" || echo "IP connection failed"
    
    # If IP works, update nginx config to use IP
    if docker exec $NGINX_CONTAINER wget -qO- --timeout=5 "http://$WHATSAPP_IP:3000/app/devices" > /dev/null 2>&1; then
        echo "IP connection works! Updating Nginx to use IP address..."
        docker exec $NGINX_CONTAINER sed -i "s|proxy_pass http://whatsapp-api:3000/|proxy_pass http://$WHATSAPP_IP:3000/|g" /etc/nginx/conf.d/app.conf
        docker exec $NGINX_CONTAINER nginx -t && docker exec $NGINX_CONTAINER nginx -s reload
    fi
else
    echo "Could not get WhatsApp container IP"
fi

# Step 8: Final verification
echo -e "\n${YELLOW}✅ Step 8: Final verification...${NC}"

echo "Final API tests:"
API_DIRECT=$(curl -s -u "admin:HartonoMotor2025!" -w "%{http_code}" "http://localhost:3000/app/devices" 2>/dev/null | tail -1)
API_NGINX=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)

echo "- Direct API: $API_DIRECT"
echo "- Nginx Proxy: $API_NGINX"

if [ "$API_NGINX" = "200" ]; then
    echo -e "\n${GREEN}🎉 SUCCESS! Network connectivity fixed!${NC}"
    echo "Now test the QR page: https://hartonomotor.xyz/whatsapp-qr.html"
else
    echo -e "\n${YELLOW}⚠️ Still having issues. Let's check what's happening...${NC}"
    
    echo "Nginx error logs:"
    docker exec $NGINX_CONTAINER tail -5 /var/log/nginx/error.log 2>/dev/null || echo "No error logs"
    
    echo -e "\nCurrent WhatsApp proxy config:"
    docker exec $NGINX_CONTAINER cat /etc/nginx/conf.d/app.conf | grep -A 10 -B 2 "proxy_pass.*whatsapp"
fi

echo -e "\n${BLUE}📊 Network Status:${NC}"
echo "WhatsApp container networks:"
docker inspect $WHATSAPP_CONTAINER | grep -A 5 '"Networks"'
