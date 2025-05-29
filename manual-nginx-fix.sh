#!/bin/bash

# Manual Nginx Fix
# Check and fix all references manually

set -e

echo "🔧 Manual Nginx Fix..."

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

# Step 1: Show current problematic config
echo "Current problematic lines:"
docker exec $NGINX_CONTAINER sed -n '30,35p' /etc/nginx/conf.d/app.conf

echo ""
echo "All whatsapp-api references:"
docker exec $NGINX_CONTAINER grep -n "whatsapp-api" /etc/nginx/conf.d/app.conf || echo "No references found"

# Step 2: Fix ALL references
echo ""
echo "Fixing ALL whatsapp-api references..."

# Replace ALL occurrences
docker exec $NGINX_CONTAINER sed -i "s|whatsapp-api|$WHATSAPP_IP|g" /etc/nginx/conf.d/app.conf

# Fix location path back
docker exec $NGINX_CONTAINER sed -i "s|location /$WHATSAPP_IP/|location /whatsapp-api/|g" /etc/nginx/conf.d/app.conf

echo "✅ All references fixed"

# Step 3: Show updated config
echo ""
echo "Updated lines 30-35:"
docker exec $NGINX_CONTAINER sed -n '30,35p' /etc/nginx/conf.d/app.conf

# Step 4: Test nginx
echo ""
echo "Testing nginx config..."
if docker exec $NGINX_CONTAINER nginx -t; then
    echo "✅ Config valid"
    
    echo "Reloading nginx..."
    docker exec $NGINX_CONTAINER nginx -s reload
    echo "✅ Nginx reloaded"
    
    # Test API
    echo ""
    echo "Testing API..."
    sleep 3
    
    API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
    echo "API Status: $API_STATUS"
    
    if [ "$API_STATUS" = "200" ]; then
        echo "🎉 SUCCESS! API is working!"
        
        # Test QR
        QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
        QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
        
        if [ -n "$QR_LINK" ]; then
            echo "QR Link: $QR_LINK"
            echo ""
            echo "🎊 COMPLETE SUCCESS!"
            echo "📱 Test: https://hartonomotor.xyz/whatsapp-qr.html"
        fi
    else
        echo "❌ API Status: $API_STATUS"
        
        # Add auth header if missing
        if [ "$API_STATUS" = "401" ]; then
            echo "Adding auth header..."
            docker exec $NGINX_CONTAINER sed -i "/proxy_pass http:\/\/$WHATSAPP_IP:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf
            
            docker exec $NGINX_CONTAINER nginx -t && docker exec $NGINX_CONTAINER nginx -s reload
            
            sleep 2
            API_STATUS2=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
            echo "API Status after auth: $API_STATUS2"
            
            if [ "$API_STATUS2" = "200" ]; then
                echo "🎉 SUCCESS with auth header!"
            fi
        fi
    fi
    
else
    echo "❌ Config still has errors"
    echo "Showing error details:"
    docker exec $NGINX_CONTAINER nginx -t 2>&1
fi

echo ""
echo "📊 Final Status:"
echo "- Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
echo "- Config: Using IP $WHATSAPP_IP"
