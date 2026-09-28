<?php

namespace App\Providers;

use App\Events\IncidentLogged;
use App\Listeners\DispatchIncidentNotifications;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        Event::listen(IncidentLogged::class, DispatchIncidentNotifications::class);

        // Rate limiter para endpoints de autenticación API (prevención de ataques de fuerza bruta)
        RateLimiter::for('api.auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Rate limiter para endpoints protegidos de la API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para endpoints públicos de consulta de estado
        RateLimiter::for('api.public', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });
    }
}
