#!/bin/bash

# Laravel Configuration Update Script for WhatsApp Integration
# Run this script on your VPS in the Laravel project directory

set -e  # Exit on any error

echo "⚙️ Updating Laravel configuration for WhatsApp integration..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LARAVEL_DIR="/var/www/hartonomotor.xyz"
DOMAIN="hartonomotor.xyz"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  Laravel Dir: ${LARAVEL_DIR}"
echo -e "  Domain: ${DOMAIN}"
echo ""

# Change to Laravel directory
cd ${LARAVEL_DIR}

# Step 1: Git pull latest changes
echo -e "${YELLOW}📥 Step 1: Pulling latest changes from repository...${NC}"
if git pull origin main; then
    echo -e "${GREEN}✅ Git pull completed${NC}"
else
    echo -e "${RED}❌ Git pull failed${NC}"
    echo -e "${YELLOW}Please resolve any conflicts and try again${NC}"
    exit 1
fi

# Step 2: Install/update Composer dependencies
echo -e "${YELLOW}📦 Step 2: Installing Composer dependencies...${NC}"
if command -v composer >/dev/null 2>&1; then
    composer install --no-dev --optimize-autoloader
    echo -e "${GREEN}✅ Composer dependencies installed${NC}"
else
    echo -e "${RED}❌ Composer not found${NC}"
    echo -e "${YELLOW}Please install Composer first${NC}"
    exit 1
fi

# Step 3: Run database migrations
echo -e "${YELLOW}🗄️ Step 3: Running database migrations...${NC}"
if php artisan migrate --force; then
    echo -e "${GREEN}✅ Database migrations completed${NC}"
else
    echo -e "${RED}❌ Database migrations failed${NC}"
    exit 1
fi

# Step 4: Seed WhatsApp integration data
echo -e "${YELLOW}🌱 Step 4: Seeding WhatsApp integration data...${NC}"
if php artisan db:seed --class=WhatsAppIntegrationSeeder --force; then
    echo -e "${GREEN}✅ WhatsApp integration data seeded${NC}"
else
    echo -e "${YELLOW}⚠️ Seeding failed (data might already exist)${NC}"
fi

# Step 5: Clear all caches
echo -e "${YELLOW}🧹 Step 5: Clearing application caches...${NC}"
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
echo -e "${GREEN}✅ Caches cleared${NC}"

# Step 6: Optimize for production
echo -e "${YELLOW}⚡ Step 6: Optimizing for production...${NC}"
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo -e "${GREEN}✅ Application optimized${NC}"

# Step 7: Set proper permissions
echo -e "${YELLOW}🔐 Step 7: Setting proper permissions...${NC}"
sudo chown -R www-data:www-data ${LARAVEL_DIR}
sudo chmod -R 755 ${LARAVEL_DIR}
sudo chmod -R 775 ${LARAVEL_DIR}/storage
sudo chmod -R 775 ${LARAVEL_DIR}/bootstrap/cache
echo -e "${GREEN}✅ Permissions set${NC}"

# Step 8: Update WhatsApp configuration in database
echo -e "${YELLOW}🔧 Step 8: Updating WhatsApp configuration...${NC}"

# Create a temporary PHP script to update the database
cat > update_whatsapp_config.php << 'EOF'
<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\WhatsAppConfig;

try {
    // Update or create WhatsApp configuration
    $config = WhatsAppConfig::first();
    
    if ($config) {
        $config->update([
            'name' => 'Production WhatsApp API',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
        ]);
        echo "✅ WhatsApp configuration updated\n";
    } else {
        WhatsAppConfig::create([
            'name' => 'Production WhatsApp API',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
        ]);
        echo "✅ WhatsApp configuration created\n";
    }
} catch (Exception $e) {
    echo "❌ Error updating WhatsApp configuration: " . $e->getMessage() . "\n";
    exit(1);
}
EOF

if php update_whatsapp_config.php; then
    echo -e "${GREEN}✅ WhatsApp configuration updated in database${NC}"
else
    echo -e "${RED}❌ Failed to update WhatsApp configuration${NC}"
fi

# Clean up temporary file
rm -f update_whatsapp_config.php

# Step 9: Test Laravel application
echo -e "${YELLOW}🧪 Step 9: Testing Laravel application...${NC}"
if php artisan about >/dev/null 2>&1; then
    echo -e "${GREEN}✅ Laravel application is working${NC}"
else
    echo -e "${RED}❌ Laravel application has issues${NC}"
    echo -e "${YELLOW}Please check the logs: tail -f storage/logs/laravel.log${NC}"
fi

# Step 10: Display completion message
echo -e "${GREEN}🎉 Laravel configuration update completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Login to admin panel: ${YELLOW}https://${DOMAIN}/admin${NC}"
echo -e "2. Go to 'WhatsApp Integration' → 'Konfigurasi WhatsApp'"
echo -e "3. Test connection (should work now)"
echo -e "4. Scan QR code: ${YELLOW}https://${DOMAIN}/whatsapp-api/app/login${NC}"
echo ""
echo -e "${BLUE}🔧 Useful Commands:${NC}"
echo -e "  View Laravel logs: ${YELLOW}tail -f ${LARAVEL_DIR}/storage/logs/laravel.log${NC}"
echo -e "  Check application status: ${YELLOW}php artisan about${NC}"
echo -e "  Clear cache: ${YELLOW}php artisan cache:clear${NC}"
echo ""
echo -e "${GREEN}✅ Laravel configuration script completed successfully!${NC}"
