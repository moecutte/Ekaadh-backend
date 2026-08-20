<?php

namespace App\Providers;

use App\Services\Payments\EDahabGateway;
use App\Services\Payments\MockGateway;
use App\Services\Payments\PaymentGatewayInterface;
use App\Services\Payments\WaafiPayGateway;
use App\Services\Payments\ZaadGateway;
use App\Support\ProductionGuard;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayInterface::class, function () {
            $gateway = (string) config('services.payment.gateway', 'waafipay');

            if ($gateway === 'mock'
                && app()->environment('production')
                && ! (bool) config('services.payment.allow_mock')) {
                throw new \RuntimeException('PAYMENT_GATEWAY=mock is not allowed in production. Set PAYMENT_GATEWAY=waafipay.');
            }

            return match ($gateway) {
                'zaad' => new ZaadGateway,
                'edahab' => new EDahabGateway,
                'mock' => new MockGateway,
                default => $this->app->make(WaafiPayGateway::class),
            };
        });
    }

    public function boot(): void
    {
        ProductionGuard::assert();

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(8)->by($request->ip());
        });

        RateLimiter::for('checkout', function (Request $request) {
            $phone = (string) $request->input('buyer_phone', $request->input('phone', ''));

            return Limit::perMinute(12)->by($request->ip().'|'.$phone);
        });

        RateLimiter::for('check-in', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('tickets', function (Request $request) {
            return Limit::perMinute(30)->by($request->ip());
        });

        RateLimiter::for('support', function (Request $request) {
            return Limit::perMinute(40)->by($request->user()?->id ?: $request->ip());
        });
    }
}
