<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
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
        foreach (['academic-submit' => 12, 'academic-draft' => 60, 'academic-assistance' => 20, 'report-export' => 30] as $name => $attempts) {
            RateLimiter::for($name, fn (Request $request): Limit => Limit::perMinute($attempts)->by((string) $request->user()?->getAuthIdentifier()));
        }

        RateLimiter::for('login', function (Request $request): Limit {
            $username = $request->input('username');
            $identity = is_string($username) ? Str::lower(trim($username)) : '';

            return Limit::perMinute(5)->by(hash('sha256', $identity.'|'.$request->ip()));
        });
    }
}
