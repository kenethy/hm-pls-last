<?php

namespace App\Providers\Filament;

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
                    </div>';
                }
            );
    }
}
