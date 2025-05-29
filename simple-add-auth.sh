#!/bin/bash

# Simple Add Auth Header
# Direct approach without complex commands

set -e

echo "🔧 Adding Auth Header..."

NGINX_CONTAINER="hartono-webserver"
WHATSAPP_IP="192.168.144.2"

# Step 1: Add auth header
echo "Adding authorization header..."
docker exec $NGINX_CONTAINER sed -i "/proxy_pass http:\/\/$WHATSAPP_IP:3000\//a\\        proxy_set_header Authorization \"Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE=\";" /etc/nginx/conf.d/app.conf

echo "✅ Auth header added"

# Step 2: Test and reload
echo "Testing nginx config..."
docker exec $NGINX_CONTAINER nginx -t

echo "Reloading nginx..."
docker exec $NGINX_CONTAINER nginx -s reload

echo "✅ Nginx reloaded"

# Step 3: Test API
echo "Testing API..."
sleep 3

API_STATUS=$(curl -s -w "%{http_code}" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null | tail -1)
echo "API Status: $API_STATUS"

if [ "$API_STATUS" = "200" ]; then
    echo "🎉 SUCCESS! API is working!"
    
    # Test QR generation
    echo "Testing QR generation..."
    QR_RESPONSE=$(curl -s "https://hartonomotor.xyz/whatsapp-api/app/login" 2>/dev/null)
    QR_LINK=$(echo "$QR_RESPONSE" | grep -o '"qr_link":"[^"]*"' | cut -d'"' -f4)
    
    if [ -n "$QR_LINK" ]; then
        echo "QR Link: $QR_LINK"
        echo "🎉 QR generation working!"
        
        echo ""
        echo "📱 Test your QR system:"
        echo "https://hartonomotor.xyz/whatsapp-qr.html"
        echo ""
        echo "✅ WhatsApp API integration is working!"
    fi
else
    echo "❌ API Status: $API_STATUS"
    echo "Still need to fix authentication"
fi

echo ""
echo "📊 Final Status:"
echo "- API: $API_STATUS"
echo "- Container: $(docker ps --format "{{.Status}}" --filter "name=whatsapp-api-hartono")"
