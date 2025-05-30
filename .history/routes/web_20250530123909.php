<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SparePartController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AboutController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MechanicServiceHistoryController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\SitemapController;

// Home
Route::get('/', [HomeController::class, 'index'])->name('home');

// Services
Route::get('/servis', [ServiceController::class, 'index'])->name('services');

// Spare Parts
Route::get('/spareparts', [SparePartController::class, 'index'])->name('spare-parts');
Route::get('/sparepart/kategori/{slug}', [SparePartController::class, 'category'])->name('spare-parts.category');
Route::get('/sparepart/{slug}', [SparePartController::class, 'show'])->name('spare-parts.show');

// Booking
Route::get('/booking', [BookingController::class, 'index'])->name('booking');
Route::post('/booking', [BookingController::class, 'store'])->name('booking.store');

// About
Route::get('/tentang', [AboutController::class, 'index'])->name('about');

// Gallery
Route::get('/galeri', [GalleryController::class, 'index'])->name('gallery');
Route::get('/galeri/{slug}', [GalleryController::class, 'show'])->name('gallery.show');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{slug}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Contact
Route::get('/kontak', [ContactController::class, 'index'])->name('contact');
Route::post('/kontak', [ContactController::class, 'store'])->name('contact.store');

// Promos
Route::get('/promo', [PromoController::class, 'index'])->name('promos');
Route::get('/promo/{slug}', [PromoController::class, 'show'])->name('promos.show');

// Mechanic Service History
Route::get('/admin/mechanic-reports/{id}/services', [MechanicServiceHistoryController::class, 'show'])
    ->name('mechanic.services.history')
    ->middleware(['web', 'auth']);

// Mechanic Rating System
Route::prefix('api')->group(function () {
    Route::get('/ratings/service/{serviceId}/modal', [App\Http\Controllers\MechanicRatingController::class, 'showRatingModal'])
        ->name('ratings.modal');
    Route::post('/ratings/submit', [App\Http\Controllers\MechanicRatingController::class, 'submitRating'])
        ->name('ratings.submit');
    Route::get('/ratings/service/{serviceId}', [App\Http\Controllers\MechanicRatingController::class, 'getServiceRatings'])
        ->name('ratings.service');
    Route::get('/check-rating-popup', [App\Http\Controllers\MechanicRatingController::class, 'checkRatingPopup'])
        ->name('ratings.check-popup');

    // WhatsApp Webhook
    Route::post('/whatsapp/webhook', [App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])
        ->name('whatsapp.webhook');

    // Debug route to test session setting
    Route::get('/test-rating-session', function () {
        session(['show_rating_modal_direct' => true]);
        session(['pending_rating_service' => [
            'service_id' => 999,
            'customer_name' => 'Test Customer',
            'service_type' => 'Test Service',
            'vehicle_info' => 'Test Vehicle',
            'mechanics' => [
                ['id' => 1, 'name' => 'Test Mechanic', 'specialization' => 'Test']
            ]
        ]]);

        return response()->json([
            'message' => 'Test session data set',
            'session_data' => [
                'show_rating_modal_direct' => session('show_rating_modal_direct'),
                'pending_rating_service' => session('pending_rating_service')
            ]
        ]);
    });
});

// Sitemap
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/sitemap/main', function () {
    $content = view('sitemap.main');
    return response($content, 200)->header('Content-Type', 'text/xml');
});
Route::get('/sitemap/posts', [SitemapController::class, 'posts']);
Route::get('/sitemap/categories', [SitemapController::class, 'categories']);
Route::get('/sitemap/tags', [SitemapController::class, 'tags']);
Route::get('/sitemap/promos', [SitemapController::class, 'promos']);

// Traditional file upload routess
Route::post('/admin/gallery/upload', [App\Http\Controllers\Admin\GalleryUploadController::class, 'upload'])
    ->middleware(['web', 'auth'])
    ->name('admin.gallery.upload');

Route::post('/admin/gallery/upload-multiple', [App\Http\Controllers\Admin\GalleryUploadController::class, 'uploadMultiple'])
    ->middleware(['web', 'auth'])
    ->name('admin.gallery.upload.multiple');

// Note: Custom upload routes removed to prevent conflicts with standard Filament uploads
// Standard Livewire file uploads now work directly through Filament's built-in endpoints

// Simple Gallery page
Route::get('/admin/simple-gallery', function () {
    return view('admin.simple-gallery-standalone');
})->middleware(['web', 'auth'])->name('admin.simple-gallery');

// Service Reports
Route::get('/laporan/{code}', [App\Http\Controllers\ServiceReportController::class, 'show'])
    ->name('service-reports.show');
