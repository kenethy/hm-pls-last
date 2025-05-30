#!/bin/bash

# =============================================================================
# Check WhatsApp API Status di VPS
# =============================================================================

echo "🔍 Checking WhatsApp API Status di VPS..."
echo "========================================"

# Check if production container exists
echo "1. Checking production container..."
if docker ps -a | grep -q "whatsapp-api-production"; then
    echo "✅ Production container found"
    docker ps --filter name=whatsapp-api-production --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
else
    echo "❌ Production container not found"
    echo "Available containers:"
    docker ps -a --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
fi

echo ""

# Check if local container exists (from previous setup)
echo "2. Checking local development container..."
if docker ps -a | grep -q "whatsapp-api-local"; then
    echo "✅ Local container found"
    docker ps --filter name=whatsapp-api-local --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}'
else
    echo "❌ Local container not found"
fi

echo ""

# Check if any container is running on port 3000
echo "3. Checking port 3000 usage..."
if ss -tlnp | grep -q ":3000"; then
    echo "✅ Port 3000 is in use:"
    ss -tlnp | grep ":3000"
else
    echo "❌ Port 3000 is not in use"
fi

echo ""

# Test internal API connection
echo "4. Testing internal API connection..."
if curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
    echo "✅ Internal API responding"
    echo "Response:"
    curl -s http://localhost:3000/app/devices | jq . 2>/dev/null || curl -s http://localhost:3000/app/devices
else
    echo "❌ Internal API not responding"
fi

echo ""

# Test external API connection (if port is open)
echo "5. Testing external API connection..."
if curl -s -f https://hartonomotor.xyz:3000/app/devices > /dev/null 2>&1; then
    echo "✅ External API responding"
else
    echo "❌ External API not responding (port might be closed)"
fi

echo ""

# Check firewall
echo "6. Checking firewall status..."
sudo ufw status | grep 3000 || echo "Port 3000 not explicitly allowed in firewall"

echo ""

# Check production deployment directory
echo "7. Checking production deployment..."
if [[ -d "/opt/whatsapp-api-production" ]]; then
    echo "✅ Production directory exists"
    ls -la /opt/whatsapp-api-production/
    
    if [[ -f "/opt/whatsapp-api-production/config/.env" ]]; then
        echo ""
        echo "Production config exists:"
        grep -E "(USERNAME|PORT)" /opt/whatsapp-api-production/config/.env 2>/dev/null || echo "Config file not readable"
    fi
else
    echo "❌ Production directory not found"
    echo "Available directories in /opt:"
    ls -la /opt/ 2>/dev/null || echo "Cannot access /opt"
fi

echo ""
echo "🎯 Recommendations:"
echo "=================="

# Provide recommendations based on findings
if docker ps | grep -q "whatsapp-api-production"; then
    echo "✅ Production container is running"
    echo "   → Update Laravel .env: WHATSAPP_API_URL=http://127.0.0.1:3000"
elif docker ps | grep -q "whatsapp-api-local"; then
    echo "⚠️  Local container is running instead of production"
    echo "   → Stop local: docker stop whatsapp-api-local"
    echo "   → Start production: /opt/whatsapp-api-production/start.sh"
elif docker ps -a | grep -q "whatsapp-api"; then
    echo "⚠️  Container exists but not running"
    echo "   → Start container: docker start [container-name]"
    echo "   → Or use: /opt/whatsapp-api-production/start.sh"
else
    echo "❌ No WhatsApp API container found"
    echo "   → Run production deployment: ./deploy-whatsapp-production.sh"
    echo "   → Or start existing: /opt/whatsapp-api-production/start.sh"
fi

echo ""
echo "🔧 Quick fixes:"
echo "==============="
echo "1. Start production API: /opt/whatsapp-api-production/start.sh"
echo "2. Update Laravel .env: WHATSAPP_API_URL=http://127.0.0.1:3000"
echo "3. Clear Laravel cache: php artisan config:clear && php artisan config:cache"
echo "4. Test again: curl http://127.0.0.1:3000/app/devices"
