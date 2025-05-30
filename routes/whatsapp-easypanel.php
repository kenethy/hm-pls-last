<?php

/*
|--------------------------------------------------------------------------
| WhatsApp Easy Panel Integration Routes
|--------------------------------------------------------------------------
|
| Routes untuk integrasi WhatsApp API via Easy Panel
| Tambahkan ke routes/web.php
|
*/

use App\Http\Controllers\EasyPanelWhatsAppController;

// WhatsApp Easy Panel Integration Routes
Route::prefix('whatsapp-easypanel')->name('whatsapp-easypanel.')->group(function () {
    
    // Test koneksi ke WhatsApp API
    Route::get('/test-connection', [EasyPanelWhatsAppController::class, 'testConnection'])
        ->name('test-connection');
    
    // Kirim pesan follow-up otomatis (dipanggil ketika service selesai)
    Route::post('/send-service-completed', [EasyPanelWhatsAppController::class, 'sendServiceCompletedMessage'])
        ->name('send-service-completed');
    
    // Kirim pesan custom (untuk testing)
    Route::post('/send-custom-message', [EasyPanelWhatsAppController::class, 'sendCustomMessage'])
        ->name('send-custom-message');
});

// Webhook endpoint untuk menerima pesan masuk dari WhatsApp
Route::post('/webhook/whatsapp', [EasyPanelWhatsAppController::class, 'handleWebhook'])
    ->name('whatsapp.webhook');

// Routes untuk admin (dengan middleware auth)
Route::middleware(['auth'])->group(function () {
    Route::prefix('admin/whatsapp')->name('admin.whatsapp.')->group(function () {
        
        // Dashboard WhatsApp
        Route::get('/dashboard', function () {
            return view('admin.whatsapp.dashboard');
        })->name('dashboard');
        
        // Test koneksi
        Route::get('/test', [EasyPanelWhatsAppController::class, 'testConnection'])
            ->name('test');
        
        // Kirim pesan test
        Route::post('/send-test', [EasyPanelWhatsAppController::class, 'sendCustomMessage'])
            ->name('send-test');
    });
});
