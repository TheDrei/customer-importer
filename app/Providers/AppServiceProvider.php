<?php

namespace App\Providers;

use App\Services\Contracts\CustomerDataProviderInterface;
use App\Services\RandomUserDataProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CustomerDataProviderInterface::class, RandomUserDataProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
