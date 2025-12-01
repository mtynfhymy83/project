<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\AuthServiceInterface;
use App\Services\AuthService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Auth Service
        $this->app->bind(AuthServiceInterface::class, AuthService::class);

        // سایر bindings...
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
