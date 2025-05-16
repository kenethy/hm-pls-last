<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\MechanicReport;
use App\Models\Promo;
use App\Models\Service;
use App\Policies\BlogPostPolicy;
use App\Policies\BookingPolicy;
use App\Policies\MechanicReportPolicy;
use App\Policies\PromoPolicy;
use App\Policies\ServicePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Service::class => ServicePolicy::class,
        Booking::class => BookingPolicy::class,
        BlogPost::class => BlogPostPolicy::class,
        Promo::class => PromoPolicy::class,
        MechanicReport::class => MechanicReportPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define gates here if needed
    }
}
