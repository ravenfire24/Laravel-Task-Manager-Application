<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Paginator::useBootstrapFour();

        // The community PHP runtime's libpq needs Neon's endpoint passed explicitly.
        $url = config('database.connections.pgsql.url');
        $host = is_string($url) ? parse_url($url, PHP_URL_HOST) : null;
        if (getenv('VERCEL') === '1' && is_string($host) && str_ends_with($host, '.neon.tech') && ! getenv('PGOPTIONS')) {
            $endpoint = preg_replace('/-pooler$/', '', explode('.', $host)[0]);
            putenv('PGOPTIONS=endpoint='.$endpoint);
        }
    }
}
