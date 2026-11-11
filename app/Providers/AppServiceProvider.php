<?php

namespace App\Providers;

use App\Database\NeonPostgresConnector;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Lets the live site reach Neon from hosts with an old PostgreSQL client library.
        $this->app->bind('db.connector.pgsql', NeonPostgresConnector::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Always generate https:// links when live, so assets and Google login work behind the proxy.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
