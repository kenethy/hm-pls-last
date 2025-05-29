#!/bin/bash

# Quick Fix for WhatsApp API Reverse Proxy
# Simple script to fix WhatsApp API access through domain

set -e

echo "🔧 Quick Fix: WhatsApp API Reverse Proxy"
echo "========================================"
echo ""

# Step 1: Update database configuration
echo "📝 Step 1: Updating WhatsApp configuration..."

cat > temp_update_config.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WhatsAppConfig;

try {
    $config = WhatsAppConfig::getActive();
    
    if ($config) {
        $config->update([
            'name' => 'Production WhatsApp API (Domain-based)',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
            'notes' => 'Updated to use domain-based reverse proxy',
        ]);
        echo "✅ WhatsApp configuration updated successfully!\n";
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
        echo "✅ WhatsApp configuration created successfully!\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

php temp_update_config.php
rm temp_update_config.php

echo ""

# Step 2: Restart containers
echo "🐳 Step 2: Restarting Docker containers..."
docker-compose down
docker-compose up -d

echo "⏳ Waiting for containers to start..."
sleep 30

echo ""

# Step 3: Test connectivity
echo "🧪 Step 3: Testing connectivity..."

# Test if containers are running
if docker-compose ps | grep -q "hartono-webserver.*Up"; then
    echo "✅ Nginx container is running"
else
    echo "❌ Nginx container is not running"
fi

if docker-compose ps | grep -q "hartono-whatsapp-api.*Up"; then
    echo "✅ WhatsApp API container is running"
else
    echo "❌ WhatsApp API container is not running"
fi

echo ""

# Step 4: Test endpoints
echo "🌐 Step 4: Testing endpoints..."

# Test auth page
if curl -s -k https://hartonomotor.xyz/whatsapp-auth.html | grep -q "WhatsApp Authentication"; then
    echo "✅ WhatsApp auth page accessible"
else
    echo "❌ WhatsApp auth page not accessible"
fi

# Test API endpoint
echo "Testing API endpoint..."
sleep 5

if curl -s -k https://hartonomotor.xyz/whatsapp-api/app/devices >/dev/null 2>&1; then
    echo "✅ WhatsApp API endpoint accessible via HTTPS"
elif curl -s http://hartonomotor.xyz/whatsapp-api/app/devices >/dev/null 2>&1; then
    echo "⚠️ WhatsApp API endpoint accessible via HTTP"
else
    echo "❌ WhatsApp API endpoint not accessible"
    echo "Checking container logs..."
    docker-compose logs --tail=10 whatsapp-api
fi

echo ""
echo "🎉 Quick fix completed!"
echo ""
echo "📝 Next steps:"
echo "1. Go to: https://hartonomotor.xyz/whatsapp-auth.html"
echo "2. Click 'Buka WhatsApp Authentication'"
echo "3. Scan QR code with WhatsApp"
echo "4. Test in admin panel: https://hartonomotor.xyz/admin/whats-app-configs"
echo ""
