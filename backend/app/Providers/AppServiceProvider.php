<?php

namespace App\Providers;

use App\Contracts\SmsGateway;
use App\Services\Sms\ArkeselSmsGateway;
use App\Services\Sms\HubtelSmsGateway;
use App\Services\Sms\LogSmsGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGateway::class, function () {
            return match (config('sms.driver')) {
                'hubtel' => new HubtelSmsGateway(config('sms.hubtel')),
                'arkesel' => new ArkeselSmsGateway(config('sms.arkesel')),
                default => new LogSmsGateway(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('sms-webhook', function (Request $request) {
            return Limit::perMinute(240)->by($request->ip());
        });

        RateLimiter::for('pass-charge', function (Request $request) {
            return Limit::perMinute(6)->by($request->user()?->id ?: $request->ip());
        });

        if (config('app.force_https')) {
            URL::forceScheme('https');
        }
    }
}
