<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

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
        // The migration repository is installed before any migration's up() runs.
        // Prepare the private schema first, using the migration role (never the web role).
        if ($this->app->runningInConsole()
            && in_array($_SERVER['argv'][1] ?? '', ['migrate', 'migrate:fresh', 'migrate:refresh', 'migrate:install', 'migrate:reset', 'migrate:rollback'], true)
            && config('database.default') === 'pgsql') {
            DB::statement('CREATE SCHEMA IF NOT EXISTS app');
        }
    }
}
