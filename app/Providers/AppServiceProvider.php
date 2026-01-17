<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom([
            database_path('migrations/000_auth'),
            database_path('migrations/100_inventory'),
            database_path('migrations/200_purchasing'),
            database_path('migrations/300_sales'),
            database_path('migrations/400_payments'),
            database_path('migrations/500_finance'),
        ]);
    }
}

