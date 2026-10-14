<?php

namespace App\Providers;

use App\Database\VerifiedPostgresConnector;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind('db.connector.pgsql', VerifiedPostgresConnector::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFour();

        $this->registerTaskRateLimits();
        $this->configurePostgresEndpoint();
    }

    private function registerTaskRateLimits(): void
    {
        RateLimiter::for('task-reads', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
        RateLimiter::for('task-writes', fn (Request $request) => Limit::perMinute(30)->by($request->ip()));
    }

    private function configurePostgresEndpoint(): void
    {
        // The community PHP runtime's libpq needs Neon's endpoint passed explicitly.
        $url = config('database.connections.pgsql.url');
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        if (getenv('VERCEL') === '1' && is_string($host) && str_ends_with($host, '.neon.tech') && ! getenv('PGOPTIONS')) {
            $endpoint = preg_replace('/-pooler$/', '', explode('.', $host)[0]);
            putenv('PGOPTIONS=endpoint='.$endpoint);
        }
    }
}
