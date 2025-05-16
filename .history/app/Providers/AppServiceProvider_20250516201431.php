<?php

namespace App\Providers;

use App\Models\BlogPost;
use App\Models\Booking;
use App\Models\MechanicReport;
use App\Models\Promo;
use App\Models\Service;
use App\Observers\ActivityLogObserver;
use App\Observers\MechanicServiceObserver;
use App\Observers\ServiceObserver;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Force HTTPS always
        URL::forceScheme('https');

        // Set asset URL to HTTPS
        $this->app['url']->assetUrl = function ($root, $path, $secure = null) {
            // Ignore unused parameters
            unset($root, $secure);
            return url($path, [], true);
        };

        // Add custom URL signature validation for multi-domain support
        Request::macro('hasValidSignature', function ($absolute = true, array $ignoreQuery = []) {
            return URL::hasValidSignature($this, $absolute, $ignoreQuery);
        });

        URL::macro('hasValidSignature', function (Request $request, $absolute = true, array $ignoreQuery = []) {
            $ignoreQuery[] = 'signature';

            $url = $absolute ? $request->url() : '/' . $request->path();

            $queryString = collect(explode('&', (string) $request->server->get('QUERY_STRING')))
                ->reject(fn($parameter) => in_array(Str::before($parameter, '='), $ignoreQuery))
                ->join('&');

            $original = rtrim($url . '?' . $queryString, '?');

            $signature = hash_hmac('sha256', $original, config('app.key'));

            return hash_equals($signature, (string) $request->query('signature', '')) &&
                !URL::signatureHasExpired($request);
        });

        // Register observers
        Service::observe(ServiceObserver::class);
        Pivot::observe(MechanicServiceObserver::class);
    }
}
