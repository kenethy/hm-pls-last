<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ActivityLogResource;
use App\Filament\Resources\BlogCategoryResource;
use App\Filament\Resources\BlogPostResource;
use App\Filament\Resources\BlogTagResource;
use App\Filament\Resources\BookingResource;
use App\Filament\Resources\CustomerResource;
use App\Filament\Resources\GalleryCategoryResource;
use App\Filament\Resources\GalleryResource;
use App\Filament\Resources\MechanicReportResource;
use App\Filament\Resources\MechanicResource;
use App\Filament\Resources\MembershipPointHistoryResource;
use App\Filament\Resources\MembershipResource;
use App\Filament\Resources\PromoResource;
use App\Filament\Resources\ServiceResource;
use App\Filament\Resources\EnhancedGalleryResource;
use App\Filament\Widgets\SimpleGalleryWidget;
use App\Filament\Widgets\EnhancedGalleryWidget;
use App\Filament\Widgets\PromoUploaderWidget;
use App\Filament\Pages\GalleryManager;
use App\Http\Middleware\CheckUserRole;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->resources([
                // Resources available to all users (both admin and staff)
                BookingResource::class,
                ServiceResource::class,
                MechanicReportResource::class,

                // Resources available only to admin users
                CustomerResource::class,
                MembershipResource::class,
                MembershipPointHistoryResource::class,
                MechanicResource::class,
                PromoResource::class,
                GalleryResource::class,
                EnhancedGalleryResource::class,
                GalleryCategoryResource::class,
                BlogPostResource::class,
                BlogCategoryResource::class,
                BlogTagResource::class,
                ActivityLogResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
                GalleryManager::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                SimpleGalleryWidget::class,
                EnhancedGalleryWidget::class,
                PromoUploaderWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // Kita akan menangani pembatasan akses dengan cara lain
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Servis & Booking'),
                NavigationGroup::make()
                    ->label('Konten Website'),
                NavigationGroup::make()
                    ->label('Galeri'),
                NavigationGroup::make()
                    ->label('Manajemen Pelanggan'),
            ])
            ->navigationItems([
                \Filament\Navigation\NavigationItem::make('Simple Gallery')
                    ->url(fn(): string => route('admin.simple-gallery'))
                    ->icon('heroicon-o-photo')
                    ->group('Konten Website')
                    ->sort(10),
                \Filament\Navigation\NavigationItem::make('Promo Uploader')
                    ->url(fn(): string => route('admin.promo-uploader'))
                    ->icon('heroicon-o-megaphone')
                    ->group('Konten Website')
                    ->sort(11),
            ])
            ->authGuard('web')
            ->renderHook(
                'panels::resource.pages.list-records.table.before',
                function () {
                    $user = Auth::user();
                    if ($user && $user->role === 'staff') {
                        return '<div class="p-4 mb-4 text-sm text-blue-800 rounded-lg bg-blue-50 dark:bg-gray-800 dark:text-blue-400" role="alert">
                            <span class="font-medium">Akses Terbatas!</span> Anda memiliki akses terbatas hanya untuk mengelola Servis dan Booking.
                        </div>';
                    }
                    return '';
                }
            )
            ->renderHook(
                'panels::global-search.end',
                function () {
                    return '<div class="px-4 py-2">
                        <a href="' . route('admin.simple-gallery') . '" class="flex items-center gap-2 text-sm text-gray-700 hover:text-primary-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>
                            Simple Gallery Manager
                        </a>
                    </div>
                    <div class="px-4 py-2">
                        <a href="' . route('admin.promo-uploader') . '" class="flex items-center gap-2 text-sm text-gray-700 hover:text-primary-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46" />
                            </svg>
                            Promo Uploader
                        </a>
                    </div>';
                }
            );
    }
}
