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

// Custom file upload route for admin forms (as a fallback)
Route::post('/admin/file-upload', [App\Http\Controllers\Admin\FileUploadController::class, 'upload'])
    ->middleware(['web', 'auth'])
    ->name('admin.file.upload');

// Custom upload routes for intercepted Livewire uploads
Route::post('/custom-upload', [App\Http\Controllers\Admin\CustomUploadController::class, 'handle'])
    ->middleware(['web', 'auth'])
    ->name('custom.upload');

Route::post('/custom-upload-multiple', [App\Http\Controllers\Admin\CustomUploadController::class, 'handleMultiple'])
    ->middleware(['web', 'auth'])
    ->name('custom.upload.multiple');

// Custom route for Livewire file uploads in Docker environments
Route::post('/livewire/upload-file', function () {
    return app(\Livewire\Features\SupportFileUploads\FileUploadController::class)->handle();
})->middleware(['web', 'auth'])->name('livewire.upload-file');

// Direct route for enhanced gallery uploads
Route::post('/enhanced-gallery/upload', [App\Http\Controllers\Admin\CustomUploadController::class, 'handle'])
    ->middleware(['web', 'auth'])
    ->name('enhanced-gallery.upload');

// Simple Gallery page
Route::get('/admin/simple-gallery', function () {
    return view('admin.simple-gallery-standalone');
})->middleware(['web', 'auth'])->name('admin.simple-gallery');
