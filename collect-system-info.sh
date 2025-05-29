#!/bin/bash

# System Information Collection Script
# Run this on your VPS and share the output

echo "=== SYSTEM INFORMATION COLLECTION ==="
echo "Date: $(date)"
echo "Hostname: $(hostname)"
echo "User: $(whoami)"
echo ""

echo "=== DOCKER STATUS ==="
docker --version
docker-compose --version
echo ""

echo "=== RUNNING CONTAINERS ==="
docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}\t{{.Image}}"
echo ""

echo "=== WHATSAPP CONTAINER DETAILS ==="
WHATSAPP_CONTAINER=$(docker ps --format "{{.Names}}" | grep -i whatsapp | head -1)
if [ -n "$WHATSAPP_CONTAINER" ]; then
    echo "Container: $WHATSAPP_CONTAINER"
    echo "Health: $(docker inspect --format='{{.State.Health.Status}}' $WHATSAPP_CONTAINER 2>/dev/null || echo 'No health check')"
    echo "Logs (last 5 lines):"
    docker logs --tail=5 $WHATSAPP_CONTAINER
else
    echo "No WhatsApp container found"
fi
echo ""

echo "=== NETWORK STATUS ==="
netstat -tlnp | grep -E ":3000|:80|:443"
echo ""

echo "=== API TESTS ==="
echo "Direct API test:"
curl -s -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" 2>/dev/null || echo "Failed"

echo "API with auth:"
curl -s -u "admin:HartonoMotor2025!" -w "Status: %{http_code}\n" "http://localhost:3000/app/devices" 2>/dev/null || echo "Failed"

echo "Nginx proxy test:"
curl -s -w "Status: %{http_code}\n" "https://hartonomotor.xyz/whatsapp-api/app/devices" 2>/dev/null || echo "Failed"
echo ""

echo "=== DISK SPACE ==="
df -h | grep -E "/$|/var"
echo ""

echo "=== MEMORY USAGE ==="
free -h
echo ""

echo "=== STATIC DIRECTORY ==="
if [ -d "/var/www/whatsapp_statics" ]; then
    ls -la /var/www/whatsapp_statics/
    if [ -d "/var/www/whatsapp_statics/qrcode" ]; then
        echo "QR files:"
        ls -la /var/www/whatsapp_statics/qrcode/ | head -5
    fi
else
    echo "Static directory not found"
fi

echo ""
echo "=== END OF REPORT ==="
