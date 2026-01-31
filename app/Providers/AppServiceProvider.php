<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
        URL::forceScheme('https');
        }

        // Fix for Vercel/Serverless (Read-only filesystem)
        // Maatwebsite Excel uses local storage for temp files by default.
        config(['excel.temporary_files.local_path' => sys_get_temp_dir()]);
    }
}
