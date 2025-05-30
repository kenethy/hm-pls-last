<?php

/*
|--------------------------------------------------------------------------
| WhatsApp API Routes
|--------------------------------------------------------------------------
|
| Tambahkan routes ini ke routes/web.php
|
*/

use App\Http\Controllers\WhatsAppQRController;

// WhatsApp QR Code Routes
Route::prefix('whatsapp')->name('whatsapp.')->group(function () {
    // Halaman QR Generator
    Route::get('/qr-generator', [WhatsAppQRController::class, 'index'])->name('qr-generator');
    
    // API Endpoints
    Route::post('/generate-qr', [WhatsAppQRController::class, 'generateFreshQR'])->name('generate-qr');
    Route::post('/check-status', [WhatsAppQRController::class, 'checkStatus'])->name('check-status');
    Route::post('/send-message', [WhatsAppQRController::class, 'sendMessage'])->name('send-message');
});

// Direct access route (optional)
Route::get('/whatsapp-qr', [WhatsAppQRController::class, 'index'])->name('whatsapp-qr');

// Admin routes (jika menggunakan middleware auth)
Route::middleware(['auth'])->group(function () {
    Route::prefix('admin/whatsapp')->name('admin.whatsapp.')->group(function () {
        Route::get('/dashboard', [WhatsAppQRController::class, 'index'])->name('dashboard');
        Route::post('/generate-qr', [WhatsAppQRController::class, 'generateFreshQR'])->name('generate-qr');
        Route::post('/check-status', [WhatsAppQRController::class, 'checkStatus'])->name('check-status');
        Route::post('/send-message', [WhatsAppQRController::class, 'sendMessage'])->name('send-message');
    });
});
