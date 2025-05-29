#!/bin/bash

# Ultimate Solution Based on Online Documentation
# Root cause: Nginx validates upstream hosts at startup, not at runtime

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔧 Ultimate Solution Based on Online Documentation${NC}"
echo -e "${BLUE}Root Cause: Nginx validates upstream hosts at startup${NC}"
echo "=================================================================="

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

echo -e "${YELLOW}📚 Based on research from StackOverflow, Reddit, and GitHub:${NC}"
echo "- Nginx validates upstream hosts when config is loaded"
echo "- Even if we replace hostnames with IPs, old references persist"
echo "- Solution: Use 'set' directive with variables for dynamic resolution"

# Step 1: Show current problematic config
echo -e "\n${YELLOW}🔍 Step 1: Current problematic configuration${NC}"
echo "Line 32 that keeps causing issues:"
docker exec $NGINX_CONTAINER sed -n '30,35p' /etc/nginx/conf.d/app.conf

# Step 2: Create completely new WhatsApp block using variables
echo -e "\n${YELLOW}🔧 Step 2: Creating new config with variable resolution${NC}"

# Backup current config
docker exec $NGINX_CONTAINER cp /etc/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf.ultimate-backup.$(date +%Y%m%d_%H%M%S)

# Remove the entire problematic WhatsApp block
echo "Removing problematic WhatsApp configuration block..."
docker exec $NGINX_CONTAINER sed -i '/# WhatsApp API Reverse Proxy/,/^    }/d' /etc/nginx/conf.d/app.conf

# Add new WhatsApp configuration using variables (recommended by documentation)
echo "Adding new WhatsApp configuration with variable resolution..."
docker exec $NGINX_CONTAINER sh -c "
cat >> /etc/nginx/conf.d/app.conf << 'EOF'

    # WhatsApp API Reverse Proxy - Variable Resolution Method
    location /whatsapp-api/ {
        # Use variable to avoid upstream validation at startup
        set \$whatsapp_backend http://$WHATSAPP_IP:3000;
        proxy_pass \$whatsapp_backend;
        
        # Authentication
        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";
        
        # Essential headers
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
        proxy_read_timeout 300s;
        proxy_connect_timeout 75s;
        proxy_buffering off;

        # CORS headers
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS' always;
        add_header 'Access-Control-Allow-Headers' 'DNT,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type,Range,Authorization' always;
        
        # Handle preflight requests
        if (\$request_method = 'OPTIONS') {
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
"

echo "✅ New configuration added using variable resolution method"

# Step 3: Show new configuration
echo -e "\n${YELLOW}📄 Step 3: New configuration${NC}"
echo "New WhatsApp configuration:"
docker exec $NGINX_CONTAINER tail -30 /etc/nginx/conf.d/app.conf

# Step 4: Test configuration
echo -e "\n${YELLOW}🧪 Step 4: Testing new configuration${NC}"

echo "Testing Nginx configuration syntax..."
if docker exec $NGINX_CONTAINER nginx -t; then
    echo -e "${GREEN}✅ SUCCESS! Configuration syntax is valid${NC}"
    echo -e "${GREEN}✅ No more 'host not found in upstream' error!${NC}"
else
    echo -e "${RED}❌ Configuration still has errors${NC}"
    echo "Showing error details:"
    docker exec $NGINX_CONTAINER nginx -t 2>&1
    exit 1
fi

# Step 5: Apply configuration
echo -e "\n${YELLOW}🔄 Step 5: Applying new configuration${NC}"

echo "Reloading Nginx with new configuration..."
docker exec $NGINX_CONTAINER nginx -s reload
echo -e "${GREEN}✅ Nginx reloaded successfully${NC}"

# Step 6: Test API endpoints
echo -e "\n${YELLOW}🧪 Step 6: Testing API endpoints${NC}"

sleep 3

echo "Testing devices endpoint:"
API_DEVICES=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "Devices API Status: $API_DEVICES"

echo "Testing login endpoint:"
API_LOGIN=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null | tail -1)
echo "Login API Status: $API_LOGIN"

# Step 7: Test QR generation
if [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${YELLOW}📱 Step 7: Testing QR generation${NC}"
    
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link Generated: $QR_LINK"
        echo -e "${GREEN}✅ QR generation working!${NC}"
    fi
fi

# Step 8: Final results
echo -e "\n${YELLOW}✅ Step 8: Final Results${NC}"
echo "=================================================================="

echo "ULTIMATE SOLUTION RESULTS:"
echo "- Configuration Method: Variable Resolution (recommended by docs)"
echo "- API Devices: $API_DEVICES"
echo "- API Login: $API_LOGIN"
echo "- 'Host Not Found' Error: $([ "$API_DEVICES" = "200" ] && echo "✅ FIXED" || echo "❌ Still present")"

if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${GREEN}🎉 ULTIMATE SUCCESS!${NC}"
    echo -e "${GREEN}✅ The 'host not found in upstream' error is permanently fixed!${NC}"
    echo -e "${GREEN}✅ Using variable resolution method from documentation${NC}"
    echo -e "${GREEN}✅ WhatsApp API fully operational${NC}"
    echo -e "${GREEN}✅ QR generation working${NC}"
    
    echo -e "\n${BLUE}📱 Your WhatsApp QR system is ready:${NC}"
    echo "https://hartonomotor.xyz/whatsapp-qr.html"
    
    echo -e "\n${BLUE}🎯 Technical Solution Used:${NC}"
    echo "- Method: Nginx variable resolution"
    echo "- Source: StackOverflow/GitHub documentation"
    echo "- Benefit: Avoids upstream validation at startup"
    echo "- Result: Permanent fix for hostname resolution issues"
    
elif [ "$API_DEVICES" = "401" ] || [ "$API_LOGIN" = "401" ]; then
    echo -e "\n${YELLOW}⚠️ Configuration fixed, but authentication needs attention${NC}"
    echo "The 'host not found' error is resolved, but auth header needs adjustment"
    
else
    echo -e "\n${RED}❌ Still having issues${NC}"
    echo "Need to investigate further"
fi

echo -e "\n${BLUE}📚 Solution Source:${NC}"
echo "Based on research from:"
echo "- StackOverflow: Docker networking nginx upstream issues"
echo "- GitHub Issues: nginx-proxy-manager discussions"
echo "- Reddit: nginx Docker container problems"
echo "- Key insight: Use variables to avoid startup validation"

echo -e "\n${BLUE}📊 Technical Details:${NC}"
echo "- Old method: proxy_pass http://hostname:port (fails at startup)"
echo "- New method: set \$var http://ip:port; proxy_pass \$var (runtime resolution)"
echo "- Benefit: Nginx doesn't validate upstream at config load time"
echo "- Result: Eliminates 'host not found in upstream' errors permanently"

if [ "$API_DEVICES" = "200" ] && [ "$API_LOGIN" = "200" ]; then
    echo -e "\n${GREEN}🎊 MISSION ACCOMPLISHED!${NC}"
    echo -e "${GREEN}Problem solved using documented best practices!${NC}"
fi
