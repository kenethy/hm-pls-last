#!/bin/bash

# Simple WhatsApp Fix - Update configuration using Docker exec
# This script runs commands inside the Laravel container where database is accessible

set -e

echo "🔧 Simple WhatsApp API Fix"
echo "=========================="
echo ""

# Check if containers are running
echo "📋 Checking container status..."

if ! docker-compose ps | grep -q "hartono-app.*Up"; then
    echo "❌ Laravel app container is not running. Starting containers..."
    docker-compose up -d
    echo "⏳ Waiting for containers to start..."
    sleep 30
fi

if docker-compose ps | grep -q "hartono-app.*Up"; then
    echo "✅ Laravel app container is running"
else
    echo "❌ Laravel app container failed to start"
    exit 1
fi

echo ""

# Step 1: Update WhatsApp configuration using Docker exec
echo "📝 Step 1: Updating WhatsApp configuration in database..."

docker-compose exec -T app php artisan tinker --execute="
use App\Models\WhatsAppConfig;

try {
    \$config = WhatsAppConfig::getActive();
    
    if (\$config) {
        \$oldUrl = \$config->api_url;
        \$config->update([
            'name' => 'Production WhatsApp API (Domain-based)',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
            'notes' => 'Updated to use domain-based reverse proxy',
        ]);
        echo \"✅ WhatsApp configuration updated!\n\";
        echo \"   Old URL: \" . \$oldUrl . \"\n\";
        echo \"   New URL: https://hartonomotor.xyz/whatsapp-api\n\";
    } else {
        WhatsAppConfig::create([
            'name' => 'Production WhatsApp API (Domain-based)',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
            'notes' => 'Created with domain-based reverse proxy',
        ]);
        echo \"✅ WhatsApp configuration created!\n\";
        echo \"   API URL: https://hartonomotor.xyz/whatsapp-api\n\";
    }
} catch (Exception \$e) {
    echo \"❌ Error: \" . \$e->getMessage() . \"\n\";
}
"

echo ""

# Step 2: Restart containers to apply Nginx changes
echo "🐳 Step 2: Restarting containers to apply changes..."
docker-compose restart webserver whatsapp-api

echo "⏳ Waiting for services to restart..."
sleep 20

echo ""

# Step 3: Check container status
echo "🔍 Step 3: Checking container status..."

if docker-compose ps | grep -q "hartono-webserver.*Up"; then
    echo "✅ Nginx webserver is running"
else
    echo "❌ Nginx webserver is not running"
fi

if docker-compose ps | grep -q "hartono-whatsapp-api.*Up"; then
    echo "✅ WhatsApp API container is running"
else
    echo "❌ WhatsApp API container is not running"
    echo "Checking logs..."
    docker-compose logs --tail=10 whatsapp-api
fi

echo ""

# Step 4: Test endpoints
echo "🧪 Step 4: Testing endpoints..."

# Test auth page
echo "Testing WhatsApp auth page..."
if curl -s -k https://hartonomotor.xyz/whatsapp-auth.html | grep -q "WhatsApp Authentication"; then
    echo "✅ WhatsApp auth page is accessible"
else
    echo "❌ WhatsApp auth page is not accessible"
fi

# Test API endpoint
echo "Testing WhatsApp API endpoint..."
sleep 5

# Try HTTPS first
if curl -s -k https://hartonomotor.xyz/whatsapp-api/app/devices >/dev/null 2>&1; then
    echo "✅ WhatsApp API is accessible via HTTPS"
    echo "   URL: https://hartonomotor.xyz/whatsapp-api/app/devices"
elif curl -s http://hartonomotor.xyz/whatsapp-api/app/devices >/dev/null 2>&1; then
    echo "⚠️ WhatsApp API is accessible via HTTP (SSL issue)"
    echo "   URL: http://hartonomotor.xyz/whatsapp-api/app/devices"
else
    echo "❌ WhatsApp API endpoint is not accessible"
    echo ""
    echo "🔍 Debugging information:"
    echo "Checking internal connectivity..."
    if docker-compose exec -T app curl -s http://whatsapp-api:3000/app/devices >/dev/null 2>&1; then
        echo "✅ Internal container connectivity works"
        echo "❌ Issue is with Nginx reverse proxy configuration"
    else
        echo "❌ WhatsApp API container is not responding internally"
        echo "Container logs:"
        docker-compose logs --tail=15 whatsapp-api
    fi
fi

echo ""
echo "🎉 Fix completed!"
echo ""
echo "📝 Next steps:"
echo "1. Go to: https://hartonomotor.xyz/whatsapp-auth.html"
echo "2. Click 'Buka WhatsApp Authentication'"
echo "3. Should open: https://hartonomotor.xyz/whatsapp-api/app/login"
echo "4. Scan QR code with WhatsApp"
echo "5. Test in admin: https://hartonomotor.xyz/admin/whats-app-configs"
echo ""
echo "If there are still issues, check the container logs:"
echo "docker-compose logs whatsapp-api"
echo "docker-compose logs webserver"
