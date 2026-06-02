<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Repositories\Payment\PaymentRepository::class,
            \App\Repositories\Payment\PaymentRepositoryEloquent::class
        );

        $this->app->bind(
            \App\Repositories\PaymentMethod\PaymentMethodRepository::class,
            \App\Repositories\PaymentMethod\PaymentMethodRepositoryEloquent::class
        );

        // Bind other repositories here as they are created
        $this->app->bind(
            \App\Repositories\Job\JobRepositoryInterface::class,
            \App\Repositories\Job\JobRepository::class
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
