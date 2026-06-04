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
        // Bind other repositories here as they are created
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
