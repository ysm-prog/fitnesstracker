<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // A silently ignored attribute is a validation gap that never announces
        // itself. Fail loudly in development instead.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Responses name their own top-level keys ('user', 'fitness_profile'),
        // so an extra 'data' envelope would only add a level to every path a
        // client has to walk.
        JsonResource::withoutWrapping();

        $this->configureRateLimits();
    }

    /**
     * Throttles are keyed on the credential as well as the address so that one
     * shared network cannot lock out an unrelated account, and one address
     * cannot spray attempts across many accounts.
     */
    private function configureRateLimits(): void
    {
        RateLimiter::for('auth', fn (Request $request) => [
            Limit::perMinute(5)->by($this->credentialKey($request)),
            Limit::perMinute(20)->by($request->ip() ?? 'unknown'),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perHour(3)->by($this->credentialKey($request)),
            Limit::perHour(10)->by($request->ip() ?? 'unknown'),
        ]);

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip() ?? 'unknown'));
    }

    private function credentialKey(Request $request): string
    {
        $email = (string) $request->input('email', '');

        return mb_strtolower(trim($email)).'|'.($request->ip() ?? 'unknown');
    }
}
