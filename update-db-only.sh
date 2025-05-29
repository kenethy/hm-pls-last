#!/bin/bash

# Update WhatsApp Database Configuration Only
# This script only updates the database configuration

echo "📝 Updating WhatsApp database configuration..."

# Check if app container is running
if ! docker-compose ps | grep -q "hartono-app.*Up"; then
    echo "❌ Laravel app container is not running. Please start it first:"
    echo "docker-compose up -d"
    exit 1
fi

echo "✅ Laravel app container is running"
echo ""

# Update database using artisan tinker inside container
echo "🔧 Updating WhatsApp configuration..."

docker-compose exec -T app php artisan tinker --execute="
use App\Models\WhatsAppConfig;

echo \"Checking current WhatsApp configuration...\n\";

\$config = WhatsAppConfig::getActive();

if (\$config) {
    echo \"Found existing configuration: \" . \$config->name . \"\n\";
    echo \"Current API URL: \" . \$config->api_url . \"\n\";
    
    \$config->update([
        'name' => 'Production WhatsApp API (Domain-based)',
        'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
        'api_username' => 'admin',
        'api_password' => 'HartonoMotor2025!',
        'webhook_secret' => 'HartonoMotorWebhookSecret2025',
        'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
        'is_active' => true,
        'notes' => 'Updated to use domain-based reverse proxy instead of direct port access',
    ]);
    
    echo \"✅ Configuration updated successfully!\n\";
    echo \"New API URL: https://hartonomotor.xyz/whatsapp-api\n\";
    
} else {
    echo \"No existing configuration found. Creating new one...\n\";
    
    \$config = WhatsAppConfig::create([
        'name' => 'Production WhatsApp API (Domain-based)',
        'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
        'api_username' => 'admin',
        'api_password' => 'HartonoMotor2025!',
        'webhook_secret' => 'HartonoMotorWebhookSecret2025',
        'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
        'is_active' => true,
        'notes' => 'Created with domain-based reverse proxy configuration',
    ]);
    
    echo \"✅ Configuration created successfully!\n\";
    echo \"API URL: https://hartonomotor.xyz/whatsapp-api\n\";
}

echo \"Webhook URL: https://hartonomotor.xyz/api/whatsapp/webhook\n\";
echo \"Configuration ID: \" . \$config->id . \"\n\";
"

echo ""
echo "✅ Database update completed!"
echo ""
echo "📝 Next steps:"
echo "1. Restart containers: docker-compose restart"
echo "2. Test WhatsApp auth: https://hartonomotor.xyz/whatsapp-auth.html"
