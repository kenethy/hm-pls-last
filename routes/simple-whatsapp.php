<?php

/*
|--------------------------------------------------------------------------
| Simple WhatsApp Routes
|--------------------------------------------------------------------------
|
| Tambahkan routes ini ke routes/web.php
|
*/

use App\Http\Controllers\SimpleWhatsAppController;

// Simple WhatsApp Routes (Laravel-native)
Route::prefix('simple-whatsapp')->name('simple-whatsapp.')->group(function () {
    // Halaman QR Generator
    Route::get('/qr-generator', [SimpleWhatsAppController::class, 'index'])->name('qr-generator');
    
    // API Endpoints
    Route::post('/generate-qr', [SimpleWhatsAppController::class, 'generateQR'])->name('generate-qr');
    Route::post('/check-status', [SimpleWhatsAppController::class, 'checkStatus'])->name('check-status');
    Route::post('/send-message', [SimpleWhatsAppController::class, 'sendMessage'])->name('send-message');
    Route::post('/send-follow-up', [SimpleWhatsAppController::class, 'sendFollowUp'])->name('send-follow-up');
});

// Direct access route
Route::get('/simple-whatsapp', [SimpleWhatsAppController::class, 'index'])->name('simple-whatsapp');
