<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp API integration with Hartono Motor system
    |
    */

    // API URL - Internal VPS communication
    'api_url' => env('WHATSAPP_API_URL', 'http://localhost:3000'),
    
    // Basic Authentication
    'basic_auth' => [
        'username' => env('WHATSAPP_BASIC_AUTH_USERNAME', 'admin'),
        'password' => env('WHATSAPP_BASIC_AUTH_PASSWORD', ''),
    ],
    
    // Webhook Configuration
    'webhook' => [
        'secret' => env('WHATSAPP_WEBHOOK_SECRET', ''),
        'url' => env('WHATSAPP_WEBHOOK_URL', ''),
    ],
    
    // Timeout Settings
    'timeout' => [
        'qr_generation' => 30, // seconds
        'status_check' => 10,  // seconds
        'send_message' => 30,  // seconds
    ],
    
    // QR Code Settings
    'qr' => [
        'default_duration' => 30, // seconds
        'auto_refresh' => true,
        'cleanup_old' => true,
    ],
    
    // Logging
    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING_ENABLED', true),
        'channel' => env('WHATSAPP_LOG_CHANNEL', 'daily'),
    ],
    
    // Follow-up Messages
    'follow_up' => [
        'enabled' => env('WHATSAPP_FOLLOW_UP_ENABLED', false),
        'delay_minutes' => env('WHATSAPP_FOLLOW_UP_DELAY', 60),
        'templates' => [
            'service_completed' => 'Terima kasih telah menggunakan layanan Hartono Motor. Bagaimana pengalaman service Anda hari ini?',
            'appointment_reminder' => 'Halo, ini adalah pengingat untuk appointment service Anda besok di Hartono Motor.',
        ],
    ],
];
