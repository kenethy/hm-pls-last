#!/bin/bash

echo "🔍 Checking WhatsApp API Access..."
echo ""

# Check if containers are running
echo "📋 Container Status:"
docker-compose ps | grep whatsapp-api

echo ""
echo "🔗 Testing Internal Access (from Laravel container):"
docker-compose exec app curl -s http://whatsapp-api:3000/app/devices | head -3

echo ""
echo "🌐 Testing External Access (from host):"
echo "Trying http://localhost:3000/app/devices..."
curl -s http://localhost:3000/app/devices | head -3

echo ""
echo "🌍 Testing Domain Access:"
echo "Trying http://hartonomotor.xyz:3000/app/devices..."
curl -s http://hartonomotor.xyz:3000/app/devices | head -3

echo ""
echo "🔧 Network Configuration:"
echo "Checking if port 3000 is listening..."
netstat -tlnp | grep :3000 || echo "Port 3000 not found in netstat"

echo ""
echo "🔥 Firewall Status (if ufw is installed):"
if command -v ufw &> /dev/null; then
    ufw status | grep 3000 || echo "Port 3000 not explicitly allowed in UFW"
else
    echo "UFW not installed"
fi

echo ""
echo "📝 Recommendations:"
echo "1. If localhost:3000 works but hartonomotor.xyz:3000 doesn't:"
echo "   - Check DNS settings for hartonomotor.xyz"
echo "   - Verify domain points to your VPS IP"
echo ""
echo "2. If neither works:"
echo "   - Check VPS firewall settings"
echo "   - Ensure port 3000 is open"
echo "   - Verify Docker container is running"
echo ""
echo "3. For browser access, use:"
echo "   - http://hartonomotor.xyz:3000 (if domain configured)"
echo "   - http://YOUR_VPS_IP:3000 (direct IP access)"
