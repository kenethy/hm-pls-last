<?php

/**
 * Script to update WhatsApp configuration to use domain-based reverse proxy
 * Run this script to fix the WhatsApp API URL configuration
 */

require_once 'vendor/autoload.php';

use App\Models\WhatsAppConfig;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🔧 Updating WhatsApp configuration...\n";

try {
    // Get the active WhatsApp configuration
    $config = WhatsAppConfig::getActive();
    
    if ($config) {
        // Update the configuration to use domain-based reverse proxy
        $config->update([
            'name' => 'Production WhatsApp API (Domain-based)',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
            'notes' => 'Updated to use domain-based reverse proxy instead of direct port access',
        ]);
        
        echo "✅ WhatsApp configuration updated successfully!\n";
        echo "   - API URL: https://hartonomotor.xyz/whatsapp-api\n";
        echo "   - Webhook URL: https://hartonomotor.xyz/api/whatsapp/webhook\n";
        
    } else {
        // Create new configuration if none exists
        WhatsAppConfig::create([
            'name' => 'Production WhatsApp API (Domain-based)',
            'api_url' => 'https://hartonomotor.xyz/whatsapp-api',
            'api_username' => 'admin',
            'api_password' => 'HartonoMotor2025!',
            'webhook_secret' => 'HartonoMotorWebhookSecret2025',
            'webhook_url' => 'https://hartonomotor.xyz/api/whatsapp/webhook',
            'is_active' => true,
            'notes' => 'Created with domain-based reverse proxy configuration',
        ]);
        
        echo "✅ WhatsApp configuration created successfully!\n";
        echo "   - API URL: https://hartonomotor.xyz/whatsapp-api\n";
        echo "   - Webhook URL: https://hartonomotor.xyz/api/whatsapp/webhook\n";
    }
    
    // Test the connection
    echo "\n🧪 Testing connection...\n";
    
    $whatsappService = new App\Services\WhatsAppService();
    $result = $whatsappService->testConnection();
    
    if ($result['success']) {
        echo "✅ Connection test successful: " . $result['message'] . "\n";
    } else {
        echo "❌ Connection test failed: " . $result['message'] . "\n";
        echo "   This is expected if the containers haven't been restarted yet.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error updating WhatsApp configuration: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n📋 Next steps:\n";
echo "1. Restart Docker containers: docker-compose restart\n";
echo "2. Test WhatsApp authentication: https://hartonomotor.xyz/whatsapp-auth.html\n";
echo "3. Verify API access: https://hartonomotor.xyz/whatsapp-api/app/devices\n";
echo "\n✅ Configuration update completed!\n";
