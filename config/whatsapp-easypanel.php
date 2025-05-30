<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Easy Panel Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for WhatsApp API integration via Easy Panel
    |
    */

    // Easy Panel WhatsApp API URL (akan dapat subdomain dari Easy Panel)
    'easypanel_api_url' => env('WHATSAPP_EASYPANEL_API_URL', 'https://your-subdomain.easypanel.host:3000'),
    
    // Basic Authentication
    'basic_auth' => [
        'username' => env('WHATSAPP_BASIC_AUTH_USERNAME', 'admin'),
        'password' => env('WHATSAPP_BASIC_AUTH_PASSWORD', 'hartonomotor123'),
    ],
    
    // Webhook Configuration
    'webhook' => [
        'url' => env('WHATSAPP_WEBHOOK_URL', 'https://hartonomotor.xyz/webhook/whatsapp'),
        'secret' => env('WHATSAPP_WEBHOOK_SECRET', ''),
    ],
    
    // Message Templates
    'templates' => [
        'service_completed' => [
            'enabled' => true,
            'delay_minutes' => 5, // Kirim 5 menit setelah service selesai
        ],
        
        'appointment_reminder' => [
            'enabled' => true,
            'delay_hours' => 24, // Kirim 24 jam sebelum appointment
        ],
        
        'follow_up_feedback' => [
            'enabled' => true,
            'delay_days' => 1, // Kirim 1 hari setelah service untuk minta feedback
        ],
    ],
    
    // Auto Reply Settings
    'auto_reply' => [
        'enabled' => env('WHATSAPP_AUTO_REPLY_ENABLED', false),
        'business_hours' => [
            'start' => '08:00',
            'end' => '17:00',
        ],
        'responses' => [
            'greeting' => 'Halo! Terima kasih telah menghubungi Hartono Motor. Kami akan segera merespon pesan Anda.',
            'outside_hours' => 'Terima kasih telah menghubungi Hartono Motor. Saat ini di luar jam operasional (08:00-17:00). Pesan Anda akan kami respon pada jam kerja.',
        ],
    ],
    
    // Logging
    'logging' => [
        'enabled' => env('WHATSAPP_LOGGING_ENABLED', true),
        'channel' => env('WHATSAPP_LOG_CHANNEL', 'daily'),
    ],
];
