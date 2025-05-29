<?php

/**
 * Update WhatsApp configuration in database
 * Simple script to update API URL to use domain-based reverse proxy
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\WhatsAppConfig;

echo "🔧 Updating WhatsApp configuration in database...\n";

try {
    // Get the active WhatsApp configuration
    $config = WhatsAppConfig::getActive();
    
    if ($config) {
        // Update existing configuration
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
        echo "   - Old API URL: " . ($config->getOriginal('api_url') ?? 'N/A') . "\n";
        echo "   - New API URL: https://hartonomotor.xyz/whatsapp-api\n";
        
    } else {
        // Create new configuration if none exists
        $config = WhatsAppConfig::create([
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
    }
    
    echo "   - Webhook URL: https://hartonomotor.xyz/api/whatsapp/webhook\n";
    echo "   - Configuration ID: " . $config->id . "\n";
    
} catch (Exception $e) {
    echo "❌ Error updating WhatsApp configuration: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ Database update completed successfully!\n";
