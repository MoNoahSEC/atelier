<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use App\Contracts\TaxCalculator;
use App\Services\Tax\FlatRateTaxCalculator;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the TaxCalculator contract to the flat-rate implementation.
        // Swap to a tax provider adapter (Avalara, TaxJar) by changing this binding.
        $this->app->singleton(TaxCalculator::class, FlatRateTaxCalculator::class);

        // Bind Payment and Shipping adapters
        $this->app->singleton(\App\Contracts\PaymentProvider::class, \App\Services\Payment\MockPaymentProvider::class);
        $this->app->singleton(\App\Contracts\ShippingProvider::class, \App\Services\Shipping\FlatRateShippingProvider::class);
    }

    public function boot(): void
    {
        RateLimiter::for('automation', function (Request $request) {
            return Limit::perMinute(config('automation.rate_limits.automation', 120))
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('webhooks', function (Request $request) {
            return Limit::perMinute(config('automation.rate_limits.webhooks', 240))
                ->by($request->ip());
        });
        // Disable lazy loading in non-production to surface N+1 query issues early.
        // This throws an exception in local/testing environments — not in production.
        Model::preventLazyLoading(! app()->isProduction());

        // Prevent silently discarding attributes not in fillable during mass-assignment.
        // Only enforced in non-production to avoid noisy production failures on old code.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        // Register authorization policies
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Order::class, \App\Policies\OrderPolicy::class);
        \Illuminate\Support\Facades\Gate::policy(\App\Models\Address::class, \App\Policies\AddressPolicy::class);

        // Use UTC for all DB datetime operations
        // (APP_TIMEZONE should be UTC in .env)
    }
}
