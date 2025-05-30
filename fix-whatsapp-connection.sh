#!/bin/bash

# =============================================================================
# Quick Fix WhatsApp API Connection
# =============================================================================

echo "🔧 Quick Fix WhatsApp API Connection..."
echo "======================================"

# Step 1: Stop any existing containers
echo "1. Stopping existing WhatsApp containers..."
docker stop whatsapp-api-local 2>/dev/null || echo "Local container not running"
docker stop whatsapp-api-production 2>/dev/null || echo "Production container not running"

# Step 2: Start production container
echo "2. Starting production WhatsApp API..."
if [[ -f "/opt/whatsapp-api-production/start.sh" ]]; then
    /opt/whatsapp-api-production/start.sh
    echo "✅ Production container started"
else
    echo "❌ Production deployment not found"
    echo "Please run: ./deploy-whatsapp-production.sh"
    exit 1
fi

# Step 3: Wait for container to be ready
echo "3. Waiting for API to be ready..."
sleep 10

# Step 4: Test connection
echo "4. Testing API connection..."
for i in {1..5}; do
    if curl -s -f http://127.0.0.1:3000/app/devices > /dev/null 2>&1; then
        echo "✅ API is responding on 127.0.0.1:3000"
        API_URL="http://127.0.0.1:3000"
        break
    elif curl -s -f http://localhost:3000/app/devices > /dev/null 2>&1; then
        echo "✅ API is responding on localhost:3000"
        API_URL="http://localhost:3000"
        break
    else
        echo "⏳ Attempt $i/5 - API not ready yet..."
        sleep 5
    fi
done

if [[ -z "$API_URL" ]]; then
    echo "❌ API is not responding after 5 attempts"
    echo "Check container logs: docker logs whatsapp-api-production"
    exit 1
fi

# Step 5: Update Laravel .env
echo "5. Updating Laravel configuration..."

# Find Laravel directory
LARAVEL_DIRS=(
    "/var/www/html"
    "/var/www/hartonomotor.xyz"
    "/home/*/hartonomotor.xyz"
    "/opt/hartonomotor.xyz"
)

LARAVEL_DIR=""
for dir in "${LARAVEL_DIRS[@]}"; do
    if [[ -f "$dir/.env" ]] && [[ -f "$dir/artisan" ]]; then
        LARAVEL_DIR="$dir"
        break
    fi
done

if [[ -z "$LARAVEL_DIR" ]]; then
    echo "❌ Laravel directory not found"
    echo "Please manually update .env with: WHATSAPP_API_URL=$API_URL"
    exit 1
fi

echo "Found Laravel at: $LARAVEL_DIR"

# Backup .env
cp "$LARAVEL_DIR/.env" "$LARAVEL_DIR/.env.backup.$(date +%Y%m%d_%H%M%S)"

# Update or add WHATSAPP_API_URL
if grep -q "WHATSAPP_API_URL=" "$LARAVEL_DIR/.env"; then
    sed -i "s|WHATSAPP_API_URL=.*|WHATSAPP_API_URL=$API_URL|" "$LARAVEL_DIR/.env"
    echo "✅ Updated existing WHATSAPP_API_URL"
else
    echo "WHATSAPP_API_URL=$API_URL" >> "$LARAVEL_DIR/.env"
    echo "✅ Added WHATSAPP_API_URL to .env"
fi

# Step 6: Clear Laravel cache
echo "6. Clearing Laravel cache..."
cd "$LARAVEL_DIR"
php artisan config:clear
php artisan config:cache
echo "✅ Laravel cache cleared"

# Step 7: Test Laravel connection
echo "7. Testing Laravel → WhatsApp API connection..."
if php artisan tinker --execute="echo \Illuminate\Support\Facades\Http::get(config('whatsapp.api_url') . '/app/devices')->successful() ? 'SUCCESS' : 'FAILED';" 2>/dev/null | grep -q "SUCCESS"; then
    echo "✅ Laravel can connect to WhatsApp API"
else
    echo "❌ Laravel cannot connect to WhatsApp API"
    echo "Manual test: php artisan tinker"
    echo ">>> \Illuminate\Support\Facades\Http::get(config('whatsapp.api_url') . '/app/devices')->json()"
fi

echo ""
echo "🎉 Fix completed!"
echo "================"
echo "✅ WhatsApp API URL: $API_URL"
echo "✅ Laravel .env updated"
echo "✅ Cache cleared"
echo ""
echo "🌐 Test your QR generator now:"
echo "https://hartonomotor.xyz/whatsapp/qr-generator"
echo ""
echo "🔍 If still not working:"
echo "1. Check container: docker logs whatsapp-api-production"
echo "2. Check Laravel logs: tail -f $LARAVEL_DIR/storage/logs/laravel.log"
echo "3. Manual test: curl $API_URL/app/devices"
