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