Route::get('/laporan/{code}/download', [App\Http\Controllers\ServiceReportController::class, 'download'])
    ->name('service-reports.download');
Route::get('/laporan/{code}/certificate', [App\Http\Controllers\ServiceReportController::class, 'downloadCertificate'])
    ->name('service-reports.certificate');
Route::get('/laporan-kedaluwarsa', [App\Http\Controllers\ServiceReportController::class, 'expired'])
    ->name('service-reports.expired');

// QR Image serving route
Route::get('/qr-image/{filename}', function ($filename) {
    // Sanitize filename
    $filename = basename($filename);
    if (!preg_match('/^scan-qr-[a-f0-9\-]+\.png$/', $filename)) {
        abort(404, 'Invalid QR filename');
    }

    // Multiple possible paths
    $possiblePaths = [
        '/var/www/whatsapp_statics/qrcode/' . $filename,
        '/var/www/html/whatsapp_statics/qrcode/' . $filename,
        public_path('qrcode/whatsapp/' . $filename),
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            return response()->file($path, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ]);
        }
    }

    abort(404, 'QR image not found');
})->name('qr.image');

// QR Latest API
Route::get('/qr-latest', function () {
    clearstatcache();

    $possiblePaths = [
        '/var/www/whatsapp_statics/qrcode/',
        '/var/www/html/whatsapp_statics/qrcode/',
        '/app/whatsapp_statics/qrcode/',
        '/hm-new/whatsapp_statics/qrcode/',
        getcwd() . '/whatsapp_statics/qrcode/',
        realpath('.') . '/whatsapp_statics/qrcode/',
    ];

    $workingPath = null;
    $debugInfo = [];

    foreach ($possiblePaths as $path) {
        $debugInfo[$path] = [
            'exists' => is_dir($path),
            'readable' => is_readable($path),
            'files' => is_dir($path) ? count(glob($path . 'scan-qr-*.png')) : 0
        ];

        if (is_dir($path) && is_readable($path)) {
            $files = glob($path . 'scan-qr-*.png');
            if (!empty($files)) {
                $workingPath = $path;
                break;
            }
        }
    }

    if (!$workingPath) {
        return response()->json([
            'error' => 'QR directory not found in any location',
            'debug_paths' => $debugInfo,
            'current_dir' => getcwd(),
            'real_path' => realpath('.'),
        ], 404);
    }

    $files = glob($workingPath . 'scan-qr-*.png');
    if (empty($files)) {
        return response()->json([
            'error' => 'No QR files found',
            'path' => $workingPath,
            'debug_paths' => $debugInfo
        ], 404);
    }

    // Sort by modification time (newest first)
    $filesWithTime = [];
    foreach ($files as $file) {
        $filesWithTime[] = [
            'file' => $file,
            'mtime' => filemtime($file)
        ];
    }

    usort($filesWithTime, function ($a, $b) {
        return $b['mtime'] - $a['mtime'];
    });

    $latestFile = $filesWithTime[0]['file'];
    $filename = basename($latestFile);
    $mtime = $filesWithTime[0]['mtime'];

    try {
        // Return both base64 and image URL
        $imageData = base64_encode(file_get_contents($latestFile));
        $imageUrl = url('/qr-image/' . $filename);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'qr_code' => 'data:image/png;base64,' . $imageData,
            'qr_image_url' => $imageUrl,
            'created_at' => date('Y-m-d H:i:s', $mtime),
            'size' => filesize($latestFile),
            'path_used' => $workingPath,
            'total_qr_files' => count($files),
            'age_seconds' => time() - $mtime,
            'debug_info' => [
                'working_path' => $workingPath,
                'current_dir' => getcwd(),
                'image_url' => $imageUrl,
                'latest_files' => array_slice(array_map(function ($item) {
                    return [
                        'file' => basename($item['file']),
                        'created' => date('Y-m-d H:i:s', $item['mtime']),
                        'age_seconds' => time() - $item['mtime']
                    ];
                }, $filesWithTime), 0, 3)
            ]
        ]);
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Failed to read QR file',
            'message' => $e->getMessage(),
            'file' => $latestFile
        ], 500);
    }
});

// Generate fresh QR API
Route::get('/generate-fresh-qr', function () {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://hartonomotor.xyz/whatsapp-api/app/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic YWRtaW46SGFydG9ub01vdG9yMjAyNSE='
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $data = json_decode($response, true);
            return response()->json([
                'success' => true,
                'whatsapp_response' => $data,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } else {
            return response()->json(['error' => 'Failed to generate QR', 'http_code' => $httpCode], 500);
        }
    } catch (Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});



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
