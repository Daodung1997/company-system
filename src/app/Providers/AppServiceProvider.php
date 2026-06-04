<?php

namespace App\Providers;

use App\Exceptions\Handler;
use App\Supports\Components\Response\ResponseFormat;
use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandlerContract;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // We'll handle routes manually in our routes files
        //        Passport::ignoreRoutes();

        $this->app->singleton(ExceptionHandlerContract::class, Handler::class);
        $this->app->singleton('response', function () {
            return new ResponseFormat;
        });

        if ($this->app->isLocal()) {
            if (config('app.secure_state')) {
                URL::forceScheme('https');
            }
        }

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like auth()->user->can() and @can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });

        // Configure Passport
        // Don't hash client secrets for now to simplify debugging
        Passport::hashClientSecrets();
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(14));
        Passport::personalAccessTokensExpireIn(now()->addMonths(12));
        Passport::enablePasswordGrant();
    }
}
