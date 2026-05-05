<?php

namespace App\Providers;

use App\Policies\RolePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

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
        Gate::policy(Role::class, RolePolicy::class);

        RateLimiter::for('device-register', function (Request $request) {
            return Limit::perMinute((int) config('iptv.rate_limit_register_per_minute', 15))
                ->by($request->ip());
        });

        RateLimiter::for('device-session', function (Request $request) {
            $deviceId = (string) $request->input('device_id', '');

            return Limit::perMinute((int) config('iptv.rate_limit_session_per_minute', 40))
                ->by(hash('sha256', $request->ip() . '::' . $deviceId));
        });
    }
}
